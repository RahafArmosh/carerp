<?php

namespace App\Http\Controllers;

use App\Models\DirectExpense;
use App\Models\DirectExpenseItem;
use App\Models\DirectExpensePayment;
use App\Models\ChartOfAccount;
use App\Models\SubProduct;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use App\Models\Vender;
use App\Models\GeneralLedger;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DirectExpenseController extends Controller
{
    public function index()
    {
        if (!\Auth::user()->can('manage expense') && !\Auth::user()->can('create bill')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $userId = \Auth::user()->creatorId();
        $expenses = DirectExpense::with(['vendor', 'currency', 'items.subProduct.productService.category', 'items.chartAccount'])
            ->where('created_by', $userId)
            ->orderByDesc('id')
            ->paginate(25);

        return view('direct_expenses.index', compact('expenses'));
    }

    public function search()
    {
        if (!\Auth::user()->can('create bill') && !\Auth::user()->can('manage expense')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $userId = \Auth::user()->creatorId();

        $invoices = \DB::table('invoices')->where('created_by', $userId)->orderByDesc('id')->get(['id','invoice_id']);
        $bills = \DB::table('bills')->where('created_by', $userId)->orderByDesc('id')->get(['id','bill_id']);
        $warehouses = \DB::table('warehouses')->where('created_by', $userId)->orderBy('name')->get(['id','name']);
        $customers = \DB::table('customers')->where('created_by', $userId)->orderBy('name')->get(['id','name']);
        $vendors = \DB::table('venders')->where('created_by', $userId)->orderBy('name')->get(['id','name']);
        $accounts = ChartOfAccount::where('created_by', $userId)->orderBy('name')->get(['id','name','code']);
        $taxes = \App\Models\Tax::where('created_by', $userId)->orderBy('name')->get(['id','name']);
        $currencies = \App\Models\Currency::orderBy('name')->get(['id','name','code']);
        $categories = ProductServiceCategory::where('created_by', $userId)->orderBy('name')->get(['id','name']);

        return view('direct_expenses.search', compact('invoices','bills','warehouses','customers','vendors','accounts','taxes','currencies','categories'));
    }

    public function doSearch(Request $request)
    {
        if (!\Auth::user()->can('create bill') && !\Auth::user()->can('manage expense')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $userId = \Auth::user()->creatorId();

        $query = \DB::table('sub_products as sp')
            ->join('product_services as ps', 'ps.id', '=', 'sp.product_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'sp.warehouse_id')
            ->leftJoin('invoice_products as ip', 'ip.sub_product_id', '=', 'sp.id')
            ->leftJoin('invoices as inv', 'inv.id', '=', 'ip.invoice_id')
            ->leftJoin('bill_products as bp', 'bp.sub_product_id', '=', 'sp.id')
            ->leftJoin('bills as b', 'b.id', '=', 'bp.bill_id')
            ->where('sp.created_by', $userId)
            ->where('sp.flag', '!=', 0)
            ->whereIn('sp.booked', [0, 1])
            ->select('sp.id','sp.chassis_no as product_no','sp.quantity','ps.name as product_name','w.name as warehouse_name');

        if ($request->filled('invoice_id')) {
            $query->where('inv.id', $request->invoice_id);
        }
        if ($request->filled('bill_id')) {
            $query->where('b.id', $request->bill_id);
        }
        if ($request->filled('vins')) {
            $vins = array_map('trim', preg_split('/\s+/', $request->vins));
            $query->whereIn('sp.chassis_no as product_no', $vins);
        }
        if ($request->filled('warehouse_id')) {
            $query->where('w.id', $request->warehouse_id);
        }
        if ($request->filled('customer_id')) {
            $query->join('invoice_products as ip2', 'ip2.sub_product_id', '=', 'sp.id')
                  ->join('invoices as inv2', 'inv2.id', '=', 'ip2.invoice_id')
                  ->where('inv2.customer_id', $request->customer_id);
        }
        if ($request->filled('vender_id')) {
            $query->join('bill_products as bp2', 'bp2.sub_product_id', '=', 'sp.id')
                  ->join('bills as b2', 'b2.id', '=', 'bp2.bill_id')
                  ->where('b2.vender_id', $request->vender_id);
        }

        $cars = $query->distinct()->get();

        $accounts = ChartOfAccount::where('created_by', $userId)->orderBy('name')->get(['id','name','code']);
        $vendors = \DB::table('venders')->where('created_by', $userId)->orderBy('name')->get(['id','name']);
        $taxes = \App\Models\Tax::where('created_by', $userId)->orderBy('name')->get(['id','name']);
        $currencies = \App\Models\Currency::orderBy('name')->get(['id','name','code']);
        $categories = ProductServiceCategory::where('created_by', $userId)->orderBy('name')->get(['id','name']);

        return view('direct_expenses.search', compact('cars','accounts','vendors','taxes','currencies','categories'));
    }

    public function store(Request $request)
    {
        if (!\Auth::user()->can('create bill') && !\Auth::user()->can('manage expense')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        // Transform simple submission (sub_product_ids + amount) into items array if needed
        $transformedItems = null;
        if (!$request->has('items') && $request->has('sub_product_ids')) {
            $amount = (float) $request->input('amount', 0);
            $description = $request->input('description');
            $headerChartAccountId = $request->input('chart_account_id');
            $transformedItems = [];
            foreach ((array) $request->input('sub_product_ids', []) as $sid) {
                $transformedItems[] = [
                    'sub_product_id' => $sid,
                    'amount' => $amount,
                    'description' => $description,
                    'chart_account_id' => $headerChartAccountId,
                ];
            }
            $request->merge(['items' => $transformedItems]);
        }

        $noPayment = $request->has('no_payment') && $request->no_payment == '1';
        
        $rules = [
            'vendor_id' => 'required|exists:venders,id',
            'expense_date' => 'required|date',
            'payment_date' => 'required|date',
            'tax_id' => 'nullable|array',
            'currency_id' => 'nullable|exists:currencies,id',
            'exchange_rate' => 'nullable|numeric|min:0',
            'chart_account_id' => 'nullable|exists:chart_of_accounts,id',
            'category_id' => 'nullable|exists:product_service_categories,id',
            'items' => 'required|array|min:1',
            'items.*.sub_product_id' => 'required|exists:sub_products,id',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.description' => 'nullable|string',
            'items.*.chart_account_id' => 'nullable|exists:chart_of_accounts,id',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:5120',
        ];

        if (!$noPayment) {
            $rules['payment_account_id'] = 'required|exists:bank_accounts,id';
        }

        $request->validate($rules);

        $createdBy = \Auth::user()->creatorId();
        $vendorAccountId = Vender::where('id', $request->vendor_id)->value('chart_account_id');
        
        // Get expense_date from request - use it for expense ledger entries
        // Check both 'expense_date' and fallback to request() method
        $expenseDate = $request->input('expense_date') ?: $request->get('expense_date');
        if (empty($expenseDate) || $expenseDate === null) {
            \Log::warning('DirectExpense: expense_date not provided in request, using today', [
                'expense_date_input' => $request->input('expense_date'),
                'expense_date_get' => $request->get('expense_date'),
                'all_request' => $request->all()
            ]);
            $expenseDate = now()->toDateString();
        }
        
        // Get payment_date from request - use it for payment ledger entries
        $paymentDate = $request->input('payment_date') ?: $request->get('payment_date');
        if (empty($paymentDate) || $paymentDate === null) {
            $paymentDate = now()->toDateString();
        }

        // Get the latest voucher id
        $latestVoucher = GeneralLedger::where('created_by', $createdBy)
            ->orderBy('vid', 'desc')
            ->value('vid');
        $nextVoucherId = ($latestVoucher ?? 0) + 1;

        // Generate expense number
        $expenseNumber = $request->expense_number ?? $this->expenseNumber();

        // Get exchange rate
        $exchangeRate = $request->exchange_rate ?? 1;
        if ($exchangeRate <= 0) {
            $exchangeRate = 1;
        }

        $attachmentName = null;
        if ($request->hasFile('attachment')) {
            $attachmentName = time() . '_' . preg_replace('/\s+/', '_', $request->file('attachment')->getClientOriginalName());
            $request->file('attachment')->storeAs('uploads/direct_expenses', $attachmentName, 'public');
        }

        // Create header
        $expense = DirectExpense::create([
            'expense_number' => $expenseNumber,
            'expense_date' => $expenseDate,
            'vendor_id' => $request->vendor_id,
            'tax_id' => $request->tax_id ? implode(',', $request->tax_id) : null,
            'currency_id' => $request->currency_id ?? null,
            'exchange_rate' => $exchangeRate,
            'attachment' => $attachmentName,
            'payment_status' => 0,
            'created_by' => $createdBy,
        ]);

        $totalAmount = 0;
        $totalTaxAmount = 0;

        // Create items and ledger entries
        foreach ($request->items as $itemData) {
            // Determine debit account
            $debitAccountId = $itemData['chart_account_id'] ?? null;
            if (empty($debitAccountId)) {
                // Try header account first
                $debitAccountId = $request->input('chart_account_id');
            }
            if (empty($debitAccountId)) {
                // Try explicit chosen category
                $explicitCategoryId = $request->input('category_id');
                if (!empty($explicitCategoryId)) {
                    $debitAccountId = ProductServiceCategory::where('id', $explicitCategoryId)->value('purchase_account_id');
                }
            }
            // Get sub product with product service and category
            $subProduct = SubProduct::with(['productService.category'])->find($itemData['sub_product_id']);
            $categoryId = optional($subProduct?->productService)->category_id;
            
            if (empty($debitAccountId)) {
                // Fallback to sub product's category purchase account
                $debitAccountId = ProductServiceCategory::where('id', $categoryId)->value('purchase_account_id');
            }

            // Get quantity from sub product
            $qty = $subProduct ? ($subProduct->quantity ?? 1) : 1;
            
            // Get category type
            $categoryType = optional($subProduct?->productService?->category)->type;
            
            // Store base amount (not multiplied) - this is the unit price
            $baseAmount = $itemData['amount'];
            
            // Calculate currency amount (base amount with exchange rate, not multiplied by qty)
            $currencyAmount = $request->currency_id ? ($baseAmount * $exchangeRate) : $baseAmount;
            
            // For ledger entries, use qty * amount if category type is "Qty product"
            $ledgerAmount = $currencyAmount;
            if ($categoryType === 'Qty product' && $qty > 0) {
                $ledgerAmount = $currencyAmount * $qty;
            }
            
            // Calculate tax on ledger amount (qty * amount for Qty product)
            $taxAmount = $request->tax_id ? (Tax::where('id', $request->tax_id)->value('rate') / 100) * $ledgerAmount : 0;
            $taxaccountId = $request->tax_id ? Tax::where('id', $request->tax_id)->value('chart_account_id') : null;
            $totalTaxAmount += $taxAmount;
            
            // Create item - store base amount (not multiplied)
            $item = DirectExpenseItem::create([
                'direct_expense_id' => $expense->id,
                'sub_product_id' => $itemData['sub_product_id'],
                'qty' => $qty,
                'amount' => $currencyAmount, // Base amount (unit price)
                'currency_amount' => $baseAmount, // Base amount in original currency
                'description' => $itemData['description'] ?? null,
                'chart_account_id' => $itemData['chart_account_id'] ?? $debitAccountId,
            ]);

            // Add to total amount for payment calculation (use ledger amount which includes qty multiplication for Qty product)
            $totalAmount += $ledgerAmount;

            // Credit vendor account - use ledger amount (qty * amount for Qty product)
            $creditEntry = new GeneralLedger();
            $creditEntry->vid = $nextVoucherId;
            $creditEntry->account = $vendorAccountId;
            $creditEntry->type = 'Direct Expense';
            $creditEntry->ref_number = \Auth::user()->expenseNumberFormat($expenseNumber);
            $creditEntry->debit = 0;
            $creditEntry->credit = $ledgerAmount + $taxAmount;
            $creditEntry->ref_id = $expense->id;
            $creditEntry->user_id = $request->vendor_id;
            $creditEntry->created_by = $createdBy;
            $creditEntry->send_date = $expenseDate;
            $creditEntry->reference = 'Direct Expense';
            $creditEntry->save();

            // Debit selected/purchase account - use ledger amount (qty * amount for Qty product)
            $debitEntry = new GeneralLedger();
            $debitEntry->vid = $nextVoucherId;
            $debitEntry->account = $debitAccountId;
            $debitEntry->type = 'Direct Expense';
            $debitEntry->ref_number = \Auth::user()->expenseNumberFormat($expenseNumber);
            $debitEntry->debit = $ledgerAmount;
            $debitEntry->credit = 0;
            $debitEntry->ref_id = $expense->id;
            $debitEntry->user_id = 0;
            $debitEntry->created_by = $createdBy;
            $debitEntry->send_date = $expenseDate;
            $debitEntry->reference = 'Direct Expense';
            $debitEntry->save();

            // Debit tax account - tax is calculated on ledger amount
            if ($taxaccountId && $taxAmount > 0) {
                $debitEntry = new GeneralLedger();
                $debitEntry->vid = $nextVoucherId;
                $debitEntry->account = $taxaccountId;
                $debitEntry->type = 'Direct Expense';
                $debitEntry->ref_number = \Auth::user()->expenseNumberFormat($expenseNumber);
                $debitEntry->debit = $taxAmount;
                $debitEntry->credit = 0;
                $debitEntry->ref_id = $expense->id;
                $debitEntry->user_id = 0;
                $debitEntry->created_by = $createdBy;
                $debitEntry->send_date = $expenseDate;
                $debitEntry->reference = 'Direct Expense';
                $debitEntry->save();
            }
        }

        // Create payment automatically if "Create without payment" is NOT checked
        if (!$noPayment && $request->filled('payment_account_id')) {
            $payment = new DirectExpensePayment();
            $payment->date = $paymentDate;
            $payment->amount = $totalAmount + $totalTaxAmount;
            $payment->account_id = $request->payment_account_id;
            $payment->direct_expense_id = $expense->id;
            $payment->vendor_id = $request->vendor_id;
            $payment->payment_method = 0;
            $payment->reference = 'Auto-created with expense';
            $payment->status = 2; // Mark as received since payment is being made
            $payment->description = 'Payment created automatically with direct expense';
            $payment->created_by = $createdBy;
            $payment->save();

            // Update payment status
            $expense->payment_status = 4; // Paid
            $expense->save();

            // Create ledger entries for payment
            $bankAccount = \App\Models\BankAccount::find($request->payment_account_id);
            if ($bankAccount && $bankAccount->chart_account_id) {
                $paymentVoucherId = $nextVoucherId + 1;

                // Debit vendor account (reduce vendor liability)
                $paymentDebitEntry = new GeneralLedger();
                $paymentDebitEntry->vid = $paymentVoucherId;
                $paymentDebitEntry->account = $vendorAccountId;
                $paymentDebitEntry->type = 'Direct Expense Payment #' . $payment->id;
                $paymentDebitEntry->ref_number = 'Direct Expense Payment #' . $payment->id;
                $paymentDebitEntry->debit = $totalAmount + $totalTaxAmount;
                $paymentDebitEntry->credit = 0;
                $paymentDebitEntry->ref_id = $expense->id;
                $paymentDebitEntry->user_id = $request->vendor_id;
                $paymentDebitEntry->created_by = $createdBy;
                $paymentDebitEntry->send_date = $paymentDate;
                $paymentDebitEntry->reference = 'Direct Expense Payment';
                $paymentDebitEntry->save();

                // Credit bank account (reduce bank balance)
                $paymentCreditEntry = new GeneralLedger();
                $paymentCreditEntry->vid = $paymentVoucherId;
                $paymentCreditEntry->account = $bankAccount->chart_account_id;
                $paymentCreditEntry->type = 'Direct Expense Payment #' . $payment->id;
                $paymentCreditEntry->ref_number = 'Direct Expense Payment #' . $payment->id;
                $paymentCreditEntry->debit = 0;
                $paymentCreditEntry->credit = $totalAmount + $totalTaxAmount;
                $paymentCreditEntry->ref_id = $expense->id;
                $paymentCreditEntry->user_id = 0;
                $paymentCreditEntry->created_by = $createdBy;
                $paymentCreditEntry->send_date = $paymentDate;
                $paymentCreditEntry->reference = 'Direct Expense Payment';
                $paymentCreditEntry->save();

                // Update vendor balance
                if ($vendor = Vender::find($request->vendor_id)) {
                    \App\Models\Utility::updateUserBalance('vendor', $vendor->id, $totalAmount, 'debit');
                }
            }
        }

        return redirect()->route('direct_expenses.index')->with('success', __('Direct expense saved successfully and posted to ledger.'));
    }

    public function show(DirectExpense $directExpense)
    {
        if ($directExpense->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $directExpense->load([
            'vendor',
            'items.subProduct.productService',
            'items.chartAccount',
            'items.subProduct.warehouse',
            'currency'
        ]);

        // Get tax names if tax_id exists
        $taxIds = $directExpense->getTaxIds();
        $taxes = [];
        if (!empty($taxIds)) {
            $taxes = \App\Models\Tax::whereIn('id', $taxIds)->get(['id', 'name', 'rate']);
        }

        return view('direct_expenses.show', compact('directExpense', 'taxes'));
    }

    public function ledger($directExpenseId)
    {
        try {
            if (\Auth::user()->can('ledger report')) {
                $start = date('Y-m-01');
                $end = date('Y-m-t');
                $chart_accounts = ChartOfAccount::where('created_by', \Auth::user()->creatorId())->get();
                $accounts = $chart_accounts->pluck('name', 'id');
                
                // Get both Direct Expense and Direct Expense Payment entries
                $generalLedgerData = GeneralLedger::selectRaw('vid, account, ref_id, type, user_id, SUM(credit) as total_credit, SUM(debit) as total_debit, created_at, updated_at, send_date, deleted_qty, sub_product_id, user_type, reference')
                    ->where('created_by', \Auth::user()->creatorId())
                    ->where('ref_id', $directExpenseId)
                    ->whereIn('reference', ['Direct Expense', 'Direct Expense Payment'])
                    ->groupBy('vid', 'account', 'reference')
                    ->orderBy('send_date', 'ASC')
                    ->orderBy('vid', 'ASC')
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
            return redirect()->back()->with('error', __('Error loading ledger: ') . $e->getMessage());
        }
    }

    public function edit(DirectExpense $directExpense)
    {
        if ($directExpense->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        // Only allow editing if payment status is Unpaid (status = 0)
        if ($directExpense->payment_status != 0) {
            return redirect()->back()->with('error', __('Only unpaid direct expenses can be edited.'));
        }

        $accounts = ChartOfAccount::where('created_by', \Auth::user()->creatorId())->orderBy('name')->get(['id','name','code']);
        $vendors = \DB::table('venders')->where('created_by', \Auth::user()->creatorId())->orderBy('name')->get(['id','name']);
        $taxes = \App\Models\Tax::where('created_by', \Auth::user()->creatorId())->orderBy('name')->get(['id','name']);
        $currencies = \App\Models\Currency::orderBy('name')->get(['id','name','code']);
        $directExpense->load('items.subProduct.productService', 'items.chartAccount');
        
        // Get sub products formatted like in ExpenseController
        $subProducts = \App\Models\ProductService::where('created_by', \Auth::user()->creatorId())
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

        return view('direct_expenses.edit', compact('directExpense','accounts','vendors','taxes','currencies','subProducts'));
    }

    public function update(Request $request, DirectExpense $directExpense)
    {
        if ($directExpense->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        // Only allow updating if payment status is Unpaid (status = 0)
        if ($directExpense->payment_status != 0) {
            return redirect()->back()->with('error', __('Only unpaid direct expenses can be edited.'));
        }

        $request->validate([
            'vendor_id' => 'required|exists:venders,id',
            'expense_date' => 'required|date',
            'tax_id' => 'nullable|array',
            'currency_id' => 'nullable|exists:currencies,id',
            'exchange_rate' => 'nullable|numeric|min:0',
            'expense_number' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.sub_product_id' => 'required|exists:sub_products,id',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.description' => 'nullable|string',
            'items.*.chart_account_id' => 'nullable|exists:chart_of_accounts,id',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:5120',
        ]);

        $createdBy = \Auth::user()->creatorId();
        $expenseDate = $request->expense_date ?? $directExpense->expense_date ?? now()->toDateString();
        $vendorAccountId = Vender::where('id', $request->vendor_id)->value('chart_account_id');

        // Get exchange rate
        $exchangeRate = $request->exchange_rate ?? 1;
        if ($exchangeRate <= 0) {
            $exchangeRate = 1;
        }

        if ($request->hasFile('attachment')) {
            if (!empty($directExpense->attachment) && Storage::disk('public')->exists('uploads/direct_expenses/' . $directExpense->attachment)) {
                Storage::disk('public')->delete('uploads/direct_expenses/' . $directExpense->attachment);
            }
            $attachmentName = time() . '_' . preg_replace('/\s+/', '_', $request->file('attachment')->getClientOriginalName());
            $request->file('attachment')->storeAs('uploads/direct_expenses', $attachmentName, 'public');
            $directExpense->attachment = $attachmentName;
        }

        // Update header
        $directExpense->update([
            'expense_number' => $request->expense_number,
            'expense_date' => $expenseDate,
            'vendor_id' => $request->vendor_id,
            'tax_id' => $request->tax_id ? implode(',', $request->tax_id) : null,
            'currency_id' => $request->currency_id ?? null,
            'exchange_rate' => $exchangeRate,
            'attachment' => $directExpense->attachment,
        ]);

        // Delete old ledger entries for this expense and preserve vid
        $oldEntries = GeneralLedger::where('ref_id', $directExpense->id)
            ->where('reference', 'Direct Expense')
            ->where('created_by', $createdBy)
            ->get();
        
        $oldVid = $oldEntries->first()?->vid ?? null;

        if ($oldVid) {
            GeneralLedger::where('ref_id', $directExpense->id)
                ->where('reference', 'Direct Expense')
                ->where('created_by', $createdBy)
                ->delete();
        }

        // Use the same voucher id from deleted entries, or get new one if no old entries existed
        if ($oldVid) {
            $nextVoucherId = $oldVid;
        } else {
            // Get new voucher id only if no old entries existed
            $latestVoucher = GeneralLedger::where('created_by', $createdBy)
                ->orderBy('vid', 'desc')
                ->value('vid');
            $nextVoucherId = ($latestVoucher ?? 0) + 1;
        }
        
        // Use expense_date for send_date in ledger entries
        $ledgerDate = $expenseDate;

        // Delete old items
        $directExpense->items()->delete();

        // Create new items and ledger entries
        foreach ($request->items as $itemData) {
            // Get sub product with product service and category
            $subProduct = SubProduct::with(['productService.category'])->find($itemData['sub_product_id']);
            $categoryId = optional($subProduct?->productService)->category_id;
            
            // Determine debit account
            $debitAccountId = $itemData['chart_account_id'] ?? null;
            if (empty($debitAccountId)) {
                $debitAccountId = ProductServiceCategory::where('id', $categoryId)->value('purchase_account_id');
            }

            // Get quantity from sub product
            $qty = $subProduct ? ($subProduct->quantity ?? 1) : 1;
            
            // Get category type
            $categoryType = optional($subProduct?->productService?->category)->type;
            
            // Store base amount (not multiplied) - this is the unit price
            $baseAmount = $itemData['amount'];
            
            // Calculate currency amount (base amount with exchange rate, not multiplied by qty)
            $currencyAmount = $request->currency_id ? ($baseAmount * $exchangeRate) : $baseAmount;
            
            // For ledger entries, use qty * amount if category type is "Qty product"
            $ledgerAmount = $currencyAmount;
            if ($categoryType === 'Qty product' && $qty > 0) {
                $ledgerAmount = $currencyAmount * $qty;
            }

            // Calculate tax on ledger amount (qty * amount for Qty product)
            // Match store method: use first tax ID if tax_id is an array
            $taxAmount = 0;
            $taxaccountId = null;
            if ($request->tax_id) {
                $taxId = is_array($request->tax_id) ? ($request->tax_id[0] ?? null) : $request->tax_id;
                if ($taxId) {
                    $taxRate = Tax::where('id', $taxId)->value('rate');
                    if ($taxRate) {
                        $taxAmount = ($taxRate / 100) * $ledgerAmount;
                        $taxaccountId = Tax::where('id', $taxId)->value('chart_account_id');
                    }
                }
            }

            // Create item - store base amount (not multiplied)
            DirectExpenseItem::create([
                'direct_expense_id' => $directExpense->id,
                'sub_product_id' => $itemData['sub_product_id'],
                'qty' => $qty,
                'amount' => $currencyAmount, // Base amount (unit price)
                'currency_amount' => $baseAmount, // Base amount in original currency
                'description' => $itemData['description'] ?? null,
                'chart_account_id' => $itemData['chart_account_id'] ?? null,
            ]);

            // Credit vendor account - use ledger amount (qty * amount for Qty product) + tax
            $creditEntry = new GeneralLedger();
            $creditEntry->vid = $nextVoucherId;
            $creditEntry->account = $vendorAccountId;
            $creditEntry->type = 'Direct Expense';
            $creditEntry->ref_number = \Auth::user()->expenseNumberFormat($request->expense_number);
            $creditEntry->debit = 0;
            $creditEntry->credit = $ledgerAmount + $taxAmount;
            $creditEntry->ref_id = $directExpense->id;
            $creditEntry->user_id = $request->vendor_id;
            $creditEntry->created_by = $createdBy;
            $creditEntry->send_date = $ledgerDate; // Use expense_date
            $creditEntry->reference = 'Direct Expense';
            $creditEntry->save();

            // Debit selected/purchase account - use ledger amount (qty * amount for Qty product)
            $debitEntry = new GeneralLedger();
            $debitEntry->vid = $nextVoucherId;
            $debitEntry->account = $debitAccountId;
            $debitEntry->type = 'Direct Expense';
            $debitEntry->ref_number = \Auth::user()->expenseNumberFormat($request->expense_number);
            $debitEntry->debit = $ledgerAmount;
            $debitEntry->credit = 0;
            $debitEntry->ref_id = $directExpense->id;
            $debitEntry->user_id = 0;
            $debitEntry->created_by = $createdBy;
            $debitEntry->send_date = $ledgerDate; // Use expense_date
            $debitEntry->reference = 'Direct Expense';
            $debitEntry->save();

            // Debit tax account - tax is calculated on ledger amount
            if ($taxaccountId && $taxAmount > 0) {
                $taxDebitEntry = new GeneralLedger();
                $taxDebitEntry->vid = $nextVoucherId;
                $taxDebitEntry->account = $taxaccountId;
                $taxDebitEntry->type = 'Direct Expense';
                $taxDebitEntry->ref_number = \Auth::user()->expenseNumberFormat($request->expense_number);
                $taxDebitEntry->debit = $taxAmount;
                $taxDebitEntry->credit = 0;
                $taxDebitEntry->ref_id = $directExpense->id;
                $taxDebitEntry->user_id = 0;
                $taxDebitEntry->created_by = $createdBy;
                $taxDebitEntry->send_date = $ledgerDate; // Use expense_date
                $taxDebitEntry->reference = 'Direct Expense';
                $taxDebitEntry->save();
            }
        }

        return redirect()->route('direct_expenses.index')->with('success', __('Direct expense updated.'));
    }

    public function destroy($id)
    {
        try {
            // Find the direct expense by ID
            $directExpense = DirectExpense::find($id);
            
            if (!$directExpense) {
                $redirectTo = request()->input('redirect_to');
                if ($redirectTo) {
                    return redirect($redirectTo)->with('error', __('Direct expense not found.'));
                }
                return redirect()->route('direct_expenses.index')->with('error', __('Direct expense not found.'));
            }
            
            if ($directExpense->created_by != \Auth::user()->creatorId()) {
                $redirectTo = request()->input('redirect_to');
                if ($redirectTo) {
                    return redirect($redirectTo)->with('error', __('Permission denied.'));
                }
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            $createdBy = \Auth::user()->creatorId();
            $expenseDate = $directExpense->expense_date ?? now()->toDateString();

            // Get all items for this expense
            $items = $directExpense->items;
            
            // Determine accounts
            $vendorAccountId = Vender::where('id', $directExpense->vendor_id)->value('chart_account_id');

            // Get tax information for VAT reversal
            $taxIds = $directExpense->getTaxIds();
            $taxId = !empty($taxIds) ? $taxIds[0] : null; // Use first tax ID (matching store method behavior)
            $taxRate = $taxId ? Tax::where('id', $taxId)->value('rate') : 0;
            $taxAccountId = $taxId ? Tax::where('id', $taxId)->value('chart_account_id') : null;

            // New voucher id for reversal
            $newVid = (GeneralLedger::where('created_by', $createdBy)->max('vid') ?? 0) + 1;

            // Reverse entries for each item
            foreach ($items as $item) {
                // Get sub product with product service and category
                $subProduct = SubProduct::with(['productService.category'])->find($item->sub_product_id);
                $categoryId = optional($subProduct?->productService)->category_id;
                
                $debitAccountId = $item->chart_account_id;
                if (empty($debitAccountId)) {
                    $debitAccountId = ProductServiceCategory::where('id', $categoryId)->value('purchase_account_id');
                }

                // Get stored base amount
                $baseAmount = $item->amount; // This is the base amount (unit price) stored in DB
                
                // Get category type
                $categoryType = optional($subProduct?->productService?->category)->type;
                
                // For Qty products, use remaining quantity from sub product, otherwise use stored qty
                $qty = $item->qty ?? 1;
                if ($categoryType === 'Qty product' && $subProduct) {
                    // Use current remaining quantity from sub product for reversal
                    $qty = $subProduct->quantity ?? $item->qty ?? 1;
                }
                
                // Calculate ledger amount (qty * amount for Qty product, otherwise just amount)
                $ledgerAmount = $baseAmount;
                if ($categoryType === 'Qty product' && $qty > 0) {
                    $ledgerAmount = $baseAmount * $qty;
                }
                
                // Calculate tax amount for this item (matching store method calculation)
                // Tax was calculated on ledger amount (qty * amount for Qty product)
                $taxAmount = $taxId ? ($taxRate / 100) * $ledgerAmount : 0;
                $totalItemAmount = $ledgerAmount + $taxAmount;

                // Reverse: debit vendor (including tax amount that was credited during creation)
                $revDebitVendor = new GeneralLedger();
                $revDebitVendor->vid = $newVid;
                $revDebitVendor->account = $vendorAccountId;
                $revDebitVendor->type = 'Direct Expense Reversal';
                $revDebitVendor->ref_number = \Auth::user()->expenseNumberFormat($directExpense->expense_number);
                $revDebitVendor->debit = $totalItemAmount;
                $revDebitVendor->credit = 0;
                $revDebitVendor->ref_id = $directExpense->id;
                $revDebitVendor->user_id = $directExpense->vendor_id;
                $revDebitVendor->created_by = $createdBy;
                $revDebitVendor->send_date = $expenseDate;
                $revDebitVendor->reference = 'Direct Expense Reversal';
                $revDebitVendor->sub_product_id = $item->sub_product_id;
                // Store deleted quantity for Qty products
                if ($categoryType === 'Qty product') {
                    $revDebitVendor->deleted_qty = $qty;
                }
                $revDebitVendor->save();

                // Reverse: credit selected/purchase account (use ledger amount)
                $revCreditAccount = new GeneralLedger();
                $revCreditAccount->vid = $newVid;
                $revCreditAccount->account = $debitAccountId;
                $revCreditAccount->type = 'Direct Expense Reversal';
                $revCreditAccount->ref_number = \Auth::user()->expenseNumberFormat($directExpense->expense_number);
                $revCreditAccount->debit = 0;
                $revCreditAccount->credit = $ledgerAmount;
                $revCreditAccount->ref_id = $directExpense->id;
                $revCreditAccount->user_id = 0;
                $revCreditAccount->created_by = $createdBy;
                $revCreditAccount->send_date = $expenseDate;
                $revCreditAccount->reference = 'Direct Expense Reversal';
                $revCreditAccount->sub_product_id = $item->sub_product_id;
                // Store deleted quantity for Qty products
                if ($categoryType === 'Qty product') {
                    $revCreditAccount->deleted_qty = $qty;
                }
                $revCreditAccount->save();

                // Reverse VAT: credit tax account (was debited during creation)
                if ($taxId && $taxAmount > 0 && $taxAccountId) {
                    $revCreditTax = new GeneralLedger();
                    $revCreditTax->vid = $newVid;
                    $revCreditTax->account = $taxAccountId;
                    $revCreditTax->type = 'Direct Expense Reversal';
                    $revCreditTax->ref_number = \Auth::user()->expenseNumberFormat($directExpense->expense_number);
                    $revCreditTax->debit = 0;
                    $revCreditTax->credit = $taxAmount;
                    $revCreditTax->ref_id = $directExpense->id;
                    $revCreditTax->user_id = 0;
                    $revCreditTax->created_by = $createdBy;
                    $revCreditTax->send_date = $expenseDate;
                    $revCreditTax->reference = 'Direct Expense Reversal';
                    $revCreditTax->sub_product_id = $item->sub_product_id;
                    // Store deleted quantity for Qty products
                    if ($categoryType === 'Qty product') {
                        $revCreditTax->deleted_qty = $qty;
                    }
                    $revCreditTax->save();
                }
            }

            // Delete the direct expense record (items will be deleted via cascade)
            $directExpense->delete();

            // Redirect back to the page that called this action
            $redirectTo = request()->input('redirect_to');
            if ($redirectTo) {
                return redirect($redirectTo)->with('success', __('Direct expense deleted with reversal.'));
            }

            return redirect()->route('direct_expenses.index')->with('success', __('Direct expense deleted with reversal.'));
        } catch (\Exception $e) {
            \Log::error('Error deleting direct expense: ' . $e->getMessage());
            $redirectTo = request()->input('redirect_to');
            if ($redirectTo) {
                return redirect($redirectTo)->with('error', __('Error deleting expense: ') . $e->getMessage());
            }
            return redirect()->back()->with('error', __('Error deleting expense: ') . $e->getMessage());
        }
    }

    public function destroyItem($itemId)
    {
        try {
            // Find the expense item by ID
            $item = DirectExpenseItem::with('directExpense')->find($itemId);
            
            if (!$item) {
                $redirectTo = request()->input('redirect_to');
                if ($redirectTo) {
                    return redirect($redirectTo)->with('error', __('Expense item not found.'));
                }
                return redirect()->route('direct_expenses.index')->with('error', __('Expense item not found.'));
            }
            
            $directExpense = $item->directExpense;
            
            if (!$directExpense || $directExpense->created_by != \Auth::user()->creatorId()) {
                $redirectTo = request()->input('redirect_to');
                if ($redirectTo) {
                    return redirect($redirectTo)->with('error', __('Permission denied.'));
                }
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            $createdBy = \Auth::user()->creatorId();
            $today = now()->toDateString();

            // Determine accounts
            $vendorAccountId = Vender::where('id', $directExpense->vendor_id)->value('chart_account_id');

            // Get tax information for VAT reversal
            $taxIds = $directExpense->getTaxIds();
            $taxId = !empty($taxIds) ? $taxIds[0] : null; // Use first tax ID (matching store method behavior)
            $taxRate = $taxId ? Tax::where('id', $taxId)->value('rate') : 0;
            $taxAccountId = $taxId ? Tax::where('id', $taxId)->value('chart_account_id') : null;

            // Get sub product with product service and category
            $subProduct = SubProduct::with(['productService.category'])->find($item->sub_product_id);
            $categoryId = optional($subProduct?->productService)->category_id;
            
            $debitAccountId = $item->chart_account_id;
            if (empty($debitAccountId)) {
                $debitAccountId = ProductServiceCategory::where('id', $categoryId)->value('purchase_account_id');
            }

            // Get stored base amount
            $baseAmount = $item->amount; // This is the base amount (unit price) stored in DB
            
            // Get category type
            $categoryType = optional($subProduct?->productService?->category)->type;
            
            // For Qty products, use remaining quantity from sub product, otherwise use stored qty
            $qty = $item->qty ?? 1;
            if ($categoryType === 'Qty product' && $subProduct) {
                // Use current remaining quantity from sub product for reversal
                $qty = $subProduct->quantity ?? $item->qty ?? 1;
            }
            
            // Calculate ledger amount (qty * amount for Qty product, otherwise just amount)
            $ledgerAmount = $baseAmount;
            if ($categoryType === 'Qty product' && $qty > 0) {
                $ledgerAmount = $baseAmount * $qty;
            }
            
            // Calculate tax amount for this item (matching store method calculation)
            // Tax was calculated on ledger amount (qty * amount for Qty product)
            $taxAmount = $taxId ? ($taxRate / 100) * $ledgerAmount : 0;
            $totalItemAmount = $ledgerAmount + $taxAmount;

            // New voucher id for reversal
            $newVid = (GeneralLedger::where('created_by', $createdBy)->max('vid') ?? 0) + 1;

            // Reverse: debit vendor (including tax amount that was credited during creation)
            $revDebitVendor = new GeneralLedger();
            $revDebitVendor->vid = $newVid;
            $revDebitVendor->account = $vendorAccountId;
            $revDebitVendor->type = 'Direct Expense Item Reversal';
            $revDebitVendor->ref_number = \Auth::user()->expenseNumberFormat($directExpense->expense_number);
            $revDebitVendor->debit = $totalItemAmount;
            $revDebitVendor->credit = 0;
            $revDebitVendor->ref_id = $directExpense->id;
            $revDebitVendor->user_id = $directExpense->vendor_id;
            $revDebitVendor->created_by = $createdBy;
            $revDebitVendor->send_date = $today;
            $revDebitVendor->reference = 'Direct Expense Item Reversal';
            $revDebitVendor->sub_product_id = $item->sub_product_id;
            // Store deleted quantity for Qty products
            if ($categoryType === 'Qty product') {
                $revDebitVendor->deleted_qty = $qty;
            }
            $revDebitVendor->save();

            // Reverse: credit selected/purchase account (use ledger amount)
            $revCreditAccount = new GeneralLedger();
            $revCreditAccount->vid = $newVid;
            $revCreditAccount->account = $debitAccountId;
            $revCreditAccount->type = 'Direct Expense Item Reversal';
            $revCreditAccount->ref_number = \Auth::user()->expenseNumberFormat($directExpense->expense_number);
            $revCreditAccount->debit = 0;
            $revCreditAccount->credit = $ledgerAmount;
            $revCreditAccount->ref_id = $directExpense->id;
            $revCreditAccount->user_id = 0;
            $revCreditAccount->created_by = $createdBy;
            $revCreditAccount->send_date = $today;
            $revCreditAccount->reference = 'Direct Expense Item Reversal';
            $revCreditAccount->sub_product_id = $item->sub_product_id;
            // Store deleted quantity for Qty products
            if ($categoryType === 'Qty product') {
                $revCreditAccount->deleted_qty = $qty;
            }
            $revCreditAccount->save();

            // Reverse VAT: credit tax account (was debited during creation)
            if ($taxId && $taxAmount > 0 && $taxAccountId) {
                $revCreditTax = new GeneralLedger();
                $revCreditTax->vid = $newVid;
                $revCreditTax->account = $taxAccountId;
                $revCreditTax->type = 'Direct Expense Item Reversal';
                $revCreditTax->ref_number = \Auth::user()->expenseNumberFormat($directExpense->expense_number);
                $revCreditTax->debit = 0;
                $revCreditTax->credit = $taxAmount;
                $revCreditTax->ref_id = $directExpense->id;
                $revCreditTax->user_id = 0;
                $revCreditTax->created_by = $createdBy;
                $revCreditTax->send_date = $today;
                $revCreditTax->reference = 'Direct Expense Item Reversal';
                $revCreditTax->sub_product_id = $item->sub_product_id;
                // Store deleted quantity for Qty products
                if ($categoryType === 'Qty product') {
                    $revCreditTax->deleted_qty = $qty;
                }
                $revCreditTax->save();
            }

            // Delete only this item (not the whole expense)
            $item->delete();

            // Redirect back to the page that called this action
            $redirectTo = request()->input('redirect_to');
            if ($redirectTo) {
                return redirect($redirectTo)->with('success', __('Expense item deleted with reversal.'));
            }

            return redirect()->route('direct_expenses.index')->with('success', __('Expense item deleted with reversal.'));
        } catch (\Exception $e) {
            \Log::error('Error deleting expense item: ' . $e->getMessage());
            $redirectTo = request()->input('redirect_to');
            if ($redirectTo) {
                return redirect($redirectTo)->with('error', __('Error deleting expense item: ') . $e->getMessage());
            }
            return redirect()->back()->with('error', __('Error deleting expense item: ') . $e->getMessage());
        }
    }

    function expenseNumber()
    {
        $latest = DirectExpense::where('created_by', '=', \Auth::user()->creatorId())->latest()->first();
        if (!$latest || !is_numeric($latest->expense_number)) {
            return 1;
        }

        return (int)$latest->expense_number + 1;
    }
}
