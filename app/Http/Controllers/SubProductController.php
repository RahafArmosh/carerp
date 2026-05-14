<?php

namespace App\Http\Controllers;

use App\Models\SubProduct;
use App\Models\CustomField;
use App\Models\ProductService;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\PosProduct;
use App\Models\BillProduct;
use App\Models\Bill;
use App\Models\Tax;
use App\Models\Vender;
use App\Models\ProductServiceCategory;
use App\Models\GeneralLedger;
use App\Models\Customer;
use App\Models\Color;
use App\Models\Country;
use App\Models\CustomFieldValue;
use App\Models\StockMovement;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\CarAccessoryRequestItem;
use App\Models\DirectExpenseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use App\Models\Utility;
use App\Models\ChartOfAccount;
use App\Imports\SubProductImport;
use App\Imports\SubProductUpdateImport;
use App\Models\warehouse;
use App\Models\Brand;
use App\Models\VehicleModel;
use App\Models\User;
use App\Models\Asn;
use App\Models\AsnItem;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class SubProductController extends Controller
{
    // Method to display all sub-products
    public function index($id, Request $request)
    {
        if (\Auth::user()->can('sub products')) {
            // dd($request->all());
            $query = SubProduct::where('product_id', '=', $id)->where('flag', '!=', 2);
            if ($request->has('exterior_color_id') && $request->input('exterior_color_id') !== null) {
                $exterior_color_id = $request->input('exterior_color_id');
                $query->where('exterior_color_id', $exterior_color_id);
            }

            if ($request->has('interior_color_id') && $request->input('interior_color_id') !== null) {
                $interior_color_id = $request->input('interior_color_id');
                $query->where('interior_color_id', $interior_color_id);
            }

            if ($request->has('location') && $request->input('location') !== null) {
                $location = $request->input('location');

                // Assuming you have relationships defined in your models
                // $query->whereHas('bill', function ($query) use ($location) {
                //     $query->whereHas('warehouse', function ($query) use ($location) {
                //         $query->where('country_id', $location);
                //     });
                // });
                $query->where('country_id', $location);
            }
            $query->orderBy('created_at', 'desc');
            $subProducts = $query->with('warehouse')->paginate(10);
            // $subProducts = SubProduct::where('product_id', '=', $id)->where('flag','!=',2)->get();
            $product_id = $id;
            $colors     = Color::get()->pluck('name', 'id');
            $colors->prepend('Select Color', '');
            $countries     = Country::get()->pluck('name', 'id');
            $countries->prepend('Select Country', '');
            $warehouses = warehouse::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $warehouses->prepend('Select Warehouse', '');
            $product_cat = ProductService::where('id', $id)->first()->category_id;
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'sub-product')->forCategory($product_cat)->get();
            $customFieldValues = [];
            foreach ($subProducts as $subProduct) {
                $customFieldValues[$subProduct->id] = CustomFieldValue::where('record_id', $subProduct->id)
                    ->whereIn('field_id', $customFields->pluck('id'))
                    ->get()
                    ->keyBy('field_id')
                    ->map(function ($item) {
                        return $item->value;
                    });
            }
            return view('subproducts.index', compact('subProducts', 'product_id', 'colors', 'countries', 'warehouses', 'customFields', 'customFieldValues'));
        }
    }

    // Method to create a new sub-product
    public function create($id)
    {
        if (\Auth::user()->can('create sub-products')) {
            $colors = Color::get()->pluck('name', 'id');
            $countries     = Country::get()->pluck('name', 'id');
            $product_cat = \App\Models\ProductService::where("id", $id)->first()->category_id;
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'sub-product')->forCategory($product_cat)->get();

            return view('subproducts.create', compact('id', 'customFields', 'colors', 'countries'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    // Method to store a new sub-product
    public function store(Request $request)
    {

        if (\Auth::user()->can('create sub-products')) {

            $rules = [
                // 'chassis_no' => 'required',
                'product_no' => 'required',
                'sale_price' => 'required|numeric',
                'purchase_price' => 'required|numeric',
                'product_id' => 'required',
                'sub_product_images' => 'nullable|array',
                'sub_product_images.*' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:10240',
            ];

            $validator = \Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->route('subProducts', ['id' => $request->id])->with('error', $messages->first());
            }

            $productService                      = new SubProduct();
            $productService->product_no                = $request->product_no;
            // $productService->engine_no              = $request->engine_no;
            $productService->sale_price          = $request->sale_price;
            $productService->purchase_price      = $request->purchase_price;
            $productService->product_id          = $request->id;
            $productService->quantity          = $request->quantity;
            $productService->initial_stock          = $request->initial_stock;
            $productService->initial_rate          = $request->initial_rate;




            $productService->created_by       = \Auth::user()->creatorId();
            $productService->flag       = 1;
            $productService->save();
            // CustomField::saveData($productService, $request->customField);
            // Store custom fields
            if ($request->has('customField')) {
                foreach ($request->customField as $fieldId => $value) {
                    CustomFieldValue::create([
                        'record_id' => $productService->id,
                        'field_id' => $fieldId,
                        'value' => $value,
                    ]);
                }
            }

            $this->appendSubProductGallery($request, $productService);

            return redirect()->route('subProducts', ['id' => $request->id])->with('success', __('Sub Product successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    // Method to display a specific sub-product
    public function show($id)
    {
        $subProduct = SubProduct::findOrFail($id);
        return view('subproducts.show', compact('subProduct'));
    }

    // Method to edit a specific sub-product
    public function edit($id)
    {
        $productService = SubProduct::with('images')->find($id);
        $productService_Id = $productService->product_id;

        if (\Auth::user()->can('edit sub-products')) {
            if ($productService->created_by == \Auth::user()->creatorId()) {
                $colors = Color::get()->pluck('name', 'id');
                $countries     = Country::get()->pluck('name', 'id');
                $warehouses = warehouse::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $warehouses->prepend('Select Warehouse', '');
                $product_cat = \App\Models\ProductService::where("id", $productService_Id)->first()->category_id;
                $customFields                = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'sub-product')->forCategory($product_cat)->get();
                // Retrieve existing custom field values for the sub-product
                $customFieldValues = CustomFieldValue::where('record_id', $productService->id)->pluck('value', 'field_id');
                return view('subproducts.edit', compact('productService', 'customFields', 'productService_Id', 'colors', 'warehouses', 'customFieldValues'));
            } else {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }


    // Method to update a specific sub-product
    public function update(Request $request, $id)
    {

        if (\Auth::user()->can('edit sub-products')) {
            $productService = SubProduct::with('images')->find($id);
            if (! $productService) {
                return redirect()->back()->with('error', __('Sub product not found.'));
            }
            $product = ProductService::where('id',$productService->product_id)->first();
            $today = now()->toDateString();
            $oldValue = $productService->initial_stock * $productService->initial_rate;
            if ($productService->created_by == \Auth::user()->creatorId()) {
                $rules = [
                    'product_no' => 'required',
                    'product_id' => 'required',
                    'sale_price' => 'required|numeric',
                    'purchase_price' => 'required|numeric',
                    'sub_product_images' => 'nullable|array',
                    'sub_product_images.*' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:10240',
                    'delete_image_ids' => 'nullable|array',
                    'delete_image_ids.*' => 'integer',
                    // 'engine_no' => 'required',

                ];

                $validator = \Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->route('subProducts', ['id' => $request->productService_Id])->with('error', $messages->first());
                }

                $deleteIds = array_filter(array_map('intval', (array) $request->input('delete_image_ids', [])));
                if (! empty($deleteIds)) {
                    $productService->images()->whereIn('id', $deleteIds)->get()->each->delete();
                }

                $productService->product_no = $request->product_no;
                // $productService->product_id    = $request->productService_Id;
                $productService->quantity = $request->quantity;
                $productService->sale_price = $request->sale_price;
                $productService->purchase_price = $request->purchase_price;
                $productService->warehouse_id = $request->warehouse_id ?? null;

                $productService->created_by = \Auth::user()->creatorId();
                $productService->flag = $request->flag;
                $productService->booked = $request->booked;
                $productService->initial_stock = $request->initial_stock;
                $productService->initial_rate = $request->initial_rate;

                // $productService->location_id          = $request->location_id;
                $productService->save();
                // CustomField::saveData($productService, $request->customField);
                // Update custom fields
                if ($request->has('customField')) {
                    foreach ($request->customField as $fieldId => $value) {
                        CustomFieldValue::updateOrCreate(
                            ['record_id' => $productService->id, 'field_id' => $fieldId],
                            ['value' => $value]
                        );
                    }
                }


                // Determine inventory account
                $inventoryAccount = $product->category && $product->category->purchase_account_id
                    ? ChartOfAccount::where('created_by', \Auth::user()->creatorId())
                        ->where('id', $product->category->purchase_account_id)
                        ->first()
                    : ChartOfAccount::where('created_by', \Auth::user()->creatorId())
                        ->where('name', 'inventory')
                        ->first();

                // Check for existing inventory ledger entry
                $existingInventory = GeneralLedger::where('account', $inventoryAccount->id)
                    ->where('reference', 'opening balance')
                    // ->whereNull('user_id')
                    ->first();

                $inventoryAmount = $request->initial_stock * $request->initial_rate;
                // dd($existingInventory);  
                if ($existingInventory) {
                    $existingInventory->update([
                        'debit' => ($existingInventory->debit - $oldValue) + $inventoryAmount,
                        'credit' => 0,
                    ]);
                } 

                // Create or update stock movement for opening balance
                // Check if there's an existing opening balance stock movement for this sub-product
                $existingStockMovement = StockMovement::where('sub_product_id', $productService->id)
                    ->where('activity', 'Opening Balance')
                    ->where('created_by', \Auth::user()->creatorId())
                    ->first();

                if ($request->initial_stock > 0 && $request->initial_rate > 0) {
                    // Opening balance has a value - create or update stock movement
                    if ($existingStockMovement) {
                        // Update existing stock movement
                        $existingStockMovement->update([
                            'product_id' => $productService->product_id,
                            'qty_in' => $request->initial_stock,
                            'qty_out' => 0,
                            'avg_cost' => $request->initial_rate,
                            'cost_price' => $request->initial_rate,
                        ]);
                    } else {
                        // Create new stock movement entry if it doesn't exist
                        StockMovement::create([
                            'product_id' => $productService->product_id,
                            'sub_product_id' => $productService->id,
                            'bill_id' => null,
                            'invoice_id' => null,
                            'pos_id' => null,
                            'qty_in' => $request->initial_stock,
                            'qty_out' => 0,
                            'avg_cost' => $request->initial_rate,
                            'cost_price' => $request->initial_rate,
                            'activity' => 'Opening Balance',
                            'use_id' => null,
                            'item' => $productService->id,
                            'created_by' => \Auth::user()->creatorId(),
                        ]);
                    }
                } else {
                    // Opening balance is 0 or invalid - delete existing stock movement if it exists
                    if ($existingStockMovement) {
                        $existingStockMovement->delete();
                    }
                }

                // Get adjustment account
                // $adjustmentAccount = ChartOfAccount::where('created_by', \Auth::user()->creatorId())
                //     ->where('name', 'Opening Balances and adjustments')
                //     ->first();

                // Check for existing adjustment entry
                // $existingAdjustment = GeneralLedger::where('account', $adjustmentAccount->id)
                //     ->where('reference', 'opening balance')
                //     // ->whereNull('user_id')
                //     ->first();

                // if ($existingAdjustment) {
                //     $existingAdjustment->update([
                //         'debit' => 0,
                //         'credit' => ($existingAdjustment->credit - $oldValue) + $inventoryAmount,
                //         'send_date' => $today,
                //     ]);
                // } 

                $this->appendSubProductGallery($request, $productService);

                return redirect()->route('subProducts', ['id' => $productService->product_id])->with('success', __('Sub Product successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Append gallery images for a sub-product (same storage pattern as product gallery).
     */
    protected function appendSubProductGallery(Request $request, SubProduct $subProduct): void
    {
        $files = $request->file('sub_product_images', []);
        if ($files === null) {
            return;
        }
        if (! is_array($files)) {
            $files = [$files];
        }
        $subProduct->appendUploadedGalleryImages($files);
    }

    /**
     * Update stock-report editable fields for a sub-product.
     */
    public function updateLocation(Request $request, $id)
    {
        if (!\Auth::user()->can('edit sub-products')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $subProduct = SubProduct::where('id', $id)->where('created_by', \Auth::user()->creatorId())->first();
        if (!$subProduct) {
            return redirect()->back()->with('error', __('Sub product not found.'));
        }

        $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'sale_price' => 'nullable|numeric|min:0',
        ]);

        if ($request->filled('sale_price')) {
            $subProduct->sale_price = $request->sale_price;
            $subProduct->save();
        }

        if ($request->has('warehouse_id') && $request->warehouse_id !== '') {
            $warehouse = warehouse::where('id', $request->warehouse_id)->where('created_by', \Auth::user()->creatorId())->first();
            if ($warehouse) {
                $subProduct->warehouse_id = $warehouse->id;
                $subProduct->save();
            }
        } else {
            $subProduct->warehouse_id = null;
            $subProduct->save();
        }

        if ($request->has('customField') && is_array($request->customField)) {
            $validFieldIds = CustomField::where('module', 'sub-product')
                ->where('created_by', \Auth::user()->creatorId())
                ->pluck('id');
            foreach ($request->customField as $fieldId => $value) {
                if ($validFieldIds->contains((int) $fieldId)) {
                    CustomFieldValue::updateOrCreate(
                        ['record_id' => $subProduct->id, 'field_id' => (int) $fieldId],
                        ['value' => $value !== null ? (string) $value : '']
                    );
                }
            }
        }

        return redirect()->back()->with('success', __('Warehouse and location updated successfully.'));
    }

    // Method to delete a specific sub-product
    public function destroy($id)
    {
        if (\Auth::user()->can('delete sub-products')) {
            $productService = SubProduct::find($id);
            $productService_ID =  $productService->product_id;
            if ($productService->created_by == \Auth::user()->creatorId()) {

                $productService->flag = 2;
                $productService->save();

                return redirect()->route('subProducts', ['id' => $productService_ID])->with('success', __('Sub Product successfully cancelled.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function getSubProductCustomFields(Request $request)
    {
        $productId = $request->input('productId');
        if ($productId != null) {
            Session::put('item_id', $productId);
        }
        // Fetch custom fields for sub-products based on the selected product ID
        $product_cat = ProductService::where('id', $productId)->first()->category_id;
        $customFields = CustomField::where('created_by', \Auth::user()->creatorId())
            ->where('module', 'sub-product')
            ->forCategory($product_cat)
            ->get();

        return response()->json($customFields);
    }


    public function getSubProducts(Request $request, $productId)
    {

        // if (!is_null($colorId) && !is_null($colorIdIn) && !is_null($country) && $colorId !== "-1" && $colorIdIn !== "-1" && $country !== "-1") {
        //     $subProducts = SubProduct::select('sub_products.*', 'countries.name as country_name')
        //     ->join('bills', 'sub_products.bill_id', '=', 'bills.id')
        //     ->join('warehouses', 'bills.warehouse_id', '=', 'warehouses.id')
        //     ->join('countries', 'warehouses.country_id', '=', 'countries.id')
        //     ->where('sub_products.product_id', $productId)
        //     ->where('sub_products.flag', '!=', 2)
        //     ->where('sub_products.booked', 0)
        //     ->where('sub_products.exterior_color_id', $colorId)
        //     ->where('sub_products.interior_color_id', $colorIdIn)
        //     ->where('warehouses.country_id', $country)
        //     ->get();
        // }
        // elseif (!is_null($colorId) && $colorIdIn === "-1" && $colorId !== "-1" && $country === "-1") {
        //     $subProducts = SubProduct::select('sub_products.*', 'countries.name as country_name')
        //     ->join('bills', 'sub_products.bill_id', '=', 'bills.id')
        //     ->join('warehouses', 'bills.warehouse_id', '=', 'warehouses.id')
        //     ->join('countries', 'warehouses.country_id', '=', 'countries.id')
        //     ->where('sub_products.product_id', $productId)
        //     ->where('sub_products.flag', '!=', 2)
        //     ->where('sub_products.booked', 0)
        //     ->where('sub_products.exterior_color_id', $colorId)
        //     ->get();
        // }
        // elseif (!is_null($colorIdIn) && $colorId === "-1" && $country === "-1" && $colorIdIn !== "-1") {
        //     $subProducts = SubProduct::select('sub_products.*', 'countries.name as country_name')
        //     ->join('bills', 'sub_products.bill_id', '=', 'bills.id')
        //     ->join('warehouses', 'bills.warehouse_id', '=', 'warehouses.id')
        //     ->join('countries', 'warehouses.country_id', '=', 'countries.id')
        //     ->where('sub_products.product_id', $productId)
        //     ->where('sub_products.flag', '!=', 2)
        //     ->where('sub_products.booked', 0)
        //     ->where('sub_products.interior_color_id', $colorIdIn)
        //     ->get();
        // }
        // elseif (!is_null($colorIdIn) && !is_null($colorId)  && $country === "-1" && $colorIdIn !== "-1" && $colorId !== "-1") {
        //     $subProducts = SubProduct::select('sub_products.*', 'countries.name as country_name')
        //     ->join('bills', 'sub_products.bill_id', '=', 'bills.id')
        //     ->join('warehouses', 'bills.warehouse_id', '=', 'warehouses.id')
        //     ->join('countries', 'warehouses.country_id', '=', 'countries.id')
        //     ->where('sub_products.product_id', $productId)
        //     ->where('sub_products.flag', '!=', 2)
        //     ->where('sub_products.booked', 0)
        //     ->where('sub_products.exterior_color_id', $colorId)
        //     ->where('sub_products.interior_color_id', $colorIdIn)
        //     ->get();
        // }
        // elseif (!is_null($country) && $colorId === "-1" && $colorIdIn === "-1" && $country !== "-1") {
        //     $subProducts = SubProduct::select('sub_products.*', 'countries.name as country_name')
        //     ->join('bills', 'sub_products.bill_id', '=', 'bills.id')
        //     ->join('warehouses', 'bills.warehouse_id', '=', 'warehouses.id')
        //     ->join('countries', 'warehouses.country_id', '=', 'countries.id')
        //     ->where('sub_products.product_id', $productId)
        //     ->where('sub_products.flag', '!=', 2)
        //     ->where('sub_products.booked', 0)
        //     ->where('warehouses.country_id', $country)
        //     ->get();
        // }
        // elseif (!is_null($country) && !is_null($colorId) && $colorId  !== "-1" && $colorIdIn === "-1" && $country !== "-1") {
        //     $subProducts = SubProduct::select('sub_products.*', 'countries.name as country_name')
        //     ->join('bills', 'sub_products.bill_id', '=', 'bills.id')
        //     ->join('warehouses', 'bills.warehouse_id', '=', 'warehouses.id')
        //     ->join('countries', 'warehouses.country_id', '=', 'countries.id')
        //     ->where('sub_products.product_id', $productId)
        //     ->where('sub_products.flag', '!=', 2)
        //     ->where('sub_products.booked', 0)
        //     ->where('warehouses.country_id', $country)
        //     ->where('sub_products.exterior_color_id', $colorId)
        //     ->get();
        // }
        // elseif (!is_null($country) && !is_null($colorIdIn) && $colorIdIn  !== "-1" && $colorId === "-1" && $country !== "-1") {
        //     $subProducts = SubProduct::select('sub_products.*', 'countries.name as country_name')
        //     ->join('bills', 'sub_products.bill_id', '=', 'bills.id')
        //     ->join('warehouses', 'bills.warehouse_id', '=', 'warehouses.id')
        //     ->join('countries', 'warehouses.country_id', '=', 'countries.id')
        //     ->where('sub_products.product_id', $productId)
        //     ->where('sub_products.flag', '!=', 2)
        //     ->where('sub_products.booked', 0)
        //     ->where('warehouses.country_id', $country)
        //     ->where('sub_products.interior_color_id', $colorIdIn)
        //     ->get();
        // }
        // else{
        $subProducts = SubProduct::select('sub_products.*')
            // ->join('bills', 'sub_products.bill_id', '=', 'bills.id')
            // ->join('warehouses', 'bills.warehouse_id', '=', 'warehouses.id')
            // ->join('countries', 'warehouses.country_id', '=', 'countries.id')
            ->where('sub_products.product_id', $productId)
            ->where('sub_products.flag', '!=', 2)
            ->where('sub_products.booked', 0)
            ->get();
        // }
        // Fetch custom fields and their values for the sub-products
        $customFieldsData = [];
        foreach ($subProducts as $subProduct) {
            $customFields = CustomField::where('module', 'sub-product')
                ->forCategory($subProduct->productService->category_id) // Assuming category_id is linked to the product
                ->where('show_in_invoice',1)
                ->get();

            $customFieldValues = [];
            foreach ($customFields as $field) {
                $value = CustomFieldValue::where('field_id', $field->id)
                    ->where('record_id', $subProduct->id)
                    ->pluck('value')
                    ->first();
                $customFieldValues[$field->name] = $value;
            }
            $customFieldsData[$subProduct->id] = $customFieldValues;
        }

        return response()->json([
            'subProducts' => $subProducts,
            'customFieldsData' => $customFieldsData
        ]);
    }

    /**
     * Get subproduct quantities grouped by warehouse location for a specific product
     */
    public function getSubProductQuantitiesByWarehouse(Request $request, $productId)
    {
        try {
            // Get the product and its category
            $product = ProductService::find($productId);
            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found.'
                ], 404);
            }
            $categoryId = $product->category_id;

            // Get all custom fields for sub-products in this category whose name contains 'color' (case-insensitive)
            $colorFields = CustomField::where('module', 'sub-product')
                ->forCategory($categoryId)
                ->where('show_in_invoice', 1)
                ->get();

            if ($colorFields->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No color-related custom fields found.'
                ], 404);
            }

            $colorFieldIds = $colorFields->pluck('id');

            // Get all warehouses
            $warehouses = warehouse::where('created_by', \Auth::user()->creatorId())->get();
            $result = [];

            foreach ($warehouses as $warehouse) {
                // Get subproducts for this product in this warehouse
                $subProducts = SubProduct::where('product_id', $productId)
                    ->where('warehouse_id', $warehouse->id)
                    ->where('flag', '!=', 2)
                    ->where('booked', 0)
                    ->where('created_by',\Auth::user()->creatorId())
                    ->get();

                $grouped = [];
                foreach ($subProducts as $subProduct) {
                    $colorValues = [];
                    foreach ($colorFieldIds as $fieldId) {
                        $field = CustomField::find($fieldId);
                        $value = CustomFieldValue::where('field_id', $fieldId)
                            ->where('record_id', $subProduct->id)
                            ->pluck('value')
                            ->first() ?? 'N/A';
                        $colorValues[$field->name] = $value;
                    }
                    $combination = implode('/', $colorValues);
                    $key = $combination;
                    if (!isset($grouped[$key])) {
                        $grouped[$key] = [
                            'quantity' => 0,
                            'combination' => $colorValues,
                            'warehouse_id' => $warehouse->id,
                            'country' => $warehouse->country ? ' ' .$warehouse->name . '/'. $warehouse->country->name : '',
                        ];
                    }
                    $grouped[$key]['quantity'] += $subProduct->quantity;
                }
                // Add each group to the result if quantity > 0
                foreach ($grouped as $g) {
                    if ($g['quantity'] > 0) {
                        $result[] = $g;
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching warehouse quantities: ' . $e->getMessage()
            ], 500);
        }
    }
    public function sent($id)
    {
        $productService = SubProduct::find($id);
        if (Invoice::where('id', $productService->invoice_id)->first()->type == 'rent') {
            $productService->booked = 0;
            $productService->invoice_id = null;
            $productService->quantity =  1;
            $productService->save();
            

        } elseif (Invoice::where('id', $productService->invoice_id)->first()->type == 'regular') {
            $productService->booked = 3;
            $productService->save();
        }
        return redirect()->back()->with('success', __('Product successfully updated.'));
    }
    public function destroyInvoice($id)
    {
        try {
            $productService = SubProduct::find($id);
            if (empty(request()->input('delete_date'))) {
                $dateToDelete = now()->toDateString();
            } else {
                $dateToDelete = request()->input('delete_date');
                $productCreatedAt = strtotime($productService->created_at);

                // Convert the input date to a timestamp
                $inputDateTimestamp = strtotime($dateToDelete);
                if ($inputDateTimestamp < $productCreatedAt) {
                    return redirect()->back()->with('error', __("Entered date is not greater than item's created date"));
                }
            }
            DB::beginTransaction();
            $invoice = Invoice::where('id', $productService->invoice_id)->first();
            $totalTaxPrice = 0;
            $taxes = \App\Models\Utility::tax($invoice->tax_id);
            foreach ($taxes as $tax) {
                $taxPrice = \App\Models\Utility::taxRate($tax->rate, $productService->sale_price, 1, 0);
                if ($productService->productService->category->type === "Qty product") {
                    $invoiceProduct = InvoiceProduct::where('sub_product_id', $productService->id)->first();
                    $totalTaxPrice += $taxPrice * $invoiceProduct->price;
                } else {
                    $totalTaxPrice += $taxPrice;
                }
            }
            // Add entries to General Ledger

            $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
            // Extract the vid value from the last record and increment it
            if ($latestVoucher) {
                $lastVid = $latestVoucher->vid;
                $newVid = $lastVid + 1;
            } else {
                // If no record exists, start with 1
                $newVid = 1;
            }
            $existingRecord = GeneralLedger::where('vid', $newVid)->where('created_by', \Auth::user()->creatorId())->exists();

            if ($existingRecord) {
                return redirect()->back()->with('error', __("something went wrong , please try again."));
            }

            // Retrieve the chart account ID for the category
            $categoryChartAccountId = \App\Models\ProductServiceCategory::where('id', $invoice->category_id)->first()->sale_account_id;
            $customer = Customer::where('id', $invoice->customer_id)->first();
            if (empty(request()->input('delete_qty'))) {
                // Create a new entry for debit the category account
                $newEntryCategory = new GeneralLedger();
                $newEntryCategory->vid = $newVid;
                $newEntryCategory->account = $categoryChartAccountId;
                $newEntryCategory->type = 'Invoice Delete Product ' . \Auth::user()->invoiceNumberFormat($invoice->id);
                $newEntryCategory->debit = $productService->sale_price; // Example value
                $newEntryCategory->credit = 0; // Example value
                $newEntryCategory->ref_id = $invoice->id;
                $newEntryCategory->user_id = 0;
                $newEntryCategory->created_by = \Auth::user()->creatorId();
                $newEntryCategory->send_date = $dateToDelete;
                $newEntryCategory->sub_product_id = $productService->id;
                $newEntryCategory->reference = 'Invoice Delete Product';
                $newEntryCategory->save();


                // Retrieve the chart account ID for the tax
                $taxChartAccountId = \App\Models\Tax::where('id', $invoice->tax_id)->first()->chart_account_id;

                // Create a new entry debit for the tax account
                $newEntryTax = new GeneralLedger();
                $newEntryTax->vid = $newVid;
                $newEntryTax->account = $taxChartAccountId;
                $newEntryTax->type = 'Invoice Delete Product ' . \Auth::user()->invoiceNumberFormat($invoice->id);
                $newEntryTax->debit = $totalTaxPrice; // Example value
                $newEntryTax->credit = 0; // Example value
                $newEntryTax->ref_id = $invoice->id;
                $newEntryTax->user_id = 0;
                $newEntryTax->created_by = \Auth::user()->creatorId();
                $newEntryTax->send_date = $dateToDelete;
                $newEntryTax->sub_product_id = $productService->id;
                $newEntryTax->reference = 'Invoice Delete Product';
                $newEntryTax->save();


                // Retrieve the chart account ID for the customer

                $customerChartAccountId = $customer->chart_account_id;

                // Create a new entry cedit for the customer account
                $newEntryCustomer = new GeneralLedger();
                $newEntryCustomer->vid = $newVid;
                $newEntryCustomer->account = $customerChartAccountId;
                $newEntryCustomer->type = 'Invoice Delete Product ' . \Auth::user()->invoiceNumberFormat($invoice->id);
                $newEntryCustomer->debit = 0; // Example value
                $newEntryCustomer->credit = $productService->sale_price + $totalTaxPrice; // Example value
                $newEntryCustomer->ref_id = $invoice->id;
                $newEntryCustomer->user_id = $customer->id;
                $newEntryCustomer->created_by = \Auth::user()->creatorId();
                $newEntryCustomer->balance = $customer->balance;
                $newEntryCustomer->send_date = $dateToDelete;
                $newEntryCustomer->sub_product_id = $productService->id;
                $newEntryCustomer->reference = 'Invoice Delete Product';
                $newEntryCustomer->save();


                // Get invoice product for quantity if available
                $invoiceProduct = InvoiceProduct::where('sub_product_id', $productService->id)->first();
                $product = $productService->productService;
                
                // Calculate product cost - use avg_cost if > 0, otherwise use purchase_price
                $product_cost = ($product->avg_cost > 0) ? $product->avg_cost : $productService->purchase_price;
                $quantity = $invoiceProduct ? $invoiceProduct->quantity : 1;
                $itemAmount_purchase = $product_cost * $quantity;
                
                ///////////////////////////////////////
                // Add records if product type is 'product'
                if ($productService->productService->type == 'product' && $invoice->type == "regular") {
                    // Retrieve the chart account ID for the purchase
                    $purchaseAccountId = \App\Models\ProductServiceCategory::where('id', $productService->productService->category_id)->first()->purchase_account_id;

                    // Calculate the sum of direct expenses related to this item's sub_product_id
                    // Only include expenses where chart_account_id matches the purchase_account_id
                    $directExpenseAmount = 0;
                    if ($productService->id && $purchaseAccountId) {
                        $directExpenseAmount = \App\Models\DirectExpenseItem::where('sub_product_id', $productService->id)
                            ->where('chart_account_id', $purchaseAccountId)
                            ->whereHas('directExpense', function ($query) {
                                $query->where('created_by', \Auth::user()->creatorId());
                            })
                            ->sum('amount');
                    } else {
                        $directExpenseAmount = 0;
                    }

                    // Calculate the sum of sell_price from car_accessory_request_items related to this item
                    $carAccessoryAmount = 0;
                    if ($productService->id) {
                        $carAccessoryAmount = \App\Models\CarAccessoryRequestItem::where(function ($query) use ($productService) {
                            $query->where('car_id', $productService->id)
                                ->orWhere('accessory_id', $productService->id);
                        })
                            ->whereHas('request', function ($query) {
                                $query->where('created_by', \Auth::user()->creatorId());
                            })
                            ->sum('sell_price');
                    }

                    // Add direct expense amount and car accessory amount to the purchase amount
                    $itemAmount_purchase += $directExpenseAmount + $carAccessoryAmount;

                    // Retrieve the chart account ID for the expense
                    $expenseAccountId = \App\Models\ProductServiceCategory::where('id', $productService->productService->category_id)->first()->expense_account_id;

                    // Create a new entry for the purchase account (debit) - Reversed from send function
                    $newEntryCredit = new GeneralLedger();
                    $newEntryCredit->vid = $newVid;
                    $newEntryCredit->account = $purchaseAccountId;
                    $newEntryCredit->type = 'Invoice Delete Product ' . \Auth::user()->invoiceNumberFormat($invoice->id);
                    $newEntryCredit->debit = $itemAmount_purchase;
                    $newEntryCredit->credit = 0;
                    $newEntryCredit->ref_id = $invoice->id;
                    $newEntryCredit->user_id = 0;
                    $newEntryCredit->created_by = \Auth::user()->creatorId();
                    $newEntryCredit->send_date = $invoice->issue_date;
                    $newEntryCredit->sub_product_id = $productService->id;
                    $newEntryCredit->reference = 'Invoice Delete Product';
                    $newEntryCredit->save();

                    // Create a new entry for the expense account (credit) - Reversed from send function
                    $newEntryDebit = new GeneralLedger();
                    $newEntryDebit->vid = $newVid;
                    $newEntryDebit->account = $expenseAccountId;
                    $newEntryDebit->type = 'Invoice Delete Product ' . \Auth::user()->invoiceNumberFormat($invoice->id);
                    $newEntryDebit->debit = 0;
                    $newEntryDebit->credit = $itemAmount_purchase;
                    $newEntryDebit->ref_id = $invoice->id;
                    $newEntryDebit->user_id = 0;
                    $newEntryDebit->created_by = \Auth::user()->creatorId();
                    $newEntryDebit->send_date = $invoice->issue_date;
                    $newEntryDebit->sub_product_id = $productService->id;
                    $newEntryDebit->reference = 'Invoice Delete Product';
                    $newEntryDebit->save();
                }
                $productService_ID =  $productService->product_id;
                $productService->booked = 0;
                $productService->invoice_id = null;
                $productService->quantity += 1;
                $productService->save();
                $invoiceProduct = InvoiceProduct::where('sub_product_id', $id)->first();
                $invoiceProduct->flag = 0;
                $invoiceProduct->delete();
                $productService->productService->quantity += 1;
                $productService->productService->save();
            } else {
                $delete_qty = request()->input('delete_qty');
                $invoiceProduct = InvoiceProduct::where('sub_product_id', $productService->id)->first();
                if ($invoiceProduct->quantity < $delete_qty) {
                    return redirect()->back()->with('error', __("Not enough quantity to return"));
                }
                $qtyIn = $invoiceProduct->quantity;
                $product = $productService->productService;
                
                // Get category to check if it's "Qty product" type
                $category = $product->category;
                
                // Calculate average cost first (for Qty product type) before using it
                $avgCost = null;
                if ($category && $category->type === "Qty product") {
                    // Check cost calculation method
                    $costCalculationMethod = $category->cost_calculation_method ?? 'avg';
                    
                    if ($costCalculationMethod === 'avg') {
                        // Calculate average cost using return formula:
                        // Average Cost = ((Old Purchased Qty from Sub Product × Last Avg) + (Return Qty × Avg from Stock Movement for this Item and this Invoice)) ÷ (Last Purchase Qty + Return Qty)
                        
                        // Get return quantity
                        $returnQty = $delete_qty;
                        
                        // Find the stock movement record for this invoice to get the avg_cost when it was sold
                        $originalStockMovement = StockMovement::where('invoice_id', $invoiceProduct->invoice_id)
                            ->where('sub_product_id', $productService->id)
                            ->where('product_id', $product->id)
                            ->where(function($query) {
                                $query->where('activity', 'Sale via Invoice')
                                      ->orWhere('activity', 'SALES');
                            })
                            ->where('qty_out', '>', 0)
                            ->first();
                        
                        // Get avg_cost from stock movement for this item and this invoice
                        $avgFromStockMovement = $originalStockMovement ? $originalStockMovement->avg_cost : ($product->avg_cost ?? 0);
                        
                        // Count purchased subproduct quantities (from sent bills) - old purchased qty from sub product
                        $purchasedBillIds = \App\Models\Bill::whereNotIn('status', [0, 1, 2])
                            ->where('created_by', \Auth::user()->creatorId())
                            ->pluck('id')
                            ->toArray();
                        
                        // Count total quantity from purchased subproducts (old purchased qty from sub product)
                        $oldPurchasedSubProductQty = SubProduct::where('product_id', $product->id)
                            ->whereIn('bill_id', $purchasedBillIds)
                            ->where('flag', '!=', 0)
                            ->whereNotNull('bill_id')
                            ->sum('quantity') ?? 0;
                        
                        // Get last avg from parent product
                        $lastAvg = ($product->avg_cost > 0) ? $product->avg_cost : ($productService->purchase_price ?? 0);
                        
                        // Calculate average cost using return formula
                        // Formula: ((old purchased qty from sub product * last avg) + (return qty * avg from stock movement for this item and this invoice)) / (last purchase qty + return qty)
                        $lastPurchaseQty = $oldPurchasedSubProductQty;
                        $totalQty = $lastPurchaseQty + $returnQty;
                        if ($totalQty > 0) {
                            $avgCost = ((($oldPurchasedSubProductQty * $lastAvg) + ($returnQty * $avgFromStockMovement)) / $totalQty);
                        } else {
                            $avgCost = $lastAvg;
                        }
                    } else {
                        // Use actual cost (purchase price from subproduct)
                        $avgCost = $productService->purchase_price ?? 0;
                    }
                }
                
                // Use calculated avgCost if available (for Qty product), otherwise use fallback
                $product_cost = ($avgCost !== null) ? $avgCost : (($product->avg_cost > 0) ? $product->avg_cost : $productService->purchase_price);
                
                $productService->productService->quantity += $delete_qty;
                $productService->productService->save();

                // Create stock movement and update avg_cost only for "Qty product" type
                if ($category && $category->type === "Qty product" && $avgCost !== null) {
                    // Create a new StockMovement for the stock return (sale return - qty_in)
                    $stockMovement = new StockMovement();
                    $stockMovement->product_id = $productService->product_id;
                    $stockMovement->sub_product_id = $productService->id;
                    $stockMovement->invoice_id = $invoiceProduct->invoice_id;
                    $stockMovement->bill_id = null;
                    $stockMovement->pos_id = null;
                    $stockMovement->qty_out = 0; // No stock out
                    $stockMovement->qty_in = $delete_qty; // Quantity returned
                    $stockMovement->avg_cost = $avgCost;
                    $stockMovement->cost_price = $originalStockMovement->cost_price ?? 0;
                    $stockMovement->activity = 'Return from Invoice';
                    $stockMovement->use_id = $invoice->customer_id; // customer_id for SALES
                    $stockMovement->item = $productService->id; // sub_product_id
                    $stockMovement->created_by = \Auth::user()->creatorId();
                    $stockMovement->save();
                    
                    // Update product average cost
                    $productService->productService->avg_cost = $avgCost;
                    $productService->productService->save();
                }

                // Use product_cost (which uses calculated avgCost if available) for itemAmount_purchase
                $itemAmount_purchase = $product_cost * $delete_qty;
                // Create a new entry for debit the category account
                $newEntryCategory = new GeneralLedger();
                $newEntryCategory->vid = $newVid;
                $newEntryCategory->account = $invoice->type == "regular" ? $categoryChartAccountId : $categoryChartAccountRentId;
                $newEntryCategory->type = 'Invoice Delete Product ' . \Auth::user()->invoiceNumberFormat($invoice->id);
                $newEntryCategory->debit = $productService->sale_price * $delete_qty; // Example value
                $newEntryCategory->credit = 0; // Example value
                $newEntryCategory->ref_id = $invoice->id;
                $newEntryCategory->user_id = 0;
                $newEntryCategory->created_by = \Auth::user()->creatorId();
                $newEntryCategory->created_at = $dateToDelete;
                $newEntryCategory->updated_at = $dateToDelete;
                $newEntryCategory->deleted_qty = $delete_qty;
                $newEntryCategory->sub_product_id = $productService->id;
                $newEntryCategory->reference = 'Invoice Delete Product';
                $newEntryCategory->save();



                // Retrieve the chart account ID for the tax
                $taxChartAccountId = \App\Models\Tax::where('id', $invoice->tax_id)->first()->chart_account_id;

                // Create a new entry debit for the tax account
                $newEntryTax = new GeneralLedger();
                $newEntryTax->vid = $newVid;
                $newEntryTax->account = $taxChartAccountId;
                $newEntryTax->type = 'Invoice Delete Product ' . \Auth::user()->invoiceNumberFormat($invoice->id);
                $newEntryTax->debit = $totalTaxPrice; // Example value
                $newEntryTax->credit = 0; // Example value
                $newEntryTax->ref_id = $invoice->id;
                $newEntryTax->user_id = 0;
                $newEntryTax->created_by = \Auth::user()->creatorId();
                $newEntryTax->created_at = $dateToDelete;
                $newEntryTax->updated_at = $dateToDelete;
                $newEntryTax->deleted_qty = $delete_qty;
                $newEntryTax->sub_product_id = $productService->id;
                $newEntryTax->reference = 'Invoice Delete Product';
                $newEntryTax->save();


                // Retrieve the chart account ID for the customer

                $customerChartAccountId = $customer->chart_account_id;

                // Create a new entry cedit for the customer account
                $newEntryCustomer = new GeneralLedger();
                $newEntryCustomer->vid = $newVid;
                $newEntryCustomer->account = $customerChartAccountId;
                $newEntryCustomer->type = 'Invoice Delete Product ' . \Auth::user()->invoiceNumberFormat($invoice->id);
                $newEntryCustomer->debit = 0; // Example value
                $newEntryCustomer->credit = $productService->sale_price * $delete_qty + $totalTaxPrice; // Example value
                $newEntryCustomer->ref_id = $invoice->id;
                $newEntryCustomer->user_id = $customer->id;
                $newEntryCustomer->created_by = \Auth::user()->creatorId();
                $newEntryCustomer->balance = $customer->balance;
                $newEntryCustomer->send_date = $dateToDelete;
                $newEntryCustomer->deleted_qty = $delete_qty;
                $newEntryCustomer->sub_product_id = $productService->id;
                $newEntryCustomer->reference = 'Invoice Delete Product';
                $newEntryCustomer->save();



                ///////////////////////////////////////
                // Add records if product type is 'product'
                if ($productService->productService->type == 'product' && $invoice->type == "regular") {
                    // Retrieve the chart account ID for the purchase
                    $purchaseAccountId = \App\Models\ProductServiceCategory::where('id', $productService->productService->category_id)->first()->purchase_account_id;

                    // Calculate the sum of direct expenses related to this item's sub_product_id
                    // Only include expenses where chart_account_id matches the purchase_account_id
                    $directExpenseAmount = 0;
                    if ($productService->id && $purchaseAccountId) {
                        $directExpenseAmount = \App\Models\DirectExpenseItem::where('sub_product_id', $productService->id)
                            ->where('chart_account_id', $purchaseAccountId)
                            ->whereHas('directExpense', function ($query) {
                                $query->where('created_by', \Auth::user()->creatorId());
                            })
                            ->sum('amount');
                    } else {
                        $directExpenseAmount = 0;
                    }

                    // Calculate the sum of sell_price from car_accessory_request_items related to this item
                    $carAccessoryAmount = 0;
                    if ($productService->id) {
                        $carAccessoryAmount = \App\Models\CarAccessoryRequestItem::where(function ($query) use ($productService) {
                            $query->where('car_id', $productService->id)
                                ->orWhere('accessory_id', $productService->id);
                        })
                            ->whereHas('request', function ($query) {
                                $query->where('created_by', \Auth::user()->creatorId());
                            })
                            ->sum('sell_price');
                    }

                    // Add direct expense amount and car accessory amount to the purchase amount
                    $itemAmount_purchase += $directExpenseAmount + $carAccessoryAmount;

                    // Retrieve the chart account ID for the expense
                    $expenseAccountId = \App\Models\ProductServiceCategory::where('id', $productService->productService->category_id)->first()->expense_account_id;

                    // Create a new entry for the purchase account (debit) - Reversed from send function
                    $newEntryCredit = new GeneralLedger();
                    $newEntryCredit->vid = $newVid;
                    $newEntryCredit->account = $purchaseAccountId;
                    $newEntryCredit->type = 'Invoice Delete Product ' . \Auth::user()->invoiceNumberFormat($invoice->id);
                    $newEntryCredit->debit = $itemAmount_purchase;
                    $newEntryCredit->credit = 0;
                    $newEntryCredit->ref_id = $invoice->id;
                    $newEntryCredit->user_id = 0;
                    $newEntryCredit->created_by = \Auth::user()->creatorId();
                    $newEntryCredit->send_date = $invoice->issue_date;
                    $newEntryCredit->deleted_qty = $delete_qty;
                    $newEntryCredit->sub_product_id = $productService->id;
                    $newEntryCredit->reference = 'Invoice Delete Product';
                    $newEntryCredit->save();

                    // Create a new entry for the expense account (credit) - Reversed from send function
                    $newEntryDebit = new GeneralLedger();
                    $newEntryDebit->vid = $newVid;
                    $newEntryDebit->account = $expenseAccountId;
                    $newEntryDebit->type = 'Invoice Delete Product ' . \Auth::user()->invoiceNumberFormat($invoice->id);
                    $newEntryDebit->debit = 0;
                    $newEntryDebit->credit = $itemAmount_purchase;
                    $newEntryDebit->ref_id = $invoice->id;
                    $newEntryDebit->user_id = 0;
                    $newEntryDebit->created_by = \Auth::user()->creatorId();
                    $newEntryDebit->send_date = $invoice->issue_date;
                    $newEntryDebit->deleted_qty = $delete_qty;
                    $newEntryDebit->sub_product_id = $productService->id;
                    $newEntryDebit->reference = 'Invoice Delete Product';
                    $newEntryDebit->save();
                }
                if ((int)$delete_qty ===  $invoiceProduct->quantity) {
                    $invoiceProduct->delete();
                    $productService->booked = 0;
                    $productService->invoice_id = null;
                    $productService->quantity += $delete_qty;
                    $productService->save();
                } else {
                    $invoiceProduct->quantity -= $delete_qty;
                    $invoiceProduct->save();
                    $productService->quantity += $delete_qty;
                    $productService->save();
                }
            }
            DB::commit();
            return redirect()->back()->with('success', __('Product successfully deleted.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
    public function destroyBill($id)
    {
        $productService = SubProduct::find($id);
        if (!$productService) {
            return redirect()->back()->with('error', __('Sub-product not found.'));
        }

        // Do not allow deleting if this sub-product has been sold in POS or Invoice
        if (!empty($productService->invoice_id) || !empty($productService->pos_id)) {
            return redirect()->back()->with('error', __('Cannot delete item: this item has already been sold (linked to an invoice or POS).'));
        }

        // Check if sub-product has direct expenses
        $hasDirectExpenses = DirectExpenseItem::where('sub_product_id', $id)
            ->whereHas('directExpense', function ($query) {
                $query->where('created_by', \Auth::user()->creatorId());
            })
            ->exists();
        
        if ($hasDirectExpenses) {
            return redirect()->back()->with('error', __('Cannot delete item: This item has direct expenses associated with it.'));
        }
        
        // Check if sub-product is linked to car manufacture (as car or accessory)
        $isLinkedToCarManufacture = CarAccessoryRequestItem::where(function ($query) use ($id) {
                $query->where('car_id', $id)
                    ->orWhere('accessory_id', $id);
            })
            ->whereHas('request', function ($query) {
                $query->where('created_by', \Auth::user()->creatorId());
            })
            ->exists();
        
        if ($isLinkedToCarManufacture) {
            return redirect()->back()->with('error', __('Cannot delete item: This item is linked to car manufacture.'));
        }
        
        // $delete_date =   request()->input('delete_date');
        if (empty(request()->input('delete_date'))) {
            $delete_date = now()->toDateString();
        } else {
            $delete_date = request()->input('delete_date');

            $productCreatedAt = strtotime($productService->created_at);

            // Convert the input date to a timestamp
            $inputDateTimestamp = strtotime($delete_date);
            if ($inputDateTimestamp < $productCreatedAt) {
                return redirect()->back()->with('error', __("Entered date is not greater than item's created date"));
            }
        }
        // Get the latest 'vid' entry, if any exist
        $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
        // Extract the vid value from the last record and increment it
        if ($latestVoucher) {
            $lastVid = $latestVoucher->vid;
            $newVoucherId = $lastVid + 1;
        } else {
            // If no record exists, start with 1
            $newVoucherId = 1;
        }
        $existingRecord = GeneralLedger::where('vid', $newVoucherId)->where('created_by', \Auth::user()->creatorId())->exists();

        if ($existingRecord) {
            return redirect()->back()->with('error', __("something went wrong , please try again."));
        }
        $productService_ID =  $productService->product_id;
        if ($productService->booked != 0) {
            return redirect()->back()->with('success', __('Delete denied.'));
        } else {
            if (empty(request()->input('delete_qty'))) {
                $billProduct = BillProduct::where('sub_product_id', $id)->first();
                // Get the quantity from billProduct before deleting
                $deleteQuantity = $billProduct->quantity ?? 1;
                
                // Delete bill product
                $billProduct->delete();
                
                // Reduce quantity from subproduct
                $productService->flag = 2;
                $productService->quantity -= $deleteQuantity;
                $productService->save();
                
                // Reduce quantity from parent product
                $productService->productService->quantity -= $deleteQuantity;
                $productService->productService->save();


                $product = ProductService::find($billProduct->product_id);
                $bill = Bill::find($billProduct->bill_id);
                $totalTaxPrice = 0;
                $totalAmountDebit = 0;
                if (!empty($bill->tax_id)) {
                    $taxes = \App\Models\Utility::tax($bill->tax_id);
                    foreach ($taxes as $tax) {
                        $taxPrice = (Tax::where('id', $tax->id)->first()->rate / 100) * $billProduct->price;
                        $totalTaxPrice += $taxPrice;
                    }
                }
                $totalAmountDebit = $billProduct->price - $billProduct->discount;

                // Add to General Ledger
                if ($bill->status != 0) {
                    $vender = Vender::where('id', $bill->vender_id)->first();
                    // Create a new entry for debit to Vendor account
                    $vendorAccountId = $vender->chart_account_id;
                    $purchaseEntry = new GeneralLedger();
                    $purchaseEntry->vid = $newVoucherId;
                    $purchaseEntry->account = $vendorAccountId;
                    $purchaseEntry->type = 'Delete Item From Bill  ' . \Auth::user()->billNumberFormat($bill->id);
                    $purchaseEntry->ref_number = 'Delete Item From Bill  ' . \Auth::user()->billNumberFormat($bill->bill_id);
                    $purchaseEntry->debit = $totalAmountDebit + $totalTaxPrice; // Example value
                    $purchaseEntry->credit = 0; // Example value
                    $purchaseEntry->ref_id = $bill->id;
                    $purchaseEntry->user_id = $vender->id;
                    $purchaseEntry->created_by = \Auth::user()->creatorId();
                    $purchaseEntry->balance = $vender->balance;
                    $purchaseEntry->send_date = $delete_date;
                    $purchaseEntry->sub_product_id = $productService->id;
                    $purchaseEntry->reference = 'Delete Item From Bill';
                    $purchaseEntry->save();


                    // Create a new entry for credit to Purchase account
                    // Get purchase account ID from ProductServiceCategory
                    $purchaseAccountId = ProductServiceCategory::where('id', $product->category_id)->first()->purchase_account_id;
                    $vendorEntry = new GeneralLedger();
                    $vendorEntry->vid = $newVoucherId;
                    $vendorEntry->account = $purchaseAccountId;
                    $vendorEntry->type = 'Delete Item From Bill ' . \Auth::user()->billNumberFormat($bill->id);
                    $vendorEntry->ref_number = 'Delete Item From Bill  ' . \Auth::user()->billNumberFormat($bill->bill_id);
                    $vendorEntry->debit = 0; // Example value
                    $vendorEntry->credit = $totalAmountDebit; // Example value
                    $vendorEntry->ref_id = $bill->id;
                    $vendorEntry->user_id = 0;
                    $vendorEntry->created_by = \Auth::user()->creatorId();
                    $vendorEntry->send_date = $delete_date;
                    $vendorEntry->sub_product_id = $productService->id;
                    $vendorEntry->reference = 'Delete Item From Bill';
                    $vendorEntry->save();

                    // Get tax account ID from Tax
                    $taxAccountId = Tax::where('id', $bill->tax_id)->first()->chart_account_id;

                    // Create a new entry for credit to Tax
                    $taxEntry = new GeneralLedger();
                    $taxEntry->vid = $newVoucherId;
                    $taxEntry->account = $taxAccountId;
                    $taxEntry->type = 'Delete Item From Bill ' . \Auth::user()->billNumberFormat($bill->id);
                    $taxEntry->debit = 0; // Example value
                    $taxEntry->credit = $totalTaxPrice; // Example value
                    $taxEntry->ref_id = $bill->id;
                    $taxEntry->user_id = 0;
                    $taxEntry->created_by = \Auth::user()->creatorId();
                    $taxEntry->send_date = $delete_date;
                    $taxEntry->sub_product_id = $productService->id;
                    $taxEntry->reference = 'Delete Item From Bill';
                    $taxEntry->save();
                }
            } else {
                $delete_qty = request()->input('delete_qty');
                if ($productService->quantity < $delete_qty) {
                    return redirect()->back()->with('error', __("Not enough quantity to return"));
                }
                $billProduct = BillProduct::where('sub_product_id', $id)->first();
                $qtyIn = $billProduct->quantity;
                if ($delete_qty ===  $billProduct->quantity) {
                    // Full deletion: delete bill product, mark subproduct as deleted, subtract from all
                    $productService->flag = 2;
                    $productService->quantity -= $delete_qty; // Subtract from subproduct
                    $productService->save();
                    $billProduct->flag = 0;
                    $billProduct->delete(); // Delete bill product
                    $productService->productService->quantity -= $delete_qty; // Subtract from parent product
                    $productService->productService->save();
                } else {
                    // Partial deletion: subtract from all three
                    $billProduct->quantity -= $delete_qty; // Subtract from bill product
                    $billProduct->save();
                    $productService->quantity -= $delete_qty; // Subtract from subproduct
                    $productService->productService->quantity -= $delete_qty; // Subtract from parent product
                    $productService->save();
                    $productService->productService->save();
                }
                // Calculate average cost using deletion formula (only for Qty product type)
                $product = $productService->productService;
                $category = $product ? $product->category : null;
                $bill = Bill::find($billProduct->bill_id);
                $avgCost = 0;
                
                if ($category && $category->type === "Qty product" && $bill && !in_array($bill->status, [0, 1, 2])) {
                    // Check cost calculation method
                    $costCalculationMethod = $category->cost_calculation_method ?? 'avg';
                    
                    if ($costCalculationMethod === 'avg') {
                        // Calculate average cost using deletion formula:
                        // Average Cost = ((Last Product Qty × Last Avg) - (Delete Qty × Delete Price)) ÷ (Last Qty - Delete Qty)
                        
                        // Get product's current quantity and average cost (before deletion)
                        $lastProductQty = $product->quantity ?? 0;
                        $lastAvg = ($product->avg_cost > 0) ? $product->avg_cost : $billProduct->price;
                        
                        // Deleted item (current bill product quantity being deleted)
                        $deleteQty = $delete_qty;
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
                    } else {
                        // Use actual cost (purchase price from bill product)
                        $avgCost = $billProduct->price;
                        if ($billProduct->discount > 0) {
                            $avgCost = ($billProduct->price * $billProduct->quantity - $billProduct->discount) / $billProduct->quantity;
                        }
                    }
                    
                    // Update product average cost
                    $product->avg_cost = $avgCost;
                    $product->save();
                    
                    // Create a new StockMovement for the stock return (purchase return)
                    $stockMovement = new StockMovement();
                    $stockMovement->product_id = $productService->product_id;
                    $stockMovement->sub_product_id = $productService->id;
                    $stockMovement->invoice_id = null;
                    $stockMovement->bill_id = $billProduct->bill_id;
                    $stockMovement->qty_out = $delete_qty; // Quantity returned out
                    $stockMovement->qty_in = 0; // Quantity returned in
                    $stockMovement->avg_cost = $avgCost;
                    $stockMovement->cost_price = $billProduct->price;
                    $stockMovement->activity = 'Return from Bill';
                    $stockMovement->use_id = $bill ? $bill->vender_id : null; // vender_id for PURCHASE
                    $stockMovement->item = $productService->id; // sub_product_id
                    $stockMovement->created_by = \Auth::user()->creatorId();
                    $stockMovement->save();
                }
                // $stock_product = SubProduct::find($productService->id);
                // $stock_product->purchase_price = $avgCost;
                // $stock_product->save();


                $product = ProductService::find($billProduct->product_id);
                $bill = Bill::find($billProduct->bill_id);
                $totalTaxPrice = 0;
                $totalAmountDebit = 0;
                if (!empty($bill->tax_id)) {
                    $taxes = \App\Models\Utility::tax($bill->tax_id);
                    foreach ($taxes as $tax) {
                        $taxPrice = (Tax::where('id', $tax->id)->first()->rate / 100) * ($billProduct->price * $delete_qty);
                        $totalTaxPrice += $taxPrice;
                    }
                }
                $totalAmountDebit = $billProduct->price * $delete_qty - $billProduct->discount;

                // Add to General Ledger
                if ($bill->status != 0) {
                    $vender = Vender::where('id', $bill->vender_id)->first();
                    // Create a new entry for debit to Vendor account
                    $vendorAccountId = $vender->chart_account_id;
                    $purchaseEntry = new GeneralLedger();
                    $purchaseEntry->vid = $newVoucherId;
                    $purchaseEntry->account = $vendorAccountId;
                    $purchaseEntry->type = 'Delete Item From Bill  ' . $bill->bill_id;
                    $purchaseEntry->debit = $totalAmountDebit + $totalTaxPrice; // Example value
                    $purchaseEntry->credit = 0; // Example value
                    $purchaseEntry->ref_id =  $bill->id;
                    $purchaseEntry->user_id = $vender->id;
                    $purchaseEntry->created_by = \Auth::user()->creatorId();
                    $purchaseEntry->balance = $vender->balance;
                    $purchaseEntry->send_date = $delete_date;
                    $purchaseEntry->deleted_qty = $delete_qty;
                    $purchaseEntry->sub_product_id = $productService->id;
                    $purchaseEntry->reference = 'Delete Item From Bill';
                    $purchaseEntry->save();


                    // Create a new entry for credit to Purchase account
                    // Get purchase account ID from ProductServiceCategory
                    $purchaseAccountId = ProductServiceCategory::where('id', $product->category_id)->first()->purchase_account_id;
                    $vendorEntry = new GeneralLedger();
                    $vendorEntry->vid = $newVoucherId;
                    $vendorEntry->account = $purchaseAccountId;
                    $vendorEntry->type = 'Delete Item From Bill ' . $bill->bill_id;
                    $vendorEntry->debit = 0; // Example value
                    $vendorEntry->credit = $totalAmountDebit; // Example value
                    $vendorEntry->ref_id =  $bill->id;
                    $vendorEntry->user_id = 0;
                    $vendorEntry->created_by = \Auth::user()->creatorId();
                    $vendorEntry->send_date = $delete_date;
                    $vendorEntry->deleted_qty = $delete_qty;
                    $vendorEntry->sub_product_id = $productService->id;
                    $vendorEntry->reference = 'Delete Item From Bill';
                    $vendorEntry->save();

                    // Get tax account ID from Tax
                    $taxAccountId = Tax::where('id', $bill->tax_id)->first()->chart_account_id;

                    // Create a new entry for credit to Tax
                    $taxEntry = new GeneralLedger();
                    $taxEntry->vid = $newVoucherId;
                    $taxEntry->account = $taxAccountId;
                    $taxEntry->type = 'Delete Item From Bill ' . $bill->bill_id;
                    $taxEntry->debit = 0; // Example value
                    $taxEntry->credit = $totalTaxPrice; // Example value
                    $taxEntry->ref_id = $bill->id;
                    $taxEntry->user_id = 0;
                    $taxEntry->created_by = \Auth::user()->creatorId();
                    $taxEntry->send_date = $delete_date;
                    $taxEntry->deleted_qty = $delete_qty;
                    $taxEntry->sub_product_id = $productService->id;
                    $taxEntry->reference = 'Delete Item From Bill';
                    $taxEntry->save();
                }
            }
            return redirect()->back()->with('success', __('Product successfully deleted.'));
        }
    }

    function GetFreeProduct($id)
    {
        $maxQuantity = SubProduct::where('product_id', $id)->where('flag', '!=', SubProduct::FLAG_CONSIGNMENT)->where('booked', 0)->count();
        return response()->json($maxQuantity);
    }
    function GetFreeProduct_in_wareHouse($W_id,$id)
    {
        $maxQuantity = warehouse::find($W_id)->GetFreeQuantity($id);
        return response()->json($maxQuantity);
    }

    function subProductsedit($id)
    {
        $subProducts = SubProduct::where('product_id', '=', $id)->orderBy('created_at', 'desc')->paginate(10);
        $product_id = $id;
        $product_cat = ProductService::where('id', $id)->first()->category_id;
        $product_services = ProductService::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'sub-product')->forCategory($product_cat)->get();
        $colors     = Color::get()->pluck('name', 'id');
        // Fetch the custom field values for each sub-product
        $customFieldValues = [];
        foreach ($subProducts as $subProduct) {
            $customFieldValues[$subProduct->id] = CustomFieldValue::where('record_id', $subProduct->id)
                ->whereIn('field_id', $customFields->pluck('id'))
                ->get()
                ->keyBy('field_id')
                ->map(function ($item) {
                    return $item->value;
                });
        }
        return view('subproducts.updateSubProduct', compact('subProducts', 'product_id', 'product_services', 'customFields', 'customFieldValues'));
    }

    function sub_product_update(Request $request, $id)
    {
        foreach ($request->items as $index => $item) {
            $productService = SubProduct::where('id', $item['sub_product_id'])->first();
            $productService->product_no = $item['product_no'];
            // $productService->engine_no = $item['engine_no'];
            $productService->sale_price = $item['sale_price'];
            $productService->purchase_price = $item['purchase_price'];
            $productService->quantity = $item['quantity'];
            // $productService->interior_color_id = $item['interior_color_id'];
            $productService->created_by = \Auth::user()->creatorId();
            $productService->save();
            // if (isset($item['customField'])) {
            //     CustomField::saveData($productService, $item['customField']);
            // }
            // Handle custom field values
            foreach ($item['customField'] as $customFieldId => $value) {
                // Check if a custom field value already exists for this sub-product
                $customFieldValue = CustomFieldValue::where('record_id', $productService->id)
                    ->where('field_id', $customFieldId)
                    ->first();

                if ($customFieldValue) {
                    // Update existing value
                    $customFieldValue->value = $value;
                    $customFieldValue->save();
                } else {
                    // Create new value if it doesn't exist
                    CustomFieldValue::create([
                        'record_id' => $productService->id,
                        'field_id' => $customFieldId,
                        'value' => $value,
                    ]);
                }
            }
        }

        return redirect()->route('subProducts', ['id' => $id])->with('success', __('Sub Products successfully updated.'));
    }


    public function getItemCategory($productId)
    {
        $item = ProductService::find($productId);

        if ($item) {
            return response()->json([
                'status' => 'success',
                'category_type' => $item->category->type, // Adjust this field to match your model
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Item not found',
            ], 404);
        }
    }


    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        $file = $request->file('file');

        return $this->importSubProductsFromExcel($file);
    }

    function importSubProductsFromExcel($file)
    {
        // $path = $file->getRealPath();
        $data = Excel::toArray([], $file)[0];
        $headers = $data[0]; // First row = headers
        $rows = array_slice($data, 1); // Rest = data rows
        $totalInventory = 0;
        $creatorId = \Auth::user()->creatorId();
        $today = now()->toDateString();
        // Get next voucher ID
        $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
        $newVoucherId = $latestVoucher ? $latestVoucher->vid + 1 : 1;
        $product = null;
        $productCategoryId = null;
        $productType = null;

        foreach ($rows as $row) {
            $subProductBaseData = [];
            $customFieldValues = [];
            $productCategoryId = null; // Reset for each row
            $productType = null; // Reset for each row
            $product = null;
            $parentSku = null;

            foreach ($headers as $index => $header) {
                $value = $row[$index] ?? '';

                switch (strtolower(trim($header))) {
                    case 'product_sku':
                    case 'product sku':
                    case 'parent_sku':
                    case 'parent sku':
                    case 'parent_product_sku':
                    case 'parent product sku':
                        $parentSku = trim((string) $value);
                        break;
                    case 'product_no':
                        $subProductBaseData['product_no'] = $value;
                        break;
                    // case 'quantity':
                    //     $subProductBaseData['quantity'] = $value;
                    //     break;
                    case 'sale_price':
                        $subProductBaseData['sale_price'] = $value;
                        break;
                    case 'purchase_price':
                        $subProductBaseData['purchase_price'] = $value;
                        break;
                    case 'initial_stock':
                        $subProductBaseData['initial_stock'] = $value;
                        // $totalQty += $value;
                        break;
                    case 'initial_rate':
                        $subProductBaseData['initial_rate'] = $value;
                        // $totalInventory += $value;
                        break;
                    case 'product_id':
                    case 'product id':
                        $product = ProductService::where('id', $value)->where('created_by', $creatorId)->first();
                        if ($product) {
                            $subProductBaseData['product_id'] = $product->id;
                            $productType = optional($product->category)->type;
                            $productCategoryId = $product->category_id ?? null;
                        }
                        break;
                    case 'warehouse_id':
                        // Legacy: numeric warehouse id (must belong to this company)
                        if ($value !== '' && $value !== null && is_numeric($value)) {
                            $warehouse = \App\Models\warehouse::where('id', (int) $value)
                                ->where('created_by', $creatorId)
                                ->first();
                            if ($warehouse) {
                                $subProductBaseData['warehouse_id'] = $warehouse->id;
                            }
                        }
                        break;
                    case 'warehouse_name':
                    case 'warehouse name':
                    case 'warehouse':
                        $warehouseLabel = trim((string) $value);
                        if ($warehouseLabel !== '') {
                            $warehouse = \App\Models\warehouse::where('created_by', $creatorId)
                                ->whereRaw('LOWER(TRIM(name)) = ?', [Str::lower($warehouseLabel)])
                                ->first();
                            if ($warehouse) {
                                $subProductBaseData['warehouse_id'] = $warehouse->id;
                            }
                        }
                        break;
                    default:
                        // Try to find custom field by name, considering category if available
                        $customField = null;
                        if ($productCategoryId) {
                            // First try with category_id
                            $customField = CustomField::where('created_by', $creatorId)
                                ->where('module', 'sub-product')
                                ->forCategory($productCategoryId)
                                ->where(function($query) use ($header) {
                                    $query->where('name', trim($header))
                                          ->orWhereRaw('LOWER(name) = ?', [strtolower(trim($header))]);
                                })
                                ->first();
                        }
                        
                        // If not found with category, try without category
                        if (!$customField) {
                            $customField = CustomField::where('created_by', $creatorId)
                                ->where('module', 'sub-product')
                                ->where(function($query) use ($header) {
                                    $query->where('name', trim($header))
                                          ->orWhereRaw('LOWER(name) = ?', [strtolower(trim($header))]);
                                })
                                ->first();
                        }

                        if ($customField && !empty($value) && trim($value) !== '') {
                            $customFieldValues[$customField->id] = trim($value);
                        }
                        break;
                }

            }

            if ($parentSku !== null && $parentSku !== '') {
                $foundBySku = ProductService::where('sku', $parentSku)->where('created_by', $creatorId)->first();
                if ($foundBySku) {
                    $subProductBaseData['product_id'] = $foundBySku->id;
                    $product = $foundBySku;
                    $productType = optional($foundBySku->category)->type;
                    $productCategoryId = $foundBySku->category_id ?? null;
                }
            }

            // Skip row if product_id is missing
            if (!isset($subProductBaseData['product_id'])) {
                continue;
            }

            $subProductBaseData['created_by'] = \Auth::user()->creatorId();
            $qty = (int) ($subProductBaseData['initial_stock'] ?? 0);
            $initialRate = (float) ($subProductBaseData['initial_rate'] ?? 0);
            
            // Only add to total inventory if qty > 0
            if ($qty > 0) {
                $totalInventory += $qty * $initialRate;
            }
            
            // Determine flag based on initial stock: 0 if initial_stock is 0, otherwise 1
            $flag = ($qty > 0) ? 1 : 0;
            
            // Check product type logic
            if (strtolower($productType) === 'product') {
                // Create multiple sub-products, each with quantity = 1
                for ($i = 0; $i < $qty; $i++) {
                    $data = $subProductBaseData;
                    $data['initial_stock'] = 1;
                    $subProduct = new SubProduct();
                    $subProduct->chassis_no = $subProductBaseData['product_no'] ?? null;
                    $subProduct->product_id = $subProductBaseData['product_id'];
                    $subProduct->quantity = $subProductBaseData['initial_stock'];
                    $subProduct->initial_stock = $subProductBaseData['initial_stock'];
                    $subProduct->initial_rate = $subProductBaseData['initial_rate'];
                    $subProduct->sale_price = $subProductBaseData['sale_price'];
                    $subProduct->purchase_price = $subProductBaseData['purchase_price'];
                    $subProduct->warehouse_id = $subProductBaseData['warehouse_id'] ?? null;
                    $subProduct->created_by = $creatorId;
                    $subProduct->flag = $flag;
                    $subProduct->booked = 0;
                    $subProduct->save();

                    // Save custom field values
                    foreach ($customFieldValues as $fieldId => $value) {
                        if (!empty($value)) {
                            CustomFieldValue::updateOrCreate(
                                [
                                    'field_id' => $fieldId,
                                    'record_id' => $subProduct->id,
                                ],
                                [
                                    'value' => (string)$value,
                                ]
                            );
                        }
                    }

                    // Create stock movement for opening balance
                    if ($subProduct->initial_stock > 0 && $subProduct->initial_rate > 0) {
                        StockMovement::create([
                            'product_id' => $subProduct->product_id,
                            'sub_product_id' => $subProduct->id,
                            'bill_id' => null,
                            'invoice_id' => null,
                            'pos_id' => null,
                            'qty_in' => $subProduct->initial_stock,
                            'qty_out' => 0,
                            'avg_cost' => $subProduct->initial_rate,
                            'cost_price' => $subProduct->initial_rate,
                            'activity' => 'Opening Balance',
                            'use_id' => null,
                            'item' => $subProduct->id,
                            'created_by' => $creatorId,
                        ]);
                    }
                }
            } else {
                // Qty product — create a single sub-product with full qty
                $subProduct = new SubProduct();
                $subProduct->chassis_no = $subProductBaseData['product_no'] ?? null;
                $subProduct->product_id = $subProductBaseData['product_id'];
                $subProduct->quantity = $subProductBaseData['initial_stock'];
                $subProduct->initial_stock = $subProductBaseData['initial_stock'];
                $subProduct->initial_rate = $subProductBaseData['initial_rate'];
                $subProduct->sale_price = $subProductBaseData['sale_price'];
                $subProduct->purchase_price = $subProductBaseData['purchase_price'];
                $subProduct->warehouse_id = $subProductBaseData['warehouse_id'] ?? null;
                $subProduct->created_by = $creatorId;
                $subProduct->flag = $flag;
                $subProduct->booked = 0;
                $subProduct->save();

                // Save custom field values
                foreach ($customFieldValues as $fieldId => $value) {
                    if (!empty($value)) {
                        CustomFieldValue::updateOrCreate(
                            [
                                'field_id' => $fieldId,
                                'record_id' => $subProduct->id,
                            ],
                            [
                                'value' => (string)$value,
                            ]
                        );
                    }
                }

                // Create stock movement for opening balance
                if ($subProduct->initial_stock > 0 && $subProduct->initial_rate > 0) {
                    StockMovement::create([
                        'product_id' => $subProduct->product_id,
                        'sub_product_id' => $subProduct->id,
                        'bill_id' => null,
                        'invoice_id' => null,
                        'pos_id' => null,
                        'qty_in' => $subProduct->initial_stock,
                        'qty_out' => 0,
                        'avg_cost' => $subProduct->initial_rate,
                        'cost_price' => $subProduct->initial_rate,
                        'activity' => 'Opening Balance',
                        'use_id' => null,
                        'item' => $subProduct->id,
                        'created_by' => $creatorId,
                    ]);
                }
            }

            

            // Determine inventory account
            $inventoryAccount = $product->category && $product->category->purchase_account_id
                ? ChartOfAccount::where('created_by', $creatorId)
                    ->where('id', $product->category->purchase_account_id)
                    ->first()
                : ChartOfAccount::where('created_by', $creatorId)
                    ->where('name', 'inventory')
                    ->first();

            // Only create/update ledger entry if there's actual inventory (qty > 0) and account exists
            if ($qty > 0 && $inventoryAccount) {
                // Check for existing inventory ledger entry
                $existingInventory = GeneralLedger::where('account', $inventoryAccount->id)
                    ->where('reference', 'opening balance')
                    // ->whereNull('user_id')
                    ->first();
                $existingVid = GeneralLedger::where('reference', 'opening balance')
                    ->where('created_by', \Auth::user()->creatorId())
                    ->first();
                $ledgerSendDate = $existingVid && !empty($existingVid->send_date) ? $existingVid->send_date : $today;
                $inventoryAmount = $qty * $initialRate;

                if ($existingInventory) {
                    $existingInventory->update([
                        'debit' => $existingInventory->debit + $inventoryAmount,
                        'credit' => 0
                    ]);
                } else {
                    GeneralLedger::create([
                        'vid' => $existingVid ? $existingVid->vid : $newVoucherId,
                        'account' => $inventoryAccount->id,
                        'type' => 'opening balance',
                        'debit' => $inventoryAmount,
                        'credit' => 0,
                        'ref_id' => $inventoryAccount->id,
                        'user_id' => 0,
                        'created_by' => $creatorId,
                        'send_date' => $ledgerSendDate,
                        'reference' => 'opening balance',
                        'ref_number' => $inventoryAccount->name,
                    ]);
                }
            }

            // Get adjustment account
            // $adjustmentAccount = ChartOfAccount::where('created_by', $creatorId)
            //     ->where('name', 'Opening Balances and adjustments')
            //     ->first();

            // Check for existing adjustment entry
            // $existingAdjustment = GeneralLedger::where('account', $adjustmentAccount->id)
            //     ->where('reference', 'opening balance')
            //     // ->whereNull('user_id')
            //     ->first();

            // if ($existingAdjustment) {
            //     $existingAdjustment->update([
            //         'debit' => 0,
            //         'credit' => $existingAdjustment->credit + $inventoryAmount,
            //         'send_date' => $today,
            //     ]);
            // } else {
            //     GeneralLedger::create([
            //         'vid' => $newVoucherId,
            //         'account' => $adjustmentAccount->id,
            //         'type' => 'opening balance',
            //         'debit' => 0,
            //         'credit' => $inventoryAmount,
            //         'ref_id' => $adjustmentAccount->id,
            //         'user_id' => 0,
            //         'created_by' => $creatorId,
            //         'send_date' => $today,
            //         'reference' => 'opening balance',
            //         'ref_number' => $adjustmentAccount->name,
            //     ]);
            // }
        }



        return back()->with('success', 'SubProducts imported successfully!');
    }


    public function importFile()
    {
        return view('subproducts.import');
    }


    public function expenses($id)
    {
        $subProduct = SubProduct::with(['productService.category', 'productService.brand', 'productService.subBrand'])
        ->find($id);
        $journalEntries = JournalItem::where('sub_product_id', '=', $id)->get();
        $directExpenses = \App\Models\DirectExpense::whereHas('items', function($query) use ($id) {
                $query->where('sub_product_id', $id);
            })
            ->where('created_by', \Auth::user()->creatorId())
            ->with(['items.chartAccount', 'vendor', 'currency'])
            ->orderByDesc('id')
            ->get();
        
        // Check if the product category is manufacturer
        $isManufacturer = $subProduct->productService->category->is_manufacturer ?? false;
        
        if ($isManufacturer) {
            // If it's a manufacturer product, get the cars that this subproduct is assigned to
            $linkedAccessories = CarAccessoryRequestItem::with(['request', 'car.productService.category', 'car.productService.brand', 'car.productService.subBrand'])
                ->where('accessory_id', $id) // This subproduct is the accessory assigned to cars
                ->whereNotNull('car_id')
                ->get()
                ->groupBy('request_id'); // Group by request for better organization
        } else {
            // If it's not a manufacturer product, get all accessories linked to this car
            $linkedAccessories = CarAccessoryRequestItem::with(['request', 'product'])
                ->where('car_id', $id)
                ->whereNotNull('product_id') // Only items with actual product_id (not just accessory_id)
                ->get()
                ->groupBy('request_id'); // Group by request for better organization
        }
        
        return view('subproducts.expenses', compact('subProduct', 'journalEntries', 'linkedAccessories', 'directExpenses'));
    }

    /**
     * Display the stock report for all subproducts with filters and custom fields.
     */
    public function stockReport(Request $request)
    {
        $user = \Auth::user();
        $canFullStock = $user->can('manage product & service');
        // Allow POS users (e.g. Pos Seller) who have POS permissions or explicit stock report permission
        $canPosStock = $user->can('manage pos') || $user->can('add pos') || $user->can('stock report');
        if (!$canFullStock && !$canPosStock) {
            return abort(403);
        }

        // Filters
        $categories = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->pluck('name', 'id');
        $products = ProductService::where('created_by', \Auth::user()->creatorId())->pluck('name', 'id');
        
        // Get brands from products (distinct brand_ids from ProductService table)
        $brandIds = ProductService::where('created_by', \Auth::user()->creatorId())
            ->whereNotNull('brand_id')
            ->distinct()
            ->pluck('brand_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
        $brands = !empty($brandIds) 
            ? Brand::whereIn('id', $brandIds)->orderBy('name', 'asc')->pluck('name', 'id')
            : collect();
        
        // Get sub-brands from products (distinct sub_brand_ids from ProductService table)
        $subBrandIds = ProductService::where('created_by', \Auth::user()->creatorId())
            ->whereNotNull('sub_brand_id')
            ->distinct()
            ->pluck('sub_brand_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
        $subBrands = !empty($subBrandIds) 
            ? VehicleModel::whereIn('id', $subBrandIds)->orderBy('name', 'asc')->pluck('name', 'id')
            : collect();
        $bills = Bill::where('created_by', \Auth::user()->creatorId())->where('type','bill')->get();
        $invoices = Invoice::where('created_by', \Auth::user()->creatorId())->get();
        $asns = Asn::where('created_by', \Auth::user()->creatorId())->orderByDesc('id')->get(['id', 'asn_no']);
        $warehouses = \DB::table('warehouses')->where('created_by', \Auth::user()->creatorId())->orderBy('name')->get(['id','name']);
        $customers = \DB::table('customers')->where('created_by', \Auth::user()->creatorId())->orderBy('name')->get(['id','name']);
        $vendors = \DB::table('venders')->where('created_by', \Auth::user()->creatorId())->orderBy('name')->get(['id','name']);

        $query = SubProduct::query();
        $query->where('created_by', \Auth::user()->creatorId());
        $query->where(function ($q) {
            $q->whereNull('import_source')->orWhere('import_source', '!=', 'item_master');
        });

        // By default, hide zero-quantity items unless user explicitly asks to show them
        if (!$request->boolean('show_zero_qty')) {
            $query->where('quantity', '>', 0);
        }
        // Show all stock including zero qty; exclude sub-products that came from "Import Item Master" (Spare Parts)
        $query->where(function ($q) {
            $q->whereNull('import_source')->orWhere('import_source', '!=', 'item_master');
        });

        // Exclude cancelled sub-products (purchase status)
        $query->where('flag', '!=', SubProduct::FLAG_CANCELLED);

        // Global search similar to car manufacturers
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function($subQ) use ($q) {
                $subQ->where('chassis_no', 'like', "%{$q}%")
                     ->orWhere('quantity', 'like', "%{$q}%")
                     ->orWhereHas('productService', function($psQ) use ($q) {
                         $psQ->where('name', 'like', "%{$q}%")
                             ->orWhere('sku', 'like', "%{$q}%");
                     });
            });
        }

        if ($request->filled('category_id')) {
            $productIds = \App\Models\ProductService::where('category_id', $request->category_id)->pluck('id');
            $query->whereIn('product_id', $productIds);
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('brand_id')) {
            $productIds = ProductService::where('brand_id', $request->brand_id)->pluck('id');
            $query->whereIn('product_id', $productIds);
        }
        if ($request->filled('sub_brand_id')) {
            $productIds = ProductService::where('sub_brand_id', $request->sub_brand_id)->pluck('id');
            $query->whereIn('product_id', $productIds);
        }
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('bill_id')) {
            $query->where('bill_id', $request->bill_id);
        }
        if ($request->filled('invoice_id')) {
            $query->where('invoice_id', $request->invoice_id);
        }
        if ($request->filled('asn_id')) {
            $query->where('asn_id', $request->asn_id);
        }
        // Chassis numbers (vins field): paste from Excel (one per line, or comma/tab separated)
        if ($request->filled('vins')) {
            $vinsRaw = trim((string) $request->vins);
            $vins = array_values(array_filter(array_map('trim', preg_split('/[\r\n\t,]+/', $vinsRaw, -1, PREG_SPLIT_NO_EMPTY)), function ($v) {
                return $v !== '';
            }));
            if (!empty($vins)) {
                $query->whereIn('chassis_no', $vins);
            }
        }
        if ($request->filled('customer_id')) {
            $query->whereIn('id', function($sub) use ($request) {
                $sub->select('sp.id')
                    ->from('sub_products as sp')
                    ->join('invoice_products as ip2', 'ip2.sub_product_id', '=', 'sp.id')
                    ->join('invoices as inv2', 'inv2.id', '=', 'ip2.invoice_id')
                    ->where('inv2.customer_id', $request->customer_id);
            });
        }
        if ($request->filled('vender_id')) {
            $query->whereIn('id', function($sub) use ($request) {
                $sub->select('sp.id')
                    ->from('sub_products as sp')
                    ->join('bill_products as bp2', 'bp2.sub_product_id', '=', 'sp.id')
                    ->join('bills as b2', 'b2.id', '=', 'bp2.bill_id')
                    ->where('b2.vender_id', $request->vender_id);
            });
        }
        
        // Filter by Purchase Status
        if ($request->filled('purchase_status')) {
            $query->where('flag', $request->purchase_status);
        }
        
        // Filter by Book Status
        if ($request->filled('book_status')) {
            $bookStatus = $request->book_status;
            $query->where(function($q) use ($bookStatus) {
                switch ($bookStatus) {
                    case 'free':
                        $q->where('booked', 0);
                        break;
                    case 'booked':
                        $q->where('booked', 1)
                          ->whereNotNull('invoice_id')
                          ->whereHas('invoice', function($invQ) {
                              $invQ->where('type', 'regular');
                          });
                        break;
                    case 'rented':
                        $q->where(function($rentQ) {
                            $rentQ->where(function($r1) {
                                $r1->where('booked', 1)
                                   ->whereNotNull('invoice_id')
                                   ->whereHas('invoice', function($invQ) {
                                       $invQ->where('type', 'rent');
                                   });
                            })->orWhere(function($r2) {
                                $r2->where('booked', 2)
                                   ->whereNotNull('invoice_id')
                                   ->whereHas('invoice', function($invQ) {
                                       $invQ->where('type', 'rent');
                                   });
                            });
                        });
                        break;
                    case 'sold':
                        $q->where(function($soldQ) {
                            $soldQ->where(function($s1) {
                                $s1->where('booked', 2)
                                   ->whereNull('invoice_id');
                            })->orWhere(function($s2) {
                                $s2->where('booked', 2)
                                   ->whereNotNull('invoice_id')
                                   ->whereHas('invoice', function($invQ) {
                                       $invQ->where('type', 'regular');
                                   });
                            })->orWhere(function($s3) {
                                $s3->where('booked', 1)
                                   ->whereNotNull('pos_id');
                            });
                        });
                        break;
                    case 'delivered':
                        // Delivered is the else case - anything that doesn't match Free, Booked, Rented, or Sold
                        $q->where(function($delQ) {
                            // booked == 1 but not rented, not booked (regular invoice), and not sold (pos_id)
                            $delQ->where(function($d1) {
                                $d1->where('booked', 1)
                                   ->where(function($d1sub) {
                                       $d1sub->whereNull('invoice_id')
                                             ->orWhereDoesntHave('invoice', function($invQ) {
                                                 $invQ->whereIn('type', ['rent', 'regular']);
                                             });
                                   })
                                   ->whereNull('pos_id');
                            })
                            // booked == 2 but not sold and not rented
                            ->orWhere(function($d2) {
                                $d2->where('booked', 2)
                                   ->whereNotNull('invoice_id')
                                   ->whereHas('invoice', function($invQ) {
                                       $invQ->where('type', '!=', 'rent')
                                            ->where('type', '!=', 'regular');
                                   });
                            });
                        });
                        break;
                }
            });
        }
        // Add more filters as needed

        // Calculate total quantity before pagination (for all filtered results)
        $totalStockQuantity = (clone $query)->sum('quantity');

        // Eager load relationships to avoid N+1 queries
        $query->with(['productService.brand', 'productService.subBrand', 'productService.category', 'invoice', 'pos', 'asn', 'images']);

        $query->orderBy('created_at', 'desc');
        $subProducts = $query->paginate(20);
        
        // Load car accessory request information for items that are accessories
        $subProductIds = $subProducts->pluck('id')->toArray();
        $carAccessoryRequests = [];
        
        if (!empty($subProductIds)) {
            // Query for items where accessory_id matches subproduct id AND request status is 'assigned'
            $requestItems = \App\Models\CarAccessoryRequestItem::whereIn('accessory_id', $subProductIds)
                ->whereNotNull('accessory_id') // Ensure accessory_id is not null
                ->with(['request'])
                ->whereHas('request', function($q) {
                    $q->where('status', 'assigned');
                })
                ->get();
            
            foreach ($requestItems as $item) {
                if ($item->request && $item->accessory_id && in_array($item->accessory_id, $subProductIds)) {
                    $accessoryId = $item->accessory_id;
                    if (!isset($carAccessoryRequests[$accessoryId])) {
                        $carAccessoryRequests[$accessoryId] = [];
                    }
                    // Add request if not already added (avoid duplicates)
                    $exists = false;
                    foreach ($carAccessoryRequests[$accessoryId] as $existing) {
                        if ($existing['id'] == $item->request->id) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $carAccessoryRequests[$accessoryId][] = [
                            'id' => $item->request->id,
                            'request_no' => $item->request->request_no
                        ];
                    }
                }
            }
        }

        // Get custom fields for sub-products (filtered by category if set)
        if ($request->filled('category_id')) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())
                ->where('module', '=', 'sub-product')
                ->forCategory($request->category_id)
                ->get();
        } else {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())
                ->where('module', '=', 'sub-product')
                ->get();
        }
        $customFieldValues = [];
        foreach ($subProducts as $subProduct) {
            $customFieldValues[$subProduct->id] = CustomFieldValue::where('record_id', $subProduct->id)
                ->whereIn('field_id', $customFields->pluck('id'))
                ->get()
                ->keyBy('field_id')
                ->map(function ($item) {
                    return $item->value;
                });
        }

        // Check if user is company/admin to show purchase price/avg cost
        $isCompany = (\Auth::user()->type == 'company' || \Auth::user()->type == 'super admin');

        return view('subproducts.stock_report', compact('subProducts', 'categories', 'products', 'brands', 'subBrands', 'bills', 'invoices', 'asns', 'warehouses', 'customers', 'vendors', 'customFields', 'customFieldValues', 'carAccessoryRequests', 'totalStockQuantity', 'isCompany'));
    }

    /**
     * Stock report for POS users (simplified - no purchase price, avg cost, bill/invoice filters)
     */
    public function posStockReport(Request $request)
    {
        // Allow access for users with POS permissions
        if (!\Auth::user()->can('manage pos') && !\Auth::user()->can('add pos') && \Auth::user()->type != 'company' && \Auth::user()->type != 'super admin') {
            return abort(403);
        }

        // Filters (simplified for users)
        $categories = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->pluck('name', 'id');
        $products = ProductService::where('created_by', \Auth::user()->creatorId())->pluck('name', 'id');
        
        // Get brands from products
        $brandIds = ProductService::where('created_by', \Auth::user()->creatorId())
            ->whereNotNull('brand_id')
            ->distinct()
            ->pluck('brand_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
        $brands = !empty($brandIds) 
            ? Brand::whereIn('id', $brandIds)->orderBy('name', 'asc')->pluck('name', 'id')
            : collect();
        
        // Get sub-brands from products
        $subBrandIds = ProductService::where('created_by', \Auth::user()->creatorId())
            ->whereNotNull('sub_brand_id')
            ->distinct()
            ->pluck('sub_brand_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
        $subBrands = !empty($subBrandIds) 
            ? VehicleModel::whereIn('id', $subBrandIds)->orderBy('name', 'asc')->pluck('name', 'id')
            : collect();
        
        // Don't load bills/invoices for users
        $bills = collect();
        $invoices = collect();
        $asns = collect();
        if (\Auth::user()->hasRole('Warehouse')) {
            $asns = Asn::where('created_by', \Auth::user()->creatorId())->orderByDesc('id')->get(['id', 'asn_no']);
        }
        $warehouses = \DB::table('warehouses')->where('created_by', \Auth::user()->creatorId())->orderBy('name')->get(['id','name']);
        $customers = \DB::table('customers')->where('created_by', \Auth::user()->creatorId())->orderBy('name')->get(['id','name']);
        $vendors = collect(); // Don't show vendors for users

        $query = SubProduct::query();
        $query->where('created_by', \Auth::user()->creatorId());
        $query->where('flag', '!=', SubProduct::FLAG_CANCELLED);

        // For regular users (not company/admin), filter by user's assigned warehouses
        $isCompany = (\Auth::user()->type == 'company' || \Auth::user()->type == 'super admin');
        if (!$isCompany) {
            // Get user's assigned warehouse IDs
            $userWarehouseIds = \Auth::user()->warehouses()->pluck('warehouses.id')->toArray();
            
            // If user has assigned warehouses, filter by them; otherwise show nothing
            if (!empty($userWarehouseIds)) {
                $query->whereIn('warehouse_id', $userWarehouseIds);
            } else {
                // If user has no assigned warehouses, return empty result
                $query->whereRaw('1 = 0'); // This will return no results
            }
        }

        // Global search
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function($subQ) use ($q) {
                $subQ->where('chassis_no', 'like', "%{$q}%")
                     ->orWhere('quantity', 'like', "%{$q}%")
                     ->orWhereHas('productService', function($psQ) use ($q) {
                         $psQ->where('name', 'like', "%{$q}%")
                             ->orWhere('sku', 'like', "%{$q}%");
                     });
            });
        }

        if ($request->filled('category_id')) {
            $productIds = \App\Models\ProductService::where('category_id', $request->category_id)->pluck('id');
            $query->whereIn('product_id', $productIds);
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('brand_id')) {
            $productIds = ProductService::where('brand_id', $request->brand_id)->pluck('id');
            $query->whereIn('product_id', $productIds);
        }
        if ($request->filled('sub_brand_id')) {
            $productIds = ProductService::where('sub_brand_id', $request->sub_brand_id)->pluck('id');
            $query->whereIn('product_id', $productIds);
        }
        // Only allow warehouse filtering for company/admin users
        if ($isCompany && $request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('asn_id') && \Auth::user()->hasRole('Warehouse')) {
            $query->where('asn_id', $request->asn_id);
        }
        // Don't filter by bill_id or invoice_id for users
        // Chassis numbers (vins field): paste from Excel (one per line, or comma/tab separated)
        if ($request->filled('vins')) {
            $vinsRaw = trim((string) $request->vins);
            $vins = array_values(array_filter(array_map('trim', preg_split('/[\r\n\t,]+/', $vinsRaw, -1, PREG_SPLIT_NO_EMPTY)), function ($v) {
                return $v !== '';
            }));
            if (!empty($vins)) {
                $query->whereIn('chassis_no', $vins);
            }
        }
        if ($request->filled('customer_id')) {
            $query->whereIn('id', function($sub) use ($request) {
                $sub->select('sp.id')
                    ->from('sub_products as sp')
                    ->join('invoice_products as ip2', 'ip2.sub_product_id', '=', 'sp.id')
                    ->join('invoices as inv2', 'inv2.id', '=', 'ip2.invoice_id')
                    ->where('inv2.customer_id', $request->customer_id);
            });
        }
        // Don't filter by vender_id for users
        
        // Filter by Purchase Status
        if ($request->filled('purchase_status')) {
            $query->where('flag', $request->purchase_status);
        }
        
        // Filter by Book Status
        if ($request->filled('book_status')) {
            $bookStatus = $request->book_status;
            $query->where(function($q) use ($bookStatus) {
                switch ($bookStatus) {
                    case 'free':
                        $q->where('booked', 0);
                        break;
                    case 'booked':
                        $q->where('booked', 1)
                          ->whereNotNull('invoice_id')
                          ->whereHas('invoice', function($invQ) {
                              $invQ->where('type', 'regular');
                          });
                        break;
                    case 'rented':
                        $q->where(function($rentQ) {
                            $rentQ->where(function($r1) {
                                $r1->where('booked', 1)
                                   ->whereNotNull('invoice_id')
                                   ->whereHas('invoice', function($invQ) {
                                       $invQ->where('type', 'rent');
                                   });
                            })->orWhere(function($r2) {
                                $r2->where('booked', 2)
                                   ->whereNotNull('invoice_id')
                                   ->whereHas('invoice', function($invQ) {
                                       $invQ->where('type', 'rent');
                                   });
                            });
                        });
                        break;
                    case 'sold':
                        $q->where(function($soldQ) {
                            $soldQ->where(function($s1) {
                                $s1->where('booked', 2)
                                   ->whereNull('invoice_id');
                            })->orWhere(function($s2) {
                                $s2->where('booked', 2)
                                   ->whereNotNull('invoice_id')
                                   ->whereHas('invoice', function($invQ) {
                                       $invQ->where('type', 'regular');
                                   });
                            })->orWhere(function($s3) {
                                $s3->where('booked', 1)
                                   ->whereNotNull('pos_id');
                            });
                        });
                        break;
                    case 'delivered':
                        $q->where(function($delQ) {
                            $delQ->where(function($d1) {
                                $d1->where('booked', 1)
                                   ->where(function($d1sub) {
                                       $d1sub->whereNull('invoice_id')
                                             ->orWhereDoesntHave('invoice', function($invQ) {
                                                 $invQ->whereIn('type', ['rent', 'regular']);
                                             });
                                   })
                                   ->whereNull('pos_id');
                            })
                            ->orWhere(function($d2) {
                                $d2->where('booked', 2)
                                   ->whereNotNull('invoice_id')
                                   ->whereHas('invoice', function($invQ) {
                                       $invQ->where('type', '!=', 'rent')
                                            ->where('type', '!=', 'regular');
                                   });
                            });
                        });
                        break;
                }
            });
        }

        // Calculate total quantity before pagination
        $totalStockQuantity = (clone $query)->sum('quantity');

        // Eager load relationships
        $query->with(['productService.brand', 'productService.subBrand', 'productService.category', 'invoice', 'pos', 'asn', 'images']);

        $query->orderBy('created_at', 'desc');
        $subProducts = $query->paginate(20);
        
        // Load car accessory request information
        $subProductIds = $subProducts->pluck('id')->toArray();
        $carAccessoryRequests = [];
        
        if (!empty($subProductIds)) {
            $requestItems = \App\Models\CarAccessoryRequestItem::whereIn('accessory_id', $subProductIds)
                ->whereNotNull('accessory_id')
                ->with(['request'])
                ->whereHas('request', function($q) {
                    $q->where('status', 'assigned');
                })
                ->get();
            
            foreach ($requestItems as $item) {
                if ($item->request && $item->accessory_id && in_array($item->accessory_id, $subProductIds)) {
                    $accessoryId = $item->accessory_id;
                    if (!isset($carAccessoryRequests[$accessoryId])) {
                        $carAccessoryRequests[$accessoryId] = [];
                    }
                    $exists = false;
                    foreach ($carAccessoryRequests[$accessoryId] as $existing) {
                        if ($existing['id'] == $item->request->id) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $carAccessoryRequests[$accessoryId][] = [
                            'id' => $item->request->id,
                            'request_no' => $item->request->request_no
                        ];
                    }
                }
            }
        }

        // Get custom fields (no custom fields for users to keep it simple)
        $customFields = collect();
        $customFieldValues = [];

        // isCompany is already defined above for warehouse filtering

        return view('subproducts.stock_report', compact('subProducts', 'categories', 'products', 'brands', 'subBrands', 'bills', 'invoices', 'asns', 'warehouses', 'customers', 'vendors', 'customFields', 'customFieldValues', 'carAccessoryRequests', 'totalStockQuantity', 'isCompany'));
    }

    public function stockReportExport(Request $request)
    {
        if (!\Auth::user()->can('manage product & service')) {
            return abort(403);
        }

        // Get custom fields (same logic as stockReport)
        if ($request->filled('category_id')) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())
                ->where('module', '=', 'sub-product')
                ->forCategory($request->category_id)
                ->get();
        } else {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())
                ->where('module', '=', 'sub-product')
                ->get();
        }

        // Get all sub-products with same filters (no pagination for export)
        $query = SubProduct::query();
        $query->where('created_by', \Auth::user()->creatorId());
        // Same as stock report: include zero qty, exclude Item Master imports
        $query->where(function ($q) {
            $q->whereNull('import_source')->orWhere('import_source', '!=', 'item_master');
        });

        // Apply the same filters as stockReport
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function($subQ) use ($q) {
                $subQ->where('chassis_no', 'like', "%{$q}%")
                     ->orWhere('quantity', 'like', "%{$q}%")
                     ->orWhereHas('productService', function($psQ) use ($q) {
                         $psQ->where('name', 'like', "%{$q}%")
                             ->orWhere('sku', 'like', "%{$q}%");
                     });
            });
        }

        if ($request->filled('category_id')) {
            $productIds = \App\Models\ProductService::where('category_id', $request->category_id)->pluck('id');
            $query->whereIn('product_id', $productIds);
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('brand_id')) {
            $productIds = ProductService::where('brand_id', $request->brand_id)->pluck('id');
            $query->whereIn('product_id', $productIds);
        }
        if ($request->filled('sub_brand_id')) {
            $productIds = ProductService::where('sub_brand_id', $request->sub_brand_id)->pluck('id');
            $query->whereIn('product_id', $productIds);
        }
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('bill_id')) {
            $query->where('bill_id', $request->bill_id);
        }
        if ($request->filled('invoice_id')) {
            $query->where('invoice_id', $request->invoice_id);
        }
        if ($request->filled('asn_id')) {
            $query->where('asn_id', $request->asn_id);
        }
        // Chassis numbers (vins field): paste from Excel (one per line, or comma/tab separated)
        if ($request->filled('vins')) {
            $vinsRaw = trim((string) $request->vins);
            $vins = array_values(array_filter(array_map('trim', preg_split('/[\r\n\t,]+/', $vinsRaw, -1, PREG_SPLIT_NO_EMPTY)), function ($v) {
                return $v !== '';
            }));
            if (!empty($vins)) {
                $query->whereIn('chassis_no', $vins);
            }
        }
        if ($request->filled('customer_id')) {
            $query->whereIn('id', function($sub) use ($request) {
                $sub->select('sp.id')
                    ->from('sub_products as sp')
                    ->join('invoice_products as ip2', 'ip2.sub_product_id', '=', 'sp.id')
                    ->join('invoices as inv2', 'inv2.id', '=', 'ip2.invoice_id')
                    ->where('inv2.customer_id', $request->customer_id);
            });
        }
        if ($request->filled('vender_id')) {
            $query->whereIn('id', function($sub) use ($request) {
                $sub->select('sp.id')
                    ->from('sub_products as sp')
                    ->join('bill_products as bp2', 'bp2.sub_product_id', '=', 'sp.id')
                    ->join('bills as b2', 'b2.id', '=', 'bp2.bill_id')
                    ->where('b2.vender_id', $request->vender_id);
            });
        }
        
        // Filter by Purchase Status
        if ($request->filled('purchase_status')) {
            $query->where('flag', $request->purchase_status);
        }
        
        // Filter by Book Status
        if ($request->filled('book_status')) {
            $bookStatus = $request->book_status;
            $query->where(function($q) use ($bookStatus) {
                switch ($bookStatus) {
                    case 'free':
                        $q->where('booked', 0);
                        break;
                    case 'booked':
                        $q->where('booked', 1)
                          ->whereNotNull('invoice_id')
                          ->whereHas('invoice', function($invQ) {
                              $invQ->where('type', 'regular');
                          });
                        break;
                    case 'rented':
                        $q->where(function($rentQ) {
                            $rentQ->where(function($r1) {
                                $r1->where('booked', 1)
                                   ->whereNotNull('invoice_id')
                                   ->whereHas('invoice', function($invQ) {
                                       $invQ->where('type', 'rent');
                                   });
                            })->orWhere(function($r2) {
                                $r2->where('booked', 2)
                                   ->whereNotNull('invoice_id')
                                   ->whereHas('invoice', function($invQ) {
                                       $invQ->where('type', 'rent');
                                   });
                            });
                        });
                        break;
                    case 'sold':
                        $q->where(function($soldQ) {
                            $soldQ->where(function($s1) {
                                $s1->where('booked', 2)
                                   ->whereNull('invoice_id');
                            })->orWhere(function($s2) {
                                $s2->where('booked', 2)
                                   ->whereNotNull('invoice_id')
                                   ->whereHas('invoice', function($invQ) {
                                       $invQ->where('type', 'regular');
                                   });
                            })->orWhere(function($s3) {
                                $s3->where('booked', 1)
                                   ->whereNotNull('pos_id');
                            });
                        });
                        break;
                    case 'delivered':
                        // Delivered is the else case - anything that doesn't match Free, Booked, Rented, or Sold
                        $q->where(function($delQ) {
                            // booked == 1 but not rented, not booked (regular invoice), and not sold (pos_id)
                            $delQ->where(function($d1) {
                                $d1->where('booked', 1)
                                   ->where(function($d1sub) {
                                       $d1sub->whereNull('invoice_id')
                                             ->orWhereDoesntHave('invoice', function($invQ) {
                                                 $invQ->whereIn('type', ['rent', 'regular']);
                                             });
                                   })
                                   ->whereNull('pos_id');
                            })
                            // booked == 2 but not sold and not rented
                            ->orWhere(function($d2) {
                                $d2->where('booked', 2)
                                   ->whereNotNull('invoice_id')
                                   ->whereHas('invoice', function($invQ) {
                                       $invQ->where('type', '!=', 'rent')
                                            ->where('type', '!=', 'regular');
                                   });
                            });
                        });
                        break;
                }
            });
        }

        // Eager load relationships for export
        $query->with(['productService.brand', 'productService.subBrand', 'productService.category', 'invoice', 'pos', 'asn']);

        $query->orderBy('created_at', 'desc');
        
        // Get sub-product IDs for custom field values (without loading all data)
        $subProductIds = (clone $query)->pluck('id')->toArray();
        
        // Load all custom field values in one query (much faster)
        $customFieldValues = [];
        if (!empty($subProductIds) && $customFields->isNotEmpty()) {
            $fieldIds = $customFields->pluck('id')->toArray();
            $allCustomValues = CustomFieldValue::whereIn('record_id', $subProductIds)
                ->whereIn('field_id', $fieldIds)
                ->get()
                ->groupBy('record_id');
            
            foreach ($allCustomValues as $recordId => $values) {
                $customFieldValues[$recordId] = $values->keyBy('field_id')->map(function ($item) {
                    return $item->value;
                });
            }
        }

        // Prepare filters for export (include all filter parameters - must match report so total qty matches)
        $filters = $request->only(['q', 'category_id', 'product_id', 'brand_id', 'sub_brand_id', 'warehouse_id', 'bill_id', 'invoice_id', 'asn_id', 'vins', 'customer_id', 'vender_id', 'purchase_status', 'book_status', 'show_zero_qty']);

        // Get track IDs from request if provided (for debugging)
        $trackIds = $request->input('track_ids', []);
        if (is_string($trackIds)) {
            $trackIds = array_map('trim', explode(',', $trackIds));
            $trackIds = array_filter($trackIds, function($id) {
                return is_numeric($id);
            });
        }
        
        // Log quantity directly from database for tracked IDs before export
        if (!empty($trackIds)) {
            foreach ($trackIds as $trackId) {
                $subProduct = SubProduct::find($trackId);
                if ($subProduct) {
                    \Log::info('StockReportExport: Database quantity check BEFORE export', [
                        'sub_product_id' => $trackId,
                        'quantity_from_db' => $subProduct->quantity,
                        'quantity_from_attributes' => $subProduct->attributes['quantity'] ?? 'NOT_SET',
                        'quantity_type' => gettype($subProduct->quantity),
                        'created_by' => $subProduct->created_by,
                        'creator_id' => \Auth::user()->creatorId(),
                    ]);
                }
            }
        }

        $filename = 'stock_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\StockReportExport(\Auth::user()->id, $filters, $customFields, $customFieldValues, $trackIds),
            $filename
        );
    }

    /**
     * Test method to debug why specific sub-product IDs are not in export
     * Usage: GET /sub-products/test-export-ids?ids=123,456,789&filters...
     */
    public function testStockReportExportIds(Request $request)
    {
        if (!\Auth::user()->can('manage product & service')) {
            return abort(403);
        }

        $ids = $request->input('ids');
        if (empty($ids)) {
            return response()->json([
                'error' => 'Please provide sub-product IDs in the "ids" parameter (comma-separated)'
            ], 400);
        }

        $subProductIds = array_map('trim', explode(',', $ids));
        $subProductIds = array_filter($subProductIds, function($id) {
            return is_numeric($id);
        });

        if (empty($subProductIds)) {
            return response()->json([
                'error' => 'No valid sub-product IDs provided'
            ], 400);
        }

        // Get filters from request (same as export)
        $filters = $request->only(['q', 'category_id', 'product_id', 'brand_id', 'sub_brand_id', 'warehouse_id', 'bill_id', 'invoice_id', 'asn_id', 'vins', 'customer_id', 'vender_id', 'purchase_status', 'book_status']);

        // Run the test
        $results = \App\Exports\StockReportExport::testSubProductIds(
            $subProductIds,
            \Auth::user()->id,
            $filters
        );

        // Also check total count
        $export = new \App\Exports\StockReportExport(\Auth::user()->id, $filters, [], []);
        $totalInExport = $export->query()->count();
        $totalInDatabase = SubProduct::where('created_by', \Auth::user()->creatorId())->count();

        return response()->json([
            'test_results' => $results,
            'summary' => [
                'total_tested' => count($subProductIds),
                'included_in_export' => count(array_filter($results, function($r) {
                    return isset($r['query_result']) && $r['query_result'] === 'INCLUDED';
                })),
                'excluded_from_export' => count(array_filter($results, function($r) {
                    return isset($r['query_result']) && $r['query_result'] === 'EXCLUDED';
                })),
                'total_in_export_query' => $totalInExport,
                'total_in_database' => $totalInDatabase,
                'filters_applied' => $filters
            ]
        ], 200, [], JSON_PRETTY_PRINT);
    }

    public function stockReportImport(Request $request)
    {
        if (!\Auth::user()->can('manage product & service')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => __('Permission denied.')], 403);
            }
            return abort(403);
        }

        try {
            $request->validate([
                'file' => 'required|mimes:xlsx,xls,csv',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Validation failed'),
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }

        try {
            // Increase execution time limit for large imports (up to 30 minutes)
            set_time_limit(1800);
            
            // Increase memory limit for large imports
            ini_set('memory_limit', '2048M');
            
            $import = new SubProductUpdateImport(\Auth::user()->creatorId());
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $errorCount = $import->getErrorCount();
            $errors = $import->getErrors();

            $message = "Import completed. Successfully updated: {$successCount} records.";
            if ($errorCount > 0) {
                $message .= " Errors: {$errorCount} records.";
                if (!empty($errors)) {
                    $message .= " Details: " . implode('; ', array_slice($errors, 0, 5));
                    if (count($errors) > 5) {
                        $message .= " (and " . (count($errors) - 5) . " more)";
                    }
                }
                
                // Return JSON for AJAX requests
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'success_count' => $successCount,
                        'error_count' => $errorCount,
                        'errors' => array_slice($errors, 0, 10) // Return first 10 errors
                    ]);
                }
                
                return redirect()->back()->with('warning', $message);
            }

            // Return JSON for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'success_count' => $successCount,
                    'error_count' => $errorCount
                ]);
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Stock report import failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => \Auth::user()->id
            ]);

            $errorMessage = __('Import failed: ') . $e->getMessage();
            
            // Return JSON for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }

            return redirect()->back()->with('error', $errorMessage);
        }
    }

    public function productsByAccount($accountId)
    {
        $creatorId = \Auth::user()->creatorId();

        $categories = ProductServiceCategory::where('purchase_account_id', $accountId)
            ->where('created_by', $creatorId)
            ->pluck('id');

        $account = ChartOfAccount::select('id', 'name', 'code', 'type')->find($accountId);
        
        // Query sub-products directly for pagination (no grouping by products)
        $subProductsQuery = SubProduct::query();
        $subProductsQuery->where(function ($q) {
            $q->whereNull('import_source')->orWhere('import_source', '!=', 'item_master');
        });
        
        if (!$categories->isEmpty()) {
            // Get product IDs that have qualifying sub-products
            $productIds = ProductService::whereIn('category_id', $categories)
                ->where('created_by', $creatorId)
                ->pluck('id');
            
            if ($productIds->isNotEmpty()) {
                $subProductsQuery->whereIn('product_id', $productIds)
                    ->whereNotNull('initial_stock')
                    ->where('initial_stock', '>', 0)
                    ->whereNotNull('initial_rate')
                    ->where('initial_rate', '>', 0);
            } else {
                // No products found, return empty pagination
                $subProductsQuery->whereRaw('1 = 0'); // Force empty result
            }
        } else {
            // No categories found, return empty pagination
            $subProductsQuery->whereRaw('1 = 0'); // Force empty result
        }
        
        // Eager load relationships to avoid N+1 queries
        $subProductsQuery->with([
            'productService.brand',
            'productService.subBrand',
            'invoice',
            'pos'
        ]);
        
        // Calculate total stock across all items (not just current page) - do this before pagination
        $totalStock = (clone $subProductsQuery)->selectRaw('SUM(initial_stock * initial_rate) as total')
            ->value('total') ?? 0;
        
        // Paginate sub-products
        $perPage = request()->get('per_page', 50); // Default 50 items per page
        $allSubProducts = $subProductsQuery->orderBy('id', 'desc')->paginate($perPage);
        
        // Get user IDs from paginated results for bulk loading
        $userIds = $allSubProducts->pluck('created_by')->unique()->filter()->values()->toArray();
        $users = \App\Models\User::whereIn('id', $userIds)
            ->select('id', 'name')
            ->get()
            ->keyBy('id');
        
        // Attach users to paginated sub-products
        foreach ($allSubProducts as $subProduct) {
            if ($subProduct->created_by && $users->has($subProduct->created_by)) {
                $subProduct->setAttribute('created_by_user', $users->get($subProduct->created_by));
            }
        }
        
        // Get all custom fields once (only necessary columns)
        $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())
            ->where('module', '=', 'sub-product')
            ->select('id', 'name', 'type', 'module', 'created_by')
            ->get();
        
        // Load custom field values for paginated sub-products only
        $paginatedSubProductIds = $allSubProducts->pluck('id');
        $customFieldValues = [];
        if ($paginatedSubProductIds->isNotEmpty() && $customFields->isNotEmpty()) {
            $allCustomFieldValues = CustomFieldValue::whereIn('record_id', $paginatedSubProductIds)
                ->whereIn('field_id', $customFields->pluck('id'))
                ->select('record_id', 'field_id', 'value')
                ->get()
                ->groupBy('record_id');
            
            foreach ($allCustomFieldValues as $subProductId => $values) {
                $customFieldValues[$subProductId] = $values->keyBy('field_id')
                    ->map(function ($item) {
                        return $item->value;
                    });
            }
        }
        
        return view('subproducts.by_account', compact('allSubProducts', 'account', 'customFields', 'customFieldValues', 'totalStock'));
    }

    /**
     * Add stock movements for all sub-products with initial stock for a given account
     * Optimized for large datasets (20k+ items) using chunking and bulk operations
     */
    public function addStockMovementsForAccount($accountId)
    {
        try {
            $creatorId = \Auth::user()->creatorId();
            set_time_limit(0); // Remove time limit for large operations
            ini_set('memory_limit', '512M'); // Increase memory limit

            // Get categories linked to this account
            $categories = ProductServiceCategory::where('purchase_account_id', $accountId)
                ->where('created_by', $creatorId)
                ->pluck('id');

            if ($categories->isEmpty()) {
                return redirect()->back()->with('error', __('No categories found for this account.'));
            }

            // Get product IDs that have qualifying sub-products
            $productIds = ProductService::whereIn('category_id', $categories)
                ->where('created_by', $creatorId)
                ->pluck('id');

            if ($productIds->isEmpty()) {
                return redirect()->back()->with('error', __('No products found for this account.'));
            }

            // Count total items first
            $totalCount = SubProduct::whereIn('product_id', $productIds)
                ->where('created_by', $creatorId)
                ->whereNotNull('initial_stock')
                ->where('initial_stock', '>', 0)
                ->whereNotNull('initial_rate')
                ->where('initial_rate', '>', 0)
                ->count();

            if ($totalCount == 0) {
                return redirect()->back()->with('error', __('No sub-products with initial stock found.'));
            }

            \Log::info('Starting stock movement processing', [
                'account_id' => $accountId,
                'total_items' => $totalCount
            ]);

            $created = 0;
            $updated = 0;
            $skipped = 0;
            $chunkSize = 500; // Process 500 items at a time
            $processed = 0;

            // Process in chunks to avoid memory issues
            SubProduct::whereIn('product_id', $productIds)
                ->where('created_by', $creatorId)
                ->whereNotNull('initial_stock')
                ->where('initial_stock', '>', 0)
                ->whereNotNull('initial_rate')
                ->where('initial_rate', '>', 0)
                ->select('id', 'product_id', 'initial_stock', 'initial_rate', 'created_by')
                ->chunk($chunkSize, function ($subProducts) use ($creatorId, &$created, &$updated, &$skipped, &$processed, $totalCount, $chunkSize) {
                    // Get all sub-product IDs for this chunk
                    $subProductIds = $subProducts->pluck('id')->toArray();

                    // Pre-load existing stock movements for this chunk (bulk query)
                    $existingStockMovements = StockMovement::whereIn('sub_product_id', $subProductIds)
                        ->where('activity', 'Opening Balance')
                        ->where('created_by', $creatorId)
                        ->get()
                        ->keyBy('sub_product_id');

                    // Prepare bulk insert array
                    $stockMovementsToInsert = [];
                    $stockMovementsToUpdate = [];
                    $now = now();

                    foreach ($subProducts as $subProduct) {
                        $existingStockMovement = $existingStockMovements->get($subProduct->id);

                        if ($existingStockMovement) {
                            // Collect updates for bulk update
                            $stockMovementsToUpdate[$existingStockMovement->id] = [
                                'product_id' => $subProduct->product_id,
                                'qty_in' => $subProduct->initial_stock,
                                'qty_out' => 0,
                                'avg_cost' => $subProduct->initial_rate,
                                'cost_price' => $subProduct->initial_rate,
                            ];
                            $updated++;
                        } else {
                            // Collect inserts for bulk insert
                            $stockMovementsToInsert[] = [
                                'product_id' => $subProduct->product_id,
                                'sub_product_id' => $subProduct->id,
                                'bill_id' => null,
                                'invoice_id' => null,
                                'pos_id' => null,
                                'qty_in' => $subProduct->initial_stock,
                                'qty_out' => 0,
                                'avg_cost' => $subProduct->initial_rate,
                                'cost_price' => $subProduct->initial_rate,
                                'activity' => 'Opening Balance',
                                'use_id' => null,
                                'item' => $subProduct->id,
                                'created_by' => $creatorId,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                            $created++;
                        }
                    }

                    // Perform bulk operations
                    DB::beginTransaction();
                    try {
                        // Bulk insert new stock movements
                        if (!empty($stockMovementsToInsert)) {
                            // Insert in smaller batches to avoid query size limits
                            $insertBatches = array_chunk($stockMovementsToInsert, 100);
                            foreach ($insertBatches as $batch) {
                                StockMovement::insert($batch);
                            }
                        }

                        // Bulk update existing stock movements
                        if (!empty($stockMovementsToUpdate)) {
                            foreach ($stockMovementsToUpdate as $id => $data) {
                                StockMovement::where('id', $id)->update($data);
                            }
                        }

                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        \Log::error('Error processing chunk', [
                            'chunk_size' => count($subProducts),
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        $skipped += count($subProducts);
                    }

                    $processed += count($subProducts);

                    // Log progress every 5000 items (10 chunks of 500)
                    $chunkNumber = (int)($processed / $chunkSize);
                    if ($chunkNumber > 0 && $chunkNumber % 10 == 0) {
                        $percentage = $totalCount > 0 ? round(($processed / $totalCount) * 100, 2) : 0;
                        \Log::info('Stock movement processing progress', [
                            'processed' => $processed,
                            'total' => $totalCount,
                            'percentage' => $percentage . '%',
                            'created' => $created,
                            'updated' => $updated,
                            'skipped' => $skipped
                        ]);
                    }

                    // Clear memory
                    unset($subProducts, $existingStockMovements, $stockMovementsToInsert, $stockMovementsToUpdate);
                });

            $message = __('Stock movements processed successfully.');
            if ($created > 0) {
                $message .= ' ' . __('Created') . ': ' . number_format($created);
            }
            if ($updated > 0) {
                $message .= ' ' . __('Updated') . ': ' . number_format($updated);
            }
            if ($skipped > 0) {
                $message .= ' ' . __('Skipped') . ': ' . number_format($skipped);
            }

            \Log::info('Stock movement processing completed', [
                'account_id' => $accountId,
                'total_processed' => $processed,
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped
            ]);

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Error adding stock movements for account', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', __('An error occurred while processing stock movements: ') . $e->getMessage());
        }
    }

    /**
     * Display sell report for items with book status 2 or 3
     */
    public function sellReport(Request $request)
    {
        if (!\Auth::user()->can('manage product & service')) {
            return abort(403);
        }

        $creatorId = \Auth::user()->creatorId();

        // Filters
        $categories = ProductServiceCategory::where('created_by', $creatorId)->pluck('name', 'id');
        $products = ProductService::where('created_by', $creatorId)->pluck('name', 'id');
        $bills = Bill::where('created_by', $creatorId)->where('type','bill')->get();
        $invoices = Invoice::where('created_by', $creatorId)->get();
        $warehouses = \DB::table('warehouses')->where('created_by', $creatorId)->orderBy('name')->get(['id','name']);
        $customers = \DB::table('customers')->where('created_by', $creatorId)->orderBy('name')->get(['id','name']);
        $vendors = \DB::table('venders')->where('created_by', $creatorId)->orderBy('name')->get(['id','name']);
        $brands = Brand::where('created_by', $creatorId)->orderBy('name')->get(['id','name']);
        $subBrands = VehicleModel::where('created_by', $creatorId)->orderBy('name')->get(['id','name','brand_id']);
        $poses = \App\Models\Pos::where('created_by', $creatorId)->orderBy('id', 'desc')->get(['id','pos_id']);

        // Build query from invoice_products and pos_products using UNION
        // Invoice Products Query
        $invoiceProductsQuery = \DB::table('invoice_products as ip')
            ->join('invoices as inv', 'inv.id', '=', 'ip.invoice_id')
            ->join('sub_products as sp', 'sp.id', '=', 'ip.sub_product_id')
            ->join('product_services as ps', 'ps.id', '=', 'ip.product_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'sp.warehouse_id')
            ->where('inv.created_by', $creatorId)
            ->where('sp.created_by', $creatorId)
            ->whereNotNull('ip.sub_product_id')
            ->select(
                'ip.id as transaction_id',
                'ip.sub_product_id',
                'ip.product_id',
                'ip.quantity',
                'ip.price',
                'ip.discount',
                'ip.tax',
                'sp.chassis_no as product_no',
                'sp.sale_price',
                'sp.purchase_price',
                'sp.warehouse_id',
                'sp.booked',
                'sp.flag',
                'sp.bill_id',
                'inv.id as invoice_id',
                \DB::raw('NULL as pos_id'),
                'inv.customer_id',
                'inv.salesman_id',
                'ps.name as product_name',
                'ps.sku',
                'ps.category_id',
                'ps.brand_id',
                'ps.sub_brand_id',
                'w.name as warehouse_name',
                'inv.created_at as transaction_date',
                \DB::raw("'invoice' as source_type")
            );

        // POS Products Query
        $posProductsQuery = \DB::table('pos_products as pp')
            ->join('pos as p', 'p.id', '=', 'pp.pos_id')
            ->join('sub_products as sp', 'sp.id', '=', 'pp.sub_product_id')
            ->join('product_services as ps', 'ps.id', '=', 'pp.product_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'sp.warehouse_id')
            ->where('p.created_by', $creatorId)
            ->where('sp.created_by', $creatorId)
            ->whereNotNull('pp.sub_product_id')
            ->select(
                'pp.id as transaction_id',
                'pp.sub_product_id',
                'pp.product_id',
                'pp.quantity',
                'pp.price',
                'pp.discount',
                'pp.tax',
                'sp.chassis_no as product_no',
                'sp.sale_price',
                'sp.purchase_price',
                'sp.warehouse_id',
                'sp.booked',
                'sp.flag',
                'sp.bill_id',
                \DB::raw('NULL as invoice_id'),
                'p.id as pos_id',
                \DB::raw('NULL as customer_id'),
                \DB::raw('NULL as salesman_id'),
                'ps.name as product_name',
                'ps.sku',
                'ps.category_id',
                'ps.brand_id',
                'ps.sub_brand_id',
                'w.name as warehouse_name',
                'p.created_at as transaction_date',
                \DB::raw("'pos' as source_type")
            );

        // Combine both queries
        $combinedQuery = $invoiceProductsQuery->union($posProductsQuery);
        
        // Get SQL and bindings
        $combinedSql = $combinedQuery->toSql();
        $combinedBindings = $combinedQuery->getBindings();
        
        // Create a subquery wrapper for filtering and pagination
        $query = \DB::table(\DB::raw("({$combinedSql}) as combined"));
        
        // Add bindings
        foreach ($combinedBindings as $binding) {
            $query->addBinding($binding, 'where');
        }

        // Global search
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function($subQ) use ($q) {
                $subQ->where('combined.product_no', 'like', "%{$q}%")
                     ->orWhere('combined.quantity', 'like', "%{$q}%")
                     ->orWhere('combined.product_name', 'like', "%{$q}%")
                     ->orWhere('combined.sku', 'like', "%{$q}%");
            });
        }

        // Product No filter
        if ($request->filled('product_no')) {
            $query->where('combined.product_no', 'like', "%{$request->product_no}%");
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->where('combined.category_id', $request->category_id);
        }

        // Product filter
        if ($request->filled('product_id')) {
            $query->where('combined.product_id', $request->product_id);
        }

        // Brand filter
        if ($request->filled('brand_id')) {
            $query->where('combined.brand_id', $request->brand_id);
        }

        // Sub Brand filter
        if ($request->filled('sub_brand_id')) {
            $query->where('combined.sub_brand_id', $request->sub_brand_id);
        }

        // Warehouse filter
        if ($request->filled('warehouse_id')) {
            $query->where('combined.warehouse_id', $request->warehouse_id);
        }

        // Bill filter - need to join with sub_products to get bill_id
        if ($request->filled('bill_id')) {
            $query->where('combined.bill_id', $request->bill_id);
        }

        // Invoice filter
        if ($request->filled('invoice_id')) {
            $query->where('combined.invoice_id', $request->invoice_id);
        }

        // POS filter
        if ($request->filled('pos_id')) {
            $query->where('combined.pos_id', $request->pos_id);
        }

        // Customer filter
        if ($request->filled('customer_id')) {
            $query->where('combined.customer_id', $request->customer_id);
        }

        // Vendor filter - need to join with bills through sub_products
        if ($request->filled('vender_id')) {
            $query->join('sub_products as sp_vendor', 'sp_vendor.id', '=', 'combined.sub_product_id')
                  ->join('bill_products as bp_vendor', 'bp_vendor.sub_product_id', '=', 'sp_vendor.id')
                  ->join('bills as b_vendor', 'b_vendor.id', '=', 'bp_vendor.bill_id')
                  ->where('b_vendor.vender_id', $request->vender_id)
                  ->where('b_vendor.created_by', $creatorId);
        }

        // Grouping logic - check all grouping options
        $groupByProduct = $request->has('group_by_product');
        $groupBySubBrand = $request->has('group_by_sub_brand');
        $groupBySalesman = $request->has('group_by_salesman');
        $groupByPurchaseMan = $request->has('group_by_purchase_man');
        $groupByCustomer = $request->has('group_by_customer');
        $groupByVendor = $request->has('group_by_vendor');
        $groupByWarehouse = $request->has('group_by_warehouse');

        // Priority order: Product > Sub Brand > Salesman > Purchase Man > Customer > Vendor > Warehouse
        if ($groupByProduct) {
            $query->orderBy('combined.product_id', 'asc');
            $query->orderBy('combined.transaction_date', 'desc');
        } elseif ($groupBySubBrand) {
            $query->orderBy('combined.sub_brand_id', 'asc');
            $query->orderBy('combined.transaction_date', 'desc');
        } elseif ($groupBySalesman) {
            $query->orderBy('combined.salesman_id', 'asc');
            $query->orderBy('combined.transaction_date', 'desc');
        } elseif ($groupByPurchaseMan) {
            // For purchase man, we need to join with bills through sub_products
            $query->join('sub_products as sp_pm', 'sp_pm.id', '=', 'combined.sub_product_id')
                  ->join('bill_products as bp_pm', 'bp_pm.sub_product_id', '=', 'sp_pm.id')
                  ->join('bills as b_pm', 'b_pm.id', '=', 'bp_pm.bill_id')
                  ->orderBy('b_pm.salesman_id', 'asc')
                  ->orderBy('combined.transaction_date', 'desc')
                  ->select('combined.*');
        } elseif ($groupByCustomer) {
            $query->orderBy('combined.customer_id', 'asc');
            $query->orderBy('combined.transaction_date', 'desc');
        } elseif ($groupByVendor) {
            // For vendor, we need to join with bills through sub_products
            if (!$request->filled('vender_id')) {
                $query->join('sub_products as sp_v', 'sp_v.id', '=', 'combined.sub_product_id')
                      ->join('bill_products as bp_v', 'bp_v.sub_product_id', '=', 'sp_v.id')
                      ->join('bills as b_v', 'b_v.id', '=', 'bp_v.bill_id')
                      ->orderBy('b_v.vender_id', 'asc')
                      ->orderBy('combined.transaction_date', 'desc')
                      ->select('combined.*');
            } else {
                $query->orderBy('combined.transaction_date', 'desc');
            }
        } elseif ($groupByWarehouse) {
            $query->orderBy('combined.warehouse_id', 'asc');
            $query->orderBy('combined.transaction_date', 'desc');
        } else {
            $query->orderBy('combined.transaction_date', 'desc');
        }

        // Get total count for pagination (before applying skip/take)
        $totalCount = $query->count();
        $perPage = 20;
        $currentPage = $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        
        // Get paginated results
        $results = $query->skip($offset)->take($perPage)->get();
        
        // Create paginator manually with proper query string support
        $subProducts = new \Illuminate\Pagination\LengthAwarePaginator(
            $results,
            $totalCount,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query()
            ]
        );

        // Load sub-products and their relationships for the results
        $subProductIds = $results->pluck('sub_product_id')->unique();
        $subProductsData = SubProduct::whereIn('id', $subProductIds)
            ->with(['productService.brand', 'productService.subBrand', 'productService.category', 'warehouse.country', 'bill', 'invoice', 'pos'])
            ->get()
            ->keyBy('id');
        
        // Also load products separately for grouping
        $productIds = $results->pluck('product_id')->unique();
        $productsData = ProductService::whereIn('id', $productIds)
            ->with(['brand', 'subBrand', 'category'])
            ->get()
            ->keyBy('id');

        // Preload users for salesman/purchase man grouping to avoid N+1 queries
        $salesmen = collect();
        $purchaseMen = collect();
        if ($groupBySalesman || $groupByPurchaseMan) {
            $salesmanIds = [];
            $purchaseManIds = [];
            foreach ($results as $row) {
                if ($groupBySalesman && $row->salesman_id) {
                    $salesmanIds[] = $row->salesman_id;
                }
                if ($groupByPurchaseMan && isset($subProductsData[$row->sub_product_id])) {
                    $sp = $subProductsData[$row->sub_product_id];
                    if ($sp->bill && $sp->bill->salesman_id) {
                        $purchaseManIds[] = $sp->bill->salesman_id;
                    }
                }
            }
            if (!empty($salesmanIds)) {
                $salesmen = \App\Models\User::whereIn('id', array_unique($salesmanIds))->pluck('name', 'id');
            }
            if (!empty($purchaseManIds)) {
                $purchaseMen = \App\Models\User::whereIn('id', array_unique($purchaseManIds))->pluck('name', 'id');
            }
        }

        // Get custom fields for sub-products
        if ($request->filled('category_id')) {
            $customFields = CustomField::where('created_by', '=', $creatorId)
                ->where('module', '=', 'sub-product')
                ->forCategory($request->category_id)
                ->get();
        } else {
            $customFields = CustomField::where('created_by', '=', $creatorId)
                ->where('module', '=', 'sub-product')
                ->get();
        }

        $customFieldValues = [];
        foreach ($subProductIds as $subProductId) {
            $customFieldValues[$subProductId] = CustomFieldValue::where('record_id', $subProductId)
                ->whereIn('field_id', $customFields->pluck('id'))
                ->get()
                ->keyBy('field_id')
                ->map(function ($item) {
                    return $item->value;
                });
        }

        // Group products by grouping criteria, then by product_id for expandable rows
        $groupedByCriteria = [];
        foreach ($results as $row) {
            $sp = $subProductsData[$row->sub_product_id] ?? null;
            $product = $productsData[$row->product_id] ?? ($sp ? $sp->productService : null);
            
            // Determine the group key based on active grouping
            $groupKey = null;
            if ($groupByProduct) {
                $groupKey = $row->product_id;
            } elseif ($groupBySubBrand) {
                $groupKey = $row->sub_brand_id ?? 'no_sub_brand';
            } elseif ($groupBySalesman) {
                $groupKey = $row->salesman_id ?? 'no_salesman';
            } elseif ($groupByPurchaseMan) {
                $purchaseManId = $sp && $sp->bill ? $sp->bill->salesman_id : null;
                $groupKey = $purchaseManId ?? 'no_purchase_man';
            } elseif ($groupByCustomer) {
                $groupKey = $row->customer_id ?? 'no_customer';
            } elseif ($groupByVendor) {
                $vendorId = $sp && $sp->bill ? $sp->bill->vender_id : null;
                $groupKey = $vendorId ?? 'no_vendor';
            } elseif ($groupByWarehouse) {
                $groupKey = $row->warehouse_id ?? 'no_warehouse';
            } else {
                $groupKey = 'all';
            }

            if (!isset($groupedByCriteria[$groupKey])) {
                $groupedByCriteria[$groupKey] = [];
            }

            $productId = $row->product_id;
            if (!isset($groupedByCriteria[$groupKey][$productId])) {
                $groupedByCriteria[$groupKey][$productId] = [
                    'product' => $product,
                    'subProducts' => [],
                    'totalQty' => 0,
                    'totalSalePrice' => 0,
                ];
            }
            // Store the row data with sub-product reference
            $rowData = (object) array_merge((array) $row, ['subProduct' => $sp]);
            $groupedByCriteria[$groupKey][$productId]['subProducts'][] = $rowData;
            // Use quantity from invoice_products/pos_products
            $groupedByCriteria[$groupKey][$productId]['totalQty'] += $row->quantity;
            $groupedByCriteria[$groupKey][$productId]['totalSalePrice'] += ($row->price * $row->quantity);
        }

        // Calculate group-level totals including expenses
        // Only include sold items from invoices and POS
        $groupTotals = [];
        foreach ($groupedByCriteria as $groupKey => $groupProducts) {
            $groupTotals[$groupKey] = [
                'totalQty' => 0,
                'totalSalePrice' => 0,
                'totalPurchasePrice' => 0,
                'totalExpense' => 0,
                'profitLoss' => 0,
            ];

            // Track expenses per sub-product (one-time costs, not multiplied by quantity)
            $subProductExpenses = [];

            foreach ($groupProducts as $productId => $productData) {
                // Sum quantities from sold items (invoices and POS only)
                $groupTotals[$groupKey]['totalQty'] += $productData['totalQty'];
                // Sum sell price * quantity for sold items
                $groupTotals[$groupKey]['totalSalePrice'] += $productData['totalSalePrice'];

                // Calculate purchase price and expenses for each sold item
                foreach ($productData['subProducts'] as $row) {
                    $subProductId = $row->sub_product_id;
                    $sp = $subProductsData[$subProductId] ?? null;
                    $purchasePrice = $row->purchase_price ?? 0;
                    $quantity = $row->quantity ?? 0; // Quantity sold from invoice/POS
                    
                    // Add purchase price total: quantity sold * purchase price
                    $groupTotals[$groupKey]['totalPurchasePrice'] += ($purchasePrice * $quantity);

                    // Calculate expenses for this sub-product (only calculate once per sub-product)
                    // Expenses are one-time costs per sub-product, not multiplied by quantity
                    if ($sp && $sp->productService && !isset($subProductExpenses[$subProductId])) {
                        $category = $sp->productService->category;
                        $purchaseAccountId = $category ? $category->purchase_account_id : null;

                        // Calculate direct expense amount
                        $directExpenseAmount = 0;
                        if ($subProductId && $purchaseAccountId) {
                            $directExpenseAmount = \App\Models\DirectExpenseItem::where('sub_product_id', $subProductId)
                                ->where('chart_account_id', $purchaseAccountId)
                                ->whereHas('directExpense', function ($query) use ($creatorId) {
                                    $query->where('created_by', $creatorId);
                                })
                                ->sum('amount');
                        }

                        // Calculate car accessory amount
                        $carAccessoryAmount = 0;
                        if ($subProductId) {
                            $carAccessoryAmount = \App\Models\CarAccessoryRequestItem::where(function ($query) use ($subProductId) {
                                $query->where('car_id', $subProductId)
                                    ->orWhere('accessory_id', $subProductId);
                            })
                            ->whereHas('request', function ($query) use ($creatorId) {
                                $query->where('created_by', $creatorId);
                            })
                            ->sum('sell_price');
                        }

                        // Store total expense per sub-product (one-time cost)
                        $subProductExpenses[$subProductId] = $directExpenseAmount + $carAccessoryAmount;
                        // Add expense once per sub-product (not multiplied by quantity)
                        $groupTotals[$groupKey]['totalExpense'] += $subProductExpenses[$subProductId];
                    }
                }
            }

            // Calculate profit/loss: total sell - total purchase - total expense
            // totalSalePrice = sum of (quantity sold * sell price)
            // totalPurchasePrice = sum of (quantity sold * purchase price)
            // totalExpense = one-time expense per sold sub-product
            $groupTotals[$groupKey]['profitLoss'] = $groupTotals[$groupKey]['totalSalePrice']
                - $groupTotals[$groupKey]['totalPurchasePrice']
                - $groupTotals[$groupKey]['totalExpense'];
        }

        return view('subproducts.sell_report', compact(
            'subProducts', 
            'categories', 
            'products', 
            'bills', 
            'invoices', 
            'warehouses', 
            'customers', 
            'vendors', 
            'brands',
            'subBrands',
            'poses',
            'customFields', 
            'customFieldValues',
            'groupByProduct',
            'groupBySubBrand',
            'groupBySalesman',
            'groupByPurchaseMan',
            'groupByCustomer',
            'groupByVendor',
            'groupByWarehouse',
            'salesmen',
            'purchaseMen',
            'groupedByCriteria',
            'groupTotals',
            'subProductsData'
        ));
    }

    public function stockMovementReport(Request $request)
    {
        if (!\Auth::user()->can('manage product & service')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        // Initialize query from sub_products table
        $query = SubProduct::where('sub_products.created_by', '=', \Auth::user()->creatorId())
            ->where('sub_products.flag', '!=', 2)
            ->where(function ($q) {
                $q->whereNull('sub_products.import_source')->orWhere('sub_products.import_source', '!=', 'item_master');
            })
            ->with(['productService.brand', 'bill', 'invoice', 'pos', 'warehouse']);

        // Apply filters
        if ($request->filled('product_id')) {
            $query->where('sub_products.product_id', $request->product_id);
        }

        if ($request->filled('sub_product_id')) {
            $query->where('sub_products.id', $request->sub_product_id);
        }

        // Filter by barcode (product_no)
        if ($request->filled('barcode')) {
            $barcode = trim($request->barcode);
            $query->where('sub_products.chassis_no', 'LIKE', '%' . $barcode . '%');
        }

        if ($request->filled('customer_id')) {
            $query->whereHas('invoice', function($q) use ($request) {
                $q->where('customer_id', $request->customer_id);
            });
        }

        if ($request->filled('vender_id')) {
            $query->whereHas('bill', function($q) use ($request) {
                $q->where('vender_id', $request->vender_id);
            });
        }

        if ($request->filled('bill_id')) {
            $query->where('sub_products.bill_id', $request->bill_id);
        }

        if ($request->filled('invoice_id')) {
            $query->where('sub_products.invoice_id', $request->invoice_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('sub_products.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('sub_products.created_at', '<=', $request->date_to);
        }

        // Filter by activity (PURCHASE = has bill_id, SALES = has invoice_id or pos_id)
        if ($request->filled('activity')) {
            if ($request->activity == 'PURCHASE') {
                $query->whereNotNull('sub_products.bill_id');
            } elseif ($request->activity == 'SALES') {
                $query->where(function($q) {
                    $q->whereNotNull('sub_products.invoice_id')
                      ->orWhereNotNull('sub_products.pos_id');
                });
            }
        }

        // Order by date
        $query->orderBy('sub_products.created_at', 'asc');

        $subProducts = $query->paginate(50);

        // Get ASN data by matching part_no with product_no
        $productNos = $subProducts->pluck('product_no')->filter()->unique();
        $asnItems = AsnItem::whereIn('part_no', $productNos)
            ->whereHas('asn', function($q) {
                $q->where('created_by', '=', \Auth::user()->creatorId());
            })
            ->with('asn')
            ->get();
        
        // Create a map of product_no to ASN number
        $asnMap = [];
        foreach ($asnItems as $asnItem) {
            if ($asnItem->asn) {
                $asnMap[$asnItem->part_no] = $asnItem->asn->asn_no;
            }
        }

        // Transform sub_products to stock movement format and calculate running stock
        $runningStock = 0;
        $stockMovements = collect();
        
        foreach ($subProducts as $subProduct) {
            // Determine activity based on bill_id or invoice_id
            $activity = null;
            $qtyIn = 0;
            $qtyOut = 0;
            $customerSupplier = null;
            $invoiceProduct = null;
            $posProduct = null;
            
            if ($subProduct->bill_id) {
                // PURCHASE - stock in
                $activity = 'PURCHASE';
                $qtyIn = $subProduct->quantity ?? 0;
                $qtyOut = 0;
                if ($subProduct->bill && $subProduct->bill->vender) {
                    $customerSupplier = $subProduct->bill->vender->name;
                }
            } elseif ($subProduct->invoice_id) {
                // SALES - stock out
                $activity = 'SALES';
                $qtyIn = 0;
                // Get quantity from invoice_product
                $invoiceProduct = \App\Models\InvoiceProduct::where('invoice_id', $subProduct->invoice_id)
                    ->where('sub_product_id', $subProduct->id)
                    ->first();
                $qtyOut = $invoiceProduct ? $invoiceProduct->quantity : 0;
                if ($subProduct->invoice && $subProduct->invoice->customer) {
                    $customerSupplier = $subProduct->invoice->customer->name;
                }
            } elseif ($subProduct->pos_id) {
                // SALES via POS - stock out
                $activity = 'SALES';
                $qtyIn = 0;
                // Get quantity from pos_products
                $posProduct = \App\Models\PosProduct::where('pos_id', $subProduct->pos_id)
                    ->where('sub_product_id', $subProduct->id)
                    ->first();
                $qtyOut = $posProduct ? $posProduct->quantity : 0;
                if ($subProduct->pos && $subProduct->pos->customer) {
                    $customerSupplier = $subProduct->pos->customer->name;
                }
            }
            
            // Skip if no activity determined
            if (!$activity) {
                continue;
            }
            
            // Apply activity filter if specified
            if ($request->filled('activity') && $activity != $request->activity) {
                continue;
            }
            
            $runningStock += $qtyIn - $qtyOut;
            
            // Sold price: invoice net of per-unit discount; POS uses combo unit price when set, then line discount %
            $soldPrice = 0;
            if ($invoiceProduct) {
                $soldPrice = StockMovement::netUnitSoldPriceFromInvoiceProduct($invoiceProduct);
            } elseif ($posProduct) {
                $soldPrice = StockMovement::netUnitSoldPriceFromPosProduct($posProduct);
            }
            
            // Create movement object
            $movement = (object)[
                'id' => $subProduct->id,
                'created_at' => $subProduct->created_at,
                'activity' => $activity,
                'product' => $subProduct->productService,
                'Subproduct' => $subProduct,
                'bill' => $subProduct->bill,
                'invoice' => $subProduct->invoice,
                'pos' => $subProduct->pos,
                'customer' => $subProduct->invoice ? $subProduct->invoice->customer : ($subProduct->pos ? $subProduct->pos->customer : null),
                'vendor' => $subProduct->bill ? $subProduct->bill->vender : null,
                'warehouse' => $subProduct->warehouse,
                'qty_in' => $qtyIn,
                'qty_out' => $qtyOut,
                'cost_price' => $subProduct->purchase_price ?? 0,
                'sold_price' => $soldPrice,
                'running_stock' => $runningStock,
                'customer_supplier' => $customerSupplier,
                'bill_id' => $subProduct->bill_id,
                'invoice_id' => $subProduct->invoice_id,
                'pos_id' => $subProduct->pos_id,
            ];
            
            // Attach ASN number if exists (match by product_no = part_no)
            if ($subProduct->chassis_no && isset($asnMap[$subProduct->chassis_no])) {
                $movement->asn_no = $asnMap[$subProduct->chassis_no];
            }
            
            $stockMovements->push($movement);
        }

        // Get filter options
        $products = ProductService::where('created_by', '=', \Auth::user()->creatorId())
            ->pluck('name', 'id');
        
        $brands = Brand::where('created_by', '=', \Auth::user()->creatorId())
            ->orderBy('name', 'asc')
            ->pluck('name', 'id');
        
        $customers = Customer::where('created_by', '=', \Auth::user()->creatorId())
            ->pluck('name', 'id');
        
        $vendors = Vender::where('created_by', '=', \Auth::user()->creatorId())
            ->pluck('name', 'id');
        
        $bills = Bill::where('created_by', '=', \Auth::user()->creatorId())
            ->orderBy('bill_date', 'desc')
            ->get();
        
        $invoices = Invoice::where('created_by', '=', \Auth::user()->creatorId())
            ->orderBy('issue_date', 'desc')
            ->get();

        // Create paginator for stock movements collection
        $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $perPage = 50;
        $currentItems = $stockMovements->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginatedMovements = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            $stockMovements->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return view('subproducts.stock_movement_report', compact(
            'stockMovements',
            'paginatedMovements',
            'subProducts',
            'products',
            'brands',
            'customers',
            'vendors',
            'bills',
            'invoices'
        ));
    }

    /**
     * Export stock movement report to Excel
     */
    public function exportStockMovementReport(Request $request)
    {
        if (!\Auth::user()->can('manage product & service')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\StockMovementReportExport($request, \Auth::user()->creatorId()),
            'stock_movement_report_' . date('Y-m-d_His') . '.xlsx'
        );
    }

    /**
     * AJAX modal body: sub-product gallery (stock report).
     */
    public function stockReportSubProductGallery($id)
    {
        if (! $this->userCanAccessStockReport()) {
            abort(403);
        }

        $subProduct = SubProduct::with(['images', 'productService.brand', 'productService.subBrand'])
            ->where('created_by', \Auth::user()->creatorId())
            ->findOrFail($id);

        return view('subproducts.stock_gallery_modal', compact('subProduct'));
    }

    /**
     * PDF brochure for a single sub-product (same spirit as product brochure).
     */
    public function stockReportSubProductBrochurePdf($id)
    {
        if (! $this->userCanAccessStockReport()) {
            abort(403);
        }

        $subProduct = SubProduct::with([
            'images',
            'warehouse',
            'customFieldValues',
            'productService.category',
            'productService.brand',
            'productService.subBrand',
            'productService.unit',
        ])
            ->where('created_by', \Auth::user()->creatorId())
            ->findOrFail($id);

        $productService = $subProduct->productService;

        $customFieldRows = $this->subProductBrochureCustomFieldRows($subProduct);

        $imageBlocks = [];
        foreach ($subProduct->images as $img) {
            $dataUri = $this->subProductBrochureImageToDataUri($img->file_name ?? null);
            if ($dataUri) {
                $imageBlocks[] = [
                    'src' => $dataUri,
                    'caption' => __('Item image'),
                ];
            }
        }

        $settings = Utility::settings();
        $tenantSettings = Utility::settingsById(\Auth::user()->creatorId());
        if (is_array($tenantSettings)) {
            $settings = array_merge($settings, $tenantSettings);
        }

        $logoDataUri = $this->subProductBrochureCompanyLogoDataUri($settings);

        $pdf = Pdf::loadView('subproducts.brochure_pdf', compact('subProduct', 'productService', 'customFieldRows', 'imageBlocks', 'settings', 'logoDataUri'))
            ->setPaper('a4', 'portrait');

        $safeChassis = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) ($subProduct->chassis_no ?? '')) ?: ('item-'.$subProduct->id);

        return $pdf->download('SubProduct-'.$safeChassis.'-Brochure.pdf');
    }

    protected function userCanAccessStockReport(): bool
    {
        $user = \Auth::user();

        return $user->can('manage product & service')
            || $user->can('manage pos')
            || $user->can('add pos')
            || $user->can('stock report');
    }

    /**
     * @return array<int, array{label: string, value: string, type: string}>
     */
    protected function subProductBrochureCustomFieldRows(SubProduct $subProduct): array
    {
        $creatorId = \Auth::user()->creatorId();
        $categoryId = $subProduct->productService?->category_id;

        $fields = CustomField::where('created_by', $creatorId)
            ->where('module', 'sub-product')
            ->with('categories')
            ->orderBy('name')
            ->get();

        $valueRows = $subProduct->relationLoaded('customFieldValues') && $subProduct->customFieldValues->isNotEmpty()
            ? $subProduct->customFieldValues->keyBy('field_id')
            : CustomFieldValue::where('record_id', $subProduct->id)
                ->whereIn('field_id', $fields->pluck('id'))
                ->get()
                ->keyBy('field_id');

        $rows = [];
        foreach ($fields as $field) {
            if ($field->categories->isNotEmpty()) {
                if (! $categoryId || ! $field->categories->contains('id', $categoryId)) {
                    continue;
                }
            }

            $raw = $valueRows->get($field->id)?->value;
            $display = ($raw === null || $raw === '') ? '—' : (string) $raw;
            $rows[] = [
                'label' => $field->name,
                'value' => $display,
                'type' => $field->type ?? 'text',
            ];
        }

        return $rows;
    }

    protected function subProductBrochureImageToDataUri(?string $fileName): ?string
    {
        if (empty($fileName)) {
            return null;
        }
        foreach ([
            storage_path('app/public/uploads/sub_product_image/'.$fileName),
            public_path('storage/uploads/sub_product_image/'.$fileName),
        ] as $path) {
            if ($path && is_readable($path)) {
                $mime = @mime_content_type($path) ?: 'image/jpeg';

                return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
            }
        }

        return null;
    }

    protected function subProductBrochureCompanyLogoDataUri(array $settings): ?string
    {
        $invoiceLogo = (string) ($settings['invoice_logo'] ?? '');
        if ($invoiceLogo !== '') {
            foreach ([
                storage_path('app/public/invoice_logo/'.$invoiceLogo),
                public_path('storage/invoice_logo/'.$invoiceLogo),
            ] as $path) {
                if ($path && is_readable($path)) {
                    $uri = $this->subProductBrochureFileToDataUri($path);
                    if ($uri !== null) {
                        return $uri;
                    }
                }
            }
            $url = Utility::get_file('invoice_logo/').$invoiceLogo;
            if (is_string($url) && str_starts_with($url, 'http')) {
                $content = @file_get_contents($url);
                if ($content !== false && $content !== '') {
                    $mime = $this->subProductBrochureGuessMimeFromFileName($invoiceLogo);

                    return 'data:'.$mime.';base64,'.base64_encode($content);
                }
            }
        }

        $companyLogoDark = $settings['company_logo_dark'] ?? '';
        $companyLogoLight = $settings['company_logo_light'] ?? '';
        if (($settings['cust_darklayout'] ?? '') == 'on') {
            $file = ! empty($companyLogoLight) ? $companyLogoLight : 'logo-dark.png';
        } else {
            $file = ! empty($companyLogoDark) ? $companyLogoDark : 'logo-dark.png';
        }
        $local = public_path('documents'.DIRECTORY_SEPARATOR.$file);
        if (is_readable($local)) {
            return $this->subProductBrochureFileToDataUri($local);
        }

        return null;
    }

    protected function subProductBrochureFileToDataUri(string $absolutePath): ?string
    {
        if (! is_readable($absolutePath)) {
            return null;
        }
        $mime = @mime_content_type($absolutePath) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($absolutePath));
    }

    protected function subProductBrochureGuessMimeFromFileName(string $fileName): string
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'image/png',
        };
    }
}
