<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\Utility;
use App\Models\GeneralLedger;
use App\Models\Customer;
use App\Models\ChartOfAccount;
use App\Models\TransactionLines;
use App\Models\Currency;
use Illuminate\Http\Request;

class CreditNoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {

        if (\Auth::user()->can('manage credit note')) {
            $invoices = Invoice::where('created_by', \Auth::user()->creatorId())->get();

            return view('creditNote.index', compact('invoices'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create($invoice_id)
    {

        if (\Auth::user()->can('create credit note')) {

            $invoiceDue = Invoice::where('id', $invoice_id)->first();
            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');
            
            $currencies = Currency::get()
                ->pluck('name', 'id');
            $currencies->prepend('Select Currency', '');
            
            return view('creditNote.create', compact('invoiceDue', 'invoice_id', 'chartAccounts', 'currencies'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function store(Request $request, $invoice_id)
    {

        if (\Auth::user()->can('create credit note')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'amount' => 'required|numeric',
                    'date' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            $invoiceDue = Invoice::where('id', $invoice_id)->first();
            if ($request->amount > $invoiceDue->getDue()) {
                return redirect()->back()->with('error', 'Maximum ' . \Auth::user()->priceFormat($invoiceDue->getDue()) . ' credit limit of this invoice.');
            }
            $invoice = Invoice::where('id', $invoice_id)->first();

            // Get selected currency and rate
            $selectedCurrencyId = $request->currency_id;
            $selectedCurrency = Currency::find($selectedCurrencyId);
            $currencyRate = $selectedCurrency ? $selectedCurrency->exchange_rate : 1;
            
            // Calculate AED amount from chosen currency
            $amountAED = $request->amount * $currencyRate;
            
            // Calculate amount in invoice currency
            $amountInCurrency = $request->amount;
            if ($invoice->currency_id && $invoice->exchange_rate > 0) {
                if ($selectedCurrencyId == $invoice->currency_id) {
                    // Same currency as invoice, use entered amount
                    $amountInCurrency = $request->amount;
                } else {
                    // Different currency, convert AED to invoice currency
                    $amountInCurrency = $amountAED / $invoice->exchange_rate;
                }
            }

            $credit              = new CreditNote();
            $credit->invoice     = $invoice_id;
            $credit->customer    = $invoice->customer_id;
            $credit->date        = $request->date;
            $credit->amount      = $amountAED; // Store in AED
            $credit->currency_id = $selectedCurrencyId; // Use chosen currency
            $credit->currency_rate = $currencyRate;
            $credit->amount_in_currency = $amountInCurrency;
            $credit->description = $request->description;
            $credit->account_id = $request->account_id;
            $credit->save();

            Utility::updateUserBalance('customer', $invoice->customer_id, $request->amount, 'debit');
            $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
            if ($latestVoucher) {
                $lastVid = $latestVoucher->vid;
                $newVoucherId = $lastVid + 1;
            } else {
                $newVoucherId = 1;
            }

            $existingRecord = GeneralLedger::where('vid', $newVoucherId)->exists();

            if ($existingRecord) {
                return redirect()->back()->with('error', __("something went wrong , please try again."));
            }
            $customer = Customer::where('id', $invoice->customer_id)->first();
            // Retrieve the chart account ID for the customer
            $customerChartAccountId = $customer->chart_account_id;

            // Create a new entry debit for the customer account
            $newEntryCustomer = new GeneralLedger();
            $newEntryCustomer->vid = $newVoucherId;
            $newEntryCustomer->account = $customerChartAccountId;
            $newEntryCustomer->type = "Credit Note for " . \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
            $newEntryCustomer->debit = 0;
            $newEntryCustomer->credit = $amountAED;
            $newEntryCustomer->ref_id = $credit->id;
            $newEntryCustomer->user_id = $customer->id;
            $newEntryCustomer->created_by = \Auth::user()->creatorId();
            $newEntryCustomer->balance = $customer->balance;
            $newEntryCustomer->send_date = $request->date;
            $newEntryCustomer->reference = 'Credit Note';
            $newEntryCustomer->save();

             // Create a new entry debit for the customer account
             $newEntryAccount = new GeneralLedger();
             $newEntryAccount->vid = $newVoucherId;
             $newEntryAccount->account = $request->account_id;
             $newEntryAccount->type = "Credit Note for " . \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
             $newEntryAccount->debit = $amountAED;
             $newEntryAccount->credit = 0;
             $newEntryAccount->ref_id = $credit->id;
             $newEntryAccount->user_id = 0;
             $newEntryAccount->created_by = \Auth::user()->creatorId();
             $newEntryAccount->balance = 0;
             $newEntryAccount->send_date = $request->date;
             $newEntryAccount->reference = 'Credit Note';
             $newEntryAccount->save();
            return redirect()->back()->with('success', __('Credit Note successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function edit($invoice_id, $creditNote_id)
    {
        if (\Auth::user()->can('edit credit note')) {

            $creditNote = CreditNote::find($creditNote_id);
            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');
            
            $currencies = Currency::get()
                ->pluck('name', 'id');
            $currencies->prepend('Select Currency', '');
            
            return view('creditNote.edit', compact('creditNote','chartAccounts', 'currencies'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function update(Request $request, $invoice_id, $creditNote_id)
    {

        if (\Auth::user()->can('edit credit note')) {

            $validator = \Validator::make(
                $request->all(),
                [
                    'amount' => 'required|numeric',
                    'date' => 'required',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $invoiceDue = Invoice::where('id', $invoice_id)->first();
            $credit = CreditNote::find($creditNote_id);
            if ($request->amount > $invoiceDue->getDue() + $credit->amount) {
                return redirect()->back()->with('error', 'Maximum ' . \Auth::user()->priceFormat($invoiceDue->getDue()) . ' credit limit of this invoice.');
            }


            Utility::updateUserBalance('customer', $invoiceDue->customer_id, $credit->amount, 'credit');

            // Get selected currency and rate
            $selectedCurrencyId = $request->currency_id;
            $selectedCurrency = Currency::find($selectedCurrencyId);
            $currencyRate = $selectedCurrency ? $selectedCurrency->exchange_rate : 1;
            
            // Calculate AED amount from chosen currency
            $amountAED = $request->amount * $currencyRate;
            
            // Calculate amount in invoice currency
            $amountInCurrency = $request->amount;
            if ($invoiceDue->currency_id && $invoiceDue->exchange_rate > 0) {
                if ($selectedCurrencyId == $invoiceDue->currency_id) {
                    // Same currency as invoice, use entered amount
                    $amountInCurrency = $request->amount;
                } else {
                    // Different currency, convert AED to invoice currency
                    $amountInCurrency = $amountAED / $invoiceDue->exchange_rate;
                }
            }

            $credit->date        = $request->date;
            $credit->amount      = $amountAED; // Store in AED
            $credit->currency_id = $selectedCurrencyId; // Use chosen currency
            $credit->currency_rate = $currencyRate;
            $credit->amount_in_currency = $amountInCurrency;
            $credit->description = $request->description;
            $credit->account_id = $request->account_id;
            $credit->save();

            Utility::updateUserBalance('customer', $invoiceDue->customer_id, $request->amount, 'debit');
            $latestVoucher = GeneralLedger::where('ref_id', $credit->id)
                ->where(function ($query) {
                    $query->where('type', 'LIKE', '%Credit Note%');
                })
                ->where('created_by',\Auth::user()->creatorId())
                ->get();

            foreach ($latestVoucher as $voucher) {
                if ($voucher->debit != 0) {
                    $voucher->debit = $amountAED;
                    $voucher->account = $request->account_id;
                }

                if ($voucher->credit != 0) {
                    $voucher->credit = $amountAED;
                }
                $voucher->send_date = $credit->date;
                $voucher->save();
            }

            return redirect()->back()->with('success', __('Credit Note successfully updated.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function destroy($invoice_id, $creditNote_id)
    {
        if (\Auth::user()->can('delete credit note')) {
            \DB::beginTransaction();
            try {
            $creditNote = CreditNote::where('id',$creditNote_id)->first();
            $creditNote->delete();

            Utility::updateUserBalance('customer', $creditNote->customer, $creditNote->amount, 'credit');
            $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
            if ($latestVoucher) {
                $lastVid = $latestVoucher->vid;
                $newVoucherId = $lastVid + 1;
            } else {
                $newVoucherId = 1;
            }

            $existingRecord = GeneralLedger::where('vid', $newVoucherId)->exists();

            if ($existingRecord) {
                return redirect()->back()->with('error', __("something went wrong , please try again."));
            }
            $invoice = Invoice::where('id', $invoice_id)->first();
            $customer = Customer::where('id', $invoice->customer_id)->first();
            TransactionLines::where('reference_id', $creditNote->id)->where('reference', 'Credit Note')->delete();
            // Retrieve the chart account ID for the customer
            $customerChartAccountId = $customer->chart_account_id;

            // Create a new entry debit for the customer account
            $newEntryCustomer = new GeneralLedger();
            $newEntryCustomer->vid = $newVoucherId;
            $newEntryCustomer->account = $customerChartAccountId;
            $newEntryCustomer->type = "Delete Credit Note for " . \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
            $newEntryCustomer->debit = 0;
            $newEntryCustomer->credit = $creditNote->amount;
            $newEntryCustomer->ref_id = $creditNote->id;
            $newEntryCustomer->user_id = $customer->id;
            $newEntryCustomer->created_by = \Auth::user()->creatorId();
            $newEntryCustomer->balance = $customer->balance;
            $newEntryCustomer->send_date = $creditNote->date;
            $newEntryCustomer->reference = 'Delete Credit Note';
            $newEntryCustomer->save();


            $newEntryAccount = new GeneralLedger();
            $newEntryAccount->vid = $newVoucherId;
            $newEntryAccount->account = $creditNote->account_id;
            $newEntryAccount->type = "Delete Credit Note for " . \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
            $newEntryAccount->debit = $creditNote->amount;
            $newEntryAccount->credit = 0;
            $newEntryAccount->ref_id = $creditNote->id;
            $newEntryAccount->user_id = 0;
            $newEntryAccount->created_by = \Auth::user()->creatorId();
            $newEntryAccount->balance = 0;
            $newEntryAccount->send_date = $creditNote->date;
            $newEntryAccount->reference = 'Delete Credit Note';
            $newEntryAccount->save();
            \DB::commit();
            return redirect()->back()->with('success', __('Credit Note successfully deleted.'));
            } catch (\Exception $e) {
                \DB::rollBack();
                \Log::error('Failed to delete credit note and save ledger:', ['error' => $e->getMessage()]);
                return redirect()->back()->with('error', 'Something went wrong while saving general ledger.');
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function customCreate()
    {
        if (\Auth::user()->can('create credit note')) {

            $invoices = Invoice::where('created_by', \Auth::user()->creatorId())
            ->whereIn('status', [4,6])
            ->get()
            ->pluck('id', 'id');
            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');
            
            $currencies = Currency::get()
                ->pluck('name', 'id');
            $currencies->prepend('Select Currency', '');
            
            return view('creditNote.custom_create', compact('invoices','chartAccounts', 'currencies'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function customStore(Request $request)
    {
        if (\Auth::user()->can('create credit note')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'invoice' => 'required|numeric',
                    'amount' => 'required|numeric',
                    'date' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            $invoice_id = $request->invoice;
            $invoiceDue = Invoice::where('id', $invoice_id)->first();

            if ($request->amount > $invoiceDue->getDue()) {
                return redirect()->back()->with('error', 'Maximum ' . \Auth::user()->priceFormat($invoiceDue->getDue()) . ' credit limit of this invoice.');
            }
            $invoice             = Invoice::where('id', $invoice_id)->first();
            
            // Get selected currency and rate
            $selectedCurrencyId = $request->currency_id;
            $selectedCurrency = Currency::find($selectedCurrencyId);
            $currencyRate = $selectedCurrency ? $selectedCurrency->exchange_rate : 1;
            
            // Calculate AED amount from chosen currency
            $amountAED = $request->amount * $currencyRate;
            
            // Calculate amount in invoice currency
            $amountInCurrency = $request->amount;
            if ($invoice->currency_id && $invoice->exchange_rate > 0) {
                if ($selectedCurrencyId == $invoice->currency_id) {
                    // Same currency as invoice, use entered amount
                    $amountInCurrency = $request->amount;
                } else {
                    // Different currency, convert AED to invoice currency
                    $amountInCurrency = $amountAED / $invoice->exchange_rate;
                }
            }
            
            $credit              = new CreditNote();
            $credit->invoice     = $invoice_id;
            $credit->customer    = $invoice->customer_id;
            $credit->date        = $request->date;
            $credit->amount      = $amountAED; // Store in AED
            $credit->currency_id = $selectedCurrencyId; // Use chosen currency
            $credit->currency_rate = $currencyRate;
            $credit->amount_in_currency = $amountInCurrency;
            $credit->description = $request->description;
            $credit->account_id = $request->account_id;
            $credit->save();

            Utility::updateUserBalance('customer', $invoice->customer_id, $request->amount, 'debit');
            $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
            if ($latestVoucher) {
                $lastVid = $latestVoucher->vid;
                $newVoucherId = $lastVid + 1;
            } else {
                $newVoucherId = 1;
            }

            $existingRecord = GeneralLedger::where('vid', $newVoucherId)->exists();

            if ($existingRecord) {
                return redirect()->back()->with('error', __("something went wrong , please try again."));
            }
            $customer = Customer::where('id', $invoice->customer_id)->first();
            // Retrieve the chart account ID for the customer
            $customerChartAccountId = $customer->chart_account_id;

            // Create a new entry debit for the customer account
            $newEntryCustomer = new GeneralLedger();
            $newEntryCustomer->vid = $newVoucherId;
            $newEntryCustomer->account = $customerChartAccountId;
            $newEntryCustomer->type = "Credit Note for " . \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
            $newEntryCustomer->debit = 0;
            $newEntryCustomer->credit = $amountAED;
            $newEntryCustomer->ref_id = $credit->id;
            $newEntryCustomer->user_id = $customer->id;
            $newEntryCustomer->created_by = \Auth::user()->creatorId();
            $newEntryCustomer->balance = $customer->balance;
            $newEntryCustomer->send_date = $request->date;
            $newEntryCustomer->reference = 'Credit Note';
            $newEntryCustomer->save();

             // Create a new entry debit for the customer account
             $newEntryAccount = new GeneralLedger();
             $newEntryAccount->vid = $newVoucherId;
             $newEntryAccount->account = $request->account_id;
             $newEntryAccount->type = "Credit Note for " . \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
            $newEntryAccount->debit = $amountAED;
             $newEntryAccount->credit = 0;
             $newEntryAccount->ref_id = $credit->id;
             $newEntryAccount->user_id = 0;
             $newEntryAccount->created_by = \Auth::user()->creatorId();
             $newEntryAccount->balance = 0;
             $newEntryAccount->send_date = $request->date;
             $newEntryAccount->reference = 'Credit Note';
             $newEntryAccount->save();


            return redirect()->back()->with('success', __('Credit Note successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function getinvoice(Request $request)
    {
        $invoice = Invoice::where('id', $request->id)->first();

        echo json_encode($invoice->getDue());
    }
}
