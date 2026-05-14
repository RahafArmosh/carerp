<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomerRefund;
use App\Models\BankAccount;
use App\Models\InvoiceAccount;
use App\Models\InvoicePayment;
use App\Models\ChartOfAccount;
use App\Models\ProductServiceCategory;
use App\Models\Transaction;
use App\Models\TransactionLines;
use App\Models\Utility;
use App\Models\Customer;
use App\Models\GeneralLedger;
use App\Models\Invoice;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class CustomerRefundController extends Controller
{
    public function index(Request $request)
    {
        if (\Auth::user()->can('manage customer payment')) {
            $customer = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customer->prepend('Select Customer', '');

            $account = BankAccount::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('holder_name', 'id');
            $account->prepend('Select Account', '');

            $category = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->where('type', '=', 'expense')->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');

            $query = CustomerRefund::where('created_by', '=', \Auth::user()->creatorId());

            if (count(explode('to', $request->date)) > 1) {
                $date_range = explode(' to ', $request->date);
                $query->whereBetween('date', $date_range);
            } elseif (!empty($request->date)) {
                $date_range = [$request->date, $request->date];
                $query->whereBetween('date', $date_range);
            }

            if (!empty($request->vender)) {
                $query->where('id', '=', $request->vender);
            }
            if (!empty($request->account)) {
                $query->where('account_id', '=', $request->account);
            }

            if (!empty($request->category)) {
                $query->where('category_id', '=', $request->category);
            }

            $payments = $query->with(['bankAccount','customer','category'])->get();

            return view('customerrefund.index', compact('payments', 'account', 'category', 'customer'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create customer refund')) {
            $customers = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customers->prepend('--', 0);

//            $categories = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->where('type', '=', 2)->get()->pluck('name', 'id');
            $categories = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())
                ->whereNotIn('type', ['product & service', 'income'])
                ->get()->pluck('name', 'id');
            $categories->prepend('Select Category', '');

            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');

            $currencies = Currency::get()->mapWithKeys(function($currency) {
                return [$currency->id => ['name' => $currency->name, 'rate' => $currency->exchange_rate ?? 1]];
            })->toArray();
            
            return view('customerrefund.create', compact('customers', 'categories', 'accounts', 'chartAccounts', 'currencies'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create customer refund')) {
            try {
                DB::beginTransaction();
            $validator = \Validator::make(
                $request->all(), [
                    'date' => 'required',
                    'amount' => 'required',
                    'account_id' => 'required',
                    'category_id' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            // Compute AED amount using provided currency rate; prefer amount_in_currency if provided
            $effectiveRate = (float)($request->currency_rate ?? 1);
            $inputAmountInCurrency = $request->filled('amount_in_currency') ? (float)$request->amount_in_currency : null;
            $amountAED = $inputAmountInCurrency !== null
                ? round($inputAmountInCurrency * ($effectiveRate > 0 ? $effectiveRate : 1), 2)
                : (float)$request->amount;
            $amountInCurrency = $inputAmountInCurrency !== null
                ? $inputAmountInCurrency
                : ($effectiveRate > 0 ? round(((float)$amountAED) / $effectiveRate, 2) : (float)$amountAED);

            $customer = Customer::where('id', $request->customer_id)->first();
            $customer->total_paid = $customer->total_paid - $amountAED;
            $customer->save();
            $payment = new CustomerRefund();
            $payment->date = $request->date;
            $payment->amount = $amountAED; // store in AED
            $payment->account_id = $request->account_id;
            $payment->currency_id = $request->currency_id;
            $payment->currency_rate = $effectiveRate;
            $payment->amount_in_currency = $amountInCurrency;
//            $payment->chart_account_id  = $request->chart_account_id;
            $payment->customer_id = $request->customer_id;
            $payment->category_id = $request->category_id;
            $payment->payment_method = 0;
            $payment->reference = $request->reference;
            if (!empty($request->invoice_id)) {
                $payment->invoice_id = $request->invoice_id;
                $invoice = Invoice::where('id', $request->invoice_id)->first();

                // Compute refundable balance = total paid - already refunded
                $totalPaid = InvoicePayment::where('invoice_id', $payment->invoice_id)->sum('amount');
                $alreadyRefunded = CustomerRefund::where('invoice_id', $payment->invoice_id)->sum('amount');
                $refundable = max(0, $totalPaid - $alreadyRefunded);

                if ($amountAED > $refundable) {
                    return redirect()->back()->with('error', 'Refund amount cannot exceed total paid amount');
                }

                // Distribute the refund across existing payments (latest first)
                $remaining = $amountAED;
                $firstPaymentId = null;
                $paymentsToAdjust = InvoicePayment::where('invoice_id', $payment->invoice_id)
                    ->where('amount', '>', 0)
                    ->orderBy('id', 'desc')
                    ->get();

                foreach ($paymentsToAdjust as $invPay) {
                    if ($remaining <= 0) break;
                    $deduct = min($invPay->amount, $remaining);
                    $invPay->amount = $invPay->amount - $deduct;
                    $invPay->save();
                    if ($firstPaymentId === null) {
                        $firstPaymentId = $invPay->id;
                    }
                    $remaining -= $deduct;
                }

                if ($remaining > 0) {
                    return redirect()->back()->with('error', 'Unable to allocate refund against payments');
                }

                $payment->payment_id = $firstPaymentId;
            }
            if (!empty($request->add_receipt)) {
                $document = $request->file('add_receipt');
                $fileName = time() . "_" . preg_replace('/[^A-Za-z0-9\-]/', '', $request->add_receipt->getClientOriginalName());
                $payment->add_receipt = $fileName;
                $dir = 'uploads/payment';
                $document->move(public_path('uploads/customer_payment'), $fileName);
            }
            $payment->description = $request->description;
            $payment->created_by = \Auth::user()->creatorId();
            $payment->save();
            $chartAccountId = ChartOfAccount::find($request->account_id);
            $accountId = BankAccount::find($payment->account_id);


            // $account = new InvoiceAccount();
            // $account->chart_account_id = $chartAccountId;
            // $account->price = $request->amount;
            // $account->description = $request->description;
            // $account->type = 'Payment';
            // $account->ref_id = $payment->id;
            // $account->save();

            $category = ProductServiceCategory::where('id', $request->category_id)->first();
            $payment->payment_id = $payment->id;
            $payment->type = 'Customer Refund Payment';
            $payment->category = $category->name;
            $payment->user_id = $payment->customer_id;
            $payment->user_type = 'Customer';
            $payment->account = $request->account_id;

            // Transaction::addTransaction($payment);


            if (!empty($customer)) {
                // Update customer balance using AED amount
                Utility::updateUserBalance('customer', $customer->id, $amountAED, 'debit');
            }

            // Utility::bankAccountBalance($request->account_id, $amountAED, 'credit');

            //For Notification
            $setting = Utility::settings(\Auth::user()->creatorId());

            //Twilio Notification
            if (isset($setting['twilio_payment_notification']) && $setting['twilio_payment_notification'] == 1) {

                $customer = Customer::find($request->vender_id);
                $paymentNotificationArr = [
                    'payment_amount' => \Auth::user()->priceFormat($amountAED),
                    'customer_name' => $customer->name,
                    'payment_type' => 'Payment',
                ];
                Utility::send_twilio_msg($request->contact, 'invoice_payment', $paymentNotificationArr);
            }


                // Add to General Ledger
                // Get the latest 'vid' entry, if any exist
                $lastPayment = CustomerRefund::latest('id')->first();
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


                // Create a new entry for debit to vendor account
                $debitEntry = new GeneralLedger();
                $debitEntry->vid = $newVoucherId;
                $debitEntry->account = $customer->chart_account_id;
                $debitEntry->type = 'Customer Refund Payment ' ;
                $debitEntry->debit =  $amountAED;
                $debitEntry->credit =0;
                $debitEntry->ref_id =  $request->filled('invoice_id') ? $request->invoice_id : -1;
                $debitEntry->user_id = $customer->id;
                $debitEntry->payment_id = $lastPayment->id;
                $debitEntry->created_by = \Auth::user()->creatorId();
                $debitEntry->balance = $customer->balance;
                $debitEntry->send_date = $lastPayment->date;
			    $debitEntry->reference = 'Customer Refund';
                $debitEntry->save();


                // Create a new entry for credit to payment account
                $creditEntry = new GeneralLedger();
                $creditEntry->vid = $newVoucherId;
                $creditEntry->account = $accountId->chart_account_id;
                $creditEntry->type = 'Customer Refund Payment  ' ;
                $creditEntry->debit = 0;
                $creditEntry->credit = $amountAED;
                $creditEntry->ref_id =  $request->filled('invoice_id') ? $request->invoice_id : -1;
                $creditEntry->user_id = 0;
                $creditEntry->payment_id = $lastPayment->id;
                $creditEntry->created_by = \Auth::user()->creatorId();
                $creditEntry->send_date = $lastPayment->date;
			    $creditEntry->reference = 'Customer Refund';
                $creditEntry->save();
                DB::commit();
            return redirect()->route('customerrefund.index')->with('success', __('Refund successfully created') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : ''));
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit($payment)
    {
        $payment = CustomerRefund::find($payment);
        if (\Auth::user()->can('edit customer refund')) {
            $customers = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customers->prepend('--', 0);

            $categories = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())
                ->get()->pluck('name', 'id');
            $categories->prepend('Select Category', '');

            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');

            $currencies = Currency::where('created_by', \Auth::user()->creatorId())->get()->mapWithKeys(function($currency) {
                return [$currency->id => ['name' => $currency->name, 'rate' => $currency->exchange_rate ?? 1]];
            })->toArray();

            $invoices=  Invoice::where('customer_id',$payment->customer_id)->where("status","!=","0")->get();
            return view('customerrefund.edit', compact('customers', 'categories', 'accounts', 'payment', 'chartAccounts','invoices', 'currencies'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, $payment)
    {
        $payment = CustomerRefund::find($payment);
        if (\Auth::user()->can('edit customer refund')) {
            try {
                DB::beginTransaction();
            $validator = \Validator::make(
                $request->all(), [
                    'date' => 'required',
                    'amount' => 'required',
                    'account_id' => 'required',
                    // 'vender_id' => 'required',
                    'category_id' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            $customer = customer::where('id', $request->old_customer_id)->first();
            if (!empty($customer)) {
                // Utility::userBalance('vendor', $vender->id, $payment->amount, 'credit');
                Utility::updateUserBalance('customer', $customer->id, $payment->amount, 'credit');
                $customer->total_paid = $customer->total_paid + $payment->amount ;
            }
            // Utility::bankAccountBalance($payment->account_id, $payment->amount, 'debit');

            // Recompute AED and currency amounts from form data
            $effectiveRate = (float)($request->currency_rate ?? 1);
            $inputAmountInCurrency = $request->filled('amount_in_currency') ? (float)$request->amount_in_currency : null;
            $amountAED = $inputAmountInCurrency !== null
                ? round($inputAmountInCurrency * ($effectiveRate > 0 ? $effectiveRate : 1), 2)
                : (float)$request->amount;
            $amountInCurrency = $inputAmountInCurrency !== null
                ? $inputAmountInCurrency
                : ($effectiveRate > 0 ? round(((float)$amountAED) / $effectiveRate, 2) : (float)$amountAED);

            $payment->date = $request->date;
            $payment->amount = $amountAED; // store in AED
            $payment->account_id = $request->account_id;
            $payment->currency_id = $request->currency_id;
            $payment->currency_rate = $effectiveRate;
            $payment->amount_in_currency = $amountInCurrency;
//            $payment->chart_account_id  = $request->chart_account_id;
            $payment->customer_id = $request->old_customer_id;
            $payment->category_id = $request->category_id;
            $payment->payment_method = 0;
            $payment->reference = $request->reference;
            if (!empty($request->invoice_id)) {
                if($payment->invoice_id != null){
                     $payment->invoice_id = $request->invoice_id;
                     $invoice_payment = InvoicePayment::find($payment->payment_id);
                     $invoice_payment->amount = ($invoice_payment->amount + $payment->amount) - $amountAED;
                     $payment->save();
                     $invoice_payment->save();
                }
                else{
                    $payment->invoice_id = $request->invoice_id;
                    $getInvoicePayment = InvoicePayment::where('invoice_id',$request->invoice_id)->where('amount','>',$amountAED)->first();
                    $getInvoicePayment->amount = $getInvoicePayment->amount - $amountAED;
                    $getInvoicePayment->save();
                    $payment->payment_id =$getInvoicePayment->id;
                    $payment->save();
                }
            }

            if (!empty($request->add_receipt)) {
                $document = $request->file('add_receipt');
                $fileName = time() . "_" . preg_replace('/[^A-Za-z0-9\-]/', '', $request->add_receipt->getClientOriginalName());
                $payment->add_receipt = $fileName;
                $dir = 'uploads/payment';
                $document->move(public_path('uploads/customer_payment'), $fileName);
            }

            $payment->description = $request->description;
            $payment->save();
            TransactionLines::where('reference_id', $payment->id)->where('reference', 'Customer Refund Payment')->delete();
            $accountId = BankAccount::find($payment->account_id);
            $category = ProductServiceCategory::where('id', $request->category_id)->first();
            $latestVoucher = GeneralLedger::where('payment_id',$payment->id)->where('type','Customer Refund Payment')->get();
            foreach ($latestVoucher as $voucher) {
                // Assuming you want to update both debit and credit to the new amount
                    if ($voucher->debit != 0) {
                        $voucher->debit = $amountAED;
                    }

                    if ($voucher->credit != 0) {
                        $voucher->credit = $amountAED;
                        $voucher->account = $accountId->chart_account_id;
                    }
                    $voucher->send_date = $request->date;
                    $voucher->save();
            }


            if (!empty($customer)) {
                // Update using AED amount
                Utility::updateUserBalance('customer', $customer->id, $amountAED, 'debit');
                $customer->total_paid = $customer->total_paid - $amountAED ;
            }

            // Utility::bankAccountBalance($request->account_id, $amountAED, 'credit');
            DB::commit();
            return redirect()->route('customerrefund.index')->with('success', __('Refund Updated Successfully') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : ''));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy($payment)
    {
        $payment = CustomerRefund::find($payment);
        if (\Auth::user()->can('delete customer refund')) {
            if ($payment->created_by == \Auth::user()->creatorId()) {
                try {
                    DB::beginTransaction();
                if (!empty($payment->add_receipt)) {
                    //storage limit
                    $file_path = '/uploads/refund/' . $payment->add_receipt;
                    $result = Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);

                }

                TransactionLines::where('reference_id', $payment->id)->where('reference', 'Refund')->delete();

                $payment->delete();
                $type = 'Customer Refund Payment';
                $user = 'Customer';
                Transaction::destroyTransaction($payment->id, $type, $user);
                if($payment->invoice_id !== null){
                    $getInvoicePayment  = InvoicePayment::where('id', '=', $payment->payment_id)->first();
                    $getInvoicePayment->amount = $getInvoicePayment->amount + $payment->amount ;
                    $getInvoicePayment->save();

                }

                if ($payment->customer_id != 0) {
                    // Utility::userBalance('vendor', $payment->vender_id, $payment->amount, 'credit');
                    Utility::updateUserBalance('customer', $payment->customer_id, $payment->amount, 'credit');
                }
                // Utility::bankAccountBalance($payment->account_id, $payment->amount, 'debit');

                 // Add to General Ledger
                // Get the latest 'vid' entry, if any exist
                $customer = Customer::where('id', $payment->customer_id)->first();
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
                $accountId = BankAccount::find($payment->account_id);

                // Create a new entry for debit to payment account
                $debitEntry = new GeneralLedger();
                $debitEntry->vid = $newVoucherId;
                $debitEntry->account = $accountId->chart_account_id ;
                $debitEntry->type =   $payment->invoice_id !== null ?  'Invoice Delete Customer Refund Payment ' .\Auth::user()->invoiceNumberFormat($payment->invoice_id) : 'Delete Customer Refund Payment';
                $debitEntry->debit = $payment->amount; // Example value
                $debitEntry->credit = 0; // Example value
                $debitEntry->ref_id =  $payment->invoice_id !== null ? $payment->invoice_id : -1;
                $debitEntry->user_id = 0;
                $debitEntry->created_by = \Auth::user()->creatorId();
                $debitEntry->send_date = now();
                $debitEntry->reference = 'Delete Customer Refund';
                $debitEntry->save();


                // Create a new entry for credit to vendor account
                $creditEntry = new GeneralLedger();
                $creditEntry->vid = $newVoucherId;
                $creditEntry->account = $customer->chart_account_id;
                $creditEntry->type = $payment->invoice_id !== null ?  'Invoice Delete Customer Refund Payment ' .\Auth::user()->invoiceNumberFormat($payment->invoice_id) : 'Delete Customer Refund Payment';
                $creditEntry->debit = 0; // Example value
                $creditEntry->credit = $payment->amount; // Example value
                $creditEntry->ref_id =  $payment->invoice_id !== null ? $payment->invoice_id : -1;
                $creditEntry->user_id = $payment->customer_id;
                $creditEntry->created_by = \Auth::user()->creatorId();
                $creditEntry->balance = $customer->balance;
                $creditEntry->send_date = now();
                $creditEntry->reference = 'Delete Customer Refund';
                $creditEntry->save();
                DB::commit();
                return redirect()->route('customerrefund.index')->with('success', __('Refund successfully deleted.'));
                } catch (\Exception $e) {
                    DB::rollBack();
                    return redirect()->back()->with('error', $e->getMessage());
                }
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function printrefundPayment($paymentId)
    {
        $payment = CustomerRefund::with([
            'customer',
            'bankAccount',
            'category',
            'currency',
            'invoice.currency',
        ])->findOrFail($paymentId);

        $settings = Utility::settings();
        $settings_data = Utility::settingsById($payment->created_by);

        $companyLogoFile = null;
        if (($settings['cust_darklayout'] ?? '') === 'on') {
            $companyLogoFile = ! empty($settings['company_logo_light'])
                ? $settings['company_logo_light']
                : ($settings['company_logo_dark'] ?? null);
        } else {
            $companyLogoFile = ! empty($settings['company_logo_dark'])
                ? $settings['company_logo_dark']
                : ($settings['company_logo_light'] ?? null);
        }

        $base = rtrim(URL::to('/'), '/');
        $company_logo_url = null;
        if (! empty($companyLogoFile)) {
            $company_logo_url = $base.'/documents/'.$companyLogoFile;
        }
        if (empty($company_logo_url) && ! empty($settings['company_logo_dark'])) {
            $company_logo_url = asset(Storage::url('uploads/logo/'.$settings['company_logo_dark']));
        }
        if (empty($company_logo_url)) {
            $company_logo_url = asset(Storage::url('uploads/logo/'.($settings['company_logo_dark'] ?? 'logo-dark.png')));
        }

        $company_stamp_url = ! empty($settings_data['company_stamp'])
            ? $base.'/documents/'.$settings_data['company_stamp']
            : asset('storage/uploads/logo/stamp-preview.png');

        $color = '#'.($settings['bill_color'] ?? 'ffffff');
        $font_color = Utility::getFontColor($color);

        return view('customerrefund.print', compact(
            'payment',
            'font_color',
            'color',
            'settings',
            'settings_data',
            'company_logo_url',
            'company_stamp_url'
        ));
    }

    public function getCurrencyRate($currencyId, $invoiceId)
    {
        try {
            $currency = Currency::find($currencyId);
            $invoice = Invoice::find($invoiceId);
            
            if (!$currency || !$invoice) {
                return response()->json(['error' => 'Currency or Invoice not found'], 404);
            }
            
            // Rate should always be between selected currency and AED
            $rate = $currency->exchange_rate ?? 1;
            
            // Get invoice currency for amount calculation
            $invoiceCurrency = $invoice->currency_id ? Currency::find($invoice->currency_id) : null;
            
            $amountRate = 1; // Default for amount calculation
            if ($invoiceCurrency) {
                // Calculate rate between selected currency and invoice currency for amount conversion
                $selectedCurrencyRate = $currency->exchange_rate ?? 1;
                $invoiceCurrencyRate = $invoiceCurrency->exchange_rate ?? 1;
                
                // Amount rate = selected_currency_rate / invoice_currency_rate
                $amountRate = $selectedCurrencyRate / $invoiceCurrencyRate;
            }
            
            return response()->json([
                'rate' => round($rate, 4), // Rate between selected currency and AED
                'amount_rate' => round($amountRate, 4) // Rate for amount conversion
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error calculating exchange rate'], 500);
        }
    }
}
