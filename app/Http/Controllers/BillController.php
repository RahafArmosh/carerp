<?php

namespace App\Http\Controllers;

use App\Exports\BillExport;
use App\Exports\BillProductExport;
use App\Imports\BillImport;
use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\BillAccount;
use App\Models\BillPayment;
use App\Models\BillProduct;
use App\Models\Payment;
use App\Models\ChartOfAccount;
use App\Models\CustomField;
use App\Models\DebitNote;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use App\Models\ProductServiceUnit;
use App\Models\Brand;
use App\Models\VehicleModel;
use App\Models\StockReport;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Utility;
use App\Models\Vender;
use App\Models\SubProduct;
use App\Models\CustomFieldValue;
use App\Models\Tax;
use App\Models\Currency;
use App\Models\Color;
use App\Models\Purchase;
use App\Models\PurchaseProduct;
use App\Models\WarehouseProduct;
use App\Models\warehouse;
use App\Models\TransactionLines;
use App\Models\BillStatusChange;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\GeneralLedger;
use App\Models\AccountingDocument;
use App\Models\StockMovement;
use App\Models\DirectExpenseItem;
use App\Models\CarAccessoryRequestItem;
use App\Models\CarAccessoryRequest;
use App\Models\AsnBill;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\Jobs\ImportBillFromExcelJob;
use App\Models\MasterlistLeadger;
use \Cache;
use Illuminate\Support\Carbon;


function generateNumericProductCode($mainProductId, $customFields)
{
    ksort($customFields);
    $combined = implode('-', $customFields);
    $hash = abs(crc32($combined));
    return (int) ($mainProductId . str_pad($hash, 8, '0', STR_PAD_LEFT));
}


class BillController extends Controller
{

