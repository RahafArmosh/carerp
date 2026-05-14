<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\InvoiceAccount;
use App\Models\InvoicePayment;
use App\Models\ChartOfAccount;
use App\Models\CustomerPayment;
use App\Models\ProductServiceCategory;
use App\Models\Transaction;
use App\Models\TransactionLines;
use App\Models\Utility;
use App\Models\Customer;
use App\Models\GeneralLedger;
use App\Models\Invoice;
use App\Models\Currency;
use App\Models\InvoiceStatusChange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use PDF;
use \Carbon\Carbon;

class CustomerPaymentController extends Controller
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
            $status = array_filter(CustomerPayment::$statues);
            $query = CustomerPayment::where('created_by', '=', \Auth::user()->creatorId());

            // Fix date filter - handle date range properly
            if (!empty($request->date)) {
                if (strpos($request->date, ' to ') !== false) {
                    $date_range = explode(' to ', $request->date);
                    if (count($date_range) == 2) {
                        $query->whereBetween('date', $date_range);
                    }
                } else {
                    $query->whereDate('date', $request->date);
                }
            }

            // Fix customer filter - was using 'vender' which doesn't exist
            if (!empty($request->customer)) {
                $query->where('customer_id', '=', $request->customer);
            }

            if (!empty($request->account)) {
                $query->where('account_id', '=', $request->account);
            }

            if (!empty($request->category)) {
                $query->where('category_id', '=', $request->category);
            }

            // Eager load all relationships to prevent N+1 queries
            // Also eager load invoice with currency to avoid querying in view
            $payments = $query->with([
                'bankAccount',
                'customer',
                'category',
                'currency',
                'invoicePayments', // For sum calculations
                'invoices', // For invoice listing
                'invoice.currency' // For currency symbol lookup
            ])
                ->orderBy('date', 'ASC')
                ->orderBy('id', 'ASC')
                ->paginate(100); // Paginate instead of loading all 700 rows

            return view('customerpayment.index', compact('payments', 'account', 'category', 'customer', 'status'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create customer payment')) {
            $customers = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customers->prepend('--', 0);

            $categories = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())
                ->whereNotIn('type', ['product & service', 'income'])
                ->get()->pluck('name', 'id');
            $categories->prepend('Select Category', '');

            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');
            $currencies = Currency::get()->pluck('name', 'id');
            $currencies->prepend('AED', '');
            return view('customerpayment.create', compact('customers', 'categories', 'accounts', 'chartAccounts', 'currencies'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create customer payment')) {
            try {
                DB::beginTransaction();
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'date' => 'required',
                        'amount' => 'required',
                        'account_id' => 'required',
                        'category_id' => 'required',
                        'customer_id' => 'required',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }
                $customer = Customer::where('id', $request->customer_id)->first();
                $payment = new CustomerPayment();
                $payment->date = $request->date;
                $payment->amount = !empty($request->currency_id) ? ($request->amount + $request->charge) * $request->currency_rate : $request->amount + $request->charge;
                $payment->account_id = $request->account_id;
                $payment->currency_id = $request->currency_id;
                $payment->currency_rate = $request->currency_rate;
                $payment->chart_account_id  = $request->account_id;
                $payment->customer_id = $request->customer_id;
                $payment->category_id = $request->category_id;
                $payment->payment_method = 0;
                $payment->status = 0;
                $payment->reference = $request->reference;
                $payment->charge = !empty($request->currency_id) ? $request->charge * $request->currency_rate : $request->charge;
                $payment->bank_charge_account_id = $request->bank_charge_account_id;
                if (!empty($request->add_receipt)) {

                    $document = $request->file('add_receipt');
                    $fileName = time() . "_" . preg_replace('/[^A-Za-z0-9\-]/', '', $request->add_receipt->getClientOriginalName());
                    $payment->add_receipt = $fileName;
                    $dir = 'uploads/payment';
                    $document->move(public_path('uploads/customer_payment'), $fileName);
                }
                $payment->description = $request->description;
                $payment->created_by = \Auth::user()->creatorId();
                $payment->payment_number = CustomerPayment::nextPaymentNumberFor($payment->created_by);
                $payment->save();

                //For Notification
                $setting = Utility::settings(\Auth::user()->creatorId());

                //Twilio Notification
                if (isset($setting['twilio_payment_notification']) && $setting['twilio_payment_notification'] == 1) {

                    $customer = Customer::find($request->vender_id);
                    $paymentNotificationArr = [
                        'payment_amount' => \Auth::user()->priceFormat($request->amount),
                        'customer_name' => $customer->name,
                        'payment_type' => 'Payment',
                    ];
                    Utility::send_twilio_msg($request->contact, 'invoice_payment', $paymentNotificationArr);
                }
                DB::commit();
                return redirect()->route('customerpayment.index')->with('success', __('Payment successfully created') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : ''));
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
        $payment = CustomerPayment::find($payment);
        if (\Auth::user()->can('edit payment')) {
            $customers = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customers->prepend('--', 0);

            $categories = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())
                ->whereNotIn('type', ['product & service', 'income'])
                ->get()->pluck('name', 'id');
            $categories->prepend('Select Category', '');

            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');
            $currencies = Currency::get()->pluck('name', 'id');
            $currencies->prepend('AED', '');
            $invoices =  Invoice::where('customer_id', $payment->customer_id)->where("payment_status", "!=", "4")->get();
            
            // Check if payment is linked to any invoices
            $hasLinkedInvoices = $payment->invoicePayments()->exists();
            
            return view('customerpayment.edit', compact('customers', 'categories', 'accounts', 'payment', 'chartAccounts', 'invoices', 'currencies', 'hasLinkedInvoices'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, $payment)
    {
        $payment = CustomerPayment::find($payment);
        $old_amount = $payment->amount - $payment->charge;
        $old_charge = $payment->charge;
        if (\Auth::user()->can('edit payment')) {
            try {
                DB::beginTransaction();
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'date' => 'required',
                        'amount' => 'required',
                        'account_id' => 'required',
                        'customer_id' => 'required',
                        'category_id' => 'required',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }
                // Check if payment is linked to any invoices - prevent customer change if linked
                $hasLinkedInvoices = $payment->invoicePayments()->exists();
                if ($hasLinkedInvoices) {
                    // If linked to invoices, force use of original customer
                    $customerId = $payment->customer_id;
                } else {
                    // Get the customer ID from request (use customer_id if provided, otherwise fall back to old_customer_id)
                    $customerId = $request->customer_id ?? $request->old_customer_id;
                }
                
                $oldCustomerId = $payment->customer_id;
                
                // Get customer object
                $customer = Customer::where('id', $customerId)->first();
                if (!$customer) {
                    return redirect()->back()->with('error', __('Customer not found'));
                }
                
                $payment->date = $request->date;
                $payment->amount = $request->currency_id ? ($request->amount + $request->charge) * $request->currency_rate : $request->amount + $request->charge;
                $payment->currency_id = $request->currency_id;
                $payment->currency_rate = $request->currency_rate;
                $payment->account_id = $request->account_id;
                $payment->chart_account_id  = $request->chart_account_id;
                $payment->customer_id = $customerId;
                $payment->category_id = $request->category_id;
                $payment->payment_method = 0;
                $payment->reference = $request->reference;
                $payment->charge = $request->currency_id ? $request->charge * $request->currency_rate : $request->charge;
                $payment->bank_charge_account_id = $request->bank_charge_account_id;

                if (!empty($request->add_receipt)) {
                    $document = $request->file('add_receipt');
                    $fileName = time() . "_" . preg_replace('/[^A-Za-z0-9\-]/', '', $request->add_receipt->getClientOriginalName());
                    $payment->add_receipt = $fileName;
                    $dir = 'uploads/payment';
                    $document->move(public_path('uploads/customer_payment'), $fileName);
                }

                $payment->description = $request->description;
                $payment->save();
                if ($payment->status === 2) {
                    TransactionLines::where('reference_id', $payment->id)->where('reference', 'Payment')->delete();
                    $accountId = BankAccount::find($payment->account_id);
                    $category = ProductServiceCategory::where('id', $request->category_id)->first();
                    $latestVoucher = GeneralLedger::where('payment_id', $payment->id)
                        ->where(function ($query) {
                            $query->where('reference', 'Like', 'Invoice Payment')
                                ->orWhere('reference', 'Like', 'Customer Payment');
                        })
                        ->where('created_by', \Auth::user()->creatorId())
                        ->first()->vid;

                    GeneralLedger::where('payment_id', $payment->id)
                        ->where(function ($query) {
                            $query->where('reference', 'Like', 'Invoice Payment')
                                ->orWhere('reference', 'Like', 'Customer Payment');
                        })
                        ->where('created_by', \Auth::user()->creatorId())
                        ->delete();


                    $accountId = BankAccount::find($payment->account_id);
                    // Create a new entry for debit to customer account (using the updated customer)
                    $debitEntry = new GeneralLedger();
                    $debitEntry->vid = $latestVoucher;
                    $debitEntry->account = $customer->chart_account_id;
                    $debitEntry->type = \Auth::user()->CustomerpaymentNumberFormat($payment->payment_number ?? $payment->id);
                    $debitEntry->ref_number = \Auth::user()->CustomerpaymentNumberFormat($payment->payment_number ?? $payment->id);
                    $debitEntry->debit = 0;
                    $debitEntry->credit = $payment->amount;
                    $debitEntry->ref_id =  -1;
                    $debitEntry->user_id = $customer->id; // Use the updated customer ID
                    $debitEntry->payment_id = $payment->id;
                    $debitEntry->created_by = \Auth::user()->creatorId();
                    $debitEntry->balance = $customer->balance;
                    $debitEntry->send_date = $payment->date;
                    $debitEntry->reference = 'Customer Payment';
                    $debitEntry->save();


                    // Create a new entry for credit to payment account
                    $creditEntry = new GeneralLedger();
                    $creditEntry->vid = $latestVoucher;
                    $creditEntry->account = $accountId->chart_account_id;
                    $creditEntry->type = \Auth::user()->CustomerpaymentNumberFormat($payment->payment_number ?? $payment->id);
                    $creditEntry->ref_number = \Auth::user()->CustomerpaymentNumberFormat($payment->payment_number ?? $payment->id);
                    $creditEntry->debit = $payment->amount - $payment->charge;
                    $creditEntry->credit = 0;
                    $creditEntry->ref_id = -1;
                    $creditEntry->user_id = 0;
                    $creditEntry->payment_id = $payment->id;
                    $creditEntry->created_by = \Auth::user()->creatorId();
                    $creditEntry->send_date = $payment->date;
                    $creditEntry->reference =  'Customer Payment';
                    $creditEntry->save();

                    $chargeaccount = BankAccount::where('id', $payment->bank_charge_account_id)->first();
                    // Create a new entry for credit to payment account
                    $creditEntry = new GeneralLedger();
                    $creditEntry->vid = $latestVoucher;
                    $creditEntry->account = $chargeaccount->chart_account_id;
                    $creditEntry->type = \Auth::user()->CustomerpaymentNumberFormat($payment->payment_number ?? $payment->id);
                    $creditEntry->ref_number = \Auth::user()->CustomerpaymentNumberFormat($payment->payment_number ?? $payment->id);
                    $creditEntry->debit = $payment->charge;
                    $creditEntry->credit = 0;
                    $creditEntry->ref_id = -1;
                    $creditEntry->user_id = 0;
                    $creditEntry->payment_id = $payment->id;
                    $creditEntry->created_by = \Auth::user()->creatorId();
                    $creditEntry->send_date = $payment->date;
                    $creditEntry->reference =  'Customer Payment';
                    $creditEntry->save();

                    // Transaction::editTransaction($payment);

                    // Handle customer balance updates
                    if ($oldCustomerId != $customerId) {
                        // Customer changed - update balances for both old and new customers
                        $oldCustomer = Customer::where('id', $oldCustomerId)->first();
                        if ($oldCustomer) {
                            // Reverse the old customer's balance
                            Utility::updateUserBalance('customer', $oldCustomer->id, $old_amount + $old_charge, 'debit');
                            $oldCustomer->total_paid = $oldCustomer->total_paid - $old_amount;
                            $oldCustomer->save();
                        }
                        // Update new customer's balance
                        Utility::updateUserBalance('customer', $customer->id, $request->amount + $request->charge, 'credit');
                        $customer->total_paid = $customer->total_paid + $request->amount;
                        $customer->save();
                    } else {
                        // Same customer - just update the balance difference
                        if (!empty($customer)) {
                            Utility::updateUserBalance('customer', $customer->id, $old_amount + $old_charge, 'debit');
                            Utility::updateUserBalance('customer', $customer->id, $request->amount + $request->charge, 'credit');
                            $customer->total_paid = $customer->total_paid + $request->amount;
                            $customer->total_paid = $customer->total_paid - $old_amount;
                            $customer->save();
                        }
                    }

                    // Utility::bankAccountBalance($request->account_id, $old_amount, 'credit');
                    // Utility::bankAccountBalance($request->bank_charge_account_id, $old_charge, 'credit');
                    // Utility::bankAccountBalance($request->account_id, $request->amount, 'debit');
                    // Utility::bankAccountBalance($request->bank_charge_account_id, $request->charge, 'debit');
                }
                DB::commit();
                return redirect()->route('customerpayment.index')->with('success', __('Payment Updated Successfully') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : ''));
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(Request $request, $payment)
    {
        $payment = CustomerPayment::find($payment);
        if (\Auth::user()->can('delete payment')) {
            if ($payment->created_by == \Auth::user()->creatorId()) {
                try {
                    DB::beginTransaction();
                    if (!empty($payment->add_receipt)) {
                        //storage limit
                        $file_path = '/uploads/payment/' . $payment->add_receipt;
                        $result = Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);
                    }
                    $deleteDate = Carbon::parse($request->input('delete_date'));
                    $voucherEntry = GeneralLedger::where('payment_id', $payment->id)
                        ->where('type', 'LIKE', '%CustomerPayment%')
                        ->where('created_by', \Auth::user()->creatorId())
                        ->first();
                    $VoucherDate = $voucherEntry ? Carbon::parse($voucherEntry->send_date) : null;
                    if ($VoucherDate && $deleteDate->lt($VoucherDate)) {
                        DB::rollBack();
                        return redirect()->back()->with('error', 'Delete date must be greater than or equal to the payment date.');
                    }
                    $payment->delete();
                    if ($payment->invoice_id !== null) {
                        InvoicePayment::where('id', '=', $payment->payment_id)->delete();
                        $invoice = Invoice::where('id', $payment->invoice_id)->first();
                        $due = $invoice->getDue();
                        $total = $invoice->getTotal();
                        if ($due > 0 && $total != $due) {
                            $invoice->payment_status = 2;
                        } else {
                            $invoice->payment_status = 0;
                        }
                    }
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
                        DB::rollBack();
                        return redirect()->back()->with('error', __("something went wrong , please try again."));
                    }
                    $accountId = BankAccount::find($payment->account_id);
                    if ($payment->status === 2) {
                        TransactionLines::where('reference_id', $payment->id)->where('reference', 'Payment')->delete();
                        $type = 'Payment';
                        $user = 'Customer';
                        Transaction::destroyTransaction($payment->id, $type, $user);

                        if ($payment->customer_id != 0) {
                            // Utility::userBalance('vendor', $payment->vender_id, $payment->amount, 'credit');
                            Utility::updateUserBalance('customer', $payment->customer_id, $payment->amount, 'debit');
                        }
                        // Utility::bankAccountBalance($payment->account_id, $payment->amount - $payment->charge, 'credit');
                        // Utility::bankAccountBalance($payment->bank_charge_account_id, $payment->charge, 'credit');
                        // Create a new entry for debit to payment account
                        $debitEntry = new GeneralLedger();
                        $debitEntry->vid = $newVoucherId;
                        $debitEntry->account = $accountId->chart_account_id;
                        $debitEntry->type = \Auth::user()->CustomerpaymentNumberFormat($payment->payment_number ?? $payment->id);
                        $debitEntry->ref_number = \Auth::user()->CustomerpaymentNumberFormat($payment->payment_number ?? $payment->id);
                        $debitEntry->debit = 0;
                        $debitEntry->credit = $payment->amount - $payment->charge;
                        $debitEntry->ref_id =  -1;
                        $debitEntry->user_id = 0;
                        $debitEntry->created_by = \Auth::user()->creatorId();
                        $debitEntry->send_date = $deleteDate;
                        $debitEntry->payment_id = $payment->id;
                        $debitEntry->reference =  'Delete Customer Payment';
                        $debitEntry->save();
                        $chargeAccount = BankAccount::find($payment->bank_charge_account_id);
                        $debitEntry = new GeneralLedger();
                        $debitEntry->vid = $newVoucherId;
                        $debitEntry->account = $chargeAccount->chart_account_id;
                        $debitEntry->type = \Auth::user()->CustomerpaymentNumberFormat($payment->payment_number ?? $payment->id);
                        $debitEntry->ref_number = \Auth::user()->CustomerpaymentNumberFormat($payment->payment_number ?? $payment->id);
                        $debitEntry->debit = 0;
                        $debitEntry->credit = $payment->charge;
                        $debitEntry->ref_id =  -1;
                        $debitEntry->user_id = 0;
                        $debitEntry->created_by = \Auth::user()->creatorId();
                        $debitEntry->send_date = $deleteDate;
                        $debitEntry->payment_id = $payment->id;
                        $debitEntry->reference = 'Delete Customer Payment';
                        $debitEntry->save();


                        // Create a new entry for credit to vendor account
                        $creditEntry = new GeneralLedger();
                        $creditEntry->vid = $newVoucherId;
                        $creditEntry->account = $customer->chart_account_id;
                        $creditEntry->type = \Auth::user()->CustomerpaymentNumberFormat($payment->payment_number ?? $payment->id);
                        $creditEntry->ref_number = \Auth::user()->CustomerpaymentNumberFormat($payment->payment_number ?? $payment->id);
                        $creditEntry->debit = $payment->amount;
                        $creditEntry->credit = 0;
                        $creditEntry->ref_id =  -1;
                        $creditEntry->user_id = $payment->customer_id;
                        $creditEntry->created_by = \Auth::user()->creatorId();
                        $creditEntry->balance = $customer->balance;
                        $creditEntry->send_date = $deleteDate;
                        $creditEntry->payment_id = $payment->id;
                        $creditEntry->reference = 'Delete Customer Payment';
                        $creditEntry->save();
                    }
                    DB::commit();
                    return redirect()->route('customerpayment.index')->with('success', __('Payment successfully deleted.'));
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

    public function printPayment($paymentId)
    {
        $payment = CustomerPayment::with([
            'customer',
            'bankAccount',
            'category',
            'currency',
            'invoice',
            'invoicePayments.invoice.currency',
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

        return view('customerpayment.print', compact(
            'payment',
            'font_color',
            'color',
            'settings',
            'settings_data',
            'company_logo_url',
            'company_stamp_url'
        ));
    }


    public function sendcustomerpayment(Request $request, $paymentId)
    {
        $payment = CustomerPayment::find($paymentId);
        $customer = Customer::where('id', $payment->customer_id)->first();
        $accountId = BankAccount::find($payment->account_id);
        $sendDate = $request->query('send_date');
        try {
            DB::beginTransaction();
            $payment->status = 2;
            $payment->save();
            $customer->total_paid = $customer->total_paid + $payment->amount;
            $customer->save();

            if (!empty($customer)) {
                Utility::updateUserBalance('customer', $customer->id, $payment->amount + $payment->charge, 'credit');
            }

            // Utility::bankAccountBalance($payment->account_id, $payment->amount , 'debit');
            // Utility::bankAccountBalance($payment->bank_charge_account_id,  $payment->charge,'debit');
            // Add to General Ledger
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
                DB::rollBack();
                return redirect()->back()->with('error', __("something went wrong , please try again."));
            }
            if ($accountId->chart_account_id === 0) {
                DB::rollBack();
                return redirect()->back()->with('error', __("something went wrong , Bank chart of account is null"));
            }
            // Create a new entry for debit to vendor account
            $debitEntry = new GeneralLedger();
            $debitEntry->vid = $newVoucherId;
            $debitEntry->account = $customer->chart_account_id;
            $debitEntry->type =  \Auth::user()->CustomerpaymentNumberFormat($payment->payment_number ?? $payment->id);
            $debitEntry->ref_number =  \Auth::user()->CustomerpaymentNumberFormat($payment->payment_number ?? $payment->id);
            $debitEntry->debit = 0;
            $debitEntry->credit = $payment->amount;
            $debitEntry->ref_id = -1;
            $debitEntry->user_id = $customer->id;
            $debitEntry->payment_id = $payment->id;
            $debitEntry->created_by = \Auth::user()->creatorId();
            $debitEntry->balance = $customer->balance;
            $debitEntry->send_date = $sendDate;
            $debitEntry->reference =  'Customer Payment';
            $debitEntry->save();


            // Create a new entry for credit to payment account
            $creditEntry = new GeneralLedger();
            $creditEntry->vid = $newVoucherId;
            $creditEntry->account = $accountId->chart_account_id;
            $creditEntry->type = \Auth::user()->CustomerpaymentNumberFormat($payment->payment_number ?? $payment->id);
            $creditEntry->ref_number = \Auth::user()->CustomerpaymentNumberFormat($payment->payment_number ?? $payment->id);
            $creditEntry->debit = $payment->amount - $payment->charge;
            $creditEntry->credit = 0;
            $creditEntry->ref_id = -1;
            $creditEntry->user_id = 0;
            $creditEntry->payment_id = $payment->id;
            $creditEntry->created_by = \Auth::user()->creatorId();
            $creditEntry->send_date = $sendDate;
            $creditEntry->reference =  'Customer Payment';
            $creditEntry->save();

            $chargeaccount = BankAccount::where('id', $payment->bank_charge_account_id)->first();
            // Create a new entry for credit to payment account
            $creditEntry = new GeneralLedger();
            $creditEntry->vid = $newVoucherId;
            $creditEntry->account = $chargeaccount->chart_account_id;
            $creditEntry->type = \Auth::user()->CustomerpaymentNumberFormat($payment->payment_number ?? $payment->id);
            $creditEntry->ref_number = \Auth::user()->CustomerpaymentNumberFormat($payment->payment_number ?? $payment->id);
            $creditEntry->debit = $payment->charge;
            $creditEntry->credit = 0;
            $creditEntry->ref_id = -1;
            $creditEntry->user_id = 0;
            $creditEntry->payment_id = $payment->id;
            $creditEntry->created_by = \Auth::user()->creatorId();
            $creditEntry->send_date = $sendDate;
            $creditEntry->reference = 'Customer Payment';
            $creditEntry->save();

            DB::commit();
            return  redirect()->back()->with('success', __('Payment successfully Received'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
    public function allocateForm($id)
    {
        $payment = CustomerPayment::findOrFail($id);
        $invoices = Invoice::where('customer_id', $payment->customer_id)
            ->where('payment_status', '!=', 4)
            ->get();

        return view('customerpayment.allocate', compact('payment', 'invoices'));
    }

    public function storeAllocation(Request $request, $paymentId)
    {
        try {
            DB::beginTransaction();
            $payment = CustomerPayment::findOrFail($paymentId);
            $allocations = $request->input('allocations', []);

            // Sum of this form's allocations
            $newAllocationTotal = collect($request->input('allocations', []))
                ->map(fn($data) => isset($data['amount']) ? floatval($data['amount']) : 0)
                ->sum();

            // Already allocated before this form
            $alreadyAllocated = $payment->invoicePayments()->sum('amount');
            $totalToAllocate = $alreadyAllocated + $newAllocationTotal;

            if ($totalToAllocate > $payment->amount) {
                return redirect()->back()->with('error', __('Allocation exceeds total payment amount.'));
            }

            foreach ($request->input('allocations', []) as $invoiceId => $data) {
                $amount = isset($data['amount']) ? floatval($data['amount']) : 0;
                $amount_in_currency = isset($data['amount_in_currency']) ? floatval($data['amount_in_currency']) : $data['amount'];
                $invoice = Invoice::find($invoiceId);
                if (!$invoice) {
                    continue;
                }

                $invoiceDue = $invoice->getDue();

                if ($amount > $invoiceDue) {
                    return redirect()->back()->with('error', __('Allocation amount exceeds due for invoice :number', ['number' => $invoice->invoice_id]));
                }

                if ($amount > 0) {
                    $invoicePayment = new InvoicePayment();
                    $invoicePayment->invoice_id = $invoice->id;
                    $invoicePayment->date = $payment->date;
                    $invoicePayment->amount =  $amount;
                    $invoicePayment->account_id = $payment->account_id;
                    $invoicePayment->currency_id = Currency::where('code', 'AED')->first()->id;
                    $invoicePayment->currency_rate = Currency::where('code', 'AED')->first()->exchange_rate;
                    //$invoicePayment->currency_id = 4;
                    $invoicePayment->amount_in_currency = $amount_in_currency;
                    $invoicePayment->payment_method = 0;
                    $invoicePayment->reference = $payment->reference;
                    $invoicePayment->description = $payment->description;
                    $invoicePayment->add_receipt = $payment->add_receipt;
                    $invoicePayment->payment_id = $payment->id;
                    $invoicePayment->save();

                    // Refresh the invoice to get updated due
                    $invoice->refresh();
                    $due = $invoice->getDue();
                    $total = $invoice->getTotal();

                    // Update invoice payment status only if this invoice received an allocation
                    if ($due <= 0) {
                        $invoice->payment_status = 4; // Paid
                        $invoice->save();

                        $ispaid = InvoiceStatusChange::where('invoice_id', $invoice->id)->where('payment_status', 4)->first();
                        if ($ispaid) {
                            $ispaid->changed_at = now();
                            $ispaid->save();
                        } else {
                            InvoiceStatusChange::create([
                                'invoice_id' => $invoice->id,
                                'status' => -1,
                                'payment_status' => 4,
                                'changed_at' => now(),
                            ]);
                        }
                    } else {
                        $invoice->payment_status = 2; // Partially paid
                        $invoice->save();

                        $ishalfpaid = InvoiceStatusChange::where('invoice_id', $invoice->id)->where('payment_status', 2)->first();
                        if ($ishalfpaid) {
                            $ishalfpaid->changed_at = now();
                            $ishalfpaid->save();
                        } else {
                            InvoiceStatusChange::create([
                                'invoice_id' => $invoice->id,
                                'status' => -1,
                                'payment_status' => 2,
                                'changed_at' => now(),
                            ]);
                        }
                    }
                }
            }
            DB::commit();
            return redirect()->back()->with('success', __('Customer Payment allocated successfully.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
