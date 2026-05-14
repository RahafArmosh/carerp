<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\BillAccount;
use App\Models\BillPayment;
use App\Models\Currency;
use App\Models\Payment;
use App\Models\BillProduct;
use App\Models\ChartOfAccount;
use App\Models\CustomField;
use App\Models\Expense;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use App\Models\Project;
use App\Models\StockReport;
use App\Models\Utility;
use App\Models\Tax;
use App\Models\ActivityLog;
use App\Models\Vender;
use App\Models\SubProduct;
use App\Models\TransactionLines;
use App\Models\GeneralLedger;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;

class ExpenseController extends Controller
{

    function billNumber()
    {
        $latest = Bill::where('created_by', '=', \Auth::user()->creatorId())->where('type', '=', 'Bill')->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->bill_id + 1;
    }

    function expenseNumber()
    {
        $latest = Bill::where('created_by', '=', \Auth::user()->creatorId())->where('type', '=', 'Expense')->latest()->first();
        if (!$latest) {
            return 1;
        }

        return (int)$latest->bill_id + 1;
    }

    public function vender(Request $request)
    {
        $vender = Vender::where('id', '=', $request->id)->first();

        return view('expense.vender_detail', compact('vender'));
    }

    public function product(Request $request)
    {
        $data['product']     = $product = ProductService::find($request->product_id);
        $data['unit']        = !empty($product->unit) ? $product->unit->name : '';
        $data['taxRate']     = $taxRate = !empty($product->tax_id) ? $product->taxRate($product->tax_id) : 0;
        $data['taxes']       = !empty($product->tax_id) ? $product->tax($product->tax_id) : 0;
        $salePrice           = $product->purchase_price;
        $quantity            = 1;
        $taxPrice            = ($taxRate / 100) * ($salePrice * $quantity);
        $data['totalAmount'] = ($salePrice * $quantity);

        return json_encode($data);
    }

    public function index(Request $request)
    {

        if (\Auth::user()->can('manage bill')) {

            $vender = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $vender->prepend('Select Vendor', '');

            $category     = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())
                ->whereNotIn('type', ['product & service', 'income',])
                ->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');

            $status = Bill::$statues;

            $query = Bill::where('type', '=', 'Expense')
                ->where('created_by', '=', \Auth::user()->creatorId());
            if (!empty($request->vender)) {
                $query->where('vender_id', '=', $request->vender);
            }
            if (count(explode('to', $request->bill_date)) > 1) {
                $date_range = explode(' to ', $request->bill_date);
                $query->whereBetween('bill_date', $date_range);
            } elseif (!empty($request->bill_date)) {
                $date_range = [$request->date, $request->bill_date];
                $query->whereBetween('bill_date', $date_range);
            }

            if (!empty($request->category)) {
                $query->where('category_id', '=', $request->category);
            }


            $expenses = $query->with(['category'])->get();

            return view('expense.index', compact('expenses', 'vender', 'status', 'category'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function create($Id)
    {
        if (\Auth::user()->can('create bill')) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'bill')->get();
            $category     = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())
                ->whereNotIn('type', ['product & service', 'income',])
                ->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');

            $expense_number = \Auth::user()->expenseNumberFormat($this->expenseNumber());

            $venders     = Vender::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $venders->prepend('Select Vender', '');

            $product_services = ProductService::where('created_by', \Auth::user()->creatorId())
                ->whereHas('subProducts')
                ->with(['brand', 'subBrand', 'category', 'subProducts']) // Load sub-products
                ->get()
                ->flatMap(function ($productService) {
                    $category = $productService->category->name ?? '';
                    $brand = $productService->brand->name ?? '';
                    $subBrand = $productService->subBrand->name ?? '';
                    $productName = $productService->name;

                    // Fetch sub-products and format them
                    return $productService->subProducts->map(function ($subProduct) use ($category, $brand, $subBrand, $productName) {
                        return [
                            'id' => $subProduct->id, // Sub-product ID
                            'name' => $category . '/' . $brand . '/' . $subBrand . '/' . $productName . '/' . $subProduct->chassis_no,
                        ];
                    });
                })
                ->pluck('name', 'id'); // Convert to key-value array
            $product_services->prepend('Select Item', '');

            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');

            $accounts   = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))
                ->where('created_by', \Auth::user()->creatorId())
                ->get()->pluck('name', 'id');