    public function index(Request $request)
    {

        if (\Auth::user()->can('approve bill')) {

            $vender = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $vender->prepend('Select Vendor', '');

            $status = array_filter(Bill::$statues);
            $paymentstatues = array_filter(Bill::$paymentstatues);

            $query = Bill::where('type', '=', 'Bill')->where('created_by', '=', \Auth::user()->creatorId());
            if ($request->filled('vender_id')) {
                $query->where('vender_id', '=', $request->vender_id);
            }
            if (count(explode('to', $request->bill_date)) > 1) {
                $date_range = explode(' to ', $request->bill_date);
                $query->whereBetween('bill_date', $date_range);
            } elseif ($request->filled('bill_date')) {
                $date_range = [$request->date, $request->bill_date];
                $query->whereBetween('bill_date', $date_range);
            }

            if ($request->filled('status')) {
                $query->where('status', '=', $request->status);
            }
            if ($request->filled('paymentstatues')) {
                $query->where('payment_status', '=', $request->paymentstatues);
            }
            $bills = $query->with('category')->orderBy('id', 'desc')->get();

            return view('bill.index', compact('bills', 'vender', 'status', 'paymentstatues'));
        } elseif (\Auth::user()->can('manage bill')) {

            $vender = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $vender->prepend('Select Vendor', '');

            $status = array_filter(Bill::$statues, function ($value) {
                return $value !== 'Send To Approve' && !empty($value);
            });
            $paymentstatues = array_filter(Bill::$paymentstatues);

            $query = Bill::where('type', '=', 'Bill')->where('created_by', '=', \Auth::user()->creatorId());
            if ($request->filled('vender_id')) {
                $query->where('vender_id', '=', $request->vender_id);
            }
            if (count(explode('to', $request->bill_date)) > 1) {
                $date_range = explode(' to ', $request->bill_date);
                $query->whereBetween('bill_date', $date_range);
            } elseif ($request->filled('bill_date')) {
                $date_range = [$request->date, $request->bill_date];
                $query->whereBetween('bill_date', $date_range);
            }

            if ($request->filled('status')) {
                $query->where('status', '=', $request->status);
            }
            if ($request->filled('paymentstatues')) {
                $query->where('payment_status', '=', $request->paymentstatues);
            }
            $bills = $query->where('status', '<>', 1)->with('category')->orderBy('id', 'desc')->get();

            return view('bill.index', compact('bills', 'vender', 'status', 'paymentstatues'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function create($vendorId)
    {

        if (\Auth::user()->can('create bill')) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'bill')->get();
            $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            $bill_number = \Auth::user()->billNumberFormat($this->billNumber());
            $bill_numberNo = $this->billNumber();

            $venders = Vender::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $venders->prepend('Select Vender', '');

            $product_services = ProductService::where('created_by', \Auth::user()->creatorId())->with(['brand', 'subBrand', 'category'])
                ->get()
                ->map(function ($productService) {
                    $category = $productService->category->name ?? '';
                    $brand = $productService->brand->name ?? '';
                    $subBrand = $productService->subBrand->name ?? '';
                    $productName = $productService->name;
                    $productCode = $productService->sku;

                    return [
                        'id' => $productService->id,
                        'name' => $category . '/' . $brand . '/' . $subBrand . '/' . $productName . '/' . $productCode,
                    ];
                })
                ->pluck('name', 'id');
            $product_services->prepend('Select Item', '');

            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');

            $fullTax = Tax::where('created_by', '=', \Auth::user()->creatorId())->get();
            $warehouse = warehouse::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $currency = Currency::get()->pluck('name', 'id');
            $customFieldsProducts = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'product')->get();

            $users = User::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            return view('bill.create', compact('venders', 'bill_number', 'product_services', 'category', 'customFields', 'vendorId', 'chartAccounts', 'fullTax', 'warehouse', 'currency', 'customFieldsProducts', 'users', 'bill_numberNo'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    /**
     * Create bill from car accessory request - NEW METHOD
     */
    public function createFromRequest(Request $request, $vendorId)
    {
        if (\Auth::user()->can('create bill')) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'bill')->get();
            $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');

            $bill_number = \Auth::user()->billNumberFormat($this->billNumber());
            $bill_numberNo = $this->billNumber();

            $venders = Vender::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $venders->prepend('Select Vender', '');

            $product_services = ProductService::where('created_by', \Auth::user()->creatorId())->with(['brand', 'subBrand', 'category'])
                ->get()
                ->map(function ($productService) {
                    $category = $productService->category->name ?? '';
                    $brand = $productService->brand->name ?? '';
                    $subBrand = $productService->subBrand->name ?? '';
                    $productName = $productService->name;
                    $productCode = $productService->sku;

                    return [
                        'id' => $productService->id,
                        'name' => $category . '/' . $brand . '/' . $subBrand . '/' . $productName . '/' . $productCode,
                    ];
                })
                ->pluck('name', 'id');
            $product_services->prepend('Select Item', '');

            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');

            $fullTax = Tax::where('created_by', '=', \Auth::user()->creatorId())->get();
            $warehouse = warehouse::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $warehouse->prepend('Select Warehouse', '');
            $currency = Currency::get()->pluck('name', 'id');
            $customFieldsProducts = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'product')->get();

            $users = User::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $users->prepend('Select User', '');

            // Get request items from the request parameters
            $requestItems = [];
            if ($request->has('items')) {
                foreach ($request->items as $productId => $quantity) {
                    $product = ProductService::find($productId);
                    if ($product) {
                        $requestItems[] = [
                            'product_id' => $productId,
                            'quantity' => $quantity,
                            'price' => $product->purchase_price ?? 0,
                            'name' => $product->name
                        ];
                    }
                }
            }

            return view('bill.create_from_request', compact(
                'venders',
                'bill_number',
                'product_services',
                'category',
                'customFields',
                'vendorId',
                'chartAccounts',
                'fullTax',
                'warehouse',
                'currency',
                'customFieldsProducts',
                'users',
                'bill_numberNo',
                'requestItems'
            ));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }


    public function store(Request $request)
    {
        // dd($request);

        if (\Auth::user()->can('create bill')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'vender_id' => 'required',
                    'warehouse_id' => 'required',
                    'bill_date' => 'required',
                    'due_date' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages3 = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages3->first())->withInput();
            }

            if (empty($request->items)) {
                \Log::warning('No items found in request');
                return redirect()->back()->with('error', __('At least one item is required.'));
            }

            // Check if items have the required fields
            foreach ($request->items as $index => $item) {
                if (empty($item['item']) || empty($item['quantity']) || !isset($item['price'])) {
                    \Log::warning('Invalid item data', ['index' => $index, 'item' => $item]);
                    return redirect()->back()->with('error', __('All items must have product, quantity, and price.'));
                }

                // Set default values for missing fields
                if (!isset($item['WareHousePrice'])) {
                    $item['WareHousePrice'] = $item['price']; // Use the price as warehouse price if not specified
                }
                if (!isset($item['discount'])) {
                    $item['discount'] = 0; // Default discount to 0
                }
                if (!isset($item['description'])) {
                    $item['description'] = ''; // Default description to empty string
                }
                if (!isset($item['custom_fields'])) {
                    $item['custom_fields'] = []; // Default custom fields to empty array
                }
            }

            try {
                // dd($request->items);
                DB::beginTransaction();

                $bill = new Bill();
                // Generate bill ID - use request value or generate new one
                $billId = $request->bill_numberNo ?? $this->billNumber();

                // Ensure bill ID is not null and is a valid value
                if (empty($billId) || is_null($billId)) {

                    // Fallback: generate a new bill number
                    $fallbackBillId = $this->billNumber();
                    if (empty($fallbackBillId) || is_null($fallbackBillId)) {
                        // Last resort: use timestamp
                        $fallbackBillId = 'BILL_' . time();
                    }
                    $billId = $fallbackBillId;
                }


                // Ensure bill ID is a string (as per database schema)
                $bill->bill_id = (string) $billId;
                $bill->vender_id = $request->vender_id;
                $bill->bill_date = $request->bill_date;
                $bill->status = 0;
                $bill->type = 'Bill';
                $bill->user_type = 'vendor';
                $bill->due_date = $request->due_date;
                $bill->warehouse_id = !empty($request->warehouse_id) ? $request->warehouse_id : 0;
                $bill->category_id = !empty($request->category_id) ? $request->category_id : 0;
                $bill->order_number = !empty($request->order_number) ? $request->order_number : '';
                $bill->created_by = \Auth::user()->creatorId();
                $bill->salesman_id = !empty($request->salesman_id) ? $request->salesman_id : \Auth::user()->creatorId();
                $bill->tax_id = !empty($request->tax_id) ? implode(',', $request->tax_id) : '';
                $bill->currency_id = $request->currency_id ?? null;
                if ($request->filled('currency_id')) {
                    if ($request->filled('exchange_rate')) {
                        $bill->exchange_rate = $request->exchange_rate;
                    } else {
                        $currency = \App\Models\Currency::find($request->currency_id);
                        $bill->exchange_rate = $currency ? $currency->rate : 0;
                    }
                } else {
                    $bill->exchange_rate = 0;
                }
                $bill->save();
                CustomField::saveData($bill, $request->customField);
                $statusChange = new BillStatusChange();
                $statusChange->bill_id = $bill->id;
                $statusChange->status = 0;
                $statusChange->payment_status = -1;
                $statusChange->changed_at = now();
                $statusChange->save();
                if ($request->hasFile('documents')) {
                    $documents = $request->file('documents');
                    foreach ($documents as $document) {
                        $filenameWithExt = $document->getClientOriginalName();
                        $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                        $extension = $document->getClientOriginalExtension();
                        $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                        $document->move(public_path('documents'), $fileNameToStore);
                        $accountDocument = new AccountingDocument();
                        $accountDocument->document_name = $filenameWithExt;
                        $accountDocument->document_path = 'documents/' . $fileNameToStore;;
                        $accountDocument->bill_id = $bill->id;
                        $accountDocument->save();
                    }
                }
                $products = $request->items;

                for ($i = 0; $i < count($products); $i++) {

                    if (!empty($products[$i]['item'])) {
                        // Check if 'custom_fields' already exists, if not, initialize it as an empty array
                        if (!isset($products[$i]['custom_fields'])) {
                            $products[$i]['custom_fields'] = [];
                        }
                        $product = ProductService::find($products[$i]['item']);
                        $product->quantity += $products[$i]['quantity'];
                        $product->save();
                        $isWarehouseProduct = null;
                        // if (!empty($request->warehouse_id)) {
                        //     $isWarehouseProduct = WarehouseProduct::where('product_id', $products[$i]['item'])->where('warehouse_id', $request->warehouse_id)->first();
                        //     // dd($isWarehouseProduct);
                        //     if ($isWarehouseProduct != null) {
                        //         $isWarehouseProduct->quantity += $products[$i]['quantity'];
                        //         $isWarehouseProduct->created_by = \Auth::user()->creatorId();
                        //         $isWarehouseProduct->sale_price = $products[$i]['WareHousePrice'];
                        //         // WareHousePricekc
                        //         $isWarehouseProduct->save();
                        //     } else {
                        //         $transfer = new WarehouseProduct();
                        //         $transfer->warehouse_id = $request->warehouse_id;
                        //         $transfer->product_id = $products[$i]['item'];
                        //         $transfer->quantity = $products[$i]['quantity'];
                        //         $transfer->created_by = \Auth::user()->creatorId();
                        //         $transfer->sale_price = $products[$i]['WareHousePrice'];
                        //         $transfer->save();
                        //     }
                        // }
                        // Initialize pnum variable for both product types
                        $pnum = null;

                        if (ProductServiceCategory::where('id', $product->category->id)->first()->type === "product") {
                            for ($j = 0; $j < $products[$i]['quantity']; $j++) {
                                $subProduct = new SubProduct();
                                $subProduct->product_id = $products[$i]['item'];
                                $subProduct->sale_price = $product->sale_price;
                                $subProduct->quantity = 1;
                                $price = $products[$i]['price'];
                                $discount_price = isset($products[$i]['discount']) ? $products[$i]['discount'] : 0;
                                $bill_price = $products[$i]['price'];

                                if (!empty($request->currency_id)) {
                                    $exchangeRate = $request->exchange_rate
                                        ?? optional(Currency::find($request->currency_id))->exchange_rate;

                                    if ($exchangeRate) {
                                        $subProduct->purchase_price = ($price - $discount_price) * $exchangeRate;
                                        $bill_price = $price * $exchangeRate;
                                        $discount_price = $discount_price * $exchangeRate;
                                    } else {
                                        $subProduct->purchase_price = $price;
                                        $bill_price = $price;
                                    }
                                } else {
                                    $subProduct->purchase_price = $price;
                                }
                                $subProduct->created_by = \Auth::user()->creatorId();
                                $subProduct->flag = 0;
                                $subProduct->bill_id = $bill->id;
                                $subProduct->warehouse_id = !empty($request->warehouse_id) ? $request->warehouse_id : 0;
                                $subProduct->save();
                                MasterlistLeadger::addFree($subProduct->productService->id,$request->warehouse_id,1,'BILL',$bill->id,\Auth::user()->creatorId());

                                $billProduct = new BillProduct();
                                $billProduct->bill_id = $bill->id;
                                $billProduct->product_id = $products[$i]['item'];
                                $billProduct->sub_product_id = $subProduct->id;
                                $billProduct->quantity = 1;
                                $billProduct->tax = !empty($request->tax_id) ? implode(',', $request->tax_id) : null;
                                $billProduct->discount = $discount_price;
                                $billProduct->price = $bill_price;
                                $billProduct->exchange_price = $products[$i]['price'];
                                $billProduct->exchange_discount = isset($products[$i]['discount']) ? $products[$i]['discount'] : 0;
                                $billProduct->description = isset($products[$i]['description']) ? $products[$i]['description'] : '';
                                $billProduct->save();
                                $subProductId = $subProduct->id;
                                if (!empty($products[$i]['custom_fields'])) {
                                    foreach ($products[$i]['custom_fields'] as $fieldName => $fieldValue) {
                                        // Find the corresponding custom field by name
                                        $categoryId = ProductServiceCategory::where('id', $product->category->id)->first()->id;
                                        $customField = CustomField::where('id', $fieldName)->where('module', 'sub-product')->forCategory($categoryId)->where('created_by', \Auth::user()->creatorId())->first();
                                        if ($customField) {
                                            // Create a new custom field value
                                            $customFieldValue = new CustomFieldValue();
                                            $customFieldValue->record_id = $subProductId;
                                            $customFieldValue->field_id = $customField->id;
                                            $customFieldValue->value = $fieldValue;

                                            $customFieldValue->save();
                                        } else {
                                            \Log::warning("Custom field with name '{$fieldName}' not found.");
                                        }
                                    }
                                }
                            }
                        } elseif (ProductServiceCategory::where('id', $product->category->id)->first()->type === "Qty product") {
                            // Ensure custom_fields is an array before passing to generateNumericProductCode
                            $customFields = is_array($products[$i]['custom_fields']) ? $products[$i]['custom_fields'] : [];
                            $pnum = generateNumericProductCode($products[$i]['item'], $customFields);

                            $subProduct = new SubProduct();
                            $subProduct->product_id = $products[$i]['item'];
                            // Use warehouse price if available, otherwise use the product's sale price
                            if (isset($products[$i]['WareHousePrice']) && !empty($products[$i]['WareHousePrice'])) {
                                $subProduct->sale_price = $products[$i]['WareHousePrice'];
                            } else {
                                $subProduct->sale_price = $product->sale_price ?? 0;
                            }
                            $subProduct->quantity = $products[$i]['quantity'];
                            $discount = isset($products[$i]['discount']) ? $products[$i]['discount'] : 0;
                            $price = $products[$i]['price'] * $products[$i]['quantity'] - $discount;

                            $subProduct->chassis_no = $pnum;
                            if (!empty($request->currency_id)) {
                                $exchangeRate = $request->exchange_rate
                                    ?? optional(Currency::find($request->currency_id))->exchange_rate;

                                if ($exchangeRate) {
                                    $subProduct->purchase_price = $price * $exchangeRate;
                                } else {
                                    $subProduct->purchase_price = $price; // fallback if exchange rate not found
                                }
                            } else {
                                $subProduct->purchase_price = $price;
                            }
                            $subProduct->created_by = \Auth::user()->creatorId();
                            $subProduct->flag = 0;
                            $subProduct->bill_id = $bill->id;
                            $subProduct->warehouse_id = !empty($request->warehouse_id) ? $request->warehouse_id : 0;
                            $subProduct->save();
                            MasterlistLeadger::addFree($subProduct->productService->id,$request->warehouse_id,$products[$i]['quantity'],'BILL',$bill->id,\Auth::user()->creatorId());

                            $billProduct = new BillProduct();
                            $billProduct->bill_id = $bill->id;
                            $billProduct->product_id = $products[$i]['item'];
                            $billProduct->sub_product_id = $subProduct->id;
                            $billProduct->quantity = $products[$i]['quantity'];
                            $billProduct->tax = !empty($request->tax_id) ? implode(',', $request->tax_id) : null;
                            $billProduct->discount = isset($products[$i]['discount']) ? $products[$i]['discount'] : 0;
                            $billProduct->price = $products[$i]['price'];
                            $billProduct->description = isset($products[$i]['description']) ? $products[$i]['description'] : '';
                            $billProduct->save();
                            $subProductId = $subProduct->id;

                            # POS Editing for the sub_product not the product 
                            $warehouse_product = new WarehouseProduct();
                            $warehouse_product->warehouse_id = $request->warehouse_id;
                            $warehouse_product->product_id = $subProduct->id;
                            $warehouse_product->quantity = $billProduct->quantity;
                            // Use warehouse price if available, otherwise use the product's sale price
                            if (isset($products[$i]['WareHousePrice']) && !empty($products[$i]['WareHousePrice'])) {
                                $warehouse_product->sale_price = $products[$i]['WareHousePrice'];
                            } else {
                                $warehouse_product->sale_price = $product->sale_price ?? 0;
                            }
                            $warehouse_product->product_num = $pnum ?? '';
                            $warehouse_product->save();


                            if (!empty($products[$i]['custom_fields'])) {

                                foreach ($products[$i]['custom_fields'] as $fieldName => $fieldValue) {
                                    // Find the corresponding custom field by name
                                    $categoryId = ProductServiceCategory::where('id', $product->category->id)->first()->id;
                                    $customField = CustomField::where('id', $fieldName)->where('module', 'sub-product')->forCategory($categoryId)->where('created_by', \Auth::user()->creatorId())->first();

                                    if ($customField) {
                                        // Create a new custom field value
                                        $customFieldValue = new CustomFieldValue();
                                        $customFieldValue->record_id = $subProductId;
                                        $customFieldValue->field_id = $customField->id;
                                        $customFieldValue->value = $fieldValue;

                                        $customFieldValue->save();
                                    } else {
                                        \Log::warning("Custom field with name '{$fieldName}' not found.");
                                    }
                                }
                            }
                        }
                    }
                }

                DB::commit();
                return redirect()->route('api.addSubProducts', $bill->id)->with('success', __('Bill successfully created.'));
            } catch (\Exception $e) {
                DB::rollBack();

                return redirect()->back()->with('error', $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    function venderNumber()
    {
        $latest = Vender::where('created_by', '=', \Auth::user()->creatorId())->latest()->first();
        if (!$latest) {
            return 1;
        }
        return $latest->customer_id + 1;
    }

    public function show($ids)
    {

        if (!\Auth::user()->can('show bill')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $id = Crypt::decrypt($ids);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Bill Not Found.'));
        }

        // Eager load all necessary relationships with pagination
        $bill = Bill::with([
            'debitNote',
            'payments.bankAccount',
            'refunds.currency',
            'refunds.bankAccount',
            'asnBills.asn',
            'items.product.unit' => function ($query) {
                $query->select('id', 'name');
            },
            'items.product.brand' => function ($query) {
                $query->select('id', 'name');
            },
            'items.product.subBrand' => function ($query) {
                $query->select('id', 'name');
            },
            'items.subProduct.productService.category' => function ($query) {
                $query->select('id', 'type', 'purchase_account_id');
            },
            'statusChanges' => function ($query) {
                $query->select('id', 'bill_id', 'status', 'created_at');
            },
            'items.taxObject'
        ])->find($id);

        if (empty($bill) || $bill->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        // Paginate items to handle large datasets
        $paginatedItems = $bill->items()->with([
            'subProduct' => function ($query) {
                $query->select('id', 'chassis_no', 'purchase_price', 'product_id');
            }
        ])->paginate(50);

        // Prepare items data with accounts
        $items = [];
        $accounts = $bill->accounts->keyBy('id'); // Key accounts by ID for faster lookup

        foreach ($paginatedItems as $k => $val) {
            $itemData = $val->toArray();

            if (isset($accounts[$k])) {
                $itemData['chart_account_id'] = $accounts[$k]['chart_account_id'];
                $itemData['account_id'] = $accounts[$k]['id'];
                $itemData['amount'] = $accounts[$k]['price'];
            }

            $itemData['bill_tax'] = $bill->tax_id;
            $items[] = $itemData;
        }

        // If no regular items, use accounts data
        if (empty($items)) {
            foreach ($bill->accounts as $k => $val) {
                $items[] = [
                    'chart_account_id' => $val['chart_account_id'],
                    'account_id' => $val['id'],
                    'amount' => $val['price'],
                    'bill_tax' => $bill->tax_id
                ];
            }
        }

        // Get status changes in single queries
        $statusChanges = [
            'approve' => $bill->statusChanges->where('status', 2)->first(),
            'send' => $bill->statusChanges->where('status', 4)->first(),
            'received' => $bill->statusChanges->where('status', 6)->first(),
            'sendToApprove' => $bill->statusChanges->where('status', 1)->first()
        ];

        // Cache custom fields data
        $bill->customField = CustomField::getData($bill, 'bill');
        $customFields = \Cache::remember('custom_fields_bill_' . auth()->id(), now()->addHours(1), function () {
            return CustomField::where('created_by', auth()->id())
                ->where('module', 'bill')
                ->get();
        });
        $statusChangesApprove = $bill->statusChanges()->where('status', 2)->first();
        $statusChangesSend = $bill->statusChanges()->where('status', 4)->first();
        $statusChangesReceived = $bill->statusChanges()->where('status', 6)->first();
        $statusChangesSendToApprove = $bill->statusChanges()->where('status', 1)->first();
        $totalQuantity = $bill->items->sum('quantity');
        $totalRate = $bill->currency_id != null ? $paginatedItems->sum('exchange_price') : $paginatedItems->sum('price');
        $totalPrice = 0;
        $totalTaxPrice = 0;
        // $totalDiscount = 0;

        foreach ($bill->items as $item) {
            $qty = $item->quantity ?? 0;
            $price = $bill->currency_id != null ? $item->exchange_price : $item->price ?? 0;

            // If discount is stored as a fixed amount
            $discount = $bill->currency_id != null ? $item->exchange_discount : $item->discount ?? 0;

            // Get tax rate from Tax model via relation
            $taxRate = $item->taxObject->rate ?? 0;

            $lineSubtotal = $qty * $price;
            $taxAmount = ($lineSubtotal - ($discount * $qty)) * ($taxRate / 100);
            $lineTotal = $lineSubtotal + $taxAmount - $discount;

            $totalPrice += $lineTotal;
            $totalTaxPrice += $taxAmount;
            // $totalDiscount += $discount;
        }
        $subTotal = $bill->currency_id != null ? $bill->getSubTotalExchange() : $bill->getSubTotal();
        $totalDiscount = $bill->currency_id != null ? $bill->getTotalDiscountExchange() : $bill->getTotalDiscount();
        $totalTaxPrice = $bill->currency_id != null ? $bill->getTotalTaxExchange() : $bill->getTotalTax();
        $total = $bill->currency_id != null ? $bill->getTotalExchange() : $bill->getTotal();
        $due = $bill->currency_id != null ? $bill->getDueExchange() : $bill->getDue();
        $debitNoteTotal = $bill->currency_id != null ? $bill->billTotalDebitNoteExchange() : $bill->billTotalDebitNote();
        $refundTotal = $bill->currency_id != null ? $bill->billTotalRefundExchange() : $bill->billTotalRefund();
        $paidAmount = $total - $due - $debitNoteTotal;
        
        // Get request numbers for items that are accessories from car_accessory_request_items
        $subProductIds = $bill->items->pluck('sub_product_id')->filter()->unique()->toArray();
        $requestNumbers = [];
        if (!empty($subProductIds)) {
            $requestItems = CarAccessoryRequestItem::whereIn('accessory_id', $subProductIds)
                ->with('request')
                ->get();
            
            foreach ($requestItems as $requestItem) {
                if ($requestItem->request && $requestItem->request->request_no) {
                    $requestNumbers[] = $requestItem->request->request_no;
                }
            }
            // Remove duplicates
            $requestNumbers = array_unique($requestNumbers);
        }

        // ASN numbers connected to this bill
        $asnNumbers = [];
        foreach ($bill->asnBills as $asnBill) {
            if ($asnBill->asn) {
                $asnNumbers[] = \Auth::user()->asnNumberFormat($asnBill->asn->asn_no);
            }
        }
        $asnNumbers = array_unique($asnNumbers);
        
        return view('bill.view', [
            'bill' => $bill,
            'vendor' => $bill->vender,
            'items' => $items,
            'asnNumbers' => $asnNumbers,
            'paginatedItems' => $paginatedItems,
            'billPayment' => $bill->payments->first(),
            'customFields' => $customFields,
            'subProducts' => $bill->subProducts,
            'statusChanges' => $statusChanges,
            'statusChangesApprove' => $statusChangesApprove,
            'statusChangesSend' => $statusChangesSend,
            'statusChangesReceived' => $statusChangesReceived,
            'statusChangesSendToApprove' => $statusChangesSendToApprove,
            'totalQuantity' => $totalQuantity,
            'totalRate' => $totalRate,
            'totalPrice' => $totalPrice,
            'totalTaxPrice' => $totalTaxPrice,
            'requestNumbers' => $requestNumbers,
            // 'totalDiscount' => $totalDiscount,
            'subTotal' => $subTotal,
            'totalDiscount' => $totalDiscount,
            'total' => $total,
            'due' => $due,
            'debitNoteTotal' => $debitNoteTotal,
            'refundTotal' => $refundTotal,
            'paidAmount' => $paidAmount
        ]);
    }

    public function show2($id)
    {

        if (!\Auth::user()->can('show bill')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }


        // Eager load all necessary relationships with pagination
        $bill = Bill::with([
            'debitNote',
            'payments.bankAccount',
            'refunds.currency',
            'refunds.bankAccount',
            'asnBills.asn',
            'items.product.unit' => function ($query) {
                $query->select('id', 'name');
            },
            'items.product.brand' => function ($query) {
                $query->select('id', 'name');
            },
            'items.product.subBrand' => function ($query) {
                $query->select('id', 'name');
            },
            'items.subProduct.productService.category' => function ($query) {
                $query->select('id', 'type', 'purchase_account_id');
            },
            'statusChanges' => function ($query) {
                $query->select('id', 'bill_id', 'status', 'created_at');
            },
            'items.taxObject'
        ])->find($id);

        if (empty($bill) || $bill->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        // Paginate items to handle large datasets
        $paginatedItems = $bill->items()->with([
            'subProduct' => function ($query) {
                $query->select('id', 'chassis_no', 'purchase_price', 'product_id');
            }
        ])->paginate(50);

        // Prepare items data with accounts
        $items = [];
        $accounts = $bill->accounts->keyBy('id'); // Key accounts by ID for faster lookup

        foreach ($paginatedItems as $k => $val) {
            $itemData = $val->toArray();

            if (isset($accounts[$k])) {
                $itemData['chart_account_id'] = $accounts[$k]['chart_account_id'];
                $itemData['account_id'] = $accounts[$k]['id'];
                $itemData['amount'] = $accounts[$k]['price'];
            }

            $itemData['bill_tax'] = $bill->tax_id;
            $items[] = $itemData;
        }

        // If no regular items, use accounts data
        if (empty($items)) {
            foreach ($bill->accounts as $k => $val) {
                $items[] = [
                    'chart_account_id' => $val['chart_account_id'],
                    'account_id' => $val['id'],
                    'amount' => $val['price'],
                    'bill_tax' => $bill->tax_id
                ];
            }
        }

        // Get status changes in single queries
        $statusChanges = [
            'approve' => $bill->statusChanges->where('status', 2)->first(),
            'send' => $bill->statusChanges->where('status', 4)->first(),
            'received' => $bill->statusChanges->where('status', 6)->first(),
            'sendToApprove' => $bill->statusChanges->where('status', 1)->first()
        ];

        // Cache custom fields data
        $bill->customField = CustomField::getData($bill, 'bill');
        $customFields = \Cache::remember('custom_fields_bill_' . auth()->id(), now()->addHours(1), function () {
            return CustomField::where('created_by', auth()->id())
                ->where('module', 'bill')
                ->get();
        });
        $statusChangesApprove = $bill->statusChanges()->where('status', 2)->first();
        $statusChangesSend = $bill->statusChanges()->where('status', 4)->first();
        $statusChangesReceived = $bill->statusChanges()->where('status', 6)->first();
        $statusChangesSendToApprove = $bill->statusChanges()->where('status', 1)->first();
        $totalQuantity = $bill->items->sum('quantity');
        $totalRate = $bill->currency_id != null ? $paginatedItems->sum('exchange_price') : $paginatedItems->sum('price');
        $totalPrice = 0;
        $totalTaxPrice = 0;
        // $totalDiscount = 0;

        foreach ($bill->items as $item) {
            $qty = $item->quantity ?? 0;
            $price = $bill->currency_id != null ? $item->exchange_price : $item->price ?? 0;

            // If discount is stored as a fixed amount
            $discount = $bill->currency_id != null ? $item->exchange_discount : $item->discount ?? 0;

            // Get tax rate from Tax model via relation
            $taxRate = $item->taxObject->rate ?? 0;

            $lineSubtotal = $qty * $price;
            $taxAmount = ($lineSubtotal - ($discount * $qty)) * ($taxRate / 100);
            $lineTotal = $lineSubtotal + $taxAmount - $discount;

            $totalPrice += $lineTotal;
            $totalTaxPrice += $taxAmount;
            // $totalDiscount += $discount;
        }
        $subTotal = $bill->currency_id != null ? $bill->getSubTotalExchange() : $bill->getSubTotal();
        $totalDiscount = $bill->currency_id != null ? $bill->getTotalDiscountExchange() : $bill->getTotalDiscount();
        $totalTaxPrice = $bill->currency_id != null ? $bill->getTotalTaxExchange() : $bill->getTotalTax();
        $total = $bill->currency_id != null ? $bill->getTotalExchange() : $bill->getTotal();
        $due = $bill->currency_id != null ? $bill->getDueExchange() : $bill->getDue();
        $debitNoteTotal = $bill->currency_id != null ? $bill->billTotalDebitNoteExchange() : $bill->billTotalDebitNote();
        $refundTotal = $bill->currency_id != null ? $bill->billTotalRefundExchange() : $bill->billTotalRefund();
        $paidAmount = $total - $due - $debitNoteTotal;
        
        // Get request numbers for items that are accessories from car_accessory_request_items
        $subProductIds = $bill->items->pluck('sub_product_id')->filter()->unique()->toArray();
        $requestNumbers = [];
        if (!empty($subProductIds)) {
            $requestItems = CarAccessoryRequestItem::whereIn('accessory_id', $subProductIds)
                ->with('request')
                ->get();
            
            foreach ($requestItems as $requestItem) {
                if ($requestItem->request && $requestItem->request->request_no) {
                    $requestNumbers[] = $requestItem->request->request_no;
                }
            }
            // Remove duplicates
            $requestNumbers = array_unique($requestNumbers);
        }

        // ASN numbers connected to this bill
        $asnNumbers = [];
        foreach ($bill->asnBills as $asnBill) {
            if ($asnBill->asn) {
                $asnNumbers[] = \Auth::user()->asnNumberFormat($asnBill->asn->asn_no);
            }
        }
        $asnNumbers = array_unique($asnNumbers);
        
        return view('bill.view', [
            'bill' => $bill,
            'vendor' => $bill->vender,
            'items' => $items,
            'asnNumbers' => $asnNumbers,
            'paginatedItems' => $paginatedItems,
            'billPayment' => $bill->payments->first(),
            'customFields' => $customFields,
            'subProducts' => $bill->subProducts,
            'statusChanges' => $statusChanges,
            'statusChangesApprove' => $statusChangesApprove,
            'statusChangesSend' => $statusChangesSend,
            'statusChangesReceived' => $statusChangesReceived,
            'statusChangesSendToApprove' => $statusChangesSendToApprove,
            'totalQuantity' => $totalQuantity,
            'totalRate' => $totalRate,
            'totalPrice' => $totalPrice,
            'totalTaxPrice' => $totalTaxPrice,
            'requestNumbers' => $requestNumbers,
            // 'totalDiscount' => $totalDiscount,
            'subTotal' => $subTotal,
            'totalDiscount' => $totalDiscount,
            'total' => $total,
            'due' => $due,
            'debitNoteTotal' => $debitNoteTotal,
            'refundTotal' => $refundTotal,
            'paidAmount' => $paidAmount
        ]);
    }

    public function edit($ids)
    {

        if (\Auth::user()->can('edit bill')) {
            try {
                $id = Crypt::decrypt($ids);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Bill Not Found.'));
            }

            $id = Crypt::decrypt($ids);
            // Load bill with only needed columns and relationships
            $bill = Bill::select('id', 'vender_id', 'bill_date', 'due_date', 'bill_id', 'status', 'payment_status', 'created_by', 'type', 'tax_id', 'currency_id', 'category_id', 'warehouse_id', 'exchange_rate')
                ->with(['tax:id,rate', 'currency:id,symbol,name,exchange_rate'])
                ->find($id);

            if (!empty($bill)) {
                // Optimize category query - only select needed fields
                $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())
                    ->select('id', 'name')
                    ->get()
                    ->pluck('name', 'id');
                $category->prepend('Select Category', '');
                $bill_number = \Auth::user()->billNumberFormat($bill->bill_id);

                // Optimize vendors query
                $venders = Vender::where('created_by', \Auth::user()->creatorId())
                    ->select('id', 'name')
                    ->get()
                    ->pluck('name', 'id');

                $bill->customField = CustomField::getData($bill, 'bill');

                // Optimize customFieldsbill query
                $customFieldsbill = CustomField::where('created_by', '=', \Auth::user()->creatorId())
                    ->where('module', '=', 'bill')
                    ->select('id', 'name', 'type', 'options')
                    ->get();

                // Optimize chartAccounts query
                $chartAccounts = ChartOfAccount::where('created_by', \Auth::user()->creatorId())
                    ->select('id', 'code', 'name')
                    ->get()
                    ->map(function ($account) {
                        return [
                            'id' => $account->id,
                            'code_name' => $account->code . ' - ' . $account->name
                        ];
                    })
                    ->pluck('code_name', 'id');
                $chartAccounts->prepend('Select Account', '');

                // Optimize users query
                $users = User::where('created_by', '=', \Auth::user()->creatorId())
                    ->select('id', 'name')
                    ->get()
                    ->pluck('name', 'id');
                $users->prepend('Select User', '');

                // Optimize currency query
                $currency = Currency::select('id', 'name')
                    ->get()
                    ->pluck('name', 'id');

                // Optimize tax query
                $fullTax = Tax::where('created_by', '=', \Auth::user()->creatorId())
                    ->select('id', 'name', 'rate')
                    ->get();

                $currency_symbol = $bill->currency ? $bill->currency->symbol : \Auth::user()->currencySymbol();

                // Eager load all relationships including custom field values to avoid N+1 queries
                $subProducts = SubProduct::select('id', 'product_id', 'chassis_no', 'sale_price', 'purchase_price', 'quantity', 'bill_id', 'created_by')
                    ->with([
                        'billProducts' => function ($query) {
                            $query->select('id', 'bill_id', 'sub_product_id', 'quantity', 'price', 'exchange_price', 'exchange_discount');
                        },
                        'productService' => function ($query) {
                            $query->select('id', 'name', 'sku', 'category_id', 'sale_price');
                        },
                        'productService.category' => function ($query) {
                            $query->select('id', 'name', 'type');
                        },
                        'customFieldValues' => function ($query) {
                            $query->select('record_id', 'field_id', 'value');
                        },
                        'images' => function ($query) {
                            $query->select('id', 'sub_product_id', 'file_name', 'sort_order');
                        },
                    ])
                    ->where('bill_id', '=', $bill->id)
                    ->paginate(perPage: 100);

                // Load custom fields for the category (only if we have subProducts)
                $customFields = collect([]);
                if ($subProducts->isNotEmpty() && $subProducts->first() && $subProducts->first()->productService) {
                    $categoryId = $subProducts->first()->productService->category_id;
                    $customFields = CustomField::where('created_by', \Auth::user()->creatorId())
                        ->where('module', 'sub-product')
                        ->forCategory($categoryId)
                        ->select('id', 'name', 'type', 'options')
                        ->get();
                }

                // Optimize product_services query - only load products that are in the current page
                $productIds = $subProducts->pluck('product_id')->unique()->filter()->toArray();

                // Only load products that are actually used in this page
                $product_services = collect();
                if (!empty($productIds)) {
                    $product_services = ProductService::where('created_by', \Auth::user()->creatorId())
                        ->whereIn('id', $productIds)
                        ->select('id', 'name', 'sku', 'category_id', 'brand_id', 'sub_brand_id')
                        ->with([
                            'brand:id,name',
                            'subBrand:id,name',
                            'category:id,name,type'
                        ])
                        ->get()
                        ->map(function ($productService) {
                            $category = $productService->category->name ?? '';
                            $brand = $productService->brand->name ?? '';
                            $subBrand = $productService->subBrand->name ?? '';
                            $productName = $productService->name;
                            $productCode = $productService->sku;

                            return [
                                'id' => $productService->id,
                                'name' => $category . '/' . $brand . '/' . $subBrand . '/' . $productName . '/' . $productCode,
                            ];
                        })
                        ->pluck('name', 'id');
                }

                // Calculate totals efficiently using database aggregation instead of loading all items
                $totals = \DB::table('bill_products')
                    ->where('bill_id', $bill->id)
                    ->selectRaw('
                        SUM(quantity * COALESCE(exchange_price, price)) as sub_total,
                        SUM(COALESCE(exchange_discount, 0)) as total_discount
                    ')
                    ->first();

                $subTotal = $totals->sub_total ?? 0;
                $totalDiscount = $totals->total_discount ?? 0;
                $totalTax = $bill->tax ? (($subTotal - $totalDiscount) * $bill->tax->rate / 100) : 0;
                $totalAmount = $subTotal + $totalTax - $totalDiscount;

                // Optimize warehouse query
                $warehouse = warehouse::where('created_by', \Auth::user()->creatorId())
                    ->select('id', 'name')
                    ->get()
                    ->pluck('name', 'id');
                $warehouse->prepend('Select Warehouse', '');
                return view('bill.edit', compact(
                    'venders',
                    'product_services',
                    'bill',
                    'bill_number',
                    'category',
                    'customFieldsbill',
                    'chartAccounts',
                    'users',
                    'currency',
                    'fullTax',
                    'currency_symbol',
                    'subProducts',
                    'customFields',
                    'bill',
                    'product_services',
                    'fullTax',
                    'subTotal',
                    'totalTax',
                    'totalDiscount',
                    'totalAmount',
                    'warehouse'
                ));
            } else {
                return redirect()->back()->with('error', __('Bill Not Found.'));
            }
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, Bill $bill)
    {
        if (!\Auth::user()->can('edit bill')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if ($bill->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = \Validator::make(
            $request->all(),
            [
                'vender_id' => 'required',
                'bill_date' => 'required',
                'due_date' => 'required',
            ]
        );
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->route('bill.index')->with('error', $messages->first());
        }

        DB::beginTransaction();
        try {
            $items = $request->items;
            $existingChassisNos = [];
            $totalAfterUpdate = 0;

            foreach ($items as $item) {
                $price = $item['purchase_price'];
                $discount = $item['discount'];

                if ($bill->currency_id) {
                    $exchangeRate = $bill->exchange_rate ?? optional($bill->currency)->exchange_rate;
                    if ($exchangeRate && $exchangeRate > 0) {
                        $price = $item['purchase_price'] * $exchangeRate;
                        $discount = $item['discount'] * $exchangeRate;
                    }
                }

                $lineTotal = max(($price * $item['qty']) - $discount, 0);
                $totalAfterUpdate += $lineTotal;
            }

            if ($totalAfterUpdate < $bill->getTotalPaid()) {
                DB::rollBack();
                return redirect()->back()->with('error', __('Cannot update. Paid amount is greater than the updated bill total.'));
            }

            // Update bill details
            $bill->vender_id = $request->vender_id;
            $bill->bill_date = $request->bill_date;
            $bill->due_date = $request->due_date;
            $bill->user_type = 'vendor';
            $bill->order_number = $request->order_number;
            $bill->category_id = $request->category_id;
            $bill->salesman_id = $request->salesman_id ?? \Auth::user()->creatorId();
            $bill->currency_id = $request->currency_id;
            $bill->exchange_rate = $request->exchange_rate;
            $bill->tax_id = !empty($request->tax_id) ? implode(',', $request->tax_id) : '';
            $bill->save();

            BillProduct::where('bill_id', $bill->id)->update(['tax' => $bill->tax_id]);

            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $document) {
                    $filename = pathinfo($document->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $document->getClientOriginalExtension();
                    $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                    $document->move(public_path('documents'), $fileNameToStore);

                    AccountingDocument::create([
                        'document_name' => $document->getClientOriginalName(),
                        'document_path' => 'documents/' . $fileNameToStore,
                        'bill_id' => $bill->id,
                    ]);
                }
            }

            foreach ($items as $index => $item) {
                $existingProduct = SubProduct::where('chassis_no', $item['product_no'])
                    ->where('flag', '!=', 2)
                    ->where('id', '!=', $item['sub_product_id'])
                    ->first();

                if ($existingProduct) {
                    $existingChassisNos[] = $item['product_no'];
                }

                $price = $item['purchase_price'];
                $discount = $item['discount'];

                if ($bill->currency_id) {
                    $exchangeRate = $bill->exchange_rate ?? optional($bill->currency)->exchange_rate;
                    if ($exchangeRate && $exchangeRate > 0) {
                        $price = $item['purchase_price'] * $exchangeRate;
                        $discount = $item['discount'] * $exchangeRate;
                    }
                }

                $productService = SubProduct::find($item['sub_product_id']);
                $productService->chassis_no = $item['product_no'];
                $productService->sale_price = $item['sale_price'];
                $productService->purchase_price = $price - $discount;
                $productService->quantity = $item['qty'];
                $productService->created_by = \Auth::user()->creatorId();
                $productService->flag = 0;
                $productService->bill_id = $bill->id;
                $productService->warehouse_id = !empty($request->warehouse_id) ? $request->warehouse_id : 0;
                $productService->save();

                $productService->appendUploadedGalleryImages($this->billItemRowSubProductImageFiles($request, (int) $index));

                $mainProduct = ProductService::find($productService->product_id);
                if ($mainProduct) {
                    $totalQty = SubProduct::where('product_id', $mainProduct->id)->sum('quantity');
                    $mainProduct->quantity = $totalQty;
                    $mainProduct->save();
                }

                // Save custom fields
                if (isset($item['customField'])) {
                    foreach ($item['customField'] as $fieldId => $value) {
                        CustomFieldValue::updateOrCreate(
                            [
                                'record_id' => $productService->id,
                                'field_id' => $fieldId,
                            ],
                            ['value' => $value]
                        );
                    }
                }

                $bill_product = BillProduct::where('sub_product_id', $item['sub_product_id'])->where('bill_id', $bill->id)->first();
                if ($bill_product) {
                    $bill_product->price = $price;
                    $bill_product->exchange_price = $item['purchase_price'];
                    $bill_product->exchange_discount = $item['discount'];
                    $bill_product->discount = $discount;
                    $bill_product->quantity = $item['qty'];
                    $bill_product->description = $request->description;
                    $bill_product->save();
                }
            }

            DB::commit();

            if (!empty($existingChassisNos)) {
                $errorMessage = "Products numbers already exist: " . implode(', ', $existingChassisNos);
                return redirect()->route('bill.index', $bill->id)->with('success', $errorMessage);
            }

            return redirect()->route('bill.show', ['bill' => Crypt::encrypt($bill->id)])
                ->with('success', __('Bill successfully updated.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }


    public function destroy(Request $request)
    {
        try {
            if (\Auth::user()->can('delete bill')) {
                $bill = Bill::where('id', $request->bill_id)->first();
                
                if (!$bill) {
                    return redirect()->back()->with('error', __('Bill not found.'));
                }
                
                // Parse delete_date (required for GeneralLedger entries, but validation skipped for draft bills)
                $deleteDate = $request->delete_date ? Carbon::parse($request->delete_date) : Carbon::now();
                
                // Skip date validation for draft bills (status = 0)
                if ($bill->status != 0) {
                    $billDate = Carbon::parse($bill->bill_date);
                    
                    if ($bill->status === 4 && $bill->send_date) {
                        $billSendDate = Carbon::parse($bill->send_date);
                        if ($deleteDate->lt($billSendDate)) {
                            return redirect()->back()->with('error', 'Delete date must be greater than or equal to the send date.');
                        }
                    } elseif ($deleteDate->lt($billDate)) {
                        return redirect()->back()->with('error', 'Delete date must be greater than or equal to the bill date.');
                    }
                }

                if ($bill->created_by == \Auth::user()->creatorId()) {
                    
                    // Get all sub_product_ids from bill products
                    $billProductSubProductIds = BillProduct::where('bill_id', $bill->id)
                        ->whereNotNull('sub_product_id')
                        ->pluck('sub_product_id')
                        ->unique()
                        ->toArray();
                    
                    if (!empty($billProductSubProductIds)) {
                        // Check if any sub-product has direct expenses
                        $hasDirectExpenses = DirectExpenseItem::whereIn('sub_product_id', $billProductSubProductIds)
                            ->whereHas('directExpense', function ($query) {
                                $query->where('created_by', \Auth::user()->creatorId());
                            })
                            ->exists();
                        
                        if ($hasDirectExpenses) {
                            return redirect()->back()->with('error', __('Cannot delete bill: One or more items in this bill have direct expenses associated with them.'));
                        }
                        
                        // Check if any sub-product is linked to car manufacture (as car or accessory)
                        $isLinkedToCarManufacture = CarAccessoryRequestItem::where(function ($query) use ($billProductSubProductIds) {
                                $query->whereIn('car_id', $billProductSubProductIds)
                                    ->orWhereIn('accessory_id', $billProductSubProductIds);
                            })
                            ->whereHas('request', function ($query) {
                                $query->where('created_by', \Auth::user()->creatorId());
                            })
                            ->exists();
                        
                        if ($isLinkedToCarManufacture) {
                            return redirect()->back()->with('error', __('Cannot delete bill: One or more items in this bill are linked to car manufacture.'));
                        }
                    }
                    
                    DB::beginTransaction();

                    // Ensure there are no conflicting records with the new voucher ID
                    $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
                    $newVoucherId = $latestVoucher ? $latestVoucher->vid + 1 : 1;

                    if (GeneralLedger::where('vid', $newVoucherId)->where('created_by', \Auth::user()->creatorId())->exists()) {
                        return redirect()->back()->with('error', __("Something went wrong, please try again."));
                    }
                    // Bulk update payments
                    Payment::where('bill_id', $bill->id)->update([
                        'bill_id' => null,
                        'payment_id' => null
                    ]);
                    
                    // Eager load all necessary data to avoid N+1 queries
                    $bill_products = BillProduct::where('bill_id', $bill->id)
                        ->with(['product.category', 'subProduct.productService.category'])
                        ->get();
                    
                    // Pre-load all products and subproducts to avoid queries in loop
                    $productIds = $bill_products->pluck('product_id')->unique();
                    $subProductIds = $bill_products->pluck('sub_product_id')->filter()->unique();
                    
                    $products = ProductService::whereIn('id', $productIds)
                        ->with('category')
                        ->get()
                        ->keyBy('id');
                    
                    $subProducts = SubProduct::whereIn('id', $subProductIds)
                        ->with('productService.category')
                        ->get()
                        ->keyBy('id');
                    
                    // Check for booked items first (before processing)
                    foreach ($subProducts as $subProduct) {
                        if ((int) $subProduct->booked !== 0) {
                            DB::rollBack();
                            return redirect()->back()->with('error', __('You cannot destroy this bill; it contains one or more items that have been Booked.'));
                        }
                    }
                    
                    // Pre-load tax data if applicable
                    $taxes = [];
                    $taxRate = 0;
                    $taxModel = null;
                    if (!empty($bill->tax_id)) {
                        $taxes = \App\Models\Utility::tax($bill->tax_id);
                        $taxModel = Tax::find($bill->tax_id);
                        $taxRate = $taxModel ? $taxModel->rate : 0;
                    }
                    
                    // Pre-load vendor data
                    $vendor = null;
                    $vendorAccountId = null;
                    if (!in_array($bill->status, [0, 1, 2])) {
                        $vendor = Vender::find($bill->vender_id);
                        $vendorAccountId = $vendor ? $vendor->chart_account_id : null;
                    }
                    
                    // Prepare bulk operations
                    $productQuantityUpdates = [];
                    $productAvgCostUpdates = [];
                    $stockMovements = [];
                    $generalLedgerEntries = [];
                    $totalTaxPrice = 0;
                    $totalAmountDebit = 0;
                    
                    // Process bill products in batches
                    foreach ($bill_products as $bill_product) {
                        $product = $products->get($bill_product->product_id);
                        if (!$product) continue;
                        
                        $subProduct = $subProducts->get($bill_product->sub_product_id);
                        if (!$subProduct) continue;
                        
                        $subProductType = $subProduct->productService->category->type ?? null;
                        // MasterlistLeadger::addFree($subProduct->productService->id,$bill->warehouse_id,1,'BILL',$bill->id,\Auth::user()->creatorId());
                        // Accumulate quantity updates
                        if (!isset($productQuantityUpdates[$product->id])) {
                            $productQuantityUpdates[$product->id] = 0;
                        }
                        $productQuantityUpdates[$product->id] -= $bill_product->quantity;
                        
                        // Calculate tax for this item
                        $itemTaxPrice = 0;
                        if (!empty($taxes)) {
                            foreach ($taxes as $tax) {
                                if ($subProductType === 'Qty product') {
                                    $itemTaxPrice += ($tax->rate / 100) * $bill_product->price * $bill_product->quantity;
                                } else {
                                    $itemTaxPrice += ($tax->rate / 100) * $bill_product->price;
                                }
                            }
                        }
                        $totalTaxPrice += $itemTaxPrice;
                        
                        // Calculate amount debit
                        if ($subProductType === 'Qty product') {
                            $itemAmountDebit = $bill_product->price * $bill_product->quantity - $bill_product->discount;
                        } else {
                            $itemAmountDebit = $bill_product->price - $bill_product->discount;
                        }
                        $totalAmountDebit += $itemAmountDebit;
                        
                        // Handle Qty product stock movements and avg cost
                        if ($subProductType === 'Qty product' && !in_array($bill->status, [0, 1, 2])) {
                            $costCalculationMethod = $product->category->cost_calculation_method ?? 'avg';
                            
                            if ($costCalculationMethod === 'avg') {
                                $lastProductQty = $product->quantity ?? 0;
                                $lastAvg = ($product->avg_cost > 0) ? $product->avg_cost : $bill_product->price;
                                $deleteQty = $bill_product->quantity;
                                $deletePrice = $bill_product->price;
                                
                                if ($bill_product->discount > 0) {
                                    $deletePrice = ($bill_product->price * $bill_product->quantity - $bill_product->discount) / $bill_product->quantity;
                                }
                                
                                $remainingQty = $lastProductQty - $deleteQty;
                                if ($remainingQty > 0) {
                                    $avgCost = (($lastProductQty * $lastAvg) - ($deleteQty * $deletePrice)) / $remainingQty;
                                } else {
                                    $avgCost = 0;
                                }
                                
                                $productAvgCostUpdates[$product->id] = $avgCost;
                            } else {
                                $avgCost = $bill_product->price;
                                if ($bill_product->discount > 0) {
                                    $avgCost = ($bill_product->price * $bill_product->quantity - $bill_product->discount) / $bill_product->quantity;
                                }
                                $productAvgCostUpdates[$product->id] = $avgCost;
                            }
                            
                            // Prepare stock movement
                            $stockMovements[] = [
                                'product_id' => $product->id,
                                'sub_product_id' => $bill_product->sub_product_id,
                                'invoice_id' => null,
                                'bill_id' => $bill_product->bill_id,
                                'qty_out' => $bill_product->quantity,
                                'qty_in' => 0,
                                'avg_cost' => $avgCost,
                                'cost_price' => $bill_product->price,
                                'activity' => 'Purchase from Bill',
                                'use_id' => $bill->vender_id,
                                'item' => $bill_product->sub_product_id,
                                'created_by' => \Auth::user()->creatorId(),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                    
                    // Bulk update product quantities
                    foreach ($productQuantityUpdates as $productId => $quantityChange) {
                        DB::table('product_services')
                            ->where('id', $productId)
                            ->decrement('quantity', abs($quantityChange));
                    }
                    
                    // Bulk update product avg costs
                    foreach ($productAvgCostUpdates as $productId => $avgCost) {
                        DB::table('product_services')
                            ->where('id', $productId)
                            ->update(['avg_cost' => $avgCost]);
                    }
                    
                    // Bulk insert stock movements
                    if (!empty($stockMovements)) {
                        StockMovement::insert($stockMovements);
                    }
                    
                    // Create GeneralLedger reversal entries when bill is sent (not draft/partial)
                    if (!in_array($bill->status, [0, 1, 2])) {
                        $isBillFromAsn = AsnBill::where('bill_id', $bill->id)->exists();

                        if ($isBillFromAsn) {
                            // Bill was created from ASN (convert to inventory then convert selected to bill).
                            // Reverse the exact ledger entries that were created (Debit Goods Received Clearing, Credit Vendor, Debit Tax).
                            $originalEntries = GeneralLedger::where('ref_id', $bill->id)->where('reference', 'Bill')->get();
                            foreach ($originalEntries as $entry) {
                                GeneralLedger::create([
                                    'vid' => $newVoucherId,
                                    'account' => $entry->account,
                                    'type' => 'Bill delete ' . \Auth::user()->billNumberFormat($bill->id),
                                    'ref_number' => 'Bill delete ' . \Auth::user()->billNumberFormat($bill->bill_id),
                                    'debit' => $entry->credit,
                                    'credit' => $entry->debit,
                                    'ref_id' => $bill->id,
                                    'user_id' => $entry->user_id,
                                    'created_by' => \Auth::user()->creatorId(),
                                    'balance' => $entry->balance ?? 0,
                                    'send_date' => $deleteDate,
                                    'reference' => 'Delete Bill',
                                    'sub_product_id' => $entry->sub_product_id,
                                ]);
                            }
                        } elseif ($vendorAccountId) {
                            // Standard bill: Debit Vendor, Credit Purchase, Credit Tax (reversal of send)
                            $categoryTotals = [];
                            foreach ($bill_products as $bill_product) {
                                $product = $products->get($bill_product->product_id);
                                if (!$product || !$product->category) continue;

                                $categoryId = $product->category_id;
                                if (!isset($categoryTotals[$categoryId])) {
                                    $categoryTotals[$categoryId] = [
                                        'amount' => 0,
                                        'account_id' => $product->category->purchase_account_id ?? null
                                    ];
                                }

                                $subProduct = $subProducts->get($bill_product->sub_product_id);
                                $subProductType = $subProduct ? ($subProduct->productService->category->type ?? null) : null;

                                if ($subProductType === 'Qty product') {
                                    $categoryTotals[$categoryId]['amount'] += $bill_product->price * $bill_product->quantity - $bill_product->discount;
                                } else {
                                    $categoryTotals[$categoryId]['amount'] += $bill_product->price - $bill_product->discount;
                                }
                            }

                            GeneralLedger::create([
                                'vid' => $newVoucherId,
                                'account' => $vendorAccountId,
                                'type' => 'Bill delete ' . \Auth::user()->billNumberFormat($bill->id),
                                'ref_number' => 'Bill delete ' . \Auth::user()->billNumberFormat($bill->bill_id),
                                'debit' => $totalAmountDebit + $totalTaxPrice,
                                'credit' => 0,
                                'ref_id' => $bill->id,
                                'user_id' => $bill->vender_id,
                                'created_by' => \Auth::user()->creatorId(),
                                'balance' => $vendor->balance,
                                'send_date' => $deleteDate,
                                'reference' => 'Delete Bill'
                            ]);

                            foreach ($categoryTotals as $categoryId => $data) {
                                if ($data['account_id'] && $data['amount'] > 0) {
                                    GeneralLedger::create([
                                        'vid' => $newVoucherId,
                                        'account' => $data['account_id'],
                                        'type' => 'Bill delete ' . \Auth::user()->billNumberFormat($bill->id),
                                        'ref_number' => 'Bill delete ' . \Auth::user()->billNumberFormat($bill->bill_id),
                                        'debit' => 0,
                                        'credit' => $data['amount'],
                                        'ref_id' => $bill->id,
                                        'user_id' => 0,
                                        'created_by' => \Auth::user()->creatorId(),
                                        'send_date' => $deleteDate,
                                        'reference' => 'Delete Bill'
                                    ]);
                                }
                            }

                            if ($taxModel && $totalTaxPrice > 0) {
                                GeneralLedger::create([
                                    'vid' => $newVoucherId,
                                    'account' => $taxModel->chart_account_id,
                                    'type' => 'Bill delete ' . \Auth::user()->billNumberFormat($bill->id),
                                    'ref_number' => 'Bill delete ' . \Auth::user()->billNumberFormat($bill->bill_id),
                                    'debit' => 0,
                                    'credit' => $totalTaxPrice,
                                    'ref_id' => $bill->id,
                                    'user_id' => 0,
                                    'created_by' => \Auth::user()->creatorId(),
                                    'send_date' => $deleteDate,
                                    'reference' => 'Delete Bill'
                                ]);
                            }
                        }
                    }
                    
                    // Delete transaction lines (moved outside loop)
                    TransactionLines::where('reference_id', $bill->id)
                        ->whereIn('reference', ['Bill', 'Bill Account'])
                        ->delete();
                    
                    // Update vendor balance (only once)
                    if (!in_array($bill->status, [0, 1, 2])) {
                        Utility::updateUserBalance('vendor', $bill->vender_id, $bill->getTotal(), 'debit');
                    }

                    if ($bill->warehouse_id) {
                        WarehouseProduct::where('warehouse_id', $bill->warehouse_id)->delete();
                    }

                    if (!in_array($bill->status, [0, 1, 2])) {
                        $billProductIds = BillProduct::where('bill_id', $bill->id)->pluck('sub_product_id');
                        $subproducts = SubProduct::where('bill_id', $bill->id)->get();
                        foreach ($subproducts as $key => $value) {
                            $document_type = null;
                            $document_id = null;
                            if ($value->asn_id) {
                                $document_type = 'ASN';
                                $document_id = $value->asn_id;
                            } else {
                                $document_type = 'Bill';
                                $document_id = $value->bill_id;
                            }
                            
                            MasterlistLeadger::where('product_service_id', $product->id)
                                ->where('warehouse_id', $value->warehouse_id)
                                ->where('movement_type', 'free')
                                ->where('document_type', $document_type)
                                ->where('document_id', $document_id)
                                ->where('created_by', \Auth::user()->creatorId())
                                ->update([
                                    'qty_out' => \DB::raw('qty')
                                ]);
                            
                            $value->flag = 2;
                            $value->save();
                        }
                    } else if ($bill->status == 0) {
                        $billProductIds = BillProduct::where('bill_id', $bill->id)->pluck('sub_product_id');
                        if ($billProductIds->isNotEmpty()) {
                            $subproducts = SubProduct::whereIn('id', $billProductIds)->get();
                            foreach ($subproducts as $key => $value) {
                                $document_type = null;
                                $document_id = null;
                                if ($value->asn_id) {
                                    $document_type = 'ASN';
                                    $document_id = $value->asn_id;
                                } else {
                                    $document_type = 'Bill';
                                    $document_id = $value->bill_id;
                                }
                                
                                MasterlistLeadger::where('product_service_id', $product->id)
                                    ->where('warehouse_id', $value->warehouse_id)
                                    ->where('movement_type', 'free')
                                    ->where('document_type', $document_type)
                                    ->where('document_id', $document_id)
                                    ->where('created_by', \Auth::user()->creatorId())
                                    ->update([
                                        'qty_out' => \DB::raw('qty')
                                    ]);
                                $value->delete();
                            }
                        }
                    }
                    BillProduct::where('bill_id', '=', $bill->id)->delete();
                    BillPayment::where('bill_id', '=', $bill->id)->delete();
                    BillAccount::where('ref_id', '=', $bill->id)->delete();
                    DebitNote::where('bill', '=', $bill->id)->delete();



                    $bill->delete();
                    DB::commit();
                    return redirect()->to('/bill')->with('success', __('Bill successfully deleted.'));
                } else {
                    DB::rollBack();
                    return redirect()->to('/bill')->with('error', __('Permission denied.'));
                }
            } else {
                DB::rollBack();
                return redirect()->to('/bill')->with('error', __('Permission denied.'));
            }
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return redirect()->to('/bill')->with('error', __("Bill not found."));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->to('/bill')->with('error', $e->getMessage());
        }
    }
    function billNumber()
    {
        $latest = Bill::where('created_by', '=', \Auth::user()->creatorId())->where('bill_id', 'not like', '%#EXP%')->withTrashed()->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->bill_id + 1;
    }

    public function product(Request $request)
    {
        $data['product'] = $product = ProductService::find($request->product_id);
        $data['unit'] = !empty($product->unit) ? $product->unit->name : '';
        $data['taxRate'] = $taxRate = !empty($product->tax_id) ? $product->taxRate($product->tax_id) : 0;
        $data['taxes'] = !empty($product->tax_id) ? $product->tax($product->tax_id) : 0;
        $salePrice = $product->purchase_price;
        $quantity = 1;
        $taxPrice = ($taxRate / 100) * ($salePrice * $quantity);
        $data['totalAmount'] = ($salePrice * $quantity);

        return json_encode($data);
    }

    public function productDestroy(Request $request)
    {
        if (\Auth::user()->can('delete bill product')) {
            DB::beginTransaction();
            try {
                $billProduct = BillProduct::find($request->id);
                $bill = Bill::find($billProduct->bill_id);
                $productService = ProductService::find($billProduct->product_id);
                
                // Get category type
                $subProduct = SubProduct::where('id', $billProduct->sub_product_id)->first();
                $QtyType = $subProduct ? $subProduct->productService->category->type : null;
                
                // Recalculate average cost if Qty product
                if ($QtyType === "Qty product" && !in_array($bill->status, [0, 1, 2])) {
                    // Calculate average cost using deletion formula:
                    // Average Cost = ((Last Product Qty × Last Avg) - (Delete Qty × Delete Price)) ÷ (Last Qty - Delete Qty)
                    
                    // Get product's current quantity and average cost (before deletion)
                    $lastProductQty = $productService->quantity ?? 0;
                    $lastAvg = ($productService->avg_cost > 0) ? $productService->avg_cost : $billProduct->price;
                    
                    // Deleted item (current bill product being deleted)
                    $deleteQty = $billProduct->quantity;
                    // Calculate price per unit (after discount if applicable)
                    $deletePrice = $billProduct->price;
                    if ($billProduct->discount > 0) {
                        // Calculate price per unit after discount
                        $deletePrice = ($billProduct->price * $billProduct->quantity - $billProduct->discount) / $billProduct->quantity;
                    }
                    
                    // Calculate average cost using deletion formula
                    // Formula: ((last Product qty * last avg) - (delete qty * delete price)) / (last qty - delete qty)
                    $remainingQty = $lastProductQty - $deleteQty;
                    if ($remainingQty > 0) {
                        $avgCost = (($lastProductQty * $lastAvg) - ($deleteQty * $deletePrice)) / $remainingQty;
                    } else {
                        $avgCost = 0;
                    }
                    
                    // Update product average cost
                    $productService->avg_cost = $avgCost;
                    $productService->save();
                }
                
                BillProduct::where('id', '=', $request->id)->delete();
                BillAccount::where('id', '=', $request->account_id)->delete();
                SubProduct::where('bill_id', $billProduct->bill_id)->flag(2);
                
                DB::commit();
                return redirect()->back()->with('success', __('Bill product successfully deleted.'));
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', __('Error: ') . $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function sent(Request $request, $id)
    {
        if (\Auth::user()->can('send bill')) {
            try {
                DB::beginTransaction();
                $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
                if ($latestVoucher) {
                    $lastVid = $latestVoucher->vid;
                    $newVoucherId = $lastVid + 1;
                } else {
                    $newVoucherId = 1;
                }

                $existingRecord = GeneralLedger::where('vid', $newVoucherId)->where('created_by', \Auth::user()->creatorId())->exists();

                if ($existingRecord) {
                    return redirect()->back()->with('error', __("something went wrong , please try again."));
                }
                $bill = Bill::where('id', $id)->first();
                if (!$bill) {
                    return redirect()->back()->with('error', __('Bill not found.'));
                }

                $creatorId = \Auth::user()->creatorId();

                $sendDate = Carbon::parse($request->send_date);
                $billDate = Carbon::parse($bill->bill_date);

                // Check if send date is earlier than bill date
                if ($sendDate->lt($billDate)) {
                    return redirect()->back()->with('error', 'Send date cannot be earlier than the bill date.');
                }
                SubProduct::where('bill_id', $id)->update(['flag' => 1]);
                $bill->send_date = $sendDate;
                $bill->status = 4;
                $bill->save();
                $statusChange = new BillStatusChange();
                $statusChange->bill_id = $id;
                $statusChange->status = 4;
                $statusChange->payment_status = -1;
                $statusChange->changed_at = now();
                $statusChange->save();
                $vender = Vender::where('id', $bill->vender_id)->first();
                if (!$vender) {
                    throw new \Exception(__('Vendor not found.'));
                }
                ChartOfAccount::ensureExistsForCompany((int) $vender->chart_account_id, $creatorId, 'vendor');

                $taxAccountIdResolved = null;
                if (!empty($bill->tax_id)) {
                    $taxModelForSend = Tax::where('id', $bill->tax_id)->first();
                    if (!$taxModelForSend) {
                        throw new \Exception(__('Invalid tax on this bill.'));
                    }
                    if (empty($taxModelForSend->chart_account_id)) {
                        throw new \Exception(__('Tax is set but has no linked chart account. Configure tax in settings.'));
                    }
                    $taxAccountIdResolved = ChartOfAccount::ensureExistsForCompany((int) $taxModelForSend->chart_account_id, $creatorId, 'tax');
                }

                Utility::updateUserBalance('vendor', $bill->vender_id, $bill->getTotal(), 'credit');

                $bill_products = BillProduct::where('bill_id', $bill->id)->get();
                foreach ($bill_products as $bill_product) {
                    $product = ProductService::find($bill_product->product_id);
                    if (!$product) {
                        throw new \Exception(__('Bill line references a product that no longer exists.'));
                    }
                    $itemAmount = 0;
                    $totalTaxPrice = 0;
                    $taxPrice = 0;
                    $productCategory = ProductServiceCategory::where('id', $product->category_id)->first();
                    if (!$productCategory) {
                        throw new \Exception(__('Product category is missing for a line item. Assign a category to the product.'));
                    }
                    $QtyType = $productCategory->type;
                    if (!empty($bill->tax_id)) {
                        $taxes = \App\Models\Utility::tax($bill->tax_id);
                        foreach ($taxes as $tax) {
                            // $taxPrice = \App\Models\Utility::taxRate($tax->rate, $bill_product->price, $QtyType === "Qty product" ?  $product->quantity : 1 , $bill_product->discount);
                            if ($QtyType === "Qty product") {
                                $taxPrice = $tax->rate * (($bill_product->price - $bill_product->discount) * $bill_product->quantity) / 100;
                            } else {
                                $taxPrice = $tax->rate * ($bill_product->price - $bill_product->discount) / 100;
                            }
                            $totalTaxPrice += $taxPrice;
                        }
                    }
                    if ($QtyType === "Qty product") {
                        // Check cost calculation method
                        $costCalculationMethod = $product->category->cost_calculation_method ?? 'avg';
                        
                        if ($costCalculationMethod === 'avg') {
                            // Calculate average cost using weighted average formula:
                            // Average Cost = ((Product Parent Qty × Product Avg Cost (or bill price if avg is 0)) + (New Qty × New Price)) ÷ (Product Parent Qty + New Qty)
                            
                            // Count purchased subproduct quantities (from sent bills, excluding current bill)
                            $purchasedBillIds = Bill::whereNotIn('status', [0, 1, 2])
                                ->where('created_by', \Auth::user()->creatorId())
                                ->where('id', '!=', $bill->id) // Exclude current bill
                                ->pluck('id')
                                ->toArray();
                            
                            // Count total quantity from purchased subproducts
                            $oldQuantity = SubProduct::where('product_id', $product->id)
                                ->whereIn('bill_id', $purchasedBillIds)
                                ->where('flag', '!=', 0)
                                ->whereNotNull('bill_id')
                                ->sum('quantity') ?? 0;
                            
                            $oldAvgCost = ($product->avg_cost > 0) ? $product->avg_cost : $bill_product->price;
                            
                            // Calculate old total cost
                            $oldTotalCost = $oldQuantity * $oldAvgCost;
                            
                            // New item (current bill product)
                            $newQuantity = $bill_product->quantity;
                            // Calculate price per unit (after discount if applicable)
                            $newPricePerUnit = $bill_product->price;
                            if ($bill_product->discount > 0) {
                            // Calculate price per unit after discount
                            $newPricePerUnit = ($bill_product->price * $bill_product->quantity - $bill_product->discount) / $bill_product->quantity;
                        }
                        
                        // Calculate total cost for new item (new qty * new price per unit)
                        $newItemTotalCost = $newQuantity * $newPricePerUnit;
                        
                            // Apply weighted average formula
                            // Formula: ((product parent qty * product avg_cost (or bill price if avg is 0)) + (new qty * new price)) / (product parent qty + new qty)
                            $totalQuantity = $oldQuantity + $newQuantity;
                            if ($totalQuantity > 0) {
                                $avgCost = ($oldTotalCost + $newItemTotalCost) / $totalQuantity;
                            } else {
                                // If no old items, use new price per unit
                                $avgCost = $newPricePerUnit;
                            }
                        } else {
                            // Use actual cost (purchase price from bill)
                            $avgCost = $bill_product->price;
                            if ($bill_product->discount > 0) {
                                $avgCost = ($bill_product->price * $bill_product->quantity - $bill_product->discount) / $bill_product->quantity;
                            }
                        }

                        // Create a new StockMovement for the stock in (purchase)
                        $stockMovement = new StockMovement();
                        $stockMovement->product_id = $product->id;
                        $stockMovement->sub_product_id = $bill_product->sub_product_id;
                        $stockMovement->invoice_id = null;
                        $stockMovement->bill_id = $bill->id;
                        $stockMovement->qty_in = $bill_product->quantity; // Stock in for purchase
                        $stockMovement->qty_out = 0; // No stock out for purchase
                        $stockMovement->avg_cost = $avgCost;
                        $stockMovement->cost_price = $bill_product->price;
                        $stockMovement->activity = 'Purchase from Bill';
                        $stockMovement->use_id = $bill->vender_id; // vender_id for PURCHASE
                        $stockMovement->item = $bill_product->sub_product_id; // sub_product_id
                        $stockMovement->created_by = \Auth::user()->creatorId();
                        $stockMovement->save();
                        $product->avg_cost = $avgCost;
                        $product->save();
                        // $stock_product = SubProduct::find($item->sub_product_id);
                        // $stock_product->purchase_price = $avgCost;
                        // $stock_product->save();
                        $itemAmount = ($bill_product->price * $bill_product->quantity) - ($bill_product->discount * $bill_product->quantity);
                    } else {
                        $itemAmount = ($bill_product->price) - ($bill_product->discount);
                    }

                    $billAccount = new BillAccount();
                    $billAccount->chart_account_id = $vender->chart_account_id;
                    $billAccount->price = ($bill_product->price) - ($bill_product->discount) + $taxPrice;
                    $billAccount->description = $bill_product->description;
                    $billAccount->type = 'Bill Vender';
                    $billAccount->ref_id = $bill->id;
                    $billAccount->save();

                    $purchaseAccountId = ChartOfAccount::ensureExistsForCompany((int) $productCategory->purchase_account_id, $creatorId, 'product category (purchase)');

                    $billAccount = new BillAccount();
                    $billAccount->chart_account_id = $purchaseAccountId;
                    $billAccount->price = $bill_product->price;
                    $billAccount->description = $bill_product->description;
                    $billAccount->type = 'Bill Category';
                    $billAccount->ref_id = $bill->id;
                    $billAccount->save();

                    $vendorCreditLine = $itemAmount + $totalTaxPrice;

                    // General ledger (amounts in company base currency; chart accounts must exist for this company)
                    if ($itemAmount != 0) {
                        $purchaseEntry = new GeneralLedger();
                        $purchaseEntry->vid = $newVoucherId;
                        $purchaseEntry->account = $purchaseAccountId;
                        $purchaseEntry->type = \Auth::user()->billNumberFormat($bill->id);
                        $purchaseEntry->debit = $itemAmount;
                        $purchaseEntry->credit = 0;
                        $purchaseEntry->ref_id = $bill->id;
                        $purchaseEntry->user_id = 0;
                        $purchaseEntry->sub_product_id = $bill_product->sub_product_id;
                        $purchaseEntry->created_by = $creatorId;
                        $purchaseEntry->send_date = $sendDate;
                        $purchaseEntry->reference = 'Bill';
                        $purchaseEntry->ref_number = \Auth::user()->billNumberFormat($bill->bill_id);
                        $purchaseEntry->save();
                    }

                    if (!empty($bill->tax_id) && $totalTaxPrice > 0 && $taxAccountIdResolved) {
                        $taxEntry = new GeneralLedger();
                        $taxEntry->vid = $newVoucherId;
                        $taxEntry->account = $taxAccountIdResolved;
                        $taxEntry->type = \Auth::user()->billNumberFormat($bill->id);
                        $taxEntry->debit = $totalTaxPrice;
                        $taxEntry->credit = 0;
                        $taxEntry->ref_id = $bill->id;
                        $taxEntry->user_id = 0;
                        $taxEntry->sub_product_id = $bill_product->sub_product_id;
                        $taxEntry->created_by = $creatorId;
                        $taxEntry->send_date = $sendDate;
                        $taxEntry->reference = 'Bill';
                        $taxEntry->ref_number = \Auth::user()->billNumberFormat($bill->bill_id);
                        $taxEntry->save();
                    }

                    if ($vendorCreditLine != 0) {
                        $vendorAccountId = $vender->chart_account_id;
                        $vendorEntry = new GeneralLedger();
                        $vendorEntry->vid = $newVoucherId;
                        $vendorEntry->account = $vendorAccountId;
                        $vendorEntry->type = \Auth::user()->billNumberFormat($bill->id);
                        $vendorEntry->debit = 0;
                        $vendorEntry->credit = $vendorCreditLine;
                        $vendorEntry->ref_id = $bill->id;
                        $vendorEntry->user_id = $vender->id;
                        $vendorEntry->sub_product_id = $bill_product->sub_product_id;
                        $vendorEntry->created_by = $creatorId;
                        $vendorEntry->balance = $vender->balance;
                        $vendorEntry->send_date = $sendDate;
                        $vendorEntry->reference = 'Bill';
                        $vendorEntry->ref_number = \Auth::user()->billNumberFormat($bill->bill_id);
                        $vendorEntry->save();
                    }
                }
                // $resp = Utility::sendEmailTemplate('vender_bill_sent', [$vender->id => $vender->email], $vendorArr);
                DB::commit();

                return redirect()->back()->with('success', __('Bill successfully sent.'));
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function resent($id)
    {
        //        if(\Auth::user()->can('send bill'))
        //        {

        // Send Email
        $setings = Utility::settings();

        if ($setings['bill_resent'] == 1) {
            $bill = Bill::where('id', $id)->first();
            $vender = Vender::where('id', $bill->vender_id)->first();
            $bill->name = !empty($vender) ? $vender->name : '';
            $bill->bill = \Auth::user()->billNumberFormat($bill->bill_id);
            $billId = Crypt::encrypt($bill->id);
            $bill->url = route('bill.pdf', $billId);
            $billResendArr = [
                'vender_name' => $vender->name,
                'vender_email' => $vender->email,
                'bill_name' => $bill->name,
                'bill_number' => $bill->bill,
                'bill_url' => $bill->url,
            ];
            $resp = Utility::sendEmailTemplate('bill_resent', [$vender->id => $vender->email], $billResendArr);
        }

        return redirect()->back()->with('success', __('Bill successfully sent.') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
        //        }
        //        else
        //        {
        //            return redirect()->back()->with('error', __('Permission denied.'));
        //        }

    }

    public function payment($bill_id)
    {
        if (\Auth::user()->can('create payment bill')) {
            $bill = Bill::where('id', $bill_id)->first();
            $venders = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            $categories = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $currencies = Currency::get()->pluck('name', 'id');
            $currencies->prepend('Select currency', '');
            $currency_symbol = $bill->currency_id && $bill->currency
                ? $bill->currency->symbol
                : \Auth::user()->currencySymbol();
            return view('bill.payment', compact('venders', 'categories', 'accounts', 'bill', 'currencies', 'currency_symbol'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function createPayment(Request $request, $bill_id)
    {
        if (\Auth::user()->can('create payment bill')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'date' => 'required',
                    'amount' => 'required',
                    'account_id' => 'required',
                    'currency_id' => 'required|exists:currencies,id',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            $bill = Bill::find($bill_id);

            $amount = !empty($request->currency_rate) ? round($request->amount * $request->currency_rate, 2) : round($request->amount, 2);
            $invoiceDue = round($bill->getDue(), 2);

            if ($amount > $invoiceDue) {
                return redirect()->back()->with('error', __('Payment amount exceeds due for bill :number', ['number' => $bill->bill_id]));
            }
            try {
                DB::beginTransaction();
                $billPayment = new BillPayment();
                $billPayment->bill_id = $bill_id;
                $billPayment->date = $request->date;
                $billPayment->amount = !empty($request->currency_rate) ? round($request->amount * $request->currency_rate, 2) : round($request->amount, 2);
                $billPayment->account_id = $request->account_id;
                $billPayment->currency_id = $request->currency_id;
                $billPayment->currency_rate = $request->currency_rate;
                if ($request->currency_id == $bill->currency_id) {
                    $billPayment->amount_in_currency = $request->amount;
                } else {
                    $billPayment->amount_in_currency = $request->amount_in_currency;
                }
                $billPayment->payment_method = 0;
                $billPayment->reference = $request->reference;
                $billPayment->description = $request->description;

                if (!empty($request->add_receipt)) {
                    $document = $request->file('add_receipt');
                    $fileName = time() . "_" . preg_replace('/[^A-Za-z0-9\-]/', '', $request->add_receipt->getClientOriginalName());
                    $billPayment->add_receipt = $fileName;
                    $dir = 'uploads/payment';
                    $document->move(public_path('uploads/payment'), $fileName);
                }

                $billPayment->save();
                //add payment

                $payment = new Payment();
                $payment->date = $request->date;
                $payment->amount = !empty($request->currency_rate) ? $request->amount * $request->currency_rate : $request->amount;
                $payment->account_id = $request->account_id;
                $payment->vender_id = $bill->vender_id;
                $payment->category_id = $bill->category_id;
                $payment->payment_method = 0;
                $payment->reference = $request->reference;
                $payment->bill_id = $bill_id;
                $payment->payment_id = $billPayment->id;
                $payment->currency_id = $billPayment->currency_id;
                $payment->currency_rate = $billPayment->currency_rate;
                if ($request->currency_id == $bill->currency_id) {
                    $payment->amount_in_currency = $request->amount;
                } else {
                    $payment->amount_in_currency = $request->amount_in_currency;
                }
                if (!empty($request->add_receipt)) {
                    $payment->add_receipt = $fileName;
                }

                $payment->description = $request->description;
                $payment->status = 0;
                $payment->created_by = \Auth::user()->creatorId();
                $payment->payment_number = Payment::nextPaymentNumberFor($payment->created_by);
                $payment->save();
                $due = $bill->getDue();
                $total = $bill->getTotal();

                if ($due <= 0) {
                    $bill->payment_status = 4;
                    $bill->save();
                    $ispaid = BillStatusChange::where('bill_id', $bill_id)->where('payment_status', 4)->first();
                    if ($ispaid) {
                        $ispaid->changed_at = now();
                        $ispaid->save();
                    } else {
                        $statusChange = new BillStatusChange();
                        $statusChange->bill_id = $bill->id;
                        $statusChange->status = -1;
                        $statusChange->payment_status = 4;
                        $statusChange->changed_at = now();
                        $statusChange->save();
                    }
                } else {
                    $bill->payment_status = 2;
                    $bill->save();
                    $ishalfpaid = BillStatusChange::where('bill_id', $bill_id)->where('payment_status', 2)->first();
                    if ($ishalfpaid) {
                        $ishalfpaid->changed_at = now();
                        $ishalfpaid->save();
                    } else {
                        $statusChange = new BillStatusChange();
                        $statusChange->bill_id = $bill->id;
                        $statusChange->status = -1;
                        $statusChange->payment_status = 2;
                        $statusChange->changed_at = now();
                        $statusChange->save();
                    }
                }
                // $billPayment->user_id = $bill->vender_id;
                // $billPayment->user_type = 'Vender';
                // $billPayment->type = 'Partial';
                // $billPayment->created_by = \Auth::user()->id;
                // $billPayment->payment_id = $billPayment->id;
                // $billPayment->category = 'Bill';
                // $billPayment->account = $request->account_id;
                $billPayment->payment_id = $payment->id;
                $billPayment->save();

                $vender = Vender::where('id', $bill->vender_id)->first();

                // Send Email
                $setings = Utility::settings();
                if ($setings['new_bill_payment'] == 1) {

                    $vender = Vender::where('id', $bill->vender_id)->first();
                    $billPaymentArr = [
                        'vender_name' => $vender->name,
                        'vender_email' => $vender->email,
                        'payment_name' => $payment->name,
                        'payment_amount' => $payment->amount,
                        'payment_bill' => $payment->bill,
                        'payment_date' => $payment->date,
                        'payment_method' => $payment->method,
                        'company_name' => $payment->method,

                    ];


                    $resp = Utility::sendEmailTemplate('new_bill_payment', [$vender->id => $vender->email], $billPaymentArr);
                    DB::commit();
                    return redirect()->back()->with('success', __('Payment successfully added.') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : '') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
                }
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', $e->getMessage());
            }

            return redirect()->back()->with('success', __('Payment successfully added.') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : ''));
        }
    }

    public function paymentDestroy(Request $request, $bill_id, $payment_id)
    {

        if (\Auth::user()->can('delete payment bill')) {
            try {
                DB::beginTransaction();
                $payment = BillPayment::find($payment_id);
                $payment->delete();

                $bill = Bill::where('id', $bill_id)->first();

                $due = $bill->getDue();
                $total = $bill->getTotal();
                $epsilon = 0.01;
                if (abs($due - $total) < $epsilon) {
                    // No payments made
                    $bill->payment_status = 0; // Unpaid
                } elseif ($due <= $epsilon) {
                    // Fully paid
                    $bill->payment_status = 4; // Paid (use 4 for consistency)
                } else {
                    // Partially paid
                    $bill->payment_status = 2; // Partial
                }
                // if ($due > 0 && $total != $due) {
                //     // Partially paid
                //     $bill->payment_status = 2;
                //     $bill->save();

                //     // Remove any 'Paid' status change entry
                //     BillStatusChange::where('bill_id', $bill->id)
                //         ->where('payment_status', 4)
                //         ->delete();

                //     // Ensure 'Partially Paid' status exists
                //     $halfPaid = BillStatusChange::where('bill_id', $bill->id)
                //         ->where('payment_status', 2)
                //         ->first();

                //     if ($halfPaid) {
                //         $halfPaid->changed_at = now();
                //         $halfPaid->save();
                //     } else {
                //         BillStatusChange::create([
                //             'bill_id' => $bill->id,
                //             'status' => -1,
                //             'payment_status' => 2,
                //             'changed_at' => now(),
                //         ]);
                //     }
                // } else {
                //     // Fully unpaid
                //     $bill->payment_status = 0;
                //     $bill->save();

                //     // Remove any 'Partially Paid' status change entry
                //     BillStatusChange::where('bill_id', $bill->id)
                //         ->where('payment_status', 2)
                //         ->delete();

                //     // You may also want to remove 'Paid' status if it was set incorrectly
                //     BillStatusChange::where('bill_id', $bill->id)
                //         ->where('payment_status', 4)
                //         ->delete();
                // }
                $bill->save();
                DB::commit();
                return redirect()->back()->with('success', __('Payment successfully deleted.'));
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function venderBill(Request $request)
    {
        if (\Auth::user()->can('manage vender bill')) {

            $status = Bill::$statues;

            $query = Bill::where('vender_id', '=', \Auth::user()->vender_id)->where('status', '!=', '0')->where('created_by', \Auth::user()->creatorId());

            if (!empty($request->vender)) {
                $query->where('id', '=', $request->vender);
            }
            if (!empty($request->bill_date)) {
                $date_range = explode(' - ', $request->bill_date);
                $query->whereBetween('bill_date', $date_range);
            }

            if (!empty($request->status)) {
                $query->where('status', '=', $request->status);
            }
            $bills = $query->get();


            return view('bill.index', compact('bills', 'status'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function venderBillShow($id)
    {
        if (\Auth::user()->can('show bill')) {
            $bill_id = Crypt::decrypt($id);
            $bill = Bill::where('id', $bill_id)->first();

            if ($bill->created_by == \Auth::user()->creatorId()) {
                $vendor = $bill->vender;
                $iteams = $bill->items;

                return view('bill.view', compact('bill', 'vendor', 'iteams'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function vender(Request $request)
    {
        $vender = Vender::where('id', '=', $request->id)->first();

        return view('bill.vender_detail', compact('vender'));
    }


    public function venderBillSend($bill_id)
    {
        return view('vender.bill_send', compact('bill_id'));
    }

    public function venderBillSendMail(Request $request, $bill_id)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'email' => 'required|email',
            ]
        );
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $email = $request->email;
        $bill = Bill::where('id', $bill_id)->first();

        $vender = Vender::where('id', $bill->vender_id)->first();
        $bill->name = !empty($vender) ? $vender->name : '';
        $bill->bill = \Auth::user()->billNumberFormat($bill->bill_id);

        $billId = Crypt::encrypt($bill->id);
        $bill->url = route('bill.pdf', $billId);

        try {
            //            Mail::to($email)->send(new VenderBillSend($bill));
        } catch (\Exception $e) {
            $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
        }

        return redirect()->back()->with('success', __('Bill successfully sent.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
    }

    public function shippingDisplay(Request $request, $id)
    {
        $bill = Bill::find($id);

        if ($request->is_display == 'true') {
            $bill->shipping_display = 1;
        } else {
            $bill->shipping_display = 0;
        }
        $bill->save();

        return redirect()->back()->with('success', __('Shipping address status successfully changed.'));
    }

    public function duplicate($bill_id)
    {
        try {
            if (\Auth::user()->can('duplicate bill')) {

                DB::beginTransaction();
                $bill = Bill::where('id', $bill_id)->first();

                $duplicateBill = new Bill();
                $duplicateBill->bill_id = $this->billNumber();
                $duplicateBill->vender_id = $bill['vender_id'];
                $duplicateBill->type = $bill['type'];
                $duplicateBill->bill_date = date('Y-m-d');
                $duplicateBill->due_date = $bill['due_date'];
                $duplicateBill->send_date = null;
                $duplicateBill->category_id = $bill['category_id'];
                $duplicateBill->order_number = $bill['order_number'];
                $duplicateBill->status = 0;
                $duplicateBill->payment_status = 0;
                $duplicateBill->shipping_display = $bill['shipping_display'];
                $duplicateBill->user_type = $bill['user_type'];
                $duplicateBill->tax_id = $bill['tax_id'];
                $duplicateBill->warehouse_id = $bill['warehouse_id'];
                $duplicateBill->created_by = $bill['created_by'];
                $duplicateBill->currency_id = $bill['currency_id'];
                $duplicateBill->exchange_rate = $bill['exchange_rate'];
                $duplicateBill->save();

                if ($duplicateBill) {
                    $billProduct = BillProduct::where('bill_id', $bill_id)->get();
                    foreach ($billProduct as $product) {
                        $old_subProduct = SubProduct::where('id', $product->sub_product_id)->first();
                        $subProduct = new SubProduct();
                        $subProduct->product_id = $product->product_id;
                        $subProduct->sale_price = $old_subProduct->sale_price;
                        $subProduct->purchase_price = $old_subProduct->purchase_price;
                        $subProduct->warehouse_id = $old_subProduct->warehouse_id;
                        $subProduct->created_by = \Auth::user()->creatorId();
                        $subProduct->flag = 0;
                        $subProduct->bill_id = $duplicateBill->id;
                        $subProduct->quantity = $old_subProduct->quantity;
                        $subProduct->save();

                        $duplicateProduct = new BillProduct();
                        $duplicateProduct->bill_id = $duplicateBill->id;
                        $duplicateProduct->product_id = $product->product_id;
                        $duplicateProduct->sub_product_id = $subProduct->id;
                        $duplicateProduct->quantity = $product->quantity;
                        $duplicateProduct->tax = $product->tax;
                        $duplicateProduct->discount = $product->discount;
                        $duplicateProduct->price = $product->price;
                        $duplicateProduct->exchange_price = $product->exchange_price;
                        $duplicateProduct->exchange_discount = $product->exchange_discount;
                        $duplicateProduct->save();
                    }
                }

                DB::commit();
                return redirect()->back()->with('success', __('Bill duplicate successfully.'));
            } else {
                DB::rollBack();
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->with('error', $th->getMessage());
        }
    }

    public function previewBill($template, $color)
    {
        $template = Utility::resolveBillTemplate($template);
        $objUser = \Auth::user();
        $settings = Utility::settings();
        $bill = new Bill();

        $vendor = new \stdClass();
        $vendor->email = '<Email>';
        $vendor->name = '<Vendor Name>';
        $vendor->shipping_name = '<Vendor Name>';
        $vendor->shipping_country = '<Country>';
        $vendor->shipping_state = '<State>';
        $vendor->shipping_city = '<City>';
        $vendor->shipping_phone = '<Vendor Phone Number>';
        $vendor->shipping_zip = '<Zip>';
        $vendor->shipping_address = '<Address>';
        $vendor->billing_name = '<Vendor Name>';
        $vendor->billing_country = '<Country>';
        $vendor->billing_state = '<State>';
        $vendor->billing_city = '<City>';
        $vendor->billing_phone = '<Vendor Phone Number>';
        $vendor->billing_zip = '<Zip>';
        $vendor->billing_address = '<Address>';

        $totalTaxPrice = 0;
        $taxesData = [];
        $items = [];
        for ($i = 1; $i <= 3; $i++) {
            $item = new \stdClass();
            $item->name = 'Item ' . $i;
            $item->quantity = 1;
            $item->tax = 5;
            $item->discount = 50;
            $item->price = 100;
            $item->unit = 1;

            $taxes = [
                'Tax 1',
                'Tax 2',
            ];

            $itemTaxes = [];
            foreach ($taxes as $k => $tax) {
                $taxPrice = 10;
                $totalTaxPrice += $taxPrice;
                $itemTax['name'] = 'Tax ' . $k;
                $itemTax['rate'] = '10 %';
                $itemTax['price'] = '$10';
                $itemTax['tax_price'] = 10;
                $itemTaxes[] = $itemTax;
                if (array_key_exists('Tax ' . $k, $taxesData)) {
                    $taxesData['Tax ' . $k] = $taxesData['Tax 1'] + $taxPrice;
                } else {
                    $taxesData['Tax ' . $k] = $taxPrice;
                }
            }
            $item->itemTax = $itemTaxes;
            $items[] = $item;
        }

        $bill->bill_id = 1;
        $bill->issue_date = date('Y-m-d H:i:s');
        $bill->due_date = date('Y-m-d H:i:s');
        $bill->itemData = $items;

        $bill->totalTaxPrice = 60;
        $bill->totalQuantity = 3;
        $bill->totalRate = 300;
        $bill->totalDiscount = 10;
        $bill->taxesData = $taxesData;
        $bill->created_by = $objUser->creatorId();


        $bill->customField = [];
        $customFields = [];

        $preview = 1;
        $color = '#' . $color;
        $font_color = Utility::getFontColor($color);

        //        $logo         = asset(Storage::url('uploads/logo/'));
        //        $bill_logo = Utility::getValByName('bill_logo');
        //        $company_logo = \App\Models\Utility::GetLogo();
        //        if(isset($bill_logo) && !empty($bill_logo))
        //        {
        //            $img          = asset(\Storage::url('bill_logo').'/'. $bill_logo);
        //        }
        //        else
        //        {
        //            $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        //        }

        $logo = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $bill_logo = Utility::getValByName('bill_logo');
        if (isset($bill_logo) && !empty($bill_logo)) {
            $img = Utility::get_file('bill_logo/') . $bill_logo;
        } else {
            $img = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }



        return view('bill.templates.' . $template, compact('bill', 'preview', 'color', 'img', 'settings', 'vendor', 'font_color', 'customFields'));
    }

    public function bill($bill_id)
    {
        $settings = Utility::settings();
        try {
            $billId = Crypt::decrypt($bill_id);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Bill Not Found.'));
        }
        $billId = Crypt::decrypt($bill_id);
        $bill = Bill::with(['vender', 'items'])->findOrFail($billId);

        // Load settings
        $data = DB::table('settings');
        $data = $data->where('created_by', '=', $bill->created_by);
        $data1 = $data->get();

        foreach ($data1 as $row) {
            $settings[$row->name] = $row->value;
        }

        $vendor = $bill->vender;

        // Initialize totals
        $totalTaxPrice = 0;
        $totalQuantity = 0;
        $totalRate = 0;
        $totalDiscount = 0;
        $taxesData = [];
        $items = [];

        // Currency conversion
        $currency = $bill->currency;
        $exchangeRate = $bill->exchange_rate != 0 ? $bill->exchange_rate : ($currency->exchange_rate ?? 1);

        foreach ($bill->items as $product) {
            $item = new \stdClass();

            $subProduct = SubProduct::with('productService')->find($product->sub_product_id);
            $productService = $subProduct->productService ?? null;

            $item->name = $productService ? $productService->name . ' / ' . $subProduct->chassis_no : 'N/A';
            $item->quantity = $product->quantity;
            $item->unit = optional(ProductService::find($product->product_id)->unit)->name ?? '';
            $item->tax = $product->getTaxPriceAttribute();

            // Adjust price and discount for currency
            $item->price = $product->price / $exchangeRate;
            $item->discount = $product->discount / $exchangeRate;
            $item->description = $product->description;

            $totalQuantity += 1;
            $totalRate += $item->price;
            $totalDiscount += $item->discount;

            // Calculate taxes per item
            $itemTaxes = [];
            $taxes = Utility::tax($bill->tax_id);
            if (!empty($item->tax)) {
                foreach ($taxes as $tax) {
                    $taxPrice = Utility::taxRate($tax->rate, $item->price, 1, $item->discount);
                    $totalTaxPrice += $taxPrice;

                    $itemTax = [
                        'name' => $tax->name,
                        'rate' => $tax->rate . '%',
                        'price' => $bill->currency_id
                            ? Utility::priceFormatCurr($settings, $taxPrice, $currency->symbol)
                            : Utility::priceFormat($settings, $taxPrice),
                        'tax_price' => $taxPrice,
                    ];
                    $itemTaxes[] = $itemTax;

                    $taxesData[$tax->name] = ($taxesData[$tax->name] ?? 0) + $taxPrice;
                }
            }

            $item->itemTax = $itemTaxes;
            $items[] = $item;
        }

        // Set data to the bill object
        $bill->itemData = $items;
        $bill->totalTaxPrice = $totalTaxPrice;
        $bill->totalQuantity = $totalQuantity;
        $bill->totalRate = $totalRate;
        $bill->totalDiscount = $totalDiscount;
        $bill->taxesData = $taxesData;
        $bill->customField = CustomField::getData($bill, 'bill');

        // Custom fields for display
        $customFields = [];
        if (\Auth::check()) {
            $customFields = CustomField::where('created_by', \Auth::user()->creatorId())
                ->where('module', 'bill')
                ->get();
        }

        // Logo
        $settings_data = Utility::settingsById($bill->created_by);
        $bill_logo = $settings_data['bill_logo'] ?? null;
        $logo_path = $bill_logo ? Utility::get_file('bill_logo/') . $bill_logo
            : asset(Storage::url('uploads/logo/' . ($settings['company_logo_dark'] ?? 'logo-dark.png')));

        // Colors
        $color = '#' . ($settings['bill_color'] ?? '000000');
        $font_color = Utility::getFontColor($color);

        $billTemplate = Utility::resolveBillTemplate($settings['bill_template'] ?? null);

        return view('bill.templates.' . $billTemplate, compact(
            'bill',
            'color',
            'settings',
            'vendor',
            'logo_path',
            'font_color',
            'customFields'
        ));
    }

    public function saveBillTemplateSettings(Request $request)
    {
        $post = $request->all();
        unset($post['_token']);

        if (isset($post['bill_template']) && (!isset($post['bill_color']) || empty($post['bill_color']))) {
            $post['bill_color'] = "ffffff";
        }

        if (isset($post['bill_template'])) {
            $post['bill_template'] = Utility::resolveBillTemplate($post['bill_template']);
        }

        //        $validator = \Validator::make(
        //            $request->all(),
        //            [
        //                'bill_logo' => 'image|mimes:png|max:20480',
        //            ]
        //        );
        //        if($validator->fails())
        //        {
        //            $messages = $validator->getMessageBag();
        //            return  redirect()->back()->with('error', $messages->first());
        //        }
        //        $bill_logo = \Auth::user()->id . '_bill_logo.png';
        //        $path = $request->file('bill_logo')->storeAs('bill_logo', $bill_logo);
        //        $post['bill_logo'] = $bill_logo;

        if ($request->bill_logo) {
            $dir = 'bill_logo/';
            $bill_logo = \Auth::user()->id . '_bill_logo.png';
            $validation = [
                'mimes:' . 'png',
                'max:' . '20480',
            ];
            $path = Utility::upload_file($request, 'bill_logo', $bill_logo, $dir, $validation);
            if ($path['flag'] == 0) {
                return redirect()->back()->with('error', __($path['msg']));
            }
            $post['bill_logo'] = $bill_logo;
        }


        foreach ($post as $key => $data) {
            \DB::insert(
                'insert into settings (`value`, `name`,`created_by`) values (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                [
                    $data,
                    $key,
                    \Auth::user()->creatorId(),
                ]
            );
        }

        return redirect()->back()->with('success', __('Bill Setting updated successfully'));
    }

    public function items(Request $request)
    {
        $items = BillProduct::where('bill_id', $request->bill_id)->where('product_id', $request->product_id)->first();
        return json_encode($items);
    }


    public function invoiceLink($billId)
    {
        try {
            $id = Crypt::decrypt($billId);
        } catch (\Throwable $th) {
            return response()->json([
                'exists' => false,
                'message' => __('Bill Not Found.'),
            ], 404);
        }

        $bill = Bill::find($id);
        if (!empty($bill)) {
            return response()->json([
                'exists' => true,
                'message' => __('Bill is valid.'),
                'id' => $bill->id,
            ]);
        } else {
            return response()->json([
                'exists' => false,
                'message' => __('Bill Not Found.'),
            ], 404);
        }
    }

    public function export()
    {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        $name = 'bill_' . date('Y-m-d i:h:s');
        $data = Excel::download(new BillExport(), $name . '.xlsx');


        return $data;
    }

    public function exportProducts($ids)
    {
        if (!\Auth::user()->can('show bill')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $id = Crypt::decrypt($ids);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Bill Not Found.'));
        }

        $bill = Bill::find($id);
        if (!$bill || $bill->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        $name = 'bill_products_' . $bill->bill_id . '_' . date('Y-m-d_H-i-s');
        $data = Excel::download(new BillProductExport($id), $name . '.xlsx');

        return $data;
    }

    public function getProductId($key)
    {
        $key_parts = explode('_', $key);
        $productId = $key_parts[3];
        return is_numeric($productId) ? $productId : null;
    }


    function goToAddSubProducts($bill_id)
    {
        # TODO edit the warehouse price 
        // Load bill without eager loading all items to save memory
        $bill = Bill::select('id', 'vender_id', 'bill_date', 'due_date', 'bill_id', 'status', 'payment_status', 'created_by', 'type', 'tax_id', 'currency_id')
            ->with(['tax:id,rate', 'currency:id,symbol'])
            ->find($bill_id);

        // Eager load all relationships including custom field values to avoid N+1 queries
        // Only load 100 items per page to manage memory
        $subProducts = SubProduct::select('id', 'product_id', 'chassis_no', 'sale_price', 'purchase_price', 'quantity', 'bill_id', 'created_by')
            ->with([
                'billProducts' => function ($query) {
                    $query->select('id', 'bill_id', 'sub_product_id', 'quantity', 'price', 'exchange_price', 'exchange_discount');
                },
                'productService' => function ($query) {
                    $query->select('id', 'name', 'sku', 'category_id', 'sale_price');
                },
                'productService.category' => function ($query) {
                    $query->select('id', 'name', 'type');
                },
                'customFieldValues' => function ($query) {
                    $query->select('record_id', 'field_id', 'value');
                },
                'images' => function ($query) {
                    $query->select('id', 'sub_product_id', 'file_name', 'sort_order');
                },
            ])
            ->where('bill_id', '=', $bill->id)
            ->paginate(perPage: 100);

        // Optimize product_services query - only load products that are in the current page
        // Get unique product IDs from the paginated subProducts
        $productIds = $subProducts->pluck('product_id')->unique()->filter()->toArray();

        // Only load products that are actually used in this page
        $product_services = collect();
        if (!empty($productIds)) {
            $product_services = ProductService::where('created_by', \Auth::user()->creatorId())
                ->whereIn('id', $productIds)
                ->select('id', 'name', 'sku', 'category_id', 'brand_id', 'sub_brand_id')
                ->with([
                    'brand:id,name',
                    'subBrand:id,name',
                    'category:id,name,type'
                ])
                ->get()
                ->map(function ($productService) {
                    $category = $productService->category->name ?? '';
                    $brand = $productService->brand->name ?? '';
                    $subBrand = $productService->subBrand->name ?? '';
                    $productName = $productService->name;
                    $productCode = $productService->sku;

                    return [
                        'id' => $productService->id,
                        'name' => $category . '/' . $brand . '/' . $subBrand . '/' . $productName . '/' . $productCode,
                    ];
                })
                ->pluck('name', 'id');
        }

        // Calculate totals efficiently using database aggregation instead of loading all items
        $totals = \DB::table('bill_products')
            ->where('bill_id', $bill->id)
            ->selectRaw('
                SUM(quantity * COALESCE(exchange_price, price)) as sub_total,
                SUM(COALESCE(exchange_discount, 0)) as total_discount
            ')
            ->first();

        $subTotal = $totals->sub_total ?? 0;
        $totalDiscount = $totals->total_discount ?? 0;
        $currency_symbol = $bill->currency ? $bill->currency->symbol : \Auth::user()->currencySymbol();
        $totalTax = $bill->tax ? (($subTotal - $totalDiscount) * $bill->tax->rate / 100) : 0;
        $totalAmount = $subTotal + $totalTax - $totalDiscount;
        // Only select needed fields for tax dropdown
        $fullTax = Tax::where('created_by', '=', \Auth::user()->creatorId())
            ->select('id', 'name', 'rate')
            ->get();

        // Load custom fields for ALL categories present in the subProducts
        $customFields = collect([]);
        if ($subProducts->isNotEmpty()) {
            // Get all unique category IDs from the subProducts
            $categoryIds = $subProducts->pluck('productService.category_id')
                ->filter()
                ->unique()
                ->toArray();

            if (!empty($categoryIds)) {
                // Load custom fields for all categories that have items in this bill
                $customFields = \App\Models\CustomField::where('created_by', \Auth::user()->creatorId())
                    ->where('module', 'sub-product')
                    ->forCategory($categoryIds)
                    ->select('id', 'name', 'type', 'options')
                    ->orderBy('name')
                    ->get();
            }
        }

        return view('bill.addProducts', compact(
            'subProducts',
            'bill',
            'product_services',
            'fullTax',
            'subTotal',
            'customFields',
            'totalTax',
            'totalDiscount',
            'totalAmount',
            'currency_symbol'
        ));
    }

    public function destroySubProduct($id, $bill_id)
    {
        $bill = Bill::find($bill_id);
        $productService = SubProduct::find($id);

        if ($bill->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $bill_product = BillProduct::where('sub_product_id', $id)->first();

        if (!$bill_product || !$productService) {
            return redirect()->back()->with('error', __('Sub Product or Bill Product not found.'));
        }

        // Simulate deletion effect
        $productAmount = ($bill_product->price * $bill_product->quantity) - $bill_product->discount;
        $currentTotal = $bill->getTotal();
        $paidTotal = $bill->getTotalPaid();
        $newTotal = $currentTotal - $productAmount;

        if ($newTotal < $paidTotal) {
            return redirect()->back()->with('error', __('Cannot delete. Paid amount would be greater than updated bill total.'));
        }

        // Safe to delete
        if ($bill->status === 0) {
            $bill_product->forceDelete();
            $productService->delete();
        } else {
            $bill_product->delete();
            $productService->flag = 2;
            $productService->save();
        }

        Utility::total_quantity('minus', 1, $productService->product_id);

        return redirect()->route('api.addSubProducts', $bill_id)
            ->with('success', __('Sub Product successfully deleted.'));
    }


    public function createSubProduct($id)
    {

        $bill = Bill::find($id);
        $product_services = ProductService::where('created_by', \Auth::user()->creatorId())->with(['brand', 'subBrand', 'category'])
            ->get()
            ->map(function ($productService) {
                $category = $productService->category->name ?? '';
                $brand = $productService->brand->name ?? '';
                $subBrand = $productService->subBrand->name ?? '';
                $productName = $productService->name;
                $productCode = $productService->sku;

                return [
                    'id' => $productService->id,
                    'name' => $category . '/' . $brand . '/' . $subBrand . '/' . $productName . '/' . $productCode,
                ];
            })
            ->pluck('name', 'id');
        $currency_symbol = $bill->currency ? $bill->currency->name : \Auth::user()->currencySymbol();
        $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'sub-product')->forCategory($bill->category_id)->get();
        $warehouses = warehouse::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $warehouses->prepend('Select Warehouse', '');
        return view('bill.createSubProduct', compact('id', 'customFields', 'product_services', 'currency_symbol', 'warehouses', 'bill'));
    }

    public function storeSubProduct(Request $request)
    {
        $rules = [
            'sale_price' => 'required|numeric',
            'purchase_price' => 'required|numeric',
            'product_id' => 'required',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'sub_product_images' => 'nullable|array',
            'sub_product_images.*' => 'nullable|image|max:10240',
        ];

        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->route('api.addSubProducts', ['bill_id' => $request->id])->with('error', $messages->first());
        }
        $bill = Bill::find($request->id);

        $productService = new SubProduct();
        $productService->sale_price = $request->sale_price;
        // $productService->purchase_price = $request->purchase_price - $request->discount;
        $price = $request->purchase_price;
        $discount_price = $request->discount;
        if (!empty($bill->currency_id)) {
            $exchangeRate = $bill->exchange_rate
                ?? optional(Currency::find($bill->currency_id))->exchange_rate;

            if ($exchangeRate) {
                $discount_price = $request->discount * $exchangeRate;
                $productService->purchase_price = ($request->purchase_price - $request->discount) * $exchangeRate;
                $price = $request->purchase_price * $exchangeRate;
            } else {
                $productService->purchase_price = $request->purchase_price - $request->discount;
                $price = $request->purchase_price;
                $discount_price = $request->discount;
            }
        } else {
            $productService->purchase_price = $request->purchase_price - $request->discount;
            $price = $request->purchase_price;
            $discount_price = $request->discount;
        }
        $productService->product_id = $request->product_id;
        $productService->created_by = \Auth::user()->creatorId();
        $productService->flag = 0;
        $productService->bill_id = $request->id;
        $productService->warehouse_id = $request->warehouse_id;
        $productService->save();

        $billProduct = new BillProduct();
        $billProduct->bill_id = $request->id;
        $billProduct->product_id = $request->product_id;
        $billProduct->sub_product_id = $productService->id;
        $billProduct->quantity = 1;
        $billProduct->tax = $bill->tax_id;
        $billProduct->discount = $discount_price ?: 0;
        $billProduct->price = $price;
        $billProduct->description = $request->description;
        $billProduct->exchange_price = $request->purchase_price;
        $billProduct->exchange_discount = $request->discount ?: 0;
        $billProduct->save();

        $gallery = $request->file('sub_product_images', []);
        if ($gallery !== null && ! is_array($gallery)) {
            $gallery = [$gallery];
        }
        $productService->appendUploadedGalleryImages($gallery ?? []);

        return redirect()->route('api.addSubProducts', $request->id)->with('success', __('Sub Product successfully created.'));
    }

    /**
     * Uploaded images for one bill line row (items[index][sub_product_images][]).
     *
     * @return array<int, \Illuminate\Http\UploadedFile>
     */
    private function billItemRowSubProductImageFiles(Request $request, int $index): array
    {
        $items = $request->file('items');
        if (! is_array($items) || empty($items[$index]['sub_product_images'])) {
            return [];
        }
        $row = $items[$index]['sub_product_images'];
        if (! is_array($row)) {
            return $row instanceof \Illuminate\Http\UploadedFile ? [$row] : [];
        }

        return array_values(array_filter($row, fn ($f) => $f instanceof \Illuminate\Http\UploadedFile));
    }

    public function updateSubProduct(Request $request)
    {
        DB::beginTransaction();

        try {
            $bill = Bill::find($request->id);
            $items = $request->items;
            $existingChassisNos = [];
            $totalAfterUpdate = 0;

            foreach ($items as $index => $item) {
                $existingProduct = SubProduct::where('chassis_no', $item['product_no'])
                    ->where('flag', '!=', 2)
                    ->where('id', '!=', $item['sub_product_id']) // exclude current
                    ->first();

                if ($existingProduct) {
                    $existingChassisNos[] = $item['product_no'];
                }

                // Calculate prices with exchange rate
                $price = $item['purchase_price'];
                $discount = $item['discount'];
                if ($bill->currency_id) {
                    $exchangeRate = $bill->exchange_rate ?? optional($bill->currency)->exchange_rate;
                    if ($exchangeRate && $exchangeRate > 0) {
                        $price = $item['purchase_price'] * $exchangeRate;
                        $discount = $item['discount'] * $exchangeRate;
                    }
                }

                // Simulate line total for check
                $lineTotal = max(($price * $item['qty']) - $discount, 0);
                $totalAfterUpdate += $lineTotal;
            }

            // Validate that new total isn't less than what's paid
            if ($totalAfterUpdate < $bill->getTotalPaid()) {
                DB::rollBack();
                return redirect()->back()->with('error', __('Cannot update. Paid amount is greater than updated bill total.'));
            }

            // Proceed with actual updates now
            foreach ($items as $index => $item) {
                $price = $item['purchase_price'];
                $discount = $item['discount'];
                if ($bill->currency_id) {
                    $exchangeRate = $bill->exchange_rate ?? optional($bill->currency)->exchange_rate;
                    if ($exchangeRate && $exchangeRate > 0) {
                        $price = $item['purchase_price'] * $exchangeRate;
                        $discount = $item['discount'] * $exchangeRate;
                    }
                }

                $productService = SubProduct::find($item['sub_product_id']);
                $productService->chassis_no = $item['product_no'];
                $productService->sale_price = $item['sale_price'];
                $productService->purchase_price = $price - $discount;
                $productService->quantity = $item['qty'];
                $productService->created_by = \Auth::user()->creatorId();
                $productService->flag = 0;
                $productService->bill_id = $request->id;
                // $productService->warehouse_id = $request->warehouse_id;
                $productService->save();

                $productService->appendUploadedGalleryImages($this->billItemRowSubProductImageFiles($request, (int) $index));

                // Update main product total quantity
                $mainProduct = ProductService::find($productService->product_id);
                if ($mainProduct) {
                    $totalQty = SubProduct::where('product_id', $mainProduct->id)->sum('quantity');
                    $mainProduct->quantity = $totalQty;
                    $mainProduct->save();
                }

                // Save custom fields
                if (isset($item['customField'])) {
                    foreach ($item['customField'] as $fieldId => $value) {
                        CustomFieldValue::updateOrCreate(
                            [
                                'record_id' => $productService->id,
                                'field_id' => $fieldId,
                            ],
                            ['value' => $value]
                        );
                    }
                }

                // Update bill product
                $bill_product = BillProduct::where('sub_product_id', $item['sub_product_id'])
                    ->where('bill_id', $request->id)
                    ->first();
                $bill_product->price = $price;
                $bill_product->exchange_price = $item['purchase_price'];
                $bill_product->exchange_discount = $item['discount'];
                $bill_product->discount = $discount;
                $bill_product->quantity = $item['qty'];
                $bill_product->description = $request->description;
                $bill_product->save();
            }

            DB::commit();

            if (!empty($existingChassisNos)) {
                $errorMessage = "Products numbers already exist: " . implode(', ', $existingChassisNos);
                return redirect()->route('bill.index', $request->id)->with('success', $errorMessage);
            }

            return redirect()->route('bill.show', ['bill' => Crypt::encrypt($bill->id)])
                ->with('success', __('Bill successfully updated.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }


    public function getBills($vendorId)
    {

        $bills = Bill::where("vender_id", $vendorId)->whereIn("payment_status", [0, 2])->get();

        return response()->json($bills);
    }

    public function getBillDetails($bill_id)
    {
        $bill = Bill::with('currency')->find($bill_id);
        $currency_symbol = $bill->currency_id && $bill->currency
            ? $bill->currency->symbol
            : \Auth::user()->currencySymbol();

        return response()->json(['due_amount' => $bill->getDue(), 'due_amount_currency' => $bill->getDueInCurrency(), 'currency_symbol' => $currency_symbol]);
    }

    public function getExchangeRate($currencyId)
    {
        // Fetch the currency details based on the provided currency ID
        $currency = Currency::findOrFail($currencyId);
        $formattedNumber = number_format($currency->exchange_rate, 2);

        // Return the exchange rate and currency details as JSON
        return response()->json([
            'exchange_rate' => $formattedNumber,
            'symbol' => $currency->symbol,
            'code' => $currency->code,
        ]);
    }

    public function uploadbill(Request $request)
    {
        // Validate the file
        $request->validate([
            'fileInput.*' => 'required|file|max:10240', // Example validation rules (max size: 10MB)
        ]);
        $billId = $request->input('billId');
        foreach ($request->file('fileInput') as $document) {
            $filenameWithExt = $document->getClientOriginalName();
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension = $document->getClientOriginalExtension();
            $fileNameToStore = $filename . '_' . time() . '.' . $extension;
            // $path = $document->storeAs('uploads/document', $fileNameToStore, 'public');
            $document->move(public_path('documents'), $fileNameToStore);
            // Save the file path to the database
            $accountDocument = new AccountingDocument();
            $accountDocument->document_name = $filenameWithExt;
            $accountDocument->document_path = 'documents/' . $fileNameToStore;
            $accountDocument->bill_id = $billId;
            $accountDocument->save();
        }
        return back()->with('success', 'File uploaded successfully.');
    }


    public function deleteFile(Request $request)
    {
        $fileId = $request->input('document_id');

        // Find the file by ID and delete it (adjust this logic based on your implementation)
        $file = AccountingDocument::find($fileId);
        if ($file) {
            Storage::delete('public/' . $file->document_path);
            $file->delete();
            return back()->with('success', 'File deleted successfully.');
        }
        return back()->with('error', 'File not found.');
    }


    public function showdelete($ids)
    {

        if (\Auth::user()->can('show bill')) {
            try {
                $id = Crypt::decrypt($ids);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Bill Not Found.'));
            }

            $id = Crypt::decrypt($ids);
            $bill = Bill::with('payments.bankAccount', 'items.product.unit')->withTrashed()->find($id);

            if (!empty($bill) && $bill->created_by == \Auth::user()->creatorId()) {
                $vendor = $bill->vender;

                $item = BillProduct::withTrashed()
                    ->where('bill_id', $bill->id)
                    ->paginate(100);
                $accounts = $bill->accounts;
                $items = [];
                if (!empty($item) && count($item) > 0) {
                    foreach ($item as $k => $val) {
                        if (!empty($accounts[$k])) {
                            $val['chart_account_id'] = $accounts[$k]['chart_account_id'];
                            $val['account_id'] = $accounts[$k]['id'];
                            $val['amount'] = $accounts[$k]['price'];
                        }
                        $val['bill_tax'] = $bill->tax_id;
                        $items[] = $val;
                    }
                } else {

                    foreach ($accounts as $k => $val) {
                        $val1['chart_account_id'] = $accounts[$k]['chart_account_id'];
                        $val1['account_id'] = $accounts[$k]['id'];
                        $val1['amount'] = $accounts[$k]['price'];
                        $val1['bill_tax'] = $bill->tax_id;
                        $items[] = $val1;
                    }
                }
                $subProductIds = collect($item)->pluck('sub_product_id')->toArray();
                $subProducts = SubProduct::where('bill_id', '=', $bill->id)->get();
                return view('bill.viewReturn', compact('bill', 'vendor', 'items', 'subProducts'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function showItemdelete($id, $qty, $bill_id)
    {

        if (\Auth::user()->can('show bill')) {

            $id = Crypt::decrypt($id);
            // $item = SubProduct::where('id',$id)->first();
            $item = BillProduct::withTrashed()->where('sub_product_id', $id)->where('bill_id', $bill_id)->first();
            $bill = Bill::withTrashed()->where('id', $bill_id)->first();

            return view('bill.viewItemReturn', compact('item', 'bill', 'qty'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function approve($id)
    {
        if (\Auth::user()->can('approve bill')) {
            $bill = Bill::where('id', $id)->first();
            $bill->send_date = date('Y-m-d');
            $bill->status = 2;
            $bill->save();

            $vender = Vender::where('id', $bill->vender_id)->first();

            $bill->name = !empty($vender) ? $vender->name : '';
            $bill->bill = \Auth::user()->billNumberFormat($bill->bill_id);

            $billId = Crypt::encrypt($bill->id);
            $statusChange = new BillStatusChange();
            $statusChange->bill_id = $bill->id; // Assign the bill_id
            $statusChange->status = 2; // Example status value
            $statusChange->payment_status = -1; // Example payment status value
            $statusChange->changed_at = now(); // Current timestamp
            $statusChange->save();




            return redirect()->back()->with('success', __('Bill successfully approved.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function receive($id)
    {
        if (\Auth::user()->can('receive bill')) {
            $bill = Bill::where('id', $id)->first();
            $bill->send_date = date('Y-m-d');
            $bill->status = 6;
            $bill->save();
            $statusChange = new BillStatusChange();
            $statusChange->bill_id = $bill->id; // Assign the bill_id
            $statusChange->status = 6; // Example status value
            $statusChange->payment_status = -1; // Example payment status value
            $statusChange->changed_at = now(); // Current timestamp
            $statusChange->save();

            return redirect()->back()->with('success', __('Bill successfully received.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
    public function sendtoapprove($id)
    {
        // if (\Auth::user()->can('send to approve bill')) {
        $bill = Bill::where('id', $id)->first();
        $bill->send_date = date('Y-m-d');
        $bill->status = 1;
        $bill->save();
        $statusChange = new BillStatusChange();
        $statusChange->bill_id = $bill->id; // Assign the bill_id
        $statusChange->status = 1; // Example status value
        $statusChange->payment_status = -1; // Example payment status value
        $statusChange->changed_at = now(); // Current timestamp
        $statusChange->save();

        return redirect()->back()->with('success', __('Bill successfully Send To Approve.'));
        // } else {
        //     return redirect()->back()->with('error', __('Permission denied.'));
        // }
    }

    public function notapprove($id)
    {
        if (\Auth::user()->can('approve bill')) {
            $bill = Bill::where('id', $id)->first();
            $bill->send_date = date('Y-m-d');
            $bill->status = 0;
            $bill->save();

            $vender = Vender::where('id', $bill->vender_id)->first();

            $bill->name = !empty($vender) ? $vender->name : '';
            $bill->bill = \Auth::user()->billNumberFormat($bill->bill_id);

            $billId = Crypt::encrypt($bill->id);
            BillStatusChange::where('bill_id', $bill->id)->where('status', 1)->delete();




            return redirect()->back()->with('success', __('Bill successfully approved.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function backtoapprove($id)
    {
        if (\Auth::user()->can('approve bill')) {
            $bill = Bill::where('id', $id)->first();
            $bill->send_date = date('Y-m-d');
            $bill->status = 1;
            $bill->save();


            $bill->name = !empty($vender) ? $vender->name : '';
            $bill->bill = \Auth::user()->billNumberFormat($bill->bill_id);

            $billId = Crypt::encrypt($bill->id);
            BillStatusChange::where('bill_id', $bill->id)->where('status', 2)->delete();




            return redirect()->back()->with('success', __('Bill successfully Back To approve.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function importFile()
    {
        return view('bill.import');
    }

    /**
     * Download sample Excel file for Bill import
     */
    public function downloadSample()
    {
        $creatorId = \Auth::user()->creatorId();
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\BillSampleExport($creatorId),
            'sample-bill.xlsx'
        );
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        try {
            // Increase MySQL settings for large imports
            DB::statement('SET SESSION wait_timeout=28800');
            DB::statement('SET SESSION interactive_timeout=28800');
            DB::reconnect();
            
            // Generate unique import session ID
            $importSessionId = time() . '_' . auth()->id();
            
            // Import to staging table first
            $stagingImport = new \App\Imports\BillStagingImport(auth()->id(), $importSessionId);
            Excel::import($stagingImport, $request->file('file'));

            // Redirect to review page
            return redirect()->route('bill.import.review', ['session_id' => $importSessionId])
                ->with('success', 'File imported to staging. Please review and process.');
        } catch (\Exception $e) {
            \Log::error('Bill import failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id()
            ]);

            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Show staging import review page
     */
    public function importReview(Request $request, $sessionId)
    {
        $stagingProducts = \App\Models\ImportStagingProduct::where('import_session_id', $sessionId)
            ->where('created_by', auth()->id())
            ->orderBy('row_number')
            ->get();

        if ($stagingProducts->isEmpty()) {
            return redirect()->route('bill.file.import')
                ->with('error', 'No staging data found for this import session.');
        }

        $foundCount = $stagingProducts->where('status', 'FOUND')->count();
        $missingCount = $stagingProducts->where('status', 'MISSING')->count();

        return view('bill.import_review', compact('stagingProducts', 'sessionId', 'foundCount', 'missingCount'));
    }

    /**
     * Process staging import - auto-create missing products
     */
    public function processStagingImport(Request $request, $sessionId)
    {
        $request->validate([
            'action' => 'required|in:auto_create,export_missing',
        ]);

        $stagingProducts = \App\Models\ImportStagingProduct::where('import_session_id', $sessionId)
            ->where('created_by', auth()->id())
            ->get();

        if ($stagingProducts->isEmpty()) {
            return back()->with('error', 'No staging data found.');
        }

        if ($request->action == 'auto_create') {
            // Auto-create missing products and process import
            return $this->autoCreateAndProcess($stagingProducts, $sessionId);
        } else {
            // Export missing items
            return $this->exportMissingItems($stagingProducts, $sessionId);
        }
    }

    /**
     * Auto-create missing products and process the import
     */
    private function autoCreateAndProcess($stagingProducts, $sessionId)
    {
        try {
            DB::beginTransaction();

            // Get bill data from first staging product
            $firstProduct = $stagingProducts->first();
            // bill_data is already cast to array in the model, so check if it's already an array
            $billData = is_array($firstProduct->bill_data) 
                ? $firstProduct->bill_data 
                : json_decode($firstProduct->bill_data, true);

            // Create missing products
            $createdProducts = [];
            foreach ($stagingProducts->where('status', 'MISSING') as $stagingProduct) {
                // Generate SKU if not provided
                if (empty($stagingProduct->sku)) {
                    $baseSku = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $stagingProduct->product_name ?? 'PROD'), 0, 15));
                    $sku = $baseSku;
                    $counter = 1;
                    
                    // Ensure SKU is unique
                    while (ProductService::where('sku', $sku)->where('created_by', auth()->id())->exists()) {
                        $sku = $baseSku . '_' . $counter;
                        $counter++;
                    }
                } else {
                    $sku = $stagingProduct->sku;
                }

                // Check if product was already created in this session
                if (isset($createdProducts[$sku])) {
                    $stagingProduct->product_id = $createdProducts[$sku]->id;
                    $stagingProduct->status = 'FOUND';
                    $stagingProduct->status_message = "Product auto-created in this import";
                    $stagingProduct->save();
                    continue;
                }

                // Get or create category by name
                $categoryId = null;
                if (!empty($stagingProduct->category_name)) {
                    $category = ProductServiceCategory::where('created_by', auth()->id())
                        ->where('name', trim($stagingProduct->category_name))
                        ->first();
                    if (!$category) {
                        $category = ProductServiceCategory::create([
                            'name' => trim($stagingProduct->category_name),
                            'created_by' => auth()->id(),
                        ]);
                    }
                    $categoryId = $category->id;
                } else {
                    // Fallback to bill category_id or first available
                    $categoryId = $billData['category_id'] ?? null;
                    if (!$categoryId) {
                        $category = ProductServiceCategory::where('created_by', auth()->id())->first();
                        $categoryId = $category ? $category->id : null;
                    }
                }

                // Get or create brand by name
                $brandId = null;
                if (!empty($stagingProduct->brand_name)) {
                    $brandName = trim($stagingProduct->brand_name);
                    $creatorId = auth()->id();
                    
                    // Check if brand exists (case-insensitive)
                    $brand = \App\Models\Brand::where('created_by', $creatorId)
                        ->whereRaw('LOWER(name) = ?', [strtolower($brandName)])
                        ->first();
                    
                    if (!$brand) {
                        // Check for auto_increment issue (id=0 record or broken auto_increment)
                        $zeroIdBrand = DB::table('brands')->where('id', 0)->first();
                        $hasIdZeroIssue = $zeroIdBrand !== null;
                        
                        // Also check auto_increment value
                        if (!$hasIdZeroIssue) {
                            try {
                                $autoIncrementResult = DB::select("SHOW TABLE STATUS LIKE 'brands'");
                                if (!empty($autoIncrementResult)) {
                                    $autoIncrement = $autoIncrementResult[0]->Auto_increment ?? null;
                                    if ($autoIncrement == 0 || $autoIncrement == 1) {
                                        $maxId = DB::table('brands')->where('id', '>', 0)->max('id');
                                        if ($maxId && $maxId > 0) {
                                            $hasIdZeroIssue = true;
                                        }
                                    }
                                }
                            } catch (\Exception $e) {
                                // If we can't check, assume we need manual insertion to be safe
                                $hasIdZeroIssue = true;
                            }
                        }
                        
                        if ($hasIdZeroIssue) {
                            // Use manual ID insertion
                            $maxId = DB::table('brands')->where('id', '>', 0)->max('id');
                            $nextId = max(1, ($maxId && $maxId > 0 ? (int)$maxId + 1 : 1));
                            
                            // Ensure we never use 0 and check if ID is available
                            while ($nextId == 0 || DB::table('brands')->where('id', $nextId)->exists()) {
                                $nextId++;
                                if ($nextId > 999999999) {
                                    throw new \Exception('ID exceeded maximum value');
                                }
                            }
                            
                            // Update auto_increment to prevent future id=0 issues
                            try {
                                DB::unprepared("ALTER TABLE brands AUTO_INCREMENT = " . (int)($nextId + 1));
                            } catch (\Exception $e) {
                                // Continue anyway - manual ID insertion is working
                            }
                            
                            // Insert with explicit ID
                            DB::table('brands')->insert([
                                'id' => $nextId,
                                'name' => $brandName,
                                'created_by' => $creatorId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            
                            $brand = \App\Models\Brand::find($nextId);
                        } else {
                            // No id=0 issue - use normal Eloquent create with firstOrCreate
                            try {
                                $brand = \App\Models\Brand::firstOrCreate(
                                    [
                                        'name' => $brandName,
                                        'created_by' => $creatorId,
                                    ],
                                    [
                                        'name' => $brandName,
                                        'created_by' => $creatorId,
                                    ]
                                );
                            } catch (\Illuminate\Database\QueryException $e) {
                                // Handle duplicate entry error (race condition)
                                if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
                                    $brand = \App\Models\Brand::whereRaw('LOWER(name) = ?', [strtolower($brandName)])
                                        ->where('created_by', $creatorId)
                                        ->first();
                                    
                                    if (!$brand) {
                                        throw new \Exception('Brand creation failed: ' . $e->getMessage());
                                    }
                                } else {
                                    throw $e;
                                }
                            }
                        }
                        
                        if (!$brand) {
                            throw new \Exception('Failed to create brand: ' . $brandName);
                        }
                    }
                    $brandId = $brand->id;
                    
                    // Link brand to category if category exists
                    if ($categoryId) {
                        $exists = DB::table('brand_category')
                            ->where('brand_id', $brandId)
                            ->where('product_service_category_id', $categoryId)
                            ->exists();
                        if (!$exists) {
                            DB::table('brand_category')->insert([
                                'brand_id' => $brandId,
                                'product_service_category_id' => $categoryId
                            ]);
                        }
                    }
                }

                // Get or create sub-brand by name (linked to brand)
                $subBrandId = null;
                if (!empty($stagingProduct->sub_brand_name)) {
                    $subBrand = \App\Models\VehicleModel::where('created_by', auth()->id())
                        ->where('name', trim($stagingProduct->sub_brand_name))
                        ->where('brand_id', $brandId ?? 0)
                        ->first();
                    if (!$subBrand) {
                        $subBrand = \App\Models\VehicleModel::create([
                            'name' => trim($stagingProduct->sub_brand_name),
                            'brand_id' => $brandId ?? 0,
                            'created_by' => auth()->id(),
                        ]);
                    }
                    $subBrandId = $subBrand->id;
                }

                // Get default unit (get first available unit)
                $unit = \App\Models\ProductServiceUnit::where('created_by', auth()->id())->first();
                $unitId = $unit ? $unit->id : null;

                // Create new product with required fields
                $productData = [
                    'name' => $stagingProduct->product_name ?? 'Imported Product ' . $sku,
                    'sku' => $sku,
                    'sale_price' => $stagingProduct->sale_price ?? 0,
                    'purchase_price' => $stagingProduct->purchase_price ?? 0,
                    'category_id' => $categoryId,
                    'type' => 'product',
                    'created_by' => auth()->id(),
                ];

                // Add brand and sub-brand if available
                if ($brandId) {
                    $productData['brand_id'] = $brandId;
                }
                if ($subBrandId) {
                    $productData['sub_brand_id'] = $subBrandId;
                }

                // Add unit_id if available
                if ($unitId) {
                    $productData['unit_id'] = $unitId;
                }

                $product = ProductService::create($productData);

                $createdProducts[$sku] = $product;
                
                // Update staging product
                $stagingProduct->product_id = $product->id;
                $stagingProduct->status = 'FOUND';
                $stagingProduct->status_message = "Product auto-created: {$product->name} (ID: {$product->id})";
                $stagingProduct->save();
            }

            // Now process the import using the original BillImport class
            // Convert staging products back to array format for BillImport
            $data = $this->convertStagingToImportFormat($stagingProducts, $billData);
            
            // Use BillImport to process
            $billImport = new \App\Imports\BillImport(auth()->id());
            $billImport->array($data);

            // Clean up staging data
            \App\Models\ImportStagingProduct::where('import_session_id', $sessionId)->delete();

            DB::commit();

            return redirect()->route('bill.index')
                ->with('success', 'Bill imported successfully! ' . count($createdProducts) . ' products were auto-created.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Auto-create and process failed', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'user_id' => auth()->id()
            ]);

            return back()->with('error', 'Processing failed: ' . $e->getMessage());
        }
    }

    /**
     * Export missing items to Excel
     */
    private function exportMissingItems($stagingProducts, $sessionId)
    {
        $missingProducts = $stagingProducts->where('status', 'MISSING');

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\MissingProductsExport($missingProducts),
            'missing_products_' . $sessionId . '_' . date('Y-m-d_His') . '.xlsx'
        );
    }

    /**
     * Convert staging products back to import format
     */
    private function convertStagingToImportFormat($stagingProducts, $billData)
    {
        // Reconstruct the array format expected by BillImport
        // Ensure billData is an array
        if (is_string($billData)) {
            $billData = json_decode($billData, true);
        }
        
        $billHeader = array_keys($billData);
        $billdata = array_values($billData);

        // Get headers from first product's custom fields and standard fields
        $firstProduct = $stagingProducts->first();
        // custom_fields is already cast to array in the model
        $firstCustomFields = is_array($firstProduct->custom_fields) 
            ? $firstProduct->custom_fields 
            : (json_decode($firstProduct->custom_fields, true) ?? []);
        
        $subProductHeader = ['product_id', 'quantity', 'sale_price', 'purchase_price', 'discount', 'product_no'];
        $subProductHeader = array_merge($subProductHeader, array_keys($firstCustomFields));

        $subProductRows = [];
        foreach ($stagingProducts as $stagingProduct) {
            if ($stagingProduct->status != 'FOUND' || !$stagingProduct->product_id) {
                continue; // Skip missing products (they should be created by now)
            }

            $row = [
                $stagingProduct->product_id,
                $stagingProduct->quantity,
                $stagingProduct->sale_price,
                $stagingProduct->purchase_price,
                $stagingProduct->discount,
                $stagingProduct->product_no,
            ];

            // Add custom fields (already cast to array in model)
            $customFields = is_array($stagingProduct->custom_fields) 
                ? $stagingProduct->custom_fields 
                : (json_decode($stagingProduct->custom_fields, true) ?? []);
            foreach (array_slice($subProductHeader, 6) as $field) {
                $row[] = $customFields[$field] ?? null;
            }

            $subProductRows[] = $row;
        }

        return [
            $billHeader,
            $billdata,
            $subProductHeader,
            ...$subProductRows
        ];
    }


    public function importBillFromExcel($file)
    {
        // Load the Excel file
        $path = $file->getRealPath();
        $extension = $file->getClientOriginalExtension();

        // Determine the file type based on the extension
        switch ($extension) {
            case 'xlsx':
                $firstSheet = Excel::toArray([], $path, null, \Maatwebsite\Excel\Excel::XLSX); // For XLSX files
                break;
            case 'csv':
                $firstSheet = Excel::toArray([], $path, null, \Maatwebsite\Excel\Excel::CSV); // For CSV files
                break;
            default:
                throw new \Exception('Unsupported file type. Please upload an XLSX or CSV file.');
        }
        $data = $firstSheet[0];
        $billHeader = $data[0];
        $billdata = $data[1];
        $subProductHeader = $data[2];
        $subProductRows = array_slice($data, 3); // The rest are data rows
        $lastBillId = Bill::withTrashed()->latest()->first();
        if ($lastBillId != null) {
            $bill_number = \Auth::user()->billNumberFormat($lastBillId->id);
        } else {
            $bill_number = \Auth::user()->billNumberFormat($this->billNumber());
        }
        try {
            DB::beginTransaction();
            // Map the bill data to your database fields
            $bill = new Bill();
            foreach ($billHeader as $index => $header) {
                if ($header == 'vender_id') {
                    $bill->vender_id = $billdata[$index];
                } elseif ($header == 'bill_date') {
                    $bill->bill_date = Date::excelToDateTimeObject($billdata[$index]);
                } elseif ($header == 'due_date') {
                    $bill->due_date = Date::excelToDateTimeObject($billdata[$index]);
                } elseif ($header == 'warehouse_id') {
                    $bill->warehouse_id = $billdata[$index];
                } elseif ($header == 'category_id') {
                    $bill->category_id = $billdata[$index];
                } elseif ($header == 'order_number') {
                    $bill->order_number = $billdata[$index];
                } elseif ($header == 'salesman_id') {
                    // salesman_id cannot be null, use 0 as default or creatorId if provided
                    $salesmanId = $billdata[$index] ?? null;
                    $bill->salesman_id = !empty($salesmanId) ? $salesmanId : 0;
                } elseif ($header == 'tax_id') {
                    $bill->tax_id = $billdata[$index];
                } elseif ($header == 'currency_id') {
                    if (!empty($billdata[$index])) {
                        $currencyExists = \DB::table('currencies')->where('id', $billdata[$index])->exists();
                        $bill->currency_id = $currencyExists ? $billdata[$index] : null;
                    } else {
                        $bill->currency_id = null; // Explicitly set to null if the value is not provided
                    }
                } elseif ($header == 'exchange_rate') {
                    $bill->exchange_rate = $billdata[$index] != null ? $billdata[$index] : 0;
                }
            }
            $bill->bill_id = $bill_number;
            $bill->created_by = \Auth::user()->creatorId();
            $bill->type = 'Bill';
            $bill->user_type = 'vendor';
            $bill->save();
            $bill->bill_id = \Auth::user()->billNumberFormat($bill->id);
            $bill->save();

            foreach (array_chunk($subProductRows, 50) as $chunk) {
                foreach ($chunk as $subProductRow) {
                    $subProduct = new SubProduct();
                    $final_price = 0;
                    foreach ($subProductHeader as $index => $header) {
                        if ($header == 'product_id') {
                            $subProduct->product_id = $subProductRow[$index];
                        } elseif ($header == 'quantity') {
                            $subProduct->quantity = $subProductRow[$index];
                        } elseif ($header == 'sale_price') {
                            $subProduct->sale_price = $subProductRow[$index];
                        } elseif ($header == 'purchase_price') {
                            $quantity = $subProductRow[array_search('quantity', $subProductHeader)];
                            $price = $subProductRow[$index];
                            if ($billdata[array_search('currency_id', $billHeader)] != 'null') {
                                $curr = Currency::find($billdata[array_search('currency_id', $billHeader)]);
                                if ($billdata[array_search('exchange_rate', $billHeader)] != 'null') {
                                    $subProduct->purchase_price = $price * (int) $billdata[array_search('exchange_rate', $billHeader)];
                                } else {
                                    $subProduct->purchase_price = $price * $curr->exchange_rate;
                                }
                            } else {
                                $subProduct->purchase_price = $price;
                            }
                            $final_price = $price;
                        } elseif ($header == 'product_no') {
                            $subProduct->chassis_no = $subProductRow[$index];
                        }
                        // Map other sub-product fields similarly
                    }
                    $subProduct->bill_id = $bill->id;
                    $subProduct->flag = 0;
                    $subProduct->created_by = \Auth::user()->creatorId();
                    $subProduct->save();
                    $product = ProductService::where('id', $subProduct->product_id)->first();
                    $product->quantity += $subProduct->quantity;
                    $product->save();
                    // If there are custom fields, handle them here
                    $customFields = [];
                    foreach ($subProductHeader as $index => $header) {
                        if (in_array(strtolower($header), array_map('strtolower', ['Gender', 'Color', 'Size', 'Style', 'Number Size', 'Internal Reference']))) {
                            $customFields[$header] = $subProductRow[$index];
                        }
                    }

                    foreach ($customFields as $fieldName => $fieldValue) {
                        $customField = CustomField::whereRaw('LOWER(name) = ?', strtolower($fieldName))
                            ->where('module', 'sub-product')
                            ->first();

                        // if ($customField) {
                        //     $customFieldValues[$customField->id] = $value;
                        // }
                        CustomFieldValue::create([
                            'field_id' => $customField->id,
                            'record_id' => $subProduct->id,
                            'value' => $fieldValue,
                        ]);
                    }

                    BillProduct::create([
                        'bill_id' => $bill->id,
                        'product_id' => $subProduct->product_id,
                        'sub_product_id' => $subProduct->id,
                        'quantity' => $subProduct->quantity,
                        'tax' => $bill->tax_id,
                        'discount' => 0,
                        'price' => $final_price,
                        'description' => '',
                    ]);

                    if (!empty($billdata[array_search('warehouse_id', $billHeader)])) {
                        $isWarehouseProduct = WarehouseProduct::where('product_id', $subProduct->product_id)->where('warehouse_id', $bill->warehouse_id)->first();
                        if ($isWarehouseProduct != null) {
                            $isWarehouseProduct->quantity += $subProduct->quantity;
                            $isWarehouseProduct->created_by = \Auth::user()->creatorId();
                            $isWarehouseProduct->save();
                        } else {
                            $transfer = new WarehouseProduct();
                            $transfer->warehouse_id = $bill->warehouse_id;
                            $transfer->product_id = $subProduct->product_id;
                            $transfer->quantity = $subProduct->quantity;
                            $transfer->created_by = \Auth::user()->creatorId();
                            $transfer->save();
                        }
                    }
                }
            }

            DB::commit();
            return back()->with('success', 'Bill successfully created.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function calculatePurchasePrice($quantity, $price, $exchangeRate = 1)
    {
        // Example calculation logic
        return $quantity * $price * $exchangeRate;
    }
    public function getProductIdByNo($productNo)
    {
        $product = ProductService::where('name', $productNo)->first();
        return $product ? $product->id : null;
    }
    public function getVenderIdByNo($venderNo)
    {
        $vender = Vender::where('name', $venderNo)->first();
        return $vender ? $vender->id : null;
    }
    public function getCategoryIdByNo($productNo)
    {
        $product = ProductServiceCategory::where('name', $productNo)->first();
        return $product ? $product->id : null;
    }
    public function getUserIdByNo($productNo)
    {
        $product = User::where('name', $productNo)->first();
        return $product ? $product->id : \Auth::user()->creatorId();
    }

    public function getCustomFields(Request $request)
    {
        $productId = $request->input('product_id');

        // Get the product and its category
        $product = ProductService::find($productId);

        // Fetch custom fields based on the product category
        $customFields = CustomField::forCategory($product->category_id)->where('show_in_bill', 1)->get();

        return response()->json([
            'customFields' => $customFields,
        ]);
    }

    public function fetchItems($id)
    {
        $items = BillProduct::with(['product', 'subProduct.productService.category', 'taxObject'])
            ->where('bill_id', $id)
            ->paginate(50); // Adjust per page

        return view('bill.items-partial', ['paginatedItems' => $items])->render();
    }
    public function calculateAverageRate(Request $request, Bill $bill)
    {
        try {
            if (!$bill->currency_id) {
                return response()->json(['success' => false, 'message' => 'Bill does not have a currency.']);
            }

            $currency = Currency::find($bill->currency_id);
            if (!$currency || !$currency->exchange_rate) {
                return response()->json(['success' => false, 'message' => 'Currency exchange rate not found.']);
            }

            $payments = $bill->payments;

            $totalPaid = $payments->sum('amount');
            $convertedTotal = 0;

            foreach ($payments as $payment) {
                $convertedTotal += ($payment->amount_in_currency ?? 0);
            }

            if ($convertedTotal == 0) {
                return response()->json(['success' => false, 'message' => 'Cannot divide by zero.']);
            }

            $newRate = $totalPaid / $convertedTotal;
            $bill->exchange_rate = round($newRate, 6);
            $bill->save();
            foreach ($bill->items as $item) {
                $subprouduct = SubProduct::find($item->sub_product_id);
                $originalAmount = $item->exchange_price ?? 0;
                $originalDiscount = $item->exchange_discount ?? 0;

                $convertedPrice = $originalAmount * round($newRate, 6);
                $convertedDiscount = $originalDiscount * round($newRate, 6);

                $item->price = $convertedPrice;
                $item->discount = $convertedDiscount;
                $item->save();
                $subprouduct->purchase_price = ($convertedPrice) - ($convertedDiscount);
                $subprouduct->save();
            }
            // Optional: update bill or its products here

            return response()->json([
                'success' => true,
                'new_rate' => round($newRate, 6),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ]);
        }
    }

    public function bill_ledger($bill_id)
    {
        try {

            if (\Auth::user()->can('ledger report')) {

                $start = date('Y-m-01');
                $end = date('Y-m-t');
                $chart_accounts = ChartOfAccount::where('created_by', \Auth::user()->creatorId())->get();
                $accounts = $chart_accounts->pluck('name', 'id');
                $generalLedgerData = GeneralLedger::selectRaw('vid, account, ref_id , type,user_id, SUM(credit) as total_credit, SUM(debit) as total_debit ,created_at,updated_at,send_date,deleted_qty,sub_product_id,user_type')
                    ->where('created_by', \Auth::user()->creatorId())
                    ->where('ref_id', $bill_id)
                    ->where('reference', 'Bill')
                    ->groupBy('vid', 'account')
                    ->orderBy('id', 'ASC')
                    ->get();
                //  GeneralLedger::all();



                $balance = 0;
                $debit = 0;
                $credit = 0;
                $filter['balance'] = $balance;
                $filter['credit'] = $credit;
                $filter['debit'] = $debit;
                $filter['startDateRange'] = $start;
                $filter['endDateRange'] = $end;
                // dd($generalLedgerData);
                return view('report.general_ledger', compact('filter', 'chart_accounts', 'accounts', 'generalLedgerData'));
            } else {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        } catch (\Exception $e) {
            // Log the error for debugging
            // Log::error('Error fetching general ledger data: ' . $e->getMessage());

            // Return a user-friendly error message
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}

