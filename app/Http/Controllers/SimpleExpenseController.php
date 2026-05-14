<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Currency;
use App\Models\ChartOfAccount;
use App\Models\CustomField;
use App\Models\ProductServiceCategory;
use App\Models\Utility;
use App\Models\Tax;
use App\Models\Vender;
use App\Models\GeneralLedger;
use App\Models\SimpleExpense;
use App\Models\ExpenseAccount;
use App\Models\SimpleExpensePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class SimpleExpenseController extends Controller
{
    public function expenseNumber()
    {
        return SimpleExpense::nextExpenseSequenceNumber(\Auth::user()->creatorId());
    }

    public function index(Request $request)
    {
        if (\Auth::user()->can('manage bill')) {
            $vender = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $vender->prepend('Select Vendor', '');

            $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())
                ->whereNotIn('type', ['product & service', 'income'])
                ->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');

            $query = SimpleExpense::where('created_by', '=', \Auth::user()->creatorId());

            if (!empty($request->bill_date)) {
                $date_range = explode(' to ', $request->bill_date);
                if (count($date_range) == 2) {
                    $query->whereBetween('expense_date', $date_range);
                }
            }

            if (!empty($request->category)) {
                $query->where('category_id', '=', $request->category);
            }

            $expenses = $query->get();

            return view('simple_expense.index', compact('expenses', 'vender', 'category'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create bill')) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'bill')->get();
            $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())
                ->whereNotIn('type', ['product & service', 'income'])
                ->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');

            $number = $this->expenseNumber();
            $expense_number = SimpleExpense::formatExpenseIdFromSequence($number);

            $venders = Vender::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $venders->prepend('Select Vender', '');

            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');

            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))
                ->where('created_by', \Auth::user()->creatorId())
                ->get()->pluck('name', 'id');

            $fullTax = Tax::where('created_by', \Auth::user()->creatorId())->get();
            $currency = Currency::get()->pluck('name', 'id');
            $currency->prepend('AED', '');
            
            return view('simple_expense.create', compact('venders', 'expense_number', 'category', 'customFields', 'chartAccounts', 'accounts', 'fullTax', 'currency'));
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
                    'vender_id' => 'required',
                    'expense_date' => 'required',
                    'category_id' => 'required',
                    'accounts' => 'required|array|min:1',
                    'accounts.*.chart_account_id' => 'required',
                    'accounts.*.amount' => 'required|numeric|min:0.01',
                    'attachment' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:5120',
                ]
            );
            
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            try {
                DB::beginTransaction();
                
                $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
                $newVoucherId = $latestVoucher ? $latestVoucher->vid + 1 : 1;

                $existingRecord = GeneralLedger::where('vid', $newVoucherId)->where('created_by', \Auth::user()->creatorId())->exists();
                if ($existingRecord) {
                    return redirect()->back()->with('error', __("Something went wrong, please try again."));
                }

                // Create expense
                $expense = new SimpleExpense();
                $nextExpenseId = SimpleExpense::formatExpenseIdFromSequence(
                    SimpleExpense::nextExpenseSequenceNumber(\Auth::user()->creatorId())
                );
                
                $expense->expense_id = $nextExpenseId;
                $expense->vender_id = $request->vender_id;
                $vendorAccountId = Vender::where('id', $request->vender_id)->first()->chart_account_id;
                $expense->expense_date = $request->expense_date;
                $expense->status = 4;
                $expense->payment_status = 4;
                $expense->type = 'Expense';
                $expense->user_type = 'vendor';
                $expense->due_date = $request->expense_date;
                $expense->category_id = $request->category_id;
                $expense->created_by = \Auth::user()->creatorId();
                $expense->tax_id = !empty($request->tax_id) ? implode(',', $request->tax_id) : '';
                $expense->currency_id = $request->currency_id ?? null;
                
                if ($request->filled('currency_id')) {
                    if ($request->filled('exchange_rate')) {
                        $expense->exchange_rate = $request->exchange_rate;
                    } else {
                        $currency = Currency::find($request->currency_id);
                        $expense->exchange_rate = $currency ? $currency->rate : 1;
                    }
                } else {
                    $expense->exchange_rate = 1;
                }
                if ($request->hasFile('attachment')) {
                    $attachmentName = time() . '_' . preg_replace('/\s+/', '_', $request->file('attachment')->getClientOriginalName());
                    $request->file('attachment')->storeAs('uploads/simple_expenses', $attachmentName, 'public');
                    $expense->attachment = $attachmentName;
                }
                $expense->save();

                // Process accounts
                $total_amount = 0;
                $accounts = $request->accounts;

                foreach ($accounts as $accountData) {
                    $account_amount = $accountData['amount'];
                    
                    if (!empty($request->currency_id)) {
                        $exchangeRate = $request->exchange_rate ?? optional(Currency::find($request->currency_id))->exchange_rate;
                        if ($exchangeRate) {
                            $account_amount = $account_amount * $exchangeRate;
                        }
                    }

                    // Create expense account
                    $expenseAccount = new ExpenseAccount();
                    $expenseAccount->chart_account_id = $accountData['chart_account_id'];
                    $expenseAccount->price = $account_amount;
                    $expenseAccount->description = $accountData['description'] ?? '';
                    $expenseAccount->type = 'Expense';
                    $expenseAccount->ref_id = $expense->id;
                    $expenseAccount->save();

                    // Create ledger entry for account
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

                // Calculate header-level tax
                $headerTaxRate = 0;
                if (!empty($request->tax_id)) {
                    foreach ($request->tax_id as $tid) {
                        $t = Tax::find($tid);
                        if ($t) {
                            $headerTaxRate += (float)$t->rate;
                        }
                    }
                }
                $headerTaxAmount = $total_amount * ($headerTaxRate / 100);
                $grandTotal = $total_amount + $headerTaxAmount;

                // Create VAT/Tax entries
                if ($headerTaxAmount > 0 && !empty($request->tax_id)) {
                    foreach ($request->tax_id as $tid) {
                        $tax = Tax::find($tid);
                        if ($tax && $tax->chart_account_id) {
                            $taxRate = (float)$tax->rate;
                            $taxAmount = $total_amount * ($taxRate / 100);
                            
                            $vatEntry = new GeneralLedger();
                            $vatEntry->vid = $newVoucherId;
                            $vatEntry->account = $tax->chart_account_id;
                            $vatEntry->type = $expense->expense_id;
                            $vatEntry->ref_number = $expense->expense_id;
                            $vatEntry->debit = $taxAmount;
                            $vatEntry->credit = 0;
                            $vatEntry->ref_id = $expense->id;
                            $vatEntry->user_id = 0;
                            $vatEntry->created_by = \Auth::user()->creatorId();
                            $vatEntry->send_date = $expense->expense_date;
                            $vatEntry->reference = SimpleExpense::REF_EXPENSE;
                            $vatEntry->save();
                        }
                    }
                }

                // Create vendor entry
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
                $vendorEntry->balance = 0;
                $vendorEntry->send_date = $expense->expense_date;
                $vendorEntry->reference = SimpleExpense::REF_EXPENSE;
                $vendorEntry->save();
                
                Utility::updateUserBalance('vendor', $expense->vender_id, $grandTotal, 'debit');

                // Handle payment
                $expensePayment = null;
                if (!$request->filled('no_payment')) {
                    $accountId = BankAccount::find($request->account_id);
                    if (!$accountId || !$accountId->chart_account_id) {
                        DB::rollBack();
                        return redirect()->back()->with('error', __('Bank account not found or chart account not configured.'));
                    }

                    $expensePayment = new SimpleExpensePayment();
                    $expensePayment->expense_id = $expense->id;
                    $expensePayment->date = $request->expense_date;
                    $expensePayment->amount = $grandTotal;
                    $expensePayment->account_id = $request->account_id;
                    $expensePayment->payment_method = 0;
                    $expensePayment->reference = 'NULL';
                    $expensePayment->description = 'NULL';
                    $expensePayment->created_by = \Auth::user()->creatorId();
                    $expensePayment->save();

                    Utility::updateUserBalance('vendor', $expense->vender_id, $grandTotal, 'credit');

                    // Bank account entry
                    $debitEntry = new GeneralLedger();
                    $debitEntry->vid = $newVoucherId + 1;
                    $debitEntry->account = $accountId->chart_account_id;
                    $debitEntry->type = SimpleExpense::REF_PAYMENT . ' ' . $expense->expense_id;
                    $debitEntry->ref_number = SimpleExpense::REF_PAYMENT . ' ' . $expense->expense_id;
                    $debitEntry->debit = 0;
                    $debitEntry->credit = $grandTotal;
                    $debitEntry->ref_id = $expense->id;
                    $debitEntry->user_id = 0;
                    $debitEntry->payment_id = $expensePayment->id;
                    $debitEntry->created_by = \Auth::user()->creatorId();
                    $debitEntry->balance = 0;
                    $debitEntry->send_date = $request->expense_date;
                    $debitEntry->reference = SimpleExpense::REF_PAYMENT;
                    $debitEntry->save();

                    // Vendor account entry
                    $creditEntry = new GeneralLedger();
                    $creditEntry->vid = $newVoucherId + 1;
                    $creditEntry->account = $vendorAccountId;
                    $creditEntry->type = SimpleExpense::REF_PAYMENT . ' ' . $expense->expense_id;
                    $creditEntry->ref_number = SimpleExpense::REF_PAYMENT . ' ' . $expense->expense_id;
                    $creditEntry->debit = $grandTotal;
                    $creditEntry->credit = 0;
                    $creditEntry->ref_id = $expense->id;
                    $creditEntry->user_id = $expense->vender_id;
                    $creditEntry->user_type = 'vendor';
                    $creditEntry->payment_id = $expensePayment->id;
                    $creditEntry->created_by = \Auth::user()->creatorId();
                    $creditEntry->send_date = $request->expense_date;
                    $creditEntry->reference = SimpleExpense::REF_PAYMENT;
                    $creditEntry->save();
                }

                DB::commit();
                return redirect()->route('simple-expense.index')->with('success', __('Service Bill successfully created.'));
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show($id)
    {
        if (\Auth::user()->can('show bill')) {
            try {
                $id = Crypt::decrypt($id);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Service Bill Not Found.'));
            }

            $expense = SimpleExpense::find($id);
            if (!empty($expense) && $expense->created_by == \Auth::user()->creatorId()) {
                $expensePayment = SimpleExpensePayment::where('expense_id', $expense->id)->first();
                $user = $expense->vender;

                $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))
                    ->where('created_by', \Auth::user()->creatorId())
                    ->get()->pluck('name', 'id');

                return view('simple_expense.view', compact('expense', 'user', 'expensePayment', 'accounts'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function simple_expense_ledger($expense_id)
    {
        try {
            if (\Auth::user()->can('ledger report')) {
                $start = date('Y-m-01');
                $end = date('Y-m-t');
                $chart_accounts = ChartOfAccount::where('created_by', \Auth::user()->creatorId())->get();
                $accounts = $chart_accounts->pluck('name', 'id');
                $generalLedgerData = GeneralLedger::selectRaw('vid, account, ref_id , type,user_id, SUM(credit) as total_credit, SUM(debit) as total_debit ,created_at,updated_at,send_date,deleted_qty,sub_product_id,user_type')
                    ->where('created_by', \Auth::user()->creatorId())
                    ->where('ref_id', $expense_id)
                    ->whereIn('reference', SimpleExpense::ledgerReferencesAll())
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
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function edit($id)
    {
        if (\Auth::user()->can('edit bill')) {
            try {
                $id = Crypt::decrypt($id);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Service Bill Not Found.'));
            }

            $expense = SimpleExpense::find($id);
            if (!empty($expense) && $expense->created_by == \Auth::user()->creatorId()) {
                $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())
                    ->whereNotIn('type', ['product & service', 'income'])
                    ->get()->pluck('name', 'id');
                $category->prepend('Select Category', '');

                $expense_number = $expense->expense_id;

                $venders = Vender::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

                $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                    ->where('created_by', \Auth::user()->creatorId())->get()
                    ->pluck('code_name', 'id');
                $chartAccounts->prepend('Select Account', '');

                $bankAccount = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))
                    ->where('created_by', \Auth::user()->creatorId())
                    ->get()->pluck('name', 'id');

                $expensePayment = SimpleExpensePayment::where('expense_id', $id)->first();
                $selectedAccount = null;
                if ($expensePayment && $expensePayment->account_id) {
                    $selectedAccount = BankAccount::find($expensePayment->account_id);
                }

                $expenseAccounts = $expense->accounts;
                $accounts = [];
                foreach ($expenseAccounts as $acc) {
                    // Convert price back to original currency amount if expense has currency
                    $amount = $acc->price;
                    if ($expense->currency_id && $expense->exchange_rate && $expense->exchange_rate > 0) {
                        $amount = $acc->price / $expense->exchange_rate;
                    }
                    $accounts[] = [
                        'chart_account_id' => $acc->chart_account_id,
                        'amount' => $amount,
                        'description' => $acc->description,
                    ];
                }

                $fullTax = Tax::where('created_by', '=', \Auth::user()->creatorId())->get();
                $currency = Currency::get()->pluck('name', 'id');
                $currency->prepend('AED', '');

                return view('simple_expense.edit', compact(
                    'venders',
                    'expense',
                    'expense_number',
                    'category',
                    'bankAccount',
                    'chartAccounts',
                    'accounts',
                    'selectedAccount',
                    'expensePayment',
                    'fullTax',
                    'currency'
                ));
            } else {
                return redirect()->back()->with('error', __('Service Bill Not Found.'));
            }
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, $id)
    {
        if (\Auth::user()->can('edit bill')) {
            $expense = SimpleExpense::find($id);

            if ($expense->created_by == \Auth::user()->creatorId()) {
                $expensePayment = SimpleExpensePayment::where('expense_id', $expense->id)->first();
                $rules = [
                    'vender_id' => 'required',
                    'expense_date' => 'required',
                    'category_id' => 'required',
                    'accounts' => 'required|array|min:1',
                    'accounts.*.chart_account_id' => 'required',
                    'accounts.*.amount' => 'required|numeric|min:0.01',
                    'attachment' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:5120',
                ];
                
                // If no payment exists and user wants to add payment, account_id is required
                // If payment exists and user doesn't want to remove it, account_id is required
                if ((!$expensePayment && $request->filled('add_payment')) || 
                    ($expensePayment && !$request->filled('no_payment'))) {
                    $rules['account_id'] = 'required|exists:bank_accounts,id';
                }
                
                $validator = \Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    return redirect()->route('simple-expense.index')->with('error', $messages->first());
                }

                try {
                    DB::beginTransaction();

                    // Delete old ledger entries
                    $latestVoucher = GeneralLedger::where('ref_id', $expense->id)
                        ->where(function ($query) {
                            SimpleExpense::applyGeneralLedgerHeadExpenseTypes($query);
                        })->where('created_by', \Auth::user()->creatorId())
                        ->first();
                    
                    if ($latestVoucher) {
                        $oldVid = $latestVoucher->vid;
                        GeneralLedger::where('ref_id', $expense->id)
                            ->where(function ($query) {
                                SimpleExpense::applyGeneralLedgerHeadExpenseTypes($query);
                            })->where('created_by', \Auth::user()->creatorId())->delete();
                    }

                    // Get new voucher ID
                    $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
                    $newVoucherId = $latestVoucher ? $latestVoucher->vid + 1 : 1;

                    // Update expense
                    $expense->vender_id = $request->vender_id;
                    $vendorAccountId = Vender::where('id', $request->vender_id)->first()->chart_account_id;
                    $expense->expense_date = $request->expense_date;
                    $expense->due_date = $request->expense_date;
                    $expense->category_id = $request->category_id;
                    $expense->currency_id = !empty($request->currency_id) ? $request->currency_id : null;
                    $expense->exchange_rate = !empty($request->exchange_rate) ? $request->exchange_rate : 1;
                    $expense->tax_id = !empty($request->tax_id) ? implode(',', $request->tax_id) : '';
                    if ($request->hasFile('attachment')) {
                        if (!empty($expense->attachment) && Storage::disk('public')->exists('uploads/simple_expenses/' . $expense->attachment)) {
                            Storage::disk('public')->delete('uploads/simple_expenses/' . $expense->attachment);
                        }
                        $attachmentName = time() . '_' . preg_replace('/\s+/', '_', $request->file('attachment')->getClientOriginalName());
                        $request->file('attachment')->storeAs('uploads/simple_expenses', $attachmentName, 'public');
                        $expense->attachment = $attachmentName;
                    }
                    $expense->save();

                    // Delete old accounts
                    ExpenseAccount::where('ref_id', $expense->id)->delete();

                    // Process new accounts
                    $total_amount = 0;
                    $accounts = $request->accounts;

                    foreach ($accounts as $accountData) {
                        $account_amount = $accountData['amount'];
                        
                        if (!empty($request->currency_id)) {
                            $exchangeRate = $request->exchange_rate ?? optional(Currency::find($request->currency_id))->exchange_rate;
                            if ($exchangeRate) {
                                $account_amount = $account_amount * $exchangeRate;
                            }
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
                    if (!empty($request->tax_id)) {
                        foreach ($request->tax_id as $tid) {
                            $t = Tax::find($tid);
                            if ($t) {
                                $headerTaxRate += (float)$t->rate;
                            }
                        }
                    }
                    $headerTaxAmount = $total_amount * ($headerTaxRate / 100);
                    $grandTotal = $total_amount + $headerTaxAmount;

                    // Create VAT entries
                    if ($headerTaxAmount > 0 && !empty($request->tax_id)) {
                        foreach ($request->tax_id as $tid) {
                            $tax = Tax::find($tid);
                            if ($tax && $tax->chart_account_id) {
                                $taxRate = (float)$tax->rate;
                                $taxAmount = $total_amount * ($taxRate / 100);
                                
                                $vatEntry = new GeneralLedger();
                                $vatEntry->vid = $newVoucherId;
                                $vatEntry->account = $tax->chart_account_id;
                                $vatEntry->type = $expense->expense_id;
                                $vatEntry->ref_number = $expense->expense_id;
                                $vatEntry->debit = $taxAmount;
                                $vatEntry->credit = 0;
                                $vatEntry->ref_id = $expense->id;
                                $vatEntry->user_id = 0;
                                $vatEntry->created_by = \Auth::user()->creatorId();
                                $vatEntry->send_date = $expense->expense_date;
                                $vatEntry->reference = SimpleExpense::REF_EXPENSE;
                                $vatEntry->save();
                            }
                        }
                    }

                    // Create vendor entry
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
                    $vendorEntry->balance = 0;
                    $vendorEntry->send_date = $expense->expense_date;
                    $vendorEntry->reference = SimpleExpense::REF_EXPENSE;
                    $vendorEntry->save();

                    Utility::updateUserBalance('vendor', $expense->vender_id, $grandTotal, 'debit');

                    // Handle payment updates
                    // Delete old payment ledger entries if they exist
                    $oldPaymentEntries = GeneralLedger::where('ref_id', $expense->id)
                        ->where(function ($query) {
                            SimpleExpense::applyGeneralLedgerPaymentTypes($query);
                        })->where('created_by', \Auth::user()->creatorId())
                        ->get();
                    
                    if ($oldPaymentEntries->count() > 0) {
                        $oldPaymentVid = $oldPaymentEntries->first()->vid;
                        GeneralLedger::where('ref_id', $expense->id)
                            ->where(function ($query) {
                                SimpleExpense::applyGeneralLedgerPaymentTypes($query);
                            })->where('created_by', \Auth::user()->creatorId())->delete();
                    }

                    // Handle payment removal
                    if ($expensePayment && $request->filled('no_payment')) {
                        // Delete payment ledger entries
                        GeneralLedger::where('ref_id', $expense->id)
                            ->where(function ($query) {
                                SimpleExpense::applyGeneralLedgerPaymentTypes($query);
                            })->where('created_by', \Auth::user()->creatorId())->delete();
                        
                        // Reverse vendor balance
                        Utility::updateUserBalance('vendor', $expense->vender_id, $expensePayment->amount, 'debit');
                        
                        $expensePayment->delete();
                        $expensePayment = null;
                    }
                    
                    $expensePayment = SimpleExpensePayment::where('expense_id', $expense->id)->first();
                    if ($expensePayment && !$request->filled('no_payment')) {
                        // Update existing payment
                        $oldAmount = $expensePayment->amount;
                        $expensePayment->amount = $grandTotal;
                        $expensePayment->account_id = $request->account_id;
                        $expensePayment->save();

                        // Create new payment ledger entries
                        $accountId = BankAccount::find($request->account_id);
                        if ($accountId && $accountId->chart_account_id) {
                            $paymentVid = $newVoucherId + 1;
                            
                            // Bank account entry
                            $debitEntry = new GeneralLedger();
                            $debitEntry->vid = $paymentVid;
                            $debitEntry->account = $accountId->chart_account_id;
                            $debitEntry->type = SimpleExpense::REF_PAYMENT . ' ' . $expense->expense_id;
                            $debitEntry->ref_number = SimpleExpense::REF_PAYMENT . ' ' . $expense->expense_id;
                            $debitEntry->debit = 0;
                            $debitEntry->credit = $grandTotal;
                            $debitEntry->ref_id = $expense->id;
                            $debitEntry->user_id = 0;
                            $debitEntry->payment_id = $expensePayment->id;
                            $debitEntry->created_by = \Auth::user()->creatorId();
                            $debitEntry->balance = 0;
                            $debitEntry->send_date = $expense->expense_date;
                            $debitEntry->reference = SimpleExpense::REF_PAYMENT;
                            $debitEntry->save();

                            // Vendor account entry
                            $creditEntry = new GeneralLedger();
                            $creditEntry->vid = $paymentVid;
                            $creditEntry->account = $vendorAccountId;
                            $creditEntry->type = SimpleExpense::REF_PAYMENT . ' ' . $expense->expense_id;
                            $creditEntry->ref_number = SimpleExpense::REF_PAYMENT . ' ' . $expense->expense_id;
                            $creditEntry->debit = $grandTotal;
                            $creditEntry->credit = 0;
                            $creditEntry->ref_id = $expense->id;
                            $creditEntry->user_id = $expense->vender_id;
                            $creditEntry->user_type = 'vendor';
                            $creditEntry->payment_id = $expensePayment->id;
                            $creditEntry->created_by = \Auth::user()->creatorId();
                            $creditEntry->send_date = $expense->expense_date;
                            $creditEntry->reference = SimpleExpense::REF_PAYMENT;
                            $creditEntry->save();
                        }
                    } elseif (!$expensePayment && $request->filled('add_payment')) {
                        // Create new payment if it doesn't exist and user wants to add payment
                        if (!$request->filled('account_id')) {
                            DB::rollBack();
                            return redirect()->back()->with('error', __('Account is required to add payment.'));
                        }
                        
                        $accountId = BankAccount::find($request->account_id);
                        if (!$accountId || !$accountId->chart_account_id) {
                            DB::rollBack();
                            return redirect()->back()->with('error', __('Bank account not found or chart account not configured.'));
                        }

                        $expensePayment = new SimpleExpensePayment();
                        $expensePayment->expense_id = $expense->id;
                        $expensePayment->date = $expense->expense_date;
                        $expensePayment->amount = $grandTotal;
                        $expensePayment->account_id = $request->account_id;
                        $expensePayment->payment_method = 0;
                        $expensePayment->reference = 'NULL';
                        $expensePayment->description = 'NULL';
                        $expensePayment->created_by = \Auth::user()->creatorId();
                        $expensePayment->save();

                        Utility::updateUserBalance('vendor', $expense->vender_id, $grandTotal, 'credit');

                        // Payment ledger entries
                        $paymentVid = $newVoucherId + 1;
                        
                        $debitEntry = new GeneralLedger();
                        $debitEntry->vid = $paymentVid;
                        $debitEntry->account = $accountId->chart_account_id;
                        $debitEntry->type = SimpleExpense::REF_PAYMENT . ' ' . $expense->expense_id;
                        $debitEntry->ref_number = SimpleExpense::REF_PAYMENT . ' ' . $expense->expense_id;
                        $debitEntry->debit = 0;
                        $debitEntry->credit = $grandTotal;
                        $debitEntry->ref_id = $expense->id;
                        $debitEntry->user_id = 0;
                        $debitEntry->payment_id = $expensePayment->id;
                        $debitEntry->created_by = \Auth::user()->creatorId();
                        $debitEntry->balance = 0;
                        $debitEntry->send_date = $expense->expense_date;
                        $debitEntry->reference = SimpleExpense::REF_PAYMENT;
                        $debitEntry->save();

                        $creditEntry = new GeneralLedger();
                        $creditEntry->vid = $paymentVid;
                        $creditEntry->account = $vendorAccountId;
                        $creditEntry->type = SimpleExpense::REF_PAYMENT . ' ' . $expense->expense_id;
                        $creditEntry->ref_number = SimpleExpense::REF_PAYMENT . ' ' . $expense->expense_id;
                        $creditEntry->debit = $grandTotal;
                        $creditEntry->credit = 0;
                        $creditEntry->ref_id = $expense->id;
                        $creditEntry->user_id = $expense->vender_id;
                        $creditEntry->user_type = 'vendor';
                        $creditEntry->payment_id = $expensePayment->id;
                        $creditEntry->created_by = \Auth::user()->creatorId();
                        $creditEntry->send_date = $expense->expense_date;
                        $creditEntry->reference = SimpleExpense::REF_PAYMENT;
                        $creditEntry->save();
                    }

                    DB::commit();
                    return redirect()->route('simple-expense.index')->with('success', __('Service Bill successfully updated.'));
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

    public function destroy(Request $request, $id)
    {
        if (\Auth::user()->can('delete bill')) {
            // try {
            //     $id = Crypt::decrypt($id);
            // } catch (\Throwable $th) {
            //     return redirect()->back()->with('error', __('Service Bill Not Found.'));
            // }

            $expense = SimpleExpense::find($id);
            if (!$expense || $expense->created_by != \Auth::user()->creatorId()) {
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            // Validate delete_date
            $deleteDate = $request->input('delete_date');
            if (!$deleteDate) {
                return redirect()->back()->with('error', __('Delete date is required.'));
            }

            // Validate that delete_date is greater than expense_date
            if (strtotime($deleteDate) <= strtotime($expense->expense_date)) {
                return redirect()->back()->with('error', __('Delete date must be greater than expense date.'));
            }

            try {
                DB::beginTransaction();

                // Get expense payment(s) before deletion
                $expensePayments = SimpleExpensePayment::where('expense_id', $expense->id)->get();
                $grandTotal = $expense->getTotal();
                $vendorAccountId = Vender::where('id', $expense->vender_id)->first()->chart_account_id ?? null;

                // Get new voucher ID
                $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
                $newVoucherId = $latestVoucher ? $latestVoucher->vid + 1 : 1;

                // Reverse vendor balance for payments if payment exists
                foreach ($expensePayments as $expensePayment) {
                    // Reverse vendor balance for payment
                    // Store: updateUserBalance('vendor', vender_id, grandTotal, 'credit')
                    // Destroy: updateUserBalance('vendor', vender_id, grandTotal, 'debit') (REVERSED)
                    Utility::updateUserBalance('vendor', $expense->vender_id, $expensePayment->amount, 'debit');
                }

                // Reverse expense ledger entries
                $ledgerEntries = GeneralLedger::where('ref_id', $expense->id)
                    ->where(function ($query) {
                        SimpleExpense::applyGeneralLedgerHeadExpenseTypes($query);
                    })
                    ->where('created_by', \Auth::user()->creatorId())
                    ->whereNull('payment_id') // Only expense entries, not payment entries
                    ->get();

                foreach ($ledgerEntries as $entry) {
                    $reverseEntry = new GeneralLedger();
                    $reverseEntry->vid = $newVoucherId;
                    $reverseEntry->account = $entry->account;
                    $reverseEntry->type = 'Delete ' . $entry->type;
                    $reverseEntry->ref_number = 'Delete ' . $entry->ref_number;
                    $reverseEntry->debit = $entry->credit;
                    $reverseEntry->credit = $entry->debit;
                    $reverseEntry->ref_id = $expense->id;
                    $reverseEntry->user_id = $entry->user_id;
                    $reverseEntry->user_type = $entry->user_type;
                    $reverseEntry->created_by = \Auth::user()->creatorId();
                    $reverseEntry->send_date = $deleteDate;
                    $reverseEntry->reference = SimpleExpense::REF_EXPENSE_DELETE;
                    $reverseEntry->save();
                }

                // Reverse vendor balance for expense
                // Store: updateUserBalance('vendor', vender_id, grandTotal, 'debit')
                // Destroy: updateUserBalance('vendor', vender_id, grandTotal, 'credit') (REVERSED)
                Utility::updateUserBalance('vendor', $expense->vender_id, $grandTotal, 'credit');

                // Reverse payment ledger entries (similar to store but reversed)
                foreach ($expensePayments as $expensePayment) {
                    $accountId = BankAccount::find($expensePayment->account_id);
                    
                    // Reverse: Bank account entry
                    // Store: debit = 0, credit = grandTotal
                    // Destroy: debit = grandTotal, credit = 0 (REVERSED)
                    if ($accountId && $accountId->chart_account_id) {
                        $reverseBankEntry = new GeneralLedger();
                        $reverseBankEntry->vid = $newVoucherId + 1;
                        $reverseBankEntry->account = $accountId->chart_account_id;
                        $reverseBankEntry->type = SimpleExpense::REF_PAYMENT_DELETE . ' ' . $expense->expense_id;
                        $reverseBankEntry->ref_number = SimpleExpense::REF_PAYMENT_DELETE . ' ' . $expense->expense_id;
                        $reverseBankEntry->debit = $expensePayment->amount;
                        $reverseBankEntry->credit = 0;
                        $reverseBankEntry->ref_id = $expense->id;
                        $reverseBankEntry->user_id = 0;
                        $reverseBankEntry->payment_id = $expensePayment->id;
                        $reverseBankEntry->created_by = \Auth::user()->creatorId();
                        $reverseBankEntry->balance = 0;
                        $reverseBankEntry->send_date = $deleteDate;
                        $reverseBankEntry->reference = SimpleExpense::REF_PAYMENT_DELETE;
                        $reverseBankEntry->save();
                    }

                    // Reverse: Vendor entry for payment
                    // Store: debit = grandTotal, credit = 0
                    // Destroy: debit = 0, credit = grandTotal (REVERSED)
                    if ($vendorAccountId) {
                        $reverseVendorPaymentEntry = new GeneralLedger();
                        $reverseVendorPaymentEntry->vid = $newVoucherId + 1;
                        $reverseVendorPaymentEntry->account = $vendorAccountId;
                        $reverseVendorPaymentEntry->type = SimpleExpense::REF_PAYMENT_DELETE . ' ' . $expense->expense_id;
                        $reverseVendorPaymentEntry->ref_number = SimpleExpense::REF_PAYMENT_DELETE . ' ' . $expense->expense_id;
                        $reverseVendorPaymentEntry->debit = 0;
                        $reverseVendorPaymentEntry->credit = $expensePayment->amount;
                        $reverseVendorPaymentEntry->ref_id = $expense->id;
                        $reverseVendorPaymentEntry->user_id = $expense->vender_id;
                        $reverseVendorPaymentEntry->user_type = 'vendor';
                        $reverseVendorPaymentEntry->payment_id = $expensePayment->id;
                        $reverseVendorPaymentEntry->created_by = \Auth::user()->creatorId();
                        $reverseVendorPaymentEntry->send_date = $deleteDate;
                        $reverseVendorPaymentEntry->reference = SimpleExpense::REF_PAYMENT_DELETE;
                        $reverseVendorPaymentEntry->save();
                    }
                }

                // Delete related records
                ExpenseAccount::where('ref_id', $expense->id)->delete();
                SimpleExpensePayment::where('expense_id', $expense->id)->delete();
                $expense->delete();

                DB::commit();
                return redirect()->route('simple-expense.index')->with('success', __('Service Bill successfully deleted.'));
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
