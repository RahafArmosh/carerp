<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BillAccount;
use App\Models\BillPayment;
use App\Models\ChartOfAccount;
use App\Models\Refund;
use App\Models\ProductServiceCategory;
use App\Models\Transaction;
use App\Models\TransactionLines;
use App\Models\Utility;
use App\Models\Vender;
use App\Models\GeneralLedger;
use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use App\Models\Currency;

class RefundController extends Controller
{
    /**
     * @return string|null Error message, or null if OK
     */
    protected function refundLedgerAccountError(?Vender $vender, ?BankAccount $bankAccount): ?string
    {
        if (! $bankAccount) {
            return __('Bank account not found.');
        }
        if (! (int) $bankAccount->chart_account_id) {
            return __('This bank account has no chart of account linked. Edit the bank account and assign a chart of account, then try again.');
        }
        if (! ChartOfAccount::where('id', $bankAccount->chart_account_id)->exists()) {
            return __('The bank account’s chart of account is missing from the chart of accounts.');
        }
        if (! $vender) {
            return __('Vendor not found.');
        }
        if (! (int) $vender->chart_account_id) {
            return __('This vendor has no chart of account. Edit the vendor and assign a chart of account, then try again.');
        }
        if (! ChartOfAccount::where('id', $vender->chart_account_id)->exists()) {
            return __('The vendor’s chart of account is missing from the chart of accounts.');
        }

        return null;
    }