            $fullTax          = Tax::where('created_by', \Auth::user()->creatorId())->get();
            $currency = Currency::get()->pluck('name', 'id');
            $currency->prepend('AED', '');
            return view('expense.create', compact('venders', 'expense_number', 'product_services', 'category', 'customFields', 'Id', 'chartAccounts', 'accounts', 'fullTax', 'currency'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }


    public function store(Request $request)
    {
        if (\Auth::user()->can('create bill')) {

            $validator = \Validator::make(
                $request->all(),
                [
                    //                    'vender_id' => 'required',
                    'payment_date' => 'required',
                    // 'items.*.tax_id' => 'required',
                    'category_id' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages3 = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages3->first());
            }
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
                if (!empty($request->items) && empty($request->items[0]['item']) && empty($request->items[0]['chart_account_id']) && empty($request->items[0]['amount'])) {
                    $itemValidator = \Validator::make(
                        $request->all(),
                        [
                            'item' => 'required'
                        ]
                    );
                    if ($itemValidator->fails()) {
                        $messages1 = $itemValidator->getMessageBag();
                        return redirect()->back()->with('error', $messages1->first());
                    }
                }

                if (!empty($request->items) && empty($request->items[0]['chart_account_id'])  && !empty($request->items[0]['amount'])) {
                    $accountValidator = \Validator::make(
                        $request->all(),
                        [
                            'chart_account_id' => 'required'
                        ]
                    );
                    if ($accountValidator->fails()) {
                        $messages2 = $accountValidator->getMessageBag();
                        return redirect()->back()->with('error', $messages2->first());
                    }
                }

                $expense                 = new Bill();
                $lastBillId = Bill::withTrashed()->where('created_by', '=', \Auth::user()->creatorId())->where('type', '=', 'Expense')->latest()->first();
                if ($lastBillId && preg_match('/#EXP(\d+)/', $lastBillId->bill_id, $matches)) {
                    $number = (int)$matches[1] + 1;
                    $nextBillId = '#EXP' . str_pad($number, 5, '0', STR_PAD_LEFT);
                } else {
                    // Start from 1 if no bill found
                    $nextBillId = '#EXP00001';
                }
                $expense->bill_id        = $nextBillId;
                $expense->vender_id      = $request->vender_id;
                $vendorAccountId = Vender::where('id', $request->vender_id)->first()->chart_account_id;
                $expense->bill_date      = $request->payment_date;
                $expense->status         = 4;
                $expense->payment_status         = 4;
                $expense->type           = 'Expense';
                $expense->user_type      = 'vendor';
                $expense->due_date       = $request->payment_date;
                $expense->category_id    = !empty($request->category_id) ? $request->category_id : 0;
                $expense->order_number   = 0;
                $expense->created_by     = \Auth::user()->creatorId();
                $expense->tax_id     = !empty($request->tax_id) ? implode(',', $request->tax_id) : '';
                $expense->currency_id = $request->currency_id ?? null;
                if ($request->filled('currency_id')) {
                    if ($request->filled('exchange_rate')) {
                        $expense->exchange_rate = $request->exchange_rate;
                    } else {
                        $currency = Currency::find($request->currency_id);
                        $expense->exchange_rate = $currency ? $currency->rate : 0;
                    }
                } else {
                    $expense->exchange_rate = 1;
                }
                $expense->save();
                $products = $request->items;


                $total_amount = 0;
                // Header-level tax will be applied after summing account lines
                $total_tax = 0;
                $total_taxTotal = 0;

                for ($i = 0; $i < count($products); $i++) {
                    $bill_price = $products[$i]['amount'];

                    if (!empty($request->currency_id)) {
                        $exchangeRate = $request->exchange_rate
                            ?? optional(Currency::find($request->currency_id))->exchange_rate;

                        if ($exchangeRate) {
                            $bill_price = $bill_price * $exchangeRate;
                        }
                    }
                    if (!empty($products[$i]['item'])) {

                        $subProduct = SubProduct::find($products[$i]['item']);
                        $product = $subProduct->productService;

                        $expenseProduct              = new BillProduct();
                        $expenseProduct->bill_id     = $expense->id;
                        $expenseProduct->product_id  = $product->id;
                        $expenseProduct->sub_product_id  = $subProduct->id;
                        $expenseProduct->quantity    = $products[$i]['quantity'] ?? 1;
                        // Per-item tax not used; tax is header-level
                        $expenseProduct->tax         = null;
                        $expenseProduct->discount    = $products[$i]['discount'] ?? 0;
                        $expenseProduct->price       = 0; // enforce zero price for item lines
                        $expenseProduct->description = $products[$i]['description'] ?? '';
                        $expenseProduct->save();
                        // Do not count item rows into totals; they are zero-priced
                        $bill_price = 0;
                    }
                    if (empty($products[$i]['item']) && !empty($products[$i]['chart_account_id'])) {
                        $expenseAccount                    = new BillAccount();
                        $expenseAccount->chart_account_id  = $products[$i]['chart_account_id'];
                        $expenseAccount->price             = $bill_price;
                        $expenseAccount->description       = $products[$i]['description'];
                        $expenseAccount->type              = 'Bill';
                        $expenseAccount->ref_id            = $expense->id;
                        $expenseAccount->save();
                        $expenseTotal = $expenseAccount->price;
                    }
                    // Tax is stored on the bill header; skip creating per-line VAT ledger entries

                    if (empty($products[$i]['item']) && $products[$i]['chart_account_id'] != null) {
                        // Create a new entry for debit to  account
                        $AccountEntry = new GeneralLedger();
                        $AccountEntry->vid = $newVoucherId;
                        $AccountEntry->account = $products[$i]['chart_account_id'];
                        $AccountEntry->type = $expense->bill_id;
                        $AccountEntry->ref_number = $expense->bill_id;
                        $AccountEntry->debit = $bill_price;
                        $AccountEntry->credit = 0;
                        $AccountEntry->ref_id = $expense->id;
                        $AccountEntry->user_id = 0;
                        $AccountEntry->created_by = \Auth::user()->creatorId();
                        $AccountEntry->send_date = $expense->bill_date;
                        $AccountEntry->reference = 'Expense';
                        $AccountEntry->save();
                    }
                    $total_amount = $total_amount + $bill_price;
                    // $total_taxTotal left 0; tax captured in bill header
                }
                // Calculate header-level tax and grand total
                $headerTaxRate = 0;
                if (!empty($request->tax_id)) {
                    foreach ($request->tax_id as $tid) {
                        $t = Tax::find($tid);
                        if ($t) { $headerTaxRate += (float)$t->rate; }
                    }
                }
                $headerTaxAmount = $total_amount * ($headerTaxRate/100);
                $grandTotal = $total_amount + $headerTaxAmount;

                // Create VAT/Tax entries (debit to Tax account)
                if ($headerTaxAmount > 0 && !empty($request->tax_id)) {
                    foreach ($request->tax_id as $tid) {
                        $tax = Tax::find($tid);
                        if ($tax && $tax->chart_account_id) {
                            // Calculate tax amount for this specific tax rate
                            $taxRate = (float)$tax->rate;
                            $taxAmount = $total_amount * ($taxRate / 100);
                            
                            $VatEntry = new GeneralLedger();
                            $VatEntry->vid = $newVoucherId;
                            $VatEntry->account = $tax->chart_account_id;
                            $VatEntry->type = $expense->bill_id;
                            $VatEntry->ref_number = $expense->bill_id;
                            $VatEntry->debit = $taxAmount;
                            $VatEntry->credit = 0;
                            $VatEntry->ref_id = $expense->id;
                            $VatEntry->user_id = 0;
                            $VatEntry->created_by = \Auth::user()->creatorId();
                            $VatEntry->send_date = $expense->bill_date;
                            $VatEntry->reference = 'Expense';
                            $VatEntry->save();
                        }
                    }
                }

                // Create a new entry for credit to Vendor account (include header tax)
                $vendorEntry = new GeneralLedger();
                $vendorEntry->vid = $newVoucherId;
                $vendorEntry->account = $vendorAccountId;
                $vendorEntry->type = $expense->bill_id;
                $vendorEntry->ref_number = $expense->bill_id;
                $vendorEntry->debit = 0;
                $vendorEntry->credit = $grandTotal;
                $vendorEntry->ref_id = $expense->id;
                $vendorEntry->user_id = $expense->vender_id;
                $vendorEntry->user_type = 'vendor';
                $vendorEntry->created_by = \Auth::user()->creatorId();
                $vendorEntry->balance = 0;
                $vendorEntry->send_date = $expense->bill_date;
                $vendorEntry->reference = 'Expense';
                $vendorEntry->save();
                Utility::updateUserBalance($request->type, $expense->vender_id, $grandTotal, 'debit');

                $expensePayment = null;
                if (!$request->filled('no_payment')) {
                    $expensePayment                 = new BillPayment();
                    $expensePayment->bill_id        =  $expense->id;
                    $expensePayment->date           = $request->payment_date;
                    $expensePayment->amount         = $grandTotal;
                    $expensePayment->account_id     = $request->account_id;
                    $expensePayment->payment_method = 0;
                    $expensePayment->reference      = 'NULL';
                    $expensePayment->description    = 'NULL';
                    $expensePayment->add_receipt    = 'NULL';
                    $expensePayment->save();
                }

                if (!empty($request->chart_account_id)) {

                    $expenseaccount = ProductServiceCategory::find($request->category_id);
                    $chart_account = ChartOfAccount::find($expenseaccount->chart_account_id);
                    $expenseAccount                    = new BillAccount();
                    $expenseAccount->chart_account_id  = $chart_account['id'];
                    $expenseAccount->price             = $grandTotal;
                    $expenseAccount->description       = $request->description;
                    $expenseAccount->type              = 'Bill Category';
                    $expenseAccount->ref_id            = $expense->id;
                    $expenseAccount->save();
                }

                if (!$request->filled('no_payment')) {
                    // Utility::bankAccountBalance($request->account_id, $total_amount + $total_taxTotal, 'debit');
                }


                if (!$request->filled('no_payment')) {
                    $accountId = BankAccount::find($request->account_id);
                    if (!$accountId || !$accountId->chart_account_id) {
                        DB::rollBack();
                        return redirect()->back()->with('error', __('Bank account not found or chart account not configured.'));
                    }
                    
                    Utility::updateUserBalance('vendor', $expense->vender_id, $grandTotal, 'credit');
                    $payment = new Payment();
                    $payment->date = $request->payment_date;
                    $payment->amount = $grandTotal;
                    $payment->currency_id = $request->currency_id ?? null;
                    $payment->currency_rate = $request->exchange_rate ?? null;
                    $payment->account_id = $request->account_id;
                    $payment->vender_id = $request->vender_id;
                    $payment->category_id = $request->category_id;
                    $payment->payment_method = 0;
                    $payment->status = 2;
                    $payment->bill_id = $expense->id;
                    $payment->payment_id = $expensePayment->id;
                    $payment->created_by = \Auth::user()->creatorId();
                    $payment->payment_number = Payment::nextPaymentNumberFor($payment->created_by);
                    $payment->save();
                    if ($expensePayment) {
                        $expensePayment->payment_id     = $payment->id;
                        $expensePayment->save();
                    }
                    
                    // Create a new entry for debit to bank account
                    $debitEntry = new GeneralLedger();
                    $debitEntry->vid = $newVoucherId + 1;
                    $debitEntry->account = $accountId->chart_account_id;
                    $debitEntry->type =  'Expense Payment ' . $expense->bill_id;
                    $debitEntry->ref_number =  'Expense Payment ' . $expense->bill_id;
                    $debitEntry->debit = 0;
                    $debitEntry->credit = $grandTotal;
                    $debitEntry->ref_id = $expense->id;
                    $debitEntry->user_id = 0;
                    $debitEntry->payment_id = $payment->id;
                    $debitEntry->created_by = \Auth::user()->creatorId();
                    $debitEntry->balance = 0;
                    $debitEntry->send_date = $request->payment_date;
                    $debitEntry->reference = 'Expense Payment';
                    $debitEntry->save();
                }


                // Create a new entry for credit to payment account
                if (!$request->filled('no_payment')) {
                    $creditEntry = new GeneralLedger();
                    $creditEntry->vid = $newVoucherId + 1;
                    $creditEntry->account = $vendorAccountId;
                    $creditEntry->type =  'Expense Payment ' . $expense->bill_id;
                    $creditEntry->ref_number =  'Expense Payment ' . $expense->bill_id;
                    $creditEntry->debit = $grandTotal;
                    $creditEntry->credit = 0;
                    $creditEntry->ref_id = $expense->id;
                    $creditEntry->user_id = $expense->vender_id;
                    $creditEntry->user_type = 'vendor';
                    $creditEntry->payment_id = $payment->id;
                    $creditEntry->created_by = \Auth::user()->creatorId();
                    $creditEntry->send_date = $request->payment_date;
                    $creditEntry->reference = 'Expense Payment';
                    $creditEntry->save();
                }

                //For Notification
                $setting  = Utility::settings(\Auth::user()->creatorId());

                if ($request->type == 'employee') {
                    $user = Employee::find($request->employee_id);
                    $contact =  $user->phone;
                } else if ($request->type  == 'customer') {
                    $user = Customer::find($request->customer_id);
                    $contact = $user->contact;
                } else {
                    $user = Vender::find($request->vender_id);
                    $contact = $user->contact;
                }



                $expenseNotificationArr = [
                    'expense_number' => \Auth::user()->expenseNumberFormat($expense->bill_id),
                    'user_name' => \Auth::user()->name,
                    'bill_date' => $expense->bill_date,
                    'bill_due_date' => $expense->due_date,
                    'vendor_name' => $user->name,
                ];


                //Slack Notification
                if (isset($setting['bill_notification']) && $setting['bill_notification'] == 1) {
                    Utility::send_slack_msg('new_bill', $expenseNotificationArr);
                }
                //Telegram Notification
                if (isset($setting['telegram_bill_notification']) && $setting['telegram_bill_notification'] == 1) {
                    Utility::send_telegram_msg('new_bill', $expenseNotificationArr);
                }
                //Twilio Notification
                if (isset($setting['twilio_bill_notification']) && $setting['twilio_bill_notification'] == 1) {
                    Utility::send_twilio_msg($contact, 'new_bill', $expenseNotificationArr);
                }


                //webhook
                $module = 'New Bill';
                $webhook =  Utility::webhookSetting($module);
                if ($webhook) {
                    $parameter = json_encode($expense);
                    $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);

                    if ($status == true) {
                        return redirect()->route('expense.index', $expense->id)->with('success', __('Expense successfully created.'));
                    } else {
                        return redirect()->back()->with('error', __('Webhook call failed.'));
                    }
                }

                DB::commit();
                return redirect()->route('expense.index', $expense->id)->with('success', __('Expense successfully created.'));
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show($ids)
    {

        if (\Auth::user()->can('show bill')) {
            try {
                $id       = Crypt::decrypt($ids);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Expense Not Found.'));
            }

            $id   = Crypt::decrypt($ids);

            $expense = Bill::with('debitNote', 'payments.bankAccount', 'items.product.unit')->find($id);

            if (!empty($expense) && $expense->created_by == \Auth::user()->creatorId()) {
                $expensePayment = BillPayment::where('bill_id', $expense->id)->first();
                $user = $expense->vender;

                // Get accounts for payment form
                $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))
                    ->where('created_by', \Auth::user()->creatorId())
                    ->get()->pluck('name', 'id');

                $item      = $expense->items;
                $accounts  = $expense->accounts;
                $items     = [];
                if (!empty($item) && count($item) > 0) {
                    foreach ($item as $k => $val) {
                        if (!empty($accounts[$k])) {
                            $val['chart_account_id'] = $accounts[$k]['chart_account_id'];
                            $val['account_id'] = $accounts[$k]['id'];
                            $val['amount'] = $accounts[$k]['price'];
                        }
                        $items[] = $val;
                    }
                } else {

                    foreach ($accounts as $k => $val) {
                        $val1['chart_account_id'] = $accounts[$k]['chart_account_id'];
                        $val1['account_id'] = $accounts[$k]['id'];
                        $val1['amount'] = $accounts[$k]['price'];
                        $items[] = $val1;
                    }
                }


                return view('expense.view', compact('expense', 'user', 'items', 'expensePayment', 'accounts'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function items(Request $request)
    {
        $items = BillProduct::where('bill_id', $request->bill_id)->where('product_id', $request->product_id)->first();
        return json_encode($items);
    }

    public function edit($ids)
    {
        if (\Auth::user()->can('edit bill')) {
            try {
                $id       = Crypt::decrypt($ids);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Expense Not Found.'));
            }

            $id       = Crypt::decrypt($ids);
            $expense     = Bill::find($id);

            $expensePayment = BillPayment::where('bill_id', $id)->first();
            $bankAccount = null;
            if ($expensePayment && $expensePayment->account_id) {
                $bankAccount = BankAccount::find($expensePayment->account_id);
            }

            if (!empty($expense)) {
                $category     = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())
                    ->whereNotIn('type', ['product & service', 'income',])
                    ->get()->pluck('name', 'id');
                $category->prepend('Select Category', '');
                $expense_number      = \Auth::user()->expenseNumberFormat($expense->bill_id);

                $venders          = Vender::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

                $product_services = ProductService::where('created_by', \Auth::user()->creatorId())
                    ->whereHas('subProducts')
                    ->with(['brand', 'subBrand', 'category', 'subProducts'])
                    ->get()
                    ->flatMap(function ($productService) {
                        $category = $productService->category->name ?? '';
                        $brand = $productService->brand->name ?? '';
                        $subBrand = $productService->subBrand->name ?? '';
                        $productName = $productService->name;

                        return $productService->subProducts->map(function ($subProduct) use ($category, $brand, $subBrand, $productName) {
                            return [
                                'id' => $subProduct->id,
                                'name' => $category . '/' . $brand . '/' . $subBrand . '/' . $productName . '/' . $subProduct->chassis_no,
                            ];
                        });
                    })
                    ->pluck('name', 'id');
                $product_services->prepend('Select Item', '');

                $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                    ->where('created_by', \Auth::user()->creatorId())->get()
                    ->pluck('code_name', 'id');
                $chartAccounts->prepend('Select Account', '');

                $bank_Account   = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))
                    ->where('created_by', \Auth::user()->creatorId())
                    ->get()->pluck('name', 'id');

                // Get expense items and accounts for foreach display
                $items = [];
                $item = $expense->items;
                $accounts = $expense->accounts;

                if (!empty($item) && count($item) > 0) {
                    foreach ($item as $k => $val) {
                        $itemData = [
                            'id' => $val->id,
                            'product_id' => $val->product_id,
                            'sub_product_id' => $val->sub_product_id,
                            'quantity' => $val->quantity,
                            'price' => $val->price,
                            'discount' => $val->discount,
                            'description' => $val->description,
                            'tax_id' => $val->tax,
                            'chart_account_id' => null,
                            'account_id' => null,
                            'amount' => null
                        ];

                        if (!empty($accounts[$k])) {
                            $itemData['chart_account_id'] = $accounts[$k]['chart_account_id'];
                            $itemData['account_id'] = $accounts[$k]['id'];
                            $itemData['amount'] = $accounts[$k]['price'];
                        }
                        $items[] = $itemData;
                    }
                } else {
                    foreach ($accounts as $k => $val) {
                        $itemData = [
                            'id' => null,
                            'product_id' => null,
                            'sub_product_id' => null,
                            'quantity' => null,
                            'price' => null,
                            'discount' => null,
                            'description' => null,
                            'tax_id' => null,
                            'chart_account_id' => $val['chart_account_id'],
                            'account_id' => $val['id'],
                            'amount' => $val['price']
                        ];
                        $items[] = $itemData;
                    }
                }

                $currency = Currency::get()->pluck('name', 'id');
                $currency->prepend('AED', '');
                $fullTax = Tax::where('created_by', '=', \Auth::user()->creatorId())->get();

                $currency_symbol = $expense->currency ? $expense->currency->symbol : \Auth::user()->currencySymbol();

                // Calculate totals using the same logic as create page
                $subTotal = 0;
                $totalTax = 0;
                $totalAmount = 0;

                foreach ($items as $item) {
                    $itemAmount = $item['amount'] ?? 0;
                    $subTotal += $itemAmount;

                    if ($item['tax_id']) {
                        $tax = Tax::find($item['tax_id']);
                        if ($tax) {
                            $taxAmount = ($itemAmount * $tax->rate) / 100;
                            $totalTax += $taxAmount;
                        }
                    }
                }

                $totalAmount = $subTotal + $totalTax;

                return view('expense.edit', compact(
                    'venders',
                    'product_services',
                    'expense',
                    'expense_number',
                    'category',
                    'bank_Account',
                    'chartAccounts',
                    'items',
                    'bankAccount',
                    'fullTax',
                    'currency',
                    'currency_symbol',
                    'subTotal',
                    'totalTax',
                    'totalAmount'
                ));
            } else {
                return redirect()->back()->with('error', __('Expense Not Found.'));
            }
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, $id)
    {
        if (\Auth::user()->can('edit bill')) {
            $expense = Bill::find($id);

            if ($expense->created_by == \Auth::user()->creatorId()) {

                $validator = \Validator::make(
                    $request->all(),
                    [
                        //                        'vender_id' => 'required',
                        'bill_date' => 'required',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->route('expense.index')->with('error', $messages->first());
                }
                $expense->vender_id      = $request->vender_id;
                $expense->user_type      = 'vendor';

                $expense->bill_date      = $request->bill_date;
                $expense->due_date       = $request->bill_date;
                $expense->order_number   = 0;
                $expense->category_id    = $request->category_id;
                $expense->currency_id = !empty($request->currency_id) ? $request->currency_id : null;
                $expense->exchange_rate = !empty($request->exchange_rate) ? $request->exchange_rate : 0;
                $expense->save();
                $products = $request->items;

                $total_amount = 0;
                $total_amount_tax = 0;
                $latestVoucher = GeneralLedger::where('ref_id', $expense->id)
                    ->where(function ($query) {
                        $query->where('type', 'LIKE', '%EXP%')
                            ->orwhere('type', 'LIKE', '%Expense Payment%');
                    })->where('created_by', \Auth::user()->creatorId())
                    ->first()->vid;
                GeneralLedger::where('ref_id', $expense->id)
                    ->where(function ($query) {
                        $query->where('type', 'LIKE', '%EXP%')
                            ->orwhere('type', 'LIKE', '%Expense Payment%');
                    })->where('created_by', \Auth::user()->creatorId())->delete();
                for ($i = 0; $i < count($products); $i++) {
                    $bill_price = $products[$i]['amount'];

                    if (!empty($request->currency_id)) {
                        $exchangeRate = $request->exchange_rate
                            ?? optional(Currency::find($request->currency_id))->exchange_rate;

                        if ($exchangeRate) {
                            $bill_price = $bill_price * $exchangeRate;
                        }
                    }
                    $expenseProduct = BillProduct::find($products[$i]['id']);
                    if ($expenseProduct == null) {
                        $expenseProduct             = new BillProduct();
                        $expenseProduct->bill_id    = $expense->id;
                    }

                    if (isset($products[$i]['items'])) {
                        $expenseProduct->product_id = $products[$i]['items'];
                        $expenseProduct->quantity    = $products[$i]['quantity'];
                        $expenseProduct->tax         = $products[$i]['tax'];
                        $expenseProduct->discount    = $products[$i]['discount'];
                        $expenseProduct->price       = $products[$i]['price'];
                        $expenseProduct->description = $products[$i]['description'];
                        $expenseProduct->save();
                    }


                    $expenseTotal = 0;
                    if (!empty($products[$i]['chart_account_id'])) {
                        $expenseAccount = BillAccount::find($products[$i]['account_id']);

                        if ($expenseAccount == null) {
                            $expenseAccount                    = new BillAccount();
                            $expenseAccount->chart_account_id = $products[$i]['chart_account_id'];
                        } else {
                            $expenseAccount->chart_account_id = $products[$i]['chart_account_id'];
                        }
                        $expenseAccount->price             = $bill_price;
                        $expenseAccount->description       = $products[$i]['description'];
                        $expenseAccount->type              = 'Expense';
                        $expenseAccount->ref_id            = $expense->id;
                        $expenseAccount->save();
                        $expenseTotal = $expenseAccount->price;
                    }

                    // if ($products[$i]['id'] > 0) {
                    //     Utility::total_quantity('plus', $products[$i]['quantity'], $expenseProduct->product_id);
                    // }

                    //Product Stock Report
                    $type = 'bill';
                    $type_id = $expense->id;
                    StockReport::where('type', '=', 'bill')->where('type_id', '=', $expense->id)->delete();
                    $description = $products[$i]['quantity'] . '  ' . __(' quantity purchase in bill') . ' ' . \Auth::user()->expenseNumberFormat($expense->bill_id);

                    if (isset($products[$i]['items'])) {
                        Utility::addProductStock($products[$i]['items'], $products[$i]['quantity'], $type, $description, $type_id);
                    }


                    if ($products[$i]['tax_id'] != null) {
                        $total_tax =  $bill_price * (Tax::where('id', $products[$i]['tax_id'])->first()->rate / 100);
                        $VatEntry = new GeneralLedger();
                        $VatEntry->vid = $latestVoucher;
                        $VatEntry->account = Tax::where('id', $products[$i]['tax_id'])->first()->chart_account_id;
                        $VatEntry->type = $expense->bill_id;
                        $VatEntry->ref_number = $expense->bill_id;
                        $VatEntry->debit = $total_tax;
                        $VatEntry->credit = 0;
                        $VatEntry->ref_id = $expense->id;
                        $VatEntry->user_id = 0;
                        $VatEntry->created_by = \Auth::user()->creatorId();
                        $VatEntry->send_date = $expense->bill_date;
                        $VatEntry->reference = 'Expense';
                        $VatEntry->save();
                    }
                    if ($products[$i]['chart_account_id'] != null) {
                        // Create a new entry for debit to  account
                        $AccountEntry = new GeneralLedger();
                        $AccountEntry->vid = $latestVoucher;
                        $AccountEntry->account = $products[$i]['chart_account_id'];
                        $AccountEntry->type = $expense->bill_id;
                        $AccountEntry->ref_number = $expense->bill_id;
                        $AccountEntry->debit = $bill_price;
                        $AccountEntry->credit = 0;
                        $AccountEntry->ref_id = $expense->id;
                        $AccountEntry->user_id = 0;
                        $AccountEntry->created_by = \Auth::user()->creatorId();
                        $AccountEntry->send_date = $expense->bill_date;
                        $AccountEntry->reference = 'Expense';
                        $AccountEntry->save();
                    }
                    $total_amount += $bill_price;
                    $total_amount_tax += $total_tax;
                }

                $vendorAccountId = Vender::where('id', $request->vender_id)->first()->chart_account_id;
                if (!$vendorAccountId) {
                    return redirect()->back()->with('error', __('Vendor account not found.'));
                }
                $expensePayment = BillPayment::where('bill_id', $expense->id)->first();
                $Payment = null;
                if ($expensePayment && $expensePayment->payment_id) {
                    $Payment = Payment::find($expensePayment->payment_id);
                }

                // Utility::bankAccountBalance($expensePayment->account_id, $expensePayment->amount, 'credit');
                // Utility::bankAccountBalance($request->account_id, $request->totalAmount, 'debit');
                Utility::updateUserBalance('vendor', $expense->vender_id, $request->totalAmount, 'credit');

                if ($expensePayment == null) {
                    $expensePayment = new BillPayment();
                    $expensePayment->bill_id = $expense->id;
                } else {
                    $expensePayment->bill_id = $expense->id;
                }

                $expensePayment->date           = $request->bill_date;
                $expensePayment->amount         = $total_amount;
                $expensePayment->account_id     = $request->account_id;
                $expensePayment->payment_method = 0;
                $expensePayment->reference      = 'NULL';
                $expensePayment->description    = 'NULL';
                $expensePayment->add_receipt    = 'NULL';
                $expensePayment->save();
                
                // Update or create Payment record
                if ($Payment == null) {
                    $Payment = new Payment();
                    $Payment->bill_id = $expense->id;
                    $Payment->payment_id = $expensePayment->id;
                    $Payment->vender_id = $expense->vender_id;
                    $Payment->category_id = $expense->category_id;
                    $Payment->payment_method = 0;
                    $Payment->status = 2;
                    $Payment->created_by = \Auth::user()->creatorId();
                    $Payment->payment_number = Payment::nextPaymentNumberFor($Payment->created_by);
                }
                $Payment->date = $request->bill_date;
                $Payment->amount = $total_amount;
                $Payment->account_id = $request->account_id;
                $Payment->save();
                
                if ($expensePayment && !$expensePayment->payment_id) {
                    $expensePayment->payment_id = $Payment->id;
                    $expensePayment->save();
                }

                if (!empty($request->chart_account_id)) {
                    $expenseaccount = ProductServiceCategory::find($request->category_id);
                    $chart_account = ChartOfAccount::find($expenseaccount->chart_account_id);
                    $expenseAccount                    = new BillAccount();
                    $expenseAccount->chart_account_id  = $chart_account['id'];
                    $expenseAccount->price             = $total_amount;
                    $expenseAccount->description       = $request->description;
                    $expenseAccount->type              = 'Bill Category';
                    $expenseAccount->ref_id            = $expense->id;
                    $expenseAccount->save();
                }


                $accountId = BankAccount::find($request->account_id);
                if (!$accountId || !$accountId->chart_account_id) {
                    return redirect()->back()->with('error', __('Bank account not found or chart account not configured.'));
                }
                
                // Create a new entry for debit to bank account
                $debitEntry = new GeneralLedger();
                $debitEntry->vid = $latestVoucher + 1;
                $debitEntry->account = $accountId->chart_account_id;
                $debitEntry->type =  'Expense Payment ' . $expense->bill_id;
                $debitEntry->ref_number =  'Expense Payment ' . $expense->bill_id;
                $debitEntry->debit = 0;
                $debitEntry->credit = $total_amount + $total_amount_tax;
                $debitEntry->ref_id = $expense->id;
                $debitEntry->user_id = 0;
                $debitEntry->payment_id = $Payment->id;
                $debitEntry->created_by = \Auth::user()->creatorId();
                $debitEntry->balance = 0;
                $debitEntry->send_date = $request->payment_date;
                $debitEntry->reference = 'Expense Payment';
                $debitEntry->save();


                // Create a new entry for credit to payment account
                $creditEntry = new GeneralLedger();
                $creditEntry->vid = $latestVoucher + 1;
                $creditEntry->account = $vendorAccountId;
                $creditEntry->type =  'Expense Payment ' . $expense->bill_id;
                $creditEntry->ref_number =  'Expense Payment ' . $expense->bill_id;
                $creditEntry->debit = $total_amount + $total_amount_tax;
                $creditEntry->credit = 0;
                $creditEntry->ref_id = $expense->id;
                $creditEntry->user_id = $expense->vender_id;
                $creditEntry->user_type = 'vendor';
                $creditEntry->payment_id = $Payment->id;
                $creditEntry->created_by = \Auth::user()->creatorId();
                $creditEntry->send_date = $request->payment_date;
                $creditEntry->reference = 'Expense Payment';
                $creditEntry->save();


                return redirect()->route('expense.index')->with('success', __('Expense successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function expense($expense_id)
    {

        $settings = Utility::settings();
        try {
            $expenseId       = Crypt::decrypt($expense_id);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Bill Not Found.'));
        }
        $expenseId   = Crypt::decrypt($expense_id);

        $expense  = Bill::where('id', $expenseId)->first();
        $data  = DB::table('settings');
        $data  = $data->where('created_by', '=', $expense->created_by);
        $data1 = $data->get();

        foreach ($data1 as $row) {
            $settings[$row->name] = $row->value;
        }

        $vendor = $expense->vender;

        $totalTaxPrice = 0;
        $totalQuantity = 0;
        $totalRate     = 0;
        $totalDiscount = 0;
        $taxesData     = [];
        $items         = [];

        foreach ($expense->items as $product) {

            $item              = new \stdClass();
            $item->name        = !empty($product->product()) ? $product->product()->name : '';
            $item->quantity    = $product->quantity;
            $item->tax         = $product->tax;
            $item->discount    = $product->discount;
            $item->price       = $product->price;
            $item->description = $product->description;

            $totalQuantity += $item->quantity;
            $totalRate     += $item->price;
            $totalDiscount += $item->discount;

            $taxes     = Utility::tax($product->tax);
            $itemTaxes = [];
            if (!empty($item->tax)) {
                foreach ($taxes as $tax) {
                    $taxPrice      = Utility::taxRate($tax->rate, $item->price, $item->quantity, $item->discount);
                    $totalTaxPrice += $taxPrice;

                    $itemTax['name']  = $tax->name;
                    $itemTax['rate']  = $tax->rate . '%';
                    $itemTax['price'] = Utility::priceFormat($settings, $taxPrice);
                    $itemTax['tax_price'] = $taxPrice;
                    $itemTaxes[]      = $itemTax;


                    if (array_key_exists($tax->name, $taxesData)) {
                        $taxesData[$tax->name] = $taxesData[$tax->name] + $taxPrice;
                    } else {
                        $taxesData[$tax->name] = $taxPrice;
                    }
                }

                $item->itemTax = $itemTaxes;
            } else {
                $item->itemTax = [];
            }
            $items[] = $item;
        }

        $expense->itemData      = $items;
        $expense->totalTaxPrice = $totalTaxPrice;
        $expense->totalQuantity = $totalQuantity;
        $expense->totalRate     = $totalRate;
        $expense->totalDiscount = $totalDiscount;
        $expense->taxesData     = $taxesData;
        $expense->customField   = CustomField::getData($expense, 'bill');

        $logo         = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $settings_data = \App\Models\Utility::settingsById($expense->created_by);
        $expense_logo = $settings_data['bill_logo'];
        if (isset($expense_logo) && !empty($expense_logo)) {
            $img = Utility::get_file('bill_logo/') . $expense_logo;
        } else {
            $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }

        if ($expense) {
            $color      = '#' . $settings['bill_color'];
            $font_color = Utility::getFontColor($color);

            $billTemplate = Utility::resolveBillTemplate($settings['bill_template'] ?? null);

            return view('bill.templates.' . $billTemplate, compact('expense', 'color', 'settings', 'vendor', 'img', 'font_color'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function productDestroy(Request $request)
    {

        if (\Auth::user()->can('delete bill product')) {
            $expenseProduct = BillProduct::find($request->id);
            $expense = Bill::find($expenseProduct->bill_id);

            Utility::updateUserBalance('vendor', $expense->vender_id, $request->amount, 'credit');

            BillProduct::where('id', '=', $request->id)->delete();
            BillAccount::where('id', '=', $request->account_id)->delete();

            return redirect()->back()->with('success', __('Expense product successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function addPayment(Request $request, $ids)
    {
        // if (\Auth::user()->can('create bill payment')) {
        try {
            $id = Crypt::decrypt($ids);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Expense Not Found.'));
        }

        $expense = Bill::find($id);
        if (!$expense || $expense->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = \Validator::make($request->all(), [
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01|max:' . $expense->getDue(),
            'account_id' => 'required|exists:bank_accounts,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            // Create BillPayment
            $expensePayment = new BillPayment();
            $expensePayment->bill_id = $expense->id;
            $expensePayment->date = $request->payment_date;
            $expensePayment->amount = $request->amount;
            $expensePayment->account_id = $request->account_id;
            $expensePayment->payment_method = 0;
            $expensePayment->reference = 'NULL';
            $expensePayment->description = $request->description ?? 'NULL';
            $expensePayment->add_receipt = 'NULL';
            $expensePayment->save();

            // Create Payment for vendor
            $accountId = BankAccount::find($request->account_id);
            if (!$accountId || !$accountId->chart_account_id) {
                DB::rollBack();
                return redirect()->back()->with('error', __('Bank account not found or chart account not configured.'));
            }
            
            $payment = new Payment();
            $payment->date = $request->payment_date;
            $payment->amount = $request->amount;
            $payment->account_id = $request->account_id;
            $payment->vender_id = $expense->vender_id;
            $payment->category_id = $expense->category_id;
            $payment->payment_method = 0;
            $payment->status = 2;
            $payment->bill_id = $expense->id;
            $payment->payment_id = $expensePayment->id;
            $payment->created_by = \Auth::user()->creatorId();
            $payment->payment_number = Payment::nextPaymentNumberFor($payment->created_by);
            $payment->save();
            $expensePayment->payment_id = $payment->id;
            $expensePayment->save();

            // Update user balance
            Utility::updateUserBalance('vendor', $expense->vender_id, $request->amount, 'credit');

            // Create General Ledger entries
            $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
            $newVoucherId = $latestVoucher ? $latestVoucher->vid + 1 : 1;

            $vendorAccountId = Vender::where('id', $expense->vender_id)->first()->chart_account_id;
            if (!$vendorAccountId) {
                DB::rollBack();
                return redirect()->back()->with('error', __('Vendor account not found.'));
            }

            // Debit bank account
            $debitEntry = new GeneralLedger();
            $debitEntry->vid = $newVoucherId;
            $debitEntry->account = $accountId->chart_account_id;
            $debitEntry->type = 'Expense Payment ' . $expense->bill_id;
            $debitEntry->ref_number = 'Expense Payment ' . $expense->bill_id;
            $debitEntry->debit = 0;
            $debitEntry->credit = $request->amount;
            $debitEntry->ref_id = $expense->id;
            $debitEntry->user_id = 0;
            $debitEntry->payment_id = $payment->id;
            $debitEntry->created_by = \Auth::user()->creatorId();
            $debitEntry->balance = 0;
            $debitEntry->send_date = $request->payment_date;
            $debitEntry->reference = 'Expense Payment';
            $debitEntry->save();

            // Credit vendor account
            $creditEntry = new GeneralLedger();
            $creditEntry->vid = $newVoucherId;
            $creditEntry->account = $vendorAccountId;
            $creditEntry->type = 'Expense Payment ' . $expense->bill_id;
            $creditEntry->ref_number = 'Expense Payment ' . $expense->bill_id;
            $creditEntry->debit = $request->amount;
            $creditEntry->credit = 0;
            $creditEntry->ref_id = $expense->id;
            $creditEntry->user_id = $expense->vender_id;
            $creditEntry->user_type = 'vendor';
            $creditEntry->payment_id = $payment->id;
            $creditEntry->created_by = \Auth::user()->creatorId();
            $creditEntry->send_date = $request->payment_date;
            $creditEntry->reference = 'Expense Payment';
            $creditEntry->save();

            DB::commit();
            return redirect()->back()->with('success', __('Payment added successfully.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
        // } else {
        //     return redirect()->back()->with('error', __('Permission denied.'));
        // }
    }

    public function destroy(Request $request, $expense_id)
    {
        if (\Auth::user()->can('delete bill')) {
            try {
                DB::beginTransaction();
                $expense = Bill::where('id', $expense_id)->first();
                $deleteDate = Carbon::parse($request->delete_date);
                $expenseDate = Carbon::parse($expense->bill_date);
                $expenseSendDate = Carbon::parse($expense->send_date);

                if ($expense->created_by == \Auth::user()->creatorId()) {
                    // if ($expenseSendDate && $expense->status === 4) {
                    //     if ($deleteDate->lt($expenseSendDate)) {
                    //         return redirect()->back()->with('error', 'Delete date must be greater than or equal to the send date.');
                    //     }
                    // } 
                    // elseif ($deleteDate->lt($expenseDate)) {
                    //     return redirect()->back()->with('error', 'Delete date must be greater than or equal to the expense date.');
                    // }
                    $expensepayment = BillPayment::where('bill_id', $expense->id)->first();
                    
                    // Find the actual payment record (Payment for vendor)
                    $paymentRecord = null;
                    if ($expensepayment && $expensepayment->payment_id) {
                        $paymentRecord = Payment::find($expensepayment->payment_id);
                    }

                    $vendorAccountId = null;
                    if ($expense->vender_id) {
                        $vendor = Vender::where('id', $expense->vender_id)->first();
                        $vendorAccountId = $vendor ? $vendor->chart_account_id : null;
                    }
                    
                    if (!$vendorAccountId) {
                        DB::rollBack();
                        return redirect()->back()->with('error', __('Vendor account not found.'));
                    }
                    $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
                    $newVoucherId = $latestVoucher ? $latestVoucher->vid + 1 : 1;

                    if (GeneralLedger::where('vid', $newVoucherId)->where('created_by', \Auth::user()->creatorId())->exists()) {
                        return redirect()->back()->with('error', __("Something went wrong, please try again."));
                    }
                    $totalAmount = 0;
                    // $bill_products = BillProduct::where('bill_id', $expense->id)->first();
                    // foreach ($bill_products as $bill_product) {
                    //     $product = ProductService::find($bill_product->product_id);
                    //     $total_tax = 0;
                    //     $totalAmountDebit = 0;
                    //     $subProduct = SubProduct::where('id', $bill_product->sub_product_id)->first();
                    //     $subProduct->flag = 2;
                    //     $subProduct->save();
                    // if ($bill_product->tax != null) {
                    //     $total_tax = $bill_product->price * (Tax::where('id', $bill_product->tax)->first()->rate  / 100);
                    //     $VatEntry = new GeneralLedger();
                    //     $VatEntry->vid = $newVoucherId;
                    //     $VatEntry->account = Tax::where('id', $bill_product->tax)->first()->chart_account_id;
                    //     $VatEntry->type = "Delete Expense " . \Auth::user()->expenseNumberFormat($expense->id);
                    //     $VatEntry->debit = 0;
                    //     $VatEntry->credit = $total_tax;
                    //     $VatEntry->ref_id = $expense->id;
                    //     $VatEntry->user_id = 0;
                    //     $VatEntry->created_by = \Auth::user()->creatorId();
                    //     $VatEntry->send_date = $dateToDelete;
                    //     $VatEntry->reference = 'Delete Expense';
                    //     $VatEntry->save();
                    // }
                    // Add to General Ledger
                    // Create a new entry for debit to Purchase account
                    // $purchaseEntry = new GeneralLedger();
                    // $purchaseEntry->vid = $newVoucherId;
                    // $purchaseEntry->account = ProductServiceCategory::where('id', $product->category_id)->first()->purchase_account_id;
                    // $purchaseEntry->type = "Delete Expense " . \Auth::user()->expenseNumberFormat($expense->id);
                    // $purchaseEntry->debit = 0;
                    // $purchaseEntry->credit = $bill_product->quantity * $bill_product->price;
                    // $purchaseEntry->ref_id = $expense->id;
                    // $purchaseEntry->user_id = 0;
                    // $purchaseEntry->created_by = \Auth::user()->creatorId();
                    // $purchaseEntry->send_date = $dateToDelete;
                    // $purchaseEntry->reference = 'Delete Expense';
                    // $purchaseEntry->save();
                    // $totalAmount += ($bill_product->quantity * $bill_product->price) + $total_tax;
                    // }

                    // Calculate total amount and tax first (same as store)
                    $total_amount = 0;
                    $bill_accounts = BillAccount::where('ref_id', '=', $expense->id)->get();
                    
                    // Check if bill_accounts exist
                    if ($bill_accounts->isEmpty()) {
                        // If no bill_accounts, try to get from expense directly or use 0
                        // This ensures we can still create vendor entry
                    } else {
                        foreach ($bill_accounts as $bill_acc) {
                            $total_amount += $bill_acc->price;
                        }
                    }
                    
                    // Calculate header-level tax (same as store)
                    $headerTaxRate = 0;
                    $headerTaxAmount = 0;
                    if ($expense->tax_id != null && $expense->tax_id != '') {
                        $taxIds = is_string($expense->tax_id) && strpos($expense->tax_id, ',') !== false 
                            ? explode(',', $expense->tax_id) 
                            : [$expense->tax_id];
                        
                        foreach ($taxIds as $tid) {
                            $t = Tax::find($tid);
                            if ($t) { 
                                $headerTaxRate += (float)$t->rate; 
                            }
                        }
                    }
                    $headerTaxAmount = $total_amount * ($headerTaxRate/100);
                    $grandTotal = $total_amount + $headerTaxAmount;

                    // Reverse: Account entries (debit -> credit)
                    if ($bill_accounts->isNotEmpty()) {
                        foreach ($bill_accounts as $bill_acc) {
                            // Store: debit = bill_price, credit = 0
                            // Destroy: debit = 0, credit = bill_price (REVERSED)
                            if ($bill_acc->chart_account_id) {
                                try {
                                    $AccountEntry = new GeneralLedger();
                                    $AccountEntry->vid = $newVoucherId;
                                    $AccountEntry->account = $bill_acc->chart_account_id;
                                    $AccountEntry->type = "Delete Expense " . $expense->bill_id;
                                    $AccountEntry->ref_number = "Delete Expense " . $expense->bill_id;
                                    $AccountEntry->debit = 0;
                                    $AccountEntry->credit = $bill_acc->price;
                                    $AccountEntry->ref_id = $expense->id;
                                    $AccountEntry->user_id = 0;
                                    $AccountEntry->created_by = \Auth::user()->creatorId();
                                    $AccountEntry->send_date = $deleteDate;
                                    $AccountEntry->reference = 'Delete Expense';
                                    $AccountEntry->save();
                                } catch (\Exception $e) {
                                    // Log error but continue with other entries
                                    \Log::error('Failed to save account entry: ' . $e->getMessage());
                                }
                            }
                        }
                    }
                    
                    // Reverse: VAT/Tax entries (if tax exists)
                    // Store: debit = headerTaxAmount, credit = 0 (to Tax account)
                    // Destroy: debit = 0, credit = headerTaxAmount (REVERSED)
                    if ($headerTaxAmount > 0 && $expense->tax_id != null && $expense->tax_id != '') {
                        $taxIds = is_string($expense->tax_id) && strpos($expense->tax_id, ',') !== false 
                            ? explode(',', $expense->tax_id) 
                            : [$expense->tax_id];
                        
                        foreach ($taxIds as $tid) {
                            $tax = Tax::find($tid);
                            if ($tax && $tax->chart_account_id) {
                                try {
                                    // Calculate tax amount for this specific tax rate
                                    $taxRate = (float)$tax->rate;
                                    $taxAmount = $total_amount * ($taxRate / 100);
                                    
                                    $VatEntry = new GeneralLedger();
                                    $VatEntry->vid = $newVoucherId;
                                    $VatEntry->account = $tax->chart_account_id;
                                    $VatEntry->type = "Delete Expense " . $expense->bill_id;
                                    $VatEntry->ref_number = "Delete Expense " . $expense->bill_id;
                                    $VatEntry->debit = 0;
                                    $VatEntry->credit = $taxAmount;
                                    $VatEntry->ref_id = $expense->id;
                                    $VatEntry->user_id = 0;
                                    $VatEntry->created_by = \Auth::user()->creatorId();
                                    $VatEntry->send_date = $deleteDate;
                                    $VatEntry->reference = 'Delete Expense';
                                    $VatEntry->save();
                                } catch (\Exception $e) {
                                    // Log error but continue with other entries
                                    \Log::error('Failed to save VAT entry: ' . $e->getMessage());
                                }
                            }
                        }
                    }
                    
                    // Reverse: Vendor entry (credit -> debit)
                    // Store: debit = 0, credit = grandTotal
                    // Destroy: debit = grandTotal, credit = 0 (REVERSED)
                    $vendorEntry = new GeneralLedger();
                    $vendorEntry->vid = $newVoucherId;
                    $vendorEntry->account = $vendorAccountId;
                    $vendorEntry->type = "Delete Expense " . $expense->bill_id;
                    $vendorEntry->ref_number = "Delete Expense " . $expense->bill_id;
                    $vendorEntry->debit = $grandTotal;
                    $vendorEntry->credit = 0;
                    $vendorEntry->ref_id = $expense->id;
                    $vendorEntry->user_id = $expense->vender_id;
                    $vendorEntry->user_type = $expense->user_type;
                    $vendorEntry->created_by = \Auth::user()->creatorId();
                    $vendorEntry->balance = 0;
                    $vendorEntry->send_date = $deleteDate;
                    $vendorEntry->reference = 'Delete Expense';
                    $vendorEntry->save();

                    // Reverse: Payment entries (if payment exists)
                    // Store creates two entries only if payment exists (!$request->filled('no_payment'))
                    // 1. Bank account: debit = 0, credit = grandTotal
                    // 2. Vendor account: debit = grandTotal, credit = 0
                    // Destroy reverses these entries
                    
                    if ($expensepayment && $expensepayment->account_id) {
                        $accountpayment = BankAccount::find($expensepayment->account_id);
                        
                        if ($accountpayment && $accountpayment->chart_account_id) {
                            // Get payment record ID - must match store method logic
                            // Store uses $payment->id where $payment is Payment
                            $paymentId = null;
                            if ($paymentRecord) {
                                $paymentId = $paymentRecord->id;
                            } elseif ($expensepayment->payment_id) {
                                $paymentId = $expensepayment->payment_id;
                            }
                            
                            // Only create entries if we have a valid paymentId
                            if ($paymentId) {
                                // Reverse: Bank account entry
                                // Store: debit = 0, credit = grandTotal
                                // Destroy: debit = grandTotal, credit = 0 (REVERSED)
                                $debitEntry = new GeneralLedger();
                                $debitEntry->vid = $newVoucherId + 1;
                                $debitEntry->account = $accountpayment->chart_account_id;
                                $debitEntry->type =  'Delete Expense Payment ' . $expense->bill_id;
                                $debitEntry->ref_number =  'Delete Expense Payment ' . $expense->bill_id;
                                $debitEntry->debit = $grandTotal;
                                $debitEntry->credit = 0;
                                $debitEntry->ref_id = $expense->id;
                                $debitEntry->user_id = 0;
                                $debitEntry->payment_id = $paymentId;
                                $debitEntry->created_by = \Auth::user()->creatorId();
                                $debitEntry->balance = 0;
                                $debitEntry->send_date = $deleteDate;
                                $debitEntry->reference = 'Delete Expense Payment';
                                $debitEntry->save();

                                // Reverse: Vendor entry for payment
                                // Store: debit = grandTotal, credit = 0
                                // Destroy: debit = 0, credit = grandTotal (REVERSED)
                                $creditEntry = new GeneralLedger();
                                $creditEntry->vid = $newVoucherId + 1;
                                $creditEntry->account = $vendorAccountId;
                                $creditEntry->type =  'Delete Expense Payment ' . $expense->bill_id;
                                $creditEntry->ref_number =  'Delete Expense Payment ' . $expense->bill_id;
                                $creditEntry->debit = 0;
                                $creditEntry->credit = $grandTotal;
                                $creditEntry->ref_id = $expense->id;
                                $creditEntry->user_id = $expense->vender_id;
                                $creditEntry->user_type = $expense->user_type;
                                $creditEntry->payment_id = $paymentId;
                                $creditEntry->created_by = \Auth::user()->creatorId();
                                $creditEntry->send_date = $deleteDate;
                                $creditEntry->reference = 'Delete Expense Payment';
                                $creditEntry->save();
                            }
                        }
                    }


                    $expense->delete();

                    if ($expense->vender_id != 0 && $expense->status != 0) {
                        Utility::updateUserBalance('vendor', $expense->vender_id, $expense->getDue(), 'credit');
                    }
                    BillProduct::where('bill_id', '=', $expense->id)->delete();
                    BillPayment::where('bill_id', '=', $expense->id)->delete();

                    BillAccount::where('ref_id', '=', $expense->id)->delete();

                    TransactionLines::where('reference_id', $expense->id)->where('reference', 'Expense Payment')->delete();
                    TransactionLines::where('reference_id', $expense->id)->where('reference', 'Expense')->delete();
                    TransactionLines::where('reference_id', $expense->id)->where('reference', 'Expense Account')->delete();
                    DB::commit();
                    return redirect()->route('expense.index')->with('success', __('Expense successfully deleted.'));
                } else {
                    return redirect()->back()->with('error', __('Permission denied.'));
                }
            } catch (\Exception $e) {
                DB::rollBack();
                // dd($e->getMessage());
                return redirect()->back()->with('error', $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function payment($id)
    {
        if (\Auth::user()->can('create expense')) {
            try {
                $decryptedId = \Crypt::decrypt($id);
                $expense = Bill::where('id', $decryptedId)->where('type', 'Expense')->first();

                if (!$expense) {
                    if (request()->ajax()) {
                        return response()->json(['error' => __('Expense not found. ID: ' . $decryptedId)], 404);
                    }
                    return redirect()->back()->with('error', __('Expense not found.'));
                }

                $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

                if (request()->ajax()) {
                    return view('expense.payment', compact('expense', 'accounts'));
                }

                return view('expense.payment', compact('expense', 'accounts'));
            } catch (\Exception $e) {
                if (request()->ajax()) {
                    return response()->json(['error' => __('Invalid expense ID: ' . $e->getMessage())], 400);
                }
                return redirect()->back()->with('error', __('Invalid expense ID.'));
            }
        } else {
            if (request()->ajax()) {
                return response()->json(['error' => __('Permission denied.')], 403);
            }
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
