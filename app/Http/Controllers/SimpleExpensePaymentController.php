<?php

namespace App\Http\Controllers;

use App\Models\SimpleExpensePayment;
use App\Models\SimpleExpense;
use App\Models\BankAccount;
use App\Models\Vender;
use App\Models\Currency;
use App\Models\GeneralLedger;
use App\Models\Utility;
use App\Models\ChartOfAccount;
use App\Models\Tax;
use App\Models\ExpenseAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

class SimpleExpensePaymentController extends Controller
{
    public function index(Request $request)
    {
        if (!\Auth::user()->can('manage payment') && !\Auth::user()->can('manage bill')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $userId = \Auth::user()->creatorId();
        $vendors = Vender::where('created_by', $userId)->get()->pluck('name', 'id');
        $vendors->prepend('Select Vendor', '');

        $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))
            ->where('created_by', $userId)
            ->get()
            ->pluck('name', 'id');
        $accounts->prepend('Select Account', '');

        $expenses = SimpleExpense::where('created_by', $userId)->get()->pluck('expense_id', 'id');
        $expenses->prepend(__('Select Service Bill'), '');

        $status = array_filter(SimpleExpensePayment::$statues);
        
        $query = SimpleExpensePayment::where('created_by', $userId)
            ->with(['expense.vender', 'bankAccount', 'currency']);

        if (!empty($request->expense_id)) {
            $query->where('expense_id', $request->expense_id);
        }

        if (!empty($request->account)) {
            $query->where('account_id', $request->account);
        }

        if (!empty($request->status) || $request->status === '0') {
            $query->where('status', $request->status);
        }

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

        $payments = $query->orderByDesc('id')->paginate(50);

