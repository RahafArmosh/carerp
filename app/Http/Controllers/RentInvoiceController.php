<?php

namespace App\Http\Controllers;

use App\Exports\InvoiceExport;
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
use App\Models\ProductServiceCategory;
use App\Models\SubProduct;
use App\Models\StockReport;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Tax;
use App\Models\Utility;
use App\Models\GeneralLedger;
use App\Models\TransactionLines;
use App\Models\Currency;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\CustomerPayment;
use App\Models\AccountingDocument;
use App\Models\Color;
class RentInvoiceController extends Controller
{
    public function __construct()
    {
    }

//     public function index(Request $request)
//     {
//         if (\Auth::user()->can('manage invoice')) {
//             $customer = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
//             $customer->prepend('Select Customer', '');
//             $status = Invoice::$statues;
//             $query = Invoice::where('created_by', '=', \Auth::user()->creatorId())->where('type','rent');

//             if (!empty($request->customer)) {
//                 $query->where('customer_id', '=', $request->customer);
//             }
//             if (count(explode('to', $request->issue_date)) > 1) {
//                 $date_range = explode(' to ', $request->issue_date);
//                 $query->whereBetween('issue_date', $date_range);
//             } elseif (!empty($request->issue_date)) {
//                 $date_range = [$request->issue_date, $request->issue_date];
//                 $query->whereBetween('issue_date', $date_range);
//             }
//             if (!empty($request->status)) {
//                 $query->where('status', '=', $request->status);
//             }
//             $invoices = $query->get();

//             return view('rentinvoice.index', compact('invoices', 'customer', 'status'));
//         } else {
//             return redirect()->back()->with('error', __('Permission Denied.'));
//         }
//     }

//     public function create($customerId)
//     {
//         if (\Auth::user()->can('create invoice')) {
//             $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'invoice')->get();
//             $lastInvoiceId = Invoice::withTrashed()->latest()->first();
//             if($lastInvoiceId != null){
//                 $invoice_number = \Auth::user()->invoiceNumberFormat($lastInvoiceId->id);
//             }
//             else{
//                 $invoice_number = \Auth::user()->invoiceNumberFormat($this->invoiceNumber());
//             }
//             $customers = Customer::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
//             $customers->prepend('Select Customer', '');
//             $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->where("rentable",1)->get()->pluck('name', 'id');
//             $category->prepend('Select Category', '');
//             $product_services = ProductService::where('created_by', \Auth::user()->creatorId())->whereHas('subProducts', function ($query) {
//                 $query->where('flag', '!=', 2)->where('booked','=' , 0);
//             })
//             ->where(function ($query) {
//                 $query->whereHas('category', function ($subQuery) {
//                     $subQuery->where('rentable', '=', 1);
//                 })->orWhere('type', 'service'); // Added orWhere clause for ProductService type
//             })
//             ->with(['brand', 'subBrand', 'category'])
//             ->get()
//             ->map(function ($productService) {
//                 $category = $productService->category->name ?? '';
//                 $brand = $productService->brand->name ?? '';
//                 $subBrand = $productService->subBrand->name ?? '';
//                 $productName = $productService->name;

//                 return [
//                     'id' => $productService->id,
//                     'name' => $category . '/' . $brand . '/' . $subBrand . '/' . $productName,
//                 ];
//             })
//             ->pluck('name', 'id');
//             $product_services->prepend('--', '');

//             $tax          = Tax::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
//             $fullTax          = Tax::where('created_by', '=', \Auth::user()->creatorId())->get();
//             $currency     = Currency::get()->pluck('name', 'id');
//             $currency->prepend('AED', '');
//             $customFieldsProducts = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'product')->get();
//             $colors     = Color::get()->pluck('name', 'id');
//             $colors->prepend('Select Color', '');
//             $allColor = Color::get();

//             $countries     = Country::get()->pluck('name', 'id');
//             $countries->prepend('Select Country', '');
//             return view('rentinvoice.create', compact('customers', 'invoice_number', 'product_services', 'category', 'customFields', 'customerId','tax','fullTax','currency','customFieldsProducts','colors','allColor','countries'));
//         } else {
//             return response()->json(['error' => __('Permission denied.')], 401);
//         }
//     }

//     public function customer(Request $request)
//     {
//         $customer = Customer::where('id', '=', $request->id)->first();
//         return view('invoice.customer_detail', compact('customer'));
//     }

//     public function product(Request $request)
//     {
//         $data['product'] = $product = ProductService::find($request->product_id);
//         $data['unit'] = (!empty($product->unit)) ? $product->unit->name : '';
//         $data['taxRate'] = $taxRate = !empty($product->tax_id) ? $product->taxRate($product->tax_id) : 0;
//         $data['taxes'] = !empty($product->tax_id) ? $product->tax($product->tax_id) : 0;
//         $salePrice = $product->sale_price;
//         $quantity = 1;
//         $taxPrice = ($taxRate / 100) * ($salePrice * $quantity);
//         $data['totalAmount'] = ($salePrice * $quantity);

//         return json_encode($data);
//     }

//     public function store(Request $request)
//     {

//         if (\Auth::user()->can('create invoice')) {
//             $validator = \Validator::make(
//                 $request->all(), [
//                     'customer_id' => 'required',
//                     'issue_date' => 'required',
//                     'due_date' => 'required',
//                     'category_id' => 'required',
//                     'items' => 'required',
//                     'tax_id' => 'required',
//                 ]
//             );
//             if ($validator->fails()) {
//                 $messages = $validator->getMessageBag();
//                 return redirect()->back()->with('error', $messages->first());
//             }
//             $status = Invoice::$statues;
//             $invoice = new Invoice();
//             $invoice->invoice_id = $this->invoiceNumber();
//             $invoice->customer_id = $request->customer_id;
//             $invoice->status = 0;
//             $invoice->issue_date = $request->issue_date;
//             $invoice->due_date = $request->due_date;
//             $invoice->category_id = $request->category_id;
//             $invoice->ref_number = $request->ref_number;
//             $invoice->type = "rent";
// //            $invoice->discount_apply = isset($request->discount_apply) ? 1 : 0;
//             $invoice->created_by = \Auth::user()->creatorId();
//             $invoice->tax_id         = !empty($request->tax_id) ? implode(',', $request->tax_id) : '';
//             $invoice->currency_id         = !empty($request->currency_id) ?  $request->currency_id : null;
//             $invoice->exchange_rate         = !empty($request->exchange_rate) ?  $request->exchange_rate : 0;
//             $invoice->save();
//             CustomField::saveData($invoice, $request->customField);
//             if ($request->hasFile('documents')) {
//                 $documents = $request->file('documents');
//                 foreach ($documents as $document) {
//                     $filenameWithExt = $document->getClientOriginalName();
//                     $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
//                     $extension = $document->getClientOriginalExtension();
//                     $fileNameToStore = $filename . '_' . time() . '.' . $extension;
//                     // $path = $document->storeAs('uploads/document', $fileNameToStore, 'public');
//                     $document->move(public_path('documents'), $fileNameToStore);
//                     // Save the file path to the database
//                     $accountDocument = new AccountingDocument();
//                     $accountDocument->document_name = $filenameWithExt;
//                     $accountDocument->document_path = 'documents/' . $fileNameToStore; ;
//                     $accountDocument->invoice_id = $invoice->id;
//                     $accountDocument->save();
//                 }

//             }
//             $products = $request->items;

//             for ($i = 0; $i < count($products); $i++) {
//                 $product = ProductService::find($products[$i]['item']);
//                 CustomField::saveData($product, $request->customFieldP);
//                 // Add the retrieved sub-products to the array
//                 $selectType = $products[$i]['selectType'];
//                 if($selectType == "manual"){
//                     $selectedSubProducts = explode(',', $products[$i]['selected']);
//                     foreach($selectedSubProducts as $item){
//                         $subProduct  = SubProduct::find($item);
//                         $subProduct->update([
//                             'invoice_id' => $invoice->id,
//                             'booked' => 1,
//                         ]);
//                         $invoiceProduct = new InvoiceProduct();
//                         $invoiceProduct->invoice_id = $invoice->id;
//                         $invoiceProduct->product_id = $products[$i]['item'];
//                         $invoiceProduct->sub_product_id = $subProduct->id;
//                         $invoiceProduct->quantity = count($selectedSubProducts);
//                         $invoiceProduct->tax = $products[$i]['tax'];
//         //                $invoiceProduct->discount    = isset($products[$i]['discount']) ? $products[$i]['discount'] : 0;
//                         $invoiceProduct->discount = $products[$i]['discount'];
//                         if(!empty($request->currency_id)){
//                             $curr = Currency::find($request->currency_id);
//                             $invoiceProduct->price = !empty($request->exchange_rate) ? $products[$i]['price'] * $request->exchange_rate : $products[$i]['price'] * $curr->exchange_rate;
//                         }
//                         else{
//                             $invoiceProduct->price = $products[$i]['price'];
//                         }
//                         $invoiceProduct->description = $products[$i]['description'];
//                         $invoiceProduct->save();
//                     }
//                 }
//                 else{
//                 $product = ProductService::find($products[$i]['item']);
//                 $qty = $products[$i]['quantity'];
//                 $subProductsArray = SubProduct::where('product_id', $products[$i]['item'])->where('flag','!=',2)->where('booked','=',0)->limit($qty)->get();
//                 if(count($subProductsArray) != $qty){
//                     $available_qty = SubProduct::where('product_id', $products[$i]['item'])->where('flag','!=',2)->where('booked','=',0)->get();
//                     $messages = $validator->getMessageBag();
//                     return redirect()->back()->with('error', 'the requested quantity for ' . $product->name .' currently unavailable in stock , The available quantity is '. count($available_qty));
//                 }
//                 foreach ($subProductsArray as $subProduct) {
//                     $subProduct->update([
//                         'invoice_id' => $invoice->id,
//                         'booked' => 1,
//                     ]);
//                 }
//                     // Add the retrieved sub-products to the array
//                     foreach($subProductsArray as $item){
//                         // $subProducts [] = $item;
//                         $invoiceProduct = new InvoiceProduct();
//                         $invoiceProduct->invoice_id = $invoice->id;
//                         $invoiceProduct->product_id = $products[$i]['item'];
//                         $invoiceProduct->sub_product_id = $item->id;
//                         $invoiceProduct->quantity = $products[$i]['quantity'];
//                         $invoiceProduct->tax = $products[$i]['tax'];
//         //                $invoiceProduct->discount    = isset($products[$i]['discount']) ? $products[$i]['discount'] : 0;
//                         $invoiceProduct->discount = $products[$i]['discount'];
//                         if(!empty($request->currency_id)){
//                             $curr = Currency::find($request->currency_id);
//                             $invoiceProduct->price = !empty($request->exchange_rate) ? $products[$i]['price'] * $request->exchange_rate : $products[$i]['price'] * $curr->exchange_rate;
//                         }
//                         else{
//                             $invoiceProduct->price = $products[$i]['price'];
//                         }
//                         $invoiceProduct->description = $products[$i]['description'];
//                         $invoiceProduct->save();
//                     }
//                 }



//                 //inventory management (Quantity)
//                 // Utility::total_quantity('minus', $invoiceProduct->quantity, $invoiceProduct->product_id);

//                 //For Notification
//                 $setting = Utility::settings(\Auth::user()->creatorId());
//                 $customer = Customer::find($request->customer_id);
//                 $invoiceNotificationArr = [
//                     'invoice_number' => \Auth::user()->invoiceNumberFormat($invoice->invoice_id),
//                     'user_name' => \Auth::user()->name,
//                     'invoice_issue_date' => $invoice->issue_date,
//                     'invoice_due_date' => $invoice->due_date,
//                     'customer_name' => $customer->name,
//                 ];
//                 //Slack Notification
//                 if (isset($setting['invoice_notification']) && $setting['invoice_notification'] == 1) {
//                     Utility::send_slack_msg('new_invoice', $invoiceNotificationArr);
//                 }
//                 //Telegram Notification
//                 if (isset($setting['telegram_invoice_notification']) && $settingب['telegram_invoice_notification'] == 1) {
//                     Utility::send_telegram_msg('new_invoice', $invoiceNotificationArr);
//                 }
//                 //Twilio Notification
//                 if (isset($setting['twilio_invoice_notification']) && $setting['twilio_invoice_notification'] == 1) {
//                     Utility::send_twilio_msg($customer->contact, 'new_invoice', $invoiceNotificationArr);
//                 }

//             }

//             //Product Stock Report
//             $type = 'invoice';
//             $type_id = $invoice->id;
//             StockReport::where('type', '=', 'invoice')->where('type_id', '=', $invoice->id)->delete();
//             $description = $invoiceProduct->quantity . '  ' . __(' quantity sold in invoice') . ' ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
//             Utility::addProductStock($invoiceProduct->product_id, $invoiceProduct->quantity, $type, $description, $type_id);

//             //webhook
//             $module = 'New Invoice';
//             $webhook = Utility::webhookSetting($module);
//             if ($webhook) {
//                 $parameter = json_encode($invoice);
//                 $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
//                 if ($status == true) {
//                     return redirect()->route('invoice.index', $invoice->id)->with('success', __('Invoice successfully created.'));
//                 } else {
//                     return redirect()->back()->with('error', __('Webhook call failed.'));
//                 }
//             }
//             return redirect()->route('invoice.addSubProducts', $invoice->id)->with('success', __('Invoice successfully created.'));
//             // return redirect()->route('invoice.index', $invoice->id)->with('success', __('Invoice successfully created.'));
//         } else {
//             return redirect()->back()->with('error', __('Permission denied.'));
//         }
//     }

//     public function edit($ids)
//     {
//         if (\Auth::user()->can('edit invoice')) {
//             $id = Crypt::decrypt($ids);
//             $invoice = Invoice::find($id);
//             $invoice_number = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
//             $customers = Customer::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
//             $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
//             $category->prepend('Select Category', '');
//             $product_services = ProductService::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
//             $invoice->customField = CustomField::getData($invoice, 'invoice');
//             $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'invoice')->get();
//             $totalTaxPrice = 0;
//             $taxes = \App\Models\Utility::tax($invoice->tax_id);
//             foreach ($taxes as $tax) {
//                 $taxPrice = Tax::where('id', $tax->id)->first()->rate;
//                 $totalTaxPrice += $taxPrice;
//             }

//             return view('invoice.edit', compact('customers', 'product_services', 'invoice', 'invoice_number', 'category', 'customFields','totalTaxPrice'));
//         } else {
//             return response()->json(['error' => __('Permission denied.')], 401);
//         }
//     }

//     public function update(Request $request, Invoice $invoice)
//     {

//         if (\Auth::user()->can('edit invoice')) {
//             if ($invoice->created_by == \Auth::user()->creatorId()) {
//                 $validator = \Validator::make(
//                     $request->all(),
//                     [
//                         'customer_id' => 'required',
//                         'issue_date' => 'required',
//                         'due_date' => 'required',
//                         'category_id' => 'required',
//                         'items' => 'required',
//                     ]
//                 );
//                 if ($validator->fails()) {
//                     $messages = $validator->getMessageBag();

//                     return redirect()->route('invoice.index')->with('error', $messages->first());
//                 }
//                 $invoice->customer_id = $request->customer_id;
//                 $invoice->issue_date = $request->issue_date;
//                 $invoice->due_date = $request->due_date;
//                 $invoice->ref_number = $request->ref_number;
// //                $invoice->discount_apply = isset($request->discount_apply) ? 1 : 0;
//                 $invoice->category_id = $request->category_id;
//                 $invoice->save();

//                 Utility::starting_number($invoice->invoice_id + 1, 'invoice');
//                 CustomField::saveData($invoice, $request->customField);

//                 // TransactionLines::where('reference_id',$invoice->id)->where('reference','Invoice')->delete();

//                 $invoice_products = InvoiceProduct::where('invoice_id', $invoice->id)->get();
//                 foreach ($invoice_products as $invoice_product) {
//                     $product = ProductService::find($invoice_product->product_id);
//                     $totalTaxPrice = 0;
//                     $taxes = \App\Models\Utility::tax($invoice->tax_id);
//                     foreach ($taxes as $tax) {
//                         $taxPrice = \App\Models\Utility::taxRate($tax->rate, $invoice_product->price, $invoice_product->quantity, $invoice_product->discount);
//                         $totalTaxPrice += $taxPrice;
//                     }
//                     $itemAmount = ($invoice_product->price * $invoice_product->quantity) - ($invoice_product->discount) + $totalTaxPrice;

//                     $data = [
//                         'account_id' => $invoice->category->saleAccount->id,
//                         'transaction_type' => 'Credit',
//                         'transaction_amount' => $itemAmount,
//                         'reference' => 'Invoice',
//                         'reference_id' => $invoice->id,
//                         'reference_sub_id' => $product->id,
//                         'date' => $invoice->issue_date,
//                     ];
//                     // Utility::addTransactionLines($data);
//                 }

//                 // return redirect()->route('invoice.index')->with('success', __('Invoice successfully updated.'));
//                 return redirect()->route('invoice.addSubProducts', $invoice->id)->with('success', __('Invoice successfully updated.'));
//             } else {
//                 return redirect()->back()->with('error', __('Permission denied.'));
//             }
//         } else {
//             return redirect()->back()->with('error', __('Permission denied.'));
//         }
//     }

//     public function invoiceNumber()
//     {
//         $latest = Invoice::where('created_by', '=', \Auth::user()->creatorId())->latest()->first();
//         if (!$latest) {
//             return 1;
//         }

//         return $latest->invoice_id + 1;
//     }

//     public function show($ids)
//     {

//         if (\Auth::user()->can('show invoice')) {
//             try {
//                 $id = Crypt::decrypt($ids);
//             } catch (\Throwable $th) {
//                 return redirect()->back()->with('error', __('Invoice Not Found.'));
//             }
//             $id = Crypt::decrypt($ids);
//             $invoice = Invoice::with(['creditNote','payments.bankAccount','items.product.unit'])->find($id);

//             if (!empty($invoice->created_by) == \Auth::user()->creatorId()) {
//                 $invoicePayment = InvoicePayment::where('invoice_id', $invoice->id)->first();

//                 $customer = $invoice->customer;
//                 $iteams = $invoice->items;
//                 $user = \Auth::user();

//                 // start for storage limit note
//                 $invoice_user = User::find($invoice->created_by);
//                 $user_plan = Plan::getPlan($invoice_user->plan);
//                 // end for storage limit note

//                 $invoice->customField = CustomField::getData($invoice, 'invoice');
//                 $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'invoice')->get();

//                 return view('invoice.view', compact('invoice', 'customer', 'iteams', 'invoicePayment', 'customFields', 'user', 'invoice_user', 'user_plan'));
//             } else {
//                 return redirect()->back()->with('error', __('Permission denied.'));
//             }
//         } else {
//             return redirect()->back()->with('error', __('Permission denied.'));
//         }
//     }

//     public function destroy(Invoice $invoice, Request $request)
//     {
//         if (\Auth::user()->can('delete invoice')) {
//             if ($invoice->created_by == \Auth::user()->creatorId()) {
//                 foreach ($invoice->payments as $invoices) {
//                     // $customer = Customer::where('id', $invoice->customer_id)->first();
//                     // $customer->total_paid = $customer->total_paid - $invoices->amount;
//                     // $customer->save();
//                     Utility::bankAccountBalance($invoices->account_id, $invoices->amount, 'debit');

//                     $invoicepayment = InvoicePayment::find($invoices->id);
//                     $invoices->delete();
//                     $invoicepayment->delete();

//                 }

//                 if ($invoice->customer_id != 0 && $invoice->status != 0) {
//                     Utility::updateUserBalance('customer', $invoice->customer_id, $invoice->getTotal(), 'credit');
//                 }


//                 TransactionLines::where('reference_id',$invoice->id)->where('reference','Invoice')->delete();
//                 TransactionLines::where('reference_id',$invoice->id)->Where('reference','Invoice Payment')->delete();
//                 if($invoice->status != 0){
//                     $isProudect= false;
//                     $itemAmount_purchase=0;
//                     $totalTaxPrice = 0;
//                     $totalAmountDebit=0;
//                     $invoice_products = InvoiceProduct::where('invoice_id', $invoice->id)->get();
//                     foreach ($invoice_products as $invoice_product) {
//                         $product = ProductService::find($invoice_product->product_id);
//                         $sub_product = SubProduct::find($invoice_product->sub_product_id);
//                         $sub_product->booked = 0;
//                         $sub_product->save();
//                         if($product->type == 'product'){
//                             $isProudect=true;
//                         }

//                         $taxes = \App\Models\Utility::tax($invoice->tax_id);
//                         foreach ($taxes as $tax) {
//                             $taxPrice = \App\Models\Utility::taxRate($tax->rate, $invoice_product->price, 1, $invoice_product->discount);
//                             $totalTaxPrice += $taxPrice;
//                         }
//                         $itemAmount = ($invoice_product->price * $invoice_product->quantity) - ($invoice_product->discount) + $totalTaxPrice;
//                         $itemAmount_purchase = $itemAmount_purchase + ($product->purchase_price * $invoice_product->quantity);
//                         $totalAmountDebit = $totalAmountDebit +  (($invoice_product->price * $invoice_product->quantity) - ($invoice_product->discount)) ;


//                     }

//                     // Add entries to General Ledger

//                     $latestVoucher = GeneralLedger::orderBy('vid', 'desc')->first();
//                 // Extract the vid value from the last record and increment it
//                 if ($latestVoucher) {
//                     $lastVid = $latestVoucher->vid;
//                     $newVid = $lastVid + 1;
//                 } else {
//                     // If no record exists, start with 1
//                     $newVid = 1;
//                 }
//                     $existingRecord = GeneralLedger::where('vid', $newVid)->exists();

//                     if ($existingRecord) {
//                         return redirect()->back()->with('error', __("something went wrong , please try again."));
//                     }

//                     // Retrieve the chart account ID for the category
//                     $categoryChartAccountId = \App\Models\ProductServiceCategory::where('id', $invoice->category_id)->first()->sale_account_id;
//                     $customer = Customer::where('id', $invoice->customer_id)->first();

//                     // Create a new entry for debit the category account
//                     $newEntryCategory = new GeneralLedger();
//                     $newEntryCategory->vid = $newVid;
//                     $newEntryCategory->account = $categoryChartAccountId;
//                     $newEntryCategory->type = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
//                     $newEntryCategory->debit = $totalAmountDebit; // Example value
//                     $newEntryCategory->credit = 0 ; // Example value
//                     $newEntryCategory->ref_id = $invoice->id;
//                     $newEntryCategory->user_id = 0;
//                     $newEntryCategory->created_by = \Auth::user()->creatorId();
//                     $newEntryCategory->save();

//                     // Retrieve the chart account ID for the tax
//                     $taxChartAccountId = \App\Models\Tax::where('id', $invoice->tax_id)->first()->chart_account_id;

//                     // Create a new entry debit for the tax account
//                     $newEntryTax = new GeneralLedger();
//                     $newEntryTax->vid = $newVid;
//                     $newEntryTax->account = $taxChartAccountId;
//                     $newEntryTax->type = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
//                     $newEntryTax->debit = $totalTaxPrice; // Example value
//                     $newEntryTax->credit = 0; // Example value
//                     $newEntryTax->ref_id = $invoice->id;
//                     $newEntryTax->user_id = 0;
//                     $newEntryTax->created_by = \Auth::user()->creatorId();
//                     $newEntryTax->save();


//                     // Retrieve the chart account ID for the customer

//                     $customerChartAccountId = $customer->chart_account_id;

//                     // Create a new entry cedit for the customer account
//                     $newEntryCustomer = new GeneralLedger();
//                     $newEntryCustomer->vid = $newVid;
//                     $newEntryCustomer->account = $customerChartAccountId;
//                     $newEntryCustomer->type = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
//                     $newEntryCustomer->debit = 0; // Example value
//                     $newEntryCustomer->credit = $totalAmountDebit + $totalTaxPrice; // Example value
//                     $newEntryCustomer->ref_id = $invoice->id;
//                     $newEntryCustomer->user_id = $customer->id;
//                     $newEntryCustomer->created_by = \Auth::user()->creatorId();
//                     $newEntryCustomer->balance = $customer->balance;
//                     $newEntryCustomer->save();


//                     ///////////////////////////////////////
//                     // Add records if product type is 'product'
//                     if ($isProudect ) {
//                         // Retrieve the chart account ID for the purchase
//                         $purchaseAccountId = \App\Models\ProductServiceCategory::where('id', $invoice->category_id)->first()->purchase_account_id;

//                         // Create a new entry for the purchase account (debit)
//                         $newEntryCredit = new GeneralLedger();
//                         $newEntryCredit->vid = $newVid;
//                         $newEntryCredit->account = $purchaseAccountId;
//                         $newEntryCredit->type = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
//                         $newEntryCredit->debit = $itemAmount_purchase; // Example value
//                         $newEntryCredit->credit = 0; // Example value
//                         $newEntryCredit->ref_id = $invoice->id;
//                         $newEntryCredit->user_id = 0;
//                         $newEntryCredit->created_by = \Auth::user()->creatorId();
//                         $newEntryCredit->save();

//                         // Retrieve the chart account ID for the expense
//                         $expenseAccountId = \App\Models\ProductServiceCategory::where('id', $invoice->category_id)->first()->expense_account_id;

//                         // Create a new entry for the expense account (credit)
//                         $newEntryDebit = new GeneralLedger();
//                         $newEntryDebit->vid = $newVid;
//                         $newEntryDebit->account = $expenseAccountId;
//                         $newEntryDebit->type = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
//                         $newEntryDebit->debit = 0; // Example value
//                         $newEntryDebit->credit = $itemAmount_purchase; // Example value
//                         $newEntryDebit->ref_id = $invoice->id;
//                         $newEntryDebit->user_id = 0;
//                         $newEntryDebit->created_by = \Auth::user()->creatorId();
//                         $newEntryDebit->save();
//                     }

// }
//                 CreditNote::where('invoice', '=', $invoice->id)->delete();
//                 $invoiceProduct = InvoiceProduct::where('invoice_id', '=', $invoice->id)->get();
//                 if(!empty($invoiceProduct->subProduct)){
//                     $subProducts = $invoiceProduct->subProduct;
//                     foreach ($subProducts as $subProduct) {
//                         // Update the invoice_id for each sub product in the array
//                         $subProduct->update(['invoice_id' => null]);
//                         $subProduct->update(['booked' => 0]);
//                         // Utility::total_quantity('plus', 1, $subProduct->product_id);
//                     }
//                 }
//                 InvoiceProduct::where('invoice_id', '=', $invoice->id)->delete();
//                 $invoice->delete();
//                 return redirect()->route('invoice.index')->with('success', __('Invoice successfully deleted.'));
//             } else {
//                 return redirect()->back()->with('error', __('Permission denied.'));
//             }
//         } else {
//             return redirect()->back()->with('error', __('Permission denied.'));
//         }
//     }

//     public function productDestroy(Request $request)
//     {

//         if (\Auth::user()->can('delete invoice product')) {
//             $invoiceProduct = InvoiceProduct::find($request->id);
//             $subProducts = $invoiceProduct->subProduct;
//             foreach ($subProducts as $subProduct) {
//                 // Update the invoice_id for each sub product in the array
//                 $subProduct->update(['invoice_id' => null]);
//                 $subProduct->update(['booked' => 0]);
//                 // Utility::total_quantity('plus', 1, $subProduct->product_id);
//             }
//             $invoice = Invoice::find($invoiceProduct->invoice_id);
//             $productService = ProductService::find($invoiceProduct->product_id);


//             TransactionLines::where('reference_sub_id',$productService->id)->where('reference','Invoice')->delete();

//             InvoiceProduct::where('id', '=', $request->id)->delete();

//             return redirect()->back()->with('success', __('Invoice product successfully deleted.'));
//         } else {
//             return redirect()->back()->with('error', __('Permission denied.'));
//         }
//     }

//     public function customerInvoice(Request $request)
//     {
//         if (\Auth::user()->can('manage customer invoice')) {

//             $status = Invoice::$statues;
//             $query = Invoice::where('customer_id', '=', \Auth::user()->id)->where('status', '!=', '0')->where('created_by', \Auth::user()->creatorId());

//             if (!empty($request->issue_date)) {
//                 $date_range = explode(' - ', $request->issue_date);
//                 $query->whereBetween('issue_date', $date_range);
//             }

//             if (!empty($request->status)) {
//                 $query->where('status', '=', $request->status);
//             }
//             $invoices = $query->get();

//             return view('invoice.index', compact('invoices', 'status'));
//         } else {
//             return redirect()->back()->with('error', __('Permission Denied.'));
//         }
//     }

//     public function customerInvoiceShow($id)
//     {

//         $invoice = Invoice::with('payments.bankAccount')->find($id);

//         $user = User::where('id', $invoice->created_by)->first();
//         if ($invoice->created_by == $user->creatorId()) {
//             $customer = $invoice->customer;
//             $iteams = $invoice->items;

//             if ($user->type == 'super admin') {
//                 return view('invoice.view', compact('invoice', 'customer', 'iteams', 'user'));
//             } elseif ($user->type == 'company') {
//                 return view('invoice.customer_invoice', compact('invoice', 'customer', 'iteams', 'user'));
//             }
//         } else {
//             return redirect()->back()->with('error', __('Permission denied.'));
//         }
//     }

//     public function sent($id)
//     {

//         if (\Auth::user()->can('send invoice')) {
//             // Send Email
//             $setings = Utility::settings();
//             if ($setings['customer_invoice_sent'] == 1) {
//                 $invoice = Invoice::where('id', $id)->first();
//                 $invoice->send_date = date('Y-m-d');
//                 $invoice->status = 1;
//                 $invoice->save();

//                 $customer = Customer::where('id', $invoice->customer_id)->first();
//                 $invoice->name = !empty($customer) ? $customer->name : '';
//                 $invoice->invoice = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);

//                 $invoiceId = Crypt::encrypt($invoice->id);
//                 $invoice->url = route('invoice.pdf', $invoiceId);

//                 Utility::updateUserBalance('customer', $customer->id, $invoice->getTotal(), 'debit');
//                 $isProudect= false;
//                 $itemAmount_purchase=0;
//                 $totalTaxPrice = 0;
//                 $totalAmountDebit=0;
//                 $invoice_products = InvoiceProduct::where('invoice_id', $invoice->id)->get();
//                 foreach ($invoice_products as $invoice_product) {
//                     $product = ProductService::find($invoice_product->product_id);
//                     $subproduct = SubProduct::find($invoice_product->sub_product_id);
//                     $subproduct->booked = 2;
//                     $subproduct->save();
//                     if($product->type == 'product'){
//                         $isProudect=true;
//                     }

//                     $taxes = \App\Models\Utility::tax($invoice->tax_id);
//                     foreach ($taxes as $tax) {
//                         $taxPrice = \App\Models\Utility::taxRate($tax->rate, $invoice_product->price, 1, $invoice_product->discount);
//                         $totalTaxPrice += $taxPrice;
//                     }
//                     $itemAmount = ($invoice_product->price * $invoice_product->quantity) - ($invoice_product->discount) + $totalTaxPrice;
//                     $itemAmount_purchase = $itemAmount_purchase + ($subproduct->purchase_price);
//                     $totalAmountDebit = $totalAmountDebit +  (($invoice_product->price ) - ($invoice_product->discount)) ;

//                 }
//                 // Retrieve the chart account ID for the category
//                 $categoryChartAccountId = \App\Models\ProductServiceCategory::where('id', $invoice->category_id)->first()->sale_account_id;
//                 $data1 = [
//                     'account_id' => $categoryChartAccountId ,
//                     'transaction_type' => 'Credit',
//                     'transaction_amount' => $totalAmountDebit,
//                     'reference' => 'Invoice',
//                     'reference_id' => $invoice->id,
//                     'reference_sub_id' => 0,
//                     'date' => $invoice->issue_date,
//                 ];
//                 Utility::addTransactionLines($data1);
//                 $data = [
//                     'account_id' => $customer->chart_account_id ,
//                     'transaction_type' => 'Debit',
//                     'transaction_amount' => $totalAmountDebit + $totalTaxPrice,
//                     'reference' => 'Invoice',
//                     'reference_id' => $invoice->id,
//                     'reference_sub_id' => 0,
//                     'date' => $invoice->issue_date,
//                 ];
//                 Utility::addTransactionLines($data);
//                 $data2 = [
//                     'account_id' => \App\Models\Tax::where('id', $invoice->tax_id)->first()->chart_account_id ,
//                     'transaction_type' => 'Credit',
//                     'transaction_amount' => $totalTaxPrice,
//                     'reference' => 'Invoice',
//                     'reference_id' => $invoice->id,
//                     'reference_sub_id' => 0,
//                     'date' => $invoice->issue_date,
//                 ];
//                 Utility::addTransactionLines($data2);
//                 $customerArr = [

//                     'customer_name' => $customer->name,
//                     'customer_email' => $customer->email,
//                     'invoice_name' => $customer->name,
//                     'invoice_number' => $invoice->invoice,
//                     'invoice_url' => $invoice->url,

//                 ];
//                 $resp = Utility::sendEmailTemplate('customer_invoice_sent', [$customer->id => $customer->email], $customerArr);

//                 // Add entries to General Ledger

//                 // Get the latest 'vid' entry, if any exist
//                 $latestVoucher = GeneralLedger::orderBy('vid', 'desc')->first();
//                 // Extract the vid value from the last record and increment it
//                 if ($latestVoucher) {
//                     $lastVid = $latestVoucher->vid;
//                     $newVid = $lastVid + 1;
//                 } else {
//                     // If no record exists, start with 1
//                     $newVid = 1;
//                 }
//                 $existingRecord = GeneralLedger::where('vid', $newVid)->exists();

//                 if ($existingRecord) {
//                     return redirect()->back()->with('error', __("something went wrong , please try again."));
//                 }

//                 // Retrieve the chart account ID for the category
//                 $categoryChartAccountId = \App\Models\ProductServiceCategory::where('id', $invoice->category_id)->first()->sale_account_id;


//                 // Create a new entry for credit the category account
//                 $newEntryCategory = new GeneralLedger();
//                 $newEntryCategory->vid = $newVid;
//                 $newEntryCategory->account = $categoryChartAccountId;
//                 $newEntryCategory->type = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
//                 $newEntryCategory->debit = 0; // Example value
//                 $newEntryCategory->credit = $totalAmountDebit ; // Example value
//                 $newEntryCategory->ref_id = $invoice->id;
//                 $newEntryCategory->user_id = 0;
//                 $newEntryCategory->created_by = \Auth::user()->creatorId();
//                 $newEntryCategory->save();

//                 // Retrieve the chart account ID for the tax
//                 $taxChartAccountId = \App\Models\Tax::where('id', $invoice->tax_id)->first()->chart_account_id;

//                 // Create a new entry cedit for the tax account
//                 $newEntryTax = new GeneralLedger();
//                 $newEntryTax->vid = $newVid;
//                 $newEntryTax->account = $taxChartAccountId;
//                 $newEntryTax->type = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
//                 $newEntryTax->debit = 0; // Example value
//                 $newEntryTax->credit = $totalTaxPrice; // Example value
//                 $newEntryTax->ref_id = $invoice->id;
//                 $newEntryTax->user_id = 0;
//                 $newEntryTax->created_by = \Auth::user()->creatorId();
//                 $newEntryTax->save();


//                 // Retrieve the chart account ID for the customer
//                 $customerChartAccountId = $customer->chart_account_id;

//                 // Create a new entry debit for the customer account
//                 $newEntryCustomer = new GeneralLedger();
//                 $newEntryCustomer->vid = $newVid;
//                 $newEntryCustomer->account = $customerChartAccountId;
//                 $newEntryCustomer->type = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
//                 $newEntryCustomer->debit = $totalAmountDebit + $totalTaxPrice; // Example value
//                 $newEntryCustomer->credit = 0; // Example value
//                 $newEntryCustomer->ref_id = $invoice->id;
//                 $newEntryCustomer->user_id = $customer->id;
//                 $newEntryCustomer->created_by = \Auth::user()->creatorId();
//                 $newEntryCustomer->balance = $customer->balance;
//                 $newEntryCustomer->save();


//                 ///////////////////////////////////////
//                 // Add records if product type is 'product'
//                 if ($isProudect ) {
//                     // Retrieve the chart account ID for the purchase
//                     $purchaseAccountId = \App\Models\ProductServiceCategory::where('id', $invoice->category_id)->first()->purchase_account_id;

//                     // Create a new entry for the purchase account (credit)
//                     $newEntryCredit = new GeneralLedger();
//                     $newEntryCredit->vid = $newVid;
//                     $newEntryCredit->account = $purchaseAccountId;
//                     $newEntryCredit->type = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
//                     $newEntryCredit->debit = 0; // Example value
//                     $newEntryCredit->credit = $itemAmount_purchase; // Example value
//                     $newEntryCredit->ref_id = $invoice->id;
//                     $newEntryCredit->user_id = 0;
//                     $newEntryCredit->created_by = \Auth::user()->creatorId();
//                     $newEntryCredit->save();

//                     // Retrieve the chart account ID for the expense
//                     $expenseAccountId = \App\Models\ProductServiceCategory::where('id', $invoice->category_id)->first()->expense_account_id;

//                     // Create a new entry for the expense account (debit)
//                     $newEntryDebit = new GeneralLedger();
//                     $newEntryDebit->vid = $newVid;
//                     $newEntryDebit->account = $expenseAccountId;
//                     $newEntryDebit->type = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
//                     $newEntryDebit->debit = $itemAmount_purchase; // Example value
//                     $newEntryDebit->credit = 0; // Example value
//                     $newEntryDebit->ref_id = $invoice->id;
//                     $newEntryDebit->user_id = 0;
//                     $newEntryDebit->created_by = \Auth::user()->creatorId();
//                     $newEntryDebit->save();
//                     $data1 = [
//                         'account_id' => $expenseAccountId ,
//                         'transaction_type' => 'Debit',
//                         'transaction_amount' => $itemAmount_purchase,
//                         'reference' => 'Invoice',
//                         'reference_id' => $invoice->id,
//                         'reference_sub_id' => 0,
//                         'date' => $invoice->issue_date,
//                     ];
//                     Utility::addTransactionLines($data1);
//                     $data = [
//                         'account_id' => $purchaseAccountId ,
//                         'transaction_type' => 'Credit',
//                         'transaction_amount' => $itemAmount_purchase,
//                         'reference' => 'Invoice',
//                         'reference_id' => $invoice->id,
//                         'reference_sub_id' => 0,
//                         'date' => $invoice->issue_date,
//                     ];
//                     Utility::addTransactionLines($data);
//                 }


//                 return redirect()->back()->with('success', __('Invoice successfully sent.') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));

//             }

//         } else {
//             return redirect()->back()->with('error', __('Permission denied.'));
//         }
//     }

//     public function resent($id)
//     {
//         if (\Auth::user()->can('send invoice')) {
//             $invoice = Invoice::where('id', $id)->first();

//             $customer = Customer::where('id', $invoice->customer_id)->first();
//             $invoice->name = !empty($customer) ? $customer->name : '';
//             $invoice->invoice = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);

//             $invoiceId = Crypt::encrypt($invoice->id);
//             $invoice->url = route('invoice.pdf', $invoiceId);
//             $customerArr = [

//                 'customer_name' => $customer->name,
//                 'customer_email' => $customer->email,
//                 'invoice_name' => $customer->name,
//                 'invoice_number' => $invoice->invoice,
//                 'invoice_url' => $invoice->url,

//             ];
//             $resp = Utility::sendEmailTemplate('customer_invoice_sent', [$customer->id => $customer->email], $customerArr);

//             return redirect()->back()->with('success', __('Invoice successfully sent.') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));

//         } else {
//             return redirect()->back()->with('error', __('Permission denied.'));
//         }
//     }

//     public function payment($invoice_id)
//     {
//         if (\Auth::user()->can('create payment invoice')) {
//             $invoice = Invoice::where('id', $invoice_id)->first();

//             $customers = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
//             $categories = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
//             $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

//             return view('invoice.payment', compact('customers', 'categories', 'accounts', 'invoice'));
//         } else {
//             return redirect()->back()->with('error', __('Permission denied.'));
//         }
//     }

//     public function createPayment(Request $request, $invoice_id)
//     {
//         $invoice = Invoice::find($invoice_id);
//         if ($invoice->getDue() < $request->amount) {
//             return redirect()->back()->with('error', __('Invoice payment amount should not greater than subtotal.'));
//         }

//         if (\Auth::user()->can('create payment invoice')) {
//             $validator = \Validator::make(
//                 $request->all(), [
//                     'date' => 'required',
//                     'amount' => 'required',
//                     'account_id' => 'required',
//                 ]
//             );
//             if ($validator->fails()) {
//                 $messages = $validator->getMessageBag();

//                 return redirect()->back()->with('error', $messages->first());
//             }

//             $invoicePayment = new InvoicePayment();
//             $invoicePayment->invoice_id = $invoice_id;
//             $invoicePayment->date = $request->date;
//             $invoicePayment->amount = $request->amount;
//             $invoicePayment->account_id = $request->account_id;
//             $invoicePayment->payment_method = 0;
//             $invoicePayment->reference = $request->reference;
//             $invoicePayment->description = $request->description;
//             if (!empty($request->add_receipt)) {
//                 //storage limit
//                 $image_size = $request->file('add_receipt')->getSize();
//                 $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);
//                 if ($result == 1) {
//                     $fileName = time() . "_" . $request->add_receipt->getClientOriginalName();
//                     $request->add_receipt->storeAs('uploads/payment', $fileName);
//                     $invoicePayment->add_receipt = $fileName;
//                 }

//             }

//             $invoicePayment->save();

//             $invoice = Invoice::where('id', $invoice_id)->first();
//             $due = $invoice->getDue();
//             $total = $invoice->getTotal();
//             if ($invoice->status == 0) {
//                 $invoice->send_date = date('Y-m-d');
//                 $invoice->save();
//             }

//             if ($due <= 0) {
//                 $invoice->status = 4;
//                 $invoice->save();
//             } else {
//                 $invoice->status = 3;
//                 $invoice->save();
//             }
//             $invoicePayment->user_id = $invoice->customer_id;
//             $invoicePayment->user_type = 'Customer';
//             $invoicePayment->type = 'Partial';
//             $invoicePayment->created_by = \Auth::user()->id;
//             $invoicePayment->payment_id = $invoicePayment->id;
//             $invoicePayment->category = 'Invoice';
//             $invoicePayment->account = $request->account_id;

//             Transaction::addTransaction($invoicePayment);
//             $customer = Customer::where('id', $invoice->customer_id)->first();
//             $customer->total_paid = $customer->total_paid + $request->amount;
//             $customer->save();

//             $payment = new InvoicePayment();
//             $payment->name = $customer['name'];
//             $payment->date = \Auth::user()->dateFormat($request->date);
//             $payment->amount = \Auth::user()->priceFormat($request->amount);
//             $payment->invoice = 'invoice ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
//             $payment->dueAmount = \Auth::user()->priceFormat($invoice->getDue());

//             Utility::updateUserBalance('customer', $invoice->customer_id, $request->amount, 'credit');

//             Utility::bankAccountBalance($request->account_id, $request->amount, 'debit');

//             $invoicePayments = InvoicePayment::where('invoice_id', $invoice->id)->get();
//             // foreach ($invoicePayments as $invoicePayment) {

//                 $accountId = BankAccount::find($invoicePayment->account_id);
//                 $data = [
//                     'account_id' => $request->account_id,
//                     'transaction_type' => 'Debit',
//                     'transaction_amount' => $request->amount,
//                     'reference' => 'Invoice Payment',
//                     'reference_id' => $invoice->id,
//                     'reference_sub_id' => $invoicePayment->id,
//                     'date' => $invoicePayment->date,
//                 ];
//                 Utility::addTransactionLines($data);
//                 $data = [
//                     'account_id' => $customer->chart_account_id,
//                     'transaction_type' => 'Credit',
//                     'transaction_amount' => $request->amount,
//                     'reference' => 'Invoice Payment',
//                     'reference_id' => $invoice->id,
//                     'reference_sub_id' => $invoicePayment->id,
//                     'date' => $invoicePayment->date,
//                 ];
//                 Utility::addTransactionLines($data);
//             // }

//             //add payment
//             $invoice = Invoice::find($invoice_id);
//             $payment = new CustomerPayment();
//             $payment->date = $request->date;
//             $payment->amount = $request->amount;
//             $payment->account_id = $request->account_id;
// //            $payment->chart_account_id  = $request->chart_account_id;
//             $payment->customer_id = $invoice->customer_id;
//             $payment->category_id = $invoice->category_id;
//             $payment->payment_method = 0;
//             $payment->reference = $request->reference;
//             $payment->invoice_id = $invoice_id;
//             $payment->payment_id =$invoicePayment->id;
//             if (!empty($request->add_receipt)) {

//                 //storage limit
//                 $image_size = $request->file('add_receipt')->getSize();
//                 $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);
//                 if ($result == 1) {
//                     if ($payment->add_receipt) {
//                         $path = storage_path('uploads/payment' . $payment->add_receipt);
//                     }
//                     $fileName = time() . "_" . preg_replace('/[^A-Za-z0-9\-]/', '', $request->add_receipt->getClientOriginalName());
// //                    $fileName = time() . "_" . $request->add_receipt->getClientOriginalName();
//                     $payment->add_receipt = $fileName;
//                     $dir = 'uploads/payment';
//                     $path = Utility::upload_file($request, 'add_receipt', $fileName, $dir, []);
//                     if ($path['flag'] == 0) {
//                         return redirect()->back()->with('error', __($path['msg']));
//                     }
//                 }

//             }
//             $payment->description = $request->description;
//             $payment->created_by = \Auth::user()->creatorId();
//             $payment->save();



//             $latestVoucher = GeneralLedger::orderBy('vid', 'desc')->first();
//                 // Extract the vid value from the last record and increment it
//                 if ($latestVoucher) {
//                     $lastVid = $latestVoucher->vid;
//                     $newVid = $lastVid + 1;
//                 } else {
//                     // If no record exists, start with 1
//                     $newVid = 1;
//                 }
//             $existingRecord = GeneralLedger::where('vid', $newVid)->exists();

//             if ($existingRecord) {
//                 return redirect()->back()->with('error', __("something went wrong , please try again."));
//             }

//             // Retrieve the chart account ID for the bank account
//             $chartAccountId = BankAccount::where('id', $request->account_id)->value('chart_account_id');

//             // Create a new entry for the bank account (debit)
//             $newEntryCredit = new GeneralLedger();
//             $newEntryCredit->vid = $newVid;
//             $newEntryCredit->account = $chartAccountId;
//             $newEntryCredit->type = 'Invoice Payment ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id); // or 'Credit'
//             $newEntryCredit->debit = $request->amount; // Example value
//             $newEntryCredit->credit = 0; // Example value
//             $newEntryCredit->ref_id = $invoice->id;
//             $newEntryCredit->user_id = 0;
//             $newEntryCredit->created_by = \Auth::user()->creatorId();
//             $newEntryCredit->save();

//             // Retrieve the chart account ID for the customer
//             $accountCustomer = $customer->chart_account_id;

//             // Create a new entry for the customer account (credit)
//             $newEntryDebit = new GeneralLedger();
//             $newEntryDebit->vid = $newVid;
//             $newEntryDebit->account = $accountCustomer;
//             $newEntryDebit->type = 'Invoice Payment ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id); // or 'Credit'
//             $newEntryDebit->debit = 0; // Example value
//             $newEntryDebit->credit = $request->amount; // Example value
//             $newEntryDebit->ref_id = $invoice->id;
//             $newEntryDebit->user_id = $customer->id;
//             $newEntryDebit->created_by = \Auth::user()->creatorId();
//             $newEntryDebit->balance = $customer->balance;
//             $newEntryDebit->save();



//             // Send Email
//             $setings = Utility::settings();
//             if ($setings['new_invoice_payment'] == 1) {
//                 $customer = Customer::where('id', $invoice->customer_id)->first();
//                 $invoicePaymentArr = [
//                     'invoice_payment_name' => $customer->name,
//                     'invoice_payment_amount' => $payment->amount,
//                     'invoice_payment_date' => $payment->date,
//                     'payment_dueAmount' => $payment->dueAmount,

//                 ];

//                 $resp = Utility::sendEmailTemplate('new_invoice_payment', [$customer->id => $customer->email], $invoicePaymentArr);
//             }

//             //webhook
//             $module = 'New Invoice Payment';
//             $webhook = Utility::webhookSetting($module);
//             if ($webhook) {
//                 $parameter = json_encode($invoice);
//                 $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
//                 if ($status == true) {
//                     return redirect()->back()->with('success', __('Payment successfully added.') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : '') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));

//                 } else {
//                     return redirect()->back()->with('error', __('Webhook call failed.'));
//                 }
//             }
//             return redirect()->back()->with('success', __('Payment successfully added.') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : '') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));

//         }

//     }

//     public function paymentDestroy(Request $request, $invoice_id, $payment_id)
//     {
// //        dd($invoice_id,$payment_id);

//         if (\Auth::user()->can('delete payment invoice')) {
//             $payment = InvoicePayment::find($payment_id);
//             $lastPayment = CustomerPayment::where('payment_id',$payment_id)->first();
//             if($lastPayment != null){
//                 $lastPayment->invoice_id = null;
//                 $lastPayment->payment_id = null;
//                 $lastPayment->save();
//             }
//             InvoicePayment::where('id', '=', $payment_id)->delete();

//             InvoiceBankTransfer::where('id', '=', $payment_id)->delete();

//             TransactionLines::where('reference_sub_id',$payment_id)->where('reference','Invoice Payment')->delete();

//             $invoice = Invoice::where('id', $invoice_id)->first();
//             $due = $invoice->getDue();
//             $total = $invoice->getTotal();

//             if ($due > 0 && $total != $due) {
//                 $invoice->status = 3;

//             } else {
//                 $invoice->status = 2;
//             }

//             if (!empty($payment->add_receipt)) {
//                 //storage limit
//                 $file_path = '/uploads/payment/' . $payment->add_receipt;
//                 $result = Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);

//             }

//             $invoice->save();
//             $type = 'Partial';
//             $user = 'Customer';
//             Transaction::destroyTransaction($payment_id, $type, $user);

//             // Utility::updateUserBalance('customer', $invoice->customer_id, $payment->amount, 'debit');

//             // Utility::bankAccountBalance($payment->account_id, $payment->amount, 'debit');

//              // Get the latest 'vid' entry, if any exist
//              $latestVoucher = GeneralLedger::orderBy('vid', 'desc')->first();
//                 // Extract the vid value from the last record and increment it
//                 if ($latestVoucher) {
//                     $lastVid = $latestVoucher->vid;
//                     $newVid = $lastVid + 1;
//                 } else {
//                     // If no record exists, start with 1
//                     $newVid = 1;
//                 }
//              $existingRecord = GeneralLedger::where('vid', $newVid)->exists();

//             if ($existingRecord) {
//                 return redirect()->back()->with('error', __("something went wrong , please try again."));
//             }

//              // Retrieve the chart account ID for the bank account
//              $chartAccountId = BankAccount::where('id', $payment->account_id)->value('chart_account_id');
//              $customer = Customer::where('id', $invoice->customer_id)->first();
//              $customer->total_paid = $customer->total_paid - $payment->amount;
//              $customer->save();
//              // Create a new entry for the bank account (credit)
//              $newEntryCredit = new GeneralLedger();
//              $newEntryCredit->vid = $newVid;
//              $newEntryCredit->account = $chartAccountId;
//              $newEntryCredit->type = 'Invoice delete Payment ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id); // or 'Credit'
//              $newEntryCredit->debit = 0; // Example value
//              $newEntryCredit->credit = $payment->amount; // Example value
//              $newEntryCredit->ref_id = $invoice->id;
//              $newEntryCredit->user_id = 0;
//              $newEntryCredit->created_by = \Auth::user()->creatorId();
//              $newEntryCredit->save();

//              // Retrieve the chart account ID for the customer

//              $accountCustomer = $customer->chart_account_id;

//              // Create a new entry for the customer account (debit)
//              $newEntryDebit = new GeneralLedger();
//              $newEntryDebit->vid = $newVid;
//              $newEntryDebit->account = $accountCustomer;
//              $newEntryDebit->type = 'Invoice delete Payment ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id); // or 'Credit'
//              $newEntryDebit->debit = $payment->amount; // Example value
//              $newEntryDebit->credit = 0; // Example value
//              $newEntryDebit->ref_id = $invoice->id;
//              $newEntryDebit->user_id = $customer->id;
//              $newEntryDebit->created_by = \Auth::user()->creatorId();
//              $newEntryDebit->balance = $customer->balance;
//              $newEntryDebit->save();

//             return redirect()->back()->with('success', __('Payment successfully deleted.'));
//         } else {
//             return redirect()->back()->with('error', __('Permission denied.'));
//         }
//     }

//     public function paymentReminder($invoice_id)
//     {

// //        dd($invoice_id);
//         $invoice = Invoice::find($invoice_id);
//         $customer = Customer::where('id', $invoice->customer_id)->first();
//         $invoice->dueAmount = \Auth::user()->priceFormat($invoice->getDue());
//         $invoice->name = $customer['name'];
//         $invoice->date = \Auth::user()->dateFormat($invoice->send_date);
//         $invoice->invoice = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);

//         //For Notification
//         $setting = Utility::settings(\Auth::user()->creatorId());
//         $customer = Customer::find($invoice->customer_id);
//         $reminderNotificationArr = [
//             'invoice_number' => \Auth::user()->invoiceNumberFormat($invoice->invoice_id),
//             'customer_name' => $customer->name,
//             'user_name' => \Auth::user()->name,
//         ];

//         //Twilio Notification
//         if (isset($setting['twilio_reminder_notification']) && $setting['twilio_reminder_notification'] == 1) {
//             Utility::send_twilio_msg($customer->contact, 'invoice_payment_reminder', $reminderNotificationArr);
//         }

//         // Send Email
//         $setings = Utility::settings();
//         if ($setings['new_payment_reminder'] == 1) {
//             $invoice = Invoice::find($invoice_id);
//             $customer = Customer::where('id', $invoice->customer_id)->first();
//             $invoice->dueAmount = \Auth::user()->priceFormat($invoice->getDue());
//             $invoice->name = $customer['name'];
//             $invoice->date = \Auth::user()->dateFormat($invoice->send_date);
//             $invoice->invoice = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);

//             $reminderArr = [

//                 'payment_reminder_name' => $invoice->name,
//                 'invoice_payment_number' => $invoice->invoice,
//                 'invoice_payment_dueAmount' => $invoice->dueAmount,
//                 'payment_reminder_date' => $invoice->date,

//             ];

//             $resp = Utility::sendEmailTemplate('new_payment_reminder', [$customer->id => $customer->email], $reminderArr);

//         }

//         return redirect()->back()->with('success', __('Payment reminder successfully send.') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
//     }

//     public function customerInvoiceSend($invoice_id)
//     {
//         return view('customer.invoice_send', compact('invoice_id'));
//     }

//     public function customerInvoiceSendMail(Request $request, $invoice_id)
//     {
//         $validator = \Validator::make(
//             $request->all(), [
//                 'email' => 'required|email',
//             ]
//         );
//         if ($validator->fails()) {
//             $messages = $validator->getMessageBag();

//             return redirect()->back()->with('error', $messages->first());
//         }

//         $email = $request->email;
//         $invoice = Invoice::where('id', $invoice_id)->first();

//         $customer = Customer::where('id', $invoice->customer_id)->first();
//         $invoice->name = !empty($customer) ? $customer->name : '';
//         $invoice->invoice = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);

//         $invoiceId = Crypt::encrypt($invoice->id);
//         $invoice->url = route('invoice.pdf', $invoiceId);

//         try
//         {
//             Mail::to($email)->send(new CustomerInvoiceSend($invoice));
//         } catch (\Exception $e) {
//             $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
//         }

//         return redirect()->back()->with('success', __('Invoice successfully sent.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));

//     }

//     public function shippingDisplay(Request $request, $id)
//     {
//         $invoice = Invoice::find($id);

//         if ($request->is_display == 'true') {
//             $invoice->shipping_display = 1;
//         } else {
//             $invoice->shipping_display = 0;
//         }
//         $invoice->save();

//         return redirect()->back()->with('success', __('Shipping address status successfully changed.'));
//     }

//     public function duplicate($invoice_id)
//     {
//         if (\Auth::user()->can('duplicate invoice')) {
//             $invoice = Invoice::where('id', $invoice_id)->first();
//             $duplicateInvoice = new Invoice();
//             $duplicateInvoice->invoice_id = $this->invoiceNumber();
//             $duplicateInvoice->customer_id = $invoice['customer_id'];
//             $duplicateInvoice->issue_date = date('Y-m-d');
//             $duplicateInvoice->due_date = $invoice['due_date'];
//             $duplicateInvoice->send_date = null;
//             $duplicateInvoice->category_id = $invoice['category_id'];
//             $duplicateInvoice->ref_number = $invoice['ref_number'];
//             $duplicateInvoice->status = 0;
//             $duplicateInvoice->shipping_display = $invoice['shipping_display'];
//             $duplicateInvoice->created_by = $invoice['created_by'];
//             $duplicateInvoice->save();

//             if ($duplicateInvoice) {
//                 $invoiceProduct = InvoiceProduct::where('invoice_id', $invoice_id)->get();
//                 foreach ($invoiceProduct as $product) {
//                     $duplicateProduct = new InvoiceProduct();
//                     $duplicateProduct->invoice_id = $duplicateInvoice->id;
//                     $duplicateProduct->product_id = $product->product_id;
//                     $duplicateProduct->quantity = $product->quantity;
//                     $duplicateProduct->tax = $product->tax;
//                     $duplicateProduct->discount = $product->discount;
//                     $duplicateProduct->price = $product->price;
//                     $duplicateProduct->save();
//                 }
//             }

//             return redirect()->back()->with('success', __('Invoice duplicate successfully.'));
//         } else {
//             return redirect()->back()->with('error', __('Permission denied.'));
//         }
//     }

//     public function previewInvoice($template, $color)
//     {

//         $objUser = \Auth::user();
//         $settings = Utility::settings();
//         $invoice = new Invoice();

//         $customer = new \stdClass();
//         $customer->email = '<Email>';
//         $customer->shipping_name = '<Customer Name>';
//         $customer->shipping_country = '<Country>';
//         $customer->shipping_state = '<State>';
//         $customer->shipping_city = '<City>';
//         $customer->shipping_phone = '<Customer Phone Number>';
//         $customer->shipping_zip = '<Zip>';
//         $customer->shipping_address = '<Address>';
//         $customer->billing_name = '<Customer Name>';
//         $customer->billing_country = '<Country>';
//         $customer->billing_state = '<State>';
//         $customer->billing_city = '<City>';
//         $customer->billing_phone = '<Customer Phone Number>';
//         $customer->billing_zip = '<Zip>';
//         $customer->billing_address = '<Address>';

//         $totalTaxPrice = 0;
//         $taxesData = [];

//         $items = [];
//         for ($i = 1; $i <= 3; $i++) {
//             $item = new \stdClass();
//             $item->name = 'Item ' . $i;
//             $item->quantity = 1;
//             $item->tax = 5;
//             $item->discount = 50;
//             $item->price = 100;
//             $item->unit = 1;
//             $item->description = 'XYZ';

//             $taxes = [
//                 'Tax 1',
//                 'Tax 2',
//             ];

//             $itemTaxes = [];
//             foreach ($taxes as $k => $tax) {
//                 $taxPrice = 10;
//                 $totalTaxPrice += $taxPrice;
//                 $itemTax['name'] = 'Tax ' . $k;
//                 $itemTax['rate'] = '10 %';
//                 $itemTax['price'] = '$10';
//                 $itemTax['tax_price'] = 10;
//                 $itemTaxes[] = $itemTax;
//                 if (array_key_exists('Tax ' . $k, $taxesData)) {
//                     $taxesData['Tax ' . $k] = $taxesData['Tax 1'] + $taxPrice;
//                 } else {
//                     $taxesData['Tax ' . $k] = $taxPrice;
//                 }
//             }
//             $item->itemTax = $itemTaxes;
//             $items[] = $item;
//         }

//         $invoice->invoice_id = 1;
//         $invoice->issue_date = date('Y-m-d H:i:s');
//         $invoice->due_date = date('Y-m-d H:i:s');
//         $invoice->itemData = $items;

//         $invoice->totalTaxPrice = 60;
//         $invoice->totalQuantity = 3;
//         $invoice->totalRate = 300;
//         $invoice->totalDiscount = 10;
//         $invoice->taxesData = $taxesData;
//         $invoice->created_by = $objUser->creatorId();

//         $invoice->customField = [];
//         $customFields = [];

//         $preview = 1;
//         $color = '#' . $color;
//         $font_color = Utility::getFontColor($color);

//         $logo = asset(Storage::url('uploads/logo/'));
//         $company_logo = Utility::getValByName('company_logo_dark');
//         $invoice_logo = Utility::getValByName('invoice_logo');
//         if (isset($invoice_logo) && !empty($invoice_logo)) {
//             $img = Utility::get_file('invoice_logo/') . $invoice_logo;
//         } else {
//             $img = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo.png'));
//         }

//         return view('invoice.templates.' . $template, compact('invoice', 'preview', 'color', 'img', 'settings', 'customer', 'font_color', 'customFields'));
//     }

//     public function invoice($invoice_id)
//     {
//         $settings = Utility::settings();

//         $invoiceId = Crypt::decrypt($invoice_id);
//         $invoice = Invoice::where('id', $invoiceId)->first();

//         $data = DB::table('settings');
//         $data = $data->where('created_by', '=', $invoice->created_by);
//         $data1 = $data->get();

//         foreach ($data1 as $row) {
//             $settings[$row->name] = $row->value;
//         }

//         $customer = $invoice->customer;
//         $items = [];
//         $totalTaxPrice = 0;
//         $totalQuantity = 0;
//         $totalRate = 0;
//         $totalDiscount = 0;
//         $taxesData = [];
//         foreach ($invoice->items as $product) {
//             $item = new \stdClass();
//             $item->name = !empty($product->product) ? $product->product->name : '';
//             $item->quantity = $product->quantity;
//             $item->tax = $product->tax;
//             $item->unit = !empty($product->product) ? $product->product->unit_id : '';
//             $item->discount = $product->discount;
//             $item->price = $product->price;
//             $item->description = $product->description;

//             $totalQuantity += $item->quantity;
//             $totalRate += $item->price;
//             $totalDiscount += $item->discount;

//             $taxes = Utility::tax($product->tax);

//             $itemTaxes = [];
//             if (!empty($item->tax)) {
//                 foreach ($taxes as $tax) {
//                     $taxPrice = Utility::taxRate($tax->rate, $item->price, $item->quantity, $item->discount);
//                     $totalTaxPrice += $taxPrice;

//                     $itemTax['name'] = $tax->name;
//                     $itemTax['rate'] = $tax->rate . '%';
//                     $itemTax['price'] = Utility::priceFormat($settings, $taxPrice);
//                     $itemTax['tax_price'] = $taxPrice;
//                     $itemTaxes[] = $itemTax;

//                     if (array_key_exists($tax->name, $taxesData)) {
//                         $taxesData[$tax->name] = $taxesData[$tax->name] + $taxPrice;
//                     } else {
//                         $taxesData[$tax->name] = $taxPrice;
//                     }

//                 }
//                 $item->itemTax = $itemTaxes;
//             } else {
//                 $item->itemTax = [];
//             }
//             $items[] = $item;
//         }

//         $invoice->itemData = $items;
//         $invoice->totalTaxPrice = $totalTaxPrice;
//         $invoice->totalQuantity = $totalQuantity;
//         $invoice->totalRate = $totalRate;
//         $invoice->totalDiscount = $totalDiscount;
//         $invoice->taxesData = $taxesData;
//         $invoice->customField = CustomField::getData($invoice, 'invoice');
//         $customFields = [];
//         if (!empty(\Auth::user())) {
//             $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'invoice')->get();
//         }
// //
// //        $logo         = asset(Storage::url('uploads/logo/'));
// //        $company_logo = Utility::getValByName('company_logo_dark');
// //        $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo.png'));

//         $logo = asset(Storage::url('uploads/logo/'));
//         $company_logo = Utility::getValByName('company_logo_dark');
//         $settings_data = \App\Models\Utility::settingsById($invoice->created_by);
//         $invoice_logo = $settings_data['invoice_logo'];
//         if (isset($invoice_logo) && !empty($invoice_logo)) {
//             $img = Utility::get_file('invoice_logo/') . $invoice_logo;
//         } else {
//             $img = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo.png'));
//         }

//         if ($invoice) {
//             $color = '#' . $settings['invoice_color'];
//             $font_color = Utility::getFontColor($color);

//             return view('invoice.templates.' . $settings['invoice_template'], compact('invoice', 'color', 'settings', 'customer', 'img', 'font_color', 'customFields'));
//         } else {
//             return redirect()->back()->with('error', __('Permission denied.'));
//         }

//     }

//     public function saveTemplateSettings(Request $request)
//     {

//         $post = $request->all();
//         unset($post['_token']);

//         if (isset($post['invoice_template']) && (!isset($post['invoice_color']) || empty($post['invoice_color']))) {
//             $post['invoice_color'] = "ffffff";
//         }

//         if ($request->invoice_logo) {
//             $dir = 'invoice_logo/';
//             $invoice_logo = \Auth::user()->id . '_invoice_logo.png';
//             $validation = [
//                 'mimes:' . 'png',
//                 'max:' . '20480',
//             ];
//             $path = Utility::upload_file($request, 'invoice_logo', $invoice_logo, $dir, $validation);

//             if ($path['flag'] == 0) {
//                 return redirect()->back()->with('error', __($path['msg']));
//             }
//             $post['invoice_logo'] = $invoice_logo;
//         }

//         foreach ($post as $key => $data) {
//             \DB::insert(
//                 'insert into settings (`value`, `name`,`created_by`) values (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ', [
//                     $data,
//                     $key,
//                     \Auth::user()->creatorId(),
//                 ]
//             );
//         }

//         return redirect()->back()->with('success', __('Invoice Setting updated successfully'));
//     }

//     public function items(Request $request)
//     {
//         $items = InvoiceProduct::where('invoice_id', $request->invoice_id)->where('product_id', $request->product_id)->first();

//         return json_encode($items);
//     }

//     public function invoiceLink($invoiceId)
//     {
//         try {
//             $id = Crypt::decrypt($invoiceId);
//         } catch (\Throwable $th) {
//             return redirect()->back()->with('error', __('Invoice Not Found.'));
//         }

//         $id = Crypt::decrypt($invoiceId);
//         $invoice = Invoice::with(['creditNote','payments.bankAccount','items.product.unit'])->find($id);

//         $settings = Utility::settingsById($invoice->created_by);

//         if (!empty($invoice)) {

//             $user_id = $invoice->created_by;
//             $user = User::find($user_id);
//             $invoicePayment = InvoicePayment::where('invoice_id', $invoice->id)->get();
//             $customer = $invoice->customer;
//             $iteams = $invoice->items;
//             $invoice->customField = CustomField::getData($invoice, 'invoice');
//             $customFields = CustomField::where('module', '=', 'invoice')->get();
//             $company_payment_setting = Utility::getCompanyPaymentSetting($user_id);

//             // start for storage limit note
//             $user_plan = Plan::find($user->plan);
//             // end for storage limit note

//             return view('invoice.customer_invoice', compact('settings', 'invoice', 'customer', 'iteams', 'invoicePayment', 'customFields', 'user', 'company_payment_setting', 'user_plan'));
//         } else {
//             return redirect()->back()->with('error', __('Permission Denied.'));
//         }

//     }

//     public function export()
//     {
//         $name = 'invoice_' . date('Y-m-d i:h:s');
//         $data = Excel::download(new InvoiceExport(), $name . '.xlsx');
//         ob_end_clean();

//         return $data;
//     }

//     function goToAddSubProducts($invoice_id) {
//         $invoice = Invoice::find($invoice_id);

//         // Assuming $invoiceId is the ID of the invoice you're interested in
//         $invoiceProducts = InvoiceProduct::where('invoice_id', $invoice_id)->get();

//         // Extract the sub_product_id values from $invoiceProducts
//         $subProductIds = $invoiceProducts->pluck('sub_product_id')->toArray();

//         // Retrieve all sub-products with the extracted IDs
//         $subProducts = SubProduct::whereIn('id', $subProductIds)->get();
//         // $subProducts = [];
//         // foreach($invoiceProducts as $invoiceProduct){
//         //     $productId = $invoiceProduct->product_id;
//         //     $qty = $invoiceProduct->qty;
//         //     $subProductsArray = SubProduct::where('product_id', $productId)->limit($qty)->get();
//         //     // Add the retrieved sub-products to the array
//         //     foreach($subProductsArray as $item){
//         //         $subProducts [] = $item;
//         //     }



//         // }
//         $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'sub-product')->get();
//         $product_services = ProductService::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
//         $totalTaxPrice = 0 ;
//         $total_amount= 0 ;
//         foreach($subProducts as $item){
//             $total_amount+=$item->sale_price;
//         }
//         $totalTaxName = ' ' ;
//         $tax_bill = $invoice->tax_id ;
//         $taxes = \App\Models\Utility::tax($invoice->tax_id);
//         foreach ($taxes as $tax) {
//                     $taxPrice = Tax::where('id', $tax->id)->first()->rate;
//                     $totalTaxPrice += $taxPrice;
//                     $totalTaxName = $totalTaxName . ' ' . Tax::where('id', $tax->id)->first()->name;
//                 }
//         return view('invoice.addProducts', compact('subProducts', 'customFields', 'invoice','product_services','tax_bill','total_amount','totalTaxPrice','totalTaxName'));
//     }

//     function destroySubProduct($id,$invoice_id)
//     {
//             $productService = SubProduct::find($id);
//             $invoice=Invoice::find($invoice_id);
//             if($productService->created_by == \Auth::user()->creatorId())
//             {
//                 $invoice_product =InvoiceProduct::where('sub_product_id',$id)->first();
//                 $invoice_product->delete();
//                 $productService->booked = 0;
//                 $productService->invoice_id  = null;
//                 $productService->save();
//                 return redirect()->route('invoice.addSubProducts', $invoice_id)->with('success', __('Sub Product successfully deleted.'));
//             }
//             else
//             {
//                 return redirect()->back()->with('error', __('Permission denied.'));
//             }


//     }

//     public function createSubProduct($id)
//     {

//             $invoice = Invoice::find($id);
//             $product_services = ProductService::where('created_by', \Auth::user()->creatorId())->whereHas('subProducts', function ($query) {
//                 $query->where('flag', '!=', 2)->where('booked','=' , 0);
//             })->get()->pluck('name', 'id');
//             $product_services->prepend('--', '');

//             return view('invoice.createSubProduct', compact('id','product_services'));

//     }

//     public function storeSubProduct(Request $request)
//     {
//             $rules = [
//                 // 'name' => 'required',
//                 'subProducts' => 'required',
//                 'quantity' => 'required|numeric',
//                 'product_id' => 'required',

//             ];

//             $validator = \Validator::make($request->all(), $rules);

//             if($validator->fails())
//             {
//                 $messages = $validator->getMessageBag();

//                 return redirect()->route('invoice.addSubProducts', ['invoice_id' => $request->id])->with('error', $messages->first());
//             }
//             $invoice = Invoice::find($request->id);
//             // $productService                      = SubProduct::find($request->sub_product_id);
//             // $productService->name                = $request->name;
//             // $productService->number              = $request->number;
//             // $productService->sale_price          = $request->sale_price;
//             // $productService->save();
//             // CustomField::saveData($productService, $request->customField);
//             $product = ProductService::find($request->product_id);
//             $qty = $request->quantity;

//             // Add the retrieved sub-products to the array
//             $subProducts = $request->input('subProducts');
//             foreach($subProducts as $key => $subProduct){
//                 $subProductID = (int)$subProduct;
//                 $productService = SubProduct::find($subProductID);
//                 if($product->type != 'service'){
//                     $productService->invoice_id = $invoice->id;
//                     $productService->booked = 1;
//                     $productService->save();
//                 }
//                 $invoiceProduct = new InvoiceProduct();
//                 $invoiceProduct->invoice_id = $invoice->id;
//                 $invoiceProduct->product_id = $request->product_id;
//                 $invoiceProduct->sub_product_id = $productService->id;
//                 $invoiceProduct->quantity = $request->quantity;
//                 $invoiceProduct->tax = $invoice->tax_id;
//                 $invoiceProduct->discount = 0;
//                 $invoiceProduct->price = $productService->sale_price;
//                 $invoiceProduct->save();
//             }
//             return redirect()->route('invoice.addSubProducts', $request->id)->with('success', __('Sub Product successfully created.'));

//     }

//     function updateSubProduct(Request $request){
//         // dd($request->all());
//         $invoice = Invoice::find($request->id);
//         $items = $request->items;
//         $invoiceProducts = InvoiceProduct::where('invoice_id',$request->id)->delete();
//         foreach($items as $index => $item ){
//             $productService = SubProduct::find($item['sub_product_id']);
//             $invoiceProduct = new InvoiceProduct();
//             $invoiceProduct->invoice_id = $invoice->id;
//             $invoiceProduct->product_id =$productService->product_id;
//             $invoiceProduct->sub_product_id = $item['sub_product_id'];
//             $invoiceProduct->quantity = InvoiceProduct::where('product_id',$productService->product_id)->where('invoice_id',$request->id)->first() ? InvoiceProduct::where('product_id',$productService->product_id)->where('invoice_id',$request->id)->first()->quantity + 1 : 1;
//             $invoiceProduct->tax = $invoice->tax_id;
//             // $invoiceProduct->discount = $item['discount'];
//             $invoiceProduct->price = $item['sale_price'];
//             // $invoiceProduct->description = $item['description'];
//             $invoiceProduct->save();
//         }
//         return redirect()->route('invoice.index', $request->id)->with('success', __('Sub Product successfully created.'));

//     }

//     public function getInvoiceDetails($invoice_id)
//     {
//         $invoice = Invoice::findOrFail($invoice_id);

//         return response()->json(['due_amount' => $invoice->getDue()]);
//     }

//     public function getInvoices($vendorId)
//     {

//         $invoices = Invoice::where("customer_id",$vendorId)->where("status","!=","0")->get();

//         return response()->json($invoices);
//     }

}