    public function index(Request $request)
    {

        if (\Auth::user()->can('manage refund')) {
            $vender = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $vender->prepend('Select Vendor', '');

            $account = BankAccount::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('holder_name', 'id');
            $account->prepend('Select Account', '');

            $category = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->where('type', '=', 'expense')->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');

            $query = Refund::where('created_by', '=', \Auth::user()->creatorId());

            //            if(!empty($request->date))
            //            {
            //                $date_range = explode('to', $request->date);
            //                $query->whereBetween('date', $date_range);
            //            }
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

            $refunds = $query->with(['bankAccount', 'vender', 'category'])->get();

            return view('refund.index', compact('refunds', 'account', 'category', 'vender'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create refund')) {
            $venders = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $venders->prepend('--', 0);

            //            $categories = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->where('type', '=', 2)->get()->pluck('name', 'id');
            $categories = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())
                ->get()->pluck('name', 'id');
            $categories->prepend('Select Category', '');

            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');
            $currencies = Currency::get()->pluck('name', 'id');
            $currencies->prepend('AED', '');
            return view('refund.create', compact('venders', 'categories', 'accounts', 'chartAccounts', 'currencies'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create refund')) {

            $validator = \Validator::make(
                $request->all(),
                [
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
            try {
                DB::beginTransaction();
                $vender = Vender::where('id', $request->vender_id)->first();
                if (! $vender) {
                    DB::rollBack();

                    return redirect()->back()->with('error', __('Vendor not found.'));
                }
                $vender->total_paid = $vender->total_paid - $request->amount;
                $vender->save();
                $payment = new Refund();
                $payment->date = $request->date;
                $payment->amount = !empty($request->currency_id) ? $request->amount * $request->currency_rate : $request->amount;
                $payment->account_id = $request->account_id;
                $payment->currency_id = $request->currency_id;
                $payment->currency_rate = $request->currency_rate;
                $payment->amount_in_currency = $request->amount_in_currency;
                $payment->account_id = $request->account_id;
                $payment->vender_id = $request->vender_id;
                $payment->category_id = $request->category_id;
                $payment->payment_method = 0;
                $payment->reference = $request->reference;
                if (!empty($request->bill_id)) {
                    $payment->bill_id = $request->bill_id;
                    $payment->save();
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
                //            dd($chartAccountId);

                $accountId = BankAccount::find($payment->account_id);
                $ledgerErr = $this->refundLedgerAccountError($vender, $accountId);
                if ($ledgerErr !== null) {
                    DB::rollBack();

                    return redirect()->back()->with('error', $ledgerErr);
                }

                $category = ProductServiceCategory::where('id', $request->category_id)->first();
                $payment->payment_id = $payment->id;
                $payment->type = 'Vendor Refund Payment';
                $payment->category = $category->name;
                $payment->user_id = $payment->vender_id;
                $payment->user_type = 'Vender';
                $payment->account = $request->account_id;

                // Transaction::addTransaction($payment);

                if (!empty($vender)) {
                    // Utility::userBalance('vendor', $vender->id, $request->amount, 'debit');
                    Utility::updateUserBalance('vendor', $vender->id, $request->amount, 'credit');
                }

                // Utility::bankAccountBalance($request->account_id, $request->amount, 'debit');

                //For Notification
                $setting = Utility::settings(\Auth::user()->creatorId());

                //Twilio Notification
                if (isset($setting['twilio_payment_notification']) && $setting['twilio_payment_notification'] == 1) {

                    $vender = Vender::find($request->vender_id);
                    $paymentNotificationArr = [
                        'payment_amount' => \Auth::user()->priceFormat($request->amount),
                        'vendor_name' => $vender->name,
                        'payment_type' => 'Refund Payment',
                    ];
                    Utility::send_twilio_msg($request->contact, 'bill_payment', $paymentNotificationArr);
                }


                // Add to General Ledger

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

                $billRefIdStore = $request->filled('bill_id') ? (int) $request->bill_id : 0;
                $billForStoreGl = $billRefIdStore > 0 ? Bill::find($billRefIdStore) : null;
                $isBillRefundStore = $billForStoreGl !== null;
                $billNumberSuffixStore = $isBillRefundStore ? ' For '.\Auth::user()->billNumberFormat($billForStoreGl->bill_id) : '';
                $refIdStoreGl = $isBillRefundStore ? $billForStoreGl->id : -1;

                // Create a new entry for debit to vendor account
                $debitEntry = new GeneralLedger();
                $debitEntry->vid = $newVoucherId;
                $debitEntry->account = $vender->chart_account_id;
                $debitEntry->type = \Auth::user()->paymentNumberRefundFormat($payment->id).$billNumberSuffixStore;
                $debitEntry->ref_number = \Auth::user()->paymentNumberRefundFormat($payment->id).$billNumberSuffixStore;
                $debitEntry->debit = 0; // Example value
                $debitEntry->credit = $payment->amount; // Example value
                $debitEntry->ref_id = $refIdStoreGl;
                $debitEntry->user_id = $vender->id;
                $debitEntry->payment_id = $payment->id;
                $debitEntry->created_by = \Auth::user()->creatorId();
                $debitEntry->balance = $vender->balance;
                $debitEntry->send_date = $payment->date;
                $debitEntry->reference = $isBillRefundStore ? 'Bill Refund' : 'Vendor Refund';
                $debitEntry->save();


                // Create a new entry for credit to payment account
                $creditEntry = new GeneralLedger();
                $creditEntry->vid = $newVoucherId;
                $creditEntry->account = $accountId->chart_account_id;
                $creditEntry->type = \Auth::user()->paymentNumberRefundFormat($payment->id).$billNumberSuffixStore;
                $creditEntry->ref_number = \Auth::user()->paymentNumberRefundFormat($payment->id).$billNumberSuffixStore;
                $creditEntry->debit = $payment->amount; // Example value
                $creditEntry->credit = 0; // Example value
                $creditEntry->ref_id = $refIdStoreGl;
                $creditEntry->user_id = 0;
                $creditEntry->payment_id = $payment->id;
                $creditEntry->created_by = \Auth::user()->creatorId();
                $creditEntry->send_date = $payment->date;
                $creditEntry->reference = $isBillRefundStore ? 'Bill Refund' : 'Vendor Refund';
                $creditEntry->save();
                DB::commit();
                return redirect()->route('refund.index')->with('success', __('Refund successfully created') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : ''));
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit(Refund $refund)
    {

        if (\Auth::user()->can('edit refund')) {
            $venders = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $venders->prepend('--', 0);

            $categories = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())
                ->get()->pluck('name', 'id');
            $categories->prepend('Select Category', '');

            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');
            $bills = Bill::where('vender_id', $refund->vender_id)->where("status", "!=", "0")->get();
            $currencies = Currency::get()->pluck('name', 'id');
            $currencies->prepend('AED', '');
            return view('refund.edit', compact('venders', 'categories', 'accounts', 'refund', 'chartAccounts', 'bills', 'currencies'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, Refund $refund)
    {
        if (\Auth::user()->can('edit refund')) {
            try {
                DB::beginTransaction();
                $validator = \Validator::make(
                    $request->all(),
                    [
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
                $vender = Vender::where('id', $request->old_vender_id)->first();
                if (!empty($vender)) {
                    // Utility::userBalance('vendor', $vender->id, $payment->amount, 'credit');
                    Utility::updateUserBalance('vendor', $vender->id, $refund->amount, 'debit');
                    $vender->total_paid = $vender->total_paid + $refund->amount;
                }
                // Utility::bankAccountBalance($refund->account_id, $refund->amount, 'credit');

                $refund->date = $request->date;
                $refund->amount = $request->currency_id ? $request->amount * $request->currency_rate : $request->amount;
                $refund->currency_id = $request->currency_id;
                $refund->currency_rate = $request->currency_rate;
                $refund->amount_in_currency = $request->amount_in_currency;
                $refund->account_id = $request->account_id;
                //            $payment->chart_account_id  = $request->chart_account_id;
                $refund->vender_id = $request->old_vender_id;
                $refund->category_id = $request->category_id;
                $refund->payment_method = 0;
                $refund->reference = $request->reference;
                if (!empty($request->bill_id)) {
                    $bill = Bill::find($request->bill_id);
                    $refund->bill_id = $request->bill_id;
                    // if($refund->bill_id != null){
                    //      $refund->bill_id = $request->bill_id;
                    //      $bill_payment = BillPayment::find($refund->payment_id);
                    //      $bill_payment->amount = ($bill_payment->amount + $refund->amount) - $request->amount;
                    //      $refund->save();
                    //      $bill_payment->save();
                    // }
                    // else{
                    //     $refund->bill_id = $request->bill_id;
                    //     $getBillPayment = BillPayment::where('bill_id',$request->bill_id)->where('amount','>',$request->amount)->first();
                    //     $getBillPayment->amount = $getBillPayment->amount - $request->amount;
                    //     $getBillPayment->save();
                    //     $refund->payment_id =$getBillPayment->id;
                    //     $refund->save();
                    // }
                }
                if (!empty($request->add_receipt)) {
                    $document = $request->file('add_receipt');
                    $fileName = time() . "_" . preg_replace('/[^A-Za-z0-9\-]/', '', $request->add_receipt->getClientOriginalName());
                    $refund->add_receipt = $fileName;
                    $dir = 'uploads/payment';
                    $document->move(public_path('uploads/customer_payment'), $fileName);
                }

                $refund->description = $request->description;
                $refund->save();
                TransactionLines::where('reference_id', $refund->id)->where('reference', 'Vendor Refund Payment')->delete();
                $accountId = BankAccount::find($refund->account_id);
                $ledgerErr = $this->refundLedgerAccountError($vender, $accountId);
                if ($ledgerErr !== null) {
                    DB::rollBack();

                    return redirect()->back()->with('error', $ledgerErr);
                }

                $billRefId = $request->filled('bill_id') ? (int) $request->bill_id : (int) ($refund->bill_id ?: 0);
                $billForGl = $billRefId > 0 ? Bill::find($billRefId) : null;
                $isBillRefund = $billForGl !== null;
                $billNumberSuffix = $isBillRefund ? ' For '.\Auth::user()->billNumberFormat($billForGl->bill_id) : '';
                $refIdForGl = $isBillRefund ? $billForGl->id : -1;

                $category = ProductServiceCategory::where('id', $request->category_id)->first();
                $existingGl = GeneralLedger::where('payment_id', $refund->id)
                    ->where(function ($query) {
                        $query->where('reference', 'LIKE', '%Bill Refund%')
                            ->orWhere('reference', 'LIKE', '%Vendor Refund%');
                    })->where('created_by', \Auth::user()->creatorId())->first();

                $latestVoucher = $existingGl?->vid;
                if ($latestVoucher === null) {
                    $latestVoucherRow = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
                    $latestVoucher = $latestVoucherRow ? $latestVoucherRow->vid + 1 : 1;
                }

                GeneralLedger::where('payment_id', $refund->id)
                    ->where(function ($query) {
                        $query->where('reference', 'LIKE', '%Bill Refund%')
                            ->orWhere('reference', 'LIKE', '%Vendor Refund%');
                    })->where('created_by', \Auth::user()->creatorId())->delete();
                // Create a new entry for debit to vendor account
                $debitEntry = new GeneralLedger();
                $debitEntry->vid = $latestVoucher;
                $debitEntry->account = $vender->chart_account_id;
                $debitEntry->type = \Auth::user()->paymentNumberRefundFormat($refund->id).$billNumberSuffix;
                $debitEntry->ref_number = \Auth::user()->paymentNumberRefundFormat($refund->id).$billNumberSuffix;
                $debitEntry->debit = 0; // Example value
                $debitEntry->credit = $refund->amount; // Example value
                $debitEntry->ref_id = $refIdForGl;
                $debitEntry->user_id = $vender->id;
                $debitEntry->payment_id = $refund->id;
                $debitEntry->created_by = \Auth::user()->creatorId();
                $debitEntry->balance = $vender->balance;
                $debitEntry->send_date = $refund->date;
                $debitEntry->reference = $isBillRefund ? 'Bill Refund' : 'Vendor Refund';
                $debitEntry->save();


                // Create a new entry for credit to payment account
                $creditEntry = new GeneralLedger();
                $creditEntry->vid = $latestVoucher;
                $creditEntry->account = $accountId->chart_account_id;
                $creditEntry->type = \Auth::user()->paymentNumberRefundFormat($refund->id).$billNumberSuffix;
                $creditEntry->ref_number = \Auth::user()->paymentNumberRefundFormat($refund->id).$billNumberSuffix;
                $creditEntry->debit = $refund->amount; // Example value
                $creditEntry->credit = 0; // Example value
                $creditEntry->ref_id = $refIdForGl;
                $creditEntry->user_id = 0;
                $creditEntry->payment_id = $refund->id;
                $creditEntry->created_by = \Auth::user()->creatorId();
                $creditEntry->send_date = $refund->date;
                $creditEntry->reference = $isBillRefund ? 'Bill Refund' : 'Vendor Refund';
                $creditEntry->save();
                if (!empty($vender)) {
                    // Utility::userBalance('vendor', $vender->id, $request->amount, 'debit');
                    Utility::updateUserBalance('vendor', $vender->id, $request->amount, 'credit');
                    $vender->total_paid = $vender->total_paid - $request->amount;
                }
                // Utility::bankAccountBalance($request->account_id, $request->amount, 'debit');
                DB::commit();
                return redirect()->route('refund.index')->with('success', __('Refund Updated Successfully') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : ''));
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
        $payment = Refund::find($payment);
        if (\Auth::user()->can('delete refund')) {
            if ($payment->created_by == \Auth::user()->creatorId()) {
                try {
                    DB::beginTransaction();
                    if (!empty($payment->add_receipt)) {
                        //storage limit
                        $file_path = '/uploads/refund/' . $payment->add_receipt;
                        $result = Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);
                    }

                    TransactionLines::where('reference_id', $payment->id)->where('reference', 'Vendor Refund Payment')->delete();

                    $payment->delete();
                    $type = 'Vendor Refund Payment';
                    $user = 'Vender';
                    Transaction::destroyTransaction($payment->id, $type, $user);

                    if ($payment->vender_id != 0) {
                        // Utility::userBalance('vendor', $payment->vender_id, $payment->amount, 'credit');
                        Utility::updateUserBalance('vendor', $payment->vender_id, $payment->amount, 'debit');
                    }
                    // Utility::bankAccountBalance($payment->account_id, $payment->amount, 'credit');
                    // if($payment->bill_id !== null){
                    //     $getBillPayment  = BillPayment::where('id', '=', $payment->payment_id)->first();
                    //     $getBillPayment->amount = $getBillPayment->amount + $payment->amount ;
                    //     $getBillPayment->save();

                    // }
                    // Add to General Ledger
                    // Get the latest 'vid' entry, if any exist
                    $vender = Vender::where('id', $payment->vender_id)->first();
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
                    $ledgerErr = $this->refundLedgerAccountError($vender, $accountId);
                    if ($ledgerErr !== null) {
                        DB::rollBack();

                        return redirect()->back()->with('error', $ledgerErr);
                    }

                    // Create a new entry for debit to payment account
                    $debitEntry = new GeneralLedger();
                    $debitEntry->vid = $newVoucherId;
                    $debitEntry->account = $accountId->chart_account_id;
                    $debitEntry->type = $payment->bill_id !== null ?  'Delete Refund ' . \Auth::user()->paymentNumberRefundFormat($payment->id) . ' For ' . \Auth::user()->billNumberFormat($payment->bill->bill_id) : 'Delete  ' . \Auth::user()->paymentNumberRefundFormat($payment->id);
                    $debitEntry->ref_number = $payment->bill_id !== null ?  'Delete Refund ' . \Auth::user()->paymentNumberRefundFormat($payment->id) . ' For ' . \Auth::user()->billNumberFormat($payment->bill->bill_id) : 'Delete  ' . \Auth::user()->paymentNumberRefundFormat($payment->id);
                    $debitEntry->debit = 0; // Example value
                    $debitEntry->credit = $payment->amount; // Example value
                    $debitEntry->ref_id = $payment->bill_id !== null ? $payment->bill_id : -1;
                    $debitEntry->user_id = 0;
                    $debitEntry->created_by = \Auth::user()->creatorId();
                    $debitEntry->send_date = now();
                    $debitEntry->reference = $payment->bill_id !== null ? 'Delete Bill Refund' : 'Delete Vendor Refund';
                    $debitEntry->save();


                    // Create a new entry for credit to vendor account
                    $creditEntry = new GeneralLedger();
                    $creditEntry->vid = $newVoucherId;
                    $creditEntry->account = $vender->chart_account_id;
                    $creditEntry->type = $payment->bill_id !== null ?  'Delete Refund ' . \Auth::user()->paymentNumberRefundFormat($payment->id) . ' For ' . \Auth::user()->billNumberFormat($payment->bill->bill_id) : 'Delete  ' . \Auth::user()->paymentNumberRefundFormat($payment->id);
                    $creditEntry->ref_number = $payment->bill_id !== null ?  'Delete Refund ' . \Auth::user()->paymentNumberRefundFormat($payment->id) . ' For ' . \Auth::user()->billNumberFormat($payment->bill->bill_id) : 'Delete  ' . \Auth::user()->paymentNumberRefundFormat($payment->id);
                    $creditEntry->debit = $payment->amount; // Example value
                    $creditEntry->credit = 0; // Example value
                    $creditEntry->ref_id = $payment->bill_id !== null ? $payment->bill_id : -1;
                    $creditEntry->user_id = $payment->vender_id;
                    $creditEntry->created_by = \Auth::user()->creatorId();
                    $creditEntry->balance = $vender->balance;
                    $creditEntry->send_date = now();
                    $creditEntry->reference = $payment->bill_id !== null ? 'Delete Bill Refund' : 'Delete Vendor Refund';
                    $creditEntry->save();
                    DB::commit();
                    return redirect()->route('refund.index')->with('success', __('Refund successfully deleted.'));
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
    public function printvendorrefundpayment($paymentId)
    {
        $payment = Refund::with(['vender', 'bankAccount', 'category', 'currency', 'bill.currency'])
            ->findOrFail($paymentId);
        $settings = Utility::settings();
        $settings_data = Utility::settingsById($payment->created_by);
        $bill_logo = $settings_data['bill_logo'] ?? null;
        $logo_path = $bill_logo
            ? Utility::get_file('bill_logo/').$bill_logo
            : asset(Storage::url('uploads/logo/'.($settings['company_logo_dark'] ?? 'logo-dark.png')));

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

        return view('refund.print', compact(
            'payment',
            'font_color',
            'color',
            'settings',
            'settings_data',
            'logo_path',
            'company_logo_url',
            'company_stamp_url'
        ));
    }
}