        return view('simple_expense_payments.index', compact('payments', 'accounts', 'vendors', 'expenses', 'status'));
    }

    public function create(Request $request)
    {
        if (!\Auth::user()->can('create payment') && !\Auth::user()->can('manage bill')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $expenses = SimpleExpense::where('created_by', \Auth::user()->creatorId())
            ->with('vender')
            ->get()
            ->mapWithKeys(function ($expense) {
                return [$expense->id => $expense->expense_id . ' - ' . ($expense->vender->name ?? 'N/A')];
            });
        $expenses->prepend(__('Select Service Bill'), '');

        $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))
            ->where('created_by', \Auth::user()->creatorId())
            ->get()
            ->pluck('name', 'id');

        $currencies = Currency::get()->pluck('name', 'id');
        $currencies->prepend('AED', '');

        $selectedExpenseId = $request->get('expense_id');

        return view('simple_expense_payments.create', compact('expenses', 'accounts', 'currencies', 'selectedExpenseId'));
    }

    public function store(Request $request)
    {
        if (!\Auth::user()->can('create payment') && !\Auth::user()->can('manage bill')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = \Validator::make(
            $request->all(),
            [
                'expense_id' => 'required|exists:simple_expenses,id',
                'date' => 'required|date',
                'amount' => 'required|numeric|min:0.01',
                'account_id' => 'required|exists:bank_accounts,id',
            ]
        );

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        $expense = SimpleExpense::where('id', $request->expense_id)
            ->where('created_by', \Auth::user()->creatorId())
            ->first();

        if (!$expense) {
            return redirect()->back()->with('error', __('Service Bill not found.'));
        }

        try {
            DB::beginTransaction();

            $payment = new SimpleExpensePayment();
            $payment->expense_id = $request->expense_id;
            $payment->date = $request->date;
            
            // Handle currency conversion
            $inputAmount = (float) $request->amount;
            $selectedCurrencyId = $request->currency_id ?? null;
            $rate = $request->currency_rate ?? null;

            if ($selectedCurrencyId && $rate && (float)$rate > 0) {
                $payment->currency_id = $selectedCurrencyId;
                $payment->currency_rate = (float) $rate;
                $payment->amount_in_currency = $inputAmount;
                $payment->amount = round($inputAmount * (float)$rate, 2);
            } else {
                $payment->currency_id = $expense->currency_id;
                $payment->currency_rate = $expense->exchange_rate ?? 1;
                $payment->amount_in_currency = null;
                $payment->amount = $inputAmount;
            }

            $payment->account_id = $request->account_id;
            $payment->payment_method = 0;
            $payment->reference = $request->reference ?? null;
            $payment->description = $request->description ?? null;
            $payment->status = 0; // Draft
            $payment->created_by = \Auth::user()->creatorId();

            if ($request->hasFile('add_receipt')) {
                $document = $request->file('add_receipt');
                $fileName = time() . "_" . preg_replace('/[^A-Za-z0-9\-]/', '', $document->getClientOriginalName());
                $payment->add_receipt = $fileName;
                $dir = 'uploads/simple_expense_payment';
                if (!file_exists(public_path($dir))) {
                    mkdir(public_path($dir), 0755, true);
                }
                $document->move(public_path($dir), $fileName);
            }

            $payment->save();

            DB::commit();

            return redirect()->route('simple-expense-payments.index')
                ->with('success', __('Payment created successfully.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        if (!\Auth::user()->can('view payment') && !\Auth::user()->can('manage bill')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $id = Crypt::decrypt($id);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Payment Not Found.'));
        }

        $payment = SimpleExpensePayment::with(['expense.vender', 'bankAccount', 'currency'])
            ->where('created_by', \Auth::user()->creatorId())
            ->find($id);

        if (!$payment) {
            return redirect()->back()->with('error', __('Payment not found.'));
        }

        return view('simple_expense_payments.show', compact('payment'));
    }

    public function edit($id)
    {
        if (!\Auth::user()->can('edit payment') && !\Auth::user()->can('manage bill')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $id = Crypt::decrypt($id);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Payment Not Found.'));
        }

        $payment = SimpleExpensePayment::where('created_by', \Auth::user()->creatorId())->find($id);

        if (!$payment) {
            return redirect()->back()->with('error', __('Payment not found.'));
        }

        if ($payment->status == 2) {
            return redirect()->back()->with('error', __('Cannot edit paid payment.'));
        }

        $expenses = SimpleExpense::where('created_by', \Auth::user()->creatorId())
            ->with('vender')
            ->get()
            ->mapWithKeys(function ($expense) {
                return [$expense->id => $expense->expense_id . ' - ' . ($expense->vender->name ?? 'N/A')];
            });
        $expenses->prepend(__('Select Service Bill'), '');

        $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))
            ->where('created_by', \Auth::user()->creatorId())
            ->get()
            ->pluck('name', 'id');

        $currencies = Currency::get()->pluck('name', 'id');
        $currencies->prepend('AED', '');

        // Load the expense and its accounts
        $expense = SimpleExpense::with('accounts')->find($payment->expense_id);
        $expenseAccounts = [];
        if ($expense && $expense->accounts) {
            foreach ($expense->accounts as $acc) {
                // Convert price back to original currency amount if needed
                $amount = $acc->price;
                if ($expense->currency_id && $expense->exchange_rate && $expense->exchange_rate > 0) {
                    $amount = $acc->price / $expense->exchange_rate;
                }
                $expenseAccounts[] = [
                    'chart_account_id' => $acc->chart_account_id,
                    'amount' => $amount,
                    'description' => $acc->description,
                ];
            }
        }

        // Load chart accounts for the repeater
        $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
            ->where('created_by', \Auth::user()->creatorId())->get()
            ->pluck('code_name', 'id');
        $chartAccounts->prepend('Select Account', '');

        // Load taxes
        $fullTax = Tax::where('created_by', \Auth::user()->creatorId())->get();

        return view('simple_expense_payments.edit', compact('payment', 'expenses', 'accounts', 'currencies', 'expense', 'expenseAccounts', 'chartAccounts', 'fullTax'));
    }

    public function update(Request $request, $id)
    {
        if (!\Auth::user()->can('edit payment') && !\Auth::user()->can('manage bill')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $id = Crypt::decrypt($id);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Payment Not Found.'));
        }

        $payment = SimpleExpensePayment::where('created_by', \Auth::user()->creatorId())->find($id);

        if (!$payment) {
            return redirect()->back()->with('error', __('Payment not found.'));
        }

        if ($payment->status == 2) {
            return redirect()->back()->with('error', __('Cannot edit paid payment.'));
        }

        $validator = \Validator::make(
            $request->all(),
            [
                'expense_id' => 'required|exists:simple_expenses,id',
                'date' => 'required|date',
                'amount' => 'required|numeric|min:0.01',
                'account_id' => 'required|exists:bank_accounts,id',
                'accounts' => 'sometimes|array|min:1',
                'accounts.*.chart_account_id' => 'required_with:accounts',
                'accounts.*.amount' => 'required_with:accounts|numeric|min:0.01',
            ]
        );

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        try {
            DB::beginTransaction();

            $payment->expense_id = $request->expense_id;
            $payment->date = $request->date;
            
            // Handle currency conversion
            $inputAmount = (float) $request->amount;
            $selectedCurrencyId = $request->currency_id ?? null;
            $rate = $request->currency_rate ?? null;

            if ($selectedCurrencyId && $rate && (float)$rate > 0) {
                $payment->currency_id = $selectedCurrencyId;
                $payment->currency_rate = (float) $rate;
                $payment->amount_in_currency = $inputAmount;
                $payment->amount = round($inputAmount * (float)$rate, 2);
            } else {
                $expense = SimpleExpense::find($request->expense_id);
                $payment->currency_id = $expense->currency_id ?? null;
                $payment->currency_rate = $expense->exchange_rate ?? 1;
                $payment->amount_in_currency = null;
                $payment->amount = $inputAmount;
            }

            $payment->account_id = $request->account_id;
            $payment->reference = $request->reference ?? null;
            $payment->description = $request->description ?? null;

            if ($request->hasFile('add_receipt')) {
                // Delete old receipt
                if ($payment->add_receipt && file_exists(public_path('uploads/simple_expense_payment/' . $payment->add_receipt))) {
                    unlink(public_path('uploads/simple_expense_payment/' . $payment->add_receipt));
                }

                $document = $request->file('add_receipt');
                $fileName = time() . "_" . preg_replace('/[^A-Za-z0-9\-]/', '', $document->getClientOriginalName());
                $payment->add_receipt = $fileName;
                $dir = 'uploads/simple_expense_payment';
                if (!file_exists(public_path($dir))) {
                    mkdir(public_path($dir), 0755, true);
                }
                $document->move(public_path($dir), $fileName);
            }

            $payment->save();

            // Update expense accounts if provided
            if ($request->has('accounts') && is_array($request->accounts) && count($request->accounts) > 0) {
                $expense = SimpleExpense::find($request->expense_id);
                
                if ($expense && $expense->created_by == \Auth::user()->creatorId()) {
                    // Delete old ledger entries for expense accounts
                    GeneralLedger::where('ref_id', $expense->id)
                        ->where(function ($query) {
                            SimpleExpense::applyGeneralLedgerHeadExpenseTypes($query);
                        })
                        ->where('created_by', \Auth::user()->creatorId())
                        ->delete();

                    // Get new voucher ID
                    $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
                    $newVoucherId = $latestVoucher ? $latestVoucher->vid + 1 : 1;

                    // Delete old expense accounts
                    ExpenseAccount::where('ref_id', $expense->id)->delete();

                    // Process new accounts
                    $total_amount = 0;
                    $accounts = $request->accounts;

                    foreach ($accounts as $accountData) {
                        $account_amount = $accountData['amount'];
                        
                        // Apply currency conversion if expense has currency
                        if ($expense->currency_id && $expense->exchange_rate && $expense->exchange_rate > 0) {
                            $account_amount = $account_amount * $expense->exchange_rate;
                        }

                        // Create expense account
                        $expenseAccount = new ExpenseAccount();
                        $expenseAccount->chart_account_id = $accountData['chart_account_id'];
                        $expenseAccount->price = $account_amount;
                        $expenseAccount->description = $accountData['description'] ?? '';
                        $expenseAccount->type = 'Expense';
                        $expenseAccount->ref_id = $expense->id;
                        $expenseAccount->save();

                        // Create ledger entry
                        $accountEntry = new GeneralLedger();
                        $accountEntry->vid = $newVoucherId;
                        $accountEntry->account = $accountData['chart_account_id'];
                        $accountEntry->type = $expense->expense_id;
                        $accountEntry->ref_number = $expense->expense_id;
                        $accountEntry->debit = $account_amount;
                        $accountEntry->credit = 0;
                        $accountEntry->ref_id = $expense->id;
                        $accountEntry->user_id = 0;
                        $accountEntry->created_by = \Auth::user()->creatorId();
                        $accountEntry->send_date = $expense->expense_date;
                        $accountEntry->reference = SimpleExpense::REF_EXPENSE;
                        $accountEntry->save();

                        $total_amount += $account_amount;
                    }

                    // Calculate tax
                    $headerTaxRate = 0;
                    $taxIds = $expense->tax_id ? explode(',', $expense->tax_id) : [];
                    if (!empty($taxIds)) {
                        foreach ($taxIds as $tid) {
                            $t = Tax::find($tid);
                            if ($t) {
                                $headerTaxRate += (float)$t->rate;
                            }
                        }
                    }
                    $headerTaxAmount = $total_amount * ($headerTaxRate / 100);

                    // Create VAT entries
                    if ($headerTaxAmount > 0 && !empty($taxIds)) {
                        foreach ($taxIds as $tid) {
                            $tax = Tax::find($tid);
                            if ($tax && $tax->chart_account_id) {
                                $taxRate = (float)$tax->rate;
                                $taxAmount = $total_amount * ($taxRate / 100);
                                
                                $taxEntry = new GeneralLedger();
                                $taxEntry->vid = $newVoucherId;
                                $taxEntry->account = $tax->chart_account_id;
                                $taxEntry->type = $expense->expense_id;
                                $taxEntry->ref_number = $expense->expense_id;
                                $taxEntry->debit = $taxAmount;
                                $taxEntry->credit = 0;
                                $taxEntry->ref_id = $expense->id;
                                $taxEntry->user_id = 0;
                                $taxEntry->created_by = \Auth::user()->creatorId();
                                $taxEntry->send_date = $expense->expense_date;
                                $taxEntry->reference = SimpleExpense::REF_TAX;
                                $taxEntry->save();
                            }
                        }
                    }

                    // Update vendor account entry
                    $vendorAccountId = Vender::where('id', $expense->vender_id)->first()->chart_account_id ?? null;
                    if ($vendorAccountId) {
                        $grandTotal = $total_amount + $headerTaxAmount;
                        
                        $vendorEntry = new GeneralLedger();
                        $vendorEntry->vid = $newVoucherId;
                        $vendorEntry->account = $vendorAccountId;
                        $vendorEntry->type = $expense->expense_id;
                        $vendorEntry->ref_number = $expense->expense_id;
                        $vendorEntry->debit = 0;
                        $vendorEntry->credit = $grandTotal;
                        $vendorEntry->ref_id = $expense->id;
                        $vendorEntry->user_id = $expense->vender_id;
                        $vendorEntry->user_type = 'vendor';
                        $vendorEntry->created_by = \Auth::user()->creatorId();
                        $vendorEntry->send_date = $expense->expense_date;
                        $vendorEntry->reference = SimpleExpense::REF_EXPENSE;
                        $vendorEntry->save();
                    }
                }
            }

            DB::commit();

            return redirect()->route('simple-expense-payments.index')
                ->with('success', __('Payment updated successfully.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        if (!\Auth::user()->can('delete payment') && !\Auth::user()->can('manage bill')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $id = Crypt::decrypt($id);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Payment Not Found.'));
        }

        $payment = SimpleExpensePayment::where('created_by', \Auth::user()->creatorId())->find($id);

        if (!$payment) {
            return redirect()->back()->with('error', __('Payment not found.'));
        }

        if ($payment->status == 2) {
            return redirect()->back()->with('error', __('Cannot delete paid payment. Please reverse it first.'));
        }

        try {
            DB::beginTransaction();

            // Get expense before deleting payment - use relationship or find with trashed
            $expense = $payment->expense ?? SimpleExpense::withTrashed()->find($payment->expense_id);
            
            if (!$expense) {
                DB::rollBack();
                return redirect()->back()->with('error', __('Service Bill not found.'));
            }

            // Store expense_id and payment info before deleting
            $expenseId = $payment->expense_id;
            $paymentAmount = $payment->amount;
            $paymentStatus = $payment->status;
            $paymentId = $payment->payment_id;
            
            // Ensure expense belongs to the same creator
            if ($expense->created_by != \Auth::user()->creatorId()) {
                DB::rollBack();
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            // Delete receipt file if exists
            if ($payment->add_receipt && file_exists(public_path('uploads/simple_expense_payment/' . $payment->add_receipt))) {
                unlink(public_path('uploads/simple_expense_payment/' . $payment->add_receipt));
            }

            // Delete payment ledger entries
            GeneralLedger::where('ref_id', $expenseId)
                ->where(function ($query) {
                    SimpleExpense::applyGeneralLedgerPaymentTypes($query);
                })
                ->where(function ($query) use ($paymentId, $payment) {
                    if ($paymentId) {
                        $query->where('payment_id', $paymentId);
                    } else {
                        $query->where('payment_id', $payment->id);
                    }
                })
                ->where('created_by', \Auth::user()->creatorId())
                ->delete();

            // Reverse vendor balance if payment was processed
            if ($paymentStatus == 2) {
                $vendor = Vender::find($expense->vender_id);
                if ($vendor) {
                    Utility::updateUserBalance('vendor', $vendor->id, $paymentAmount, 'debit');
                }
            }

            $payment->delete();

            // Reload expense to ensure we have latest data
            $expense = SimpleExpense::find($expenseId);
            
            if (!$expense) {
                DB::rollBack();
                return redirect()->back()->with('error', __('Service Bill not found after payment deletion.'));
            }
            
            // Update expense payment status based on remaining payments
            $totalPaid = SimpleExpensePayment::where('expense_id', $expenseId)
                ->where('status', 2)
                ->sum('amount');
            $expenseTotal = $expense->getTotal();
            
            if ($totalPaid >= $expenseTotal) {
                $expense->payment_status = 4; // Paid
            } elseif ($totalPaid > 0) {
                $expense->payment_status = 2; // Partially Paid
            } else {
                $expense->payment_status = 0; // Unpaid
            }
            $expense->save();

            DB::commit();

            return redirect()->route('simple-expense-payments.index')
                ->with('success', __('Payment deleted successfully.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function sendPayment(Request $request, $id)
    {
        if (!\Auth::user()->can('manage payment') && !\Auth::user()->can('manage bill')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $id = Crypt::decrypt($id);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Payment Not Found.'));
        }

        $payment = SimpleExpensePayment::with(['expense.vender', 'bankAccount'])->find($id);
        
        if (!$payment || $payment->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Payment not found.'));
        }

        if ($payment->status == 2) {
            return redirect()->back()->with('error', __('Payment already received.'));
        }

        $bankAccount = BankAccount::find($payment->account_id);
        $expense = $payment->expense;
        $vendor = $expense->vender;
        $sendDate = $request->query('send_date', $payment->date);

        try {
            DB::beginTransaction();

            if (!$bankAccount || !$bankAccount->chart_account_id) {
                DB::rollBack();
                return redirect()->back()->with('error', __('Bank account chart of account is not set.'));
            }

            if (!$vendor || !$vendor->chart_account_id) {
                DB::rollBack();
                return redirect()->back()->with('error', __('Vendor account not found.'));
            }

            $payment->status = 2;
            $payment->save();

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

            // Debit vendor account (reduce vendor liability)
            $debitEntry = new GeneralLedger();
            $debitEntry->vid = $newVoucherId;
            $debitEntry->account = $vendor->chart_account_id;
            $debitEntry->type = SimpleExpense::REF_PAYMENT . ' ' . $expense->expense_id;
            $debitEntry->ref_number = SimpleExpense::REF_PAYMENT . ' ' . $expense->expense_id;
            $debitEntry->debit = $payment->amount;
            $debitEntry->credit = 0;
            $debitEntry->ref_id = $expense->id;
            $debitEntry->user_id = $vendor->id;
            $debitEntry->user_type = 'vendor';
            $debitEntry->payment_id = $payment->id;
            $debitEntry->created_by = \Auth::user()->creatorId();
            $debitEntry->balance = 0;
            $debitEntry->send_date = $sendDate;
            $debitEntry->reference = SimpleExpense::REF_PAYMENT;
            $debitEntry->save();

            // Credit bank account (reduce bank balance)
            $creditEntry = new GeneralLedger();
            $creditEntry->vid = $newVoucherId;
            $creditEntry->account = $bankAccount->chart_account_id;
            $creditEntry->type = SimpleExpense::REF_PAYMENT . ' ' . $expense->expense_id;
            $creditEntry->ref_number = SimpleExpense::REF_PAYMENT . ' ' . $expense->expense_id;
            $creditEntry->debit = 0;
            $creditEntry->credit = $payment->amount;
            $creditEntry->ref_id = $expense->id;
            $creditEntry->user_id = 0;
            $creditEntry->payment_id = $payment->id;
            $creditEntry->created_by = \Auth::user()->creatorId();
            $creditEntry->balance = 0;
            $creditEntry->send_date = $sendDate;
            $creditEntry->reference = SimpleExpense::REF_PAYMENT;
            $creditEntry->save();

            Utility::updateUserBalance('vendor', $vendor->id, $payment->amount, 'credit');

            // Update expense payment status
            $totalPaid = SimpleExpensePayment::where('expense_id', $expense->id)
                ->where('status', 2)
                ->sum('amount');
            $expenseTotal = $expense->getTotal();
            
            if ($totalPaid >= $expenseTotal) {
                $expense->payment_status = 4; // Paid
            } elseif ($totalPaid > 0) {
                $expense->payment_status = 2; // Partially Paid
            } else {
                $expense->payment_status = 0; // Unpaid
            }
            $expense->save();

            DB::commit();

            return redirect()->back()->with('success', __('Payment successfully received.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
