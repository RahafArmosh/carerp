<?php

namespace App\Http\Controllers;

use App\Models\DirectExpensePayment;
use App\Models\DirectExpense;
use App\Models\BankAccount;
use App\Models\Vender;
use App\Models\Currency;
use App\Models\GeneralLedger;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DirectExpensePaymentController extends Controller
{
    public function index(Request $request)
    {
        if (!\Auth::user()->can('manage payment') && !\Auth::user()->can('manage expense')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $userId = \Auth::user()->creatorId();
        $vendors = Vender::where('created_by', $userId)->get()->pluck('name', 'id');
        $vendors->prepend('Select Vendor', '');

        $accounts = BankAccount::where('created_by', $userId)->get()->pluck('holder_name', 'id');
        $accounts->prepend('Select Account', '');

        $status = array_filter(DirectExpensePayment::$statues);
        $query = DirectExpensePayment::where('created_by', $userId)->with(['directExpense', 'vendor', 'bankAccount', 'currency']);

        if (!empty($request->vendor)) {
            $query->where('vendor_id', $request->vendor);
        }

        if (!empty($request->account)) {
            $query->where('account_id', $request->account);
        }

        if (!empty($request->status) || $request->status === '0') {
            $query->where('status', $request->status);
        }

        if (count(explode('to', $request->date ?? '')) > 1) {
            $date_range = explode(' to ', $request->date);
            $query->whereBetween('date', $date_range);
        } elseif (!empty($request->date)) {
            $query->where('date', $request->date);
        }

        $payments = $query->orderByDesc('id')->get();

        return view('direct_expense_payments.index', compact('payments', 'accounts', 'vendors', 'status'));
    }

    public function create($directExpenseId)
    {
        if (!\Auth::user()->can('create payment') && !\Auth::user()->can('manage expense')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $directExpense = DirectExpense::with(['vendor', 'items', 'payments'])->find($directExpenseId);
        if (!$directExpense || $directExpense->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Direct expense not found.'));
        }

        $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))
            ->where('created_by', \Auth::user()->creatorId())
            ->get()
            ->pluck('name', 'id');

        $currencies = Currency::get();
        return view('direct_expense_payments.create', compact('directExpense', 'accounts', 'currencies'));
    }

    public function store(Request $request, $directExpenseId)
    {
        if (!\Auth::user()->can('create payment') && !\Auth::user()->can('manage expense')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $request->validate([
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'account_id' => 'required|exists:bank_accounts,id',
            'currency_id' => 'nullable|exists:currencies,id',
            'currency_rate' => 'nullable|numeric|min:0',
            'currency_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'reference' => 'nullable|string',
        ]);

        $directExpense = DirectExpense::find($directExpenseId);
        if (!$directExpense || $directExpense->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Direct expense not found.'));
        }

        try {
            DB::beginTransaction();

            $payment = new DirectExpensePayment();
            $payment->date = $request->date;
            // If a currency is chosen and rate > 0, treat input amount as currency_amount, convert to AED in amount
            $inputAmount = (float) $request->amount;
            $selectedCurrencyId = $request->currency_id ?: ($directExpense->currency_id ?? null);
            $rate = $request->currency_rate ?: ($directExpense->exchange_rate ?? null);

            if ($selectedCurrencyId && $rate && (float)$rate > 0) {
                $payment->currency_id = $selectedCurrencyId;
                $payment->currency_rate = (float) $rate;
                $payment->currency_amount = $inputAmount; // original amount in selected currency
                $payment->amount = round($inputAmount * (float)$rate, 2); // store AED
            } else {
                $payment->currency_id = null;
                $payment->currency_rate = null;
                $payment->currency_amount = null;
                $payment->amount = $inputAmount; // already AED
            }
            $payment->account_id = $request->account_id;
            $payment->direct_expense_id = $directExpenseId;
            $payment->vendor_id = $directExpense->vendor_id;
            $payment->payment_method = 0;
            $payment->reference = $request->reference;
            $payment->status = 0; // Draft
            $payment->description = $request->description;
            $payment->created_by = \Auth::user()->creatorId();

            if ($request->hasFile('add_receipt')) {
                $document = $request->file('add_receipt');
                $fileName = time() . "_" . preg_replace('/[^A-Za-z0-9\-]/', '', $document->getClientOriginalName());
                $payment->add_receipt = $fileName;
                $document->move(public_path('uploads/direct_expense_payment'), $fileName);
            }

            $payment->save();

            // Recalculate parent direct expense payment status (0 unpaid, 2 partial, 4 paid)
            $this->recalculateDirectExpenseStatus($directExpense);

            DB::commit();

            return redirect()->route('direct_expense_payments.index')
                ->with('success', __('Payment created successfully.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        if (!\Auth::user()->can('view payment') && !\Auth::user()->can('manage expense')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $payment = DirectExpensePayment::with([
            'directExpense.vendor', 
            'directExpense.currency',
            'directExpense.items.subProduct.productService',
            'directExpense.items.chartAccount',
            'directExpense.payments',
            'vendor', 
            'bankAccount',
            'currency'
        ])
            ->where('created_by', \Auth::user()->creatorId())
            ->find($id);

        if (!$payment) {
            return redirect()->back()->with('error', __('Payment not found.'));
        }

        // Get tax information for the direct expense
        $taxes = [];
        if ($payment->directExpense) {
            $taxIds = $payment->directExpense->getTaxIds();
            if (!empty($taxIds)) {
                $taxes = \App\Models\Tax::whereIn('id', $taxIds)->get(['id', 'name', 'rate']);
            }
        }

        return view('direct_expense_payments.show', compact('payment', 'taxes'));
    }

    public function edit($id)
    {
        if (!\Auth::user()->can('edit payment') && !\Auth::user()->can('manage expense')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $payment = DirectExpensePayment::with(['directExpense', 'vendor', 'bankAccount', 'currency'])
            ->where('created_by', \Auth::user()->creatorId())
            ->find($id);

        if (!$payment) {
            return redirect()->back()->with('error', __('Payment not found.'));
        }

        // Only allow editing if payment status is unpaid (status = 0)
        if ($payment->status != 0) {
            return redirect()->back()->with('error', __('Only unpaid payments can be edited.'));
        }

        $directExpense = DirectExpense::with(['vendor', 'items', 'payments'])
            ->where('id', $payment->direct_expense_id)
            ->where('created_by', \Auth::user()->creatorId())
            ->first();

        if (!$directExpense) {
            return redirect()->back()->with('error', __('Direct expense not found.'));
        }

        $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))
            ->where('created_by', \Auth::user()->creatorId())
            ->get()
            ->pluck('name', 'id');

        $currencies = Currency::get();
        return view('direct_expense_payments.edit', compact('payment', 'directExpense', 'accounts', 'currencies'));
    }

    public function update(Request $request, $id)
    {
        if (!\Auth::user()->can('edit payment') && !\Auth::user()->can('manage expense')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $payment = DirectExpensePayment::with('directExpense')
            ->where('created_by', \Auth::user()->creatorId())
            ->find($id);

        if (!$payment) {
            return redirect()->back()->with('error', __('Payment not found.'));
        }

        // Only allow updating if payment status is unpaid (status = 0)
        if ($payment->status != 0) {
            return redirect()->back()->with('error', __('Only unpaid payments can be edited.'));
        }

        $request->validate([
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'account_id' => 'required|exists:bank_accounts,id',
            'currency_id' => 'nullable|exists:currencies,id',
            'currency_rate' => 'nullable|numeric|min:0',
            'currency_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'reference' => 'nullable|string',
        ]);

        $directExpense = DirectExpense::find($payment->direct_expense_id);
        if (!$directExpense || $directExpense->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Direct expense not found.'));
        }

        try {
            DB::beginTransaction();

            // Delete old receipt if new one is uploaded
            if ($request->hasFile('add_receipt')) {
                if ($payment->add_receipt && file_exists(public_path('uploads/direct_expense_payment/' . $payment->add_receipt))) {
                    unlink(public_path('uploads/direct_expense_payment/' . $payment->add_receipt));
                }
                $document = $request->file('add_receipt');
                $fileName = time() . "_" . preg_replace('/[^A-Za-z0-9\-]/', '', $document->getClientOriginalName());
                $payment->add_receipt = $fileName;
                $document->move(public_path('uploads/direct_expense_payment'), $fileName);
            }

            // Update payment fields
            $payment->date = $request->date;
            $inputAmount = (float) $request->amount;
            $selectedCurrencyId = $request->currency_id ?: ($directExpense->currency_id ?? null);
            $rate = $request->currency_rate ?: ($directExpense->exchange_rate ?? null);

            if ($selectedCurrencyId && $rate && (float)$rate > 0) {
                $payment->currency_id = $selectedCurrencyId;
                $payment->currency_rate = (float) $rate;
                $payment->currency_amount = $inputAmount; // original amount in selected currency
                $payment->amount = round($inputAmount * (float)$rate, 2); // store AED
            } else {
                $payment->currency_id = null;
                $payment->currency_rate = null;
                $payment->currency_amount = null;
                $payment->amount = $inputAmount; // already AED
            }
            $payment->account_id = $request->account_id;
            $payment->reference = $request->reference;
            $payment->description = $request->description;
            // Status remains 0 (unpaid) - cannot change status via edit

            $payment->save();

            // Recalculate parent direct expense payment status (0 unpaid, 2 partial, 4 paid)
            $this->recalculateDirectExpenseStatus($directExpense);

            DB::commit();

            return redirect()->route('direct_expense_payments.index')
                ->with('success', __('Payment updated successfully.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function sendPayment(Request $request, $paymentId)
    {
        if (!\Auth::user()->can('manage payment') && !\Auth::user()->can('manage expense')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $payment = DirectExpensePayment::with('directExpense', 'vendor')->find($paymentId);
        if (!$payment || $payment->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Payment not found.'));
        }

        $bankAccount = BankAccount::find($payment->account_id);
        $vendor = Vender::find($payment->vendor_id);
        $sendDate = $request->query('send_date', $payment->date);

        try {
            DB::beginTransaction();

            if ($payment->status == 2) {
                DB::rollBack();
                return redirect()->back()->with('error', __('Payment already received.'));
            }

            $payment->status = 2;
            $payment->save();

            // Recalculate parent direct expense payment status (0 unpaid, 2 partial, 4 paid)
            if ($payment->direct_expense_id) {
                $linkedExpense = DirectExpense::where('id', $payment->direct_expense_id)
                    ->where('created_by', \Auth::user()->creatorId())
                    ->with('items')
                    ->first();
                if ($linkedExpense) {
                    $this->recalculateDirectExpenseStatus($linkedExpense);
                }
            }

            if ($vendor) {
                $vendor->total_paid = ($vendor->total_paid ?? 0) + $payment->amount;
                $vendor->save();
            }

            // Get new voucher ID
            $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())
                ->orderBy('vid', 'desc')
                ->first();
            
            $newVoucherId = $latestVoucher ? ($latestVoucher->vid + 1) : 1;

            $existingRecord = GeneralLedger::where('vid', $newVoucherId)
                ->where('created_by', \Auth::user()->creatorId())
                ->exists();

            if ($existingRecord) {
                DB::rollBack();
                return redirect()->back()->with('error', __("Something went wrong, please try again."));
            }

            if (!$bankAccount || $bankAccount->chart_account_id == 0) {
                DB::rollBack();
                return redirect()->back()->with('error', __("Bank account chart of account is not set."));
            }

            // Debit vendor account (reduce vendor liability)
            $debitEntry = new GeneralLedger();
            $debitEntry->vid = $newVoucherId;
            $debitEntry->account = $vendor->chart_account_id;
            $debitEntry->type = 'Direct Expense Payment #' . $payment->id;
            $debitEntry->ref_number = 'Direct Expense Payment #' . $payment->id;
            $debitEntry->debit = $payment->amount;
            $debitEntry->credit = 0;
            $debitEntry->ref_id = $payment->direct_expense_id;
            $debitEntry->user_id = $vendor->id;
            $debitEntry->created_by = \Auth::user()->creatorId();
            $debitEntry->balance = $vendor->balance ?? 0;
            $debitEntry->send_date = $sendDate;
            $debitEntry->reference = 'Direct Expense Payment';
            $debitEntry->save();

            // Credit bank account (reduce bank balance)
            $creditEntry = new GeneralLedger();
            $creditEntry->vid = $newVoucherId;
            $creditEntry->account = $bankAccount->chart_account_id;
            $creditEntry->type = 'Direct Expense Payment #' . $payment->id;
            $creditEntry->ref_number = 'Direct Expense Payment #' . $payment->id;
            $creditEntry->debit = 0;
            $creditEntry->credit = $payment->amount;
            $creditEntry->ref_id = $payment->direct_expense_id;
            $creditEntry->user_id = 0;
            $creditEntry->created_by = \Auth::user()->creatorId();
            $creditEntry->send_date = $sendDate;
            $creditEntry->reference = 'Direct Expense Payment';
            $creditEntry->save();

            if ($vendor) {
                Utility::updateUserBalance('vendor', $vendor->id, $payment->amount, 'debit');
            }

            DB::commit();
            return redirect()->back()->with('success', __('Payment successfully received.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        if (!\Auth::user()->can('delete payment') && !\Auth::user()->can('manage expense')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $payment = DirectExpensePayment::with(['directExpense', 'vendor', 'bankAccount'])
            ->where('created_by', \Auth::user()->creatorId())
            ->find($id);
        
        if (!$payment) {
            return redirect()->back()->with('error', __('Payment not found.'));
        }

        try {
            DB::beginTransaction();

            // Store payment data before deletion for recalculation
            $directExpenseId = $payment->direct_expense_id;
            $paymentStatus = $payment->status;
            $paymentAmount = $payment->amount;
            $paymentDate = $payment->date;
            $vendorId = $payment->vendor_id;
            $accountId = $payment->account_id;

            // If payment is paid (status = 2), reverse ledger entries BEFORE deletion
            if ($paymentStatus == 2) {
                $vendor = Vender::find($vendorId);
                $bankAccount = BankAccount::find($accountId);
                
                if (!$vendor) {
                    DB::rollBack();
                    return redirect()->back()->with('error', __("Vendor not found."));
                }
                
                if (!$bankAccount || $bankAccount->chart_account_id == 0) {
                    DB::rollBack();
                    return redirect()->back()->with('error', __("Bank account chart of account is not set."));
                }

                // Get new voucher ID for reversal entries
                $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())
                    ->orderBy('vid', 'desc')
                    ->first();
                
                $newVoucherId = $latestVoucher ? ($latestVoucher->vid + 1) : 1;

                // Check if voucher ID already exists (safety check)
                $existingRecord = GeneralLedger::where('vid', $newVoucherId)
                    ->where('created_by', \Auth::user()->creatorId())
                    ->exists();

                if ($existingRecord) {
                    // Try next voucher ID
                    $newVoucherId = $newVoucherId + 1;
                    $existingRecord = GeneralLedger::where('vid', $newVoucherId)
                        ->where('created_by', \Auth::user()->creatorId())
                        ->exists();
                    
                    if ($existingRecord) {
                        DB::rollBack();
                        return redirect()->back()->with('error', __("Something went wrong, please try again."));
                    }
                }

                // Reverse: Credit vendor account (increase vendor liability)
                // Original: Debit vendor (reduce liability) -> Reverse: Credit vendor (increase liability)
                $reverseDebitEntry = new GeneralLedger();
                $reverseDebitEntry->vid = $newVoucherId;
                $reverseDebitEntry->account = $vendor->chart_account_id;
                $reverseDebitEntry->type = 'Direct Expense Payment Reversal #' . $payment->id;
                $reverseDebitEntry->ref_number = 'Direct Expense Payment Reversal #' . $payment->id;
                $reverseDebitEntry->debit = 0;
                $reverseDebitEntry->credit = $paymentAmount; // Reverse: credit instead of debit
                $reverseDebitEntry->ref_id = $directExpenseId;
                $reverseDebitEntry->user_id = $vendorId;
                $reverseDebitEntry->created_by = \Auth::user()->creatorId();
                $reverseDebitEntry->balance = $vendor->balance ?? 0;
                $reverseDebitEntry->send_date = $paymentDate;
                $reverseDebitEntry->reference = 'Direct Expense Payment Reversal';
                $reverseDebitEntry->save();

                // Reverse: Debit bank account (increase bank balance)
                // Original: Credit bank (reduce balance) -> Reverse: Debit bank (increase balance)
                $reverseCreditEntry = new GeneralLedger();
                $reverseCreditEntry->vid = $newVoucherId;
                $reverseCreditEntry->account = $bankAccount->chart_account_id;
                $reverseCreditEntry->type = 'Direct Expense Payment Reversal #' . $payment->id;
                $reverseCreditEntry->ref_number = 'Direct Expense Payment Reversal #' . $payment->id;
                $reverseCreditEntry->debit = $paymentAmount; // Reverse: debit instead of credit
                $reverseCreditEntry->credit = 0;
                $reverseCreditEntry->ref_id = $directExpenseId;
                $reverseCreditEntry->user_id = 0;
                $reverseCreditEntry->created_by = \Auth::user()->creatorId();
                $reverseCreditEntry->send_date = $paymentDate;
                $reverseCreditEntry->reference = 'Direct Expense Payment Reversal';
                $reverseCreditEntry->save();

                // Reverse vendor balance update (credit to increase vendor liability)
                Utility::updateUserBalance('vendor', $vendorId, $paymentAmount, 'credit');
                
                // Reverse vendor total_paid if it was updated
                if ($vendor && isset($vendor->total_paid)) {
                    $vendor->total_paid = max(0, ($vendor->total_paid ?? 0) - $paymentAmount);
                    $vendor->save();
                }
            }

            // Delete receipt file if exists
            if ($payment->add_receipt && file_exists(public_path('uploads/direct_expense_payment/' . $payment->add_receipt))) {
                unlink(public_path('uploads/direct_expense_payment/' . $payment->add_receipt));
            }

            // Delete the payment FIRST, then recalculate status
            $payment->delete();

            // CRITICAL FIX: Recalculate parent direct expense payment status AFTER deletion
            // This ensures the deleted payment is NOT included in the calculation
            if ($directExpenseId) {
                $linkedExpense = DirectExpense::where('id', $directExpenseId)
                    ->where('created_by', \Auth::user()->creatorId())
                    ->with('items')
                    ->first();
                if ($linkedExpense) {
                    $this->recalculateDirectExpenseStatus($linkedExpense);
                }
            }

            DB::commit();
            return redirect()->route('direct_expense_payments.index')
                ->with('success', __('Payment deleted successfully.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
    private function recalculateDirectExpenseStatus(DirectExpense $expense): void
    {
        $total = $expense->items()->sum('amount');
        $paid = DirectExpensePayment::where('direct_expense_id', $expense->id)
            ->where('status', 2) // received
            ->sum('amount');

        $newStatus = 0; // unpaid
        if ($paid > 0 && $paid < $total) {
            $newStatus = 2; // partially paid
        } elseif ($total > 0 && $paid >= $total) {
            $newStatus = 4; // paid
        }

        $expense->payment_status = $newStatus;
        $expense->save();
    }
}

