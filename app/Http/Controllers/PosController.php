<?php

namespace App\Http\Controllers;

use App\Mail\SelledInvoice;
use App\Models\Customer;
use App\Models\Pos;
use App\Models\PosPayment;
use App\Models\PosProduct;
use App\Models\ProductService;
use App\Models\StockReport;
use App\Models\User;
use App\Models\Tax;
use App\Models\Utility;
use App\Models\warehouse;
use App\Models\WarehouseProduct;
use App\Models\SubProduct;
use App\Models\GeneralLedger;
use App\Models\ChartOfAccount;
use App\Models\BankAccount;
use App\Models\ComboOffer;
use App\Models\PaymentMethod;
use App\Models\Voucher;
use App\Models\CustomField;
use App\Models\CustomFieldValue;
use App\Models\StockMovement;
use App\Models\Bill;
use App\Models\BillProduct;
use App\Models\ProductServiceCategory;
use App\Models\Brand;
use App\Models\PrintJob;
use App\Models\PosLog;
use App\Models\PosRefund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PosReportExport;
use App\Models\MasterlistLeadger;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Exception;

class PosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Allow access if user has 'manage pos' or 'add pos' permission
        $hasPosPermission = Auth::user()->can('manage pos') || Auth::user()->can('add pos');
        if ($hasPosPermission)
        {
            $customers      = Customer::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            // $customers->prepend('Walk-in-customer', '');
            
            // Get warehouses - filter by user's assigned warehouses if user has any, otherwise show all company warehouses
            $user = Auth::user();
            $warehousesWithTax = [];
            if ($user->warehouses()->count() > 0) {
                // User has assigned warehouses - only show those
                $warehousesCollection = $user->warehouses()
                    ->with('tax')
                    ->select('*', \DB::raw("CONCAT(name) AS name"))
                    ->get();
                
                $warehouses = $warehousesCollection->pluck('name', 'id');
                
                // Build warehouse tax mapping
                foreach ($warehousesCollection as $warehouse) {
                    $warehousesWithTax[$warehouse->id] = $warehouse->tax_id;
                }
                
                // Log assigned warehouses for debugging
                Log::info('POS: Loading assigned warehouses for user', [
                    'user_id' => $user->id,
                    'user_creator_id' => $user->creatorId(),
                    'assigned_warehouses' => $warehouses->keys()->toArray(),
                    'warehouse_names' => $warehouses->toArray()
                ]);
            } else {
                // No assigned warehouses - show all company warehouses (backward compatibility)
                $warehousesCollection = warehouse::with('tax')
                    ->select('*', \DB::raw("CONCAT(name) AS name"))
                    ->where('created_by', \Auth::user()->creatorId())
                    ->get();
                
                $warehouses = $warehousesCollection->pluck('name', 'id');
                
                // Build warehouse tax mapping
                foreach ($warehousesCollection as $warehouse) {
                    $warehousesWithTax[$warehouse->id] = $warehouse->tax_id;
                }
                
                Log::info('POS: Loading company warehouses for user (no assigned warehouses)', [
                    'user_id' => $user->id,
                    'user_creator_id' => $user->creatorId(),
                    'company_warehouses' => $warehouses->keys()->toArray()
                ]);
            }
            //            $warehouses->prepend('Select Warehouse', '');
            $user = Auth::user();
            
            // Generate preview POS number and store it in session for later use
            $previewPosId = $this->invoicePosNumber();
            session()->put('preview_pos_id', $previewPosId);
            
            $details = [
                'pos_id' => $user->posNumberFormat($previewPosId),
                'customer' => $customers != null ? $customers->toArray() : [],
                'user' => $user != null ? $user->toArray() : [],
                'date' => date('Y-m-d'),
                'pay' => 'show',
            ];
            $tax = Tax::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $tax->prepend('Select Tax', '');
            $fullTax = Tax::where('created_by', '=', \Auth::user()->creatorId())->get();

            // Get all cashiers for the company (not warehouse-specific)
            $users = $this->getCashiersForWarehouse();
            $users->prepend('Select Cashier', '');

            return view('pos.index',compact('customers','warehouses','details','tax','fullTax','users','warehousesWithTax'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if(!empty($request->tax_id)){
            session()->put('tax_id', $request->tax_id);
        }
        
        // Store user_id in session if provided (for cashier selection)
        if(!empty($request->user_id)){
            session()->put('pos_user_id', $request->user_id);
        }

        $sess = session()->get('pos');

        foreach ($sess as $key => $value){
            $id = (int) substr((string) $key, 0, 1);
            // return response()->json( );
        }

        // Allow access if user has either 'manage pos' or 'add pos' permission
        $hasPosPermission = Auth::user()->can('manage pos') || Auth::user()->can('add pos');
        if ($hasPosPermission && isset($sess) && !empty($sess) && count($sess) > 0) {
            $user = Auth::user();
            $settings = Utility::settings();
            // Get customer_id from request (support both customer_id and vc_name for backward compatibility)
            $customerId = $request->customer_id ?? $request->vc_name ?? null;
            $customer = $customerId ? Customer::where('id', '=', $customerId)->where('created_by', $user->creatorId())->first() : null;
            
            // Get warehouse - prioritize user-warehouse assignments (same logic as ProductServiceController)
            $warehouseId = $request->warehouse_name;
            $warehouse = null;
            $hasWarehouseAccess = false;
            $creatorId = $user->creatorId();
            
            if ($warehouseId) {
                // First, find the warehouse (don't filter by created_by yet)
                $warehouse = warehouse::where('id', '=', $warehouseId)->first();
                
                if ($warehouse) {
                    // Option 1: Warehouse is assigned to the user via pivot table (highest priority)
                    $isAssigned = \DB::table('user_warehouses')
                        ->where('user_id', $user->id)
                        ->where('warehouse_id', $warehouseId)
                        ->exists();
                    
                    if ($isAssigned) {
                        $hasWarehouseAccess = true;
                    }
                    
                    // Option 2: Warehouse belongs to the same company (if not assigned, check company ownership)
                    if (!$hasWarehouseAccess && $warehouse->created_by == $creatorId) {
                        $hasWarehouseAccess = true;
                    }
                    
                    // Option 3: User is company type (super admin/company) - they have access to all company warehouses
                    if (!$hasWarehouseAccess && ($user->type == 'company' || $user->type == 'super admin')) {
                        if ($warehouse->created_by == $creatorId) {
                            $hasWarehouseAccess = true;
                        }
                    }
                    
                    // If no access, try to use first assigned warehouse as fallback
                    if (!$hasWarehouseAccess) {
                        $assignedWarehouses = $user->warehouses()->pluck('warehouses.id')->toArray();
                        if (!empty($assignedWarehouses)) {
                            $warehouseId = $assignedWarehouses[0];
                            $warehouse = warehouse::where('id', '=', $warehouseId)->first();
                            if ($warehouse) {
                                $hasWarehouseAccess = true;
                            }
                        }
                    }
                }
            }
            
            // Validate warehouse exists and user has access
            if (!$warehouse || !$hasWarehouseAccess) {
                return response()->json([
                    'code' => 400,
                    'error' => __('Warehouse not found or you do not have access to it.')
                ], 400);
            }

            // Generate preview POS number and store it in session for later use
            $previewPosId = $this->invoicePosNumber();
            session()->put('preview_pos_id', $previewPosId);
            
            $details = [
                'pos_id' => $user->posNumberFormat($previewPosId),
                'customer' => $customer != null ? $customer->toArray() : [],
                'warehouse' => $warehouse != null ? $warehouse->toArray() : [],
                'user' => $user != null ? $user->toArray() : [],
                'date' => date('Y-m-d'),
                'pay' => 'show',
            ];

            // Get warehouse name safely
            $warehouseName = !empty($details['warehouse']) && isset($details['warehouse']['name']) 
                ? ucfirst($details['warehouse']['name']) 
                : __('Unknown Warehouse');
            
            if (!empty($details['customer'])){
                $warehousedetails = '<h7 class="text-dark">' . $warehouseName . '</p></h7>';
                $details['customer']['billing_state'] = isset($details['customer']['billing_state']) && $details['customer']['billing_state'] != '' ? ", " . $details['customer']['billing_state'] : '';
                $details['customer']['shipping_state'] = isset($details['customer']['shipping_state']) && $details['customer']['shipping_state'] != '' ? ", " . $details['customer']['shipping_state'] : '';
                $customerName = isset($details['customer']['name']) ? ucfirst($details['customer']['name']) : __('Customer');
                $customerdetails = '<h6 class="text-dark">' . $customerName . '<p class="m-0 h6 font-weight-normal">' . (isset($details['customer']['billing_phone']) ? $details['customer']['billing_phone'] : '') . '</p>' . '<p class="m-0 h6 font-weight-normal">' . (isset($details['customer']['billing_address']) ? $details['customer']['billing_address'] : '') . '</p>' . '<p class="m-0 h6 font-weight-normal">' . (isset($details['customer']['billing_city']) ? $details['customer']['billing_city'] : '') . $details['customer']['billing_state'] . '</p>' . '<p class="m-0 h6 font-weight-normal">' . (isset($details['customer']['billing_country']) ? $details['customer']['billing_country'] : '') . '</p>' . '<p class="m-0 h6 font-weight-normal">' . (isset($details['customer']['billing_zip']) ? $details['customer']['billing_zip'] : '') . '</p></h6>';
                $shippdetails = '<h6 class="text-dark"><b>' . $customerName . '</b>' . '<p class="m-0 h6 font-weight-normal">' . (isset($details['customer']['shipping_phone']) ? $details['customer']['shipping_phone'] : '') . '</p>' . '<p class="m-0 h6 font-weight-normal">' . (isset($details['customer']['shipping_address']) ? $details['customer']['shipping_address'] : '') . '</p>' . '<p class="m-0 h6 font-weight-normal">' . (isset($details['customer']['shipping_city']) ? $details['customer']['shipping_city'] : '') . $details['customer']['shipping_state'] . '</p>' . '<p class="m-0 h6 font-weight-normal">' . (isset($details['customer']['shipping_country']) ? $details['customer']['shipping_country'] : '') . '</p>' . '<p class="m-0 h6 font-weight-normal">' . (isset($details['customer']['shipping_zip']) ? $details['customer']['shipping_zip'] : '') . '</p></h6>';
            }
            else {
                $customerdetails = '<h2 class="h6"><b>' . __('Walk-in Customer') . '</b><h2>';
                $warehousedetails = '<h7 class="text-dark">' . $warehouseName . '</p></h7>';
                $shippdetails = '-';
            }


            $settings['company_telephone'] = $settings['company_telephone'] != '' ? ", " . $settings['company_telephone'] : '';
            $settings['company_state'] = $settings['company_state'] != '' ? ", " . $settings['company_state'] : '';

            $userdetails = '<h6 class="text-dark"><b>' . ucfirst($details['user']['name']) . ' </b> <h2  class="font-weight-normal">' . '<p class="m-0 font-weight-normal">' . $settings['company_name'] . $settings['company_telephone'] . '</p>' . '<p class="m-0 font-weight-normal">' . $settings['company_address'] . '</p>' . '<p class="m-0 h6 font-weight-normal">' . $settings['company_city'] . $settings['company_state'] . '</p>' . '<p class="m-0 font-weight-normal">' . $settings['company_country'] . '</p>' . '<p class="m-0 font-weight-normal">' . $settings['company_zipcode'] . '</p></h2>';

            $details['customer']['details'] = $customerdetails;
            $details['warehouse']['details'] = $warehousedetails;

            $details['customer']['shippdetails'] = $shippdetails;

            $details['user']['details'] = $userdetails;

            $mainsubtotal = 0;
            $sales = [];
            
            // CRITICAL FIX: Deduplicate products by product_no (key) before processing
            // If same product_no appears multiple times, merge quantities and keep latest data
            $deduplicatedSess = [];
            foreach ($sess as $key => $value) {
                // Normalize key (trim whitespace, ensure consistent format)
                $normalizedKey = trim((string)$key);
                
                if (isset($deduplicatedSess[$normalizedKey])) {
                    // Product already exists - merge quantities and keep latest data
                    \Log::warning('Duplicate product_no found in POS cart (create method)', [
                        'product_no' => $normalizedKey,
                        'existing_quantity' => $deduplicatedSess[$normalizedKey]['quantity'],
                        'new_quantity' => $value['quantity']
                    ]);
                    
                    // Merge: add quantities together and keep latest other data
                    $deduplicatedSess[$normalizedKey]['quantity'] += $value['quantity'];
                    // Recalculate subtotal based on merged quantity
                    $price = $deduplicatedSess[$normalizedKey]['price'];
                    $discount = $deduplicatedSess[$normalizedKey]['discount'];
                    $deduplicatedSess[$normalizedKey]['subtotal'] = ($price - ($price * ($discount / 100))) * $deduplicatedSess[$normalizedKey]['quantity'];
                    // Keep latest other fields (combo_id, etc.)
                    $deduplicatedSess[$normalizedKey]['compo_id'] = $value['compo_id'] ?? $deduplicatedSess[$normalizedKey]['compo_id'];
                    $deduplicatedSess[$normalizedKey]['discount'] = $value['discount'] ?? $deduplicatedSess[$normalizedKey]['discount'];
                } else {
                    // New product - add to deduplicated array
                    $deduplicatedSess[$normalizedKey] = $value;
                }
            }
            
            // NOTE: Deduplication removed from create() - show all items for preview/payment
            // Deduplication only happens in store() when saving to database
            // $sess = $deduplicatedSess; // Commented out - use original $sess to show all items
            
            // return response()->json($sess);
            foreach ($sess as $key => $value){
                // Ensure warehouse is not null before using it
                if (!$warehouse) {
                    return response()->json([
                        'code' => 400,
                        'error' => __('Warehouse not found or you do not have access to it.')
                    ], 400);
                }
                
                $id_p = $warehouse->GetProduct_id($key);
                if (!$id_p) {
                    return response()->json([
                        'code' => 400,
                        'error' => __('Product not found for barcode: ') . $key
                    ], 400);
                }
                
                $product = ProductService::where('id',$id_p)->first();
                if (!$product) {
                    return response()->json([
                        'code' => 400,
                        'error' => __('Product not found.')
                    ], 400);
                }
                
                if($product->category && $product->category->type === "Qty product"){
                    $subProductsArray = $warehouse->GetFreeQuantity($key);
                    if($subProductsArray === 0){
                            return response()->json(
                                [
                                    'error' => __('Product out of stock!.'),
                                ],
                                '404'
                            );
                    }
                }
                else if($product->category){
                    $subProductsArray = SubProduct::where('product_id', $id_p)->where('flag','!=',2)->where('booked','=',0)->limit($value['quantity'])->get();
                    if(count($subProductsArray) === 0){
                            return response()->json(
                                [
                                    'error' => __('Product out of stock!.'),
                                ],
                                '404'
                            );
                    }
                }
                if($settings['site_vat_calculation'] === 'not_add'){
                    $subtotal = $warehouse->GetPrice($key) * $value['quantity'];
                }
                else{
                    $subtotal = $warehouse->GetPrice($key) * $value['quantity'];
                }

                $tax = ($subtotal * $value['tax']) / 100;
                $unitPrice = $warehouse->GetPrice($key); // Get the actual unit price from warehouse
                $sales['data'][$key]['name'] = $value['name'];
                $sales['data'][$key]['quantity'] = $value['quantity'];
                $sales['data'][$key]['price'] = Auth::user()->priceFormat($unitPrice);
                $sales['data'][$key]['tax'] = $value['tax'] . '%';
                $sales['data'][$key]['product_tax'] = $value['product_tax'];
                $sales['data'][$key]['tax_amount'] = Auth::user()->priceFormat($tax);
                $sales['data'][$key]['subtotal']   = Auth::user()->priceFormat($value['subtotal']);
                $sales['data'][$key]['discount'] = $value['discount'];
                $sales['data'][$key]['compo_id'] = $value['compo_id'];
                $text = '';
                if ($value['compo_id'] != 0){
                    $compo = ComboOffer::find($value['compo_id']);
                    if ($compo->type == 'bogo'){
                        $text = 'buy: '.$compo->buy_quantity . '| get: '.$compo->get_quantity;
                    }else{
                        $text = 'buy: '.$compo->buy_quantity . '| for: '.$compo->tiered_price;
                    }
                }
                $sales['data'][$key]['compo_text'] = $text;
                $mainsubtotal += $value['subtotal'];
            }
            
            $vouchers = session()->get('vouchers', []);
            $vouchers_amount = 0.0;
            $vouchersWithDetails = [];
            
            if(!empty($vouchers)){
                foreach ($vouchers as $key => $value) {
                    $vouchers_amount += $value['amount'];
                    
                    // Fetch full voucher details including validity
                    $voucher = Voucher::find($key);
                    if ($voucher) {
                        $vouchersWithDetails[$key] = [
                            'amount' => $value['amount'],
                            'id' => $voucher->id,
                            'valid_until' => $voucher->valid_until,
                            'active' => $voucher->active,
                        ];
                    } else {
                        // Fallback if voucher not found
                        $vouchersWithDetails[$key] = [
                            'amount' => $value['amount'],
                            'id' => $key,
                            'valid_until' => null,
                            'active' => false,
                        ];
                    }
                }
            }
            
            $discount=!empty($request->discount)?$request->discount:0;
            $sales['discount'] = Auth::user()->priceFormat($discount);
            $tax_id =$request->tax_id;
            
            // Calculate tax rate and total with tax
            $taxOb = 0;
            if (!empty($tax_id)) {
                $taxModel = \App\Models\Tax::where('id', $tax_id)->first();
                if ($taxModel) {
                    $taxOb = $taxModel->rate;
                }
            }
            
            // Calculate tax amount on full subtotal (BEFORE voucher deduction)
            $taxAmount = $mainsubtotal * ($taxOb / 100);
            // Round tax amount to 2 decimal places
            $taxAmount = round($taxAmount, 2);
            
            // Calculate total: subtotal + tax - discount, then apply voucher AFTER tax
            $total = $mainsubtotal + $taxAmount - $discount - $vouchers_amount;
            // Round total to 2 decimal places (do NOT round to whole number)
            $total = round($total, 2);
            
            $sales['sub_total'] = Auth::user()->priceFormat($mainsubtotal);
            $sales['sub_total_number'] = $mainsubtotal - $vouchers_amount;
            $sales['total'] = Auth::user()->priceFormat($total);
            // Raw total number (with 2 decimal precision) for JavaScript calculations
            $sales['total_number'] = $total;
            $sales['tax_amount'] = Auth::user()->priceFormat($taxAmount);
            $sales['tax_rate'] = $taxOb;

            $payment_methods =  PaymentMethod::where('warehouse_id' , $warehouse->id)->get();
            return view('pos.show', compact('sales', 'details','tax_id','payment_methods','vouchers', 'vouchersWithDetails'));
        } else {
            return response()->json(
                [
                    'error' => __('Add some products to cart!'),
                ],
                '404'
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Log that store method was called
        \Log::info('POS Store Method Called', [
            'user_id' => Auth::id(),
            'request_data' => $request->all(),
            'session_pos_count' => count(session()->get('pos', []))
        ]);
        
        // Allow access if user has either 'manage pos' or 'add pos' permission
        $hasPosPermission = Auth::user()->can('manage pos') || Auth::user()->can('add pos');
        if ($hasPosPermission) {
            try{
                DB::beginTransaction();

                // Normalize payments from either array or JSON string
                $rawPayments = $request->input('payments');
                $paymentsJson = $request->input('payments_json');
                $normalizedPayments = [];

                if (is_array($rawPayments) && count($rawPayments) > 0) {
                    $normalizedPayments = $rawPayments;
                } elseif (!empty($paymentsJson)) {
                    $decoded = json_decode($paymentsJson, true);
                    if (is_array($decoded)) {
                        $normalizedPayments = $decoded;
                    }
                }

                // Ensure all payment values are numeric strings/floats
                if (is_array($normalizedPayments)) {
                    foreach ($normalizedPayments as $k => $v) {
                        $amount = (float)$v;
                        if ($amount > 0) {
                            $normalizedPayments[$k] = $amount;
                        } else {
                            unset($normalizedPayments[$k]);
                        }
                    }
                }

                \Log::info('POS Store: Transaction Started', [
                    'has_payments_key' => $request->has('payments'),
                    'payments_raw' => $request->has('payments') ? $request->input('payments') : 'not set',
                    'payments_is_array' => is_array($request->input('payments')),
                    'has_payments_json' => $request->has('payments_json'),
                    'payments_json' => $paymentsJson ?? 'not set',
                    'normalized_payments' => $normalizedPayments,
                ]);
                
                $discount=$request->discount;
                // Get the latest 'vid' entry, if any exist
                $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
                // Extract the vid value from the last record and increment it
                if ($latestVoucher) {
                    $lastVid = $latestVoucher->vid;
                    $newVid = $lastVid + 1;
                } else {
                    $newVid = 1;
                }
                $existingRecord = GeneralLedger::where('vid', $newVid)->where('created_by', \Auth::user()->creatorId())->exists();
                if ($existingRecord) {
                    DB::rollBack();
                    return redirect()->back()->with('error', __("something went wrong , please try again."));
                }
            $user = Auth::user();
            $user_id = $user->creatorId();
            $creatorId = $user_id;
            
            // Get customer_id from request (changed from vc_name to customer_id)
            $customer_id = $request->customer_id ?? $request->vc_name ?? null;
            if (empty($customer_id)) {
                DB::rollBack();
                return response()->json([
                    'code' => 400,
                    'error' => __('Customer is required')
                ], 400);
            }
            
            // Get warehouse - prioritize user-warehouse assignments (same logic as create method)
            $warehouseId = $request->warehouse_name;
            $warehouse = null;
            $hasWarehouseAccess = false;
            
            if ($warehouseId) {
                // First, find the warehouse (don't filter by created_by yet)
                $warehouse = warehouse::where('id', '=', $warehouseId)->first();
                
                if ($warehouse) {
                    // Option 1: Warehouse is assigned to the user via pivot table (highest priority)
                    $isAssigned = \DB::table('user_warehouses')
                        ->where('user_id', $user->id)
                        ->where('warehouse_id', $warehouseId)
                        ->exists();
                    
                    if ($isAssigned) {
                        $hasWarehouseAccess = true;
                    }
                    
                    // Option 2: Warehouse belongs to the same company (if not assigned, check company ownership)
                    if (!$hasWarehouseAccess && $warehouse->created_by == $creatorId) {
                        $hasWarehouseAccess = true;
                    }
                    
                    // Option 3: User is company type (super admin/company) - they have access to all company warehouses
                    if (!$hasWarehouseAccess && ($user->type == 'company' || $user->type == 'super admin')) {
                        if ($warehouse->created_by == $creatorId) {
                            $hasWarehouseAccess = true;
                        }
                    }
                    
                    // If no access, try to use first assigned warehouse as fallback
                    if (!$hasWarehouseAccess) {
                        $assignedWarehouses = $user->warehouses()->pluck('warehouses.id')->toArray();
                        if (!empty($assignedWarehouses)) {
                            $warehouseId = $assignedWarehouses[0];
                            $warehouse = warehouse::where('id', '=', $warehouseId)->first();
                            if ($warehouse) {
                                $hasWarehouseAccess = true;
                            }
                        }
                    }
                }
            }
            
            // Validate warehouse exists and user has access
            if (!$warehouse || !$hasWarehouseAccess) {
                DB::rollBack();
                return response()->json([
                    'code' => 400,
                    'error' => __('Warehouse not found or you do not have access to it.')
                ], 400);
            }
            
            $warehouse_id = $warehouse->id;
            
            // Use preview POS ID from session if available, otherwise generate new one
            $previewPosId = session()->get('preview_pos_id');
            if ($previewPosId) {
                // Check if preview POS ID is still available (not already used)
                $existingPos = Pos::where('pos_id', $previewPosId)
                    ->where('created_by', $user_id)
                    ->first();
                
                if (!$existingPos) {
                    // Preview POS ID is still available, use it
                    $pos_id = $previewPosId;
                    \Log::info('Using preview POS ID from session', [
                        'preview_pos_id' => $previewPosId,
                        'user_id' => $user_id
                    ]);
                } else {
                    // Preview POS ID was already used, generate new one
                    $pos_id = $this->invoicePosNumber();
                    \Log::warning('Preview POS ID already used, generating new one', [
                        'preview_pos_id' => $previewPosId,
                        'new_pos_id' => $pos_id,
                        'user_id' => $user_id
                    ]);
                }
                
                // Clear preview POS ID from session after use
                session()->forget('preview_pos_id');
            } else {
                // No preview POS ID in session, generate new one
                $pos_id = $this->invoicePosNumber();
                \Log::info('No preview POS ID in session, generating new one', [
                    'new_pos_id' => $pos_id,
                    'user_id' => $user_id
                ]);
            }
            
            $sales = session()->get('pos');

                    // return response()->json($request);

            if (isset($sales) && !empty($sales) && count($sales) > 0) {
                // CRITICAL FIX: Deduplicate products by product_no (key)
                // If same product_no appears multiple times, merge quantities and keep latest data
                $deduplicatedSales = [];
                
                // Log original cart structure for debugging
                \Log::info('POS Cart Before Deduplication', [
                    'original_keys' => array_keys($sales),
                    'original_count' => count($sales),
                    'cart_items' => array_map(function($key, $value) {
                        return [
                            'key' => $key,
                            'name' => $value['name'] ?? 'N/A',
                            'quantity' => $value['quantity'] ?? 0,
                            'discount' => $value['discount'] ?? 0
                        ];
                    }, array_keys($sales), $sales),
                    'user_id' => $user_id
                ]);
                
                foreach ($sales as $key => $value) {
                    // Normalize key (trim whitespace, ensure consistent format)
                    $normalizedKey = trim((string)$key);
                    
                    // Log each product being processed
                    \Log::debug('Processing product in POS cart', [
                        'original_key' => $key,
                        'normalized_key' => $normalizedKey,
                        'product_name' => $value['name'] ?? 'N/A',
                        'quantity' => $value['quantity'] ?? 0,
                        'discount' => $value['discount'] ?? 0
                    ]);
                    
                    if (isset($deduplicatedSales[$normalizedKey])) {
                        // Product already exists - merge quantities and keep latest data
                        \Log::warning('Duplicate product_no found in POS cart - MERGING', [
                            'product_no' => $normalizedKey,
                            'original_key' => $key,
                            'existing_name' => $deduplicatedSales[$normalizedKey]['name'] ?? 'N/A',
                            'new_name' => $value['name'] ?? 'N/A',
                            'existing_quantity' => $deduplicatedSales[$normalizedKey]['quantity'],
                            'new_quantity' => $value['quantity'],
                            'existing_discount' => $deduplicatedSales[$normalizedKey]['discount'] ?? 0,
                            'new_discount' => $value['discount'] ?? 0,
                            'user_id' => $user_id
                        ]);
                        
                        // Merge: add quantities together and keep latest other data
                        $deduplicatedSales[$normalizedKey]['quantity'] += $value['quantity'];
                        // CRITICAL: Ensure id matches normalized key after merge
                        $deduplicatedSales[$normalizedKey]['id'] = $normalizedKey;
                        // Recalculate subtotal based on merged quantity
                        $price = $deduplicatedSales[$normalizedKey]['price'];
                        $discount = $deduplicatedSales[$normalizedKey]['discount'];
                        $deduplicatedSales[$normalizedKey]['subtotal'] = ($price - ($price * ($discount / 100))) * $deduplicatedSales[$normalizedKey]['quantity'];
                        // Keep latest other fields (combo_id, etc.)
                        $deduplicatedSales[$normalizedKey]['compo_id'] = $value['compo_id'] ?? $deduplicatedSales[$normalizedKey]['compo_id'];
                        $deduplicatedSales[$normalizedKey]['discount'] = $value['discount'] ?? $deduplicatedSales[$normalizedKey]['discount'];
                    } else {
                        // New product - add to deduplicated array
                        // CRITICAL: Ensure value['id'] matches the normalized key
                        $value['id'] = $normalizedKey;
                        $deduplicatedSales[$normalizedKey] = $value;
                        \Log::debug('Added new product to deduplicated cart', [
                            'product_no' => $normalizedKey,
                            'name' => $value['name'] ?? 'N/A',
                            'quantity' => $value['quantity'] ?? 0,
                            'value_id' => $value['id']
                        ]);
                    }
                }
                
                // Replace original sales array with deduplicated version
                $sales = $deduplicatedSales;
                
                \Log::info('POS Cart After Deduplication', [
                    'original_count' => count(session()->get('pos', [])),
                    'deduplicated_count' => count($sales),
                    'deduplicated_keys' => array_keys($sales),
                    'deduplicated_items' => array_map(function($key, $value) {
                        return [
                            'key' => $key,
                            'name' => $value['name'] ?? 'N/A',
                            'quantity' => $value['quantity'] ?? 0,
                            'discount' => $value['discount'] ?? 0
                        ];
                    }, array_keys($sales), $sales),
                    'user_id' => $user_id
                ]);
                // Check for existing POS using model to respect soft deletes
                $existingPos = Pos::where('pos_id', $pos_id)
                    ->where('created_by', $user_id)
                    ->first();
                if ($existingPos) {
                    \Log::warning('POS ID already exists', [
                        'pos_id' => $pos_id,
                        'existing_pos_db_id' => $existingPos->id,
                        'user_id' => $user_id
                    ]);
                    return response()->json(
                        [
                            'code' => 200,
                            'success' => 'Payment is already completed!',
                            ]
                        );
                } else {
                    \Log::info('POS ID is available for use', [
                        'pos_id' => $pos_id,
                        'user_id' => $user_id,
                        'preview_pos_id_was_used' => $previewPosId == $pos_id
                    ]);
                    // Get tax_id from request first (form submission), then session, then warehouse tax as default
                    $taxId = $request->tax_id ?? session()->get('tax_id') ?? null;
                    
                    // If no tax_id, try to get from warehouse
                    if (empty($taxId) && $warehouse_id) {
                        $warehouse = warehouse::find($warehouse_id);
                        if ($warehouse && $warehouse->tax_id) {
                            $taxId = $warehouse->tax_id;
                        }
                    }
                    
                    // If still no tax_id, default to 5% tax rate for the company
                    if (empty($taxId)) {
                        // Find default 5% tax for the company
                        $defaultTax = \App\Models\Tax::where('created_by', $user_id)
                            ->where('rate', 5)
                            ->first();
                        
                        if ($defaultTax) {
                            $taxId = $defaultTax->id;
                        } else {
                            // If no 5% tax exists, get the first tax for the company as fallback
                            $firstTax = \App\Models\Tax::where('created_by', $user_id)->first();
                            if ($firstTax) {
                                $taxId = $firstTax->id;
                            } else {
                                // Last resort: throw error if no tax exists for the company
                                DB::rollBack();
                                return response()->json([
                                    'code' => 400,
                                    'error' => __('No tax found for your company. Please create a tax with 5% rate.')
                                ], 400);
                            }
                        }
                    }
                    
                    $pos = new Pos();
                    $pos->pos_id = $pos_id;
                    $pos->customer_id = $customer_id;
                    $pos->warehouse_id = $warehouse_id; // Use validated warehouse_id
                    $pos->tax_id = $taxId; // Never empty - always has a tax_id (5% tax or fallback)
                    $pos->pos_date = date('Y-m-d');
                    $pos->created_by = $user_id;
                    $pos->user_id = $request->user_id ?? null; // Cashier/User who made this POS transaction
                    $pos->discount = $discount;
                    $pos->status = 'active'; // Set default status
                    
                    // Save POS and check if it succeeded
                    $saveResult = $pos->save();
                    if (!$saveResult) {
                        DB::rollBack();
                        \Log::error('POS Save Failed - save() returned false', [
                            'pos_id' => $pos_id,
                            'customer_id' => $customer_id,
                            'warehouse_id' => $warehouse_id,
                            'tax_id' => $taxId
                        ]);
                        return response()->json([
                            'code' => 500,
                            'error' => __('Failed to save POS transaction. Please try again.')
                        ], 500);
                    }
                    
                    // Verify POS was actually saved by checking if it has an ID
                    if (!$pos->id) {
                        DB::rollBack();
                        \Log::error('POS Save Failed - No ID assigned', [
                            'pos_id' => $pos_id,
                            'customer_id' => $customer_id,
                            'warehouse_id' => $warehouse_id
                        ]);
                        return response()->json([
                            'code' => 500,
                            'error' => __('POS transaction was not saved properly. Please try again.')
                        ], 500);
                    }
                    
                    // Verify POS exists in database by reloading it
                    $savedPos = Pos::find($pos->id);
                    if (!$savedPos) {
                        DB::rollBack();
                        \Log::error('POS Not Found After Save', [
                            'pos_id' => $pos_id,
                            'saved_pos_id' => $pos->id,
                            'created_by' => $user_id
                        ]);
                        return response()->json([
                            'code' => 500,
                            'error' => __('POS transaction was not saved properly. Please try again.')
                        ], 500);
                    }
                    
                    // Log successful POS creation for debugging
                    \Log::info('POS Created Successfully', [
                        'pos_id' => $pos->pos_id,
                        'pos_db_id' => $pos->id,
                        'customer_id' => $customer_id,
                        'warehouse_id' => $warehouse_id,
                        'created_by' => $user_id,
                        'status' => $pos->status,
                        'deleted_at' => $pos->deleted_at
                    ]);
                    
                    // Use the saved POS instance
                    $pos = $savedPos;
                    
                    // Double-check POS exists before proceeding
                    if (!$pos || !$pos->id) {
                        DB::rollBack();
                        \Log::error('POS Lost After Save - Critical Error', [
                            'pos_id' => $pos_id,
                            'original_pos_id' => $pos->id ?? 'null'
                        ]);
                        return response()->json([
                            'code' => 500,
                            'error' => __('Critical error: POS was lost after creation. Please contact support.')
                        ], 500);
                    }


                    foreach ($sales as $key => $value) {
                        // Use the validated warehouse object instead of finding again
                        $warehouse_obj = $warehouse;
                        if (!$warehouse_obj) {
                            DB::rollBack();
                            return redirect()->back()->with('error', __("Warehouse not found."));
                        }
                        $quantity = $warehouse_obj->GetFreeQuantity($key);
                        if ($quantity < $value['quantity']){
                            DB::rollBack();
                            return redirect()->back()->with('error', __("Product out of stock!."));
                        }
                    }

                    $product_id = null;
                    // Track free items allocation per product for BOGO combos
                    $freeItemsTracker = [];
                    $totalAmountVoucher = 0;

                    foreach ($sales as $key => $value) {
                        # Start Editting
                        // CRITICAL FIX: Ensure value['id'] matches the key (product_no/barcode)
                        // After deduplication, the key is normalized but value['id'] might still have original value
                        if (!isset($value['id']) || $value['id'] !== $key) {
                            \Log::warning('Product ID mismatch in POS cart - fixing', [
                                'key' => $key,
                                'value_id' => $value['id'] ?? 'NOT SET',
                                'product_name' => $value['name'] ?? 'N/A',
                                'user_id' => $user_id
                            ]);
                            // Update value['id'] to match the key (normalized product_no)
                            $value['id'] = $key;
                        }
                        
                        $qty_for_minuse = $value['quantity'];
                        $qty_for_minuse = $value['quantity'];
                        
                        // Log which product is being processed
                        \Log::debug('Processing product for POS save', [
                            'product_no' => $key,
                            'value_id' => $value['id'],
                            'product_name' => $value['name'] ?? 'N/A',
                            'quantity' => $value['quantity'] ?? 0,
                            'discount' => $value['discount'] ?? 0
                        ]);
                        
                        // CRITICAL FIX: Get only the LATEST SubProduct per product_no to prevent duplicates
                        // If multiple SubProduct records exist with same product_no, use only the latest one
                        // This matches the product listing logic which uses MAX(id) per product_no
                        // This ensures only ONE PosProduct is created per product_no, preventing duplicate items
                        $subProductsArray = SubProduct::where('chassis_no', $value['id'])
                                                        ->where('flag', '!=', 2)
                                                        ->where('warehouse_id', $pos->warehouse_id)
                                                        ->where('booked', '=', 0)
                                                        ->orderBy('id', 'desc') // Get latest first
                                                        ->limit(1) // Only get the latest one to prevent duplicates
                                                        ->get();
                        
                        // Log if multiple SubProduct records exist (for debugging)
                        $totalSubProducts = SubProduct::where('chassis_no', $value['id'])
                                                        ->where('flag', '!=', 2)
                                                        ->where('warehouse_id', $pos->warehouse_id)
                                                        ->where('booked', '=', 0)
                                                        ->count();
                        if ($totalSubProducts > 1) {
                            \Log::warning('Multiple SubProduct records found for product_no - using latest only to prevent duplicates', [
                                'product_no' => $value['id'],
                                'warehouse_id' => $pos->warehouse_id,
                                'total_subproducts' => $totalSubProducts,
                                'using_subproduct_id' => $subProductsArray->first()->id ?? 'none',
                                'cart_quantity' => $value['quantity'] ?? 0,
                                'pos_id' => $pos->id
                            ]);
                        }
                        
                        // Initialize free items tracker for this product if combo is BOGO
                        if (isset($value['compo_id']) && $value['compo_id'] != 0) {
                            $combo = ComboOffer::find($value['compo_id']);
                            if ($combo && $combo->type == 'bogo') {
                                $productKey = $value['id'] . '_' . $value['compo_id'];
                                if (!isset($freeItemsTracker[$productKey])) {
                                    $quantity = $value['quantity'] ?? 1;
                                    $subtotal = $value['subtotal'] ?? 0;
                                    $regularPrice = $value['price'] - ($value['price'] * ($value['discount'] / 100));
                                    
                                    // Calculate free items: subtotal = paid_items * regularPrice
                                    $paidItems = ($regularPrice > 0) ? round($subtotal / $regularPrice, 2) : 0;
                                    $freeItemsTracker[$productKey] = [
                                        'total_free' => max(0, $quantity - $paidItems),
                                        'allocated' => 0
                                    ];
                                }
                            }
                        }
                        
                        foreach($subProductsArray as $subproduct){
                            // $subproduct;
                            # for in the quantity and 

                            if ($qty_for_minuse<=0){
                                break;
                            }
                            $positems = new PosProduct();
                            $positems->pos_id = $pos->id;
                            $positems->product_id = $subproduct->product_id;
                            $positems->sub_product_id = $subproduct->id;
                            $positems->price = $value['price'];
                            $positems->discount = $value['discount'];
                            
                            // Calculate and store combo price if combo is applied
                            if ( $value['compo_id'] != 0){
                                $positems->compo_id = $value['compo_id'];
                                
                                // Get combo offer details
                                $combo = ComboOffer::find($value['compo_id']);
                                if ($combo) {
                                    $quantity = $value['quantity'] ?? 1;
                                    $subtotal = $value['subtotal'] ?? 0;
                                    
                                    // Calculate regular price after discount
                                    $regularPrice = $value['price'] - ($value['price'] * ($value['discount'] / 100));
                                    
                                    if ($combo->type == 'bogo') {
                                        // For BOGO (Buy X Get Y): Free items (cheapest) should have combo_price = 0
                                        // We'll calculate combo_price after quantity is set below
                                        // For now, mark that we need to calculate it
                                        $positems->_calculate_bogo_combo_price = true;
                                        $positems->_bogo_regular_price = $regularPrice;
                                        $positems->_bogo_product_key = $value['id'] . '_' . $value['compo_id'];
                                    } elseif ($combo->type == 'tiered_pricing') {
                                        // For tiered_pricing: combo_price = subtotal / quantity (effective price per item)
                                        if ($quantity > 0 && $subtotal > 0) {
                                            $positems->combo_price = $subtotal / $quantity;
                                        } else {
                                            $positems->combo_price = null;
                                        }
                                    } else {
                                        $positems->combo_price = null;
                                    }
                                } else {
                                    $positems->combo_price = null;
                                }
                            } else {
                                // No combo applied
                                $positems->combo_price = null;
                            }
                            
                            if ( $subproduct->price_rule_id){
                                $positems->price_list_id = $subproduct->price_rule_id;
                            }
                            
                            // CRITICAL FIX: Always use the cart quantity (qty_for_minuse) for POS item quantity
                            // The POS item quantity should reflect what the customer purchased, not what's available in SubProduct
                            // Since we're only using the latest SubProduct (limit 1), we should always use the full cart quantity
                                $positems->quantity = (int)$qty_for_minuse;
                            
                            // Update SubProduct inventory: reduce by the quantity sold
                            $newQuantity = max(0, $subproduct->quantity - (int)$qty_for_minuse);
                                $subproduct->update([
                                    'pos_id' => $pos->id,
                                    'quantity' => $newQuantity,
                                    'booked' => $newQuantity == 0 ? 3 : $subproduct->booked,
                                ]);
                                $subproduct->save();
                            ///////////////////////////////////////// u are here 
                            // MasterlistLeadger::addFree($subproduct->product_id->id,$request->warehouse_id,1,'BILL',$bill->id,\Auth::user()->creatorId());
                            ///////////////////////////////////////// u are here 
                            // Since we're only processing one SubProduct, set qty_for_minuse to 0 to exit loop
                            $qty_for_minuse = 0;
                            // $positems->quantity =  min(0,$subproduct->quantity - (int)$qty_for_minuse);
                            $positems->tax = $pos->tax_id;
                            // $positems->discount = $discount;
                            
                            // Calculate combo_price for BOGO after quantity is set
                            if (isset($positems->_calculate_bogo_combo_price) && $positems->_calculate_bogo_combo_price) {
                                $productKey = $positems->_bogo_product_key;
                                $regularPrice = $positems->_bogo_regular_price;
                                $currentQty = $positems->quantity;
                                
                                // Check if this subproduct gets free items
                                if (isset($freeItemsTracker[$productKey]) && $freeItemsTracker[$productKey]['total_free'] > 0) {
                                    $remainingFree = $freeItemsTracker[$productKey]['total_free'] - $freeItemsTracker[$productKey]['allocated'];
                                    
                                    if ($remainingFree > 0 && $currentQty > 0) {
                                        // This subproduct gets some free items (cheapest ones)
                                        $freeForThisSubproduct = min($remainingFree, $currentQty);
                                        $paidForThisSubproduct = $currentQty - $freeForThisSubproduct;
                                        
                                        // Update tracker
                                        $freeItemsTracker[$productKey]['allocated'] += $freeForThisSubproduct;
                                        
                                        // Store combo_price: weighted average (0 for free items, regular price for paid items)
                                        $positems->combo_price = ($paidForThisSubproduct * $regularPrice) / $currentQty;
                                    } else {
                                        // No free items left, all are paid
                                        $positems->combo_price = $regularPrice;
                                    }
                                } else {
                                    // No free items for this product, all are paid
                                    $positems->combo_price = $regularPrice;
                                }
                                
                                // Clean up temporary properties
                                unset($positems->_calculate_bogo_combo_price);
                                unset($positems->_bogo_regular_price);
                                unset($positems->_bogo_product_key);
                            }
                            
                            \Log::info('POS product payload before save', [
                                'pos_id' => $pos->id,
                                'pos_number' => $pos->pos_id ?? null,
                                'product_id' => $positems->product_id,
                                'sub_product_id' => $positems->sub_product_id,
                                'barcode' => $subproduct->product_no ?? ($value['id'] ?? $key),
                                'cart_key' => $key,
                                'quantity' => $positems->quantity,
                                'price' => $positems->price,
                                'discount' => $positems->discount,
                                'combo_id' => $positems->compo_id ?? null,
                            ]);

                            $positems->save();

                            \Log::info('POS product saved', [
                                'pos_product_id' => $positems->id,
                                'pos_id' => $pos->id,
                                'pos_number' => $pos->pos_id ?? null,
                                'product_id' => $positems->product_id,
                                'sub_product_id' => $positems->sub_product_id,
                                'barcode' => $subproduct->product_no ?? ($value['id'] ?? $key),
                                'quantity' => $positems->quantity,
                            ]);
                            
                            // Create stock movement for POS sale.
                            // Previously this was limited to "Qty product" only, which skipped consignment barcode sales.
                            $subProductParent = ProductService::with('category')->find($subproduct->product_id);
                            $sellQty = $positems->quantity;
                            $isQtyProduct = $subProductParent && $subProductParent->category && $subProductParent->category->type === "Qty product";
                            $isConsignment = $subproduct->isConsignment();

                            if ($subProductParent && ($isQtyProduct || $isConsignment)) {
                                // Default cost values (used for consignment or non-avg scenarios)
                                $avgCost = $subproduct->purchase_price ?? 0;
                                $lastAvg = $subproduct->purchase_price ?? 0;

                                // Check cost calculation method
                                $costCalculationMethod = $subProductParent->category->cost_calculation_method ?? 'avg';

                                // Only apply rolling average formula for Qty product.
                                if ($isQtyProduct && $costCalculationMethod === 'avg') {
                                    // Calculate average cost using formula:
                                    // Average Cost = ((Last Purchased Qty × Last Avg) - (Sell Qty × Last Avg)) ÷ (Last Purchased Qty - Sell Qty)
                                    
                                    // Get purchased bill IDs (sent bills only)
                                    $purchasedBillIds = \App\Models\Bill::whereNotIn('status', [0, 1, 2])
                                        ->where('created_by', \Auth::user()->creatorId())
                                        ->pluck('id')
                                        ->toArray();
                                    
                                    // Count purchased subproduct quantities (last purchased qty from sub product)
                                    $lastPurchasedQty = SubProduct::where('product_id', $subProductParent->id)
                                        ->whereIn('bill_id', $purchasedBillIds)
                                        ->where('flag', '!=', 0)
                                        ->whereNotNull('bill_id')
                                        ->sum('quantity') ?? 0;
                                    
                                    // Get last avg from parent product
                                    $lastAvg = ($subProductParent->avg_cost > 0) ? $subProductParent->avg_cost : ($subproduct->purchase_price ?? 0);
                                    
                                    // Calculate average cost using formula
                                    $remainingQty = $lastPurchasedQty - $sellQty;
                                    if ($remainingQty > 0) {
                                        $avgCost = (($lastPurchasedQty * $lastAvg) - ($sellQty * $lastAvg)) / $remainingQty;
                                    } else {
                                        $avgCost = 0;
                                    }
                                } else {
                                    // Use actual cost (purchase price from subproduct)
                                    $avgCost = $subproduct->purchase_price ?? 0;
                                    $lastAvg = $subproduct->purchase_price ?? 0;
                                }
                                
                                // Create stock movement record for each sold item
                                $stockMovement = new StockMovement();
                                $stockMovement->product_id = $subproduct->product_id;
                                $stockMovement->sub_product_id = $subproduct->id;
                                $stockMovement->invoice_id = null; // POS sale, not invoice
                                $stockMovement->bill_id = null; // POS sale, not bill
                                $stockMovement->pos_id = $pos->id; // POS ID
                                $stockMovement->qty_in = 0; // No stock in for sales
                                $stockMovement->qty_out = $sellQty; // Quantity sold
                                $stockMovement->avg_cost = $avgCost; // New calculated average cost
                                $stockMovement->cost_price = $lastAvg ?? ($subproduct->purchase_price ?? 0);
                                $stockMovement->activity = $isConsignment ? 'Sale via POS (Consignment)' : 'Sale via POS';
                                $stockMovement->use_id = $customer_id; // Customer ID for SALES
                                $stockMovement->item = $subproduct->id; // sub_product_id
                                $stockMovement->created_by = \Auth::user()->creatorId();
                                $stockMovement->save();
                                
                                // Update product average cost only for Qty product logic
                                if ($isQtyProduct) {
                                    $subProductParent->avg_cost = $avgCost;
                                    $subProductParent->save();
                                }
                            }
                            
                            // $subProductDB->update;
                            $product_id = $subproduct->product_id;
                        }
                        $product = ProductService::find($product_id);

                        # End Editting
                        // $product_id = $value['id'];

                        // $product = ProductService::whereId($product_id)->where('created_by', $user_id)->first();

                        // $original_quantity = ($product == null) ? 0 : (int)$product->quantity;

                        // $product_quantity = $original_quantity - $value['quantity'];
                        // if ($product != null && !empty($product)) {
                        //     ProductService::where('id', $product_id)->update(['quantity' => $product_quantity]);
                        // }

                        // $tax_id = ProductService::tax_id($product_id);

                        // Utility::warehouse_quantity('minus',$positems->quantity,$positems->product_id,$request->warehouse_name);
                        //Product Stock Report
                        // $type = 'pos';
                        // $type_id = $pos->id;
                        // StockReport::where('type','=','pos')->where('type_id' ,'=', $pos->id)->delete();
                        // $description = $positems->quantity.'  '.__(' quantity sold in pos').' '. \Auth::user()->posNumberFormat($pos->pos_id);
                        // Utility::addProductStock( $positems->product_id,$positems->quantity,$type,$description,$type_id);
                        // $itemAmount_purchase=0;
                        $totalTaxPrice = 0;
                        
                        // Calculate tax strictly based on the POS's stored tax_id
                        $taxes = \App\Models\Utility::tax($pos->tax_id);
                        if (!empty($pos->tax_id) && !empty($taxes)) {
                            foreach ($taxes as $tax) {
                                $taxPrice = \App\Models\Utility::taxRate($tax->rate, $value['subtotal'], 1, $discount);
                                $totalTaxPrice += $taxPrice;
                            }
                        }

                        $itemAmount = ($value['subtotal']) - ($discount) ;
                        
                        // Get the first subproduct from the array to calculate purchase price and expenses
                        $subproduct = $subProductsArray->first();
                        
                        // Calculate product cost - use avg_cost if > 0, otherwise use subproduct purchase_price
                        $product_cost = ($product->avg_cost > 0) ? $product->avg_cost : ($subproduct ? $subproduct->purchase_price : $product->purchase_price);
                        $itemAmount_purchase = $product_cost * $value['quantity'];
                        
                        // Calculate expenses if product type is 'product' and we have a subproduct
                        if ($product->type == 'product' && $subproduct) {
                            // Retrieve the chart account ID for the purchase
                            $purchaseAccountId = \App\Models\ProductServiceCategory::where('id', $product->category_id)->first()->purchase_account_id;
                            
                            // Calculate the sum of direct expenses related to this item's sub_product_id
                            // Only include expenses where chart_account_id matches the purchase_account_id
                            $directExpenseAmount = 0;
                            if ($subproduct->id && $purchaseAccountId) {
                                $directExpenseAmount = \App\Models\DirectExpenseItem::where('sub_product_id', $subproduct->id)
                                    ->where('chart_account_id', $purchaseAccountId)
                                    ->whereHas('directExpense', function ($query) {
                                        $query->where('created_by', \Auth::user()->creatorId());
                                    })
                                    ->sum('amount');
                            }
                            
                            // Calculate the sum of sell_price from car_accessory_request_items related to this item
                            $carAccessoryAmount = 0;
                            if ($subproduct->id) {
                                $carAccessoryAmount = \App\Models\CarAccessoryRequestItem::where(function ($query) use ($subproduct) {
                                    $query->where('car_id', $subproduct->id)
                                        ->orWhere('accessory_id', $subproduct->id);
                                })
                                    ->whereHas('request', function ($query) {
                                        $query->where('created_by', \Auth::user()->creatorId());
                                    })
                                    ->sum('sell_price');
                            }
                            
                            // Add direct expense amount and car accessory amount to the purchase amount
                            $itemAmount_purchase += $directExpenseAmount + $carAccessoryAmount;
                        }
                        
                        // $totalAmountDebit = $totalAmountDebit +  (($value['price'] * $value['quantity']) - ($discount)) ;

                         // Retrieve the chart account ID for the category
                        $categoryChartAccountId = \App\Models\ProductServiceCategory::where('id', $product->category_id)->first()->sale_account_id;
                        
                        $customer = Customer::where('id', $customer_id)->first();
                        
                        Utility::updateUserBalance('customer', $customer->id, $itemAmount + $totalTaxPrice, 'debit');


                        // Add entries to General Ledger
                        // Create a new entry for credit the category account
                        $newEntryCategory = new GeneralLedger();
                        $newEntryCategory->vid = $newVid;
                        $newEntryCategory->account = $categoryChartAccountId;
                        $newEntryCategory->type =  \Auth::user()->posNumberFormat($pos->pos_id);
                        $newEntryCategory->debit = 0; // Example value
                        $newEntryCategory->credit = $itemAmount ; // Example value
                        $newEntryCategory->ref_id = $pos->id;
                        $newEntryCategory->user_id = 0;
                        $newEntryCategory->sub_product_id = $subproduct ? $subproduct->id : null;
                        $newEntryCategory->created_by = \Auth::user()->creatorId();
					    $newEntryCategory->send_date = $pos->pos_date;
					    $newEntryCategory->reference = 'POS';
                        $newEntryCategory->save();
                        
                        // Get tax_id from stored POS record first, then fall back to defaults
                        $taxId = $pos->tax_id;
                        if (empty($taxId)) {
                            // Find first tax with rate 5 created by the company
                            $defaultTax = \App\Models\Tax::where('created_by', \Auth::user()->creatorId())
                                ->where('rate', 5)
                                ->first();
                            
                            if ($defaultTax) {
                                $taxId = $defaultTax->id;
                            } else {
                                // If no 5% tax exists, get the first tax for the company as fallback
                                $firstTax = \App\Models\Tax::where('created_by', \Auth::user()->creatorId())->first();
                                if ($firstTax) {
                                    $taxId = $firstTax->id;
                                }
                            }
                        }
                        
                        // Create tax ledger entry if tax_id is available
                        if(!empty($taxId)){
                            // Get tax from either session or default
                            $tax = \App\Models\Tax::where('id', $taxId)->first();
                            if ($tax) {
                                // Create a new entry credit for the tax account
                            $newEntryTax = new GeneralLedger();
                            $newEntryTax->vid = $newVid;
                                $newEntryTax->account = $tax->chart_account_id;
                            $newEntryTax->type =   \Auth::user()->posNumberFormat($pos->pos_id);
                            $newEntryTax->debit = 0; // Example value
                            $newEntryTax->credit = $totalTaxPrice; // Example value
                            $newEntryTax->ref_id = $pos->id;
                            $newEntryTax->user_id = 0;
                                $newEntryTax->sub_product_id = $subproduct ? $subproduct->id : null;
                            $newEntryTax->created_by = \Auth::user()->creatorId();
                            $newEntryTax->send_date = $pos->pos_date;
                            $newEntryTax->reference = 'POS';
                            $newEntryTax->save();
                            }
                        }
                        // Retrieve the chart account ID for the customer
                        $customerChartAccountId = $customer->chart_account_id;

                        // Create a new entry debit for the customer account
                        $newEntryCustomer = new GeneralLedger();
                        $newEntryCustomer->vid = $newVid;
                        $newEntryCustomer->account = $customerChartAccountId;
                        $newEntryCustomer->type =  \Auth::user()->posNumberFormat($pos->pos_id);
                        $newEntryCustomer->debit = $itemAmount + $totalTaxPrice; // Example value
                        $newEntryCustomer->credit = 0; // Example value
                        $newEntryCustomer->ref_id = $pos->id;
                        $newEntryCustomer->user_id = $customer_id;
                        $newEntryCustomer->sub_product_id = $subproduct ? $subproduct->id : null;
                        $newEntryCustomer->created_by = \Auth::user()->creatorId();
                        $newEntryCustomer->balance = $customer->balance;
                        $newEntryCustomer->send_date = $pos->pos_date;
					    $newEntryCustomer->reference = 'POS';
                        $newEntryCustomer->save();
                        $totalAmountVoucher += $itemAmount + $totalTaxPrice;
                        // Add records if product type is 'product'
                        if ($product->type == 'product') {
                            // Retrieve the chart account ID for the purchase
                            $purchaseAccountId = $product->category->purchase_account_id;
                            // Create a new entry for the purchase account (credit)
                            $newEntryCredit = new GeneralLedger();
                            $newEntryCredit->vid = $newVid;
                            $newEntryCredit->account = $purchaseAccountId;
                            $newEntryCredit->type =  \Auth::user()->posNumberFormat($pos->pos_id);
                            $newEntryCredit->debit = 0; // Example value
                            $newEntryCredit->credit = $itemAmount_purchase; // Example value
                            $newEntryCredit->ref_id = $pos->id;
                            $newEntryCredit->user_id = 0;
                            $newEntryCredit->sub_product_id = $subproduct ? $subproduct->id : null;
                            $newEntryCredit->created_by = \Auth::user()->creatorId();
                            $newEntryCredit->send_date = $pos->pos_date;
					        $newEntryCredit->reference = 'POS';
                            $newEntryCredit->save();
                            
                            // Retrieve the chart account ID for the expense
                            $expenseAccountId = $product->category->expense_account_id;

                            // Create a new entry for the expense account (debit)
                            $newEntryDebit = new GeneralLedger();
                            $newEntryDebit->vid = $newVid;
                            $newEntryDebit->account = $expenseAccountId;
                            $newEntryDebit->type = \Auth::user()->posNumberFormat($pos->pos_id);
                            $newEntryDebit->debit = $itemAmount_purchase; // Example value
                            $newEntryDebit->credit = 0; // Example value
                            $newEntryDebit->ref_id = $pos->id;
                            $newEntryDebit->user_id = 0;
                            $newEntryDebit->sub_product_id = $subproduct ? $subproduct->id : null;
                            $newEntryDebit->created_by = \Auth::user()->creatorId();
                            $newEntryDebit->send_date = $pos->pos_date;
					        $newEntryDebit->reference = 'POS';
                            $newEntryDebit->save();

                        }
                    }
                    
                    


                    $mainsubtotal = 0;
                    $sales        = [];
                    $tax_rate = 0;
                    $posTax = !empty($pos->tax_id) ? Tax::find($pos->tax_id) : null;
                    if ($posTax) {
                        $tax_rate = (float) $posTax->rate;
                    }

                    $sess = session()->get('pos');
                    foreach ($sess as $key => $value) {
                        $subtotal = $value['subtotal'];
                        $tax      = $subtotal ;
                        $sales['data'][$key]['price']      = Auth::user()->priceFormat($subtotal);
                        $sales['data'][$key]['tax']        = $tax_rate . '%';
                        $sales['data'][$key]['tax_amount'] = Auth::user()->priceFormat($tax); // delete
                        $sales['data'][$key]['subtotal']   = Auth::user()->priceFormat($value['subtotal']);
                        $text = '';
                        if ($value['compo_id'] != 0){
                            $compo = ComboOffer::find($value['compo_id']);
                            if ($compo->type == 'bogo'){
                                $text = 'buy: '.$compo->buy_quantity . '| get: '.$compo->get_quantity;
                            }else{
                                $text = 'buy: '.$compo->buy_quantity . '| for: '.$compo->tiered_price;
                            }
                        }
                        $sales['data'][$key]['compo_text'] = $text;
                        $mainsubtotal += $value['subtotal'];
                    }
                    $amount = $mainsubtotal;
                    $total= $mainsubtotal - $discount;
                    
                    // Use normalized payments array prepared at transaction start
                    $normalizedPayments = isset($normalizedPayments) && is_array($normalizedPayments) ? $normalizedPayments : [];
                    
                    // Calculate total payment amount from all payment methods (what customer actually paid)
                    $total_payment_amount = 0.0;
                    foreach ($normalizedPayments as $methodId => $paymentAmount) {
                        $total_payment_amount += (float)$paymentAmount;
                    }
                    
                    // Calculate voucher total for POS total calculation
                    $vouchers = session()->get('vouchers',[]);
                    $voucher_total = 0.0;
                    foreach ($vouchers as $key => $value) {
                        $voucher_total += $value['amount'];
                    }
                    
                    // Calculate POS total: subtotal + tax - discount - vouchers
                    // Calculate tax amount on subtotal (before discount)
                    $taxAmount = $mainsubtotal * ($tax_rate / 100);
                    // Round tax amount to 2 decimal places
                    $taxAmount = round($taxAmount, 2);
                    // Calculate final POS total (amount still to pay after vouchers)
                    $posTotal = $mainsubtotal + $taxAmount - $discount - $voucher_total;
                    // Round POS total to 2 decimal places (keep cents, do NOT round to whole number)
                    $posTotal = round($posTotal, 2);
                    // When voucher covers or exceeds the sale, nothing is left to pay
                    $posTotal = max(0, $posTotal);

                    // Create a separate PosPayment record for each payment method
                    $posPayments = []; // Store all created payments for later reference
                    
                    if (!empty($normalizedPayments) && is_array($normalizedPayments)) {
                        // Track remaining POS total to allocate across payment methods
                        $remainingPosTotal = $posTotal;
                        
                        foreach ($normalizedPayments as $methodId => $paymentAmount) {
                            $paymentAmount = (float)$paymentAmount;
                            if ($paymentAmount > 0) {
                                $methodId = (int) preg_replace('/\D/', '', (string) $methodId);
                                if ($methodId <= 0) {
                                    continue;
                                }
                                $posPayment = new PosPayment();
                                $posPayment->pos_id = $pos->id;
                                $posPayment->date = $request->date;
                                $posPayment->created_by = \Auth::user()->creatorId();
                                $posPayment->payment_method_id = $methodId;
                                
                                // Calculate amount allocated to this payment method (needed amount)
                                // This is the minimum of what user paid and what's still needed
                                $allocatedAmount = min($paymentAmount, $remainingPosTotal);
                                
                                // Calculate proportional discount for this payment method
                                // Distribute discount proportionally based on allocated amount
                                $paymentRatio = $posTotal > 0 ? ($allocatedAmount / $posTotal) : 0;
                                $methodDiscount = $discount * $paymentRatio;
                                
                                // Ensure exact-pay case stores same value in amount and total_user_payment.
                                // (Use tiny tolerance for float comparisons.)
                                if (abs($paymentAmount - $posTotal) < 0.00001) {
                                    $posPayment->amount = max(0, $paymentAmount);
                                } else {
                                    $posPayment->amount = max(0, $allocatedAmount);
                                }
                                // When voucher covers full sale (posTotal is 0), no payment; otherwise what user paid via this method
                                $posPayment->total_user_payment = ($posTotal == 0 ? 0 : $paymentAmount);
                                $posPayment->discount = $methodDiscount;
                                $posPayment->discount_amount = 0.0;
                                
                                $posPayment->save();
                                $posPayments[] = $posPayment; // Store for reference
                                
                                // Update remaining POS total
                                $remainingPosTotal -= $allocatedAmount;
                                
                                // If we've allocated all the POS total, break (no need to process remaining payments)
                                if ($remainingPosTotal <= 0) {
                                    break;
                                }
                            }
                        }
                    } else {
                        // If no payment methods provided, create a single payment record and assign default (Cash) method
                        $defaultPaymentMethod = PaymentMethod::where('warehouse_id', $warehouse_id)
                            ->where('name', 'LIKE', '%cash%')
                            ->first();
                        $posPayment = new PosPayment();
                        $posPayment->pos_id = $pos->id;
                        $posPayment->date = $request->date;
                        $posPayment->created_by = \Auth::user()->creatorId();
                        $posPayment->payment_method_id = $defaultPaymentMethod ? $defaultPaymentMethod->id : null;
                        // Set amount to POS total
                        $posPayment->amount = $posTotal;
                        // Set total_user_payment to POS total if no separate payment methods (customer paid exact amount)
                        $posPayment->total_user_payment = $posTotal;
                        $posPayment->discount = $discount;
                        $posPayment->discount_amount = 0.0;
                        $posPayment->save();
                        $posPayments[] = $posPayment; // Store for reference
                    }
                    
                    //add trans
                    $customer->total_paid = $customer->total_paid + $total + ($total*($tax_rate / 100));
                    $customer->save();
                    // TODO
                    $payBank = BankAccount::where('chart_account_id',ChartOfAccount::where('name','Petty Cash')->first()->id)->first();

                    Utility::updateUserBalance('customer', $customer_id, $total + ($total*($tax_rate / 100)), 'credit');
                    // return response()->json("this is a prove tha saving is complate ");
                    // Utility::bankAccountBalance($payBank->id, $total + ($total*($tax_rate / 100)), 'debit');
                    // return response()->json('hhhhhhhhh');
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

                    $vouchers = session()->get('vouchers',[]);
                    $voucher_total = 0.0;

                    // Calculate total amount with VAT
                    $total_with_vat = $total + ($total * ($tax_rate / 100));

                    // Process vouchers if any
                    $accountReceivablesId = ChartOfAccount::where('created_by', \Auth::user()->creatorId())
                        ->where('name', 'Account Receivables')
                        ->first()
                        ?->id;

                    foreach ($vouchers as $key => $value) {
                        $voucher = Voucher::find($key);
                        if ($voucher) {
                            $voucher->active = false;
                            $voucher->save();
                        }
                        if ($voucher->chart_of_account_id != 0 ){
                            $voucherChartAccountId = $voucher->chart_of_account_id;
                        }else{
                            $voucherChartAccountId = ChartOfAccount::where('created_by',\Auth::user()->creatorId())->where('name', 'Gift Voucher Liability')->first()->id ;
                        }
                        
                        // Debit voucher account (changed from credit to debit)
                        $newEntryVoucherDebit = new GeneralLedger();
                        $newEntryVoucherDebit->vid = $newVid;
                        $newEntryVoucherDebit->account = $voucherChartAccountId; 
                        $newEntryVoucherDebit->type =  \Auth::user()->posNumberFormat($pos->pos_id) .'Voucher Payment_'. (string)$key;
                        $newEntryVoucherDebit->debit = $value['amount']; // Debit voucher account
                        $newEntryVoucherDebit->credit = 0;
                        $newEntryVoucherDebit->ref_id = $pos->id;
                        // If voucher account is Account Receivables, tie ledger line to customer.
                        // Otherwise keep it generic (user_id = 0).
                        $newEntryVoucherDebit->user_id = ((int)($accountReceivablesId ?? 0) > 0 && (int)$voucherChartAccountId === (int)$accountReceivablesId)
                            ? (int)($customer->id ?? $customer_id)
                            : 0;
                        $newEntryVoucherDebit->created_by = \Auth::user()->creatorId();
                        $newEntryVoucherDebit->send_date = $pos->pos_date;
                        $newEntryVoucherDebit->reference = 'POS Payment';
                        $newEntryVoucherDebit->save();
                        
                        $voucher_total += $value['amount'];
                    }
                    
                    $needs_to_pay = $total_with_vat - $voucher_total;
                    
                    // If vouchers are used, always credit customer account with voucher amount
                    if ($voucher_total > 0) {
                        // Credit customer account with voucher amount
                        $accountCustomer = $customer->chart_account_id;
                        $newEntryCustomerVoucherCredit = new GeneralLedger();
                        $newEntryCustomerVoucherCredit->vid = $newVid;
                        $newEntryCustomerVoucherCredit->account = $accountCustomer;
                        $newEntryCustomerVoucherCredit->type = \Auth::user()->posNumberFormat($pos->pos_id) . ' Voucher Payment';
                        $newEntryCustomerVoucherCredit->debit = 0;
                        $voucherAppliedToSale = min($voucher_total, $total_with_vat);
                        $newEntryCustomerVoucherCredit->credit = $voucherAppliedToSale; // Credit voucher amount to customer
                        $newEntryCustomerVoucherCredit->ref_id = $pos->id;
                        $newEntryCustomerVoucherCredit->user_id = $customer_id;
                        $newEntryCustomerVoucherCredit->created_by = \Auth::user()->creatorId();
                        $newEntryCustomerVoucherCredit->balance = $customer->balance;
                        $newEntryCustomerVoucherCredit->send_date = $pos->pos_date;
                        $newEntryCustomerVoucherCredit->reference = 'POS Payment';
                        $newEntryCustomerVoucherCredit->save();

                        // When voucher value exceeds amount due, credit Discount Earned (excess voucher = discount)
                        $discountAmount = max(0, $voucher_total - $total_with_vat);
                        if ($discountAmount > 0) {
                            $discountEarnedAccount = ChartOfAccount::where('created_by', \Auth::user()->creatorId())
                                ->where('name', 'Discounts Received')
                                ->first();
                            if ($discountEarnedAccount) {
                                $newEntryDiscountEarned = new GeneralLedger();
                                $newEntryDiscountEarned->vid = $newVid;
                                $newEntryDiscountEarned->account = $discountEarnedAccount->id;
                                $newEntryDiscountEarned->type = \Auth::user()->posNumberFormat($pos->pos_id) . ' Voucher Payment';
                                $newEntryDiscountEarned->debit = 0;
                                $newEntryDiscountEarned->credit = $discountAmount;
                                $newEntryDiscountEarned->ref_id = $pos->id;
                                $newEntryDiscountEarned->user_id = $customer_id;
                                $newEntryDiscountEarned->created_by = \Auth::user()->creatorId();
                                $newEntryDiscountEarned->send_date = $pos->pos_date;
                                $newEntryDiscountEarned->reference = 'POS Payment';
                                $newEntryDiscountEarned->save();
                            }
                        }
                    }

                    // If receipt value is less than or equal to voucher amount, only process voucher transactions
                    if ($total_with_vat <= $voucher_total && $voucher_total > 0) {
                        // Skip payment method processing - vouchers cover the full amount
                        $total_customer_pay = 0.0;
                    } else {
                    $cash_method = PaymentMethod::where('warehouse_id', $warehouse_id)
                                                        ->where('name', 'LIKE', '%cash%')
                                                        ->first();
                    # this is use for return mony from cash
                    $whare_house_cash_account = $cash_method ? $cash_method->bankAccount->chartAccount : null;
                    $total_customer_pay = 0.0;
                    
                    // Note: Payment methods are now stored as separate PosPayment records above
                    // The pos_payment_methods table is kept for backward compatibility if needed
                    // Each payment method now has its own PosPayment record with payment_method_id and amount
                    
                    // Retrieve the chart account ID for the bank account
                    // Use normalized payments for ledger entries as well
                    $normalizedPayments = isset($normalizedPayments) && is_array($normalizedPayments) ? $normalizedPayments : [];
                    if (!empty($normalizedPayments)) {
                        // Calculate total payment amount
                        foreach ($normalizedPayments as $key => $value) {
                            $total_customer_pay += (float)$value;
                        }
                        
                        // When there are vouchers, handle transactions differently
                        if ($voucher_total > 0) {
                            // Process each payment method and credit respective bank accounts
                            foreach ($normalizedPayments as $key => $value) {
                                $payment_amount = (float)$value;
                                if ($payment_amount <= 0) {
                                    continue;
                                }
                                
                                $methodKey = (int) preg_replace('/\D/', '', (string) $key);
                                $P_method = $methodKey > 0 ? PaymentMethod::find($methodKey) : null;
                                if (!$P_method || !$P_method->bankAccount) {
                                    continue;
                                }
                                $chartAccountId = $P_method->bankAccount->chartAccount ?? null;
                                if (!$chartAccountId) {
                                    continue;
                                }
                                // Credit payment method account (payment received)
                                $newEntryPaymentCredit = new GeneralLedger();
                                $newEntryPaymentCredit->vid = $newVid;
                                $newEntryPaymentCredit->account = $chartAccountId->id;
                                $newEntryPaymentCredit->type = \Auth::user()->posNumberFormat($pos->pos_id) . ' Payment';
                                $newEntryPaymentCredit->debit = $payment_amount;
                                $newEntryPaymentCredit->credit = 0; // Credit payment amount
                                $newEntryPaymentCredit->ref_id = $pos->id;
                                $newEntryPaymentCredit->user_id = 0;
                                $newEntryPaymentCredit->created_by = \Auth::user()->creatorId();
                                $newEntryPaymentCredit->send_date = $pos->pos_date;
                                $newEntryPaymentCredit->reference = 'POS Payment';
                                $newEntryPaymentCredit->save();
                            }
                            
                            // Credit customer account with extra payment amount (beyond voucher)
                            // Note: Voucher amount was already credited above
                            if ($needs_to_pay > 0 && $total_customer_pay > 0) {
                                $accountCustomer = $customer->chart_account_id;
                                $newEntryCustomerPaymentCredit = new GeneralLedger();
                                $newEntryCustomerPaymentCredit->vid = $newVid;
                                $newEntryCustomerPaymentCredit->account = $accountCustomer;
                                $newEntryCustomerPaymentCredit->type = \Auth::user()->posNumberFormat($pos->pos_id) . ' Payment';
                                $newEntryCustomerPaymentCredit->debit = 0;
                                $newEntryCustomerPaymentCredit->credit = $total_customer_pay; // Credit extra payment amount
                                $newEntryCustomerPaymentCredit->ref_id = $pos->id;
                                $newEntryCustomerPaymentCredit->user_id = $customer_id;
                                $newEntryCustomerPaymentCredit->created_by = \Auth::user()->creatorId();
                                $newEntryCustomerPaymentCredit->balance = $customer->balance;
                                $newEntryCustomerPaymentCredit->send_date = $pos->pos_date;
                                $newEntryCustomerPaymentCredit->reference = 'POS Payment';
                                $newEntryCustomerPaymentCredit->save();
                            }
                        } else {
                            // No vouchers - use original payment logic
                            foreach ($normalizedPayments as $key => $value) {
                                $methodKey = (int) preg_replace('/\D/', '', (string) $key);
                                $P_method = $methodKey > 0 ? PaymentMethod::find($methodKey) : null;
                                if (!$P_method || !$P_method->bankAccount) {
                                    continue;
                                }
                                $chartAccountId = $P_method->bankAccount->chartAccount ?? null;
                                if (!$chartAccountId) {
                                    continue;
                                }
                                if ($needs_to_pay == 0 && $value == 0 ){
                                    continue;
                                }
                                $value = (float)$value;
                                if($value <= $needs_to_pay){
                                    // Create a new entry for the bank account (debit)
                                    $newEntryCredit = new GeneralLedger();
                                    $newEntryCredit->vid = $newVid;
                                    $newEntryCredit->account = $chartAccountId->id;
                                    $newEntryCredit->type =  \Auth::user()->posNumberFormat($pos->pos_id) .' Payment';
                                    $newEntryCredit->debit =$value;
                                    $newEntryCredit->credit = 0;
                                    $newEntryCredit->ref_id = $pos->id;
                                    $newEntryCredit->user_id = 0;
                                    $newEntryCredit->created_by = \Auth::user()->creatorId();
                                    $newEntryCredit->send_date = $pos->pos_date;
                                    $newEntryCredit->reference = 'POS Payment';
                                    $newEntryCredit->save();

                                    // Retrieve the chart account ID for the customer
                                    $accountCustomer = $customer->chart_account_id;

                                    // Create a new entry for the customer account (credit)
                                    $newEntryDebit = new GeneralLedger();
                                    $newEntryDebit->vid = $newVid;
                                    $newEntryDebit->account = $accountCustomer;
                                    $newEntryDebit->type =  \Auth::user()->posNumberFormat($pos->pos_id) .' Payment';
                                    $newEntryDebit->debit = 0;
                                    $newEntryDebit->credit = $value;
                                    $newEntryDebit->ref_id = $pos->id;
                                    $newEntryDebit->user_id = $customer_id;
                                    $newEntryDebit->created_by = \Auth::user()->creatorId();
                                    $newEntryDebit->balance = $customer->balance;
                                    $newEntryDebit->send_date = $pos->pos_date;
                                    $newEntryDebit->reference = 'POS Payment';
                                    $newEntryDebit->save();

                                    $needs_to_pay = $needs_to_pay - $value;
                                }else{
                                    $newEntryCredit = new GeneralLedger();
                                    $newEntryCredit->vid = $newVid;
                                    $newEntryCredit->account = $chartAccountId->id;
                                    $newEntryCredit->type =  \Auth::user()->posNumberFormat($pos->pos_id) .' Payment';
                                    $newEntryCredit->debit =$value;
                                    $newEntryCredit->credit = 0;
                                    $newEntryCredit->ref_id = $pos->id;
                                    $newEntryCredit->user_id = 0;
                                    $newEntryCredit->created_by = \Auth::user()->creatorId();
                                    $newEntryCredit->send_date = $pos->pos_date;
                                    $newEntryCredit->reference = 'POS';
                                    $newEntryCredit->save();

                                    // Retrieve the chart account ID for the customer
                                    $accountCustomer = $customer->chart_account_id;

                                    // Create a new entry for the customer account (credit)
                                    $newEntryDebit = new GeneralLedger();
                                    $newEntryDebit->vid = $newVid;
                                    $newEntryDebit->account = $accountCustomer;
                                    $newEntryDebit->type =  \Auth::user()->posNumberFormat($pos->pos_id) .' Payment';
                                    $newEntryDebit->debit = 0;
                                    $newEntryDebit->credit = $needs_to_pay;
                                    $newEntryDebit->ref_id = $pos->id;
                                    $newEntryDebit->user_id = $customer_id;
                                    $newEntryDebit->created_by = \Auth::user()->creatorId();
                                    $newEntryDebit->balance = $customer->balance;
                                    $newEntryDebit->send_date = $pos->pos_date;
                                    $newEntryDebit->reference = 'POS';
                                    $newEntryDebit->save();

                                    $newEntryReturnCredit = new GeneralLedger();
                                    $newEntryReturnCredit->vid = $newVid;
                                    $newEntryReturnCredit->account = $whare_house_cash_account->id;
                                    $newEntryReturnCredit->type =  \Auth::user()->posNumberFormat($pos->pos_id) .' Payment';
                                    $newEntryReturnCredit->debit =0;
                                    $newEntryReturnCredit->credit = $value - $needs_to_pay;
                                    $newEntryReturnCredit->ref_id = $pos->id;
                                    $newEntryReturnCredit->user_id = 0;
                                    $newEntryReturnCredit->created_by = \Auth::user()->creatorId();
                                    $newEntryReturnCredit->send_date = $pos->pos_date;
                                    $newEntryReturnCredit->reference = 'POS';
                                    $newEntryReturnCredit->save();
                                    $needs_to_pay = 0;
                                }
                            }
                        }
                    } else {
                        // No payment breakdown sent: we already created one PosPayment with total. Create ledger entries using default (Cash) method.
                        if ($needs_to_pay > 0) {
                            $cash_method = PaymentMethod::where('warehouse_id', $warehouse_id)
                                ->where('name', 'LIKE', '%cash%')
                                ->first();
                            if ($cash_method && $cash_method->bankAccount) {
                                $chartAccountId = $cash_method->bankAccount->chartAccount ?? null;
                                if ($chartAccountId) {
                                    $newEntryCredit = new GeneralLedger();
                                    $newEntryCredit->vid = $newVid;
                                    $newEntryCredit->account = $chartAccountId->id;
                                    $newEntryCredit->type = \Auth::user()->posNumberFormat($pos->pos_id) . ' Payment';
                                    $newEntryCredit->debit = $needs_to_pay;
                                    $newEntryCredit->credit = 0;
                                    $newEntryCredit->ref_id = $pos->id;
                                    $newEntryCredit->user_id = 0;
                                    $newEntryCredit->created_by = \Auth::user()->creatorId();
                                    $newEntryCredit->send_date = $pos->pos_date;
                                    $newEntryCredit->reference = 'POS Payment';
                                    $newEntryCredit->save();

                                    $accountCustomer = $customer->chart_account_id;
                                    $newEntryDebit = new GeneralLedger();
                                    $newEntryDebit->vid = $newVid;
                                    $newEntryDebit->account = $accountCustomer;
                                    $newEntryDebit->type = \Auth::user()->posNumberFormat($pos->pos_id) . ' Payment';
                                    $newEntryDebit->debit = 0;
                                    $newEntryDebit->credit = $needs_to_pay;
                                    $newEntryDebit->ref_id = $pos->id;
                                    $newEntryDebit->user_id = $customer_id;
                                    $newEntryDebit->created_by = \Auth::user()->creatorId();
                                    $newEntryDebit->balance = $customer->balance;
                                    $newEntryDebit->send_date = $pos->pos_date;
                                    $newEntryDebit->reference = 'POS Payment';
                                    $newEntryDebit->save();
                                }
                            }
                        }
                    }
                    } // End of else block for payment processing when receipt value > voucher amount
                    
                    if ($total_customer_pay > 0){
                        session()->put('total_customer_pay', $total_customer_pay);
                    }
                    
                    // Verify POS still exists before committing transaction
                    $posCheck = Pos::find($pos->id);
                    if (!$posCheck) {
                        DB::rollBack();
                        \Log::error('POS Missing Before Commit', [
                            'pos_id' => $pos->pos_id,
                            'pos_db_id' => $pos->id
                        ]);
                        return response()->json([
                            'code' => 500,
                            'error' => __('POS transaction was lost during processing. Please try again.')
                        ], 500);
                    }
                    
                    // Refresh POS instance to ensure we have latest data
                    $pos->refresh();
                    
                    \Log::info('Committing POS Transaction', [
                        'pos_id' => $pos->pos_id,
                        'pos_db_id' => $pos->id
                    ]);
                    
                    DB::commit();
                    
                    // Verify POS exists after commit - use fresh query to bypass any caching
                    $posAfterCommit = Pos::withoutGlobalScopes()->find($pos->id);
                    if (!$posAfterCommit) {
                        \Log::error('POS Missing After Commit', [
                            'pos_id' => $pos->pos_id,
                            'pos_db_id' => $pos->id,
                            'created_by' => $user_id
                        ]);
                        return response()->json([
                            'code' => 500,
                            'error' => __('POS transaction was not saved. Please contact support.')
                        ], 500);
                    }
                    
                    // Check if POS was soft-deleted
                    if ($posAfterCommit->trashed()) {
                        \Log::error('POS Was Soft-Deleted After Commit', [
                            'pos_id' => $pos->pos_id,
                            'pos_db_id' => $pos->id,
                            'deleted_at' => $posAfterCommit->deleted_at
                        ]);
                        return response()->json([
                            'code' => 500,
                            'error' => __('POS transaction was deleted immediately after creation. Please contact support.')
                        ], 500);
                    }
                    
                    // Final verification - query directly from database
                    $posFinalCheck = DB::table('pos')
                        ->where('id', $pos->id)
                        ->where('created_by', $user_id)
                        ->whereNull('deleted_at')
                        ->first();
                    
                    if (!$posFinalCheck) {
                        \Log::error('POS Final Check Failed - Not Found in Database', [
                            'pos_id' => $pos->pos_id,
                            'pos_db_id' => $pos->id,
                            'created_by' => $user_id
                        ]);
                        return response()->json([
                            'code' => 500,
                            'error' => __('POS transaction verification failed. Please contact support.')
                        ], 500);
                    }
                    
                    \Log::info('POS Successfully Saved and Verified', [
                        'pos_id' => $pos->pos_id,
                        'pos_db_id' => $pos->id,
                        'database_pos_id' => $posFinalCheck->pos_id ?? 'null',
                        'formatted_pos_id' => $user->posNumberFormat($pos->pos_id),
                        'preview_pos_id_was_used' => isset($previewPosId) && $previewPosId == $pos_id
                    ]);
                    
                    // Log POS order creation
                    PosLog::logAction('create_order', [
                        'type' => 'pos',
                        'reference_id' => $pos->id,
                        'pos_id' => $pos->id,
                        'warehouse_id' => $warehouse_id,
                        'customer_id' => $customer_id,
                        'new_value' => [
                            'pos_id' => $pos->pos_id,
                            'total' => $total,
                            'tax_rate' => $tax_rate,
                            'discount' => $discount,
                            'voucher_total' => $voucher_total,
                            'total_customer_pay' => $total_customer_pay,
                            'items_count' => count($sales),
                        ],
                        'description' => "POS order created with POS ID: {$pos->pos_id}",
                    ]);
                    
                    // Return the actual saved POS ID (formatted)
                    $formattedPosId = $user->posNumberFormat($pos->pos_id);
                    \Log::info('Returning POS ID in response', [
                        'numeric_pos_id' => $pos->pos_id,
                        'formatted_pos_id' => $formattedPosId,
                        'pos_db_id' => $pos->id
                    ]);
                    
                    return response()->json(
                        [
                            'code' => 200,
                            'success' => __('Payment completed successfully!'),
                            'pos_id' => $formattedPosId, // Return formatted pos_id (e.g., "#POS00426")
                            'pos_id_numeric' => $pos->pos_id, // Return numeric version from database
                            'pos_db_id' => $pos->id, // Return database ID for reference
                        ]
                    );
                }
            } else {
                DB::rollBack();
                return response()->json([
                    'code' => 400,
                    'error' => __('No items in cart')
                ], 400);
            }

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('POS Store Error: ' . $e->getMessage());
                \Log::error('POS Store Error Trace: ' . $e->getTraceAsString());
                return response()->json([
                    'code' => 500,
                    'error' => __('Error saving POS transaction: ') . $e->getMessage()
                ], 500);
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show($ids)
    {

        if(\Auth::user()->can('manage pos'))
        {
            try {
                $id = Crypt::decrypt($ids);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Pos Not Found.'));
            }

            $id   = Crypt::decrypt($ids);

            $pos = Pos::find($id);
            // dd($id);

            if($pos->created_by == \Auth::user()->creatorId())
            {
                $posPayment = PosPayment::where('pos_id', $pos->id)->first();
                $customer = $pos->customer;
                $iteams = $pos->items()->with(['product', 'sub_product.productService'])->get();
                $tax_bill = $pos->tax_id ;
                $totalTaxPrice = 0;
                if(!empty($pos->tax_id)){
                    $taxes = explode(",", $pos->tax_id);
                    foreach ($taxes as $tax) {
                                $taxModel = Tax::where('id', $tax)->first();
                                if ($taxModel) {
                                    $taxPrice = $taxModel->rate;
                                    $totalTaxPrice += $taxPrice;
                                }
                            }
                }
                $totalPay = 0;
                foreach($iteams as $it){
                    $totalPay+=$it->price;
                }
                $totalPay = ($totalPay-$pos->discount) + (($totalPay-$pos->discount)*$totalTaxPrice);
                
                // Get payment methods from PosPayment records (more accurate)
                $paymentMethods = [];
                $posPayments = PosPayment::where('pos_id', $pos->id)
                    ->whereNotNull('payment_method_id')
                    ->get();
                
                foreach($posPayments as $posPayment) {
                    if($posPayment->payment_method_id && $posPayment->amount > 0) {
                        $paymentMethod = PaymentMethod::find($posPayment->payment_method_id);
                        if($paymentMethod) {
                            // Check if payment method already exists, if so add to amount
                            $found = false;
                            foreach($paymentMethods as &$pm) {
                                if($pm['id'] == $paymentMethod->id) {
                                    $pm['amount'] += $posPayment->amount;
                                    $found = true;
                                    break;
                                }
                            }
                            if(!$found) {
                                $paymentMethods[] = [
                                    'id' => $paymentMethod->id,
                                    'name' => $paymentMethod->name,
                                    'amount' => $posPayment->amount
                                ];
                            }
                        }
                    }
                }
                
                // Fallback: Get payment methods from GeneralLedger entries if no PosPayment records found
                if(empty($paymentMethods)) {
                    $generalLedgerEntries = GeneralLedger::where('ref_id', $pos->id)
                        ->whereIn('reference', ['POS_payment', 'POS'])
                        ->where('debit', '>', 0)
                        ->where('user_id', 0)
                        ->get();
                    
                    foreach($generalLedgerEntries as $entry) {
                        $chartAccount = ChartOfAccount::find($entry->account);
                        if($chartAccount) {
                            $bankAccount = BankAccount::where('chart_account_id', $chartAccount->id)->first();
                            if($bankAccount) {
                                $paymentMethod = PaymentMethod::where('bank_account_id', $bankAccount->id)
                                    ->where('warehouse_id', $pos->warehouse_id)
                                    ->first();
                                if($paymentMethod) {
                                    // Check if payment method already exists, if so add to amount
                                    $found = false;
                                    foreach($paymentMethods as &$pm) {
                                        if($pm['id'] == $paymentMethod->id) {
                                            $pm['amount'] += $entry->debit;
                                            $found = true;
                                            break;
                                        }
                                    }
                                    if(!$found) {
                                        $paymentMethods[] = [
                                            'id' => $paymentMethod->id,
                                            'name' => $paymentMethod->name,
                                            'amount' => $entry->debit
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Get detailed payment method records for the new section
                $posPaymentMethods = PosPayment::where('pos_id', $pos->id)
                    ->whereNotNull('payment_method_id')
                    ->where('amount', '>', 0)
                    ->get();
                
                // Get vouchers used in this POS from GeneralLedger entries
                $vouchers = [];
                $vouchersWithDetails = [];
                $voucherLedgerEntries = GeneralLedger::where('ref_id', $pos->id)
                    ->where('reference', 'POS Payment')
                    ->where('type', 'LIKE', '%Voucher Payment_%')
                    ->where('debit', '>', 0)
                    ->get();
                
                foreach($voucherLedgerEntries as $entry) {
                    // Extract voucher ID from type field (format: POS_IDVoucher Payment_VOUCHER_ID)
                    if(preg_match('/Voucher Payment_(\d+)/', $entry->type, $matches)) {
                        $voucherId = $matches[1];
                        if(!isset($vouchers[$voucherId])) {
                            $voucher = Voucher::find($voucherId);
                            if($voucher) {
                                $vouchers[$voucherId] = [
                                    'id' => $voucher->id,
                                    'amount' => $entry->debit
                                ];
                                $vouchersWithDetails[$voucherId] = [
                                    'id' => $voucher->id,
                                    'amount' => $voucher->amount,
                                    'valid_until' => $voucher->valid_until,
                                    'active' => $voucher->active,
                                ];
                            }
                        }
                    }
                }
                
                // Get logs related to this POS
                $logs = PosLog::where('type', 'pos')
                    ->where('reference_id', $pos->id)
                    ->where('created_by', \Auth::user()->creatorId())
                    ->with('user')
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);
                
                return view('pos.view', compact('pos', 'customer','iteams','posPayment','totalTaxPrice','totalPay','paymentMethods','vouchers','vouchersWithDetails','posPaymentMethods','logs'));
            }
            else
            {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function updatePosDate(Request $request, $id)
    {
        $canChangePosDate = \Auth::user()->can('change pos date')
            || in_array(\Auth::user()->type, ['company', 'super admin'], true);

        if (!$canChangePosDate) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $request->validate([
            'pos_date' => 'required|date',
        ]);

        $pos = Pos::find($id);

        if (!$pos) {
            return redirect()->back()->with('error', __('POS not found.'));
        }

        if ((int) $pos->created_by !== (int) \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        DB::beginTransaction();
        try {
            $newPosDate = Carbon::parse($request->pos_date)->format('Y-m-d');
            $oldPosDate = $pos->pos_date;

            $pos->pos_date = $newPosDate;
            $pos->save();

            GeneralLedger::where('ref_id', $pos->id)
                ->whereIn('reference', ['POS', 'POS Payment', 'POS_payment'])
                ->update(['send_date' => $newPosDate]);

            $creatorId = (int) \Auth::user()->creatorId();
            StockMovement::where('pos_id', $pos->id)
                ->where('created_by', $creatorId)
                ->orderBy('id')
                ->chunkById(200, function ($movements) use ($newPosDate) {
                    foreach ($movements as $sm) {
                        $oldTs = Carbon::parse($sm->created_at);
                        $sm->created_at = Carbon::parse($newPosDate)->setTime(
                            (int) $oldTs->format('H'),
                            (int) $oldTs->format('i'),
                            (int) $oldTs->format('s')
                        );
                        $sm->save();
                    }
                });

            PosLog::create([
                'type' => 'pos',
                'reference_id' => $pos->id,
                'user_id' => \Auth::id(),
                'created_by' => \Auth::user()->creatorId(),
                'description' => sprintf(
                    'POS date updated from %s to %s; synced ledger send_date and stock movement created_at.',
                    (string) $oldPosDate,
                    (string) $newPosDate
                ),
            ]);

            DB::commit();

            return redirect()->back()->with('success', __('POS date, ledger send date, and stock movement dates updated successfully.'));
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('POS date update error: ' . $th->getMessage(), [
                'pos_id' => $id,
                'user_id' => \Auth::id(),
            ]);

            return redirect()->back()->with('error', __('Failed to update POS date.'));
        }
    }

    function invoicePosNumber()
    {
        // Allow access if user has either 'manage pos' or 'add pos' permission
        $hasPosPermission = Auth::user()->can('manage pos') || Auth::user()->can('add pos');
        if ($hasPosPermission)
        {
            // Get latest POS excluding soft-deleted records
            $latest = Pos::where('created_by', '=', \Auth::user()->creatorId())
                ->orderBy('pos_id', 'desc')
                ->first();

            return $latest ? $latest->pos_id + 1 : 1;
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function updateDiscount(Request $request)
    {
        $cart = session()->get('pos', []);
        $vouchers = session()->get('vouchers', []);
        $productId = $request->input('product_id');
        $discount = $request->input('item_discount');

        if (isset($cart[$productId])) {
            $cart[$productId]['discount'] = $discount;
            # this need to fix 
            $quantity_new = $cart[$productId]['quantity'];
            if ($cart[$productId]['compo_id'] != 0){
                $compo = ComboOffer::find($cart[$productId]['compo_id']);
                $bundleSize = $compo->get_quantity + $compo->buy_quantity;
                $quantity_new = ($compo->buy_quantity*(int)($quantity_new / $bundleSize)) 
                                + (int)($quantity_new % $bundleSize);
            }
            $new_subtotal  =$cart[$productId]['price']  * $quantity_new;
            $cart[$productId]['subtotal'] = ($new_subtotal - ($new_subtotal*($discount/100))) ;
        }
        
        $voucher_total = 0.0;
        foreach ($vouchers as $key => $value) {
            $voucher_total += $value['amount'];
        }

        $total = 0.0;

        if (is_iterable($cart)) {
            foreach ($cart as $item) {
                $subtotal = $item['subtotal'] ?? 0;
                $total += (float) $subtotal;
            }
        }
        
        session()->put('pos', $cart);
        // return response()->json($cart);
        $cart['total_sum'] = $total;
        $cart['voucher_amoutn'] = $voucher_total;
        $cart[$productId]['subtotal']  = (float)($cart[$productId]['subtotal'] );
        
        return response()->json($cart);



    }


    function report(Request $request)
    {
        if(\Auth::user()->can('manage pos') || \Auth::user()->can('add pos'))
        {
            $user = \Auth::user();
            $creatorId = $user->creatorId();
            $selectedWarehouseId = $request->input('warehouse_id');
            $selectedCashierId = $request->input('cashier_id');
            
            // Build query (avoid N+1 by pre-aggregating sums)
            $query = Pos::where('created_by', '=', $creatorId)
                ->select([
                    'id',
                    'pos_id',
                    'customer_id',
                    'warehouse_id',
                    'pos_date',
                    'created_by',
                    'user_id',
                    'tax_id',
                    'discount',
                ])
                ->with([
                    'customer:id,name',
                    'warehouse:id,name',
                    'cashier:id,name',
                    // Needed for subtotal/discount/tax calculations (but keep payload small)
                    'items:id,pos_id,quantity,price,discount,compo_id,combo_price',
                ])
                ->withSum('items as items_qty_sum', 'quantity')
                ->withSum(['payments as actual_paid_sum' => function ($q) {
                    $q->where('amount', '>', 0);
                }], 'amount');
            
            // Filter by user's assigned warehouses for user accounts
            if ($user->type != 'company' && $user->type != 'super admin') {
                // Get user's assigned warehouse IDs
                $assignedWarehouseIds = $user->warehouses()->pluck('warehouses.id')->toArray();
                
                if (!empty($assignedWarehouseIds)) {
                    // User has assigned warehouses - only show POS from those warehouses
                    $query->whereIn('warehouse_id', $assignedWarehouseIds);
                } else {
                    // No assigned warehouses - show empty result
                    $query->whereRaw('1 = 0'); // Always false condition
                }
            }
            
            // Filter by date range - default to today if no dates provided
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            
            // If no dates provided, default to today
            if (!$startDate && !$endDate) {
                $today = date('Y-m-d');
                $startDate = $today;
                $endDate = $today;
            }
            
            if ($startDate && $endDate) {
                $query->whereBetween('pos_date', [$startDate, $endDate]);
            } elseif ($startDate) {
                $query->where('pos_date', '>=', $startDate);
            } elseif ($endDate) {
                $query->where('pos_date', '<=', $endDate);
            }

            if (!empty($selectedWarehouseId)) {
                $query->where('warehouse_id', $selectedWarehouseId);
            }

            if (!empty($selectedCashierId)) {
                $query->where('user_id', $selectedCashierId);
            }
            
            $posPayments = $query->orderBy('pos_date', 'desc')->orderBy('id', 'desc')->get();
            
            // Store filters for view
            $filters = [
                'start_date' => $startDate ?? '',
                'end_date' => $endDate ?? '',
                'warehouse_id' => $selectedWarehouseId ?? '',
                'cashier_id' => $selectedCashierId ?? '',
            ];

            $warehouses = warehouse::where('created_by', $creatorId)
                ->orderBy('name')
                ->pluck('name', 'id');
            if ($user->type != 'company' && $user->type != 'super admin') {
                $assignedWarehouseIds = $user->warehouses()->pluck('warehouses.id')->toArray();
                if (!empty($assignedWarehouseIds)) {
                    $warehouses = warehouse::whereIn('id', $assignedWarehouseIds)
                        ->orderBy('name')
                        ->pluck('name', 'id');
                } else {
                    $warehouses = collect();
                }
            }

            $cashiers = User::where('created_by', $creatorId)
                ->where('is_active', 1)
                ->orderBy('name')
                ->pluck('name', 'id');
            
            // Calculate totals for card and cash payments per warehouse
            $warehouseTotals = [];
            
            // Calculate main table totals
            $mainTotals = [
                'subtotal' => 0, // Raw subtotal before discount and tax
                'discount' => 0, // Total discount (product + overall)
                'tax' => 0,
                'total' => 0,
                'actual_paid' => 0, // Sum of all payment method amounts (excluding vouchers)
                'quantity' => 0, // Total quantity of items
            ];
            
            // Get all POS IDs from the filtered results
            $posIds = $posPayments->pluck('id')->toArray();
            
            if (!empty($posIds)) {
                // Get warehouse names map
                $warehouseMap = [];
                foreach ($posPayments as $pos) {
                    if ($pos->warehouse) {
                        $warehouseMap[$pos->warehouse_id] = $pos->warehouse->name;
                    }
                }
                
                // Calculate main totals and warehouse totals
                foreach ($posPayments as $pos) {
                    // Calculate values:
                    // Sub Total = Raw subtotal (before discount, before tax)
                    $rawSubtotal = $pos->getRawSubTotal();
                    
                    // Total Discount = Product discount + Overall discount
                    $totalDiscount = $pos->getTotalDiscountAmount();
                    
                    // Tax = calculated on (subtotal - discount)
                    $tax = $pos->getTotalTax();
                    
                    // Total = (subtotal - discount) + tax
                    $total = $pos->getTotal();
                    
                    // Calculate total quantity of items
                    $totalQuantity = (float) ($pos->items_qty_sum ?? 0);
                    
                    $mainTotals['subtotal'] += $rawSubtotal;
                    $mainTotals['discount'] += $totalDiscount;
                    $mainTotals['tax'] += $tax;
                    $mainTotals['total'] += ($total < 0 ? 0 : $total);
                    $mainTotals['quantity'] += $totalQuantity;
                    
                    $mainTotals['actual_paid'] += (float) ($pos->actual_paid_sum ?? 0);
                }
                
                // Group by warehouse and payment method
                $paymentAgg = PosPayment::query()
                    ->join('payment_methods', 'payment_methods.id', '=', 'pos_payments.payment_method_id')
                    ->whereIn('pos_payments.pos_id', $posIds)
                    ->where('pos_payments.amount', '>', 0)
                    ->selectRaw('payment_methods.warehouse_id as warehouse_id, LOWER(TRIM(payment_methods.name)) as method_name, SUM(pos_payments.amount) as total_amount')
                    ->groupBy('payment_methods.warehouse_id', DB::raw('LOWER(TRIM(payment_methods.name))'))
                    ->get();

                foreach ($paymentAgg as $row) {
                    $warehouseId = (int) $row->warehouse_id;
                    $methodName = (string) $row->method_name;
                    $amount = (float) $row->total_amount;

                    if (!isset($warehouseTotals[$warehouseId])) {
                        $warehouseTotals[$warehouseId] = [
                            'warehouse_name' => $warehouseMap[$warehouseId] ?? '',
                            'card_total' => 0,
                            'cash_total' => 0,
                        ];
                    }

                    if (strpos($methodName, 'card') !== false || strpos($methodName, 'credit') !== false || strpos($methodName, 'debit') !== false) {
                        $warehouseTotals[$warehouseId]['card_total'] += $amount;
                    } elseif (strpos($methodName, 'cash') !== false) {
                        $warehouseTotals[$warehouseId]['cash_total'] += $amount;
                    }
                }
            }
            
            // Calculate warehouse totals footer (sum of all warehouses)
            $warehouseTotalsFooter = [
                'card_total' => 0,
                'cash_total' => 0,
            ];
            foreach ($warehouseTotals as $warehouseTotal) {
                $warehouseTotalsFooter['card_total'] += $warehouseTotal['card_total'];
                $warehouseTotalsFooter['cash_total'] += $warehouseTotal['cash_total'];
            }
            
            // For user accounts, filter to only show their assigned warehouses
            if ($user->type != 'company' && $user->type != 'super admin') {
                $assignedWarehouseIds = $user->warehouses()->pluck('warehouses.id')->toArray();
                if (!empty($assignedWarehouseIds)) {
                    $warehouseTotals = array_filter($warehouseTotals, function($key) use ($assignedWarehouseIds) {
                        return in_array($key, $assignedWarehouseIds);
                    }, ARRAY_FILTER_USE_KEY);
                } else {
                    $warehouseTotals = [];
                }
            }

            // POS Refunds: filter by refund date (created_at) and same creator/warehouse rules
            $posRefundsQuery = PosRefund::whereHas('pos', function ($q) use ($creatorId) {
                $q->where('created_by', '=', $creatorId);
            })->with(['pos.customer', 'pos.warehouse', 'pos.cashier', 'items', 'voucher']);

            if ($user->type != 'company' && $user->type != 'super admin') {
                $assignedWarehouseIds = $user->warehouses()->pluck('warehouses.id')->toArray();
                if (!empty($assignedWarehouseIds)) {
                    $posRefundsQuery->whereHas('pos', function ($q) use ($assignedWarehouseIds) {
                        $q->whereIn('warehouse_id', $assignedWarehouseIds);
                    });
                } else {
                    $posRefundsQuery->whereRaw('1 = 0');
                }
            }

            if ($startDate && $endDate) {
                $posRefundsQuery->whereBetween(\DB::raw('DATE(created_at)'), [$startDate, $endDate]);
            } elseif ($startDate) {
                $posRefundsQuery->whereRaw('DATE(created_at) >= ?', [$startDate]);
            } elseif ($endDate) {
                $posRefundsQuery->whereRaw('DATE(created_at) <= ?', [$endDate]);
            }

            if (!empty($selectedWarehouseId)) {
                $posRefundsQuery->whereHas('pos', function ($q) use ($selectedWarehouseId) {
                    $q->where('warehouse_id', $selectedWarehouseId);
                });
            }

            if (!empty($selectedCashierId)) {
                $posRefundsQuery->whereHas('pos', function ($q) use ($selectedCashierId) {
                    $q->where('user_id', $selectedCashierId);
                });
            }

            $posRefunds = $posRefundsQuery->orderBy('created_at', 'desc')->orderBy('id', 'desc')->get();

            $refundTotals = [
                'quantity' => 0,
                'total' => 0,
            ];
            foreach ($posRefunds as $refund) {
                $refundTotals['quantity'] += $refund->items->sum('quantity');
                $refundTotals['total'] += (float) $refund->total_amount;
            }

            return view('pos.report', compact('posPayments', 'filters', 'warehouseTotals', 'mainTotals', 'warehouseTotalsFooter', 'posRefunds', 'refundTotals', 'warehouses', 'cashiers'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

    }

    function exportReport(Request $request)
    {
        if(\Auth::user()->can('manage pos') || \Auth::user()->can('add pos'))
        {
            try {
                $user = \Auth::user();
                $creatorId = $user->creatorId();
                $selectedWarehouseId = $request->input('warehouse_id');
                $selectedCashierId = $request->input('cashier_id');
                
                // Build query (same as report method)
                $query = Pos::where('created_by', '=', $creatorId)->with(['customer','warehouse','posPayment','cashier']);
                
                // Filter by user's assigned warehouses for user accounts
                if ($user->type != 'company' && $user->type != 'super admin') {
                    // Get user's assigned warehouse IDs
                    $assignedWarehouseIds = $user->warehouses()->pluck('warehouses.id')->toArray();
                    
                    if (!empty($assignedWarehouseIds)) {
                        // User has assigned warehouses - only show POS from those warehouses
                        $query->whereIn('warehouse_id', $assignedWarehouseIds);
                    } else {
                        // No assigned warehouses - show empty result
                        $query->whereRaw('1 = 0'); // Always false condition
                    }
                }
                
                // Filter by date range - default to today if no dates provided
                $startDate = $request->input('start_date');
                $endDate = $request->input('end_date');
                
                // If no dates provided, default to today
                if (!$startDate && !$endDate) {
                    $today = date('Y-m-d');
                    $startDate = $today;
                    $endDate = $today;
                }
                
                if ($startDate && $endDate) {
                    $query->whereBetween('pos_date', [$startDate, $endDate]);
                } elseif ($startDate) {
                    $query->where('pos_date', '>=', $startDate);
                } elseif ($endDate) {
                    $query->where('pos_date', '<=', $endDate);
                }

                if (!empty($selectedWarehouseId)) {
                    $query->where('warehouse_id', $selectedWarehouseId);
                }

                if (!empty($selectedCashierId)) {
                    $query->where('user_id', $selectedCashierId);
                }
                
                $posPayments = $query->orderBy('pos_date', 'desc')->orderBy('id', 'desc')->get();
                
                // Calculate totals for card and cash payments per warehouse (same logic as report method)
                $warehouseTotals = [];
                
                // Get all POS IDs from the filtered results
                $posIds = $posPayments->pluck('id')->toArray();
                
                if (!empty($posIds)) {
                    // Get all payment records for these POS records
                    $allPosPayments = PosPayment::whereIn('pos_id', $posIds)
                        ->whereNotNull('payment_method_id')
                        ->get();
                    
                    // Get warehouse names map
                    $warehouseMap = [];
                    foreach ($posPayments as $pos) {
                        if ($pos->warehouse) {
                            $warehouseMap[$pos->warehouse_id] = $pos->warehouse->name;
                        }
                    }
                    
                    // Group by warehouse and payment method
                    foreach ($allPosPayments as $posPayment) {
                        if ($posPayment->payment_method_id) {
                            $paymentMethod = PaymentMethod::find($posPayment->payment_method_id);
                            
                            if ($paymentMethod) {
                                $warehouseId = $paymentMethod->warehouse_id;
                                $methodName = strtolower(trim($paymentMethod->name));
                                $amount = (float)$posPayment->amount;
                                
                                // Initialize warehouse if not exists
                                if (!isset($warehouseTotals[$warehouseId])) {
                                    $warehouseTotals[$warehouseId] = [
                                        'warehouse_name' => $warehouseMap[$warehouseId] ?? '',
                                        'card_total' => 0,
                                        'cash_total' => 0,
                                    ];
                                }
                                
                                // Check if it's card or cash (case-insensitive)
                                if (strpos($methodName, 'card') !== false || strpos($methodName, 'credit') !== false || strpos($methodName, 'debit') !== false) {
                                    $warehouseTotals[$warehouseId]['card_total'] += $amount;
                                } elseif (strpos($methodName, 'cash') !== false) {
                                    $warehouseTotals[$warehouseId]['cash_total'] += $amount;
                                }
                            }
                        }
                    }
                }
                
                // For user accounts, filter to only show their assigned warehouses
                if ($user->type != 'company' && $user->type != 'super admin') {
                    $assignedWarehouseIds = $user->warehouses()->pluck('warehouses.id')->toArray();
                    if (!empty($assignedWarehouseIds)) {
                        $warehouseTotals = array_filter($warehouseTotals, function($key) use ($assignedWarehouseIds) {
                            return in_array($key, $assignedWarehouseIds);
                        }, ARRAY_FILTER_USE_KEY);
                    } else {
                        $warehouseTotals = [];
                    }
                }
                
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }
                
                $fileName = 'pos_report_' . date('Y-m-d_H-i-s') . '.xlsx';
                
                return Excel::download(new PosReportExport($posPayments, $user, $warehouseTotals), $fileName);
            } catch (\Exception $e) {
                \Log::error('POS report export failed', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'user_id' => \Auth::user()->id
                ]);
                
                return redirect()->back()->with('error', __('Export failed: ') . $e->getMessage());
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    function printReport(Request $request)
    {
        if(\Auth::user()->can('manage pos') || \Auth::user()->can('add pos') || \Auth::user()->can('print pos'))
        {
            try {
                $user = \Auth::user();
                $creatorId = $user->creatorId();
                $selectedWarehouseId = $request->input('warehouse_id');
                $selectedCashierId = $request->input('cashier_id');
                
                // Build query (same as report method) - load items with all necessary relationships
                $query = Pos::where('created_by', '=', $creatorId)
                    ->with([
                        'customer',
                        'warehouse',
                        'posPayment',
                        'items.product.category',
                        'items.product.brand',
                        'items.product.subBrand',
                        'items.sub_product.productService.category',
                        'items.sub_product.productService.brand',
                        'items.sub_product.productService.subBrand',
                        'cashier'
                    ]);
                
                // Filter by user's assigned warehouses for user accounts
                if ($user->type != 'company' && $user->type != 'super admin') {
                    // Get user's assigned warehouse IDs
                    $assignedWarehouseIds = $user->warehouses()->pluck('warehouses.id')->toArray();
                    
                    if (!empty($assignedWarehouseIds)) {
                        // User has assigned warehouses - only show POS from those warehouses
                        $query->whereIn('warehouse_id', $assignedWarehouseIds);
                    } else {
                        // No assigned warehouses - show empty result
                        $query->whereRaw('1 = 0'); // Always false condition
                    }
                }
                
                // Filter by date range - default to today if no dates provided
                $startDate = $request->input('start_date');
                $endDate = $request->input('end_date');
                
                // If no dates provided, default to today
                if (!$startDate && !$endDate) {
                    $today = date('Y-m-d');
                    $startDate = $today;
                    $endDate = $today;
                }
                
                if ($startDate && $endDate) {
                    $query->whereBetween('pos_date', [$startDate, $endDate]);
                } elseif ($startDate) {
                    $query->where('pos_date', '>=', $startDate);
                } elseif ($endDate) {
                    $query->where('pos_date', '<=', $endDate);
                }

                if (!empty($selectedWarehouseId)) {
                    $query->where('warehouse_id', $selectedWarehouseId);
                }

                if (!empty($selectedCashierId)) {
                    $query->where('user_id', $selectedCashierId);
                }
                
                $posPayments = $query->orderBy('pos_date', 'desc')->orderBy('id', 'desc')->get();
                
                // Collect POS transactions with their details
                $posTransactions = [];
                $totalQty = 0;
                $totalAmount = 0;
                $totalAmountVoucher = 0;
                $totalDiscount = 0;
                $totalComboSavings = 0;
                $totalVoucher = 0;
                
                foreach ($posPayments as $pos) {
                    // Calculate values for this POS
                    $rawSubtotal = $pos->getRawSubTotal();
                    $posDiscount = $pos->getTotalDiscountAmount();
                    $tax = $pos->getTotalTax();
                    $total = $pos->getTotal();
                    $posVoucher = $pos->getVoucherTotal();
                    
                    // Calculate combo savings for this POS
                    $posComboSavings = 0;
                    $posQuantity = 0;
                    $items = [];
                    
                    if ($pos->items && $pos->items->isNotEmpty()) {
                        // Get tax data for calculations
                        $taxes = \App\Models\Utility::tax($pos->tax_id);
                        $taxRate = 0;
                        foreach ($taxes as $taxRecord) {
                            if ($taxRecord && $taxRecord->rate) {
                                $taxRate += $taxRecord->rate;
                            }
                        }
                        
                        foreach ($pos->items as $item) {
                            $posQuantity += (float)($item->quantity ?? 0);
                            
                            // Get product name
                            $productName = 'N/A';
                            if ($item->product) {
                                $product = $item->product;
                                $nameParts = [];
                                if ($product->category && $product->category->name) {
                                    $nameParts[] = $product->category->name;
                                }
                                if ($product->brand && $product->brand->name) {
                                    $nameParts[] = $product->brand->name;
                                }
                                if ($product->subBrand && $product->subBrand->name) {
                                    $nameParts[] = $product->subBrand->name;
                                }
                                if ($product->name) {
                                    $nameParts[] = $product->name;
                                }
                                $productName = !empty($nameParts) ? implode(' -> ', $nameParts) : ($product->name ?? 'N/A');
                            } elseif ($item->sub_product && $item->sub_product->productService) {
                                $product = $item->sub_product->productService;
                                $nameParts = [];
                                if ($product->category && $product->category->name) {
                                    $nameParts[] = $product->category->name;
                                }
                                if ($product->brand && $product->brand->name) {
                                    $nameParts[] = $product->brand->name;
                                }
                                if ($product->subBrand && $product->subBrand->name) {
                                    $nameParts[] = $product->subBrand->name;
                                }
                                if ($product->name) {
                                    $nameParts[] = $product->name;
                                }
                                $productName = !empty($nameParts) ? implode(' -> ', $nameParts) : ($product->name ?? 'N/A');
                                
                                // Add product number if available
                                if ($item->sub_product && $item->sub_product->product_no) {
                                    $productName .= ' (' . $item->sub_product->product_no . ')';
                                }
                            }
                            
                            // Calculate item prices
                            $basePrice = (float)($item->price ?? 0);
                            $itemQuantity = (float)($item->quantity ?? 0);
                            $itemDiscount = (float)($item->discount ?? 0);
                            
                            // Use combo_price if available
                            if ($item->compo_id != 0 && $item->compo_id != '0' && $item->combo_price !== null) {
                                $basePrice = (float)($item->combo_price ?? 0);
                                $regularPrice = (float)($item->price ?? 0);
                                $comboPrice = (float)($item->combo_price ?? 0);
                                $posComboSavings += ($regularPrice - $comboPrice) * $itemQuantity;
                            }
                            
                            // Calculate price after discount (per unit)
                            $priceAfterDiscount = $basePrice - ($basePrice * ($itemDiscount / 100));
                            
                            // Calculate tax amount using the same method as POS view
                            // Tax is calculated on price after discount * quantity
                            $itemTaxAmount = 0;
                            $taxName = '';
                            if (!empty($pos->tax_id)) {
                                $taxes = \App\Models\Utility::tax($pos->tax_id);
                                foreach ($taxes as $taxRecord) {
                                    if ($taxRecord && $taxRecord->rate && $taxRecord->name) {
                                        // Use Utility::taxRate method (same as POS view)
                                        $taxPrice = \App\Models\Utility::taxRate($taxRecord->rate, $priceAfterDiscount, $itemQuantity);
                                        $itemTaxAmount += $taxPrice;
                                        if (empty($taxName)) {
                                            $taxName = $taxRecord->name . ' (' . $taxRecord->rate . '%)';
                                        } else {
                                            $taxName .= ', ' . $taxRecord->name . ' (' . $taxRecord->rate . '%)';
                                        }
                                    }
                                }
                            }
                            
                            // Calculate item subtotal (price after discount * quantity)
                            $itemSubtotal = $priceAfterDiscount * $itemQuantity;
                            
                            // Calculate item discount amount (base price * discount% * quantity)
                            $itemDiscountAmount = ($basePrice * ($itemDiscount / 100)) * $itemQuantity;
                            
                            // Calculate item total (subtotal + tax) - same as POS view
                            $itemTotal = $itemSubtotal + $itemTaxAmount;
                            
                            $items[] = [
                                'name' => $productName,
                                'quantity' => $itemQuantity,
                                'price' => $basePrice,
                                'discount' => $itemDiscount,
                                'discount_amount' => $itemDiscountAmount,
                                'price_after_discount' => $priceAfterDiscount,
                                'tax_name' => $taxName,
                                'tax_rate' => $taxRate,
                                'tax_amount' => $itemTaxAmount,
                                'subtotal' => $itemSubtotal,
                                'total' => $itemTotal,
                            ];
                        }
                    }
                    
                    // Get customer name
                    $customerName = 'Walk-in Customer';
                    if ($pos->customer_id != 0 && !empty($pos->customer)) {
                        $customerName = $pos->customer->name;
                    }
                    
                    // Get warehouse name
                    $warehouseName = !empty($pos->warehouse) ? $pos->warehouse->name : '';
                    
                    // Get cashier name
                    $cashierName = !empty($pos->cashier) ? $pos->cashier->name : 'N/A';
                    
                    $posTransactions[] = [
                        'pos_id' => $user->posNumberFormat($pos->pos_id),
                        'date' => $user->dateFormat($pos->created_at),
                        'customer' => $customerName,
                        'warehouse' => $warehouseName,
                        'cashier' => $cashierName,
                        'quantity' => $posQuantity,
                        'subtotal' => $rawSubtotal,
                        'discount' => $posDiscount,
                        'tax' => $tax,
                        'total' => $total,
                        'combo_savings' => $posComboSavings,
                        'voucher' => $posVoucher,
                        'items' => $items,
                    ];
                    
                    // Accumulate totals
                    $totalQty += $posQuantity;
                    $totalAmount += $rawSubtotal;
                    $totalDiscount += $posDiscount;
                    $totalComboSavings += $posComboSavings;
                    $totalVoucher += $posVoucher;
                }
                
                // Collect all items from all transactions into a flat list
                $allItems = [];
                $totalTax = 0;
                $totalActualPaid = 0; // Track total actual amount paid
                
                foreach ($posTransactions as $transaction) {
                    if (!empty($transaction['items']) && is_array($transaction['items'])) {
                        foreach ($transaction['items'] as $item) {
                            // Price after discount and combo (per unit)
                            $finalPrice = $item['price_after_discount'] ?? 0;
                            
                            // Tax amount for this item
                            $itemTaxAmount = $item['tax_amount'] ?? 0;
                            $totalTax += $itemTaxAmount;
                            
                            // Item total already includes tax (subtotal + tax)
                            $itemTotal = $item['total'] ?? 0;
                            
                            $allItems[] = [
                                'name' => $item['name'],
                                'quantity' => $item['quantity'],
                                'price_after_discount_combo' => $finalPrice,
                                'tax_amount' => $itemTaxAmount,
                                'total' => $itemTotal,
                            ];
                        }
                    }
                }
                
                // Calculate total actual amount paid for all POS transactions
                foreach ($posPayments as $pos) {
                    $posPaymentRecords = PosPayment::where('pos_id', $pos->id)->get();
                    foreach ($posPaymentRecords as $posPaymentRecord) {
                        // Only include payments with amount > 0
                        // Vouchers are tracked separately in GeneralLedger, not in PosPayment
                        if ((float)$posPaymentRecord->amount > 0) {
                            $totalActualPaid += (float)$posPaymentRecord->amount;
                        }
                    }
                }
                
                // POS Refunds total (filtered by refund date, same as report)
                $totalRefund = 0;
                $posRefundsPrintQuery = PosRefund::whereHas('pos', function ($q) use ($creatorId) {
                    $q->where('created_by', '=', $creatorId);
                });
                if ($user->type != 'company' && $user->type != 'super admin') {
                    $assignedWarehouseIds = $user->warehouses()->pluck('warehouses.id')->toArray();
                    if (!empty($assignedWarehouseIds)) {
                        $posRefundsPrintQuery->whereHas('pos', function ($q) use ($assignedWarehouseIds) {
                            $q->whereIn('warehouse_id', $assignedWarehouseIds);
                        });
                    } else {
                        $posRefundsPrintQuery->whereRaw('1 = 0');
                    }
                }
                if ($startDate && $endDate) {
                    $posRefundsPrintQuery->whereBetween(\DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                } elseif ($startDate) {
                    $posRefundsPrintQuery->whereRaw('DATE(created_at) >= ?', [$startDate]);
                } elseif ($endDate) {
                    $posRefundsPrintQuery->whereRaw('DATE(created_at) <= ?', [$endDate]);
                }
                $totalRefund = (float) $posRefundsPrintQuery->sum('total_amount');

                // Store filters for view
                $filters = [
                    'start_date' => $startDate ?? '',
                    'end_date' => $endDate ?? ''
                ];
                
                return view('pos.report_print', compact('allItems', 'totalQty', 'totalAmount', 'totalDiscount', 'totalComboSavings', 'totalVoucher', 'totalTax', 'totalActualPaid', 'totalRefund', 'filters'));
            } catch (\Exception $e) {
                \Log::error('POS Report Print Error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return redirect()->back()->with('error', __('Error generating print report: ') . $e->getMessage());
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    function barcode()
    {
        if(\Auth::user()->can('manage pos'))
        {
            $productServices = ProductService::where('created_by', '=', \Auth::user()->creatorId())->get();
            $barcode  = [
                'barcodeType' => Auth::user()->barcodeType() ,
                'barcodeFormat' => Auth::user()->barcodeFormat(),
            ];

            return view('pos.barcode',compact('productServices','barcode'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

    }

    public function setting()
    {
        if(\Auth::user()->can('manage pos'))
        {
            $settings                = Utility::settings();

            return view('pos.setting',compact('settings'));
        }
        else
        {
            return redirect()->back()->with('error', 'Permission denied.');
        }


    }

    public function BarcodesettingStore(Request $request)
    {
        $request->validate(
                [
                    'barcode_type' => 'required',
                    'barcode_format' => 'required',
                ]
            );

        $post['barcode_type'] = $request->barcode_type;
        $post['barcode_format'] = $request->barcode_format;

        foreach($post as $key => $data)
        {

            $arr = [
                $data,
                $key,
                \Auth::user()->id,
            ];

            \DB::insert(
                'insert into settings (`value`, `name`,`created_by`) values (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ', $arr
            );
        }
        return redirect()->back()->with('success', 'Barcode setting successfully updated.');

    }

    public function printBarcode()
    {
        if(\Auth::user()->can('manage pos') || \Auth::user()->can('print pos') || \Auth::user()->can('create barcode'))
        {
            // Get warehouses - filter by user's assigned warehouses if user has any
            $user = Auth::user();
            if ($user->warehouses()->count() > 0) {
                // User has assigned warehouses - only show those
                $warehouses = $user->warehouses()
                    ->select('*', \DB::raw("CONCAT(name) AS name"))
                    ->get()
                    ->pluck('name', 'id');
            } else {
                // No assigned warehouses - show all company warehouses (backward compatibility)
                $warehouses = warehouse::select('*', \DB::raw("CONCAT(name) AS name"))
                    ->where('created_by', \Auth::user()->creatorId())
                    ->get()
                    ->pluck('name', 'id');
            }

            return view('pos.print',compact('warehouses'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Get categories for barcode print filtering
     */
    public function getBarcodeCategories(Request $request)
    {
        // Check permission for barcode printing
        if(!\Auth::user()->can('manage pos') && !\Auth::user()->can('print pos') && !\Auth::user()->can('create barcode'))
        {
            return response()->json(['error' => __('Permission denied.')], 403);
        }
        
        try {
            $warehouseId = $request->warehouse_id;
            
            if (!$warehouseId) {
                return response()->json(['error' => 'Warehouse ID is required'], 400);
            }

            // Get unique category IDs from products that have sub-products in this warehouse
            $productIds = SubProduct::where('warehouse_id', $warehouseId)
                ->where('created_by', \Auth::user()->creatorId())
                ->whereNotNull('product_id')
                ->distinct()
                ->pluck('product_id');

            $categoryIds = ProductService::whereIn('id', $productIds)
                ->where('created_by', \Auth::user()->creatorId())
                ->whereNotNull('category_id')
                ->distinct()
                ->pluck('category_id');

            $categories = ProductServiceCategory::whereIn('id', $categoryIds)
                ->where('created_by', \Auth::user()->creatorId())
                ->orderBy('name')
                ->get(['id', 'name']);

            return response()->json(['categories' => $categories]);
        } catch (\Exception $e) {
            \Log::error('Error getting barcode categories', [
                'error' => $e->getMessage(),
                'warehouse_id' => $request->warehouse_id
            ]);
            return response()->json(['error' => 'Error getting categories'], 500);
        }
    }

    /**
     * Get brands for barcode print filtering
     */
    public function getBarcodeBrands(Request $request)
    {
        // Check permission for barcode printing
        if(!\Auth::user()->can('manage pos') && !\Auth::user()->can('print pos') && !\Auth::user()->can('create barcode'))
        {
            return response()->json(['error' => __('Permission denied.')], 403);
        }
        
        try {
            $warehouseId = $request->warehouse_id;
            $categoryId = $request->category_id;
            
            if (!$warehouseId || !$categoryId) {
                return response()->json(['error' => 'Warehouse ID and Category ID are required'], 400);
            }

            // Get product IDs from sub-products in this warehouse
            $productIds = SubProduct::where('warehouse_id', $warehouseId)
                ->where('created_by', \Auth::user()->creatorId())
                ->whereNotNull('product_id')
                ->distinct()
                ->pluck('product_id');

            // Get brand IDs from products that match category
            $brandIds = ProductService::whereIn('id', $productIds)
                ->where('category_id', $categoryId)
                ->where('created_by', \Auth::user()->creatorId())
                ->whereNotNull('brand_id')
                ->distinct()
                ->pluck('brand_id');

            $brands = Brand::whereIn('id', $brandIds)
                ->where('created_by', \Auth::user()->creatorId())
                ->orderBy('name')
                ->get(['id', 'name']);

            return response()->json(['brands' => $brands]);
        } catch (\Exception $e) {
            \Log::error('Error getting barcode brands', [
                'error' => $e->getMessage(),
                'warehouse_id' => $request->warehouse_id,
                'category_id' => $request->category_id
            ]);
            return response()->json(['error' => 'Error getting brands'], 500);
        }
    }

    /**
     * Get products for barcode print filtering
     */
    public function getBarcodeProducts(Request $request)
    {
        // Check permission for barcode printing
        if(!\Auth::user()->can('manage pos') && !\Auth::user()->can('print pos') && !\Auth::user()->can('create barcode'))
        {
            return response()->json(['error' => __('Permission denied.')], 403);
        }
        
        try {
            $warehouseId = $request->warehouse_id;
            $categoryId = $request->category_id;
            $brandId = $request->brand_id;
            
            if (!$warehouseId || !$categoryId || !$brandId) {
                return response()->json(['error' => 'Warehouse ID, Category ID, and Brand ID are required'], 400);
            }

            // Get product IDs from sub-products in this warehouse
            $productIds = SubProduct::where('warehouse_id', $warehouseId)
                ->where('created_by', \Auth::user()->creatorId())
                ->whereNotNull('product_id')
                ->distinct()
                ->pluck('product_id');

            // Get products that match category and brand
            $products = ProductService::whereIn('id', $productIds)
                ->where('category_id', $categoryId)
                ->where('brand_id', $brandId)
                ->where('created_by', \Auth::user()->creatorId())
                ->orderBy('name')
                ->get(['id', 'name', 'sku']);

            return response()->json(['products' => $products]);
        } catch (\Exception $e) {
            \Log::error('Error getting barcode products', [
                'error' => $e->getMessage(),
                'warehouse_id' => $request->warehouse_id,
                'category_id' => $request->category_id,
                'brand_id' => $request->brand_id
            ]);
            return response()->json(['error' => 'Error getting products'], 500);
        }
    }

    /**
     * Get sub-products for barcode print filtering
     */
    public function getBarcodeSubProducts(Request $request)
    {
        // Check permission for barcode printing
        if(!\Auth::user()->can('manage pos') && !\Auth::user()->can('print pos') && !\Auth::user()->can('create barcode'))
        {
            return response()->json(['error' => __('Permission denied.')], 403);
        }
        
        try {
            $warehouseId = $request->warehouse_id;
            $productId = $request->product_id;
            
            if (!$warehouseId || !$productId) {
                return response()->json(['error' => 'Warehouse ID and Product ID are required'], 400);
            }

            // Get latest SubProduct for each product_no using subquery
            $latestSubProductIds = SubProduct::select(DB::raw('MAX(id) as id'))
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('created_by', \Auth::user()->creatorId())
                ->whereNotNull('chassis_no')
                ->groupBy('chassis_no')
                ->pluck('id');
            
            if ($latestSubProductIds->isEmpty()) {
                return response()->json([]);
            }
            
            // Get quantities grouped by product_no
            $quantities = SubProduct::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('created_by', \Auth::user()->creatorId())
                ->whereNotNull('chassis_no')
                ->select('chassis_no', DB::raw('SUM(quantity) as total_quantity'))
                ->groupBy('chassis_no')
                ->pluck('total_quantity', 'chassis_no');
            
            // Eager load all relationships
            $latestSubProducts = SubProduct::select('id', 'chassis_no', 'product_id', 'sale_price', 'purchase_price', 'price_rule_id')
                ->with([
                    'productService:id,name,sku,sale_price,purchase_price,category_id,brand_id,sub_brand_id,tax_id',
                    'productService.category:id,name',
                    'productService.brand:id,name',
                    'productService.subBrand:id,name',
                    'customFieldValues:id,record_id,field_id,value',
                    'customFieldValues.customField:id,name'
                ])
                ->whereIn('id', $latestSubProductIds)
                ->get()
                ->keyBy('chassis_no');
            
            // Get tax data for VAT calculation
            $taxData = \App\Models\Utility::getTaxData();
            
            // Build response
            $grouped = $latestSubProducts->map(function ($latestSubProduct) use ($quantities, $taxData) {
                $productService = $latestSubProduct->productService;
                
                if (!$productService) {
                    return null;
                }
                
                // Build full name starting from category, brand, sub brand, then product name
                $fullNameParts = [];
                if ($productService->category) {
                    $fullNameParts[] = $productService->category->name;
                }
                if ($productService->brand) {
                    $fullNameParts[] = $productService->brand->name;
                }
                if ($productService->subBrand) {
                    $fullNameParts[] = $productService->subBrand->name;
                }
                $fullNameParts[] = $productService->name;
                
                // Build name string with full path
                $n_name = implode(' > ', $fullNameParts);
                
                // Build custom fields string
                $customFieldsStr = '';
                foreach ($latestSubProduct->customFieldValues as $customFieldValue) {
                    if ($customFieldValue->customField) {
                        $customFieldsStr .= $customFieldValue->customField->name . ' : ';
                        $customFieldsStr .= $customFieldValue->value . ' | ';
                    }
                }
                
                // Calculate VAT rate
                $totalTaxRate = 0;
                if ($productService->tax_id) {
                    $taxIds = explode(',', $productService->tax_id);
                    foreach ($taxIds as $taxId) {
                        $taxId = trim($taxId);
                        if (!empty($taxId) && isset($taxData[$taxId]['rate'])) {
                            $totalTaxRate += (float) $taxData[$taxId]['rate'];
                        }
                    }
                }
                
                $salePrice = $latestSubProduct->get_price_list_sale_price();
                $priceWithoutVat = $salePrice;
                $priceWithVat = $salePrice;
                
                // Calculate price with VAT
                if ($totalTaxRate > 0) {
                    $priceWithVat = $salePrice * (1 + ($totalTaxRate / 100));
                }
                
                return [
                    'id' => $latestSubProduct->product_no,
                    'product_no' => $latestSubProduct->product_no,
                    'total_quantity' => $quantities[$latestSubProduct->product_no] ?? 0,
                    'sale_price' => $salePrice,
                    'price_without_vat' => $priceWithoutVat,
                    'price_with_vat' => $priceWithVat,
                    'tax_rate' => $totalTaxRate,
                    'name' => $n_name,
                    'custom_fields' => $customFieldsStr,
                    'parent_product' => [
                        'id' => $productService->id,
                        'name' => $productService->name,
                        'sku' => $productService->sku,
                        'sale_price' => $productService->sale_price,
                        'purchase_price' => $productService->purchase_price,
                        'tax_id' => $productService->tax_id,
                        'category' => $productService->category ? [
                            'id' => $productService->category->id,
                            'name' => $productService->category->name,
                        ] : null,
                        'brand' => $productService->brand ? [
                            'id' => $productService->brand->id,
                            'name' => $productService->brand->name,
                        ] : null,
                        'sub_brand' => $productService->subBrand ? [
                            'id' => $productService->subBrand->id,
                            'name' => $productService->subBrand->name,
                        ] : null,
                    ],
                ];
            })
            ->filter()
            ->values();
            
            return response()->json($grouped);
        } catch (\Exception $e) {
            \Log::error('Error getting barcode sub-products', [
                'error' => $e->getMessage(),
                'warehouse_id' => $request->warehouse_id,
                'product_id' => $request->product_id
            ]);
            return response()->json(['error' => 'Error getting sub-products'], 500);
        }
    }

    /**
     * Search sub-product by barcode directly (for barcode print page)
     * Returns all necessary data to auto-populate filters
     */
    public function searchBarcodeDirect(Request $request)
    {
        // Check permission for barcode printing
        if(!\Auth::user()->can('manage pos') && !\Auth::user()->can('print pos') && !\Auth::user()->can('create barcode'))
        {
            return response()->json(['error' => __('Permission denied.')], 403);
        }
        
        try {
            $barcode = trim($request->input('barcode', ''));
            $warehouseId = $request->input('warehouse_id');
            
            if (empty($barcode)) {
                return response()->json(['error' => __('Barcode is required')], 400);
            }
            
            $creatorId = \Auth::user()->creatorId();
            
            // Escape special characters for LIKE queries (%, _, /, etc.)
            $escapedBarcode = str_replace(['%', '_', '\\'], ['\%', '\_', '\\\\'], $barcode);
            
            // Build query to find sub-product by barcode or SKU
            $query = SubProduct::with([
                'productService:id,name,sku,category_id,brand_id,sub_brand_id,tax_id',
                'productService.category:id,name',
                'productService.brand:id,name',
                'productService.subBrand:id,name',
                'customFieldValues:id,record_id,field_id,value',
                'customFieldValues.customField:id,name',
                'warehouse:id,name'
            ])
            ->where('created_by', $creatorId)
            ->whereNotNull('chassis_no')
            ->where(function($q) use ($barcode, $escapedBarcode) {
                // Search by product_no (barcode)
                $q->where(function($subQ) use ($barcode, $escapedBarcode) {
                    $subQ->where('chassis_no', '=', $barcode) // Exact match first
                         ->orWhere('chassis_no', 'LIKE', '%' . $escapedBarcode . '%'); // Partial match (escaped)
                })
                // Also search by SKU through ProductService relationship
                ->orWhereHas('productService', function($psQ) use ($barcode, $escapedBarcode) {
                    $psQ->where('sku', '=', $barcode) // Exact match first
                        ->orWhere('sku', 'LIKE', '%' . $escapedBarcode . '%'); // Partial match (escaped)
                });
            });
            
            // If warehouse_id is provided, filter by it
            if ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }
            
            // Get the latest sub-product for this barcode/SKU (if multiple exist)
            $subProduct = $query->orderBy('id', 'desc')->first();
            
            if (!$subProduct) {
                return response()->json([
                    'error' => __('Product not found for barcode/SKU: :barcode', ['barcode' => $barcode])
                ], 404);
            }
            
            $productService = $subProduct->productService;
            if (!$productService) {
                return response()->json(['error' => __('Product service not found')], 404);
            }
            
            // Calculate total quantity for this product_no
            $totalQuantity = SubProduct::where('chassis_no', $subProduct->chassis_no)
                ->where('warehouse_id', $subProduct->warehouse_id)
                ->where('created_by', $creatorId)
                ->sum('quantity');
            
            // Get tax data for VAT calculation
            $taxData = \App\Models\Utility::getTaxData();
            $totalTaxRate = 0;
            if ($productService->tax_id) {
                $taxIds = explode(',', $productService->tax_id);
                foreach ($taxIds as $taxId) {
                    $taxId = trim($taxId);
                    if (!empty($taxId) && isset($taxData[$taxId]['rate'])) {
                        $totalTaxRate += (float) $taxData[$taxId]['rate'];
                    }
                }
            }
            
            // Calculate prices
            $priceWithoutVat = $subProduct->sale_price ?? 0;
            $priceWithVat = $priceWithoutVat * (1 + ($totalTaxRate / 100));
            
            // Build custom fields data
            $customFields = [];
            foreach ($subProduct->customFieldValues as $customFieldValue) {
                if ($customFieldValue->customField) {
                    $customFields[] = [
                        'id' => $customFieldValue->customField->id,
                        'name' => $customFieldValue->customField->name,
                        'value' => $customFieldValue->value
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'sub_product' => [
                    'id' => $subProduct->chassis_no, // Use product_no as id to match getBarcodeSubProducts format
                    'product_no' => $subProduct->chassis_no,
                    'sub_product_model_id' => $subProduct->id, // Store actual model id for reference
                    'total_quantity' => $totalQuantity,
                    'price_without_vat' => $priceWithoutVat,
                    'price_with_vat' => $priceWithVat,
                    'sale_price' => $subProduct->sale_price,
                ],
                'product' => [
                    'id' => $productService->id,
                    'name' => $productService->name,
                    'sku' => $productService->sku,
                ],
                'category' => [
                    'id' => $productService->category_id,
                    'name' => $productService->category->name ?? '',
                ],
                'brand' => [
                    'id' => $productService->brand_id,
                    'name' => $productService->brand->name ?? '',
                ],
                'warehouse' => [
                    'id' => $subProduct->warehouse_id,
                    'name' => $subProduct->warehouse->name ?? '',
                ],
                'custom_fields' => $customFields
            ]);
        } catch (\Exception $e) {
            \Log::error('Error searching barcode directly', [
                'error' => $e->getMessage(),
                'barcode' => $request->input('barcode'),
                'warehouse_id' => $request->input('warehouse_id')
            ]);
            return response()->json(['error' => __('Error searching barcode: ') . $e->getMessage()], 500);
        }
    }

    public function getproduct(Request $request)
    {
        $WHID = $request->warehouse_id;
        
        // Early return if no warehouse selected
        if (empty($WHID)) {
            return response()->json([]);
        }
        
        // Optimized query: Get latest SubProduct for each product_no using subquery
        // This eliminates N+1 queries by getting all data in one query
        $latestSubProductIds = SubProduct::select(DB::raw('MAX(id) as id'))
            ->where('warehouse_id', $WHID)
            ->whereNotNull('chassis_no')
            ->groupBy('chassis_no')
            ->pluck('id');
        
        // Early return if no sub-products found
        if ($latestSubProductIds->isEmpty()) {
            return response()->json([]);
        }
        
        // Get quantities grouped by product_no
        $quantities = SubProduct::where('warehouse_id', $WHID)
            ->whereNotNull('chassis_no')
            ->select('chassis_no', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('chassis_no')
            ->pluck('total_quantity', 'chassis_no');
        
        // Eager load all relationships in a single query
        // Using select() to limit columns and reduce memory usage
        $latestSubProducts = SubProduct::select('id', 'chassis_no', 'product_id', 'sale_price', 'purchase_price', 'price_rule_id')
            ->with([
                'productService:id,name,sku,sale_price,purchase_price,category_id,brand_id,sub_brand_id,tax_id',
                'productService.category:id,name',
                'productService.brand:id,name',
                'productService.subBrand:id,name',
                'customFieldValues:id,record_id,field_id,value',
                'customFieldValues.customField:id,name'
            ])
            ->whereIn('id', $latestSubProductIds)
            ->get()
            ->keyBy('chassis_no');
        
        // Get tax data for VAT calculation
        $taxData = \App\Models\Utility::getTaxData();
        
        // Build response efficiently
        $grouped = $latestSubProducts->map(function ($latestSubProduct) use ($quantities, $taxData) {
            $productService = $latestSubProduct->productService;
            
            if (!$productService) {
                return null;
            }
            
            // Build full name starting from category, brand, sub brand, then product name
            $fullNameParts = [];
            if ($productService->category) {
                $fullNameParts[] = $productService->category->name;
            }
            if ($productService->brand) {
                $fullNameParts[] = $productService->brand->name;
            }
            if ($productService->subBrand) {
                $fullNameParts[] = $productService->subBrand->name;
            }
            $fullNameParts[] = $productService->name;
            
            // Build name string with full path
            $n_name = implode(' > ', $fullNameParts);
            
            // Build custom fields string (keep for display in dropdown if needed)
            $customFieldsStr = '';
            foreach ($latestSubProduct->customFieldValues as $customFieldValue) {
                if ($customFieldValue->customField) {
                    $customFieldsStr .= $customFieldValue->customField->name . ' : ';
                    $customFieldsStr .= $customFieldValue->value . ' | ';
                }
            }
            
            // Calculate VAT rate
            $totalTaxRate = 0;
            if ($productService->tax_id) {
                $taxIds = explode(',', $productService->tax_id);
                foreach ($taxIds as $taxId) {
                    $taxId = trim($taxId);
                    if (!empty($taxId) && isset($taxData[$taxId]['rate'])) {
                        $totalTaxRate += (float) $taxData[$taxId]['rate'];
                    }
                }
            }
            
            $salePrice = $latestSubProduct->get_price_list_sale_price();
            $priceWithoutVat = $salePrice;
            $priceWithVat = $salePrice;
            
            // Calculate price with VAT (assuming sale_price is base price)
            if ($totalTaxRate > 0) {
                $priceWithVat = $salePrice * (1 + ($totalTaxRate / 100));
            }
            
            return [
                'id' => $latestSubProduct->product_no,
                'product_no' => $latestSubProduct->product_no,
                'total_quantity' => $quantities[$latestSubProduct->product_no] ?? 0,
                'sale_price' => $salePrice,
                'price_without_vat' => $priceWithoutVat,
                'price_with_vat' => $priceWithVat,
                'tax_rate' => $totalTaxRate,
                'name' => $n_name,
                'custom_fields' => $customFieldsStr,
                'parent_product' => [
                    'id' => $productService->id,
                    'name' => $productService->name,
                    'sku' => $productService->sku,
                    'sale_price' => $productService->sale_price,
                    'purchase_price' => $productService->purchase_price,
                    'tax_id' => $productService->tax_id,
                    'category' => $productService->category ? [
                        'id' => $productService->category->id,
                        'name' => $productService->category->name,
                    ] : null,
                    'brand' => $productService->brand ? [
                        'id' => $productService->brand->id,
                        'name' => $productService->brand->name,
                    ] : null,
                    'sub_brand' => $productService->subBrand ? [
                        'id' => $productService->subBrand->id,
                        'name' => $productService->subBrand->name,
                    ] : null,
                ],
            ];
        })
        ->filter() // Remove null entries
        ->values(); // Reset keys for clean JSON array
        
        return response()->json($grouped);
    }

    public function getCustomFields(Request $request)
    {
        $productNo = $request->input('product_no');
        
        if (!$productNo) {
            return response()->json([]);
        }

        // Get the subproduct to find the product and category
        $subproduct = SubProduct::where('chassis_no', $productNo)
            ->with('productService.category')
            ->latest()
            ->first();

        if (!$subproduct || !$subproduct->productService) {
            return response()->json([]);
        }

        $categoryId = $subproduct->productService->category_id;

        // Get custom fields for this category
        $customFields = CustomField::where('created_by', \Auth::user()->creatorId())
            ->where('module', 'sub-product')
            ->forCategory($categoryId)
            ->get();

        // Get custom field values for this subproduct
        $customFieldValues = CustomFieldValue::where('record_id', $subproduct->id)
            ->get()
            ->keyBy('field_id');

        // Combine fields with their values
        $fieldsWithValues = [];
        foreach ($customFields as $field) {
            $value = $customFieldValues[$field->id]->value ?? '';
            $fieldsWithValues[] = [
                'id' => $field->id,
                'name' => $field->name,
                'value' => $value,
            ];
        }

        return response()->json($fieldsWithValues);
    }

    public function receipt(Request $request)
    {
        if(!empty($request->product_id))
        {
            $subproduct = SubProduct::where('chassis_no',$request->product_id)
                ->with(['productService.brand', 'productService.category'])
                ->latest()
                ->first();
            
            if (!$subproduct) {
                return redirect()->back()->with('error', 'Product not found.');
            }

            $quantity  = $request->quantity;
            $barcode  = [
                'barcodeType' => Auth::user()->barcodeType() == '' ? 'code128' : Auth::user()->barcodeType(),
                'barcodeFormat' => Auth::user()->barcodeFormat() == '' ? 'css' : Auth::user()->barcodeFormat(),
            ];
            
            // Build labeling with product name
            $labeling = $subproduct->productService->name . ' | ';
            
            // Add brand if exists
            $brandName = '';
            if ($subproduct->productService->brand) {
                $brandName = $subproduct->productService->brand->name . ' | ';
            }
            
            // Add selected custom fields
            $selectedCustomFields = [];
            if ($request->has('custom_fields') && is_array($request->custom_fields)) {
                $customFieldIds = $request->custom_fields;
                $customFieldValues = CustomFieldValue::where('record_id', $subproduct->id)
                    ->whereIn('field_id', $customFieldIds)
                    ->with('customField')
                    ->get();
                
                foreach ($customFieldValues as $cfValue) {
                    if ($cfValue->customField) {
                        $selectedCustomFields[] = $cfValue->customField->name . ' : ' . $cfValue->value;
                    }
                }
            }
            
            // Combine all labeling parts
            $labeling = $subproduct->productService->name . ' | ' . $brandName . implode(' | ', $selectedCustomFields);
            
            // Step 1: Get base sale price (without VAT)
            $baseSalePrice = $subproduct->get_price_list_sale_price();
            
            // Step 2: Add VAT from parent product's tax_id
            $priceWithVat = $baseSalePrice;
            $totalTaxRate = 0;
            
            if ($subproduct->productService && !empty($subproduct->productService->tax_id)) {
                // Handle comma-separated tax IDs
                $taxIds = explode(',', $subproduct->productService->tax_id);
                
                foreach ($taxIds as $taxId) {
                    $taxId = trim($taxId);
                    if (!empty($taxId)) {
                        $tax = \App\Models\Tax::find($taxId);
                        if ($tax) {
                            $totalTaxRate += (float) $tax->rate;
                        }
                    }
                }
                
                // Calculate price with VAT: basePrice * (1 + VAT/100)
                if ($totalTaxRate > 0) {
                    $priceWithVat = $baseSalePrice * (1 + ($totalTaxRate / 100));
                }
            }
            
            // Step 3: Apply discount if any
            $discount = 0;
            $old_price = 0.0;
            $price_on_ticket = $priceWithVat;

            if ($request->has('discount')){
                $discount = (int)$request->discount;
                if($discount != 0 && $discount < 100){
                    // New price is the price with VAT (after discount)
                    $price_on_ticket = $priceWithVat;
                    // Calculate old price (before discount) using reverse formula
                    $old_price = $priceWithVat / (1 - ($discount / 100));
                } else if($discount == 100){
                    // Handle 100% discount case
                    $price_on_ticket = 0;
                    // For 100% discount, show double the price as original
                    $old_price = $priceWithVat * 2;
                }
            } else {
                // No discount, old_price is same as price_on_ticket
                $old_price = $priceWithVat;
            }
            
            // Round to integer as before
            $price_on_ticket = (int)round($price_on_ticket);
            
            // Get currency symbol
            $currencySymbol = \Auth::user()->currencySymbol();
            
            // Prepare data for view
            $productName = $subproduct->productService->name;
            $brand = $subproduct->productService->brand ? $subproduct->productService->brand->name : '';
        }
        else
        {
            return redirect()->back()->with('error', 'Product is required.');
        }
        
        return view('pos.receipt', compact(
            'subproduct', 
            'barcode', 
            'quantity', 
            'labeling', 
            'price_on_ticket', 
            'old_price',
            'discount',
            'productName',
            'brand',
            'currencySymbol',
            'selectedCustomFields'
        ));
    }

    public function cartdiscount(Request $request)
    {

        // return response()->json($request);
        if ($request->tax){
            $tax = Tax::find($request->tax);

            if($request->discount){
                $sess = session()->get('pos');
                $subtotal = !empty($sess)?array_sum(array_column($sess, 'subtotal')):0;
                $discount = $request->discount;
                $total = ($subtotal - $discount)+(($subtotal - $discount)*($tax->rate/100));
                $total = User::priceFormats($total);

            }else{
                $sess = session()->get('pos');
                $subtotal = !empty($sess)?array_sum(array_column($sess, 'subtotal')):0;
                $discount = 0;
                $total = ($subtotal - $discount)+((($subtotal - $discount))*($tax->rate/100));
                $total = User::priceFormats($total);
            }
            return response()->json(['total' => $total], '200');
        }else{

            if($request->discount){
                $sess = session()->get('pos');
                $subtotal = !empty($sess)?array_sum(array_column($sess, 'subtotal')):0;
                $discount = $request->discount;
                $total = $subtotal - $discount;
                $total = User::priceFormats($total);

            }else{
                $sess = session()->get('pos');
                $subtotal = !empty($sess)?array_sum(array_column($sess, 'subtotal')):0;
                $discount = 0;
                $total = $subtotal - $discount;
                $total = User::priceFormats($total);
            }

            return response()->json(['total' => $total], '200');
        }

    }

    public function pos($pos_id)
    {
        $settings = Utility::settings();
        $posId   = Crypt::decrypt($pos_id);
        $pos  = Pos::where('id', $posId)->first();

        $posPayment = PosPayment::where('pos_id', $pos->id)->first();



        $data  = DB::table('settings');
        $data  = $data->where('created_by', '=', $pos->created_by);
        $data1 = $data->get();

        foreach($data1 as $row)
        {
            $settings[$row->name] = $row->value;
        }

        $customer = $pos->customer;

        $totalTaxPrice = 0;
        $totalQuantity = 0;
        $totalRate     = 0;
        $totalDiscount = 0;
        $taxesData     = [];
        $items         = [];

        foreach($pos->items as $product)
        {

            $item              = new \stdClass();
            $item->name        = !empty($product->product) ? $product->product->name : '';
            $item->quantity    = $product->quantity;
            $item->tax         = $pos->tax;
            $item->discount    = $product->discount;
            $item->price       = $product->price;
            $item->description = $product->description;
            $totalQuantity += $item->quantity;
            $totalRate     += $item->price;
            $totalDiscount += $item->discount;
            $taxes     = Utility::tax($product->tax);
            $itemTaxes = [];
            if(!empty($item->tax))
            {
                foreach($taxes as $tax)
                {
                    $taxPrice      = Utility::taxRate($tax->rate, $item->price, $item->quantity);
                    $totalTaxPrice += $taxPrice;

                    $itemTax['name']  = $tax->name;
                    $itemTax['rate']  = $tax->rate . '%';
                    $itemTax['price'] = Utility::priceFormat($settings, $taxPrice);
                    $itemTaxes[]      = $itemTax;


                    if(array_key_exists($tax->name, $taxesData))
                    {
                        $taxesData[$tax->name] = $taxesData[$tax->name] + $taxPrice;
                    }
                    else
                    {
                        $taxesData[$tax->name] = $taxPrice;
                    }

                }

                $item->itemTax = $itemTaxes;
            }
            else
            {
                $item->itemTax = [];
            }
            $items[] = $item;
        }

        $pos->itemData      = $items;
        $pos->totalTaxPrice = $totalTaxPrice;
        $pos->totalQuantity = $totalQuantity;
        $pos->totalRate     = $totalRate;
        $pos->totalDiscount = $totalDiscount;
        $pos->taxesData     = $taxesData;


        $logo         = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $pos_logo = Utility::getValByName('pos_logo');
        if(isset($pos_logo) && !empty($pos_logo))
        {
            $img = Utility::get_file('pos_logo/') . $pos_logo;
        }
        else{
            $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }

        if($pos)
        {
            $color      = '#' . $settings['pos_color'];
            $font_color = Utility::getFontColor($color);

            return view('pos.templates.' . $settings['pos_template'], compact('pos','posPayment', 'color', 'settings', 'customer', 'img', 'font_color'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

    }

    public function previewPos($template, $color)
    {

        $objUser  = \Auth::user();
        $settings = Utility::settings();

        $pos     = new Pos();
        //        $posPayment = PosPayment::where('pos_id', $pos->id)->first();
        $posPayment     = new posPayment();
        $posPayment->amount=360;
        $posPayment->discount=100;

        $customer = new \stdClass();
        $customer->email            = '<Email>';
        $customer->shipping_name    = '<Customer Name>';
        $customer->shipping_country = '<Country>';
        $customer->shipping_state   = '<State>';
        $customer->shipping_city    = '<City>';
        $customer->shipping_phone   = '<Customer Phone Number>';
        $customer->shipping_zip     = '<Zip>';
        $customer->shipping_address = '<Address>';
        $customer->billing_name     = '<Customer Name>';
        $customer->billing_country  = '<Country>';
        $customer->billing_state    = '<State>';
        $customer->billing_city     = '<City>';
        $customer->billing_phone    = '<Customer Phone Number>';
        $customer->billing_zip      = '<Zip>';
        $customer->billing_address  = '<Address>';

        $totalTaxPrice = 0;
        $taxesData     = [];
        $items         = [];
        for($i = 1; $i <= 3; $i++)
        {
            $item           = new \stdClass();
            $item->name     = 'Item ' . $i;
            $item->quantity = 1;
            $item->tax      = 5;
            $item->discount = 50;
            $item->price    = 100;

            $taxes = [
                'Tax 1',
                'Tax 2',
            ];

            $itemTaxes = [];
            foreach($taxes as $k => $tax)
            {
                $taxPrice         = 10;
                $totalTaxPrice    += $taxPrice;
                $itemTax['name']  = 'Tax ' . $k;
                $itemTax['rate']  = '10 %';
                $itemTax['price'] = '$10';
                $itemTaxes[]      = $itemTax;
                if(array_key_exists('Tax ' . $k, $taxesData))
                {
                    $taxesData['Tax ' . $k] = $taxesData['Tax 1'] + $taxPrice;
                }
                else
                {
                    $taxesData['Tax ' . $k] = $taxPrice;
                }
            }
            $item->itemTax = $itemTaxes;
            $items[]       = $item;
        }

        $pos->pos_id    = 1;

        $pos->issue_date = date('Y-m-d H:i:s');
        //        $pos->due_date   = date('Y-m-d H:i:s');
        $pos->itemData   = $items;

        $pos->totalTaxPrice = 60;
        $pos->totalQuantity = 3;
        $pos->totalRate     = 300;
        $pos->totalDiscount = 10;
        $pos->taxesData     = $taxesData;
        $pos->created_by     = $objUser->creatorId();
        $preview      = 1;
        $color        = '#' . $color;
        $font_color   = Utility::getFontColor($color);
        $logo         = asset(Storage::url('uploads/logo/'));

        $company_logo = Utility::getValByName('company_logo_dark');
        $settings_data = \App\Models\Utility::settingsById($pos->created_by);
        $pos_logo = $settings_data['pos_logo'];

        if(isset($pos_logo) && !empty($pos_logo))
        {
            $img = Utility::get_file('pos_logo/') . $pos_logo;
        }
        else{
            $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }


        return view('pos.templates.' . $template, compact('pos', 'preview', 'color', 'img', 'settings', 'customer', 'font_color','posPayment'));
    }



    public function savePosTemplateSettings(Request $request)
    {

        $post = $request->all();
        unset($post['_token']);

        if(isset($post['pos_template']) && (!isset($post['pos_color']) || empty($post['pos_color'])))
        {
            $post['pos_color'] = "ffffff";
        }


        if($request->pos_logo)
        {
            $dir = 'pos_logo/';
            $pos_logo = \Auth::user()->id . '_pos_logo.png';
            $validation =[
                'mimes:'.'png',
                'max:'.'20480',
            ];
            $path = Utility::upload_file($request,'pos_logo',$pos_logo,$dir,$validation);
            if($path['flag']==0)
            {
                return redirect()->back()->with('error', __($path['msg']));
            }
            $post['pos_logo'] = $pos_logo;
        }
        //        dd($post);


        foreach($post as $key => $data)
        {
            \DB::insert(
                'insert into settings (`value`, `name`,`created_by`) values (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ', [
                    $data,
                    $key,
                    \Auth::user()->creatorId(),
                ]
            );
        }

        return redirect()->back()->with('success', __('POS Setting updated successfully'));
    }


    //for thermal print
    public function printView(Request $request)
    {
        // Check permission for printing POS
        if(!\Auth::user()->can('manage pos') && !\Auth::user()->can('add pos') && !\Auth::user()->can('print pos'))
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
        
        $customer_pay = (float)$request->payments;
       
        $sess = session()->get('pos');
        // Initialize vouchers - will be loaded from database if pos_id is provided
        $vouchers = null;
        $tax_id = session()->get('tax_id');

        $user = Auth::user();
        $settings = Utility::settings();
        $creatorId = $user->creatorId();

        $customer = Customer::where('id', '=', $request->vc_name)->where('created_by', $creatorId)->first();
        
        // Get warehouse with proper access check
        $warehouse = null;
        $warehouseId = $request->warehouse_name;
        
        if ($warehouseId) {
            // First, find the warehouse (don't filter by created_by yet)
            $warehouse = warehouse::where('id', '=', $warehouseId)->first();
            
            if ($warehouse) {
                $hasWarehouseAccess = false;
                
                // Option 1: Warehouse is assigned to the user via pivot table (highest priority)
                $isAssigned = \DB::table('user_warehouses')
                    ->where('user_id', $user->id)
                    ->where('warehouse_id', $warehouseId)
                    ->exists();
                
                if ($isAssigned) {
                    $hasWarehouseAccess = true;
                }
                
                // Option 2: Warehouse belongs to the same company
                if (!$hasWarehouseAccess && $warehouse->created_by == $creatorId) {
                    $hasWarehouseAccess = true;
                }
                
                // Option 3: User is company type
                if (!$hasWarehouseAccess && ($user->type == 'company' || $user->type == 'super admin')) {
                    if ($warehouse->created_by == $creatorId) {
                        $hasWarehouseAccess = true;
                    }
                }
                
                // If no access, try to use first assigned warehouse as fallback
                if (!$hasWarehouseAccess) {
                    $assignedWarehouses = $user->warehouses()->pluck('warehouses.id')->toArray();
                    if (!empty($assignedWarehouses)) {
                        $warehouseId = $assignedWarehouses[0];
                        $warehouse = warehouse::where('id', '=', $warehouseId)->first();
                    } else {
                        $warehouse = null;
                    }
                }
            }
        }
        
        // If still no warehouse, try to get first assigned warehouse
        if (!$warehouse) {
            $assignedWarehouses = $user->warehouses()->pluck('warehouses.id')->toArray();
            if (!empty($assignedWarehouses)) {
                $warehouse = warehouse::where('id', '=', $assignedWarehouses[0])->first();
            }
        }

        // Initialize variables
        $pos = null;
        $loadedFromDatabase = false;

        // Get POS ID: use from request if provided, otherwise generate new one
        // BUT: If we loaded POS from database, use the actual saved POS ID (not preview/generated)
        $posIdNumeric = null;
        
        // CRITICAL FIX: Try to load POS from database first if pos_id is provided
        if ($request->has('pos_id') && !empty($request->pos_id)) {
            $posIdString = $request->pos_id;
            
            // Extract numeric part from pos_id (handles both formatted "POS000506" and numeric "506")
            $posIdNumericFromRequest = preg_replace('/[^0-9]/', '', (string)$posIdString);
            
            if (!empty($posIdNumericFromRequest)) {
                $posIdNumericFromRequest = (int)$posIdNumericFromRequest;
                
                // Try to find POS by numeric pos_id (database stores as integer)
                $pos = Pos::where('created_by', $creatorId)
                    ->where('pos_id', $posIdNumericFromRequest)
                    ->first();
                
                if ($pos) {
                    $posIdNumeric = $pos->pos_id;
                    $loadedFromDatabase = true;
                    \Log::info('POS PrintView: Found POS by numeric ID, using saved POS ID', [
                        'requested_pos_id' => $posIdString,
                        'extracted_numeric' => $posIdNumericFromRequest,
                        'saved_pos_id' => $posIdNumeric,
                        'formatted' => $user->posNumberFormat($posIdNumeric)
                    ]);
                } else {
                    \Log::warning('POS PrintView: POS not found in database', [
                        'requested_pos_id' => $posIdString,
                        'extracted_numeric' => $posIdNumericFromRequest,
                        'creator_id' => $creatorId
                    ]);
                }
            }
        }
        
        // If POS was not loaded from database, extract from request or generate new one
        if (!$posIdNumeric) {
        if ($request->has('pos_id') && !empty($request->pos_id)) {
            // Extract numeric part from formatted POS ID (e.g., "POS00007" -> 7)
            $posIdNumeric = preg_replace('/[^0-9]/', '', $request->pos_id);
            $posIdNumeric = !empty($posIdNumeric) ? (int)$posIdNumeric : null;
        }
        
        // If no valid pos_id in request, generate new one
        if (!$posIdNumeric) {
            $posIdNumeric = $this->invoicePosNumber();
            }
        }

        $details = [
            'pos_id' => $user->posNumberFormat($posIdNumeric),
            'customer' => $customer != null ? $customer->toArray() : [],
            'warehouse' => $warehouse != null ? $warehouse->toArray() : [],
            'user' => $user != null ? $user->toArray() : [],
            'date' => date('Y-m-d'),
            'pay' => 'show',
        ];

        // Get warehouse name safely
        $warehouseName = !empty($details['warehouse']) && isset($details['warehouse']['name']) 
            ? ucfirst($details['warehouse']['name']) 
            : __('Warehouse');

        if (!empty($details['customer']))
        {
            $warehousedetails = '<h7 class="text-dark">' . $warehouseName . '</p></h7>';
            $details['customer']['billing_state'] = isset($details['customer']['billing_state']) && $details['customer']['billing_state'] != '' ? ", " . $details['customer']['billing_state'] : '';
            $details['customer']['shipping_state'] = isset($details['customer']['shipping_state']) && $details['customer']['shipping_state'] != '' ? ", " . $details['customer']['shipping_state'] : '';
            $customerdetails = '<h6 class="text-dark">' . ucfirst($details['customer']['name']) . '<p class="m-0 h6 font-weight-normal">' . (isset($details['customer']['billing_phone']) ? $details['customer']['billing_phone'] : '') . '</p>' . '<p class="m-0 h6 font-weight-normal">' . (isset($details['customer']['billing_address']) ? $details['customer']['billing_address'] : '') . '</p>' . '<p class="m-0 h6 font-weight-normal">' . (isset($details['customer']['billing_city']) ? $details['customer']['billing_city'] : '') . $details['customer']['billing_state'] . '</p>' . '<p class="m-0 h6 font-weight-normal">' . (isset($details['customer']['billing_country']) ? $details['customer']['billing_country'] : '') . '</p>' . '<p class="m-0 h6 font-weight-normal">' . (isset($details['customer']['billing_zip']) ? $details['customer']['billing_zip'] : '') . '</p></h6>';
            $shippdetails = '<h6 class="text-dark"><b>' . ucfirst($details['customer']['name']) . '</b>' . '<p class="m-0 h6 font-weight-normal">' . (isset($details['customer']['shipping_phone']) ? $details['customer']['shipping_phone'] : '') . '</p>' . '<p class="m-0 h6 font-weight-normal">' . (isset($details['customer']['shipping_address']) ? $details['customer']['shipping_address'] : '') . '</p>' . '<p class="m-0 h6 font-weight-normal">' . (isset($details['customer']['shipping_city']) ? $details['customer']['shipping_city'] : '') . $details['customer']['shipping_state'] . '</p>' . '<p class="m-0 h6 font-weight-normal">' . (isset($details['customer']['shipping_country']) ? $details['customer']['shipping_country'] : '') . '</p>' . '<p class="m-0 h6 font-weight-normal">' . (isset($details['customer']['shipping_zip']) ? $details['customer']['shipping_zip'] : '') . '</p></h6>';

        }
        else {
            $customerdetails = '<h2 class="h6"><b>' . __('Walk-in Customer') . '</b><h2>';
            $warehousedetails = '<h7 class="text-dark">' . $warehouseName . '</p></h7>';
            $shippdetails = '-';

        }


        $settings['company_telephone'] = $settings['company_telephone'] != '' ? ", " . $settings['company_telephone'] : '';
        $settings['company_state']     = $settings['company_state'] != '' ? ", " . $settings['company_state'] : '';

        $userdetails = '<h6 class="text-dark"><b>' . ucfirst($details['user']['name']) . ' </b> <h2  class="font-weight-normal">' . '<p class="m-0 font-weight-normal">' . $settings['company_name'] . $settings['company_telephone'] . '</p>' . '<p class="m-0 font-weight-normal">' . $settings['company_address'] . '</p>' . '<p class="m-0 h6 font-weight-normal">' . $settings['company_city'] . $settings['company_state'] . '</p>' . '<p class="m-0 font-weight-normal">' . $settings['company_country'] . '</p>' . '<p class="m-0 font-weight-normal">' . $settings['company_zipcode'] . '</p></h2>';

        $details['customer']['details'] = $customerdetails;
        $details['warehouse']['details'] = $warehousedetails;
        //
        $details['customer']['shippdetails'] = $shippdetails;

        $details['user']['details'] = $userdetails;

        $mainsubtotal = 0;
        $sales        = [];
        $taxOb = $tax_id != null ? Tax::where('id',$tax_id)->first()->rate : 0;
        // return response()->json($taxOb);
        
        // Ensure $sess is an array (handle null case when session is not available)
        if (empty($sess) || !is_array($sess)) {
            $sess = [];
        }
        
        // Initialize payment methods array (will be loaded from database or request)
        $paymentMethods = [];
        
        // If pos_id is provided, ALWAYS try to load from database first
        // This is critical for "Print Like Voucher" after payment when session is cleared
        // We prioritize database data over session when pos_id is provided
        $loadedFromDatabase = false;
        if ($request->has('pos_id') && !empty($request->pos_id)) {
            $posIdString = $request->pos_id;
            $pos = null;
            
            // Try to find POS by exact pos_id match first
            $pos = Pos::where('created_by', $creatorId)
                ->where('pos_id', $posIdString)
                ->first();
            
            // If not found, try to find by extracting numeric part
            if (!$pos) {
                $posIdNumeric = preg_replace('/[^0-9]/', '', $posIdString);
                if (!empty($posIdNumeric)) {
                    $allPos = Pos::where('created_by', $creatorId)->get();
                    foreach ($allPos as $p) {
                        $pIdNum = preg_replace('/[^0-9]/', '', $p->pos_id);
                        if ((int)$pIdNum == (int)$posIdNumeric) {
                            $pos = $p;
                            break;
                        }
                    }
                }
            }
            
            // If still not found and session is empty, try to get the most recent POS for this user
            // This is a fallback for when pos_id format doesn't match
            if (!$pos && (empty($sess) || count($sess) == 0)) {
                $pos = Pos::where('created_by', $creatorId)
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($pos) {
                    \Log::info('POS PrintView: Using most recent POS as fallback', [
                        'requested_pos_id' => $request->pos_id,
                        'found_pos_id' => $pos->pos_id,
                        'user_id' => Auth::id()
                    ]);
                }
            }
            
            if ($pos) {
                // Load POS items from database with full relationships
                $posItems = $pos->items()->with([
                    'product.category', 
                    'product.brand', 
                    'product.subBrand',
                    'sub_product.productService.category',
                    'sub_product.productService.brand',
                    'sub_product.productService.subBrand'
                ])->get();
                
                // Convert POS items to session format
                // IMPORTANT: Reset $sess so we don't keep old session items AND database items (which would duplicate lines)
                $sess = [];
                foreach ($posItems as $index => $item) {
                    // Get product and build full name path
                    $product = null;
                    $productName = '';
                    $categoryName = '';
                    $brandName = '';
                    $subBrandName = '';
                    $fullProductName = '';
                    
                    if ($item->product) {
                        $product = $item->product;
                        $productName = $product->name ?? '';
                        
                        // Load relationships if not already loaded
                        if (!$product->relationLoaded('category')) {
                            $product->load('category');
                        }
                        if (!$product->relationLoaded('brand')) {
                            $product->load('brand');
                        }
                        if (!$product->relationLoaded('subBrand')) {
                            $product->load('subBrand');
                        }
                        
                        // Get category, brand, subBrand names
                        if ($product->category) {
                            $categoryName = $product->category->name ?? '';
                        }
                        if ($product->brand) {
                            $brandName = $product->brand->name ?? '';
                        }
                        if ($product->subBrand) {
                            $subBrandName = $product->subBrand->name ?? '';
                        }
                    } elseif ($item->sub_product && $item->sub_product->productService) {
                        $product = $item->sub_product->productService;
                        $productName = $product->name ?? '';
                        
                        // Load relationships if not already loaded
                        if (!$product->relationLoaded('category')) {
                            $product->load('category');
                        }
                        if (!$product->relationLoaded('brand')) {
                            $product->load('brand');
                        }
                        if (!$product->relationLoaded('subBrand')) {
                            $product->load('subBrand');
                        }
                        
                        // Get category, brand, subBrand names
                        if ($product->category) {
                            $categoryName = $product->category->name ?? '';
                        }
                        if ($product->brand) {
                            $brandName = $product->brand->name ?? '';
                        }
                        if ($product->subBrand) {
                            $subBrandName = $product->subBrand->name ?? '';
                        }
                    }
                    
                    // Build full product name: Category → Brand → Sub Brand → Name
                    $nameParts = [];
                    if (!empty($categoryName)) {
                        $nameParts[] = $categoryName;
                    }
                    if (!empty($brandName)) {
                        $nameParts[] = $brandName;
                    }
                    if (!empty($subBrandName)) {
                        $nameParts[] = $subBrandName;
                    }
                    if (!empty($productName)) {
                        $nameParts[] = $productName;
                    }
                    $fullProductName = !empty($nameParts) ? implode(' → ', $nameParts) : $productName;
                    
                    // Get combo text and combo_price first
                    $compoText = '';
                    $compoId = (int)($item->compo_id ?? 0);
                    $comboPrice = null;
                    
                    if ($compoId != 0) {
                        $compo = ComboOffer::find($compoId);
                        if ($compo) {
                            if ($compo->type == 'bogo') {
                                $compoText = 'buy: ' . $compo->buy_quantity . '| get: ' . $compo->get_quantity;
                            } else {
                                $compoText = 'buy: ' . $compo->buy_quantity . '| for: ' . $compo->tiered_price;
                            }
                        }
                        
                        // Get combo_price from database item
                        if ($item->combo_price !== null) {
                            $comboPrice = (float)($item->combo_price ?? 0);
                        }
                    }
                    
                    // Calculate subtotal - use combo_price if available, otherwise use regular price
                    $itemPrice = (float)($item->price ?? 0);
                    $itemQuantity = (int)($item->quantity ?? 0);
                    $itemDiscount = (float)($item->discount ?? 0);
                    
                    // Use combo_price as base price if available (same logic as view.blade.php)
                    $basePrice = $itemPrice;
                    if ($comboPrice !== null) {
                        $basePrice = $comboPrice;
                    }
                    
                    // Calculate subtotal: (basePrice - discount) * quantity
                    $itemSubtotal = ($basePrice - ($basePrice * ($itemDiscount / 100))) * $itemQuantity;
                    
                    // Use item ID or index as key
                    $key = $item->id ?? $index;
                    $sess[$key] = [
                        'name' => $fullProductName, // Use full product name path: Category → Brand → Sub Brand → Name
                        'quantity' => $itemQuantity,
                        'price' => $itemPrice,
                        'subtotal' => $itemSubtotal,
                        'discount' => $itemDiscount,
                        'compo_id' => $compoId,
                        'compo_text' => $compoText,
                        'combo_price' => $comboPrice, // Include combo_price for display
                    ];
                }
                
                // Load vouchers from database when loading from pos_id (ALWAYS load when pos_id is provided)
                // This ensures vouchers are displayed correctly in "Print Like Voucher"
                if ($request->has('pos_id') && $pos) {
                    $vouchers = [];
                    
                    // Try to find voucher entries by ref_id (pos database ID)
                    $voucherLedgerEntries = GeneralLedger::where('ref_id', $pos->id)
                        ->where('reference', 'POS Payment')
                        ->where('type', 'LIKE', '%Voucher Payment_%')
                        ->where('debit', '>', 0)
                        ->get();
                    
                    // If no entries found, try searching by POS number format in type field
                    if ($voucherLedgerEntries->count() == 0) {
                        $posNumberFormatted = Auth::user()->posNumberFormat($pos->pos_id);
                        $voucherLedgerEntries = GeneralLedger::where('type', 'LIKE', '%' . $posNumberFormatted . 'Voucher Payment_%')
                            ->where('reference', 'POS Payment')
                            ->where('debit', '>', 0)
                            ->get();
                    }
                    
                    \Log::info('POS PrintView: Loading vouchers', [
                        'pos_id' => $pos->id,
                        'pos_pos_id' => $pos->pos_id,
                        'entries_found' => $voucherLedgerEntries->count(),
                        'request_pos_id' => $request->pos_id
                    ]);
                    
                    foreach($voucherLedgerEntries as $entry) {
                        \Log::info('POS PrintView: Processing voucher entry', [
                            'entry_type' => $entry->type,
                            'entry_debit' => $entry->debit,
                            'entry_ref_id' => $entry->ref_id,
                            'entry_id' => $entry->id
                        ]);
                        
                        // Match pattern: {POS_NUMBER}Voucher Payment_{voucher_id}
                        // Example: POS00033Voucher Payment_22
                        if(preg_match('/Voucher Payment_(\d+)/', $entry->type, $matches)) {
                            $voucherId = $matches[1];
                            \Log::info('POS PrintView: Extracted voucher ID', ['voucher_id' => $voucherId]);
                            
                            if(!isset($vouchers[$voucherId])) {
                                $voucher = Voucher::find($voucherId);
                                if($voucher) {
                                    $vouchers[$voucherId] = [
                                        'id' => $voucher->id,
                                        'amount' => $entry->debit
                                    ];
                                    \Log::info('POS PrintView: Added voucher', [
                                        'voucher_id' => $voucher->id,
                                        'amount' => $entry->debit
                                    ]);
                                } else {
                                    \Log::warning('POS PrintView: Voucher not found in database', ['voucher_id' => $voucherId]);
                                    // Still add the voucher even if not found in Voucher table (use the ID from the entry)
                                    $vouchers[$voucherId] = [
                                        'id' => $voucherId,
                                        'amount' => $entry->debit
                                    ];
                                }
                            } else {
                                // If voucher already exists, add to amount (in case of multiple entries)
                                $vouchers[$voucherId]['amount'] += $entry->debit;
                                \Log::info('POS PrintView: Updated voucher amount', [
                                    'voucher_id' => $voucherId,
                                    'new_amount' => $vouchers[$voucherId]['amount']
                                ]);
                            }
                        } else {
                            \Log::warning('POS PrintView: Could not extract voucher ID from type', ['type' => $entry->type]);
                        }
                    }
                    
                    \Log::info('POS PrintView: Final vouchers array', [
                        'vouchers' => $vouchers,
                        'count' => count($vouchers),
                        'vouchers_keys' => array_keys($vouchers)
                    ]);
                }
                
                // Ensure vouchers is always an array (fallback to session or empty array)
                // Only use session if we didn't load from database
                if (!isset($vouchers) || (!is_array($vouchers) && $vouchers !== null)) {
                    $vouchers = session()->get('vouchers', []);
                    \Log::info('POS PrintView: Using vouchers from session', ['count' => count($vouchers)]);
                }
                
                // Final fallback: ensure it's an array
                if (!is_array($vouchers)) {
                    $vouchers = [];
                }
                
                // Try to load payment methods from request first (if available)
                if (empty($paymentMethods) || count($paymentMethods) == 0) {
                    // Check for payment_methods array (direct format)
                    if ($request->has('payment_methods') && is_array($request->payment_methods)) {
                        $paymentMethods = $request->payment_methods;
                    }
                    // Check for amounts array (from POS form: amounts[payment_method_id] = amount)
                    elseif ($request->has('amounts') && is_array($request->amounts)) {
                        $amounts = $request->amounts;
                        foreach ($amounts as $methodId => $amount) {
                            if ((float)$amount > 0) {
                                $paymentMethod = PaymentMethod::find($methodId);
                                if ($paymentMethod) {
                                    $found = false;
                                    foreach($paymentMethods as &$pm) {
                                        if($pm['id'] == $paymentMethod->id) {
                                            $pm['amount'] += (float)$amount;
                                            $found = true;
                                            break;
                                        }
                                    }
                                    if(!$found) {
                                        $paymentMethods[] = [
                                            'id' => $paymentMethod->id,
                                            'name' => $paymentMethod->name,
                                            'amount' => (float)$amount
                                        ];
                                    }
                                } else {
                                    // Cash payment (no payment method ID or methodId is null/0)
                                    $found = false;
                                    foreach($paymentMethods as &$pm) {
                                        if(($pm['id'] == null || $pm['id'] == 0) && ($pm['name'] == 'Cash' || $pm['name'] == null)) {
                                            $pm['amount'] += (float)$amount;
                                            $found = true;
                                            break;
                                        }
                                    }
                                    if(!$found) {
                                        $paymentMethods[] = [
                                            'id' => null,
                                            'name' => 'Cash',
                                            'amount' => (float)$amount
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Load payment methods if we're loading from database (have pos_id) and payment methods are empty
                \Log::info('POS PrintView: Checking payment methods loading condition', [
                    'has_pos_id' => $request->has('pos_id'),
                    'request_pos_id' => $request->pos_id ?? 'not set',
                    'pos_exists' => isset($pos),
                    'pos_id_value' => isset($pos) ? $pos->id : 'pos not set',
                    'paymentMethods_empty' => empty($paymentMethods),
                    'paymentMethods_count' => count($paymentMethods)
                ]);
                
                if ($request->has('pos_id') && isset($pos) && (empty($paymentMethods) || count($paymentMethods) == 0)) {
                    // Load ALL PosPayment records (including those without payment_method_id for cash)
                    $posPayments = PosPayment::where('pos_id', $pos->id)->get();
                    
                    \Log::info('POS PrintView: Loading payment methods from database', [
                        'pos_id' => $pos->id,
                        'pos_pos_id' => $pos->pos_id,
                        'posPayments_count' => $posPayments->count(),
                        'posPayments_data' => $posPayments->map(function($pp) {
                            return [
                                'id' => $pp->id,
                                'pos_id' => $pp->pos_id,
                                'payment_method_id' => $pp->payment_method_id,
                                'amount' => $pp->amount,
                                'payment_method_name' => $pp->payment_method_id ? (PaymentMethod::find($pp->payment_method_id)->name ?? 'not found') : 'null/0 (Cash)'
                            ];
                        })->toArray()
                    ]);
                    
                    foreach($posPayments as $posPayment) {
                        if($posPayment->amount > 0) {
                            \Log::info('POS PrintView: Processing PosPayment', [
                                'posPayment_id' => $posPayment->id,
                                'payment_method_id' => $posPayment->payment_method_id,
                                'amount' => $posPayment->amount,
                                'current_paymentMethods_count' => count($paymentMethods)
                            ]);
                            
                            // Check if payment_method_id exists and is not 0/null
                            if($posPayment->payment_method_id && $posPayment->payment_method_id != 0) {
                                // Has payment method (card, etc.)
                                $paymentMethod = PaymentMethod::find($posPayment->payment_method_id);
                                if($paymentMethod) {
                                    \Log::info('POS PrintView: Found PaymentMethod', [
                                        'payment_method_id' => $paymentMethod->id,
                                        'payment_method_name' => $paymentMethod->name
                                    ]);
                                    
                                    // Check if payment method name is "Cash" (case-insensitive)
                                    $isCashMethod = stripos($paymentMethod->name, 'cash') !== false;
                                    
                                    if($isCashMethod) {
                                        // This is a Cash payment method - aggregate with other cash payments
                                        \Log::info('POS PrintView: Identified as Cash payment method');
                                        $found = false;
                                        foreach($paymentMethods as &$pm) {
                                            if(($pm['id'] == null || $pm['id'] == 0) || (isset($pm['id']) && $pm['id'] == $paymentMethod->id) || stripos($pm['name'] ?? '', 'cash') !== false) {
                                                $oldAmount = $pm['amount'];
                                                $pm['amount'] += $posPayment->amount;
                                                $pm['id'] = null; // Normalize to null for cash
                                                $pm['name'] = 'Cash'; // Normalize name
                                                $found = true;
                                                \Log::info('POS PrintView: Aggregated Cash payment', [
                                                    'old_amount' => $oldAmount,
                                                    'new_amount' => $pm['amount']
                                                ]);
                                                break;
                                            }
                                        }
                                        if(!$found) {
                                            $paymentMethods[] = [
                                                'id' => null,
                                                'name' => 'Cash',
                                                'amount' => $posPayment->amount
                                            ];
                                            \Log::info('POS PrintView: Added new Cash payment', [
                                                'amount' => $posPayment->amount
                                            ]);
                                        }
                                    } else {
                                        // Regular payment method (card, etc.) - aggregate by ID
                                        \Log::info('POS PrintView: Identified as regular payment method', [
                                            'method_name' => $paymentMethod->name
                                        ]);
                                        $found = false;
                                        foreach($paymentMethods as &$pm) {
                                            if(isset($pm['id']) && $pm['id'] == $paymentMethod->id && stripos($pm['name'] ?? '', 'cash') === false) {
                                                $oldAmount = $pm['amount'];
                                                $pm['amount'] += $posPayment->amount;
                                                $found = true;
                                                \Log::info('POS PrintView: Aggregated payment method', [
                                                    'method_id' => $paymentMethod->id,
                                                    'method_name' => $paymentMethod->name,
                                                    'old_amount' => $oldAmount,
                                                    'new_amount' => $pm['amount']
                                                ]);
                                                break;
                                            }
                                        }
                                        unset($pm); // CRITICAL: Unset reference to prevent PHP reference bugs
                                        if(!$found) {
                                            $paymentMethods[] = [
                                                'id' => $paymentMethod->id,
                                                'name' => $paymentMethod->name,
                                                'amount' => $posPayment->amount
                                            ];
                                            \Log::info('POS PrintView: Added new payment method', [
                                                'method_id' => $paymentMethod->id,
                                                'method_name' => $paymentMethod->name,
                                                'amount' => $posPayment->amount
                                            ]);
                                        }
                                    }
                                }
                            } else {
                                // No payment method ID or ID is 0/null = Cash payment
                                \Log::info('POS PrintView: Identified as Cash (no payment_method_id)');
                                $found = false;
                                foreach($paymentMethods as &$pm) {
                                    if(($pm['id'] == null || $pm['id'] == 0) || stripos($pm['name'] ?? '', 'cash') !== false) {
                                        $oldAmount = $pm['amount'];
                                        $pm['amount'] += $posPayment->amount;
                                        $pm['id'] = null; // Normalize to null
                                        $pm['name'] = 'Cash'; // Normalize name
                                        $found = true;
                                        \Log::info('POS PrintView: Aggregated Cash payment (no ID)', [
                                            'old_amount' => $oldAmount,
                                            'new_amount' => $pm['amount']
                                        ]);
                                        break;
                                    }
                                }
                                unset($pm); // CRITICAL: Unset reference to prevent PHP reference bugs
                                if(!$found) {
                                    $paymentMethods[] = [
                                        'id' => null,
                                        'name' => 'Cash',
                                        'amount' => $posPayment->amount
                                    ];
                                    \Log::info('POS PrintView: Added new Cash payment (no ID)', [
                                        'amount' => $posPayment->amount
                                    ]);
                                }
                            }
                        }
                    }
                    
                    \Log::info('POS PrintView: Payment methods after loading from database', [
                        'paymentMethods_count' => count($paymentMethods),
                        'paymentMethods_data' => $paymentMethods
                    ]);
                    
                    // CRITICAL: Unset foreach references to prevent PHP reference bugs
                    // PHP foreach with &$pm leaves the reference variable pointing to the last element
                    // This can cause unexpected modifications if $pm is used later
                    unset($pm);
                }
                
                // CRITICAL: Make a copy of payment methods to prevent accidental modification
                // This ensures the array structure is preserved
                if (!empty($paymentMethods) && is_array($paymentMethods)) {
                    $paymentMethods = array_values(array_map(function($pm) {
                        return [
                            'id' => $pm['id'] ?? null,
                            'name' => $pm['name'] ?? 'Payment',
                            'amount' => (float)($pm['amount'] ?? 0)
                        ];
                    }, $paymentMethods));
                    
                    \Log::info('POS PrintView: Payment methods after normalization', [
                        'paymentMethods_count' => count($paymentMethods),
                        'paymentMethods_data' => $paymentMethods
                    ]);
                }
                
                // Update tax_id from POS if not in session
                if (empty($tax_id) && $pos->tax_id) {
                    $tax_id = $pos->tax_id;
                    $taxOb = 0;
                    if (!empty($pos->tax_id)) {
                        $taxes = explode(",", $pos->tax_id);
                        foreach ($taxes as $tax) {
                            $taxModel = Tax::where('id', trim($tax))->first();
                            if ($taxModel) {
                                $taxOb += $taxModel->rate;
                            }
                        }
                    }
                }
                
                // Update customer_pay from payment methods if available (more accurate than calculating)
                // Calculate customer_pay from actual payment methods amounts
                // CRITICAL: Use a copy to prevent modifying the original array
                if (!empty($paymentMethods) && count($paymentMethods) > 0) {
                    $totalPaymentFromMethods = 0;
                    $paymentMethodsCopy = array_map(function($pm) {
                        return [
                            'id' => $pm['id'] ?? null,
                            'name' => $pm['name'] ?? 'Payment',
                            'amount' => (float)($pm['amount'] ?? 0)
                        ];
                    }, $paymentMethods);
                    
                    foreach($paymentMethodsCopy as $pm) {
                        $totalPaymentFromMethods += (float)($pm['amount'] ?? 0);
                    }
                    if ($totalPaymentFromMethods > 0) {
                        $customer_pay = $totalPaymentFromMethods;
                    }
                    
                    // Ensure paymentMethods is not modified
                    // Restore from copy if needed (shouldn't be necessary, but safety check)
                    if (count($paymentMethods) != count($paymentMethodsCopy)) {
                        \Log::warning('POS PrintView: Payment methods array was modified during customer_pay calculation!', [
                            'original_count' => count($paymentMethods),
                            'copy_count' => count($paymentMethodsCopy),
                            'original_data' => $paymentMethods,
                            'copy_data' => $paymentMethodsCopy
                        ]);
                        $paymentMethods = $paymentMethodsCopy;
                    }
                }
                
                // If still no customer_pay, try to get from PosPayment records
                if (empty($customer_pay) || $customer_pay == 0) {
                    $posPayments = PosPayment::where('pos_id', $pos->id)->get();
                    if ($posPayments->count() > 0) {
                        $totalPaymentAmount = 0;
                        foreach($posPayments as $posPayment) {
                            $totalPaymentAmount += (float)($posPayment->amount ?? 0);
                        }
                        if ($totalPaymentAmount > 0) {
                            $customer_pay = $totalPaymentAmount;
                        }
                    }
                }
                
                // Update discount from POS if not in request
                if (empty($request->discount) && $pos->discount) {
                    $request->merge(['discount' => $pos->discount]);
                }
                
                // Mark that we successfully loaded from database
                $loadedFromDatabase = true;
                
                // CRITICAL FIX: Update details['pos_id'] to use the actual saved POS ID from database
                // This ensures printview shows the correct POS number that was actually saved
                if (isset($pos) && $pos && isset($details)) {
                    $details['pos_id'] = $user->posNumberFormat($pos->pos_id);
                    \Log::info('POS PrintView: Updated details[pos_id] to use saved POS ID', [
                        'requested_pos_id' => $request->pos_id,
                        'saved_pos_id' => $pos->pos_id,
                        'formatted_pos_id' => $details['pos_id']
                    ]);
                }
                
                // Log successful database load for debugging
                \Log::info('POS PrintView: Loaded items from database', [
                    'pos_id' => $request->pos_id,
                    'saved_pos_id' => isset($pos) ? $pos->pos_id : 'not set',
                    'items_count' => count($sess),
                    'user_id' => Auth::id()
                ]);
            } else {
                // Log when pos_id is provided but POS not found
                \Log::warning('POS PrintView: pos_id provided but POS not found in database', [
                    'pos_id' => $request->pos_id,
                    'user_id' => Auth::id(),
                    'creator_id' => $creatorId
                ]);
            }
        }
        
        // If we loaded from database but got no items, log a warning
        if ($loadedFromDatabase && empty($sess)) {
            \Log::warning('POS PrintView: Loaded from database but no items found', [
                'pos_id' => $request->pos_id,
                'user_id' => Auth::id()
            ]);
        }
        
        foreach ($sess as $key => $value) {

            $subtotal = $value['subtotal'];
            $tax      = $subtotal * ($taxOb / 100);
            $sales['data'][$key]['name']       = $value['name'];
            $sales['data'][$key]['quantity']   = $value['quantity'];
            $sales['data'][$key]['discount']   = $value['discount'];
            $sales['data'][$key]['price']      = Auth::user()->priceFormat($value['price']);
            $sales['data'][$key]['tax']        = $taxOb . '%';
            $sales['data'][$key]['product_tax']        = $tax;
            $sales['data'][$key]['tax_amount'] = Auth::user()->priceFormat($tax);
            $sales['data'][$key]['subtotal']   = Auth::user()->priceFormat($value['subtotal']);
            $text = '';
            if (isset($value['compo_id']) && $value['compo_id'] != 0){
                if (!empty($value['compo_text'])) {
                    $text = $value['compo_text'];
                } else {
                    $compo = ComboOffer::find($value['compo_id']);
                    if ($compo) {
                        if ($compo->type == 'bogo'){
                            $text = 'buy: '.$compo->buy_quantity . '| get: '.$compo->get_quantity;
                        }else{
                            $text = 'buy: '.$compo->buy_quantity . '| for: '.$compo->tiered_price;
                        }
                    }
                }
            }
            $sales['data'][$key]['compo_text'] = $text;
            $sales['data'][$key]['compo_id'] = $value['compo_id'] ?? 0;
            // Include combo_price if available (for display in receipt)
            $sales['data'][$key]['combo_price'] = $value['combo_price'] ?? null;
            $mainsubtotal += $value['subtotal'];
        }
        
        // Only load vouchers from session if they weren't already loaded from database
        // This prevents overwriting vouchers loaded from database when pos_id is provided
        if (!isset($vouchers) || !is_array($vouchers) || empty($vouchers)) {
            $vouchers = session()->get('vouchers', []);
        }
        
        $vouchers_amount = 0.0;
        
        if(!empty($vouchers) && is_array($vouchers)){
            foreach ($vouchers as $key => $value) {
                if (is_array($value)) {
                    $vouchers_amount += (float)($value['amount'] ?? 0);
                } else {
                    $vouchers_amount += (float)$value;
                }
            }
        }

        $discount=!empty($request->discount)?$request->discount:0;
        $sales['discount'] = Auth::user()->priceFormat($discount);
        $sales['discount_amount'] = $discount; // Add raw discount amount
        
        // Calculate tax amount on full subtotal (BEFORE voucher deduction)
        $taxAmount = $mainsubtotal * ($taxOb / 100);
        // Round tax amount to 2 decimal places (normal rounding)
        $taxAmount = round($taxAmount, 2);
        
        // Calculate total: subtotal + tax - discount, then apply voucher AFTER tax
        $total = $mainsubtotal + $taxAmount - $discount - $vouchers_amount;
        // Round to nearest whole number (37.5 becomes 38)
        $total = round($total);
        
        $customer_return = $customer_pay - $total ;
        $sales['sub_total'] = Auth::user()->priceFormat($mainsubtotal);
        $sales['total'] = Auth::user()->priceFormat($total);
        $sales['total_number'] = $total; // Add raw total number for JavaScript calculations
        $sales['tax_amount'] = Auth::user()->priceFormat($taxAmount);
        $sales['tax_rate'] = $taxOb;

        //for barcode

        $productServices = ProductService::where('created_by', '=', \Auth::user()->creatorId())->get();
        $barcode  = [
            'barcodeType' => Auth::user()->barcodeType() ,
            'barcodeFormat' => Auth::user()->barcodeFormat(),
        ];
        
        // Extract payment methods from request if available (only if not already loaded from database)
        // Payment methods should already be loaded from database if pos_id was provided
        // CRITICAL: Never overwrite database-loaded payment methods
        
        // CRITICAL: Create a deep copy to prevent any reference issues
        // PHP foreach with &$pm can cause reference bugs that modify the array unexpectedly
        if (!empty($paymentMethods) && is_array($paymentMethods)) {
            $paymentMethodsBackup = array_map(function($pm) {
                return [
                    'id' => $pm['id'] ?? null,
                    'name' => $pm['name'] ?? 'Payment',
                    'amount' => (float)($pm['amount'] ?? 0)
                ];
            }, $paymentMethods);
            $paymentMethods = $paymentMethodsBackup;
            unset($paymentMethodsBackup);
        }
        
        $hasDatabasePaymentMethods = !empty($paymentMethods) && is_array($paymentMethods) && count($paymentMethods) > 0;
        
        \Log::info('POS PrintView: Before final payment methods check', [
            'hasDatabasePaymentMethods' => $hasDatabasePaymentMethods,
            'paymentMethods_count' => count($paymentMethods ?? []),
            'paymentMethods_data' => $paymentMethods ?? [],
            'request_has_amounts' => $request->has('amounts'),
            'request_amounts' => $request->has('amounts') ? $request->amounts : 'not set'
        ]);
        
        // Only load from request if we DON'T have database-loaded payment methods
        if (!$hasDatabasePaymentMethods) {
            if ($request->has('amounts') && is_array($request->amounts)) {
                $paymentMethods = [];
                foreach ($request->amounts as $methodId => $amount) {
                    if ((float)$amount > 0) {
                        $paymentMethod = PaymentMethod::find($methodId);
                        if ($paymentMethod) {
                            // Check if it's a cash payment method
                            $isCashMethod = stripos($paymentMethod->name, 'cash') !== false;
                            if ($isCashMethod) {
                                $paymentMethods[] = [
                                    'id' => null,
                                    'name' => 'Cash',
                                    'amount' => (float)$amount
                                ];
                            } else {
                                $paymentMethods[] = [
                                    'id' => $paymentMethod->id,
                                    'name' => $paymentMethod->name,
                                    'amount' => (float)$amount
                                ];
                            }
                        } else {
                            // Cash payment (no payment method ID)
                            $paymentMethods[] = [
                                'id' => null,
                                'name' => 'Cash',
                                'amount' => (float)$amount
                            ];
                        }
                    }
                }
                
                \Log::info('POS PrintView: Loaded payment methods from request', [
                    'paymentMethods_count' => count($paymentMethods),
                    'paymentMethods_data' => $paymentMethods
                ]);
            }
        } else {
            \Log::info('POS PrintView: Skipping request load - using database-loaded payment methods', [
                'paymentMethods_count' => count($paymentMethods),
                'paymentMethods_data' => $paymentMethods
            ]);
        }
        
        // Final log to confirm payment methods before passing to view
        \Log::info('POS PrintView: Final payment methods before view', [
            'paymentMethods_count' => count($paymentMethods ?? []),
            'paymentMethods_data' => $paymentMethods ?? []
        ]);
            
        // session()->forget('pos');
        // session()->forget('tax_id');
        
        // return response()->json(compact('vouchers','vouchers_amount','details', 'sales', 'customer','productServices','barcode','customer_pay','customer_return'));
        // Pass original session data for direct print (before formatting)
        $originalSessionData = $sess ?? [];
        return view('pos.printview', compact('vouchers','vouchers_amount','details', 'sales', 'customer','productServices','barcode','customer_pay','customer_return', 'settings', 'originalSessionData', 'paymentMethods'));

    }

    public function printtemp(){
        // Check permission for printing POS
        if(!\Auth::user()->can('manage pos') && !\Auth::user()->can('add pos') && !\Auth::user()->can('print pos'))
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
        
        return view('pos.temp-printview');
    }

    /**
     * Direct print to Epson TM-M30 using escpos-php
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function directPrint(Request $request)
    {
        // Check permission for printing POS
        if(!\Auth::user()->can('manage pos') && !\Auth::user()->can('add pos') && !\Auth::user()->can('print pos'))
        {
            return response()->json([
                'success' => false,
                'error' => __('Permission denied.')
            ], 403);
        }
        
        // Add debug mode - return immediately to test if method is being called
        if ($request->has('debug') && $request->debug == '1') {
            return response()->json([
                'success' => true,
                'debug' => true,
                'message' => 'Direct print method is accessible',
                'request_data' => $request->all(),
                'session_pos' => session()->has('pos'),
                'session_pos_count' => count(session()->get('pos', [])),
                'user_id' => Auth::id(),
                'printer_ip' => $request->printer_ip ?? Utility::settings()['pos_printer_ip'] ?? 'not set'
            ]);
        }

        try {
            // Log the request for debugging
            \Log::info('Direct Print Request (Queue Mode):', [
                'request_data' => $request->all(),
                'session_pos' => session()->has('pos'),
                'session_pos_count' => count(session()->get('pos', [])),
                'user_id' => Auth::id(),
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type')
            ]);

            $customer_pay = (float)$request->payments;
            
            // Try to get data from request first (passed directly from view), fallback to session
            $sess = null;
            if ($request->has('sales_data') && !empty($request->sales_data)) {
                $sess = $request->sales_data;
                \Log::info('Using sales_data from request', ['count' => is_array($sess) ? count($sess) : 'not array']);
            } else {
                $sess = session()->get('pos');
                \Log::info('Using sales_data from session', ['count' => is_array($sess) ? count($sess) : 'not array']);
            }
            
            $vouchers = [];
            if ($request->has('vouchers') && !empty($request->vouchers)) {
                $vouchers = $request->vouchers;
            } else {
                $vouchers = session()->get('vouchers', []);
            }
            
            $tax_id = $request->has('tax_id') && $request->tax_id !== null
                ? $request->tax_id
                : session()->get('tax_id');

            // Validate data exists
            if (empty($sess) || (!is_array($sess) && !is_object($sess))) {
                \Log::error('Direct Print Error: No POS data found', [
                    'has_sales_data' => $request->has('sales_data'),
                    'sales_data_type' => gettype($request->sales_data),
                    'sales_data_count' => is_array($request->sales_data) ? count($request->sales_data) : (is_object($request->sales_data) ? 'object' : 'empty'),
                    'session_pos' => session()->has('pos'),
                    'session_pos_count' => is_array(session()->get('pos')) ? count(session()->get('pos')) : 0,
                    'request_all' => $request->all()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'No items in cart. Please add items before printing. Data not found in request or session.'
                ], 400);
            }
            
            // Convert object to array if needed
            if (is_object($sess)) {
                $sess = (array)$sess;
            }
            
            // Ensure vouchers is an array
            if (!is_array($vouchers)) {
                $vouchers = [];
            }

            $user = Auth::user();
            if (!$user) {
                \Log::error('Direct Print Error: User not authenticated');
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $settings = Utility::settings();
            $creatorId = $user->creatorId();

            $customer = Customer::where('id', '=', $request->vc_name)->where('created_by', $creatorId)->first();
            
            // Get warehouse with proper access check
            $warehouse = null;
            $warehouseId = $request->warehouse_name;
            
            if ($warehouseId) {
                // First, find the warehouse (don't filter by created_by yet)
                $warehouse = warehouse::with('country')->where('id', '=', $warehouseId)->first();
                
                if ($warehouse) {
                    $hasWarehouseAccess = false;
                    
                    // Option 1: Warehouse is assigned to the user via pivot table (highest priority)
                    $isAssigned = \DB::table('user_warehouses')
                        ->where('user_id', $user->id)
                        ->where('warehouse_id', $warehouseId)
                        ->exists();
                    
                    if ($isAssigned) {
                        $hasWarehouseAccess = true;
                    }
                    
                    // Option 2: Warehouse belongs to the same company
                    if (!$hasWarehouseAccess && $warehouse->created_by == $creatorId) {
                        $hasWarehouseAccess = true;
                    }
                    
                    // Option 3: User is company type
                    if (!$hasWarehouseAccess && ($user->type == 'company' || $user->type == 'super admin')) {
                        if ($warehouse->created_by == $creatorId) {
                            $hasWarehouseAccess = true;
                        }
                    }
                    
                    // If no access, try to use first assigned warehouse as fallback
                    if (!$hasWarehouseAccess) {
                        $assignedWarehouses = $user->warehouses()->pluck('warehouses.id')->toArray();
                        if (!empty($assignedWarehouses)) {
                            $warehouseId = $assignedWarehouses[0];
                            $warehouse = warehouse::with('country')->where('id', '=', $warehouseId)->first();
                        } else {
                            $warehouse = null;
                        }
                    }
                }
            }
            
            // If still no warehouse, try to get first assigned warehouse
            if (!$warehouse) {
                $assignedWarehouses = $user->warehouses()->pluck('warehouses.id')->toArray();
                if (!empty($assignedWarehouses)) {
                    $warehouse = warehouse::with('country')->where('id', '=', $assignedWarehouses[0])->first();
                }
            }

            // Get printer settings (IP address and port)
            $printer_ip = $request->printer_ip ?? $settings['pos_printer_ip'] ?? '10.255.254.17';
            $printer_port = $request->printer_port ?? $settings['pos_printer_port'] ?? 9100;

            // Get POS ID: use from request if provided, otherwise generate new one
            $posIdNumeric = null;
            if ($request->has('pos_id') && !empty($request->pos_id)) {
                // Extract numeric part from formatted POS ID (e.g., "POS00007" -> 7)
                $posIdNumeric = preg_replace('/[^0-9]/', '', $request->pos_id);
                $posIdNumeric = !empty($posIdNumeric) ? (int)$posIdNumeric : null;
            }
            
            // If no valid pos_id in request, generate new one
            if (!$posIdNumeric) {
                $posIdNumeric = $this->invoicePosNumber();
            }

            // Prepare data
            $pos_id = $user->posNumberFormat($posIdNumeric);
            $date = date('Y-m-d H:i:s');
            
            // Helper function to extract numeric value from formatted price (e.g., "340.00Dhs" -> 340.00)
            $extractNumeric = function($value) {
                if (is_numeric($value)) {
                    return (float)$value;
                }
                // Remove all non-numeric characters except decimal point
                $numeric = preg_replace('/[^0-9.]/', '', (string)$value);
                return (float)$numeric;
            };
            
            // Calculate totals
            $mainsubtotal = 0;
            $taxOb = $tax_id != null ? Tax::where('id',$tax_id)->first()->rate : 0;
            
            // Handle both session format and request format
            foreach ($sess as $key => $value) {
                // Convert to array if object
                if (is_object($value)) {
                    $value = (array)$value;
                }
                // Get subtotal - handle different data structures and formatted prices
                $subtotal_raw = $value['subtotal'] ?? $value['sub_total'] ?? 0;
                $subtotal = $extractNumeric($subtotal_raw);
                $mainsubtotal += $subtotal;
            }
            
            $vouchers_amount = 0.0;
            if(!empty($vouchers)){
                foreach ($vouchers as $value) {
                    // Convert to array if object
                    if (is_object($value)) {
                        $value = (array)$value;
                    }
                    $vouchers_amount += (float)($value['amount'] ?? 0);
                }
            }

            $discount = !empty($request->discount) ? (float)$request->discount : 0;
            
            // Calculate tax amount on full subtotal (BEFORE voucher deduction)
            $taxAmount = $mainsubtotal * ($taxOb / 100);
            // Round tax amount to 2 decimal places
            $taxAmount = round($taxAmount, 2);
            
            // Calculate total: subtotal + tax - discount, then apply voucher AFTER tax
            $total = $mainsubtotal + $taxAmount - $discount - $vouchers_amount;
            // Round total to 2 decimal places (keep cents)
            $total = round($total, 2);
            
            // Use customer_return from request if available, otherwise calculate
            $customer_return = $request->has('customer_return') 
                ? (float)$request->customer_return 
                : ($customer_pay - $total);

            // Validate printer IP
            if (empty($printer_ip) || $printer_ip === '0.0.0.0') {
                \Log::error('Direct Print Error: Printer IP not configured', ['printer_ip' => $printer_ip]);
                return response()->json([
                    'success' => false,
                    'message' => 'Printer IP address not configured. Please set pos_printer_ip in settings.'
                ], 400);
            }

            // Instead of printing directly, queue the job for local print service
            // This works even when server and printer are on different networks
            $printData = [
                'pos_id' => $pos_id,
                'date' => $date,
                'customer' => $customer ? [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'billing_phone' => $customer->billing_phone ?? '',
                    'billing_address' => $customer->billing_address ?? '',
                ] : null,
                'warehouse' => $warehouse ? [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'logo' => $warehouse->logo,
                    'company_name' => $warehouse->company_name,
                    'address' => $warehouse->address,
                    'city' => $warehouse->city,
                    'city_zip' => $warehouse->city_zip,
                    'country' => $warehouse->country ? $warehouse->country->name : null,
                ] : null,
                'items' => [],
                'vouchers' => $vouchers,
                'totals' => [
                    'subtotal' => $mainsubtotal,
                    'tax_rate' => $taxOb,
                    'tax_amount' => $mainsubtotal * ($taxOb / 100),
                    'discount' => $discount,
                    'vouchers_amount' => $vouchers_amount,
                    'total' => $total,
                    'customer_pay' => $customer_pay,
                    'customer_return' => $customer_return,
                ],
                'settings' => [
                    'company_name' => $settings['company_name'] ?? 'Company Name',
                    'company_address' => $settings['company_address'] ?? '',
                    'company_city' => $settings['company_city'] ?? '',
                    'company_state' => $settings['company_state'] ?? '',
                    'company_zipcode' => $settings['company_zipcode'] ?? '',
                    'company_country' => $settings['company_country'] ?? '',
                    'company_telephone' => $settings['company_telephone'] ?? '',
                    'mail_from_address' => $settings['mail_from_address'] ?? '',
                    'company_logo_dark' => $settings['company_logo_dark'] ?? '',
                    'company_logo' => $settings['company_logo'] ?? '',
                ],
                'logo_path' => \App\Models\Utility::get_file('uploads/logo'),
            ];

            // Process items
            foreach ($sess as $key => $value) {
                if (is_object($value)) {
                    $value = (array)$value;
                }
                
                // Generate compo_text if compo_id exists but compo_text is missing or invalid
                $compoText = $value['compo_text'] ?? null;
                // Check if compo_text is null, empty, or the string "null"
                $needsCompoText = empty($compoText) || 
                                 $compoText === 'null' || 
                                 $compoText === null || 
                                 trim($compoText) === '';
                
                if ($needsCompoText && !empty($value['compo_id']) && $value['compo_id'] != 0 && $value['compo_id'] != '0') {
                    $combo = \App\Models\ComboOffer::find($value['compo_id']);
                    if ($combo) {
                        if ($combo->type == 'bogo') {
                            $compoText = 'buy: ' . $combo->buy_quantity . '| get: ' . $combo->get_quantity;
                        } else {
                            $compoText = 'buy: ' . $combo->buy_quantity . '| for: ' . $combo->tiered_price;
                        }
                    }
                }
                
                // Set to null if still empty after generation attempt
                if (empty($compoText) || $compoText === 'null' || trim($compoText) === '') {
                    $compoText = null;
                }
                
                // Calculate combo_price if combo is applied
                $comboPrice = null;
                $compoId = $value['compo_id'] ?? 0;
                if ($compoId != 0 && $compoId != '0') {
                    $quantity = $value['quantity'] ?? 1;
                    $subtotal = $extractNumeric($value['subtotal'] ?? $value['sub_total'] ?? 0);
                    if ($quantity > 0 && $subtotal > 0) {
                        // Combo price = effective price per item after combo is applied
                        $comboPrice = $subtotal / $quantity;
                    }
                }
                
                $printData['items'][] = [
                    'name' => $value['name'] ?? '',
                    'quantity' => $value['quantity'] ?? 0,
                    'price' => $extractNumeric($value['price'] ?? 0),
                    'discount' => $value['discount'] ?? 0,
                    'subtotal' => $extractNumeric($value['subtotal'] ?? $value['sub_total'] ?? 0),
                    'tax' => $taxOb,
                    'compo_text' => $compoText,
                    'compo_id' => $compoId,
                    'combo_price' => $comboPrice,
                ];
            }
            
            // Get payment methods if available in request
            $paymentMethods = [];
            
            // Check for payment_methods array (direct format)
            if ($request->has('payment_methods') && is_array($request->payment_methods)) {
                // Payment methods passed as array with name and amount
                $paymentMethods = $request->payment_methods;
            } 
            // Check for amounts array (from POS form: amounts[payment_method_id] = amount)
            elseif ($request->has('amounts') && is_array($request->amounts)) {
                foreach ($request->amounts as $methodId => $amount) {
                    if ((float)$amount > 0) {
                        $paymentMethod = PaymentMethod::find($methodId);
                        if ($paymentMethod) {
                            $paymentMethods[] = [
                                'id' => $paymentMethod->id,
                                'name' => $paymentMethod->name,
                                'amount' => (float)$amount
                            ];
                        }
                    }
                }
            }
            // Check for payments array (alternative format: payments[payment_method_id] = amount)
            elseif ($request->has('payments') && is_array($request->payments) && !is_numeric($request->payments)) {
                // Payment methods passed as array with method ID as key and amount as value
                foreach ($request->payments as $methodId => $amount) {
                    if ((float)$amount > 0) {
                        $paymentMethod = PaymentMethod::find($methodId);
                        if ($paymentMethod) {
                            $paymentMethods[] = [
                                'id' => $paymentMethod->id,
                                'name' => $paymentMethod->name,
                                'amount' => (float)$amount
                            ];
                        }
                    }
                }
            }
            // Try to retrieve payment methods from saved POS record if reference_id exists
            if (empty($paymentMethods) && !empty($pos_id)) {
                // Try to find POS by ID (extract numeric ID from formatted POS ID)
                $posIdNumeric = preg_replace('/[^0-9]/', '', $pos_id);
                if ($posIdNumeric) {
                    $pos = Pos::where('pos_id', 'LIKE', '%' . $posIdNumeric . '%')
                        ->where('created_by', $user->creatorId())
                        ->orderBy('id', 'desc')
                        ->first();
                    
                    if ($pos) {
                        // Get payment methods from GeneralLedger entries
                        $generalLedgerEntries = GeneralLedger::where('ref_id', $pos->id)
                            ->whereIn('reference', ['POS_payment', 'POS'])
                            ->where('debit', '>', 0)
                            ->where('user_id', 0)
                            ->get();
                        
                        foreach($generalLedgerEntries as $entry) {
                            $chartAccount = ChartOfAccount::find($entry->account);
                            if($chartAccount) {
                                $bankAccount = BankAccount::where('chart_account_id', $chartAccount->id)->first();
                                if($bankAccount) {
                                    $paymentMethod = PaymentMethod::where('bank_account_id', $bankAccount->id)
                                        ->where('warehouse_id', $warehouse ? $warehouse->id : null)
                                        ->first();
                                    if($paymentMethod) {
                                        // Check if payment method already exists, if so add to amount
                                        $found = false;
                                        foreach($paymentMethods as &$pm) {
                                            if($pm['id'] == $paymentMethod->id) {
                                                $pm['amount'] += $entry->debit;
                                                $found = true;
                                                break;
                                            }
                                        }
                                        if(!$found) {
                                            $paymentMethods[] = [
                                                'id' => $paymentMethod->id,
                                                'name' => $paymentMethod->name,
                                                'amount' => $entry->debit
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            $printData['payment_methods'] = $paymentMethods;

            // Create print job in database
            $printJob = PrintJob::create([
                'job_type' => 'pos_receipt',
                'reference_id' => $pos_id,
                'user_id' => $user->id,
                'printer_ip' => $printer_ip,
                'printer_port' => $printer_port,
                'print_data' => $printData,
                'status' => 'pending',
            ]);

            \Log::info('Print job queued successfully', [
                'job_id' => $printJob->id,
                'pos_id' => $pos_id,
                'printer_ip' => $printer_ip,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Print job queued successfully. The local print service will process it automatically.',
                'job_id' => $printJob->id,
                'queue_info' => 'A local print service running on your POS computer will pick up this job and print it directly to the printer.'
            ]);

        } catch (\Exception $e) {
            // Log the full error with stack trace
            \Log::error('Direct Print Exception:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to queue print job: ' . $e->getMessage(),
                'error_details' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        } catch (\Throwable $e) {
            // Catch any other throwable errors
            \Log::error('Direct Print Fatal Error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to queue print job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending print jobs for local print service
     * This endpoint is called by the local print service running on POS computer
     */
    public function getPendingPrintJobs(Request $request)
    {
        try {
            $user = null;
            $creatorId = null;
            
            // Check if user is authenticated via session (for web users)
            if (Auth::check()) {
                $user = Auth::user();
                $creatorId = $user->creatorId();
            } else {
                // Fallback to token-based authentication (for local print services)
                $token = $request->header('X-Print-Service-Token') ?? $request->input('token');
                $expectedToken = config('print.agent_token', env('PRINT_AGENT_TOKEN', 'default-token-change-me'));
                
                if ($token !== $expectedToken) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized. Invalid token or session required.'
                    ], 401);
                }
                // For token-based auth, don't filter by creator (allows local print service to see all jobs)
            }

            // Build query for pending jobs
            $query = PrintJob::pending();
            
            // Filter by user's company if authenticated user (not token-based)
            if ($creatorId !== null) {
                // Get all user IDs that belong to the same company
                $companyUserIds = \App\Models\User::where('created_by', $creatorId)
                    ->orWhere('id', $creatorId)
                    ->pluck('id')
                    ->toArray();
                
                $query->whereIn('user_id', $companyUserIds);
            }

            // Get pending jobs, ordered by creation time
            $jobs = $query->orderBy('created_at', 'asc')
                ->limit(10) // Process up to 10 jobs at a time
                ->get();

            return response()->json([
                'success' => true,
                'jobs' => $jobs->map(function ($job) {
                    return [
                        'id' => $job->id,
                        'job_type' => $job->job_type,
                        'reference_id' => $job->reference_id,
                        'printer_ip' => $job->printer_ip,
                        'printer_port' => $job->printer_port,
                        'print_data' => $job->print_data,
                        'created_at' => $job->created_at->toIso8601String(),
                    ];
                }),
                'count' => $jobs->count()
            ]);
        } catch (\Exception $e) {
            \Log::error('Get Pending Print Jobs Error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve print jobs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark a print job as completed
     */
    public function completePrintJob(Request $request, $id)
    {
        try {
            $user = null;
            $creatorId = null;
            
            // Check if user is authenticated via session (for web users)
            if (Auth::check()) {
                $user = Auth::user();
                $creatorId = $user->creatorId();
            } else {
                // Fallback to token-based authentication (for local print services)
                $token = $request->header('X-Print-Service-Token') ?? $request->input('token');
                $expectedToken = config('print.agent_token', env('PRINT_AGENT_TOKEN', 'default-token-change-me'));
                
                if ($token !== $expectedToken) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized. Invalid token or session required.'
                    ], 401);
                }
            }

            $job = PrintJob::findOrFail($id);
            
            // Verify job belongs to user's company if authenticated user (not token-based)
            if ($creatorId !== null) {
                $companyUserIds = \App\Models\User::where('created_by', $creatorId)
                    ->orWhere('id', $creatorId)
                    ->pluck('id')
                    ->toArray();
                
                if (!in_array($job->user_id, $companyUserIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized. You do not have permission to complete this job.'
                    ], 403);
                }
            }
            
            $job->markAsCompleted();

            return response()->json([
                'success' => true,
                'message' => 'Print job marked as completed'
            ]);
        } catch (\Exception $e) {
            \Log::error('Complete Print Job Error:', [
                'job_id' => $id,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete print job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get cashiers for a specific warehouse (AJAX endpoint)
     * Returns users assigned to the warehouse with "Cashier" role
     */
    public function getCashiersForWarehouseAjax(Request $request)
    {
        // CRITICAL: Clear any output buffers to prevent HTML from being sent
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Log immediately to verify method is being called
        \Log::info('=== getCashiersForWarehouseAjax METHOD CALLED ===', [
            'timestamp' => now()->toDateTimeString(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'accept_header' => $request->header('Accept'),
            'x_requested_with' => $request->header('X-Requested-With'),
            'ob_level' => ob_get_level(),
            'is_ajax' => $request->ajax(),
            'expects_json' => $request->expectsJson(),
            'all_headers' => $request->headers->all()
        ]);
        
        // Ensure this is treated as a JSON request
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        
        try {
            // Verify user is authenticated
            if (!\Auth::check()) {
                \Log::error('User not authenticated in getCashiersForWarehouseAjax');
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401, [
                    'Content-Type' => 'application/json; charset=utf-8'
                ], JSON_UNESCAPED_UNICODE);
            }
            
            // Get all cashiers for the company (warehouse is not needed)
            \Log::info('AJAX Cashiers Request - METHOD CALLED', [
                'creator_id' => \Auth::user()->creatorId(),
                'user_id' => \Auth::id(),
                'request_all' => $request->all(),
                'request_method' => $request->method(),
                'request_url' => $request->fullUrl(),
                'is_ajax' => $request->ajax(),
                'expects_json' => $request->expectsJson(),
                'wants_json' => $request->wantsJson()
            ]);

            \Log::info('Before calling getCashiersForWarehouse', [
                'creator_id' => \Auth::user()->creatorId()
            ]);
            
            $users = $this->getCashiersForWarehouse();
            
            \Log::info('After calling getCashiersForWarehouse', [
                'users_type' => gettype($users),
                'users_is_collection' => $users instanceof \Illuminate\Support\Collection,
                'users_count' => $users ? (method_exists($users, 'count') ? $users->count() : count($users)) : 0
            ]);
            
            // Convert collection to array - ensure it's an associative array (object in JSON)
            // The pluck('name', 'id') already returns a collection with id => name pairs
            $usersArrayFormatted = [];
            
            if ($users) {
                try {
                    // Convert collection to array - pluck already gives us id => name pairs
                    if (method_exists($users, 'toArray')) {
                        $usersArray = $users->toArray();
                    } else {
                        // Fallback: iterate directly
                        $usersArray = [];
                        foreach ($users as $id => $name) {
                            $usersArray[$id] = $name;
                        }
                    }
                    
                    // Ensure keys are strings (JavaScript object keys should be strings)
                    if (is_array($usersArray)) {
                        foreach ($usersArray as $id => $name) {
                            $usersArrayFormatted[(string)$id] = (string)$name;
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Error converting users to array', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw $e; // Re-throw to be caught by outer try-catch
                }
            }
            
            // Debug logging
            \Log::info('AJAX Cashiers Request Response', [
                'creator_id' => \Auth::user()->creatorId(),
                'users_count' => count($usersArrayFormatted),
                'users' => $usersArrayFormatted,
                'users_type' => gettype($usersArrayFormatted),
                'is_empty' => empty($usersArrayFormatted),
                'json_encoded' => json_encode($usersArrayFormatted, JSON_UNESCAPED_UNICODE),
                'first_key_type' => !empty($usersArrayFormatted) ? gettype(array_keys($usersArrayFormatted)[0]) : 'empty'
            ]);
            
            // Ensure we return proper JSON response
            $response = [
                'success' => true,
                'users' => $usersArrayFormatted, // Associative array with string keys: {"265": "rahaf"}
                'debug' => [
                    'count' => count($usersArrayFormatted),
                    'users_keys' => array_keys($usersArrayFormatted),
                    'users_keys_types' => array_map('gettype', array_keys($usersArrayFormatted))
                ]
            ];
            
            \Log::info('Returning JSON response', [
                'response' => $response,
                'json_encoded' => json_encode($response, JSON_UNESCAPED_UNICODE)
            ]);
            
            // Ensure we return JSON with proper headers
            $jsonResponse = response()->json($response, 200, [
                'Content-Type' => 'application/json; charset=utf-8',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'no-cache, no-store, must-revalidate'
            ]);
            
            // Set JSON encoding options
            $jsonResponse->setEncodingOptions(JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            
            // Log the actual response to verify it's JSON
            \Log::info('JSON Response created', [
                'content_type' => $jsonResponse->headers->get('Content-Type'),
                'status_code' => $jsonResponse->getStatusCode(),
                'response_class' => get_class($jsonResponse)
            ]);
            
            return $jsonResponse;
        } catch (\Throwable $e) {
            // Catch all errors including fatal errors
            \Log::error('Error in getCashiersForWarehouseAjax', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'exception_type' => get_class($e)
            ]);
            
            // Always return JSON, never HTML
            $errorResponse = response()->json([
                'success' => false,
                'message' => 'Error loading cashiers: ' . $e->getMessage(),
                'error_type' => get_class($e)
            ], 500, [
                'Content-Type' => 'application/json; charset=utf-8',
                'X-Content-Type-Options' => 'nosniff'
            ]);
            
            $errorResponse->setEncodingOptions(JSON_UNESCAPED_UNICODE);
            
            \Log::info('Error JSON Response created', [
                'content_type' => $errorResponse->headers->get('Content-Type'),
                'status_code' => $errorResponse->getStatusCode()
            ]);
            
            return $errorResponse;
        }
    }

    /**
     * Get cashiers for the company
     * Filters users by: created by user's creator AND has "Cashier" role
     * Returns all cashiers for the company
     */
    private function getCashiersForWarehouse()
    {
        $creatorId = \Auth::user()->creatorId();
        
        // Get all users created by the company creator (regardless of warehouse assignment)
        $allUsers = User::where('created_by', $creatorId)
            ->whereNotIn('type', ['client', 'company'])
            ->get();
        
        \Log::info('Getting cashiers for company', [
            'creator_id' => $creatorId,
            'all_users_count' => $allUsers->count()
        ]);
        
        // Filter users who have "Cashier" role (case-insensitive check)
        $cashiers = $allUsers->filter(function($user) use ($creatorId) {
            // Get all roles for the user - try multiple methods
            $roles = $user->getRoleNames();
            $rolesArray = $roles->toArray();
            
            // Also try hasRole method directly (Spatie Permission) - try both cases
            $hasCashierRoleDirect = $user->hasRole('Cashier') || $user->hasRole('cashier');
            
            // Check if user has Cashier role (case-insensitive)
            $hasCashierRole = false;
            foreach ($roles as $role) {
                // Compare lowercase versions for case-insensitive matching
                if (strtolower(trim($role)) === 'cashier') {
                    $hasCashierRole = true;
                    break;
                }
            }
            
            // Use either method
            $finalHasCashierRole = $hasCashierRole || $hasCashierRoleDirect;
            
            \Log::info('User role check', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_created_by' => $user->created_by,
                'creator_id' => $creatorId,
                'created_by_match' => $user->created_by == $creatorId,
                'user_type' => $user->type,
                'roles' => $rolesArray,
                'roles_lowercase' => array_map('strtolower', array_map('trim', $rolesArray)),
                'has_cashier_role_filter' => $hasCashierRole,
                'has_cashier_role_direct' => $hasCashierRoleDirect,
                'final_has_cashier_role' => $finalHasCashierRole
            ]);
            
            return $finalHasCashierRole;
        });
        
        \Log::info('Final cashiers result', [
            'creator_id' => $creatorId,
            'cashiers_count' => $cashiers->count(),
            'cashier_ids' => $cashiers->pluck('id')->toArray(),
            'cashier_names' => $cashiers->pluck('name')->toArray()
        ]);
        
        // If no cashiers found, log all users for debugging
        if ($cashiers->isEmpty()) {
            \Log::warning('No cashiers found - listing all users for debugging', [
                'all_users' => $allUsers->map(function($u) {
                    $roles = $u->getRoleNames()->toArray();
                    return [
                        'id' => $u->id,
                        'name' => $u->name,
                        'created_by' => $u->created_by,
                        'type' => $u->type,
                        'roles' => $roles,
                        'has_cashier_role' => $u->hasRole('Cashier') || $u->hasRole('cashier')
                    ];
                })->toArray()
            ]);
        }
        
        return $cashiers->pluck('name', 'id');
    }

    /**
     * Mark a print job as failed
     */
    public function failPrintJob(Request $request, $id)
    {
        try {
            $user = null;
            $creatorId = null;
            
            // Check if user is authenticated via session (for web users)
            if (Auth::check()) {
                $user = Auth::user();
                $creatorId = $user->creatorId();
            } else {
                // Fallback to token-based authentication (for local print services)
                $token = $request->header('X-Print-Service-Token') ?? $request->input('token');
                $expectedToken = config('print.agent_token', env('PRINT_AGENT_TOKEN', 'default-token-change-me'));
                
                if ($token !== $expectedToken) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized. Invalid token or session required.'
                    ], 401);
                }
            }

            $job = PrintJob::findOrFail($id);
            
            // Verify job belongs to user's company if authenticated user (not token-based)
            if ($creatorId !== null) {
                $companyUserIds = \App\Models\User::where('created_by', $creatorId)
                    ->orWhere('id', $creatorId)
                    ->pluck('id')
                    ->toArray();
                
                if (!in_array($job->user_id, $companyUserIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized. You do not have permission to fail this job.'
                    ], 403);
                }
            }
            
            $errorMessage = $request->input('error_message', 'Print failed');
            $job->markAsFailed($errorMessage);

            return response()->json([
                'success' => true,
                'message' => 'Print job marked as failed'
            ]);
        } catch (\Exception $e) {
            \Log::error('Fail Print Job Error:', [
                'job_id' => $id,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark print job as failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the web-based print service page (for Chrome OS)
     */
    public function printServicePage()
    {
        // Check permission for printing POS
        if(!\Auth::user()->can('manage pos') && !\Auth::user()->can('add pos') && !\Auth::user()->can('print pos'))
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
        
        return view('pos.print-service');
    }

    public function productsStock(Request $request)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();
        
        // Cache accessible warehouse IDs (used multiple times)
        $assignedWarehouseIds = $user->warehouses()->pluck('warehouses.id')->toArray();
        $accessibleWarehouseIds = !empty($assignedWarehouseIds) 
            ? $assignedWarehouseIds 
            : warehouse::where('created_by', $creatorId)->pluck('id')->toArray();
        
        // Get warehouses - filter by user's assigned warehouses if user has any, otherwise show all company warehouses
        if (!empty($assignedWarehouseIds)) {
            // User has assigned warehouses - only show those
            $warehouses = $user->warehouses()->get();
        } else {
            // No assigned warehouses - show all company warehouses (backward compatibility)
            $warehouses = warehouse::where('created_by', $creatorId)->get();
        }

        if ($request->filled('warehouse')) {
            $WHID = (int)$request->input('warehouse');
            
            // Quick access check: if warehouse is in accessible list, allow it
            if (!in_array($WHID, $accessibleWarehouseIds)) {
                // Check if user is company type and warehouse belongs to company
                $warehouse = warehouse::select('id', 'name', 'created_by')->where('id', $WHID)->first();
                if (!$warehouse || $warehouse->created_by != $creatorId || ($user->type != 'company' && $user->type != 'super admin')) {
                    // No access - fallback to first assigned warehouse
                    if (!empty($assignedWarehouseIds)) {
                        $WHID = $assignedWarehouseIds[0];
                        $warehouse = warehouse::select('id', 'name')->where('id', $WHID)->first();
                    } else {
                        // No access and no assigned warehouses - return empty result
                        $grouped = collect([]);
                        return view('pos.products', compact('grouped', 'warehouses'));
                    }
                } else {
                    $warehouse = warehouse::select('id', 'name')->where('id', $WHID)->first();
                }
            } else {
                $warehouse = warehouse::select('id', 'name')->where('id', $WHID)->first();
            }

            // Optimized: Single query to get quantities and latest IDs together
            $productsData = SubProduct::select(
                    'chassis_no',
                    DB::raw('SUM(quantity) as total_quantity'),
                    DB::raw('MAX(id) as latest_id')
                )
                ->where('warehouse_id', $WHID)
                ->where('created_by', $creatorId)
                ->whereNotNull('chassis_no')
                ->groupBy('chassis_no')
                ->havingRaw('SUM(quantity) > 0') // Filter out zero quantities early
                ->get();

            if ($productsData->isEmpty()) {
                $grouped = collect([]);
                return view('pos.products', compact('grouped', 'warehouses'));
            }

            // Get latest subproduct IDs
            $latestSubProductIds = $productsData->pluck('latest_id')->filter()->unique()->toArray();

            // Eager load all latest subproducts with relationships in one query (optimized select)
            $latestSubProducts = SubProduct::select('id', 'chassis_no', 'product_id', 'sale_price', 'price_rule_id')
                ->with([
                    'productService:id,name,sku,sale_price,purchase_price',
                    'priceRule:id,base_price_source,price_mode,value,apply_99',
                    'customFieldValues:id,record_id,field_id,value',
                    'customFieldValues.customField:id,name'
                ])
                ->whereIn('id', $latestSubProductIds)
                ->get()
                ->keyBy('chassis_no');

            // Pre-cache warehouse name
            $warehouseName = $warehouse ? $warehouse->name : '-';

            // Map data efficiently using pre-loaded collections
            $grouped = $productsData->map(function ($productData) use ($latestSubProducts, $warehouseName) {
                $latestSubProduct = $latestSubProducts->get($productData->chassis_no);
                
                if (!$latestSubProduct || !$latestSubProduct->productService) {
                    return null;
                }

                // Build labeling string (only if custom fields exist)
                $labeling = $latestSubProduct->productService->name;
                if ($latestSubProduct->customFieldValues->isNotEmpty()) {
                    $labeling .= ' | ';
                    foreach ($latestSubProduct->customFieldValues as $customFieldValue) {
                        if ($customFieldValue->customField) {
                            $labeling .= $customFieldValue->customField->name . ' : ' . $customFieldValue->value . ' | ';
                        }
                    }
                }

                return [
                    'name' => $labeling,
                    'product_no' => $productData->chassis_no,
                    'total_quantity' => $productData->total_quantity,
                    'sale_price' => $latestSubProduct->get_price_list_sale_price(),
                    'product' => $latestSubProduct->productService,
                    'price' => $latestSubProduct->sale_price,
                    'warehouse_name' => $warehouseName,
                ];
            })->filter()->values();
        } else {
            // Optimized: Single query to get quantities and latest IDs together for all warehouses
            $productsData = SubProduct::select(
                    'chassis_no',
                    'warehouse_id',
                    DB::raw('SUM(quantity) as total_quantity'),
                    DB::raw('MAX(id) as latest_id')
                )
                ->where('created_by', $creatorId)
                ->whereIn('warehouse_id', $accessibleWarehouseIds)
                ->whereNotNull('chassis_no')
                ->groupBy('chassis_no', 'warehouse_id')
                ->havingRaw('SUM(quantity) > 0') // Filter out zero quantities early
                ->get();

            if ($productsData->isEmpty()) {
                $grouped = collect([]);
                return view('pos.products', compact('grouped', 'warehouses'));
            }

            // Get latest subproduct IDs
            $latestSubProductIds = $productsData->pluck('latest_id')->filter()->unique()->toArray();

            // Eager load all latest subproducts with relationships in one query (optimized select)
            $latestSubProducts = SubProduct::select('id', 'chassis_no', 'warehouse_id', 'product_id', 'sale_price', 'price_rule_id')
                ->with([
                    'productService:id,name,sku,sale_price,purchase_price',
                    'priceRule:id,base_price_source,price_mode,value,apply_99',
                    'customFieldValues:id,record_id,field_id,value',
                    'customFieldValues.customField:id,name',
                    'warehouse:id,name'
                ])
                ->whereIn('id', $latestSubProductIds)
                ->get()
                ->keyBy(function ($item) {
                    return $item->chassis_no . '_' . $item->warehouse_id;
                });

            // Pre-load warehouse names to avoid N+1
            $warehouseNames = warehouse::whereIn('id', $accessibleWarehouseIds)
                ->pluck('name', 'id')
                ->toArray();

            // Map data efficiently
            $grouped = $productsData->map(function ($productData) use ($latestSubProducts, $warehouseNames) {
                $key = $productData->chassis_no . '_' . $productData->warehouse_id;
                $latestSubProduct = $latestSubProducts->get($key);
                
                if (!$latestSubProduct || !$latestSubProduct->productService) {
                    return null;
                }

                // Build labeling string (only if custom fields exist)
                $labeling = $latestSubProduct->productService->name;
                if ($latestSubProduct->customFieldValues->isNotEmpty()) {
                    $labeling .= ' | ';
                    foreach ($latestSubProduct->customFieldValues as $customFieldValue) {
                        if ($customFieldValue->customField) {
                            $labeling .= $customFieldValue->customField->name . ' : ' . $customFieldValue->value . ' | ';
                        }
                    }
                }

                $warehouseName = $warehouseNames[$productData->warehouse_id] ?? 
                               ($latestSubProduct->warehouse ? $latestSubProduct->warehouse->name : '-');

                return [
                    'name' => $labeling,
                    'product_no' => $productData->chassis_no,
                    'total_quantity' => $productData->total_quantity,
                    'sale_price' => $latestSubProduct->get_price_list_sale_price(),
                    'price' => $latestSubProduct->sale_price,
                    'warehouse_name' => $warehouseName,
                    'product' => $latestSubProduct->productService,
                ];
            })->filter()->values();
        }

        return view('pos.products', compact('grouped', 'warehouses'));
    }

    public function pos_ledger($pos_id)
    {
        try {
            if (\Auth::user()->can('ledger report')) {
                $start = date('Y-m-01');
                $end = date('Y-m-t');
                $chart_accounts = \App\Models\ChartOfAccount::where('created_by', \Auth::user()->creatorId())->get();
                $accounts = $chart_accounts->pluck('name', 'id');
                $generalLedgerData = \App\Models\GeneralLedger::selectRaw('vid, account, ref_id , type,user_id, SUM(credit) as total_credit, SUM(debit) as total_debit ,created_at,updated_at,send_date,deleted_qty,sub_product_id,user_type')
                    ->where('created_by', \Auth::user()->creatorId())
                    ->where('ref_id', $pos_id)
                    ->whereIn('reference', ['POS', 'POS_payment','POS Payment'])
                    ->groupBy('vid', 'account')
                    ->orderBy('id', 'ASC')
                    ->get();

                $balance = 0;
                $debit = 0;
                $credit = 0;
                $filter['balance'] = $balance;
                $filter['credit'] = $credit;
                $filter['debit'] = $debit;
                $filter['startDateRange'] = $start;
                $filter['endDateRange'] = $end;
                
                return view('report.general_ledger', compact('filter', 'chart_accounts', 'accounts', 'generalLedgerData'));
            } else {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Something went wrong.'));
        }
    }

    public function destroy(Request $request, $id)
    {
        // Only allow company users to delete POS
        if (\Auth::user()->type != 'company') {
            return redirect()->back()->with('error', __('Permission denied. Only company users can delete POS.'));
        }

        try {
            $pos = Pos::with(['customer'])->find($id);
            if (!$pos || $pos->created_by != \Auth::user()->creatorId()) {
                return redirect()->back()->with('error', __('POS not found or permission denied.'));
            }

            DB::beginTransaction();

            $creatorId = \Auth::user()->creatorId();
            
            // Get latest voucher ID for reverse entries
            $latestVoucher = GeneralLedger::where('created_by', $creatorId)->orderBy('vid', 'desc')->first();
            $newVid = $latestVoucher ? ($latestVoucher->vid + 1) : 1;
            
            // Check if voucher ID already exists
            $existingRecord = GeneralLedger::where('vid', $newVid)->where('created_by', $creatorId)->exists();
            if ($existingRecord) {
                DB::rollBack();
                return redirect()->back()->with('error', __("Something went wrong, please try again."));
            }

            // Check if POS has payment (indicates it's sold/completed)
            $posPayment = PosPayment::where('pos_id', $pos->id)->first();
            $isSold = $posPayment !== null;

            // Get customer for balance updates
            $customer = $pos->customer;
            
            // Get all POS ledger entries to reverse (both POS and POS Payment)
            $posLedgerEntries = GeneralLedger::where('ref_id', $pos->id)
                ->whereIn('reference', ['POS', 'POS Payment', 'POS_payment'])
                ->where('created_by', $creatorId)
                ->get();

            // Calculate total customer balance change from customer ledger entries
            $customerBalanceChange = 0;
            if ($customer) {
                foreach ($posLedgerEntries as $entry) {
                    if ($entry->user_id > 0 && $entry->user_id == $customer->id) {
                        // Original debit increases receivable, credit decreases receivable
                        // Reverse: credit decreases receivable, debit increases receivable
                        // So we reverse: subtract original debit, add original credit
                        $customerBalanceChange += ($entry->credit - $entry->debit);
                    }
                }
            }

            // Get current customer balance before creating entries (needed for initial balance setting)
            $currentCustomerBalance = 0;
            if ($customer) {
                $customer->refresh(); // Ensure we have the latest balance
                $currentCustomerBalance = $customer->balance ?? 0;
            }

            // Reverse POS ledger entries (swap debit and credit)
            foreach ($posLedgerEntries as $originalEntry) {
                $reverseEntry = new GeneralLedger();
                $reverseEntry->vid = $newVid;
                $reverseEntry->account = $originalEntry->account;
                $reverseEntry->type = 'POS Deletion Reversal - ' . $originalEntry->type;
                $reverseEntry->debit = $originalEntry->credit; // Swap: original credit becomes reverse debit
                $reverseEntry->credit = $originalEntry->debit; // Swap: original debit becomes reverse credit
                $reverseEntry->ref_id = $pos->id;
                $reverseEntry->user_id = $originalEntry->user_id;
                $reverseEntry->sub_product_id = $originalEntry->sub_product_id;
                $reverseEntry->created_by = $creatorId;
                $reverseEntry->send_date = $pos->pos_date;
                $reverseEntry->reference = 'POS Deletion Reversal';
                
                // Set balance - use current customer balance for customer entries, will update after balance change
                if ($originalEntry->user_id > 0 && $customer && $originalEntry->user_id == $customer->id) {
                    // Use current balance initially, will be updated after customer balance is updated
                    $reverseEntry->balance = $currentCustomerBalance;
                } else {
                    $reverseEntry->balance = $originalEntry->balance ?? 0;
                }
                
                $reverseEntry->save();
            }

            // Update customer balance if there are customer entries
            if ($customer && $customerBalanceChange != 0) {
                // If balance change is positive, we need to credit (decrease receivable)
                // If balance change is negative, we need to debit (increase receivable)
                if ($customerBalanceChange > 0) {
                    Utility::updateUserBalance('customer', $customer->id, abs($customerBalanceChange), 'credit');
                } else {
                    Utility::updateUserBalance('customer', $customer->id, abs($customerBalanceChange), 'debit');
                }
                
                // Reload customer to get updated balance
                $customer->refresh();
                
                // Update balance in reversal entries
                GeneralLedger::where('ref_id', $pos->id)
                    ->where('reference', 'POS Deletion Reversal')
                    ->where('user_id', $customer->id)
                    ->where('created_by', $creatorId)
                    ->update(['balance' => $customer->balance]);
            }

            // Process POS products for stock movement and return to subproduct
            $posProducts = PosProduct::where('pos_id', $pos->id)->get();
            foreach ($posProducts as $posProduct) {
                $subProduct = SubProduct::find($posProduct->sub_product_id);
                if ($subProduct) {
                    // Return quantity to subproduct
                    $subProduct->quantity += $posProduct->quantity;
                    // Sale flow sets booked=3 when qty goes to 0; restore to available when stock is returned
                    if ((int) $subProduct->booked === 3) {
                        $subProduct->booked = 0;
                    }
                    if ((int) $subProduct->pos_id === (int) $pos->id) {
                        $subProduct->pos_id = null;
                    }
                    $subProduct->save();
                }

                $product = ProductService::find($posProduct->product_id);
                if (!$product) continue;

                $category = $product->category;

                // Add stock movement for Qty product type when POS is sold/completed
                if ($isSold && $category && $category->type === "Qty product") {
                    // Check cost calculation method
                    $costCalculationMethod = $category->cost_calculation_method ?? 'avg';
                    
                    if ($costCalculationMethod === 'avg') {
                        // Calculate average cost using weighted average formula:
                        // Average Cost = ((Product Parent Qty × Product Avg Cost (or bill price if avg is 0)) + (New Qty × New Price)) ÷ (Product Parent Qty + New Qty)
                        // For deletion: Calculate from remaining quantity after returning sold qty
                        
                        // Get product's current quantity and average cost (before returning sold qty)
                        // We need to subtract the returned quantity to get the old quantity before sale
                        $returnedQuantity = $posProduct->quantity;
                        $oldQuantity = ($product->quantity ?? 0) - $returnedQuantity;
                        // Use product's avg_cost or subproduct purchase_price as fallback
                        $oldAvgCost = ($product->avg_cost > 0) ? $product->avg_cost : ($subProduct ? ($subProduct->purchase_price ?? 0) : 0);
                        
                        // Calculate old total cost
                        $oldTotalCost = $oldQuantity * $oldAvgCost;
                        
                        // Returned item (current POS product being returned)
                        $returnedQty = $posProduct->quantity;
                        // Use product's avg_cost or subproduct purchase_price as the price per unit for returned item
                        $returnedPricePerUnit = ($product->avg_cost > 0) ? $product->avg_cost : ($subProduct ? ($subProduct->purchase_price ?? 0) : 0);
                        
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
                    $stockMovement->sub_product_id = $posProduct->sub_product_id;
                    $stockMovement->invoice_id = null;
                    $stockMovement->bill_id = null;
                    $stockMovement->pos_id = $pos->id;
                    $stockMovement->qty_in = $posProduct->quantity; // Return sold qty
                    $stockMovement->qty_out = 0; // No stock out for return
                    $stockMovement->avg_cost = $avgCost;
                    $stockMovement->cost_price = $subProduct ? ($subProduct->purchase_price ?? 0) : 0;
                    $stockMovement->activity = 'Return from POS';
                    $stockMovement->use_id = $pos->customer_id; // customer_id for SALES
                    $stockMovement->item = $posProduct->sub_product_id; // sub_product_id
                    $stockMovement->created_by = $creatorId;
                    $stockMovement->save();
                    
                    // Update product average cost
                    $product->avg_cost = $avgCost;
                    $product->save();
                }

                // Return quantity to parent product
                $product->quantity += $posProduct->quantity;
                $product->save();
            }

            // Delete related records
            PosProduct::where('pos_id', $pos->id)->delete();
            PosPayment::where('pos_id', $pos->id)->delete();
            
            // Keep original General Ledger entries for audit; only add reversal entries above.

            // Log POS deletion before deleting
            PosLog::logAction('delete_order', [
                'type' => 'pos',
                'reference_id' => $pos->id,
                'pos_id' => $pos->pos_id,
                'warehouse_id' => $pos->warehouse_id,
                'customer_id' => $pos->customer_id,
                'old_value' => [
                    'pos_id' => $pos->pos_id,
                    'pos_date' => $pos->pos_date,
                    'status' => $pos->status,
                    'discount' => $pos->discount,
                    'is_sold' => $isSold,
                ],
                'description' => "POS order deleted with POS ID: {$pos->pos_id}",
            ]);

            // Delete the POS (soft delete)
            $pos->delete();

            DB::commit();
            return redirect()->route('pos.report')->with('success', __('POS successfully deleted and all transactions reversed.'));
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('POS Deletion Error: ' . $e->getMessage(), [
                'pos_id' => $id,
                'user_id' => \Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', __('Error deleting POS: ') . $e->getMessage());
        }
    }

    /**
     * Display POS logs (only for company users)
     */
    public function logs(Request $request)
    {
        // Only allow company users
        if (\Auth::user()->type != 'company') {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $creatorId = \Auth::user()->creatorId();
            
            // Build query with optional relationships
            $query = PosLog::with([
                    'user:id,name',
                    'pos:id,pos_id,customer_id,warehouse_id',
                    'warehouse:id,name',
                    'customer:id,name',
                    'product:id,name'
                ])
                ->where('created_by', $creatorId)
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->filled('action_type')) {
                $query->where('action_type', $request->action_type);
            }

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            if ($request->filled('reference_id')) {
                $query->where('reference_id', $request->reference_id);
            }

            if ($request->filled('warehouse_id')) {
                $query->where('warehouse_id', $request->warehouse_id);
            }

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $logs = $query->paginate(50)->withQueryString();

            // Get filter options
            $warehouses = warehouse::where('created_by', $creatorId)->get()->pluck('name', 'id');
            $users = User::where('created_by', $creatorId)->get()->pluck('name', 'id');
            
            // Get unique action types for filter dropdown
            $actionTypes = PosLog::where('created_by', $creatorId)
                ->distinct()
                ->pluck('action_type')
                ->sort()
                ->values();

            // Get unique types for filter dropdown
            $types = PosLog::where('created_by', $creatorId)
                ->distinct()
                ->pluck('type')
                ->filter()
                ->sort()
                ->values();

            return view('pos.logs', compact('logs', 'warehouses', 'users', 'actionTypes', 'types'));
        } catch (\Exception $e) {
            \Log::error('POS Logs Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => \Auth::id(),
            ]);
            return redirect()->back()->with('error', __('Error loading logs: ') . $e->getMessage());
        }
    }

    /**
     * Get POS Report API
     * Returns POS report data filtered by company, warehouse, and date range
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPosReportApi(Request $request)
    {
        try {
            // Get authenticated user
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please provide a valid token.'
                ], 401);
            }

            // Get company ID from authenticated user
            $companyId = $user->creatorId();
            
            // Get user's assigned warehouses first
            $assignedWarehouseIds = $user->warehouses()->pluck('warehouses.id')->toArray();
            $isCompanyAdmin = ($user->type == 'company' || $user->type == 'super admin');
            
            // Get warehouse ID from request (optional filter) - cast to integer
            $requestedWarehouseId = $request->has('warehouse_id') ? (int)$request->input('warehouse_id') : null;
            
            // Get date range from request
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            
            // Validate date range if provided
            if ($fromDate && $toDate && $fromDate > $toDate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date range. from_date must be less than or equal to to_date.'
                ], 400);
            }

            // Determine which warehouse(s) to use
            $warehouseId = null;
            $effectiveWarehouseIds = [];
            
            // If user is NOT company/admin, restrict to assigned warehouses only
            if (!$isCompanyAdmin) {
                if (empty($assignedWarehouseIds)) {
                    // User has no assigned warehouses - return empty result
                    return response()->json([
                        'success' => true,
                        'data' => [],
                        'message' => 'No POS records found. User has no assigned warehouses.'
                    ]);
                }
                
                // Ignore warehouse_id parameter - user can only access assigned warehouses
                if ($requestedWarehouseId) {
                    // Check if requested warehouse is in assigned warehouses
                    if (!in_array($requestedWarehouseId, $assignedWarehouseIds)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Access denied. You can only access your assigned warehouses.',
                            'assigned_warehouses' => $assignedWarehouseIds
                        ], 403);
                    }
                    // If it's in assigned warehouses, use it
                    $warehouseId = $requestedWarehouseId;
                } else {
                    // No warehouse specified - use all assigned warehouses
                    $effectiveWarehouseIds = $assignedWarehouseIds;
                }
            } else {
                // Company/admin users can specify warehouse_id or access all warehouses
                if ($requestedWarehouseId) {
                    // Verify warehouse belongs to company
                    $warehouse = warehouse::where('id', $requestedWarehouseId)
                        ->where('created_by', $companyId)
                        ->first();
                    
                    if (!$warehouse) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Warehouse not found or does not belong to your company.'
                        ], 404);
                    }
                    $warehouseId = $requestedWarehouseId;
                }
                // If no warehouse specified for company/admin, $effectiveWarehouseIds stays empty (shows all)
            }

            // Build base query - same pattern as report() method
            $query = Pos::where('created_by', '=', $companyId)
                ->with([
                    'customer:id,name,customer_id',
                    'warehouse:id,name',
                    'payments.paymentMethod:id,name',
                    'items:id,pos_id,product_id,quantity,price,discount'
                ]);

            // Apply warehouse filter
            if ($warehouseId) {
                // Filter by specific warehouse
                $query->where('warehouse_id', '=', (int)$warehouseId);
            } elseif (!empty($effectiveWarehouseIds)) {
                // Filter by multiple assigned warehouses
                $query->whereIn('warehouse_id', $effectiveWarehouseIds);
            }
            // If neither is set and user is company/admin, no warehouse filter (shows all)

            // Filter by date range - use pos_date field (same as report() method)
            if ($fromDate && $toDate) {
                $query->whereBetween('pos_date', [$fromDate, $toDate]);
            } elseif ($fromDate) {
                $query->where('pos_date', '>=', $fromDate);
            } elseif ($toDate) {
                $query->where('pos_date', '<=', $toDate);
            }

            // Get total count before ordering (for debugging)
            $totalCountBeforeFilter = $query->count();
            
            // Order by date descending - same as report() method
            $posRecords = $query->orderBy('pos_date', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            // Debug: Get count of all POS records for this company (without date filter) for troubleshooting
            $debugInfo = [];
            if ($request->input('debug', false) || $request->input('debug') == '1' || $request->input('debug') == 'true') {
                $allPosCount = Pos::where('created_by', $companyId)->count();
                $allPosWithWarehouseCount = $warehouseId 
                    ? Pos::where('created_by', $companyId)->where('warehouse_id', $warehouseId)->count()
                    : null;
                
                $allPosWithDateCount = ($fromDate || $toDate)
                    ? Pos::where('created_by', $companyId)
                        ->when($warehouseId, function($q) use ($warehouseId) {
                            return $q->where('warehouse_id', $warehouseId);
                        })
                        ->when($fromDate && $toDate, function($q) use ($fromDate, $toDate) {
                            return $q->whereBetween('pos_date', [$fromDate, $toDate]);
                        })
                        ->when($fromDate && !$toDate, function($q) use ($fromDate) {
                            return $q->where('pos_date', '>=', $fromDate);
                        })
                        ->when($toDate && !$fromDate, function($q) use ($toDate) {
                            return $q->where('pos_date', '<=', $toDate);
                        })
                        ->count()
                    : null;
                
                $debugInfo = [
                    'total_pos_records_for_company' => $allPosCount,
                    'total_pos_records_for_warehouse' => $allPosWithWarehouseCount,
                    'records_with_date_filter' => $allPosWithDateCount,
                    'records_after_all_filters' => $totalCountBeforeFilter,
                    'query_applied' => [
                        'company_id' => $companyId,
                        'warehouse_id' => $warehouseId,
                        'from_date' => $fromDate,
                        'to_date' => $toDate
                    ],
                    'sample_pos_dates' => Pos::where('created_by', $companyId)
                        ->when($warehouseId, function($q) use ($warehouseId) {
                            return $q->where('warehouse_id', $warehouseId);
                        })
                        ->select('id', 'pos_id', 'pos_date', 'warehouse_id', 'created_at')
                        ->orderBy('id', 'desc')
                        ->limit(5)
                        ->get()
                        ->map(function($pos) {
                            return [
                                'id' => $pos->id,
                                'pos_id' => $pos->pos_id,
                                'pos_date' => $pos->pos_date,
                                'warehouse_id' => $pos->warehouse_id,
                                'created_at' => $pos->created_at ? $pos->created_at->format('Y-m-d') : null
                            ];
                        })
                ];
            }

            // Format response data
            $formattedData = $posRecords->map(function ($pos) {
                // Calculate totals using model methods
                $subTotal = $pos->getSubTotal();
                $discount = $pos->getTotalDiscountAmount();
                $tax = $pos->getTotalTax();
                $total = $pos->getTotal();
                
                // Calculate total quantity
                $totalQuantity = $pos->items->sum('quantity');
                
                // Format payments (group by card/cash)
                $payments = [];
                $cashTotal = 0;
                $cardTotal = 0;
                
                foreach ($pos->payments as $payment) {
                    $paymentMethod = $payment->paymentMethod;
                    $isCash = false;
                    
                    // Determine if payment is cash or card
                    if (!$paymentMethod || !$payment->payment_method_id || $payment->payment_method_id == 0) {
                        // No payment method = Cash
                        $isCash = true;
                    } else {
                        // Check if payment method name contains "cash" (case-insensitive)
                        $isCash = stripos($paymentMethod->name, 'cash') !== false;
                    }
                    
                    if ($isCash) {
                        $cashTotal += (float)$payment->amount;
                    } else {
                        $cardTotal += (float)$payment->amount;
                        
                        // Add individual card payment
                        $payments[] = [
                            'type' => 'card',
                            'method_name' => $paymentMethod ? $paymentMethod->name : 'Unknown',
                            'amount' => (float)$payment->amount
                        ];
                    }
                }
                
                // Add cash payment if exists
                if ($cashTotal > 0) {
                    $payments[] = [
                        'type' => 'cash',
                        'method_name' => 'Cash',
                        'amount' => $cashTotal
                    ];
                }
                
                return [
                    'pos_no' => $pos->pos_id,
                    'customer' => $pos->customer ? [
                        'name' => $pos->customer->name,
                        'customer_id' => $pos->customer->customer_id
                    ] : null,
                    'warehouse' => $pos->warehouse ? [
                        'name' => $pos->warehouse->name
                    ] : null,
                    'pos_date' => $pos->pos_date,
                    'quantity' => $totalQuantity,
                    'sub_total' => round($subTotal, 2),
                    'discount' => round($discount, 2),
                    'tax' => round($tax, 2),
                    'total' => round($total, 2),
                    'payments' => $payments,
                    'payment_summary' => [
                        'cash' => round($cashTotal, 2),
                        'card' => round($cardTotal, 2),
                        'total' => round($cashTotal + $cardTotal, 2)
                    ]
                ];
            });

            // Determine effective warehouse filter for response (warehouse name instead of ID)
            $effectiveWarehouseName = null;
            if ($warehouseId) {
                $warehouse = warehouse::find($warehouseId);
                $effectiveWarehouseName = $warehouse ? $warehouse->name : null;
            } elseif (!empty($effectiveWarehouseIds)) {
                $warehouses = warehouse::whereIn('id', $effectiveWarehouseIds)->pluck('name')->toArray();
                $effectiveWarehouseName = !empty($warehouses) ? $warehouses : null;
            }
            
            $response = [
                'success' => true,
                'data' => $formattedData,
                'count' => $formattedData->count(),
                'filters' => [
                    'warehouse' => $effectiveWarehouseName,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                    'user_restricted' => !$isCompanyAdmin && !empty($assignedWarehouseIds)
                ]
            ];
            
            // Add assigned warehouses info for non-admin users (names only, no IDs)
            if (!$isCompanyAdmin && !empty($assignedWarehouseIds)) {
                $assignedWarehouseNames = warehouse::whereIn('id', $assignedWarehouseIds)
                    ->pluck('name')
                    ->toArray();
                $response['assigned_warehouses'] = $assignedWarehouseNames;
            }

            // Add message if no records found
            if ($formattedData->count() == 0) {
                $message = 'No POS records found';
                if ($warehouseId && ($fromDate || $toDate)) {
                    $message .= ' for the specified warehouse and date range.';
                } elseif ($warehouseId) {
                    $message .= ' for the specified warehouse.';
                } elseif ($fromDate || $toDate) {
                    $message .= ' for the specified date range.';
                } else {
                    $message .= '.';
                }
                $response['message'] = $message;
            }

            // Add debug information if requested
            if ($request->input('debug', false) || $request->input('debug') == '1' || $request->input('debug') == 'true') {
                $response['debug'] = $debugInfo;
            }

            return response()->json($response);

        } catch (\Exception $e) {
            \Log::error('POS Report API Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching POS report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display mobile-friendly barcode scan page
     */
    public function mobileBarcodeScan()
    {
        // Allow access if user has POS permissions OR product management permissions (for stock report users)
        $hasPosPermission = \Auth::user()->can('manage pos') || \Auth::user()->can('add pos') || \Auth::user()->can('create pos');
        $hasProductPermission = \Auth::user()->can('manage product & service');
        
        if (!$hasPosPermission && !$hasProductPermission) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        return view('pos.mobile-barcode-scan');
    }

    /**
     * Search product by barcode for mobile page
     */
    public function mobileBarcodeScanSearch(Request $request)
    {
        // Allow access if user has POS permissions OR product management permissions (for stock report users)
        $hasPosPermission = \Auth::user()->can('manage pos') || \Auth::user()->can('add pos') || \Auth::user()->can('create pos');
        $hasProductPermission = \Auth::user()->can('manage product & service');
        
        if (!$hasPosPermission && !$hasProductPermission) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        try {
            $barcode = trim($request->input('barcode', ''));
            
            if (empty($barcode)) {
                return response()->json(['error' => __('Barcode is required')], 400);
            }
            
            $creatorId = \Auth::user()->creatorId();
            
            // Escape special characters for LIKE queries
            $escapedBarcode = str_replace(['%', '_', '\\'], ['\%', '\_', '\\\\'], $barcode);
            
            // Build query to find sub-product by barcode or SKU
            $query = SubProduct::with([
                'productService:id,name,sku,category_id,brand_id,sub_brand_id',
                'productService.category:id,name',
                'productService.brand:id,name',
                'productService.subBrand:id,name',
                'customFieldValues:id,record_id,field_id,value',
                'customFieldValues.customField:id,name,type',
                'warehouse:id,name'
            ])
            ->where('created_by', $creatorId)
            ->whereNotNull('chassis_no')
            ->where(function($q) use ($barcode, $escapedBarcode) {
                // Primary search: by product_no (barcode) - exact match first
                $q->where('chassis_no', '=', $barcode)
                  ->orWhere('chassis_no', 'LIKE', '%' . $escapedBarcode . '%');
            });
            
            // Get ALL matching sub-products for this barcode/SKU (grouped by product_no and warehouse)
            $subProducts = $query->where('flag', '!=', 2) // Exclude cancelled
                ->orderBy('warehouse_id', 'asc')
                ->orderBy('id', 'desc')
                ->get();
            
            if ($subProducts->isEmpty()) {
                return response()->json([
                    'error' => __('Product not found for barcode: :barcode', ['barcode' => $barcode])
                ], 404);
            }
            
            // Group sub-products by product_no and warehouse, and aggregate data
            $groupedSubProducts = [];
            foreach ($subProducts as $subProduct) {
                $productService = $subProduct->productService;
                if (!$productService) {
                    continue;
                }
                
                $key = $subProduct->chassis_no . '_' . ($subProduct->warehouse_id ?? 'no_warehouse');
                
                if (!isset($groupedSubProducts[$key])) {
                    // Calculate total quantity for this product_no in this warehouse
                    $totalQuantity = SubProduct::where('chassis_no', $subProduct->chassis_no)
                        ->where('warehouse_id', $subProduct->warehouse_id)
                        ->where('created_by', $creatorId)
                        ->where('flag', '!=', 2)
                        ->sum('quantity');
                    
                    // Build custom fields data for this sub-product
                    $customFields = [];
                    foreach ($subProduct->customFieldValues as $customFieldValue) {
                        if ($customFieldValue->customField) {
                            $customFields[] = [
                                'id' => $customFieldValue->customField->id,
                                'name' => $customFieldValue->customField->name,
                                'type' => $customFieldValue->customField->type,
                                'value' => $customFieldValue->value
                            ];
                        }
                    }
                    
                    // Get the first sub-product's note (or combine notes if multiple)
                    $note = $subProduct->note;
                    
                    $groupedSubProducts[$key] = [
                        'id' => $subProduct->id, // Use first sub-product ID
                        'product_no' => $subProduct->chassis_no,
                        'quantity' => $totalQuantity,
                        'sale_price' => $subProduct->sale_price ?? 0,
                        'note' => $note,
                        'warehouse' => [
                            'id' => $subProduct->warehouse_id,
                            'name' => $subProduct->warehouse->name ?? __('No Warehouse'),
                        ],
                        'custom_fields' => $customFields
                    ];
                }
            }
            
            // Get product info from first sub-product
            $firstSubProduct = $subProducts->first();
            $productService = $firstSubProduct->productService;
            
            return response()->json([
                'success' => true,
                'product' => [
                    'id' => $productService->id,
                    'name' => $productService->name,
                    'sku' => $productService->sku,
                ],
                'category' => [
                    'id' => $productService->category_id,
                    'name' => $productService->category->name ?? '',
                ],
                'brand' => [
                    'id' => $productService->brand_id,
                    'name' => $productService->brand->name ?? '',
                ],
                'sub_products' => array_values($groupedSubProducts) // Return as indexed array
            ]);
        } catch (\Exception $e) {
            \Log::error('Mobile barcode scan search error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'error' => __('Error searching product: ') . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save note for a sub-product
     */
    public function saveSubProductNote(Request $request)
    {
        // Allow access if user has POS permissions OR product management permissions
        $hasPosPermission = \Auth::user()->can('manage pos') || \Auth::user()->can('add pos') || \Auth::user()->can('create pos');
        $hasProductPermission = \Auth::user()->can('manage product & service');
        
        if (!$hasPosPermission && !$hasProductPermission) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        try {
            $subProductId = $request->input('sub_product_id');
            $note = $request->input('note', '');
            
            if (!$subProductId) {
                return response()->json(['error' => __('Sub-product ID is required')], 400);
            }
            
            $creatorId = \Auth::user()->creatorId();
            
            $subProduct = SubProduct::where('id', $subProductId)
                ->where('created_by', $creatorId)
                ->first();
            
            if (!$subProduct) {
                return response()->json(['error' => __('Sub-product not found')], 404);
            }
            
            // Update note for all sub-products with the same product_no and warehouse_id
            SubProduct::where('chassis_no', $subProduct->chassis_no)
                ->where('warehouse_id', $subProduct->warehouse_id)
                ->where('created_by', $creatorId)
                ->where('flag', '!=', 2)
                ->update(['note' => $note]);
            
            return response()->json([
                'success' => true,
                'message' => __('Note saved successfully')
            ]);
        } catch (\Exception $e) {
            \Log::error('Save sub-product note error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'error' => __('Error saving note: ') . $e->getMessage()
            ], 500);
        }
    }


}
