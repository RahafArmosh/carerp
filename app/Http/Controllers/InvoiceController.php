<?php

namespace App\Http\Controllers;

use App\Exports\InvoiceExport;
use App\Exports\InvoiceItemsExport;
use App\Imports\InvoiceImport;
use App\Models\BankAccount;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\CustomField;
use App\Models\Invoice;
use App\Models\InvoiceBankTransfer;
use App\Models\InvoicePayment;
use App\Models\InvoiceProduct;
use App\Models\Plan;
use App\Models\ProductService;
use App\Models\BillProduct;
use App\Models\ProductServiceCategory;
use App\Models\SubProduct;
use App\Models\StockReport;
use App\Models\CustomFieldValue;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Tax;
use App\Models\Utility;
use App\Models\GeneralLedger;
use App\Models\TransactionLines;
use App\Models\Bill;
use App\Models\Currency;
use App\Models\Color;
use App\Models\Country;
use App\Models\StockMovement;
use App\Models\ChartOfAccount;
use App\Models\InvoiceExpense;
use App\Models\ChartOfAccountType;
use App\Models\ChartOfAccountSubType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\CustomerPayment;
use App\Models\AccountingDocument;
use App\Models\InvoiceStatusChange;
use App\Models\MasterlistLeadger;
use App\Models\SaleOrder;
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    /**
     * Get sub-products for an invoice using invoice_products table only.
     */
    private function getInvoiceSubProductsFromItems(int $invoiceId)
    {
        $subProductIds = InvoiceProduct::where('invoice_id', $invoiceId)
            ->whereNotNull('sub_product_id')
            ->pluck('sub_product_id')
            ->filter()
            ->unique()
            ->values();

        if ($subProductIds->isEmpty()) {
            return collect();
        }

        return SubProduct::whereIn('id', $subProductIds)->get()->keyBy('id');
    }

    public function __construct() {}

    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->can('approve invoice') && !$user->can('manage invoice')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        // Shared values
        $customer = Customer::where('created_by', $user->creatorId())->pluck('name', 'id')->prepend('Select Customer', '');
        $status = array_filter(Invoice::$statues, fn($val) => trim($val) !== '', ARRAY_FILTER_USE_BOTH);
        $paymentstatues = array_filter(Invoice::$paymentstatues, fn($val) => trim($val) !== '', ARRAY_FILTER_USE_BOTH);

        // Adjust statuses for "manage invoice" (without approve)
        if ($user->can('manage invoice') && !$user->can('approve invoice')) {
            $status = array_filter($status, fn($val) => $val !== 'Send To Approve' && !empty($val));
        }

        // Define base query for invoices
        $query = Invoice::where('created_by', $user->creatorId())->where('type', 'regular');

        // DataTables server-side processing
        if ($request->ajax() || $request->wantsJson() || $request->has('draw')) {
            $draw = $request->get('draw');
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 100);

            // Apply filters
            if ($request->filled('customer')) {
                $query->where('customer_id', $request->customer);
            }

            if ($request->filled('issue_date')) {
                $dates = explode(' to ', $request->issue_date);
                $start_date = trim($dates[0]);
                $end_date = $dates[1] ?? $start_date;
                $query->whereBetween('issue_date', [$start_date, $end_date]);
            }

            // IMPORTANT: Use `has` or `isset` instead of `!empty` to allow status = 0
            if ($request->status !== null && $request->status !== '') {
                $query->where('status', $request->status);
            }

            if ($request->paymentstatues !== null && $request->paymentstatues !== '') {
                $query->where('payment_status', $request->paymentstatues);
            }

            if ($user->can('manage invoice') && !$user->can('approve invoice')) {
                $query->where('status', '<>', 1); // remove 'Send to Approve' records
            }

            // Global search
            if (!empty($request->input('search.value'))) {
                $search = $request->input('search.value');
                $query->search($search);
            }

            $totalRecords = Invoice::where('created_by', $user->creatorId())->where('type', 'regular')->count();
            $filteredRecords = $query->count();

            // Ordering - Default to sort by issue_date ascending (oldest first)
            $columns = [
                'invoice_id',
                'customer',
                'issue_date',
                'due_date',
                'due_amount',
                'status',
                'payment_status',
                'action',
            ];
            
            // Check if user explicitly requested a sort column
            $orderColIdx = $request->input('order.0.column');
            $orderCol = null;
            
            // Handle order column index (DataTables sends 0-based index)
            if ($orderColIdx !== null && is_numeric($orderColIdx) && isset($columns[(int)$orderColIdx])) {
                $orderCol = $columns[(int)$orderColIdx];
            }
            
            // Default to issue_date if no explicit sort or if invalid column
            if ($orderCol === null || $orderCol === 'issue_date') {
                $orderDir = ($orderCol === 'issue_date' && $request->input('order.0.dir'))
                    ? $request->input('order.0.dir')
                    : 'asc';
                // Sort by issue_date (oldest first by default) - ensure proper date sorting
                $query->orderBy('invoices.issue_date', $orderDir);
            } else {
                // User sorted by another column - apply that sort, then issue_date as secondary
                $orderDir = $request->input('order.0.dir', 'asc');
                switch ($orderCol) {
                    case 'invoice_id':
                        $query->orderBy('invoices.invoice_id', $orderDir)
                              ->orderBy('invoices.issue_date', 'asc');
                        break;
                    case 'customer':
                        $query->leftJoin('customers', 'invoices.customer_id', '=', 'customers.id')
                            ->orderBy('customers.name', $orderDir)
                            ->orderBy('invoices.issue_date', 'asc')
                            ->select('invoices.*');
                        break;
                    case 'due_date':
                        $query->orderBy('invoices.due_date', $orderDir)
                              ->orderBy('invoices.issue_date', 'asc');
                        break;
                    case 'status':
                        $query->orderBy('invoices.status', $orderDir)
                              ->orderBy('invoices.issue_date', 'asc');
                        break;
                    case 'payment_status':
                        $query->orderBy('invoices.payment_status', $orderDir)
                              ->orderBy('invoices.issue_date', 'asc');
                        break;
                    default:
                        // Fallback to issue_date
                        $query->orderBy('invoices.issue_date', 'asc');
                        break;
                }
            }

            $invoices = $query->skip($start)->take($length)->get();

            $data = [];
            foreach ($invoices as $invoice) {
                $data[] = [
                    'invoice_id' => ($invoice->type == 'rent')
                        ? '<a href="' . route('rentinvoice.show', Crypt::encrypt($invoice->id)) . '" class="btn btn-outline-primary">' . e(Auth::user()->invoiceNumberFormat($invoice->invoice_id)) . '</a>'
                        : '<a href="' . route('invoice.show', Crypt::encrypt($invoice->id)) . '" class="btn btn-outline-primary">' . e(Auth::user()->invoiceNumberFormat($invoice->invoice_id)) . '</a>',
                    'customer' => e(optional(\App\Models\Customer::find($invoice->customer_id))->name ?? 'N/A'),
                    'issue_date' => e(Auth::user()->dateFormat($invoice->issue_date)),
                    'issue_date_raw' => $invoice->issue_date, // Raw date for proper sorting
                    'due_date' => ($invoice->due_date < date('Y-m-d'))
                        ? '<p class="text-danger">' . e(Auth::user()->dateFormat($invoice->due_date)) . '</p>'
                        : e(Auth::user()->dateFormat($invoice->due_date)),
                    'due_date_raw' => $invoice->due_date, // Raw date for proper sorting
                    'due_amount' => e(Auth::user()->priceFormat($invoice->getDue())),
                    'status' => view('invoice.partials.status', compact('invoice'))->render(),
                    'payment_status' => view('invoice.partials.payment_status', compact('invoice'))->render(),
                    'action' => view('invoice.partials.actions', compact('invoice'))->render(),
                ];
            }

            return response()->json([
                'draw' => intval($draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ]);
        }

        // Fallback to normal view
        $type = 'regular';

        return view('invoice.index', compact('customer', 'status', 'paymentstatues', 'type'));
    }

    public function rent_index(Request $request)
    {
        $user = \Auth::user();

        if (!$user->can('approve invoice') && !$user->can('manage invoice')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        // Shared values
        $customer = Customer::where('created_by', $user->creatorId())->pluck('name', 'id')->prepend('Select Customer', '');
        $status = array_filter(Invoice::$statues, fn($val) => trim($val) !== '', ARRAY_FILTER_USE_BOTH);
        $paymentstatues = array_filter(Invoice::$paymentstatues, fn($val) => trim($val) !== '', ARRAY_FILTER_USE_BOTH);

        // Adjust statuses for "manage invoice" (without approve)
        if ($user->can('manage invoice') && !$user->can('approve invoice')) {
            $status = array_filter($status, fn($val) => $val !== 'Send To Approve' && !empty($val));
        }

        // Define base query for rent invoices
        $query = Invoice::where('created_by', $user->creatorId())->where('type', 'rent');

        // DataTables server-side processing
        if ($request->ajax() || $request->wantsJson() || $request->has('draw')) {
            $draw = $request->get('draw');
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 100);

            // Apply filters
            if ($request->filled('customer')) {
                $query->where('customer_id', $request->customer);
            }

            if ($request->filled('issue_date')) {
                $dates = explode(' to ', $request->issue_date);
                $start_date = trim($dates[0]);
                $end_date = $dates[1] ?? $start_date;
                $query->whereBetween('issue_date', [$start_date, $end_date]);
            }

            // IMPORTANT: Use `has` or `isset` instead of `!empty` to allow status = 0
            if ($request->status !== null && $request->status !== '') {
                $query->where('status', $request->status);
            }

            if ($request->paymentstatues !== null && $request->paymentstatues !== '') {
                $query->where('payment_status', $request->paymentstatues);
            }

            if ($user->can('manage invoice') && !$user->can('approve invoice')) {
                $query->where('status', '<>', 1); // remove 'Send to Approve' records
            }

            // Global search
            if (!empty($request->input('search.value'))) {
                $search = $request->input('search.value');
                $query->search($search);
            }

            $totalRecords = Invoice::where('created_by', $user->creatorId())->where('type', 'rent')->count();
            $filteredRecords = $query->count();

            // Ordering - Default to sort by issue_date ascending (oldest first)
            $columns = [
                'invoice_id',
                'customer',
                'issue_date',
                'due_date',
                'due_amount',
                'status',
                'payment_status',
                'action',
            ];
            
            // Check if user explicitly requested a sort column
            $orderColIdx = $request->input('order.0.column');
            $orderCol = null;
            
            // Handle order column index (DataTables sends 0-based index)
            if ($orderColIdx !== null && is_numeric($orderColIdx) && isset($columns[(int)$orderColIdx])) {
                $orderCol = $columns[(int)$orderColIdx];
            }
            
            // Default to issue_date if no explicit sort or if invalid column
            if ($orderCol === null || $orderCol === 'issue_date') {
                $orderDir = ($orderCol === 'issue_date' && $request->input('order.0.dir'))
                    ? $request->input('order.0.dir')
                    : 'asc';
                // Sort by issue_date (oldest first by default) - ensure proper date sorting
                $query->orderBy('invoices.issue_date', $orderDir);
            } else {
                // User sorted by another column - apply that sort, then issue_date as secondary
                $orderDir = $request->input('order.0.dir', 'asc');
                switch ($orderCol) {
                    case 'invoice_id':
                        $query->orderBy('invoices.invoice_id', $orderDir)
                              ->orderBy('invoices.issue_date', 'asc');
                        break;
                    case 'customer':
                        $query->leftJoin('customers', 'invoices.customer_id', '=', 'customers.id')
                            ->orderBy('customers.name', $orderDir)
                            ->orderBy('invoices.issue_date', 'asc')
                            ->select('invoices.*');
                        break;
                    case 'due_date':
                        $query->orderBy('invoices.due_date', $orderDir)
                              ->orderBy('invoices.issue_date', 'asc');
                        break;
                    case 'status':
                        $query->orderBy('invoices.status', $orderDir)
                              ->orderBy('invoices.issue_date', 'asc');
                        break;
                    case 'payment_status':
                        $query->orderBy('invoices.payment_status', $orderDir)
                              ->orderBy('invoices.issue_date', 'asc');
                        break;
                    default:
                        // Fallback to issue_date
                        $query->orderBy('invoices.issue_date', 'asc');
                        break;
                }
            }

            $invoices = $query->skip($start)->take($length)->get();

            $data = [];
            foreach ($invoices as $invoice) {
                $data[] = [
                    'invoice_id' => ($invoice->type == 'rent')
                        ? '<a href="' . route('rentinvoice.show', Crypt::encrypt($invoice->id)) . '" class="btn btn-outline-primary">' . e(Auth::user()->invoiceNumberFormat($invoice->invoice_id)) . '</a>'
                        : '<a href="' . route('invoice.show', Crypt::encrypt($invoice->id)) . '" class="btn btn-outline-primary">' . e(Auth::user()->invoiceNumberFormat($invoice->invoice_id)) . '</a>',
                    'customer' => e(optional(\App\Models\Customer::find($invoice->customer_id))->name ?? 'N/A'),
                    'issue_date' => e(Auth::user()->dateFormat($invoice->issue_date)),
                    'issue_date_raw' => $invoice->issue_date, // Raw date for proper sorting
                    'due_date' => ($invoice->due_date < date('Y-m-d'))
                        ? '<p class="text-danger">' . e(Auth::user()->dateFormat($invoice->due_date)) . '</p>'
                        : e(Auth::user()->dateFormat($invoice->due_date)),
                    'due_date_raw' => $invoice->due_date, // Raw date for proper sorting
                    'due_amount' => e(Auth::user()->priceFormat($invoice->getDue())),
                    'status' => view('invoice.partials.status', compact('invoice'))->render(),
                    'payment_status' => view('invoice.partials.payment_status', compact('invoice'))->render(),
                    'action' => view('invoice.partials.actions', compact('invoice'))->render(),
                ];
            }

            return response()->json([
                'draw' => intval($draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ]);
        }

        // Fallback to normal view
        $type = 'rent';

        return view('invoice.index', compact('customer', 'status', 'paymentstatues', 'type'));
    }

    public function create($type)
    {
        if (\Auth::user()->can('create invoice')) {
            $settings = Utility::settings();
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'invoice')->get();
            $lastInvoiceId = Invoice::withTrashed()->latest()->first();
            // if ($lastInvoiceId != null) {
            //     $invoice_number = \Auth::user()->invoiceNumberFormat($lastInvoiceId->invoice_id);
            // } else {
            $invoice_number = \Auth::user()->invoiceNumberFormat($this->invoiceNumber());
            $invoice_numberNo = $this->invoiceNumber();
            // }
            $customers = Customer::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customers->prepend('Select Customer', '');
            $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');
            $product_services = ProductService::where('created_by', \Auth::user()->creatorId())
                ->whereHas('subProducts', function ($query) {
                    $query->where('flag', '!=', 2)->where('booked', '=', 0);
                })
                ->with(['brand', 'subBrand', 'category'])
                ->get()
                ->map(function ($productService) {
                    $category = $productService->category->name ?? '';
                    $brand = $productService->brand->name ?? '';
                    $subBrand = $productService->subBrand->name ?? '';
                    $productName = $productService->name;
                    $productSku = $productService->sku;

                    return [
                        'id' => $productService->id,
                        'name' => $category . '/' . $brand . '/' . $subBrand . '/' . $productName . '/' . $productSku,
                    ];
                })
                ->pluck('name', 'id');
            $product_services->prepend('Select Product', '');
            $fullTax = Tax::where('created_by', '=', \Auth::user()->creatorId())->get();

            $currency     = Currency::get()->pluck('name', 'id');
            $customFieldsProducts = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'product')->get();
            $users = User::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $users->prepend('Select User', '');

            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');
            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $accounts->prepend('Select Bank', '');
            if (count($product_services) === 1) {
                return back()->with('error', 'Products are not available for invoice creation. Please add products to the system.');
            }
            return view('invoice.create', compact('customers', 'invoice_number', 'product_services', 'category', 'customFields', 'fullTax', 'currency', 'customFieldsProducts', 'type', 'users', 'chartAccounts', 'invoice_numberNo', 'accounts'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function create_rent_invoice($type)
    {
        if (\Auth::user()->can('create invoice')) {
            $settings = Utility::settings();
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'invoice')->get();
            $lastInvoiceId = Invoice::withTrashed()->latest()->first();
            if ($lastInvoiceId != null) {
                $invoice_number = \Auth::user()->invoiceNumberFormat($lastInvoiceId->id);
            } else {
                $invoice_number = \Auth::user()->invoiceNumberFormat($this->invoiceNumber());
            }
            $customers = Customer::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customers->prepend('Select Customer', '');
            $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');
            $product_services = ProductService::where('created_by', \Auth::user()->creatorId())
                ->whereHas('subProducts', function ($query) {
                    $query->where('flag', '!=', 2)->where('booked', '=', 0);
                })
                ->with(['brand', 'subBrand', 'category'])
                ->get()
                ->map(function ($productService) {
                    $category = $productService->category->name ?? '';
                    $brand = $productService->brand->name ?? '';
                    $subBrand = $productService->subBrand->name ?? '';
                    $productName = $productService->name;

                    return [
                        'id' => $productService->id,
                        'name' => $category . '/' . $brand . '/' . $subBrand . '/' . $productName,
                    ];
                })
                ->pluck('name', 'id');
            $product_services->prepend('--', '');
            $tax = Tax::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $fullTax = Tax::where('created_by', '=', \Auth::user()->creatorId())->get();

            $currency     = Currency::get()->pluck('name', 'id');
            $currency->prepend('AED', '');
            $customFieldsProducts = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'product')->get();
            // dd($customFieldsProducts);
            $colors     = Color::get()->pluck('name', 'id');
            $colors->prepend('Select Color', '');
            $allColor = Color::get();

            $countries     = Country::get()->pluck('name', 'id');
            $countries->prepend('Select Country', '');
            $allCountries = Country::get();
            $users     = User::get()->pluck('name', 'id');
            $users->prepend('Select User', '');
            return view('invoice.create', compact('customers', 'invoice_number', 'product_services', 'category', 'customFields', 'tax', 'fullTax', 'currency', 'customFieldsProducts', 'colors', 'allColor', 'countries', 'allCountries', 'type', 'users'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function customer(Request $request)
    {
        $customer = Customer::where('id', '=', $request->id)->first();
        return view('invoice.customer_detail', compact('customer'));
    }

    public function product(Request $request)
    {
        $data['product'] = $product = ProductService::find($request->product_id);
        $data['unit'] = (!empty($product->unit)) ? $product->unit->name : '';
        $data['taxRate'] = $taxRate = !empty($product->tax_id) ? $product->taxRate($product->tax_id) : 0;
        $data['taxes'] = !empty($product->tax_id) ? $product->tax($product->tax_id) : 0;
        $salePrice = $product->sale_price;
        $quantity = 1;
        $taxPrice = ($taxRate / 100) * ($salePrice * $quantity);
        $data['totalAmount'] = ($salePrice * $quantity);

        return json_encode($data);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        if (\Auth::user()->can('create invoice')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'customer_id' => 'required',
                    'issue_date' => 'required',
                    'due_date' => 'required',
                    'category_id' => 'required',
                    'items' => 'required',
                    'tax_id' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }
            try {
                DB::beginTransaction();
                $status = Invoice::$statues;
                $invoice = new Invoice();
                $invoice->invoice_id = $this->invoiceNumber();
                $invoice->customer_id = $request->customer_id;
                $invoice->status = 0;
                $invoice->issue_date = $request->issue_date;
                $invoice->due_date = $request->due_date;
                $invoice->category_id = $request->category_id;
                $invoice->ref_number = $request->ref_number;
                $invoice->type = $request->type;
                $invoice->bank_account_id = $request->bank_account_id;

                // Resolve discount account: use provided one, otherwise find or create "Discounts Allowed"
                if (!empty($request->discount_account_id)) {
                    $invoice->discount_account_id = $request->discount_account_id;
                } else {
                    $discountAccount = ChartOfAccount::where('created_by', \Auth::user()->creatorId())
                        ->where('name', 'Discounts Allowed')
                        ->first();

                    if (!$discountAccount) {
                        // Ensure Costs of Goods Sold type exists
                        $expenseType = ChartOfAccountType::where('created_by', \Auth::user()->creatorId())
                            ->where('name', 'Expenses')
                            ->first();
                        if (!$expenseType) {
                            $expenseType = ChartOfAccountType::create([
                                'name' => 'Costs of Goods Sold',
                                'created_by' => \Auth::user()->creatorId(),
                            ]);
                        }

                        // Ensure Costs of Goods Sold subtype exists
                        $gaSubType = ChartOfAccountSubType::where('type', $expenseType->id)
                            ->where('name', 'General and Administrative expenses')
                            ->first();
                        if (!$gaSubType) {
                            $gaSubType = ChartOfAccountSubType::create([
                                'name' => 'Costs of Goods Sold',
                                'type' => $expenseType->id
                            ]);
                        }

                        // Create the Discounts Allowed account
                        $discountAccount = ChartOfAccount::create([
                            'code' => '5060',
                            'name' => 'Discounts Allowed',
                            'type' => $expenseType->id,
                            'sub_type' => $gaSubType->id,
                            'is_enabled' => 1,
                            'created_by' => \Auth::user()->creatorId(),
                        ]);
                    }

                    $invoice->discount_account_id = $discountAccount->id;
                }
                //            $invoice->discount_apply = isset($request->discount_apply) ? 1 : 0;
                $invoice->created_by = \Auth::user()->creatorId();
                $invoice->salesman_id = !empty($request->salesman_id) ? $request->salesman_id : \Auth::user()->creatorId();
                $invoice->tax_id         = !empty($request->tax_id) ? implode(',', $request->tax_id) : '';
                $invoice->currency_id         = !empty($request->currency_id) ?  $request->currency_id : null;
                $invoice->exchange_rate         = !empty($request->exchange_rate) ?  $request->exchange_rate : 1;
                $invoice->driver_id         = !empty($request->driver_id) ?  $request->driver_id : null;
                $invoice->save();
                CustomField::saveData($invoice, $request->customField);
                if ($request->hasFile('documents')) {
                    $documents = $request->file('documents');
                    foreach ($documents as $document) {
                        $filenameWithExt = $document->getClientOriginalName();
                        $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                        $extension = $document->getClientOriginalExtension();
                        $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                        // $path = $document->storeAs('uploads/document', $fileNameToStore, 'public');
                        $document->move(public_path('documents'), $fileNameToStore);
                        // Save the file path to the database
                        $accountDocument = new AccountingDocument();
                        $accountDocument->document_name = $filenameWithExt;
                        $accountDocument->document_path = 'documents/' . $fileNameToStore;;
                        $accountDocument->invoice_id = $invoice->id;
                        $accountDocument->save();
                    }
                }
                if (!empty($request->itemsAccount)) {
                    // Filter out empty or null values
                    $validItems = array_filter($request->itemsAccount, function ($item) {
                        return !empty($item['chart_account_id']) && !empty($item['amount']);
                    });

                    // Only proceed if there are valid items
                    if (!empty($validItems)) {
                        foreach ($validItems as $item) {
                            // Calculate AED amount from invoice currency
                            $amountAED = $item['amount'];
                            $amountInCurrency = $item['amount'];
                            $currencyRate = 1;

                            if ($invoice->currency_id && $invoice->exchange_rate > 0) {
                                // Convert from invoice currency to AED
                                $amountAED = $item['amount'] * $invoice->exchange_rate;
                                $amountInCurrency = $item['amount'];
                                $currencyRate = $invoice->exchange_rate;
                            }

                            InvoiceExpense::create([
                                'invoice_id' => $invoice->id,
                                'account_id' => $item['chart_account_id'],
                                'amount' => $amountAED, // Store in AED
                                'currency_id' => $invoice->currency_id, // Use invoice currency
                                'currency_rate' => $currencyRate,
                                'amount_in_currency' => $amountInCurrency,
                                'description' => $item['description'] ?? null,
                                'created_by' => auth()->id(),
                            ]);
                        }
                    }
                }
                $products = $request->items;
                $errorArray = [];
                for ($i = 0; $i < count($products); $i++) {
                    $product = ProductService::find($products[$i]['item']);
                    // Add the retrieved sub-products to the array
                    $selectType = $products[$i]['selectType'];
                    if ($selectType == "manual" && $product->type == 'product') {
                        $selectedSubProducts = explode(',', $products[$i]['selected']);
                        foreach ($selectedSubProducts as $item) {
                            if ($item === "on") {
                                continue;
                            }
                            $subProduct  = SubProduct::find($item);
                            $subProduct->update([
                                'invoice_id' => $invoice->id,
                                'booked' => 1,
                                'quantity' =>  $subProduct->quantity - 1,
                            ]);
                            $invoiceProduct = new InvoiceProduct();
                            $invoiceProduct->invoice_id = $invoice->id;
                            $invoiceProduct->product_id = $products[$i]['item'];
                            $invoiceProduct->sub_product_id = $subProduct->id;
                            $invoiceProduct->quantity = 1;
                            $invoiceProduct->tax = $products[$i]['tax'];


                            $basePrice = $products[$i]['price'];
                            $baseDiscount = isset($products[$i]['discount']) ? $products[$i]['discount'] : 0;

                            // Multiply price by number of days if type is 'rent'
                            if ($invoice->type == 'rent') {
                                $days = $invoice->getDaysDifferenceAttribute();
                                $basePrice *= $days;
                            }

                            // Save original values before applying exchange rate
                            $invoiceProduct->exchange_price = $basePrice;
                            $invoiceProduct->exchange_discount = $baseDiscount;

                            if (!empty($request->currency_id)) {
                                $curr = Currency::find($request->currency_id);

                                $exchangeRate = !empty($request->exchange_rate)
                                    ? $request->exchange_rate
                                    : ($curr ? $curr->exchange_rate : 1); // fallback to 1 if currency not found

                                $invoiceProduct->price = $basePrice * $exchangeRate;
                                $invoiceProduct->discount = $baseDiscount * $exchangeRate;
                            } else {
                                $invoiceProduct->price = $basePrice;
                                $invoiceProduct->discount = $baseDiscount;
                            }
                            $invoiceProduct->description = $products[$i]['description'];
                            $invoiceProduct->save();

                            $target_document_type = "";
                            $target_document = 0;
                            if($subProduct->asn_id){
                                $target_document_type = "ASN";
                                $target_document = $subProduct->asn_id;
                            }else{
                                $target_document_type = "BILL";
                                $target_document = $subProduct->bill_id;
                            }
                            MasterlistLeadger::addBooked($subProduct->product_id,$subProduct->warehouse_id,1,'INVOICE',$invoice->id,$invoice->created_by,$target_document_type,$target_document);
                            
                        }
                    } elseif ($selectType == "auto" && $product->type == 'product') {
                        // dd($customFields);
                        $product = ProductService::find($products[$i]['item']);
                        if ($product->category->type === "Qty product") {
                            $selectedData = json_decode($products[$i]['selected'], true);

                            foreach ($selectedData as $selection) {
                                $combination = $selection['combination']; // ["size" => "33", "gender" => "female"]
                                $qty = $selection['quantity'];
                                $warehouseId = $selection['warehouse_id'] ?? null;

                                // Load sub-products (FIFO) with custom field values
                                $subProductsQuery = SubProduct::where('product_id', $product->id)
                                    ->where('flag', '!=', 2)
                                    ->where('booked', 0) // only free ones
                                    ->where('quantity', '>', 0)
                                    ->with(['customFieldValues.customField'])
                                    ->orderBy('created_at', 'asc');

                                if ($warehouseId && $warehouseId !== 'undefined') {
                                    $subProductsQuery->where('warehouse_id', $warehouseId);
                                }

                                $subProducts = $subProductsQuery->get();

                                // Filter by combination (only match non-null/non-empty fields)
                                $matchedSubProducts = $subProducts->filter(function ($subProduct) use ($combination) {
                                    foreach ($combination as $fieldName => $fieldValue) {
                                        // Skip null or empty values in combination - they don't need to match
                                        if (empty($fieldValue) || $fieldValue === null || $fieldValue === 'N/A' || $fieldValue === 'n/a') {
                                            continue;
                                        }

                                        $fieldValueDb = $subProduct->customFieldValues
                                            ->firstWhere('customField.name', $fieldName)
                                            ->value ?? null;

                                        // Only check if database value doesn't match the non-empty combination value
                                        if ($fieldValueDb != $fieldValue) {
                                            return false;
                                        }
                                    }
                                    return true;
                                });

                                $totalAvailable = $matchedSubProducts->sum('quantity');

                                // Not enough stock
                                if ($totalAvailable < $qty) {
                                    $errorArray[] = 'The requested quantity for ' . $product->name .
                                        ' (combination: ' . json_encode($combination) . ') is unavailable. ' .
                                        'Available: ' . $totalAvailable;
                                    continue;
                                }

                                // Allocate FIFO
                                foreach ($matchedSubProducts as $subProductItem) {
                                    if ($qty <= 0) break;

                                    $availableQuantity = $subProductItem->quantity;
                                    $quantityToDeduct = min($availableQuantity, $qty);

                                    // Update sub-product stock
                                    $subProductItem->update([
                                        'invoice_id' => $invoice->id,
                                        'booked' => ($availableQuantity - $quantityToDeduct) <= 0 ? 1 : 0, // only fully book if finished
                                        'quantity' => $availableQuantity - $quantityToDeduct,
                                    ]);

                                    // Create invoice line
                                    $invoiceProduct = new InvoiceProduct();
                                    $invoiceProduct->invoice_id = $invoice->id;
                                    $invoiceProduct->product_id = $products[$i]['item'];
                                    $invoiceProduct->sub_product_id = $subProductItem->id;
                                    $invoiceProduct->quantity = $quantityToDeduct;
                                    $invoiceProduct->tax = $products[$i]['tax'];

                                    $basePrice = $products[$i]['price'];
                                    $baseDiscount = $products[$i]['discount'] ?? 0;

                                    if ($invoice->type == 'rent') {
                                        $days = $invoice->getDaysDifferenceAttribute();
                                        $basePrice *= $days;
                                    }

                                    $invoiceProduct->exchange_price = $basePrice;
                                    $invoiceProduct->exchange_discount = $baseDiscount;

                                    if (!empty($request->currency_id)) {
                                        $curr = Currency::find($request->currency_id);
                                        $exchangeRate = $request->exchange_rate ?? ($curr->exchange_rate ?? 1);

                                        $invoiceProduct->price = $basePrice * $exchangeRate;
                                        $invoiceProduct->discount = $baseDiscount * $exchangeRate;
                                    } else {
                                        $invoiceProduct->price = $basePrice;
                                        $invoiceProduct->discount = $baseDiscount;
                                    }

                                    $invoiceProduct->description = $products[$i]['description'];
                                    $invoiceProduct->save();
                                    
                                    $target_document_type = "";
                                    $target_document = 0;
                                    if($subProductItem->asn_id){
                                        $target_document_type = "ASN";
                                        $target_document = $subProductItem->asn_id;
                                    }else{
                                        $target_document_type = "BILL";
                                        $target_document = $subProductItem->bill_id;
                                    }
                                    MasterlistLeadger::addBooked($subProductItem->product_id,$subProductItem->warehouse_id,$quantityToDeduct,'INVOICE',$invoice->id,$invoice->created_by,$target_document_type,$target_document);
                                    
                            

                                    // Deduct qty still needed
                                    $qty -= $quantityToDeduct;
                                }
                            }
                        } else {
                            $selectedData = json_decode($products[$i]['selected'], true) ?: [];

                            foreach ($selectedData as $selection) {
                                $combination = $selection['combination'] ?? [];         // e.g. ["size"=>"33","gender"=>"female"]
                                $need        = max(1, (int)($selection['quantity'] ?? 1)); // number of distinct items to allocate
                                $warehouseId = $selection['warehouse_id'] ?? null;

                                // Candidates: available, unbooked sub-products of the parent product (FIFO)
                                $candidates = SubProduct::query()
                                    ->where('product_id', $products[$i]['item'])
                                    ->where('flag', '!=', 2)
                                    ->where('booked', 0)
                                    ->when($warehouseId && $warehouseId !== 'undefined', function ($q) use ($warehouseId) {
                                        $q->where('warehouse_id', $warehouseId);
                                    })
                                    ->with(['customFieldValues.customField' => function ($q) {
                                        // If you have a 'module' column on CustomField and want to ensure only subProduct fields:
                                        // $q->where('module', 'subProduct');
                                    }])
                                    ->orderBy('created_at', 'asc') // FIFO
                                    ->get();

                                // Match by custom-field combination (only match non-null/non-empty fields)
                                $matched = $candidates->filter(function ($sp) use ($combination) {
                                    foreach ($combination as $fieldName => $fieldValue) {
                                        // Skip null or empty values in combination - they don't need to match
                                        if (empty($fieldValue) || $fieldValue === null || $fieldValue === 'N/A' || $fieldValue === 'n/a') {
                                            continue;
                                        }

                                        $val = optional(
                                            $sp->customFieldValues->firstWhere(function ($cfv) use ($fieldName) {
                                                return optional($cfv->customField)->name === $fieldName;
                                            })
                                        )->value;

                                        // Only check if database value doesn't match the non-empty combination value
                                        if ((string)$val !== (string)$fieldValue) {
                                            return false;
                                        }
                                    }
                                    return true;
                                })->values();

                                // Availability check
                                if ($matched->count() < $need) {
                                    $errorArray[] = 'Requested ' . $need . ' of "' . $product['name'] .
                                        '" for combination ' . json_encode($combination) .
                                        ' but only ' . $matched->count() . ' available.';
                                    continue;
                                }

                                // Pick first N (FIFO) and book each as a distinct item
                                $picked = $matched->take($need);

                                foreach ($picked as $sp) {
                                    // Update sub-product
                                    $sp->update([
                                        'invoice_id' => $invoice->id,
                                        'booked'     => 1,
                                        // For item-wise, quantity is typically 1. If selling (regular), drop to 0; for rent, keep quantity as-is.
                                        'quantity'   => max(0, (int)$sp->quantity - 1),
                                    ]);

                                    // Create invoice product line (qty = 1 per sub-product)
                                    $invoiceProduct = new InvoiceProduct();
                                    $invoiceProduct->invoice_id      = $invoice->id;
                                    $invoiceProduct->product_id      = $products[$i]['item'];
                                    $invoiceProduct->sub_product_id  = $sp->id;
                                    $invoiceProduct->quantity        = 1;
                                    $invoiceProduct->tax             = $products[$i]['tax'];

                                    $basePrice    = (float)$products[$i]['price'];
                                    $baseDiscount = (float)($products[$i]['discount'] ?? 0);

                                    if ($invoice->type === 'rent') {
                                        $days = $invoice->getDaysDifferenceAttribute();
                                        $basePrice *= $days;
                                    }

                                    // Store original values
                                    $invoiceProduct->exchange_price    = $basePrice;
                                    $invoiceProduct->exchange_discount = $baseDiscount;

                                    // Apply exchange rate if provided
                                    if (!empty($request->currency_id)) {
                                        $curr        = Currency::find($request->currency_id);
                                        $exchangeRate = $request->exchange_rate ?? ($curr->exchange_rate ?? 1);
                                        $invoiceProduct->price    = $basePrice * $exchangeRate;
                                        $invoiceProduct->discount = $baseDiscount * $exchangeRate;
                                    } else {
                                        $invoiceProduct->price    = $basePrice;
                                        $invoiceProduct->discount = $baseDiscount;
                                    }

                                    $invoiceProduct->description = $products[$i]['description'];
                                    $invoiceProduct->save();
                                    
                                    $target_document_type = "";
                                    $target_document = 0;
                                    if($sp->asn_id){
                                        $target_document_type = "ASN";
                                        $target_document = $sp->asn_id;
                                    }else{
                                        $target_document_type = "BILL";
                                        $target_document = $sp->bill_id;
                                    }
                                    MasterlistLeadger::addBooked($sp->product_id,$sp->warehouse_id,1,'INVOICE',$invoice->id,$invoice->created_by,$target_document_type,$target_document);

                            
                                }
                            }
                        }
                    } elseif ($product->type == 'service') {
                        $subProduct = SubProduct::where('product_id', $products[$i]['item'])->first();
                        $invoiceProduct = new InvoiceProduct();
                        $invoiceProduct->invoice_id = $invoice->id;
                        $invoiceProduct->product_id = $products[$i]['item'];
                        $invoiceProduct->sub_product_id = $subProduct->id;
                        $invoiceProduct->quantity = 1;
                        $invoiceProduct->tax = $products[$i]['tax'];
                        $invoiceProduct->discount = $products[$i]['discount'];
                        if (!empty($request->currency_id)) {
                            $curr = Currency::find($request->currency_id);
                            $invoiceProduct->price = !empty($request->exchange_rate) ? $products[$i]['price'] * $request->exchange_rate : $products[$i]['price'] * $curr->exchange_rate;
                        } else {
                            $invoiceProduct->price = $products[$i]['price'];
                        }
                        $invoiceProduct->description = $products[$i]['description'];
                        $invoiceProduct->save();
                                                            
                        $target_document_type = "";
                        $target_document = 0;
                        if($subProduct->asn_id){
                            $target_document_type = "ASN";
                            $target_document = $subProduct->asn_id;
                        }else{
                            $target_document_type = "BILL";
                            $target_document = $subProduct->bill_id;
                        }
                        MasterlistLeadger::addBooked($subProduct->product_id,$subProduct->warehouse_id,1,'INVOICE',$invoice->id,$invoice->created_by,$target_document_type,$target_document);
                            
                    }



                    //inventory management (Quantity)
                    // Utility::total_quantity('minus', $invoiceProduct->quantity, $invoiceProduct->product_id);

                    //For Notification
                    $setting = Utility::settings(\Auth::user()->creatorId());
                    $customer = Customer::find($request->customer_id);
                    $invoiceNotificationArr = [
                        'invoice_number' => \Auth::user()->invoiceNumberFormat($invoice->invoice_id),
                        'user_name' => \Auth::user()->name,
                        'invoice_issue_date' => $invoice->issue_date,
                        'invoice_due_date' => $invoice->due_date,
                        'customer_name' => $customer->name,
                    ];
                    //Slack Notification
                    if (isset($setting['invoice_notification']) && $setting['invoice_notification'] == 1) {
                        Utility::send_slack_msg('new_invoice', $invoiceNotificationArr);
                    }
                    //Telegram Notification
                    if (isset($setting['telegram_invoice_notification']) && $setting['telegram_invoice_notification'] == 1) {
                        Utility::send_telegram_msg('new_invoice', $invoiceNotificationArr);
                    }
                    //Twilio Notification
                    if (isset($setting['twilio_invoice_notification']) && $setting['twilio_invoice_notification'] == 1) {
                        Utility::send_twilio_msg($customer->contact, 'new_invoice', $invoiceNotificationArr);
                    }
                }

                //Product Stock Report
                $type = 'invoice';
                $type_id = $invoice->id;
                StockReport::where('type', '=', 'invoice')->where('type_id', '=', $invoice->id)->delete();
                // $description = $invoiceProduct->quantity . '  ' . __(' quantity sold in invoice') . ' ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
                // Utility::addProductStock($invoiceProduct->product_id, $invoiceProduct->quantity, $type, $description, $type_id);

                //webhook
                $module = 'New Invoice';
                $webhook = Utility::webhookSetting($module);
                if ($webhook) {
                    $parameter = json_encode($invoice);
                    $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                    if ($status == true) {
                        return redirect()->route('invoice.index', $invoice->id)->with('success', __('Invoice successfully created.'));
                    } else {
                        return redirect()->back()->with('error', __('Webhook call failed.'));
                    }
                }

                // If there are any errors, don't create the invoice - rollback and return error
                if (!empty($errorArray)) {
                    DB::rollBack();
                    return redirect()->back()->with('error', implode(' | ', $errorArray));
                }
                
                DB::commit();
                return redirect()->to(route('invoice.addSubProducts', $invoice->id))
                    ->with('success', __('Invoice successfully created.'));
                // return redirect()->route('invoice.index', $invoice->id)->with('success', __('Invoice successfully created.'));
            } catch (\Exception $e) {
                DB::rollBack();
                // dd($e->getMessage());
                return redirect()->back()->with('error', $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit($ids)
    {
        if (\Auth::user()->can('edit invoice')) {
            $id = Crypt::decrypt($ids);
            $invoice = Invoice::find($id);
            $invoice_number = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
            $customers = Customer::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');
            $product_services = ProductService::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $invoice->customField = CustomField::getData($invoice, 'invoice');
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'invoice')->get();
            $totalTaxPrice = 0;
            $taxType = 'add';
            $taxes = \App\Models\Utility::tax($invoice->tax_id);
            foreach ($taxes as $tax) {
                $taxPrice = Tax::where('id', $tax->id)->first()->rate;
                $totalTaxPrice += $taxPrice;
            }
            $users     = User::get()->pluck('name', 'id');
            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $currency = Currency::get()->pluck('name', 'id');
            $currency->prepend('AED', '');
            $currency_symbol = $invoice->currency ? $invoice->currency->symbol : \Auth::user()->currencySymbol();
            $fullTax = Tax::where('created_by', \Auth::user()->creatorId())->get();
            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');
            return view('invoice.edit', compact('customers', 'product_services', 'invoice', 'invoice_number', 'category', 'customFields', 'totalTaxPrice', 'users', 'taxType', 'accounts', 'currency', 'fullTax', 'currency_symbol', 'chartAccounts'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, Invoice $invoice)
    {

        if (\Auth::user()->can('edit invoice')) {
            if ($invoice->created_by == \Auth::user()->creatorId()) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'customer_id' => 'required',
                        'issue_date' => 'required',
                        'due_date' => 'required',
                        'category_id' => 'required',
                        // 'items' => 'required',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->route('invoice.index')->with('error', $messages->first());
                }
                $invoice->customer_id = $request->customer_id;
                $invoice->driver_id = $request->driver_id;
                $invoice->issue_date = $request->issue_date;
                $invoice->due_date = $request->due_date;
                $invoice->ref_number = $request->ref_number;
                $invoice->category_id = $request->category_id;
                $invoice->bank_account_id = $request->bank_account_id;
                $invoice->currency_id = $request->currency_id;
                $invoice->exchange_rate = $request->exchange_rate;
                // tax_id is posted as array from blade (name="tax_id[]")
                $invoice->tax_id = !empty($request->tax_id)
                    ? (is_array($request->tax_id) ? implode(',', $request->tax_id) : $request->tax_id)
                    : '';
                $invoice->discount_account_id = !empty($request->discount_account_id) ? $request->discount_account_id : $invoice->discount_account_id;
                $invoice->salesman_id = !empty($request->salesman_id) ? $request->salesman_id : \Auth::user()->creatorId();
                $invoice->save();

                Utility::starting_number($invoice->invoice_id + 1, 'invoice');
                CustomField::saveData($invoice, $request->customField);
                if ($request->hasFile('documents')) {
                    $documents = $request->file('documents');
                    foreach ($documents as $document) {
                        $filenameWithExt = $document->getClientOriginalName();
                        $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                        $extension = $document->getClientOriginalExtension();
                        $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                        // $path = $document->storeAs('uploads/document', $fileNameToStore, 'public');
                        $document->move(public_path('documents'), $fileNameToStore);
                        // Save the file path to the database
                        $accountDocument = new AccountingDocument();
                        $accountDocument->document_name = $filenameWithExt;
                        $accountDocument->document_path = 'documents/' . $fileNameToStore;;
                        $accountDocument->invoice_id = $invoice->id;
                        $accountDocument->save();
                    }
                }
                // TransactionLines::where('reference_id',$invoice->id)->where('reference','Invoice')->delete();

                // $invoice_products = InvoiceProduct::where('invoice_id', $invoice->id)->get();
                // foreach ($invoice_products as $invoice_product) {
                //     $product = ProductService::find($invoice_product->product_id);
                //     $totalTaxPrice = 0;
                //     $taxes = \App\Models\Utility::tax($invoice->tax_id);
                //     foreach ($taxes as $tax) {
                //         $taxPrice = \App\Models\Utility::taxRate($tax->rate, $invoice_product->price, $invoice_product->quantity, $invoice_product->discount);
                //         $totalTaxPrice += $taxPrice;
                //     }
                //     $itemAmount = ($invoice_product->price * $invoice_product->quantity) - ($invoice_product->discount) + $totalTaxPrice;

                //     $data = [
                //         'account_id' => $invoice->category->saleAccount->id,
                //         'transaction_type' => 'Credit',
                //         'transaction_amount' => $itemAmount,
                //         'reference' => 'Invoice',
                //         'reference_id' => $invoice->id,
                //         'reference_sub_id' => $product->id,
                //         'date' => $invoice->issue_date,
                //     ];
                //     // Utility::addTransactionLines($data);
                // }

                // return redirect()->route('invoice.index')->with('success', __('Invoice successfully updated.'));
                // return redirect()->route('invoice.addSubProducts', $invoice->id)->with('success', __('Invoice successfully updated.'));
                return redirect()->route('invoice.show', ['invoice' => Crypt::encrypt($invoice->id)])->with('success', __('Invoice successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function invoiceNumber()
    {
        $latest = Invoice::where('created_by', '=', \Auth::user()->creatorId())->withTrashed()->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->invoice_id + 1;
    }

    public function show($ids)
    {

        if (\Auth::user()->can('show invoice')) {
            try {
                $id = Crypt::decrypt($ids);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Invoice Not Found.'));
            }
            $id = Crypt::decrypt($ids);
            $invoice = Invoice::with(['creditNote.currency', 'payments.bankAccount', 'payments.currency', 'refunds.currency', 'refunds.bankAccount', 'expenses.currency'])->findOrFail($id);
            $isSent = false;
            $count = 0;
            $taxType = $invoice->tax_id != null ? 'add' : '-';
            if (!empty($invoice->created_by) == \Auth::user()->creatorId()) {
                $invoicePayment = InvoicePayment::where('invoice_id', $invoice->id)->first();

                $customer = $invoice->customer;
                $driver = $invoice->driver;
                $allItems = $invoice->items()->with('product.unit')->get();
                $iteams = $invoice->items()
                    ->with('product.unit')
                    ->orderBy('id')
                    ->paginate(25)
                    ->withQueryString();
                $user = \Auth::user();

                // start for storage limit note
                $invoice_user = User::find($invoice->created_by);
                $user_plan = Plan::getPlan($invoice_user->plan);
                // end for storage limit note

                $invoice->customField = CustomField::getData($invoice, 'invoice');
                $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'invoice')->get();
                $invoiceSubProducts = $this->getInvoiceSubProductsFromItems((int)$invoice->id);
                foreach ($allItems as $x) {
                    $sub = $invoiceSubProducts->get($x->sub_product_id);
                    if ($sub && $sub->flag == 1) {
                        $count += 1;
                    }
                }
                if ($count === count($allItems)) {
                    $isSent = true;
                }
                $statusChangesApprove = $invoice->statusChanges()->where('status', 2)->first();
                $statusChangesSend = $invoice->statusChanges()->where('status', 4)->first();
                $statusChangesReceived = $invoice->statusChanges()->where('status', 6)->first();
                $statusChangesSendToApprove = $invoice->statusChanges()->where('status', 1)->first();
                $expenses = $invoice->expenses;
                $refundTotal = $invoice->currency_id != null ? $invoice->invoiceTotalRefundInInvoiceCurrency() : $invoice->invoiceTotalRefund();
                // dd($expenses);
                return view('invoice.view', compact('invoice', 'customer', 'iteams', 'invoicePayment', 'customFields', 'user', 'invoice_user', 'user_plan', 'isSent', 'statusChangesApprove', 'statusChangesSend', 'statusChangesReceived', 'statusChangesSendToApprove', 'taxType', 'expenses', 'driver', 'refundTotal'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show2($id)
    {

        if (\Auth::user()->can('show invoice')) {
            try {
                $id = Crypt::decrypt($ids);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Invoice Not Found.'));
            }
            $id = Crypt::decrypt($ids);
            $invoice = Invoice::with(['creditNote.currency', 'payments.bankAccount', 'payments.currency', 'refunds.currency', 'refunds.bankAccount', 'items.product.unit', 'expenses.currency'])->findOrFail($id);
            $isSent = false;
            $count = 0;
            $taxType = $invoice->tax_id != null ? 'add' : '-';
            if (!empty($invoice->created_by) == \Auth::user()->creatorId()) {
                $invoicePayment = InvoicePayment::where('invoice_id', $invoice->id)->first();

                $customer = $invoice->customer;
                $driver = $invoice->driver;
                $iteams = $invoice->items;
                $user = \Auth::user();

                // start for storage limit note
                $invoice_user = User::find($invoice->created_by);
                $user_plan = Plan::getPlan($invoice_user->plan);
                // end for storage limit note

                $invoice->customField = CustomField::getData($invoice, 'invoice');
                $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'invoice')->get();
                $invoiceSubProducts = $this->getInvoiceSubProductsFromItems((int)$invoice->id);
                foreach ($iteams as $x) {
                    $sub = $invoiceSubProducts->get($x->sub_product_id);
                    if ($sub && $sub->flag == 1) {
                        $count += 1;
                    }
                }
                if ($count === count($iteams)) {
                    $isSent = true;
                }
                $statusChangesApprove = $invoice->statusChanges()->where('status', 2)->first();
                $statusChangesSend = $invoice->statusChanges()->where('status', 4)->first();
                $statusChangesReceived = $invoice->statusChanges()->where('status', 6)->first();
                $statusChangesSendToApprove = $invoice->statusChanges()->where('status', 1)->first();
                $expenses = $invoice->expenses;
                $refundTotal = $invoice->currency_id != null ? $invoice->invoiceTotalRefundInInvoiceCurrency() : $invoice->invoiceTotalRefund();
                // dd($expenses);
                return view('invoice.view', compact('invoice', 'customer', 'iteams', 'invoicePayment', 'customFields', 'user', 'invoice_user', 'user_plan', 'isSent', 'statusChangesApprove', 'statusChangesSend', 'statusChangesReceived', 'statusChangesSendToApprove', 'taxType', 'expenses', 'driver', 'refundTotal'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(Request $request, $id)
    {
        if (!\Auth::user()->can('delete invoice')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $invoice = Invoice::find($id);
        if (!$invoice || $invoice->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Invoice not found or permission denied.'));
        }

        try {
            
            $so = SaleOrder::where('invoice_id', $invoice->id)->first();
            $deleteDate = Carbon::parse($request->delete_date);
            $invoiceDate = Carbon::parse($invoice->issue_date);
            $invoiceSendDate = $invoice->send_date ? Carbon::parse($invoice->send_date) : null;

            if ($invoiceSendDate && ($invoice->status === 4 || $invoice->status === 6) && $deleteDate->lt($invoiceSendDate)) {
                return redirect()->back()->with('error', 'Delete date must be greater than or equal to the send date.');
            } elseif ($deleteDate->lt($invoiceDate)) {
                return redirect()->back()->with('error', 'Delete date must be greater than or equal to the invoice date.');
            }

            DB::beginTransaction();
            $dateToDelete = $deleteDate;

            // Get a new unique vid
            $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
            $newVid = $latestVoucher ? $latestVoucher->vid + 1 : 1;

            if (GeneralLedger::where('vid', $newVid)->where('created_by', \Auth::user()->creatorId())->exists()) {
                return redirect()->back()->with('error', __("Something went wrong, please try again."));
            }
            $customer = Customer::find($invoice->customer_id);
            // Revert sub-product quantities and reset
            $invoiceProducts = InvoiceProduct::where('invoice_id', $invoice->id)->get();
            $invoiceSubProducts = $this->getInvoiceSubProductsFromItems((int)$invoice->id);
            foreach ($invoiceProducts as $invoiceProduct) {
                $subProduct = $invoiceSubProducts->get($invoiceProduct->sub_product_id);
                if ($subProduct) {
                    $subProduct->update([
                        'invoice_id' => null,
                        'booked' => 0,
                        'quantity' => $subProduct->quantity + $invoiceProduct->quantity,
                    ]);
                }
                $product = ProductService::find($invoiceProduct->product_id);
                if (!$product) continue;

                $subProduct = $invoiceSubProducts->get($invoiceProduct->sub_product_id);

                if ($subProduct && $subProduct->asn_id) {
                    $ledgerCreatedBy = $invoice->created_by ?? \Auth::user()->creatorId();
                    if ($so) {
                        MasterlistLeadger::returnBookedToFree(
                            $product->id,
                            $subProduct->warehouse_id,
                            (float) $invoiceProduct->quantity,
                            'SO',
                            $so->id,
                            'ASN',
                            $subProduct->asn_id,
                            $ledgerCreatedBy
                        );
                    } else {
                        MasterlistLeadger::returnBookedToFree(
                            $product->id,
                            $subProduct->warehouse_id,
                            (float) $invoiceProduct->quantity,
                            'INVOICE',
                            $invoice->id,
                            'ASN',
                            $subProduct->asn_id,
                            $ledgerCreatedBy
                        );
                    }
                }

                $category = $product->category;

                $itemAmount = ($invoiceProduct->price * $invoiceProduct->quantity);
                $itemAmount_purchase = 0;
                $totalTaxPrice = 0;

                if ($invoice->status === 4 || $invoice->status === 6) {
                    // Sale Account (Debit)
                    $saleAccount = $category->sale_account_id;
                    GeneralLedger::create([
                        'vid' => $newVid,
                        'account' => $saleAccount,
                        'type' => 'Invoice delete ' . \Auth::user()->invoiceNumberFormat($invoice->id),
                        'ref_number' => 'Invoice delete ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id),
                        'debit' => $itemAmount,
                        'credit' => 0,
                        'ref_id' => $invoice->id,
                        'user_id' => 0,
                        'created_by' => \Auth::user()->creatorId(),
                        'send_date' => $dateToDelete,
                        'reference' => 'Delete Invoice',
                    ]);
                    if ($invoiceProduct->discount > 0) {
                        $discountAccount = $invoice->discount_account_id ? $invoice->discount_account_id : ChartOfAccount::where('created_by', \Auth::user()->creatorId())->where('name', '=', 'Discounts Allowed')->first()->id;
                        GeneralLedger::create([
                            'vid' => $newVid,
                            'account' => $discountAccount,
                            'type' => 'Invoice delete ' . \Auth::user()->invoiceNumberFormat($invoice->id),
                            'ref_number' => 'Invoice delete ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id),
                            'debit' => 0,
                            'credit' => $invoiceProduct->discount,
                            'ref_id' => $invoice->id,
                            'user_id' => 0,
                            'created_by' => \Auth::user()->creatorId(),
                            'send_date' => $dateToDelete,
                            'reference' => 'Delete Invoice',
                        ]);
                    }
                    // Tax Account (Debit)
                    if ($invoice->tax_id) {
                        $taxes = Utility::tax($invoice->tax_id);
                        foreach ($taxes as $tax) {
                            $taxPrice = ($itemAmount - $invoiceProduct->discount) * ($tax->rate / 100);
                            $totalTaxPrice += $taxPrice;
                        }
                        $taxAccount = Tax::find($invoice->tax_id)?->chart_account_id;
                        if ($taxAccount) {
                            GeneralLedger::create([
                                'vid' => $newVid,
                                'account' => $taxAccount,
                                'type' => 'Invoice delete ' . \Auth::user()->invoiceNumberFormat($invoice->id),
                                'ref_number' => 'Invoice delete ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id),
                                'debit' => $totalTaxPrice,
                                'credit' => 0,
                                'ref_id' => $invoice->id,
                                'user_id' => 0,
                                'created_by' => \Auth::user()->creatorId(),
                                'send_date' => $dateToDelete,
                                'reference' => 'Delete Invoice',
                            ]);
                        }
                    }

                    // Customer Account (Credit)
                    GeneralLedger::create([
                        'vid' => $newVid,
                        'account' => $customer->chart_account_id,
                        'type' => 'Invoice delete ' . \Auth::user()->invoiceNumberFormat($invoice->id),
                        'ref_number' => 'Invoice delete ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id),
                        'debit' => 0,
                        'credit' => ($itemAmount - $invoiceProduct->discount) + $totalTaxPrice,
                        'ref_id' => $invoice->id,
                        'user_id' => $customer->id,
                        'created_by' => \Auth::user()->creatorId(),
                        'balance' => $customer->balance,
                        'send_date' => $dateToDelete,
                        'reference' => 'Delete Invoice',
                    ]);

                    // Calculate average cost first (for Qty product type) before using it in GL entries
                    $avgCost = null;
                    if ($category && $category->type === "Qty product") {
                        // Check cost calculation method
                        $costCalculationMethod = $category->cost_calculation_method ?? 'avg';

                        if ($costCalculationMethod === 'avg') {
                            // Calculate average cost using weighted average formula:
                            // Average Cost = ((Product Parent Qty × Product Avg Cost (or bill price if avg is 0)) + (New Qty × New Price)) ÷ (Product Parent Qty + New Qty)
                            // For deletion: Calculate from remaining quantity after returning sold qty

                            // Get product's current quantity and average cost (before returning sold qty)
                            $returnedQuantity = $invoiceProduct->quantity;
                            $oldQuantity = ($product->quantity ?? 0);
                            // Use product's avg_cost or subproduct purchase_price as fallback
                            $oldAvgCost = ($product->avg_cost > 0) ? $product->avg_cost : ($subProduct ? ($subProduct->purchase_price ?? 0) : 0);

                            // Calculate old total cost
                            $oldTotalCost = $oldQuantity * $oldAvgCost;

                            // Returned item (current invoice product being returned)
                            $returnedQty = $invoiceProduct->quantity;

                            // Find the stock movement record for this invoice to get the avg_cost when it was sold
                            $originalStockMovement = \App\Models\StockMovement::where('invoice_id', $invoice->id)
                                ->where('sub_product_id', $invoiceProduct->sub_product_id)
                                ->where('product_id', $product->id)
                                ->where(function ($query) {
                                    $query->where('activity', 'Sale via Invoice')
                                        ->orWhere('activity', 'SALES');
                                })
                                ->where('qty_out', '>', 0)
                                ->first();

                            // Use avg_cost from stock movement if available, otherwise use product's avg_cost or subproduct purchase_price
                            $returnedPricePerUnit = $originalStockMovement
                                ? $originalStockMovement->avg_cost
                                : (($product->avg_cost > 0) ? $product->avg_cost : ($subProduct ? ($subProduct->purchase_price ?? 0) : 0));

                            // Calculate total cost for returned item (returned qty * returned price per unit)
                            $returnedItemTotalCost = $returnedQty * $returnedPricePerUnit;

                            // Calculate remaining quantity and cost after returning
                            $remainingQuantity = $oldQuantity + $returnedQty;
                            $remainingTotalCost = $oldTotalCost + $returnedItemTotalCost;

                            // Calculate average cost from remaining items
                            if ($remainingQuantity > 0) {
                                $avgCost = $remainingTotalCost / $remainingQuantity;
                            } else {
                                $avgCost = 0;
                            }
                        } else {
                            // Use actual cost (purchase price from subproduct)
                            $avgCost = $subProduct ? ($subProduct->purchase_price ?? 0) : 0;
                        }
                        // Create stock movement record for returning sold quantity
                        $stockMovement = new StockMovement();
                        $stockMovement->product_id = $product->id;
                        $stockMovement->sub_product_id = $invoiceProduct->sub_product_id;
                        $stockMovement->invoice_id = $invoice->id;
                        $stockMovement->bill_id = null;
                        $stockMovement->pos_id = null;
                        $stockMovement->qty_in = $invoiceProduct->quantity; // Return sold qty
                        $stockMovement->qty_out = 0; // No stock out for return
                        $stockMovement->avg_cost = $avgCost;
                        $stockMovement->cost_price = $originalStockMovement->cost_price ?? 0;
                        $stockMovement->activity = 'Return from Invoice';
                        $stockMovement->use_id = $invoice->customer_id; // customer_id for SALES
                        $stockMovement->item = $invoiceProduct->sub_product_id; // sub_product_id
                        $stockMovement->created_by = \Auth::user()->creatorId();
                        $stockMovement->save();


                        if($invoiceProduct->subProduct->asn_id){
                            if($so){
                                MasterlistLeadger::returnBookedToFree($invoiceProduct->product->id,
                                                                    $invoiceProduct->subProduct->warehouse_id,
                                                                    $invoiceProduct->quantity,'SO',
                                                                    $so->id,'ASN',$invoiceProduct->subProduct->asn_id,
                                                                    \Auth::user()->creatorId());
                            }else{
                                MasterlistLeadger::returnBookedToFree($invoiceProduct->product->id,
                                                                    $invoiceProduct->subProduct->warehouse_id,
                                                                    $invoiceProduct->quantity,'INVOICE',
                                                                    $invoice->id,'ASN',$invoiceProduct->subProduct->asn_id,
                                                                    \Auth::user()->creatorId());
                            }
                        }
                        
                        // Update product average cost
                        $product->avg_cost = $avgCost;
                        $product->save();
                    }

                    // If product type is 'product', add cost-related GL entries
                    if ($product->type == 'product' && $invoice->type == 'regular') {
                        // Retrieve the chart account ID for the purchase
                        $purchaseAccountId = \App\Models\ProductServiceCategory::where('id', $product->category_id)->first()->purchase_account_id;

                        // Use calculated avgCost if available (for Qty product), otherwise use fallback
                        $product_cost = ($avgCost !== null) ? $avgCost : (($product->avg_cost > 0) ? $product->avg_cost : $subProduct->purchase_price);
                        $itemAmount_purchase = $product_cost * $invoiceProduct->quantity;

                        // Calculate the sum of direct expenses related to this item's sub_product_id
                        // Only include expenses where chart_account_id matches the purchase_account_id
                        $directExpenseAmount = 0;
                        if ($invoiceProduct->sub_product_id && $purchaseAccountId) {
                            $directExpenseAmount = \App\Models\DirectExpenseItem::where('sub_product_id', $invoiceProduct->sub_product_id)
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
                        if ($invoiceProduct->sub_product_id) {
                            $carAccessoryAmount = \App\Models\CarAccessoryRequestItem::where(function ($query) use ($invoiceProduct) {
                                $query->where('car_id', $invoiceProduct->sub_product_id)
                                    ->orWhere('accessory_id', $invoiceProduct->sub_product_id);
                            })
                                ->whereHas('request', function ($query) {
                                    $query->where('created_by', \Auth::user()->creatorId());
                                })
                                ->sum('sell_price');
                        }

                        // Add direct expense amount and car accessory amount to the purchase amount
                        $itemAmount_purchase += $directExpenseAmount + $carAccessoryAmount;

                        // Retrieve the chart account ID for the expense
                        $expenseAccountId = \App\Models\ProductServiceCategory::where('id', $product->category_id)->first()->expense_account_id;

                        // Purchase Account (Debit) - Reversed from send function
                        GeneralLedger::create([
                            'vid' => $newVid,
                            'account' => $purchaseAccountId,
                            'type' => 'Invoice delete ' . \Auth::user()->invoiceNumberFormat($invoice->id),
                            'ref_number' => 'Invoice delete ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id),
                            'debit' => $itemAmount_purchase,
                            'credit' => 0,
                            'ref_id' => $invoice->id,
                            'user_id' => 0,
                            'created_by' => \Auth::user()->creatorId(),
                            'send_date' => $dateToDelete,
                            'reference' => 'Delete Invoice',
                        ]);

                        // Expense Account (Credit) - Reversed from send function
                        GeneralLedger::create([
                            'vid' => $newVid,
                            'account' => $expenseAccountId,
                            'type' => 'Invoice delete ' . \Auth::user()->invoiceNumberFormat($invoice->id),
                            'ref_number' => 'Invoice delete ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id),
                            'debit' => 0,
                            'credit' => $itemAmount_purchase,
                            'ref_id' => $invoice->id,
                            'user_id' => 0,
                            'created_by' => \Auth::user()->creatorId(),
                            'send_date' => $dateToDelete,
                            'reference' => 'Delete Invoice',
                        ]);
                    }
                }
            }
            $invoice_expenses = InvoiceExpense::where('invoice_id', $invoice->id)->get();
            if ($invoice->status === 4 || $invoice->status === 6) {
                foreach ($invoice_expenses as $expense) {
                    $expenseAmount = $expense->amount;

                    // Retrieve the expense account

                    GeneralLedger::create([
                        'vid' => $newVid,
                        'account' => $expense->account_id,
                        'type' => 'Invoice delete ' . \Auth::user()->invoiceNumberFormat($invoice->id),
                        'ref_number' => 'Invoice delete ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id),
                        'debit' => $expenseAmount,
                        'credit' => 0,
                        'ref_id' => $invoice->id,
                        'user_id' => 0,
                        'created_by' => \Auth::user()->creatorId(),
                        'send_date' => $dateToDelete,
                        'reference' => 'Delete Invoice',
                    ]);

                    GeneralLedger::create([
                        'vid' => $newVid,
                        'account' => $customer->chart_account_id,
                        'type' => 'Invoice delete ' . \Auth::user()->invoiceNumberFormat($invoice->id),
                        'ref_number' => 'Invoice delete ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id),
                        'debit' => 0,
                        'credit' => $expenseAmount,
                        'ref_id' => $invoice->id,
                        'user_id' => $customer->id,
                        'created_by' => \Auth::user()->creatorId(),
                        'send_date' => $dateToDelete,
                        'reference' => 'Delete Invoice',
                    ]);
                }
            }

            // Reverse balance if needed
            if (!in_array($invoice->status, [0, 1, 2]) && $invoice->customer_id != 0) {
                Utility::updateUserBalance('customer', $invoice->customer_id, $invoice->getTotal(), 'credit');
            }

            // Delete related records
            // CustomerPayment::where('invoice_id', $invoice->id)->delete();
            InvoicePayment::where('invoice_id', $invoice->id)->delete();
            InvoiceProduct::where('invoice_id', $invoice->id)->delete();
            InvoiceExpense::where('invoice_id', $invoice->id)->delete();
            TransactionLines::where('reference_id', $invoice->id)->whereIn('reference', ['Invoice', 'Invoice Payment'])->delete();
            CreditNote::where('invoice', $invoice->id)->delete();

            // Finally delete invoice
            $invoice->delete();

            DB::commit();

            return redirect()->route($invoice->type == 'regular' ? 'invoice.index' : 'rentinvoice.index')
                ->with('success', __('Invoice successfully deleted.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function productDestroy(Request $request)
    {

        if (\Auth::user()->can('delete invoice product')) {
            $invoiceProduct = InvoiceProduct::find($request->id);
            $subProducts = $invoiceProduct->subProduct;
            foreach ($subProducts as $subProduct) {
                // Update the invoice_id for each sub product in the array
                $subProduct->update(['invoice_id' => null]);
                $subProduct->update(['booked' => 0]);
                $bill = Bill::where('id', $subProduct->bill_id)->first();
                if ($bill->warehouse_id != null) {
                    Utility::warehouse_quantity('plus', 1, $subProduct->product_id, $bill->warehouse_id);
                }
                // Utility::total_quantity('plus', 1, $subProduct->product_id);
            }
            $invoice = Invoice::find($invoiceProduct->invoice_id);
            $productService = ProductService::find($invoiceProduct->product_id);
            
            
            // Master list ledger save 
            $so = SaleOrder::where('invoice_id', $invoice->id)->first();

            // if($invoiceProduct->subProduct->asn_id){
            //     if($so){
            //         MasterlistLeadger::returnBookedToFree($invoiceProduct->product->id,
            //                                             $invoiceProduct->subProduct->warehouse_id,
            //                                             $invoiceProduct->quantity,'SO',
            //                                             $so->id,'ASN',$invoiceProduct->subProduct->asn_id,
            //                                             \Auth::user()->creatorId());
            //     }else{
            //         MasterlistLeadger::returnBookedToFree($invoiceProduct->product->id,
            //                                             $invoiceProduct->subProduct->warehouse_id,
            //                                             $invoiceProduct->quantity,'INVOICE',
            //                                             $invoice->id,'ASN',$invoiceProduct->subProduct->asn_id,
            //                                             \Auth::user()->creatorId());
            //     }
            // }
            // end of master list ledger

            TransactionLines::where('reference_sub_id', $productService->id)->where('reference', 'Invoice')->delete();

            InvoiceProduct::where('id', '=', $request->id)->delete();

            return redirect()->back()->with('success', __('Invoice product successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function customerInvoice(Request $request)
    {
        if (\Auth::user()->can('manage customer invoice')) {

            $status = Invoice::$statues;
            $query = Invoice::where('customer_id', '=', \Auth::user()->id)->where('status', '!=', '0')->where('created_by', \Auth::user()->creatorId());

            if (!empty($request->issue_date)) {
                $date_range = explode(' - ', $request->issue_date);
                $query->whereBetween('issue_date', $date_range);
            }

            if (!empty($request->status)) {
                $query->where('status', '=', $request->status);
            }
            $invoices = $query->get();

            return view('invoice.index', compact('invoices', 'status'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function customerInvoiceShow($id)
    {

        $invoice = Invoice::with('payments.bankAccount')->find($id);

        $user = User::where('id', $invoice->created_by)->first();
        if ($invoice->created_by == $user->creatorId()) {
            $customer = $invoice->customer;
            $iteams = $invoice->items;

            if ($user->type == 'super admin') {
                return view('invoice.view', compact('invoice', 'customer', 'iteams', 'user'));
            } elseif ($user->type == 'company') {
                return view('invoice.customer_invoice', compact('invoice', 'customer', 'iteams', 'user'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function sent($id, Request $request)
    {
        $date = $request->get('date');

        try {
            $date = \Carbon\Carbon::createFromFormat('Y-m-d', $date)->toDateString();
        } catch (\Exception $e) {
            return back()->with('error', 'Invalid date format.');
        }

        // --- Custom validation for bill send date ---
        $invoice = Invoice::with('items.subProduct.bill')->find($id);
        foreach ($invoice->items as $item) {
            $subProduct = $item->subProduct;
            if ($subProduct && $subProduct->bill) {
                $bill = $subProduct->bill;
                // Check if bill is in 'Sent' status (status == 4)
                if ($bill->status == 4) {
                    // Get the bill's send date from the general ledger (reference 'Bill')
                    $billLedger = \App\Models\GeneralLedger::where('ref_id', $bill->id)
                        ->where('reference', 'Bill')
                        ->orderByDesc('send_date')
                        ->first();
                    $billSendDate = $billLedger ? $billLedger->send_date : $bill->send_date;
                    if ($billSendDate && $date < $billSendDate) {
                        return back()->with('error', 'Invoice send date must be after the bill send date for product #' . $item->product_id . '. Bill send date: ' . $billSendDate);
                    }
                }
            }
        }

        if (\Auth::user()->can('send invoice')) {
            // Send Email
            $setings = Utility::settings();
            if ($setings['customer_invoice_sent'] == 1) {
                try {
                    DB::beginTransaction();
                    // Get the latest 'vid' entry, if any exist
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

                    $invoice = Invoice::where('id', $id)->first();
                    $invoice->send_date = $date;
                    $invoice->status = 4;
                    $invoice->save();
                    $statusChange = new InvoiceStatusChange();
                    $statusChange->invoice_id = $invoice->id;
                    $statusChange->status = 4;
                    $statusChange->payment_status = -1;
                    $statusChange->changed_at = now();
                    $statusChange->save();
                    $customer = Customer::where('id', $invoice->customer_id)->first();
                    $invoice->name = !empty($customer) ? $customer->name : '';
                    $invoice->invoice = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);

                    $invoiceId = Crypt::encrypt($invoice->id);
                    $invoice->url = route('invoice.pdf', $invoiceId);

                    Utility::updateUserBalance('customer', $customer->id, $invoice->getTotal(), 'debit');
                    // Invoice from SO: stock was already booked at SO; only discrepancy was returned on convert. Do not deduct qty again.
                    $isFromSaleOrder = ($invoice->ref_number && strpos(trim($invoice->ref_number), 'SO-') === 0)
                        || \App\Models\SaleOrder::where('invoice_id', $invoice->id)->exists();
                    $invoice_products = InvoiceProduct::where('invoice_id', $invoice->id)->get();
                    $invoiceSubProducts = $this->getInvoiceSubProductsFromItems((int)$invoice->id);
                    foreach ($invoice_products as $invoice_product) {
                        $product = ProductService::find($invoice_product->product_id);
                        if (!$product) {
                            // Skip this item if product not found
                            continue;
                        }
                        if (!$isFromSaleOrder) {
                            $product->quantity -= $invoice_product->quantity;
                            $product->save();
                        }
                        $product_cost = $product->avg_cost;
                        $subproduct = $invoiceSubProducts->get($invoice_product->sub_product_id);
                        if (!$subproduct) {
                            continue;
                        }
                        if ($subproduct->flag == 0) {
                            return redirect()->back()->with('error', __('You cannot send this invoice; it contains one or more items that have not been purchased.'));
                        }

                        // Get category type first
                        $categoryModel = ProductServiceCategory::where('id', $product->category_id)->first();
                        $QtyType = $categoryModel ? $categoryModel->type : null;

                        if ($product->type == 'product') {
                            // Handle booked status based on category type and quantity
                            if ($QtyType === "Qty product") {
                                if ($subproduct->quantity > 0) {
                                    $subproduct->booked = 0; // Keep booked as 0 if there's still quantity
                                } else {
                                    $subproduct->booked = 2; // Mark as sold if quantity is 0
                                }
                            } else {
                                // For non-Qty product types, use original logic
                                $subproduct->booked = $invoice->type === 'rent' ? 0 : 2; // Sold for regular, 0 for rent
                            }
                            $subproduct->invoice_id = $invoice->type === 'rent' ? null : $subproduct->invoice_id;
                            $subproduct->quantity = $invoice->type === 'rent' ? 1 : $subproduct->quantity;
                            $subproduct->save();
                        }
                        $itemAmount_purchase = 0;
                        $totalTaxPrice = 0;
                        $itemAmount  = 0;
                        $taxes = Utility::tax($invoice->tax_id);
                        foreach ($taxes as $tax) {
                            if ($product->type === 'product') {
                                $rate = is_object($tax) ? ($tax->rate ?? null) : (is_array($tax) ? ($tax['rate'] ?? null) : null);
                                if ($rate === null) {
                                    continue;
                                }
                                if ($QtyType === "Qty product") {
                                    $taxPrice = Utility::taxRate($rate, $invoice_product->price, $invoice_product->quantity, $invoice_product->discount);
                                } else {
                                    $taxPrice = Utility::taxRate($rate, $invoice_product->price, 1, $invoice_product->discount);
                                }
                                $totalTaxPrice +=  $taxPrice;
                            }
                        }
                        if ($QtyType === "Qty product") {
                            $avgCost = $product->avg_cost ?? $subproduct->purchase_price ?? 0;
                            if (!$isFromSaleOrder) {
                                // Check cost calculation method
                                $costCalculationMethod = $product->category->cost_calculation_method ?? 'avg';

                                if ($costCalculationMethod === 'avg') {
                                    // Calculate average cost using sale formula:
                                    // Average Cost = ((Last Purchased Sub Product Qty × Last Avg from Parent) - (Sell Qty × Last Avg from Parent)) ÷ (Last Purchased Sub Product Qty - Sell Qty)

                                    // Count purchased subproduct quantities (from sent bills)
                                    $purchasedBillIds = \App\Models\Bill::whereNotIn('status', [0, 1, 2])
                                        ->where('created_by', \Auth::user()->creatorId())
                                        ->pluck('id')
                                        ->toArray();

                                    // Count total quantity from purchased subproducts
                                    $lastPurchasedSubProductQty = \App\Models\SubProduct::where('product_id', $product->id)
                                        ->whereIn('bill_id', $purchasedBillIds)
                                        ->where('flag', '!=', 0)
                                        ->whereNotNull('bill_id')
                                        ->sum('quantity') ?? 0;

                                    // Get last avg from parent product
                                    $lastAvgFromParent = ($product->avg_cost > 0) ? $product->avg_cost : ($subproduct->purchase_price ?? 0);

                                    // Sell quantity
                                    $sellQty = $invoice_product->quantity;

                                    // Calculate average cost using sale formula
                                    // Formula: ((last purchased sub product qty * last avg from parent) - (sell qty * last avg from parent)) / (last purchased sub product qty - sell qty)
                                    $remainingQty = $lastPurchasedSubProductQty - $sellQty;
                                    if ($remainingQty > 0) {
                                        $avgCost = ((($lastPurchasedSubProductQty * $lastAvgFromParent) - ($sellQty * $lastAvgFromParent)) / $remainingQty);
                                    } else {
                                        $avgCost = $lastAvgFromParent;
                                    }
                                } else {
                                    // Use actual cost (purchase price from subproduct)
                                    $avgCost = $subproduct->purchase_price ?? 0;
                                }

                                // Update product average cost
                                $product->avg_cost = $avgCost;
                                $product->save();

                                // Create a new StockMovement for the stock out (sale)
                                $stockMovement = new StockMovement();
                                $stockMovement->product_id = $product->id;
                                $stockMovement->sub_product_id = $invoice_product->sub_product_id;
                                $stockMovement->invoice_id = $invoice->id;
                                $stockMovement->bill_id = null;
                                $stockMovement->pos_id = null;
                                $stockMovement->qty_in = 0; // No stock in for a sale
                                $stockMovement->qty_out = $invoice_product->quantity; // Quantity sold
                                $stockMovement->avg_cost = $avgCost;
                                $stockMovement->cost_price = $product_cost;
                                $stockMovement->activity = 'Sale via Invoice';
                                $stockMovement->use_id = $invoice->customer_id; // customer_id for SALES
                                $stockMovement->item = $invoice_product->sub_product_id; // sub_product_id
                                $stockMovement->created_by = \Auth::user()->creatorId();
                                $stockMovement->save();
                                
                                MasterlistLeadger::addSold(
                                    $product->id,
                                    $invoice_product->subProduct->warehouse_id,
                                    $invoice_product->quantity,
                                    'INVOICE',
                                    $invoice->id,
                                    'INVOICE',
                                    $invoice->id,
                                    \Auth::user()->creatorId()
                                );
                                
                                } else {
                                // From SO: stock already booked at SO; do not record stock out (qty_out=0) to avoid double deduction
                                $stockMovement = new StockMovement();
                                $stockMovement->product_id = $product->id;
                                $stockMovement->sub_product_id = $invoice_product->sub_product_id;
                                $stockMovement->invoice_id = $invoice->id;
                                $stockMovement->bill_id = null;
                                $stockMovement->pos_id = null;
                                $stockMovement->qty_in = 0;
                                $stockMovement->qty_out = 0;
                                $stockMovement->avg_cost = $avgCost;
                                $stockMovement->cost_price = $product_cost;
                                $stockMovement->activity = 'Sale via Invoice (from SO - stock already booked)';
                                $stockMovement->use_id = $invoice->customer_id;
                                $stockMovement->item = $invoice_product->sub_product_id;
                                $stockMovement->created_by = \Auth::user()->creatorId();
                                $stockMovement->save();
                                $so = SaleOrder::where('invoice_id', $invoice->id)->first();

                                // Invoice from SO → reduce booked
                                MasterlistLeadger::addSold(
                                    $product->id,
                                    $invoice_product->subProduct->warehouse_id,
                                    $invoice_product->quantity,
                                    'SO',
                                    $so->id,                 // SO id
                                    'INVOICE',
                                    $invoice->id,
                                    \Auth::user()->creatorId()
                                );
                                    
                                // MasterlistLeadger::addSold($product->id,$invoice_product->subProduct->warehouse_id,$invoice_product->quantity,$invoice->id,'SO',$invoice->id,\Auth::user()->creatorId());
                            }
                            $itemAmount = ($invoice_product->price * $invoice_product->quantity);
                            $itemAmount_purchase =  $avgCost * $invoice_product->quantity;
                        } else {
                            $itemAmount = ($invoice_product->price);
                            $itemAmount_purchase = $subproduct->purchase_price;
                        }

                        // Retrieve the chart account ID for the category
                        $categoryChartAccountId = \App\Models\ProductServiceCategory::where('id', $product->category_id)->first()->sale_account_id;

                        // Add entries to General Ledger

                        // Create a new entry for credit the category account
                        $newEntryCategory = new GeneralLedger();
                        $newEntryCategory->vid = $newVid;
                        $newEntryCategory->account = $categoryChartAccountId;
                        $newEntryCategory->type = \Auth::user()->invoiceNumberFormat($invoice->id);
                        $newEntryCategory->ref_number = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
                        $newEntryCategory->debit = 0;
                        $newEntryCategory->credit = $itemAmount;
                        $newEntryCategory->ref_id = $invoice->id;
                        $newEntryCategory->user_id = 0;
                        $newEntryCategory->sub_product_id = $invoice_product->sub_product_id;
                        $newEntryCategory->created_by = \Auth::user()->creatorId();
                        $newEntryCategory->send_date = $date;
                        $newEntryCategory->reference = 'Invoice';
                        $newEntryCategory->save();

                        if ($invoice_product->discount != 0) {
                            $discountAccount = $invoice->discount_account_id ? $invoice->discount_account_id : ChartOfAccount::where('created_by', \Auth::user()->creatorId())->where('name', '=', 'Discounts Allowed')->first()->id;
                            // Create a new entry for credit the category account
                            $newEntryCategory = new GeneralLedger();
                            $newEntryCategory->vid = $newVid;
                            $newEntryCategory->account = $discountAccount;
                            $newEntryCategory->type = \Auth::user()->invoiceNumberFormat($invoice->id);
                            $newEntryCategory->ref_number = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
                            $newEntryCategory->debit = $invoice_product->discount;
                            $newEntryCategory->credit = 0;
                            $newEntryCategory->ref_id = $invoice->id;
                            $newEntryCategory->user_id = 0;
                            $newEntryCategory->sub_product_id = $invoice_product->sub_product_id;
                            $newEntryCategory->created_by = \Auth::user()->creatorId();
                            $newEntryCategory->send_date = $date;
                            $newEntryCategory->reference = 'Invoice';
                            $newEntryCategory->save();
                        }

                        // Retrieve the chart account ID for the tax
                        $taxChart = \App\Models\Tax::find($invoice->tax_id);
                        $taxChartAccountId = $taxChart ? $taxChart->chart_account_id : null;

                        // Create a new entry credit for the tax account only if tax data is valid
                        if ($taxChartAccountId && $totalTaxPrice > 0) {
                            $newEntryTax = new GeneralLedger();
                            $newEntryTax->vid = $newVid;
                            $newEntryTax->account = $taxChartAccountId;
                            $newEntryTax->type = \Auth::user()->invoiceNumberFormat($invoice->id);
                            $newEntryTax->ref_number = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
                            $newEntryTax->debit = 0;
                            $newEntryTax->credit = $totalTaxPrice;
                            $newEntryTax->ref_id = $invoice->id;
                            $newEntryTax->user_id = 0;
                            $newEntryTax->sub_product_id = $invoice_product->sub_product_id;
                            $newEntryTax->created_by = \Auth::user()->creatorId();
                            $newEntryTax->send_date = $date;
                            $newEntryTax->reference = 'Invoice';
                            $newEntryTax->save();
                        }


                        // Retrieve the chart account ID for the customer
                        $customerChartAccountId = $customer->chart_account_id;

                        // Create a new entry debit for the customer account
                        $newEntryCustomer = new GeneralLedger();
                        $newEntryCustomer->vid = $newVid;
                        $newEntryCustomer->account = $customerChartAccountId;
                        $newEntryCustomer->type = \Auth::user()->invoiceNumberFormat($invoice->id);
                        $newEntryCustomer->ref_number = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
                        $newEntryCustomer->debit =  ($itemAmount - $invoice_product->discount) + $totalTaxPrice;
                        $newEntryCustomer->credit = 0;
                        $newEntryCustomer->ref_id = $invoice->id;
                        $newEntryCustomer->user_id = $customer->id;
                        $newEntryCustomer->sub_product_id = $invoice_product->sub_product_id;
                        $newEntryCustomer->created_by = \Auth::user()->creatorId();
                        $newEntryCustomer->balance = $customer->balance;
                        $newEntryCustomer->send_date = $date;
                        $newEntryCustomer->reference = 'Invoice';
                        $newEntryCustomer->save();


                        ///////////////////////////////////////
                        // Add records if product type is 'product'
                        if ($product->type == 'product' && $invoice->type == "regular") {
                            // Retrieve the chart account ID for the purchase
                            $purchaseAccountId = \App\Models\ProductServiceCategory::where('id', $product->category_id)->first()->purchase_account_id;

                            // Calculate the sum of direct expenses related to this item's sub_product_id
                            // Only include expenses where chart_account_id matches the purchase_account_id
                            $directExpenseAmount = 0;
                            if ($invoice_product->sub_product_id && $purchaseAccountId) {
                                $directExpenseAmount = \App\Models\DirectExpenseItem::where('sub_product_id', $invoice_product->sub_product_id)
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
                            if ($invoice_product->sub_product_id) {
                                $carAccessoryAmount = \App\Models\CarAccessoryRequestItem::where(function ($query) use ($invoice_product) {
                                    $query->where('car_id', $invoice_product->sub_product_id)
                                        ->orWhere('accessory_id', $invoice_product->sub_product_id);
                                })
                                    ->whereHas('request', function ($query) {
                                        $query->where('created_by', \Auth::user()->creatorId());
                                    })
                                    ->sum('sell_price');
                            }

                            // Add direct expense amount and car accessory amount to the purchase amount
                            $itemAmount_purchase += $directExpenseAmount + $carAccessoryAmount;

                            // Create a new entry for the purchase account (credit)
                            $newEntryCredit = new GeneralLedger();
                            $newEntryCredit->vid = $newVid;
                            $newEntryCredit->account = $purchaseAccountId;
                            $newEntryCredit->type = \Auth::user()->invoiceNumberFormat($invoice->id);
                            $newEntryCredit->ref_number = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
                            $newEntryCredit->debit = 0;
                            $newEntryCredit->credit = $itemAmount_purchase;
                            $newEntryCredit->ref_id = $invoice->id;
                            $newEntryCredit->user_id = 0;
                            $newEntryCredit->sub_product_id = $invoice_product->sub_product_id;
                            $newEntryCredit->created_by = \Auth::user()->creatorId();
                            $newEntryCredit->send_date = $date;
                            $newEntryCredit->reference = 'Invoice';
                            $newEntryCredit->save();

                            // Retrieve the chart account ID for the expense
                            $expenseAccountId = \App\Models\ProductServiceCategory::where('id', $product->category_id)->first()->expense_account_id;

                            // Create a new entry for the expense account (debit)
                            $newEntryDebit = new GeneralLedger();
                            $newEntryDebit->vid = $newVid;
                            $newEntryDebit->account = $expenseAccountId;
                            $newEntryDebit->type = \Auth::user()->invoiceNumberFormat($invoice->id);
                            $newEntryDebit->ref_number = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
                            $newEntryDebit->debit = $itemAmount_purchase;
                            $newEntryDebit->credit = 0;
                            $newEntryDebit->ref_id = $invoice->id;
                            $newEntryDebit->user_id = 0;
                            $newEntryDebit->sub_product_id = $invoice_product->sub_product_id;
                            $newEntryDebit->created_by = \Auth::user()->creatorId();
                            $newEntryDebit->send_date = $date;
                            $newEntryDebit->reference = 'Invoice';
                            $newEntryDebit->save();
                        }
                    }
                    // -------------------- Handle Expenses --------------------
                    $invoice_expenses = InvoiceExpense::where('invoice_id', $invoice->id)->get();
                    foreach ($invoice_expenses as $expense) {
                        $expenseAmount = $expense->amount;

                        // Retrieve the expense account


                        // Debit Expense Account
                        $newEntryExpense = new GeneralLedger();
                        $newEntryExpense->vid = $newVid;
                        $newEntryExpense->account = $expense->account_id;
                        $newEntryExpense->debit = 0;
                        $newEntryExpense->credit = $expenseAmount;
                        $newEntryExpense->type = \Auth::user()->invoiceNumberFormat($invoice->id);
                        $newEntryExpense->ref_number = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
                        $newEntryExpense->ref_id = $invoice->id;
                        $newEntryExpense->user_id = 0;
                        $newEntryExpense->created_by = \Auth::user()->creatorId();
                        $newEntryExpense->send_date = $date;
                        $newEntryExpense->reference = 'Invoice';
                        $newEntryExpense->save();

                        // Credit Tax Account (if applicable)
                        $customerChartAccountId = $customer->chart_account_id;
                        $newEntryExpenseTax = new GeneralLedger();
                        $newEntryExpenseTax->vid = $newVid;
                        $newEntryExpenseTax->account = $customerChartAccountId;
                        $newEntryExpenseTax->debit = $expenseAmount;
                        $newEntryExpenseTax->credit = 0;
                        $newEntryExpenseTax->type = \Auth::user()->invoiceNumberFormat($invoice->id);
                        $newEntryExpenseTax->ref_number = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
                        $newEntryExpenseTax->ref_id = $invoice->id;
                        $newEntryExpenseTax->user_id = $customer->id;
                        $newEntryExpenseTax->created_by = \Auth::user()->creatorId();
                        $newEntryExpenseTax->send_date = $date;
                        $newEntryExpenseTax->reference = 'Invoice';
                        $newEntryExpenseTax->save();
                    }
                    $customerArr = [

                        'customer_name' => $customer->name,
                        'customer_email' => $customer->email,
                        'invoice_name' => $customer->name,
                        'invoice_number' => $invoice->invoice,
                        'invoice_url' => $invoice->url,

                    ];
                    $resp = Utility::sendEmailTemplate('customer_invoice_sent', [$customer->id => $customer->email], $customerArr);

                    DB::commit();
                    return redirect()->back()->with('success', __('Invoice successfully sent.') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
                } catch (\Exception $e) {
                    DB::rollBack();
                    return redirect()->back()->with('error', $e->getMessage());
                }
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function resent($id)
    {
        if (\Auth::user()->can('send invoice')) {
            $invoice = Invoice::where('id', $id)->first();

            $customer = Customer::where('id', $invoice->customer_id)->first();
            $invoice->name = !empty($customer) ? $customer->name : '';
            $invoice->invoice = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);

            $invoiceId = Crypt::encrypt($invoice->id);
            $invoice->url = route('invoice.pdf', $invoiceId);
            $customerArr = [

                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'invoice_name' => $customer->name,
                'invoice_number' => $invoice->invoice,
                'invoice_url' => $invoice->url,

            ];
            $resp = Utility::sendEmailTemplate('customer_invoice_sent', [$customer->id => $customer->email], $customerArr);

            return redirect()->back()->with('success', __('Invoice successfully sent.') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function payment($invoice_id)
    {
        if (\Auth::user()->can('create payment invoice')) {
            $invoice = Invoice::where('id', $invoice_id)->first();

            $customers = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $categories = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $currencies = Currency::get()->pluck('name', 'id');
            $currencies->prepend('select currency', '');
            $currency_symbol = $invoice->currency_id && $invoice->currency
                ? $invoice->currency->symbol
                : \Auth::user()->currencySymbol();
            return view('invoice.payment', compact('customers', 'categories', 'accounts', 'invoice', 'currencies', 'currency_symbol'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function createPayment(Request $request, $invoice_id)
    {
        $invoice = Invoice::find($invoice_id);
        // if ($invoice->getDue() < $request->amount + $request->charge) {
        //     return redirect()->back()->with('error', __('Invoice payment amount should not greater than subtotal.'));
        // }

        if (\Auth::user()->can('create payment invoice')) {
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
            $amount = !empty($request->currency_rate) ? ($request->amount + $request->charge) * $request->currency_rate : $request->amount + $request->charge;
            $invoiceDue = $invoice->getDue();

            if ($amount > $invoiceDue) {
                return redirect()->back()->with('error', __('Payment amount exceeds due for invoice :number', ['number' => $invoice->invoice_id]));
            }
            try {
                DB::beginTransaction();
                $invoicePayment = new InvoicePayment();
                $invoicePayment->invoice_id = $invoice_id;
                $invoicePayment->date = $request->date;
                $invoicePayment->amount = !empty($request->currency_rate) ? ($request->amount + $request->charge) * $request->currency_rate : $request->amount + $request->charge;
                $invoicePayment->account_id = $request->account_id;
                $invoicePayment->currency_id = $request->currency_id;
                $invoicePayment->currency_rate = $request->currency_rate;
                if ($request->currency_id == $invoice->currency_id) {
                    $invoicePayment->amount_in_currency = $request->amount + $request->charge;
                } else {
                    $invoicePayment->amount_in_currency = $request->amount_in_currency;
                }
                $invoicePayment->payment_method = 0;
                $invoicePayment->reference = $request->reference;
                $invoicePayment->description = $request->description;
                $invoicePayment->charge = !empty($request->currency_rate) ? $request->charge * $request->currency_rate : $request->charge;
                $invoicePayment->bank_charge_account_id = $request->bank_charge_account_id;

                // Handle file upload once
                $fileName = null;
                if (!empty($request->add_receipt)) {
                    $document = $request->file('add_receipt');
                    $fileName = time() . "_" . preg_replace('/[^A-Za-z0-9\-]/', '', $request->add_receipt->getClientOriginalName());
                    $invoicePayment->add_receipt = $fileName;
                    $dir = 'uploads/customer_payment';
                    $document->move(public_path($dir), $fileName);
                }

                $invoicePayment->save();

                $invoice = Invoice::where('id', $invoice_id)->first();
                $due = $invoice->getDue();
                $total = $invoice->getTotal();

                $epsilon = 0.01; // or your currency's smallest unit

                // Use number_format to avoid floating point issues
                $dueFormatted = number_format($due, 2, '.', '');
                $totalFormatted = number_format($total, 2, '.', '');

                if (abs($dueFormatted - $totalFormatted) < $epsilon) {
                    // No payments made
                    $invoice->payment_status = 0; // Unpaid
                } elseif ($dueFormatted <= $epsilon) {
                    // Fully paid
                    $invoice->payment_status = 4; // Paid (use 4 for consistency)
                } else {
                    // Partially paid
                    $invoice->payment_status = 2; // Partial
                }

                $invoice->save();



                // Transaction::addTransaction($invoicePayment);
                $customer = Customer::where('id', $invoice->customer_id)->first();
                // $customer->total_paid = $customer->total_paid + $request->amount;
                // $customer->save();


                //add payment
                $invoice = Invoice::find($invoice_id);
                $payment = new CustomerPayment();
                $payment->date = $request->date;
                $payment->amount = !empty($request->currency_rate) ? ($request->amount + $request->charge) * $request->currency_rate : $request->amount + $request->charge;
                $payment->account_id = $request->account_id;
                $payment->status  = 0;
                $payment->customer_id = $invoice->customer_id;
                $payment->category_id = $invoice->category_id;
                $payment->payment_method = 0;
                $payment->reference = $request->reference;
                $payment->invoice_id = $invoice_id;
                $payment->payment_id = $invoicePayment->id;
                $payment->charge = !empty($request->currency_rate) ? $request->charge * $request->currency_rate : $request->charge;
                $payment->bank_charge_account_id = $request->bank_charge_account_id;
                $payment->currency_id = $request->currency_id;
                $payment->currency_rate = $request->currency_rate;
                $payment->amount_in_currency = $request->amount_in_currency;
                // Use the same filename if file was uploaded
                if ($fileName) {
                    $payment->add_receipt = $fileName;
                }
                $payment->description = $request->description;
                $payment->created_by = \Auth::user()->creatorId();
                $payment->payment_number = CustomerPayment::nextPaymentNumberFor($payment->created_by);
                $payment->save();
                $invoicePayment->payment_id = $payment->id;
                $invoicePayment->save();

                // Send Email
                $setings = Utility::settings();
                if ($setings['new_invoice_payment'] == 1) {
                    $customer = Customer::where('id', $invoice->customer_id)->first();
                    $invoicePaymentArr = [
                        'invoice_payment_name' => $customer->name,
                        'invoice_payment_amount' => $payment->amount,
                        'invoice_payment_date' => $payment->date,
                        'payment_dueAmount' => $payment->dueAmount,

                    ];

                    $resp = Utility::sendEmailTemplate('new_invoice_payment', [$customer->id => $customer->email], $invoicePaymentArr);
                }

                //webhook
                $module = 'New Invoice Payment';
                $webhook = Utility::webhookSetting($module);
                DB::commit();
                if ($webhook) {
                    $parameter = json_encode($invoice);
                    $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                    if ($status == true) {
                        return redirect()->back()->with('success', __('Payment successfully added.') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : '') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
                    } else {
                        return redirect()->back()->with('error', __('Webhook call failed.'));
                    }
                }
                return redirect()->back()->with('success', __('Payment successfully added.') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : '') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', $e->getMessage());
            }
        }
    }

    public function paymentDestroy(Request $request, $invoice_id, $payment_id)
    {
        //        dd($invoice_id,$payment_id);

        if (\Auth::user()->can('delete payment invoice')) {
            try {
                DB::beginTransaction();
                $payment = InvoicePayment::find($payment_id);
                $lastPayment = CustomerPayment::where('payment_id', $payment_id)->first();
                if ($lastPayment != null) {
                    $lastPayment->invoice_id = null;
                    $lastPayment->payment_id = null;
                    $lastPayment->save();
                }
                InvoicePayment::where('id', '=', $payment_id)->delete();



                $invoice = Invoice::where('id', $invoice_id)->first();
                $due = $invoice->getDue();
                $total = $invoice->getTotal();
                $epsilon = 0.01;
                if (abs($due - $total) < $epsilon) {
                    // No payments made
                    $invoice->payment_status = 0; // Unpaid
                } elseif ($due <= $epsilon) {
                    // Fully paid
                    $invoice->payment_status = 4; // Paid (use 4 for consistency)
                } else {
                    // Partially paid
                    $invoice->payment_status = 2; // Partial
                }

                if (!empty($payment->add_receipt)) {
                    //storage limit
                    $file_path = '/uploads/payment/' . $payment->add_receipt;
                    $result = Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);
                }

                $invoice->save();


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

    public function paymentReminder($invoice_id)
    {

        //        dd($invoice_id);
        $invoice = Invoice::find($invoice_id);
        $customer = Customer::where('id', $invoice->customer_id)->first();
        $invoice->dueAmount = \Auth::user()->priceFormat($invoice->getDue());
        $invoice->name = $customer['name'];
        $invoice->date = \Auth::user()->dateFormat($invoice->send_date);
        $invoice->invoice = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);

        //For Notification
        $setting = Utility::settings(\Auth::user()->creatorId());
        $customer = Customer::find($invoice->customer_id);
        $reminderNotificationArr = [
            'invoice_number' => \Auth::user()->invoiceNumberFormat($invoice->invoice_id),
            'customer_name' => $customer->name,
            'user_name' => \Auth::user()->name,
        ];

        //Twilio Notification
        if (isset($setting['twilio_reminder_notification']) && $setting['twilio_reminder_notification'] == 1) {
            Utility::send_twilio_msg($customer->contact, 'invoice_payment_reminder', $reminderNotificationArr);
        }

        // Send Email
        $setings = Utility::settings();
        if ($setings['new_payment_reminder'] == 1) {
            $invoice = Invoice::find($invoice_id);
            $customer = Customer::where('id', $invoice->customer_id)->first();
            $invoice->dueAmount = \Auth::user()->priceFormat($invoice->getDue());
            $invoice->name = $customer['name'];
            $invoice->date = \Auth::user()->dateFormat($invoice->send_date);
            $invoice->invoice = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);

            $reminderArr = [

                'payment_reminder_name' => $invoice->name,
                'invoice_payment_number' => $invoice->invoice,
                'invoice_payment_dueAmount' => $invoice->dueAmount,
                'payment_reminder_date' => $invoice->date,

            ];

            $resp = Utility::sendEmailTemplate('new_payment_reminder', [$customer->id => $customer->email], $reminderArr);
        }

        return redirect()->back()->with('success', __('Payment reminder successfully send.') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
    }

    public function customerInvoiceSend($invoice_id)
    {
        return view('customer.invoice_send', compact('invoice_id'));
    }

    public function customerInvoiceSendMail(Request $request, $invoice_id)
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
        $invoice = Invoice::where('id', $invoice_id)->first();

        $customer = Customer::where('id', $invoice->customer_id)->first();
        $invoice->name = !empty($customer) ? $customer->name : '';
        $invoice->invoice = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);

        $invoiceId = Crypt::encrypt($invoice->id);
        $invoice->url = route('invoice.pdf', $invoiceId);

        try {
            Mail::to($email)->send(new CustomerInvoiceSend($invoice));
        } catch (\Exception $e) {
            $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
        }

        return redirect()->back()->with('success', __('Invoice successfully sent.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
    }

    public function shippingDisplay(Request $request, $id)
    {
        $invoice = Invoice::find($id);

        if ($request->is_display == 'true') {
            $invoice->shipping_display = 1;
        } else {
            $invoice->shipping_display = 0;
        }
        $invoice->save();

        return redirect()->back()->with('success', __('Shipping address status successfully changed.'));
    }

    public function duplicate($invoice_id)
    {
        if (\Auth::user()->can('duplicate invoice')) {
            $invoice = Invoice::where('id', $invoice_id)->first();
            $duplicateInvoice = new Invoice();
            $duplicateInvoice->invoice_id = $this->invoiceNumber();
            $duplicateInvoice->customer_id = $invoice['customer_id'];
            $duplicateInvoice->issue_date = date('Y-m-d');
            $duplicateInvoice->due_date = $invoice['due_date'];
            $duplicateInvoice->send_date = null;
            $duplicateInvoice->category_id = $invoice['category_id'];
            $duplicateInvoice->ref_number = $invoice['ref_number'];
            $duplicateInvoice->status = 0;
            $duplicateInvoice->payment_status = 0;
            $duplicateInvoice->shipping_display = $invoice['shipping_display'];
            $duplicateInvoice->type = $invoice['type'];
            $duplicateInvoice->tax_id = $invoice['tax_id'];
            $duplicateInvoice->currency_id = $invoice['currency_id'];
            $duplicateInvoice->exchange_rate = $invoice['exchange_rate'];
            $duplicateInvoice->created_by = $invoice['created_by'];
            $duplicateInvoice->save();

            if ($duplicateInvoice) {
                $invoiceProduct = InvoiceProduct::where('invoice_id', $invoice_id)->get();
                foreach ($invoiceProduct as $product) {
                    if ($invoice['type'] == 'rent') {
                        $subP = SubProduct::where('id', $product->sub_product_id)->where('flag', '!=', 2)->where('booked', '=', 0)->first();
                        if ($subP != null) {
                            $productP = ProductService::where('id', $product->product_id)->first();
                            if ($productP->type == 'product') {
                                $subP->booked = 1;
                                $subP->invoice_id = $duplicateInvoice->id;
                                $subP->save();
                            }
                            $duplicateProduct = new InvoiceProduct();
                            $duplicateProduct->invoice_id = $duplicateInvoice->id;
                            $duplicateProduct->product_id = $product->product_id;
                            $duplicateProduct->sub_product_id = $product->sub_product_id;
                            $duplicateProduct->quantity = 1;
                            // $duplicateProduct->tax = $product->tax;
                            $duplicateProduct->discount = $product->discount;
                            if (!empty($duplicateInvoice->currency_id)) {
                                $curr = Currency::find($duplicateInvoice->currency_id);
                                $duplicateProduct->price = !empty($duplicateInvoice->exchange_rate) ? $product->price * $duplicateInvoice->exchange_rate : $product->price * $curr->exchange_rate;
                            } else {
                                $duplicateProduct->price = $product->price;
                            }
                            // $duplicateProduct->price = $product->price;
                            $duplicateProduct->save();
                        }
                    } else {
                        $subP = SubProduct::where('product_id', $product->product_id)->where('flag', '!=', 2)->where('booked', '=', 0)->first();
                        if ($subP != null) {
                            $productP = ProductService::where('id', $product->product_id)->first();
                            if ($productP->type == 'product') {
                                $subP->booked = 1;
                                $subP->invoice_id = $duplicateInvoice->id;
                                $subP->save();
                            }
                            $duplicateProduct = new InvoiceProduct();
                            $duplicateProduct->invoice_id = $duplicateInvoice->id;
                            $duplicateProduct->product_id = $product->product_id;
                            $duplicateProduct->sub_product_id = $subP->id;
                            $duplicateProduct->quantity = 1;
                            // $duplicateProduct->tax = $product->tax;
                            $duplicateProduct->discount = $product->discount;
                            if (!empty($duplicateInvoice->currency_id)) {
                                $curr = Currency::find($duplicateInvoice->currency_id);
                                $duplicateProduct->price = !empty($duplicateInvoice->exchange_rate) ? $product->price * $duplicateInvoice->exchange_rate : $product->price * $curr->exchange_rate;
                            } else {
                                $duplicateProduct->price = $product->price;
                            }
                            // $duplicateProduct->price = $product->price;
                            $duplicateProduct->save();
                        }
                    }
                }
            }
            if ($duplicateInvoice->type == "regular") {
                return redirect()->back()->with('success', __('Invoice duplicate successfully.'));
            } else {
                return redirect()->route('rentinvoice.index', $duplicateProduct->id)->with('success', __('Invoice duplicate successfully.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function previewInvoice($template, $color)
    {
        $template = Utility::resolveInvoiceTemplate($template);
        $objUser = \Auth::user();
        $settings = Utility::settings();
        $invoice = new Invoice();

        $customer = new \stdClass();
        $customer->email = '<Email>';
        $customer->name = '<Customer Name>';
        $customer->shipping_name = '<Customer Name>';
        $customer->shipping_country = '<Country>';
        $customer->shipping_state = '<State>';
        $customer->shipping_city = '<City>';
        $customer->shipping_phone = '<Customer Phone Number>';
        $customer->shipping_zip = '<Zip>';
        $customer->shipping_address = '<Address>';
        $customer->billing_name = '<Customer Name>';
        $customer->billing_country = '<Country>';
        $customer->billing_state = '<State>';
        $customer->billing_city = '<City>';
        $customer->billing_phone = '<Customer Phone Number>';
        $customer->billing_zip = '<Zip>';
        $customer->billing_address = '<Address>';

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
            $item->description = 'XYZ';
            $item->product_id = 1;
            $item->sub_product_id = 1;

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

        $invoice->invoice_id = 1;
        $invoice->type = 'rent';
        $invoice->issue_date = date('Y-m-d H:i:s');
        $invoice->due_date = date('Y-m-d H:i:s');
        $invoice->itemData = $items;

        $invoice->totalTaxPrice = 60;
        $invoice->totalQuantity = 3;
        $invoice->totalRate = 300;
        $invoice->totalDiscount = 10;
        $invoice->taxesData = $taxesData;
        $invoice->created_by = $objUser->creatorId();

        $invoice->customField = [];
        $customFields = [];

        $preview = 1;
        $color = '#' . $color;
        $font_color = Utility::getFontColor($color);

        $logo = asset('storage/uploads/logo/');
        $company_logo = Utility::getValByName('company_logo_dark');
        $invoice_logo = Utility::getValByName('invoice_logo');
        if (isset($invoice_logo) && !empty($invoice_logo)) {
            $img = Utility::get_file('invoice_logo/') . $invoice_logo;
        } else {
            $img = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }

        return view('invoice.templates.' . $template, compact('invoice', 'preview', 'color', 'img', 'settings', 'customer', 'font_color', 'customFields'));
    }

    public function invoice_ledger($invoice_id)
    {
        try {

            if (\Auth::user()->can('ledger report')) {

                $start = date('Y-m-01');
                $end = date('Y-m-t');
                $chart_accounts = ChartOfAccount::where('created_by', \Auth::user()->creatorId())->get();
                $accounts = $chart_accounts->pluck('name', 'id');
                $generalLedgerData = GeneralLedger::selectRaw('vid, account, ref_id , type,user_id, SUM(credit) as total_credit, SUM(debit) as total_debit ,created_at,updated_at,send_date,deleted_qty,sub_product_id,user_type')
                    ->where('created_by', \Auth::user()->creatorId())
                    ->where('ref_id', $invoice_id)
                    ->where('reference', 'Invoice')
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
    public function invoice(Request $request, $invoice_id)
    {
        $settings = Utility::settings();
        $showCustomFields = $request->boolean('show_custom_fields', false);

        $invoiceId = Crypt::decrypt($invoice_id);
        $invoice = Invoice::where('id', $invoiceId)->first();

        $data = DB::table('settings');
        $data = $data->where('created_by', '=', $invoice->created_by);
        $data1 = $data->get();

        foreach ($data1 as $row) {
            $settings[$row->name] = $row->value;
        }

        $customer = $invoice->customer;
        $items = [];
        $totalTaxPrice = 0;
        $totalPrice = 0;
        $totalQuantity = 0;
        $totalRate = 0;
        $totalDiscount = 0;
        $taxesData = [];
        $taxType = 'add';
        $itemcustomFields = collect();
        if ($showCustomFields) {
            $itemcustomFields = CustomField::where('created_by', '=', $invoice->created_by)
                ->where('module', '=', 'sub-product')
                ->get();
        }

        foreach ($invoice->items as $product) {
            $item = new \stdClass();
            $item->brand = !empty($product->product->brand) ? $product->product->brand->name : '';
            $item->subBrand = !empty($product->product->subBrand) ? $product->product->subBrand->name : '';
            $item->name = !empty($product->product) ? $product->product->name : '';
            $linkedSubProduct = !empty($product->sub_product_id) ? SubProduct::where('id', $product->sub_product_id)->first() : null;
            $item->subProductName = $linkedSubProduct ? $linkedSubProduct->name : '';
            $item->product_no = $linkedSubProduct ? ($linkedSubProduct->product_no ?? '') : '';
            $item->sub_product_id = $product->sub_product_id; // Added sub_product_id to item
            $item->quantity = $product->quantity;
            $item->tax = $invoice->tax_id;
            $item->unit = !empty($product->product) ? $product->product->unit_id : '';
            $item->discount = $product->discount;
            $item->product_id = $product->product_id;
            if ($invoice->currency_id != null) {
                $curr = Currency::where('id', $invoice->currency_id)->first();
                if ($invoice->exchange_rate != 0) {
                    $item->price = $product->price / $invoice->exchange_rate;
                } else {
                    $item->price = $product->price / $curr->exchange_rate;
                }
            } else {
                $item->price = $product->price;
            }
            $item->description = $product->description;
            if (!empty($item->product_no)) {
                $baseDescription = trim((string) ($item->description ?? ''));
                $item->description = $baseDescription !== ''
                    ? ('Product No: ' . $item->product_no . ' | ' . $baseDescription)
                    : ('Product No: ' . $item->product_no);
            }
            if ($showCustomFields && !empty($product->sub_product_id)) {
                $subProduct = SubProduct::where('id', $product->sub_product_id)->first();
                $item->customField = $subProduct ? CustomField::getData($subProduct, 'sub-product') : collect();
            } else {
                $item->customField = collect();
            }

            if ($showCustomFields && $itemcustomFields->count() > 0 && !empty($item->customField)) {
                $pairs = [];
                foreach ($itemcustomFields as $field) {
                    $value = $item->customField[$field->id] ?? null;
                    if ($value !== null && $value !== '') {
                        $pairs[] = $field->name . ': ' . $value;
                    }
                }

                if (!empty($pairs)) {
                    $baseDescription = trim((string) ($item->description ?? ''));
                    $customFieldsText = implode(', ', $pairs);
                    $item->description = $baseDescription !== ''
                        ? ($baseDescription . ' | ' . $customFieldsText)
                        : $customFieldsText;
                }
            }
            // $item->customFields = CustomField::getData(SubProduct::where('id',$product->sub_product_id)->first(), 'sub-product');
            $totalQuantity += 1;
            $totalRate += $item->price;
            $totalDiscount += $item->discount;

            $taxes = Utility::tax($invoice->tax_id);

            $itemTaxes = [];
            if (!empty($invoice->tax_id)) {
                foreach ($taxes as $tax) {
                    if (\App\Models\ProductService::where('id', $item->product_id)->first()->type === 'product') {
                        $taxPrice = Utility::taxRate($tax->rate, $item->price, 1, $item->discount);
                        $totalTaxPrice += $taxPrice;

                        $itemTax['name'] = $tax->name;
                        $itemTax['rate'] = $tax->rate . '%';
                        $itemTax['price'] = $invoice->currency_id == null ? Utility::priceFormat($settings, $taxPrice) : Utility::priceFormatCurr($settings, $taxPrice, Currency::where('id', $invoice->currency_id)->first()->symbol);
                        $itemTax['tax_price'] = $taxPrice;
                        $itemTaxes[] = $itemTax;

                        if (array_key_exists($tax->name, $taxesData)) {
                            $taxesData[$tax->name] = $taxesData[$tax->name] + $taxPrice;
                        } else {
                            $taxesData[$tax->name] = $taxPrice;
                        }
                    }
                }

                $item->itemTax = $itemTaxes;
            } else {
                $item->itemTax = [];
            }
            $items[] = $item;
        }

        $invoice->itemData = $items;
        $invoice->totalTaxPrice = $totalTaxPrice;
        $invoice->totalQuantity = $totalQuantity;
        $invoice->totalRate = $totalRate;
        $invoice->totalDiscount = $totalDiscount;
        $invoice->taxesData = $taxesData;
        $invoice->customField = CustomField::getData($invoice, 'invoice');
        $customFields = [];
        if (!empty(\Auth::user())) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'invoice')->get();
        }
        //
        //        $logo         = asset(Storage::url('uploads/logo/'));
        //        $company_logo = Utility::getValByName('company_logo_dark');
        //        $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));

        $logo = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $settings_data = \App\Models\Utility::settingsById($invoice->created_by);
        $invoice_logo = $settings_data['invoice_logo'];
        if (isset($invoice_logo) && !empty($invoice_logo)) {
            $img = Utility::get_file('invoice_logo/') . $invoice_logo;
        } else {
            $img = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }

        if ($invoice) {
            $color = '#' . $settings['invoice_color'];
            $font_color = Utility::getFontColor($color);
            $invoiceTemplate = Utility::resolveInvoiceTemplate($settings['invoice_template'] ?? null);

            return view('invoice.templates.' . $invoiceTemplate, compact('invoice', 'color', 'settings', 'customer', 'img', 'font_color', 'customFields', 'itemcustomFields', 'taxType', 'invoiceId'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function printGrouped($encryptedId)
    {
        try {
            $invoiceId = Crypt::decrypt($encryptedId);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Invoice Not Found.'));
        }

        $invoice = Invoice::with(['items.product.brand', 'items.product.subBrand', 'customer'])->find($invoiceId);
        if (!$invoice || $invoice->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Invoice not found or permission denied.'));
        }

        $settings = Utility::settingsById($invoice->created_by);
        $customer = $invoice->customer;

        $items = [];
        $totalTaxPrice = 0;
        foreach ($invoice->items as $product) {
            $item = new \stdClass();
            $item->brand = !empty($product->product->brand) ? $product->product->brand->name : '';
            $item->subBrand = !empty($product->product->subBrand) ? $product->product->subBrand->name : '';
            $item->name = !empty($product->product) ? $product->product->name : '';

            $subProduct = $product->sub_product_id ? SubProduct::find($product->sub_product_id) : null;
            $item->sub_product_id = $product->sub_product_id;
            $item->sub_product_no = $subProduct ? $subProduct->chassis_no : null;
            $item->product_no = $subProduct ? $subProduct->chassis_no : '';

            $item->quantity = $product->quantity;
            $item->discount = $product->discount;
            $item->product_id = $product->product_id;

            if ($invoice->currency_id != null) {
                $curr = Currency::find($invoice->currency_id);
                if ($invoice->exchange_rate != 0) {
                    $item->price = $product->price / $invoice->exchange_rate;
                } else {
                    $item->price = $product->price / ($curr->exchange_rate ?: 1);
                }
            } else {
                $item->price = $product->price;
            }

            $item->description = $product->description;
            if (!empty($item->product_no)) {
                $baseDescription = trim((string) ($item->description ?? ''));
                $item->description = $baseDescription !== ''
                    ? ('Product No: ' . $item->product_no . ' | ' . $baseDescription)
                    : ('Product No: ' . $item->product_no);
            }
            $item->customField = $subProduct
                ? CustomField::getData($subProduct, 'sub-product')
                : collect();

            // Taxes per item (reusing logic from invoice())
            $item->itemTax = [];
            if (!empty($invoice->tax_id) && \App\Models\ProductService::where('id', $item->product_id)->first()?->type === 'product') {
                $taxes = Utility::tax($invoice->tax_id);
                $itemTaxes = [];
                foreach ($taxes as $tax) {
                    $taxPrice = Utility::taxRate($tax->rate, $item->price, 1, $item->discount);
                    $totalTaxPrice += $taxPrice;

                    $itemTax = [];
                    $itemTax['name'] = $tax->name;
                    $itemTax['rate'] = $tax->rate . '%';
                    $itemTax['price'] = $invoice->currency_id == null
                        ? Utility::priceFormat($settings, $taxPrice)
                        : Utility::priceFormatCurr($settings, $taxPrice, Currency::find($invoice->currency_id)->symbol);
                    $itemTax['tax_price'] = $taxPrice;
                    $itemTaxes[] = $itemTax;
                }
                $item->itemTax = $itemTaxes;
            }

            $items[] = $item;
        }

        // Group items by sub product no + custom field values
        $groupedItems = collect($items)->groupBy(function ($item) {
            $cf = $item->customField ?? collect();
            $cfKey = $cf->map(function ($v, $k) {
                return is_array($v) ? implode(',', $v) : $v;
            })->implode('|');
            return ($item->sub_product_no ?: $item->sub_product_id) . '|' . $cfKey;
        });

        return view('invoice.print_grouped', [
            'invoice' => $invoice,
            'customer' => $customer,
            'groupedItems' => $groupedItems,
        ]);
    }

    public function saveTemplateSettings(Request $request)
    {

        $post = $request->all();
        unset($post['_token']);

        if (isset($post['invoice_template']) && (!isset($post['invoice_color']) || empty($post['invoice_color']))) {
            $post['invoice_color'] = "ffffff";
        }
        if (isset($post['invoice_template'])) {
            $post['invoice_template'] = Utility::resolveInvoiceTemplate($post['invoice_template']);
        }

        if ($request->invoice_logo) {
            $dir = 'invoice_logo/';
            $invoice_logo = \Auth::user()->id . '_invoice_logo.png';
            $validation = [
                'mimes:' . 'png',
                'max:' . '20480',
            ];
            $path = Utility::upload_file($request, 'invoice_logo', $invoice_logo, $dir, $validation);

            if ($path['flag'] == 0) {
                return redirect()->back()->with('error', __($path['msg']));
            }
            $post['invoice_logo'] = $invoice_logo;
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

        return redirect()->back()->with('success', __('Invoice Setting updated successfully'));
    }

    public function items(Request $request)
    {
        $items = InvoiceProduct::where('invoice_id', $request->invoice_id)->where('product_id', $request->product_id)->first();

        return json_encode($items);
    }

    public function invoiceLink($invoiceId)
    {
        try {
            $id = Crypt::decrypt($invoiceId);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Invoice Not Found.'));
        }

        $id = Crypt::decrypt($invoiceId);
        $invoice = Invoice::with(['creditNote', 'payments.bankAccount', 'items.product.unit'])->find($id);

        $settings = Utility::settingsById($invoice->created_by);

        if (!empty($invoice)) {

            $user_id = $invoice->created_by;
            $user = User::find($user_id);
            $invoicePayment = InvoicePayment::where('invoice_id', $invoice->id)->get();
            $customer = $invoice->customer;
            $iteams = $invoice->items;
            $invoice->customField = CustomField::getData($invoice, 'invoice');
            $customFields = CustomField::where('module', '=', 'invoice')->get();
            $company_payment_setting = Utility::getCompanyPaymentSetting($user_id);

            // start for storage limit note
            $user_plan = Plan::find($user->plan);
            // end for storage limit note

            return view('invoice.customer_invoice', compact('settings', 'invoice', 'customer', 'iteams', 'invoicePayment', 'customFields', 'user', 'company_payment_setting', 'user_plan', 'id'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function export()
    {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        $name = 'invoice_' . date('Y-m-d i:h:s');
        $data = Excel::download(new InvoiceExport(), $name . '.xlsx');

        return $data;
    }

    public function exportInvoiceItems($id)
    {
        if (!\Auth::user()->can('show invoice')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $invoiceId = Crypt::decrypt($id);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Invoice Not Found.'));
        }

        $invoice = Invoice::find($invoiceId);
        if (!$invoice || $invoice->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $name = 'invoice_items_' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id) . '_' . date('Y-m-d_H-i-s');
        return Excel::download(new InvoiceItemsExport($invoice), $name . '.xlsx');
    }

    public function importFile()
    {
        return view('invoice.import');
    }

    public function downloadSample()
    {
        if (!\Auth::user()->can('create invoice')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Row 0: Invoice Headers
            $invoiceHeaders = [
                'customer_id',
                'Issue Date',
                'Due Date',
                'category_id',
                'salesman_id',
                'tax_id',
                'currency_id',
                'exchange_rate',
                'Bank_id'
            ];
            $sheet->fromArray([$invoiceHeaders], null, 'A1');

            // Row 1: Sample Invoice Data
            $sampleInvoiceData = [
                '1', // customer_id (replace with actual customer ID)
                date('Y-m-d'), // Issue Date
                date('Y-m-d', strtotime('+30 days')), // Due Date
                '1', // category_id (replace with actual category ID)
                \Auth::user()->id, // salesman_id
                '1', // tax_id (replace with actual tax ID, can be comma-separated for multiple)
                '', // currency_id (optional, leave empty for AED)
                '1', // exchange_rate (1 for AED)
                '' // Bank_id (optional)
            ];
            $sheet->fromArray([$sampleInvoiceData], null, 'A2');

            // Row 2: Product Headers
            $productHeaders = [
                'product_no',
                'warehouse_id',
                'qty',
                'price',
                'discount'
            ];
            $sheet->fromArray([$productHeaders], null, 'A3');

            // Row 3+: Sample Product Data
            $sampleProducts = [
                ['PROD-001', '1', '2', '100.00', '10.00'],
                ['PROD-002', '1', '1', '150.00', '0.00'],
                ['PROD-003', '', '3', '75.50', '5.00'],
            ];
            $sheet->fromArray($sampleProducts, null, 'A4');

            // Style header rows
            $sheet->getStyle('A1:I1')->getFont()->setBold(true);
            $sheet->getStyle('A1:I1')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');
            $sheet->getStyle('A1:I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle('A3:E3')->getFont()->setBold(true);
            $sheet->getStyle('A3:E3')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');
            $sheet->getStyle('A3:E3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Auto-size columns
            foreach (range('A', 'I') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            foreach (range('A', 'E') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Format date columns
            $sheet->getStyle('B2')->getNumberFormat()->setFormatCode('yyyy-mm-dd');
            $sheet->getStyle('C2')->getNumberFormat()->setFormatCode('yyyy-mm-dd');

            // Format price and discount columns
            $sheet->getStyle('D4:D6')->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('E4:E6')->getNumberFormat()->setFormatCode('#,##0.00');

            // Create writer and save to temporary file
            $writer = new Xlsx($spreadsheet);
            $fileName = 'sample-invoice.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), 'sample_invoice');
            $writer->save($tempFile);

            return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            \Log::error('Error generating invoice sample file', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return back()->with('error', __('Failed to generate sample file: ') . $e->getMessage());
        }
    }

    public function import(Request $request)
    {
        if (!\Auth::user()->can('create invoice')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        try {
            // Use Laravel Excel's import system directly
            Excel::import(new InvoiceImport(auth()->id()), $request->file('file'));

            return back()->with('success', __('Invoice imported successfully!'));
        } catch (\Exception $e) {
            \Log::error('Invoice import failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id()
            ]);

            return back()->with('error', __('Import failed: ') . $e->getMessage());
        }
    }

    function goToAddSubProducts($invoice_id)
    {

        $invoice = Invoice::find($invoice_id);

        $invoiceProducts = InvoiceProduct::where('invoice_id', $invoice_id)->get();

        $totalTaxPrice = 0;
        $total_amount = 0;
        $product_services = ProductService::where('created_by', \Auth::user()->creatorId())->with(['brand', 'subBrand', 'category'])
            ->get()
            ->map(function ($productService) {
                $category = $productService->category->name ?? '';
                $brand = $productService->brand->name ?? '';
                $subBrand = $productService->subBrand->name ?? '';
                $productName = $productService->name;
                $productSku = $productService->sku;

                return [
                    'id' => $productService->id,
                    'name' => $category . '/' . $brand . '/' . $subBrand . '/' . $productName . '/' . $productSku,
                ];
            })
            ->pluck('name', 'id');
        $startDate = \Carbon\Carbon::parse($invoice->issue_date);
        $endDate = \Carbon\Carbon::parse($invoice->due_date);
        foreach ($invoiceProducts as $item) {
            if (SubProduct::where('id', $item->sub_product_id)->first()->productService->category->type === "Qty product") {
                $total_amount += (($item->price * $item->quantity) - ($item->discount * $item->quantity));
            } else {
                $total_amount += ($item->price - $item->discount);
            }
        }
        $totalTaxName = ' ';
        $taxType = 'add';
        $tax_bill = $invoice->tax_id;
        $taxes = Utility::tax($invoice->tax_id);
        foreach ($taxes as $tax) {
            $taxPrice = Tax::where('id', $tax->id)->first()->rate;
            $totalTaxPrice += $taxPrice;
            $totalTaxName = $totalTaxName . ' ' . Tax::where('id', $tax->id)->first()->name;
        }
        $type = $invoice->type;
        $subProductData = [];


        $expenses = $invoice->expenses;
        $totalExpenseAmount = $expenses->sum('amount');

        return view('invoice.addProducts', compact('invoiceProducts', 'invoice', 'product_services', 'tax_bill', 'total_amount', 'totalTaxName', 'totalTaxPrice', 'type', 'taxType', 'expenses', 'totalExpenseAmount'));
    }

    function destroySubProduct($id, $invoice_id)
    {
        $productService = SubProduct::find($id);
        $invoice = Invoice::find($invoice_id);
        if ($productService->created_by == \Auth::user()->creatorId()) {
            $invoice_product = InvoiceProduct::where('sub_product_id', $id)->where('invoice_id', $invoice_id)->first();
            $invoice_product->delete();
            $productService->booked = 0;
            $productService->invoice_id  = null;
            // $productService->quantity  += $invoice_product->quantity;
            $productService->save();
            // $bill = Bill::where('id', $productService->bill_id)->first();
            // if ($bill->warehouse_id != null) {
            //     Utility::warehouse_quantity('plus', 1, $id, $bill->warehouse_id);
            // }
            return redirect()->back()->with('success', __('Sub Product successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function createSubProduct($id)
    {

        $invoice = Invoice::find($id);
        $product_services = ProductService::where('created_by', \Auth::user()->creatorId())
            ->whereHas('subProducts', function ($query) {
                $query->where('flag', '!=', 2)->where('booked', '=', 0);
            })
            ->with(['brand', 'subBrand', 'category'])
            ->get()
            ->map(function ($productService) {
                $category = $productService->category->name ?? '';
                $brand = $productService->brand->name ?? '';
                $subBrand = $productService->subBrand->name ?? '';
                $productName = $productService->name;

                return [
                    'id' => $productService->id,
                    'name' => $category . '/' . $brand . '/' . $subBrand . '/' . $productName,
                ];
            })
            ->pluck('name', 'id');
        $product_services->prepend('--', '');

        return view('invoice.createSubProduct', compact('id', 'product_services'));
    }

    public function createSubProductExpense($id)
    {

        $invoice = Invoice::find($id);
        $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
            ->where('created_by', \Auth::user()->creatorId())->get()
            ->pluck('code_name', 'id');
        $chartAccounts->prepend('Select Account', '');

        return view('invoice.createSubProductExpense', compact('id', 'chartAccounts'));
    }

    public function storeSubProduct(Request $request)
    {
        $rules = [
            // 'name' => 'required',
            'subProducts' => 'required',
            'quantity' => 'required|numeric',
            'product_id' => 'required',

        ];

        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->route('invoice.addSubProducts', ['invoice_id' => $request->id])->with('error', $messages->first());
        }
        $invoice = Invoice::find($request->id);
        // $productService                      = SubProduct::find($request->sub_product_id);
        // $productService->name                = $request->name;
        // $productService->number              = $request->number;
        // $productService->sale_price          = $request->sale_price;
        // $productService->save();
        // CustomField::saveData($productService, $request->customField);
        $product = ProductService::find($request->product_id);

        // Add the retrieved sub-products to the array
        $subProducts = $request->input('subProducts');
        foreach ($subProducts as $key => $subProduct) {
            $subProductID = (int)$subProduct;
            $productService = SubProduct::find($subProductID);
            if ($product->type == 'product') {
                $productService->invoice_id = $invoice->id;
                $productService->booked = 1;
                $productService->quantity -= 1;
                $productService->save();
            }
            $invoiceProduct = new InvoiceProduct();
            $invoiceProduct->invoice_id = $invoice->id;
            $invoiceProduct->product_id = $request->product_id;
            $invoiceProduct->sub_product_id = $productService->id;
            $invoiceProduct->quantity = 1;
            $invoiceProduct->tax = $invoice->tax_id;
            $invoiceProduct->discount = 0;
            $invoiceProduct->price = $productService->sale_price;
            $invoiceProduct->save();
        }
        return redirect()->route('invoice.addSubProducts', $request->id)->with('success', __('Sub Product successfully created.'));
    }

    public function storeInvoiceExpense(Request $request)
    {

        $rules = [
            // 'name' => 'required',
            'amount' => 'required|numeric',
            'account_id' => 'required',

        ];

        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->route('invoice.addSubProducts', ['invoice_id' => $request->id])->with('error', $messages->first());
        }
        $invoice = Invoice::find($request->id);

        // Calculate AED amount from invoice currency
        $amountAED = $request->amount;
        $amountInCurrency = $request->amount;
        $currencyRate = 1;

        if ($invoice->currency_id && $invoice->exchange_rate > 0) {
            // Convert from invoice currency to AED
            $amountAED = $request->amount * $invoice->exchange_rate;
            $amountInCurrency = $request->amount;
            $currencyRate = $invoice->exchange_rate;
        }

        // Create the expense record
        $expense = new InvoiceExpense();
        $expense->invoice_id = $request->id;
        $expense->account_id = $request->account_id;
        $expense->amount = $amountAED; // Store in AED
        $expense->currency_id = $invoice->currency_id; // Use invoice currency
        $expense->currency_rate = $currencyRate;
        $expense->amount_in_currency = $amountInCurrency;
        $expense->description = $request->description ?? '';
        $expense->created_by = \Auth::user()->creatorId();
        $expense->save();
        return redirect()->route('invoice.addSubProducts', $request->id)->with('success', __('Expense successfully created.'));
    }

    function updateSubProduct(Request $request)
    {
        // dd($request->all());
        $invoice = Invoice::find($request->id);
        $items = $request->items;
        // $invoiceProducts = InvoiceProduct::where('invoice_id', $request->id)->delete();
        foreach ($items as $index => $item) {
            $productService = SubProduct::find($item['sub_product_id']);
            $invoiceProduct  = InvoiceProduct::where('sub_product_id', $item['sub_product_id'])->where('invoice_id', $request->id)->first();

            // Check if productService exists
            if (!$productService) {
                return redirect()->back()->with('error', __("Sub product not found"));
            }

            // Check if invoiceProduct exists
            if (!$invoiceProduct) {
                return redirect()->back()->with('error', __("Invoice product not found"));
            }

            // Ensure qty is set and is a valid number
            if (!isset($item['qty']) || $item['qty'] === '' || $item['qty'] === null) {
                return redirect()->back()->with('error', __("Quantity is required for item at index " . $index));
            }

            $qty = (int)$item['qty'];
            if ($qty <= 0) {
                return redirect()->back()->with('error', __("Quantity must be greater than 0 for item at index " . $index));
            }

            // Get the product to check category type
            $product = ProductService::find($invoiceProduct->product_id);
            $categoryType = $product && $product->category ? $product->category->type : null;

            // Check quantity availability
            if ($productService->quantity + $invoiceProduct->quantity < $qty) {
                return redirect()->back()->with('error', __("Not enough quantity to invoice"));
            }

            $productService->product_no = $item['product_no'];

            // Update sub product quantity for Qty product types
            if ($categoryType === "Qty product") {
                // Store the old quantity that was previously invoiced
                $oldQty = $invoiceProduct->quantity;

                // Step 1: Add back the old quantity to sub product (return to stock)
                $productService->quantity += $oldQty;

                // Step 2: Subtract the new quantity from sub product (deduct from stock)
                $productService->quantity -= $qty;

                // Ensure quantity doesn't go negative
                if ($productService->quantity < 0) {
                    $productService->quantity = 0;
                }

                // Update booked status based on remaining quantity
                $productService->booked = $productService->quantity > 0 ? 0 : 1;
            }

            // $productService->exterior_color_id = $item['exterior_color_id'];
            // $productService->interior_color_id = $item['interior_color_id'];
            $productService->save();

            // Calculate amounts in invoice currency if invoice has currency
            $priceInCurrency = $item['sale_price'];
            $discountInCurrency = $item['discount'];

            if ($invoice->currency_id && $invoice->exchange_rate > 0) {
                // Convert from AED to invoice currency for storage
                $priceInCurrency = $item['sale_price'] / $invoice->exchange_rate;
                $discountInCurrency = $item['discount'] / $invoice->exchange_rate;
            }

            $invoiceProduct->price = $item['sale_price'];
            $invoiceProduct->quantity = $qty;
            $invoiceProduct->discount = $item['discount'];
            $invoiceProduct->exchange_price = $priceInCurrency;
            $invoiceProduct->exchange_discount = $discountInCurrency;
            $invoiceProduct->save();
            // dd($invoiceProduct);
        }
        // Update expenses if provided
        if ($request->has('expenses')) {
            foreach ($request->expenses as $expenseIndex => $expenseData) {
                if (isset($expenseData['id'])) {
                    $expense = \App\Models\InvoiceExpense::find($expenseData['id']);
                    if ($expense) {
                        if (isset($expenseData['description'])) {
                            $expense->description = $expenseData['description'];
                        }
                        if (isset($expenseData['amount'])) {
                            // Calculate amount in invoice currency if invoice has currency
                            $amountInCurrency = $expenseData['amount'];

                            if ($invoice->currency_id && $invoice->exchange_rate > 0) {
                                // Convert from AED to invoice currency for storage
                                $amountInCurrency = $expenseData['amount'] / $invoice->exchange_rate;
                            }

                            $expense->amount = $expenseData['amount']; // Store in invoice currency
                            $expense->amount_in_currency = $amountInCurrency; // Store in invoice currency
                        }
                        $expense->save();
                    }
                }
            }
        }
        
        // Refresh invoice to get updated totals after product changes
        $invoice->refresh();
        
        // Check if payment status needs to be updated after price changes
        $oldPaymentStatus = $invoice->payment_status;
        $due = $invoice->getDue();
        $total = $invoice->getTotal();
        $totalPaid = $invoice->getTotalPaid();
        
        $epsilon = 0.01; // Small value to handle floating point comparison
        
        // If invoice was fully paid (status 4) and the new total is higher than what was paid, change to partially paid
        if ($oldPaymentStatus == 4 && $total > $totalPaid + $epsilon) {
            $invoice->payment_status = 2; // Partially paid
            $invoice->save();
        } else {
            // Recalculate payment status based on due amount
            $dueFormatted = number_format($due, 2, '.', '');
            $totalFormatted = number_format($total, 2, '.', '');
            
            if (abs($dueFormatted - $totalFormatted) < $epsilon) {
                // No payments made
                $invoice->payment_status = 0; // Unpaid
            } elseif ($dueFormatted <= $epsilon) {
                // Fully paid
                $invoice->payment_status = 4; // Paid
            } else {
                // Partially paid
                $invoice->payment_status = 2; // Partial
            }
            $invoice->save();
        }
        
        return redirect()->route('invoice.show', ['invoice' => Crypt::encrypt($invoice->id)])->with('success', __('Invoice successfully created.'));
        // if ($invoice->type == "regular") {
        //     return redirect()->route('invoice.index', $request->id)->with('success', __('Sub Product successfully created.'));
        // } else {
        //     return redirect()->route('rentinvoice.index', $request->id)->with('success', __('Sub Product successfully created.'));
        // }
    }

    public function getInvoiceDetails($invoice_id)
    {
        $invoice = Invoice::findOrFail($invoice_id);

        return response()->json([
            'id' => $invoice->id,
            'due_amount' => $invoice->getDue(),
            'currency_id' => $invoice->currency_id,
            'exchange_rate' => $invoice->exchange_rate ?? 1
        ]);
    }

    public function getInvoices($vendorId)
    {

        $invoices = Invoice::where("customer_id", $vendorId)->whereIn("payment_status", [2, 4])->whereIn("status", [4, 6])->get();

        return response()->json($invoices);
    }

    public function userPayment(Request $request, $invoice_id)
    {
        $invoice = Invoice::find($invoice_id);

        if (\Auth::user()->can('create payment invoice')) {
            try {
                DB::beginTransaction();
                $invoicePayment = new InvoicePayment();
                $invoicePayment->invoice_id = $invoice_id;
                $invoicePayment->date = date('Y-m-d');
                $invoicePayment->amount = $invoice->getTotal();
                $invoicePayment->account_id = 0;
                $invoicePayment->payment_method = 0;
                $invoicePayment->reference = null;
                $invoicePayment->description = '';

                $invoicePayment->save();

                $invoice = Invoice::where('id', $invoice_id)->first();
                $due = $invoice->getDue();
                $total = $invoice->getTotal();

                $epsilon = 0.01; // or your currency's smallest unit

                if (abs($due - $total) < $epsilon) {
                    // No payments made
                    $invoice->payment_status = 0; // Unpaid
                } elseif ($due <= $epsilon) {
                    // Fully paid
                    $invoice->payment_status = 4; // Paid (use 4 for consistency)
                } else {
                    // Partially paid
                    $invoice->payment_status = 2; // Partial
                }

                $invoice->save();

                $invoicePayment->user_id = $invoice->customer_id;
                $invoicePayment->user_type = 'Customer';
                $invoicePayment->type = 'Paid';
                $invoicePayment->created_by = \Auth::user()->id;
                $invoicePayment->payment_id = $invoicePayment->id;
                $invoicePayment->category = 'Invoice';
                $invoicePayment->account = 0;


                $customer = Customer::where('id', $invoice->customer_id)->first();


                $payment = new InvoicePayment();
                $payment->name = $customer['name'];
                $payment->date = \Auth::user()->dateFormat(now());
                $payment->amount = \Auth::user()->priceFormat($invoice->getTotal());
                $payment->invoice = 'invoice ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
                $payment->dueAmount = \Auth::user()->priceFormat($invoice->getDue());

                //webhook
                $module = 'New Invoice Payment';
                $webhook = Utility::webhookSetting($module);
                DB::commit();
                if ($webhook) {
                    $parameter = json_encode($invoice);
                    $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                    if ($status == true) {
                        return redirect()->route('invoice.index')->with('success', __('Payment successfully added.'));
                    } else {
                        return redirect()->route('invoice.index')->with('error', __('Webhook call failed.'));
                    }
                }
                return redirect()->route('invoice.index')->with('success', __('Payment successfully added.'));
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->route('invoice.index')->with('error', $e->getMessage());
            }
        }
    }

    public function uploadinvoice(Request $request)
    {
        // Validate the file
        $request->validate([
            'fileInput.*' => 'required|file|max:10240', // Example validation rules (max size: 10MB)
        ]);
        $invoiceId = $request->input('invoiceId');
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
            $accountDocument->invoice_id = $invoiceId;
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

    public function showItemdelete($id, $qty, $inv_id)
    {

        if (\Auth::user()->can('show invoice')) {

            $id   = Crypt::decrypt($id);
            // $item = SubProduct::where('id',$id)->first();
            $item = InvoiceProduct::withTrashed()->where('sub_product_id', $id)->where('invoice_id', $inv_id)->first();
            // dd($item);
            $invoice = Invoice::withTrashed()->where('id', $inv_id)->first();

            return view('invoice.viewItemReturn', compact('item', 'invoice', 'qty'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
    public function showdelete($ids)
    {

        if (\Auth::user()->can('show invoice')) {
            try {
                $id = Crypt::decrypt($ids);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Invoice Not Found.'));
            }
            $id = Crypt::decrypt($ids);
            $invoice = Invoice::with(['payments.bankAccount', 'items.product.unit'])->withTrashed()->find($id);
            $isSent = false;
            $count = 0;
            if (!empty($invoice->created_by) == \Auth::user()->creatorId()) {
                $invoicePayment = InvoicePayment::where('invoice_id', $invoice->id)->first();

                $customer = $invoice->customer;
                $iteams = InvoiceProduct::withTrashed()
                    ->where('invoice_id', $invoice->id)
                    ->get();
                $user = \Auth::user();

                // start for storage limit note
                $invoice_user = User::find($invoice->created_by);
                $user_plan = Plan::getPlan($invoice_user->plan);
                // end for storage limit note

                $invoiceSubProducts = $this->getInvoiceSubProductsFromItems((int)$invoice->id);
                foreach ($iteams as $x) {
                    $sub = $invoiceSubProducts->get($x->sub_product_id);
                    if ($sub && $sub->flag == 1) {
                        $count += 1;
                    }
                }
                if ($count === count($iteams)) {
                    $isSent = true;
                }
                $expenses = InvoiceExpense::withTrashed()->where('invoice_id', $invoice->id)->get();
                return view('invoice.viewReturn', compact('invoice', 'customer', 'iteams', 'invoicePayment', 'user', 'invoice_user', 'user_plan', 'isSent', 'expenses'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function approve($id)
    {
        if (\Auth::user()->can('approve invoice')) {
            $invoice            = Invoice::where('id', $id)->first();
            $invoice->send_date = date('Y-m-d');
            $invoice->status    = 2;
            $invoice->save();


            $invoiceId    = Crypt::encrypt($invoice->id);
            $statusChange = new InvoiceStatusChange();
            $statusChange->invoice_id = $invoice->id;
            $statusChange->status = 2;
            $statusChange->payment_status = -1;
            $statusChange->changed_at = now();
            $statusChange->save();
            return redirect()->back()->with('success', __('Invoice successfully approved.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function receive($id)
    {
        if (\Auth::user()->can('receive invoice')) {
            $invoice            = Invoice::where('id', $id)->first();
            $invoice->send_date = date('Y-m-d');
            $invoice->status    = 6;
            $invoice->save();
            $statusChange = new InvoiceStatusChange();
            $statusChange->invoice_id = $invoice->id; // Assign the bill_id
            $statusChange->status = 6; // Example status value
            $statusChange->payment_status = -1; // Example payment status value
            $statusChange->changed_at = now(); // Current timestamp
            $statusChange->save();

            return redirect()->back()->with('success', __('Invoice successfully received.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
    public function sendtoapprove($id)
    {
        if (\Auth::user()->can('send to approve invoice')) {
            $invoice            = Invoice::where('id', $id)->first();
            $invoice->send_date = date('Y-m-d');
            $invoice->status    = 1;
            $invoice->save();
            $statusChange = new InvoiceStatusChange();
            $statusChange->invoice_id = $invoice->id; // Assign the bill_id
            $statusChange->status = 1; // Example status value
            $statusChange->payment_status = -1; // Example payment status value
            $statusChange->changed_at = now(); // Current timestamp
            $statusChange->save();

            return redirect()->back()->with('success', __('Invoice successfully Send To Approve.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function notapprove($id)
    {
        if (\Auth::user()->can('approve invoice')) {
            $invoice            = invoice::where('id', $id)->first();
            $invoice->send_date = date('Y-m-d');
            $invoice->status    = 0;
            $invoice->save();


            $billId    = Crypt::encrypt($invoice->id);
            InvoiceStatusChange::where('invoice_id', $invoice->id)->where('status', 1)->delete();




            return redirect()->back()->with('success', __('Invoice successfully approved.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
    public function backtoapprove($id)
    {
        if (\Auth::user()->can('approve invoice')) {
            $invoice            = invoice::where('id', $id)->first();
            $invoice->send_date = date('Y-m-d');
            $invoice->status    = 1;
            $invoice->save();


            $billId    = Crypt::encrypt($invoice->id);
            InvoiceStatusChange::where('invoice_id', $invoice->id)->where('status', 2)->delete();




            return redirect()->back()->with('success', __('Invoice successfully Back To approve.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function getCustomFields(Request $request)
    {
        $productId = $request->input('product_id');

        // Get the product and its category
        $product = ProductService::find($productId);

        // Fetch custom fields based on the product category
        $customFields = CustomField::forCategory($product->category_id)->where('show_in_invoice', 1)->get();

        return response()->json([
            'customFields' => $customFields,
        ]);
    }

    public function destroyExpense($id)
    {
        try {
            $expense = \App\Models\InvoiceExpense::findOrFail($id);
            $expense->delete();

            return response()->json(['success' => true, 'message' => 'Expense deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete expense.'], 500);
        }
    }
    public function rent_report()
    {
        // Get all rental invoice products with sub product info
        $rentCounts = InvoiceProduct::whereHas('invoice', function ($query) {
            $query->where('type', 'rent')
                ->where('created_by', \Auth::user()->creatorId());
        })
            ->with('subProduct.productService') // Eager load subProduct + main product
            ->get()
            ->groupBy('sub_product_id')
            ->map(function ($group) {
                $subProduct = $group->first()->subProduct ?? null;
                $mainProduct = $subProduct->productService ?? null;

                return [
                    'main_product_name' => $mainProduct ? $mainProduct->name : 'N/A',
                    'sub_product_no'    => $subProduct ? $subProduct->chassis_no : 'N/A',
                    'times_rented'      => $group->count(),
                ];
            });

        return view('rentReport.index', ['rentCounts' => $rentCounts]);
    }
    public function indexMonthly(Request $request)
    {
        $report = [];

        $query = InvoiceProduct::with(['invoice' => function ($query) {
            $query->where('type', 'rent');
        }, 'subProduct.productService'])
            ->whereHas('invoice', fn($q) => $q->where('type', 'rent'));

        // Filter by selected car
        if ($request->filled('car_id')) {
            $query->where('sub_product_id', $request->car_id);
        }

        // Filter by date range
        if ($request->filled('from') && $request->filled('to')) {
            $from = Carbon::parse($request->from)->startOfDay();
            $to = Carbon::parse($request->to)->endOfDay();

            $query->whereHas('invoice', function ($q) use ($from, $to) {
                $q->whereBetween('issue_date', [$from, $to]);
            });
        }

        $invoiceProducts = $query->get()->groupBy('sub_product_id');

        foreach ($invoiceProducts as $carId => $products) {
            $monthlyRents = [];
            $rentedDates = [];

            $productNo = optional($products->first()->subProduct)->product_no;
            $productName = optional($products->first()->subProduct->productService)->name ?? 'N/A';
            $fromDate = null;
            $toDate = null;

            foreach ($products as $product) {
                $invoice = $product->invoice;

                if (!$invoice || !$invoice->issue_date || !$invoice->due_date) {
                    continue;
                }

                $start = Carbon::parse($invoice->issue_date);
                $end = Carbon::parse($invoice->due_date);

                // Save global min and max
                if (is_null($fromDate) || $start < $fromDate) {
                    $fromDate = $start;
                }
                if (is_null($toDate) || $end > $toDate) {
                    $toDate = $end;
                }

                foreach (CarbonPeriod::create($start, $end) as $date) {
                    $monthKey = $date->format('Y-m');
                    $dayKey = $date->format('Y-m-d');

                    // Track unique dates
                    $rentedDates[$monthKey][$dayKey] = true;
                }
            }

            // Now compute days per month
            foreach ($rentedDates as $month => $dates) {
                $daysInMonth = Carbon::parse($month . '-01')->daysInMonth;
                $rentedDays = count($dates);
                $freeDays = $daysInMonth - $rentedDays;

                $monthlyRents[$month] = [
                    'rented_days' => $rentedDays,
                    'free_days' => $freeDays,
                ];
            }
            $allCars = SubProduct::select('id', 'chassis_no')->get();
            $report[] = [
                'car_id' => $carId,
                'product_no' => $productNo,
                'product_name' => $productName,
                'from' => $fromDate ? $fromDate->format('Y-m-d') : null,
                'to' => $toDate ? $toDate->format('Y-m-d') : null,
                'monthly' => $monthlyRents,
            ];
        }

        return view('rentReport.monthlyreport', compact('report', 'allCars'));
    }

    private function generateMonthRange($start, $end)
    {
        $months = [];
        $start = Carbon::parse($start)->startOfMonth();
        $end = Carbon::parse($end)->endOfMonth();

        while ($start <= $end) {
            $months[] = $start->format('Y-m');
            $start->addMonth();
        }

        return $months;
    }

    public function calculateAverageRate(Request $request, Invoice $invoice)
    {
        try {
            if (!$invoice->currency_id) {
                return response()->json(['success' => false, 'message' => 'Invoice does not have a currency.']);
            }

            $currency = Currency::find($invoice->currency_id);
            if (!$currency || !$currency->exchange_rate) {
                return response()->json(['success' => false, 'message' => 'Currency exchange rate not found.']);
            }

            $payments = $invoice->payments;

            $totalPaid = $payments->sum('amount') + $payments->sum('charges');
            $convertedTotal = 0;

            foreach ($payments as $payment) {
                $convertedTotal += ($payment->amount_in_currency ?? 0);
            }

            if ($convertedTotal == 0) {
                return response()->json(['success' => false, 'message' => 'Cannot divide by zero.']);
            }

            $newRate = $totalPaid / $convertedTotal;
            $invoice->exchange_rate = round($newRate, 6);
            $invoice->save();
            foreach ($invoice->items as $item) {
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
}
