<?php

namespace App\Http\Controllers;

use App\Exports\AccountStatementExport;
use App\Exports\CustomerStatementExport;
use App\Exports\BalanceSheetExport;
use App\Exports\LeaveReportExport;
use App\Exports\PayrollExport;
use App\Exports\ProductStockExport;
use App\Exports\ProfitLossExport;
use App\Exports\ReceivableExport;
use App\Exports\SalesReportExport;
use App\Exports\TrialBalancExport;
use App\Exports\TrialBalancTotalExport;
use App\Models\AttendanceEmployee;
use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\BillProduct;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountSubType;
use App\Models\ChartOfAccountType;
use App\Models\ClientDeal;
use App\Models\TransactionLines;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\DebitNote;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\Lead;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Payment;
use App\Models\PaySlip;
use App\Models\Pipeline;
use App\Models\Pos;
use App\Models\ProductServiceCategory;
use App\Models\Purchase;
use App\Models\Revenue;
use App\Models\Source;
use App\Models\StockReport;
use App\Models\Tax;
use App\Models\User;
use App\Models\UserDeal;
use App\Models\Utility;
use App\Models\Vender;
use App\Models\warehouse;
use App\Models\WarehouseProduct;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\GeneralLedger;
use App\Exports\LedgerSummaryExport;
use App\Exports\GledgerExport;
use App\Models\UserLead;
use DateTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\PosProduct;
use App\Models\PosRefund;
use App\Models\PosRefundItem;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function incomeSummary(Request $request)
    {
        if (\Auth::user()->can('income report')) {
            $account = BankAccount::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('holder_name', 'id');
            $account->prepend('select Account', '');
            $customer = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customer->prepend('Select Customer', '');
            $category = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->where('type', '=', 'income')->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');

            $data['monthList'] = $month = $this->yearMonth();
            $data['yearList'] = $this->yearList();
            $filter['category'] = __('All');
            $filter['customer'] = __('All');

            if (isset($request->year)) {
                $year = $request->year;
            } else {
                $year = date('Y');
            }
            $data['currentYear'] = $year;

            // ------------------------------REVENUE INCOME-----------------------------------
            $incomes = Revenue::selectRaw('sum(revenues.amount) as amount,MONTH(date) as month,YEAR(date) as year,category_id')
                ->leftjoin('product_service_categories', 'revenues.category_id', '=', 'product_service_categories.id')->where('product_service_categories.type', '=', 1);
            $incomes->where('revenues.created_by', '=', \Auth::user()->creatorId());
            $incomes->whereRAW('YEAR(date) =?', [$year]);

            if (!empty($request->category)) {
                $incomes->where('category_id', '=', $request->category);
                $cat = ProductServiceCategory::find($request->category);
                $filter['category'] = !empty($cat) ? $cat->name : '';
            }

            if (!empty($request->customer)) {
                $incomes->where('customer_id', '=', $request->customer);
                $cust = Customer::find($request->customer);
                $filter['customer'] = !empty($cust) ? $cust->name : '';
            }
            $incomes->groupBy('month', 'year', 'category_id');
            $incomes = $incomes->get();

            $tmpArray = [];
            foreach ($incomes as $income) {
                $tmpArray[$income->category_id][$income->month] = $income->amount;
            }
            $array = [];
            foreach ($tmpArray as $cat_id => $record) {
                $tmp = [];
                $tmp['category'] = !empty(ProductServiceCategory::where('id', '=', $cat_id)->first()) ? ProductServiceCategory::where('id', '=', $cat_id)->first()->name : '';
                $tmp['data'] = [];
                for ($i = 1; $i <= 12; $i++) {
                    $tmp['data'][$i] = array_key_exists($i, $record) ? $record[$i] : 0;
                }
                $array[] = $tmp;
            }

            $incomesData = Revenue::selectRaw('sum(revenues.amount) as amount,MONTH(date) as month,YEAR(date) as year');
            $incomesData->where('revenues.created_by', '=', \Auth::user()->creatorId());
            $incomesData->whereRAW('YEAR(date) =?', [$year]);

            if (!empty($request->category)) {
                $incomesData->where('category_id', '=', $request->category);
            }
            if (!empty($request->customer)) {
                $incomesData->where('customer_id', '=', $request->customer);
            }
            $incomesData->groupBy('month', 'year');
            $incomesData = $incomesData->get();
            $incomeArr = [];
            foreach ($incomesData as $k => $incomeData) {
                $incomeArr[$incomeData->month] = $incomeData->amount;
            }
            for ($i = 1; $i <= 12; $i++) {
                $incomeTotal[] = array_key_exists($i, $incomeArr) ? $incomeArr[$i] : 0;
            }

            //---------------------------INVOICE INCOME-----------------------------------------------

            $invoices = Invoice::selectRaw('MONTH(send_date) as month,YEAR(send_date) as year,category_id,invoice_id,id')
                ->where('created_by', \Auth::user()->creatorId())
                ->where('status', '!=', 0);

            $invoices->whereRAW('YEAR(send_date) =?', [$year]);

            if (!empty($request->customer)) {
                $invoices->where('customer_id', '=', $request->customer);
            }

            if (!empty($request->category)) {
                $invoices->where('category_id', '=', $request->category);
            }

            $invoices = $invoices->get();
            $invoiceTmpArray = [];
            foreach ($invoices as $invoice) {
                $item = Invoice::where('id',$invoice->id)->first();
                $invoiceTmpArray[$invoice->category_id][$invoice->month][] = $item->getTotal();
            }

            $invoiceArray = [];
            foreach ($invoiceTmpArray as $cat_id => $record) {

                $invoice = [];
                $productCategory = ProductServiceCategory::where('id', '=', $cat_id)->first();
                $invoice['category'] = !empty($productCategory) ? $productCategory->name : '';
                $invoice['data'] = [];
                for ($i = 1; $i <= 12; $i++) {

                    $invoice['data'][$i] = array_key_exists($i, $record) ? array_sum($record[$i]) : 0;
                }
                $invoiceArray[] = $invoice;
            }

            $invoiceTotalArray = [];
            foreach ($invoices as $invoice) {
                $item = Invoice::where('id',$invoice->id)->first();
                $invoiceTotalArray[$invoice->month][] = $item->getTotal();
            }
            for ($i = 1; $i <= 12; $i++) {
                $invoiceTotal[] = array_key_exists($i, $invoiceTotalArray) ? array_sum($invoiceTotalArray[$i]) : 0;
            }

            $chartIncomeArr = array_map(
                function () {
                    return array_sum(func_get_args());
                },
                $incomeTotal,
                $invoiceTotal
            );

            $data['chartIncomeArr'] = $chartIncomeArr;
            $data['incomeArr'] = $array;
            $data['invoiceArray'] = $invoiceArray;
            $data['account'] = $account;
            $data['customer'] = $customer;
            $data['category'] = $category;

            $filter['startDateRange'] = 'Jan-' . $year;
            $filter['endDateRange'] = 'Dec-' . $year;

            return view('report.income_summary', compact('filter'), $data);
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function expenseSummary(Request $request)
    {
        if (\Auth::user()->can('expense report')) {
            $account = BankAccount::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('holder_name', 'id');
            $account->prepend('Select Account', '');
            $vender = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $vender->prepend('Select Vendor', '');
            $category = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->where('type', '=', 'expense')->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');

            $data['monthList'] = $month = $this->yearMonth();
            $data['yearList'] = $this->yearList();
            $filter['category'] = __('All');
            $filter['vender'] = __('All');

            if (isset($request->year)) {
                $year = $request->year;
            } else {
                $year = date('Y');
            }
            $data['currentYear'] = $year;

            //   -----------------------------------------PAYMENT EXPENSE ------------------------------------------------------------
            $expenses = Payment::selectRaw('sum(payments.amount) as amount,MONTH(date) as month,YEAR(date) as year,category_id')->leftjoin('product_service_categories', 'payments.category_id', '=', 'product_service_categories.id')->where('product_service_categories.type', '=', 2);
            $expenses->where('payments.created_by', '=', \Auth::user()->creatorId());
            $expenses->whereRAW('YEAR(date) =?', [$year]);

            if (!empty($request->category)) {
                $expenses->where('category_id', '=', $request->category);
                $cat = ProductServiceCategory::find($request->category);
                $filter['category'] = !empty($cat) ? $cat->name : '';
            }
            if (!empty($request->vender)) {
                $expenses->where('vender_id', '=', $request->vender);

                $vend = Vender::find($request->vender);
                $filter['vender'] = !empty($vend) ? $vend->name : '';
            }

            $expenses->groupBy('month', 'year', 'category_id');
            $expenses = $expenses->get();
            $tmpArray = [];
            foreach ($expenses as $expense) {
                $tmpArray[$expense->category_id][$expense->month] = $expense->amount;
            }
            $array = [];
            foreach ($tmpArray as $cat_id => $record) {
                $tmp = [];
                $tmp['category'] = !empty(ProductServiceCategory::where('id', '=', $cat_id)->first()) ? ProductServiceCategory::where('id', '=', $cat_id)->first()->name : '';
                $tmp['data'] = [];
                for ($i = 1; $i <= 12; $i++) {
                    $tmp['data'][$i] = array_key_exists($i, $record) ? $record[$i] : 0;
                }
                $array[] = $tmp;
            }
            $expensesData = Payment::selectRaw('sum(payments.amount) as amount,MONTH(date) as month,YEAR(date) as year');
            $expensesData->where('payments.created_by', '=', \Auth::user()->creatorId());
            $expensesData->whereRAW('YEAR(date) =?', [$year]);

            if (!empty($request->category)) {
                $expensesData->where('category_id', '=', $request->category);
            }
            if (!empty($request->vender)) {
                $expensesData->where('vender_id', '=', $request->vender);
            }
            $expensesData->groupBy('month', 'year');
            $expensesData = $expensesData->get();

            $expenseArr = [];
            foreach ($expensesData as $k => $expenseData) {
                $expenseArr[$expenseData->month] = $expenseData->amount;
            }
            for ($i = 1; $i <= 12; $i++) {
                $expenseTotal[] = array_key_exists($i, $expenseArr) ? $expenseArr[$i] : 0;
            }

            //     ------------------------------------BILL EXPENSE----------------------------------------------------

            $bills = Bill::selectRaw('MONTH(send_date) as month,YEAR(send_date) as year,category_id,bill_id,id')->where('created_by', \Auth::user()->creatorId())->where('status', '!=', 0);
            $bills->whereRAW('YEAR(send_date) =?', [$year]);

            if (!empty($request->vender)) {
                $bills->where('vender_id', '=', $request->vender);
            }

            if (!empty($request->category)) {
                $bills->where('category_id', '=', $request->category);
            }
            $bills = $bills->get();
            $billTmpArray = [];
            foreach ($bills as $bill) {
                $billTmpArray[$bill->category_id][$bill->month][] = $bill->getTotal();
            }

            $billArray = [];
            foreach ($billTmpArray as $cat_id => $record) {

                $bill = [];
                $productCategory = ProductServiceCategory::where('id', '=', $cat_id)->first();
                $bill['category'] = !empty($productCategory) ? $productCategory->name : '';
                $bill['data'] = [];
                for ($i = 1; $i <= 12; $i++) {

                    $bill['data'][$i] = array_key_exists($i, $record) ? array_sum($record[$i]) : 0;
                }
                $billArray[] = $bill;
            }

            $billTotalArray = [];
            foreach ($bills as $bill) {
                $billTotalArray[$bill->month][] = $bill->getTotal();
            }
            for ($i = 1; $i <= 12; $i++) {
                $billTotal[] = array_key_exists($i, $billTotalArray) ? array_sum($billTotalArray[$i]) : 0;
            }

            $chartExpenseArr = array_map(
                function () {
                    return array_sum(func_get_args());
                },
                $expenseTotal,
                $billTotal
            );

            $data['chartExpenseArr'] = $chartExpenseArr;
            $data['expenseArr'] = $array;
            $data['billArray'] = $billArray;
            $data['account'] = $account;
            $data['vender'] = $vender;
            $data['category'] = $category;

            $filter['startDateRange'] = 'Jan-' . $year;
            $filter['endDateRange'] = 'Dec-' . $year;

            return view('report.expense_summary', compact('filter'), $data);
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function incomeVsExpenseSummary(Request $request)
    {
        if (\Auth::user()->can('income vs expense report')) {
            $account = BankAccount::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('holder_name', 'id');
            $account->prepend('Select Account', '');
            $vender = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $vender->prepend('Select Vendor', '');
            $customer = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customer->prepend('Select Customer', '');

            $category = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->whereIn(
                'type',
                [
                    1,
                    2,
                ]
            )->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');

            $data['monthList'] = $month = $this->yearMonth();
            $data['yearList'] = $this->yearList();

            $filter['category'] = __('All');
            $filter['customer'] = __('All');
            $filter['vender'] = __('All');

            if (isset($request->year)) {
                $year = $request->year;
            } else {
                $year = date('Y');
            }
            $data['currentYear'] = $year;

            // ------------------------------TOTAL PAYMENT EXPENSE-----------------------------------------------------------
            $expensesData = Payment::selectRaw('sum(payments.amount) as amount,MONTH(date) as month,YEAR(date) as year');
            $expensesData->where('payments.created_by', '=', \Auth::user()->creatorId());
            $expensesData->whereRAW('YEAR(date) =?', [$year]);

            if (!empty($request->category)) {
                $expensesData->where('category_id', '=', $request->category);
                $cat = ProductServiceCategory::find($request->category);
                $filter['category'] = !empty($cat) ? $cat->name : '';
            }
            if (!empty($request->vender)) {
                $expensesData->where('vender_id', '=', $request->vender);

                $vend = Vender::find($request->vender);
                $filter['vender'] = !empty($vend) ? $vend->name : '';
            }
            $expensesData->groupBy('month', 'year');
            $expensesData = $expensesData->get();

            $expenseArr = [];
            foreach ($expensesData as $k => $expenseData) {
                $expenseArr[$expenseData->month] = $expenseData->amount;
            }

            // ------------------------------TOTAL BILL EXPENSE-----------------------------------------------------------

            $bills = Bill::selectRaw('MONTH(send_date) as month,YEAR(send_date) as year,category_id,bill_id,id')->where('created_by', \Auth::user()->creatorId())->where('status', '!=', 0);
            $bills->whereRAW('YEAR(send_date) =?', [$year]);

            if (!empty($request->vender)) {
                $bills->where('vender_id', '=', $request->vender);
            }

            if (!empty($request->category)) {
                $bills->where('category_id', '=', $request->category);
            }

            $bills = $bills->get();
            $billTmpArray = [];
            foreach ($bills as $bill) {
                $billTmpArray[$bill->category_id][$bill->month][] = $bill->getTotal();
            }
            $billArray = [];
            foreach ($billTmpArray as $cat_id => $record) {
                $bill = [];
                $productCategory = ProductServiceCategory::where('id', '=', $cat_id)->first();
                $bill['category'] = !empty($productCategory) ? $productCategory->name : '';
                $bill['data'] = [];
                for ($i = 1; $i <= 12; $i++) {

                    $bill['data'][$i] = array_key_exists($i, $record) ? array_sum($record[$i]) : 0;
                }
                $billArray[] = $bill;
            }

            $billTotalArray = [];
            foreach ($bills as $bill) {
                $billTotalArray[$bill->month][] = $bill->getTotal();
            }

            // ------------------------------TOTAL REVENUE INCOME-----------------------------------------------------------

            $incomesData = Revenue::selectRaw('sum(revenues.amount) as amount,MONTH(date) as month,YEAR(date) as year');
            $incomesData->where('revenues.created_by', '=', \Auth::user()->creatorId());
            $incomesData->whereRAW('YEAR(date) =?', [$year]);

            if (!empty($request->category)) {
                $incomesData->where('category_id', '=', $request->category);
            }
            if (!empty($request->customer)) {
                $incomesData->where('customer_id', '=', $request->customer);
                $cust = Customer::find($request->customer);
                $filter['customer'] = !empty($cust) ? $cust->name : '';
            }
            $incomesData->groupBy('month', 'year');
            $incomesData = $incomesData->get();
            $incomeArr = [];
            foreach ($incomesData as $k => $incomeData) {
                $incomeArr[$incomeData->month] = $incomeData->amount;
            }

            // ------------------------------TOTAL INVOICE INCOME-----------------------------------------------------------
            $invoices = Invoice::selectRaw('MONTH(send_date) as month,YEAR(send_date) as year,category_id,invoice_id,id')
                ->where('created_by', \Auth::user()->creatorId())->where('status', '!=', 0);
            $invoices->whereRAW('YEAR(send_date) =?', [$year]);
            if (!empty($request->customer)) {
                $invoices->where('customer_id', '=', $request->customer);
            }
            if (!empty($request->category)) {
                $invoices->where('category_id', '=', $request->category);
            }
            $invoices = $invoices->get();
            $invoiceTmpArray = [];
            foreach ($invoices as $invoice) {
                $item = Invoice::where('id',$invoice->id)->first();
                $invoiceTmpArray[$invoice->category_id][$invoice->month][] = $item->getTotal();
            }

            $invoiceArray = [];
            foreach ($invoiceTmpArray as $cat_id => $record) {

                $invoice = [];
                $productCategory = ProductServiceCategory::where('id', '=', $cat_id)->first();

                $invoice['category'] = !empty($productCategory) ? $productCategory->name : '';
                $invoice['data'] = [];
                for ($i = 1; $i <= 12; $i++) {

                    $invoice['data'][$i] = array_key_exists($i, $record) ? array_sum($record[$i]) : 0;
                }
                $invoiceArray[] = $invoice;
            }

            $invoiceTotalArray = [];
            foreach ($invoices as $invoice) {
                $item = Invoice::where('id', $invoice->id)->first();
                $invoiceTotalArray[$invoice->month][] = $item->getTotal();
            }
            //        ----------------------------------------------------------------------------------------------------

            for ($i = 1; $i <= 12; $i++) {
                $paymentExpenseTotal[] = array_key_exists($i, $expenseArr) ? $expenseArr[$i] : 0;
                $billExpenseTotal[] = array_key_exists($i, $billTotalArray) ? array_sum($billTotalArray[$i]) : 0;

                $RevenueIncomeTotal[] = array_key_exists($i, $incomeArr) ? $incomeArr[$i] : 0;
                $invoiceIncomeTotal[] = array_key_exists($i, $invoiceTotalArray) ? array_sum($invoiceTotalArray[$i]) : 0;
            }

            $totalIncome = array_map(
                function () {
                    return array_sum(func_get_args());
                },
                $RevenueIncomeTotal,
                $invoiceIncomeTotal
            );

            $totalExpense = array_map(
                function () {
                    return array_sum(func_get_args());
                },
                $paymentExpenseTotal,
                $billExpenseTotal
            );

            $profit = [];
            $keys = array_keys($totalIncome + $totalExpense);
            foreach ($keys as $v) {
                $profit[$v] = (empty($totalIncome[$v]) ? 0 : $totalIncome[$v]) - (empty($totalExpense[$v]) ? 0 : $totalExpense[$v]);
            }

            $data['paymentExpenseTotal'] = $paymentExpenseTotal;
            $data['billExpenseTotal'] = $billExpenseTotal;
            $data['revenueIncomeTotal'] = $RevenueIncomeTotal;
            $data['invoiceIncomeTotal'] = $invoiceIncomeTotal;
            $data['profit'] = $profit;
            $data['account'] = $account;
            $data['vender'] = $vender;
            $data['customer'] = $customer;
            $data['category'] = $category;

            $filter['startDateRange'] = 'Jan-' . $year;
            $filter['endDateRange'] = 'Dec-' . $year;

            return view('report.income_vs_expense_summary', compact('filter'), $data);
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function taxSummary(Request $request)
    {

        if (\Auth::user()->can('tax report')) {
            $data['monthList'] = $month = $this->yearMonth();
            $data['yearList'] = $this->yearList();
            $data['taxList'] = $taxList = Tax::where('created_by', \Auth::user()->creatorId())->get();

            if (isset($request->year)) {
                $year = $request->year;
            } else {
                $year = date('Y');
            }

            $data['currentYear'] = $year;

            $invoiceProducts = InvoiceProduct::selectRaw('invoice_products.* ,MONTH(invoice_products.created_at) as month,YEAR(invoice_products.created_at) as year')->leftjoin('product_services', 'invoice_products.product_id', '=', 'product_services.id')->whereRaw('YEAR(invoice_products.created_at) =?', [$year])->where('product_services.created_by', '=', \Auth::user()->creatorId())->get();

            $incomeTaxesData = [];

            foreach ($invoiceProducts as $invoiceProduct) {
                $incomeTax = [];
                $getTaxData = Utility::getTaxData();
                $invoice = Invoice::where('id', $invoiceProduct->invoice_id)->first();

                foreach (explode(',', $invoice->tax_id) as $tax) {
                    $taxPrice = \Utility::taxRate($getTaxData[$tax]['rate'], $invoiceProduct->price, 1);

                    $itemName = $getTaxData[$tax]['name'];
                    $itemTax['name'] = $itemName;
                    $itemTax['rate'] = $getTaxData[$tax]['rate'] . '%';
                    $itemTax['price'] = ($taxPrice);

                    if (!isset($incomeTax[$itemName])) {
                        $incomeTax[$itemName] = 0;
                    }

                    $incomeTax[$itemName] += $itemTax['price'];
                }

                $incomeTaxesData[$invoiceProduct->month][] = $incomeTax;
            }

            $income = [];
            foreach ($incomeTaxesData as $month => $incomeTaxx) {
                $incomeTaxRecord = [];
                foreach ($incomeTaxx as $k => $record) {
                    foreach ($record as $incomeTaxName => $incomeTaxAmount) {
                        if (array_key_exists($incomeTaxName, $incomeTaxRecord)) {
                            $incomeTaxRecord[$incomeTaxName] += $incomeTaxAmount;
                        } else {
                            $incomeTaxRecord[$incomeTaxName] = $incomeTaxAmount;
                        }
                    }
                    $income['data'][$month] = $incomeTaxRecord;
                }
            }

            foreach ($income as $incomeMonth => $incomeTaxData) {
                $incomeData = [];
                for ($i = 1; $i <= 12; $i++) {
                    $incomeData[$i] = array_key_exists($i, $incomeTaxData) ? $incomeTaxData[$i] : 0;
                }
            }

            $incomes = [];
            if (isset($incomeData) && !empty($incomeData)) {
                foreach ($taxList as $taxArr) {
                    foreach ($incomeData as $month => $tax) {
                        if ($tax != 0) {
                            if (isset($tax[$taxArr->name])) {
                                $incomes[$taxArr->name][$month] = $tax[$taxArr->name];
                            } else {
                                $incomes[$taxArr->name][$month] = 0;
                            }
                        } else {
                            $incomes[$taxArr->name][$month] = 0;
                        }
                    }
                }
            }

            $billProducts = BillProduct::selectRaw('bill_products.* ,MONTH(bill_products.created_at) as month,YEAR(bill_products.created_at) as year')->leftjoin('product_services', 'bill_products.product_id', '=', 'product_services.id')->whereRaw('YEAR(bill_products.created_at) =?', [$year])->where('product_services.created_by', '=', \Auth::user()->creatorId())->get();

            $expenseTaxesData = [];
            foreach ($billProducts as $billProduct) {
                $billTax = [];

                $getTaxData = Utility::getTaxData();
                $taxesData = [];
                $bill = Bill::where('id', $billProduct->bill_id)->first();
                if ($bill->tax_id != null) {
                    foreach (explode(',', $bill->tax_id) as $tax) {
                        $taxPrice = \Utility::taxRate($getTaxData[$tax]['rate'], $billProduct->price, 1);
                        $itemName = $getTaxData[$tax]['name'];
                        $itemTax['name'] = $itemName;
                        $itemTax['rate'] = $getTaxData[$tax]['rate'] . '%';
                        $itemTax['price'] = ($taxPrice);

                        if (!isset($billTax[$itemName])) {
                            $billTax[$itemName] = 0;
                        }
                        $billTax[$itemName] += $itemTax['price'];
                    }
                }


                $expenseTaxesData[$billProduct->month][] = $billTax;
            }

            $bill = [];
            foreach ($expenseTaxesData as $month => $billTaxx) {
                $billTaxRecord = [];
                foreach ($billTaxx as $k => $record) {
                    foreach ($record as $billTaxName => $billTaxAmount) {
                        if (array_key_exists($billTaxName, $billTaxRecord)) {
                            $billTaxRecord[$billTaxName] += $billTaxAmount;
                        } else {
                            $billTaxRecord[$billTaxName] = $billTaxAmount;
                        }
                    }
                    $bill['data'][$month] = $billTaxRecord;
                }
            }

            foreach ($bill as $billMonth => $billTaxData) {
                $billData = [];
                for ($i = 1; $i <= 12; $i++) {
                    $billData[$i] = array_key_exists($i, $billTaxData) ? $billTaxData[$i] : 0;
                }
            }
            $expenses = [];
            if (isset($billData) && !empty($billData)) {

                foreach ($taxList as $taxArr) {
                    foreach ($billData as $month => $tax) {
                        if ($tax != 0) {
                            if (isset($tax[$taxArr->name])) {
                                $expenses[$taxArr->name][$month] = $tax[$taxArr->name];
                            } else {
                                $expenses[$taxArr->name][$month] = 0;
                            }
                        } else {
                            $expenses[$taxArr->name][$month] = 0;
                        }
                    }
                }
            }

            $data['expenses'] = $expenses;
            $data['incomes'] = $incomes;

            $filter['startDateRange'] = 'Jan-' . $year;
            $filter['endDateRange'] = 'Dec-' . $year;

            return view('report.tax_summary', compact('filter'), $data);
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function yearMonth()
    {

        $month[] = __('January');
        $month[] = __('February');
        $month[] = __('March');
        $month[] = __('April');
        $month[] = __('May');
        $month[] = __('June');
        $month[] = __('July');
        $month[] = __('August');
        $month[] = __('September');
        $month[] = __('October');
        $month[] = __('November');
        $month[] = __('December');

        return $month;
    }

    public function yearList()
    {
        $starting_year = date('Y', strtotime('-5 year'));
        $ending_year = date('Y');

        foreach (range($ending_year, $starting_year) as $year) {
            $years[$year] = $year;
        }

        return $years;
    }

    public function invoiceSummary(Request $request)
    {

        if (\Auth::user()->can('invoice report')) {
            $filter['customer'] = __('All');
            $filter['status'] = __('All');

            $customer = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customer->prepend('Select Customer', '');
            $status = array_values(array_filter(Invoice::$statues, function($value) {
                return !empty($value);
            }));

            $invoices = Invoice::selectRaw('invoices.*,MONTH(send_date) as month,YEAR(send_date) as year');

            if ($request->status != '') {
                $invoices->where('status', $request->status);

                $filter['status'] = Invoice::$statues[$request->status];
            } else {
                $invoices->where('status', '!=', 0);
            }

            $invoices->where('created_by', '=', \Auth::user()->creatorId());

            if (!empty($request->start_month) && !empty($request->end_month)) {
                $start = strtotime($request->start_month);
                $end = strtotime($request->end_month);
            } else {
                $start = strtotime(date('Y-01'));
                $end = strtotime(date('Y-12'));
            }

            $invoices->where('send_date', '>=', date('Y-m-01', $start))->where('send_date', '<=', date('Y-m-t', $end));

            $filter['startDateRange'] = date('M-Y', $start);
            $filter['endDateRange'] = date('M-Y', $end);

            if (!empty($request->customer)) {
                $invoices->where('customer_id', $request->customer);
                $cust = Customer::find($request->customer);

                $filter['customer'] = !empty($cust) ? $cust->name : '';
            }

            $invoices = $invoices->with(['customer', 'category'])->get();

            $totalInvoice = 0;
            $totalDueInvoice = 0;
            $invoiceTotalArray = [];
            foreach ($invoices as $invoice) {
                $totalInvoice += $invoice->getTotal();
                $totalDueInvoice += $invoice->getDue();

                $invoiceTotalArray[$invoice->month][] = $invoice->getTotal();
            }
            $totalPaidInvoice = $totalInvoice - $totalDueInvoice;

            for ($i = 1; $i <= 12; $i++) {
                $invoiceTotal[] = array_key_exists($i, $invoiceTotalArray) ? array_sum($invoiceTotalArray[$i]) : 0;
            }

            $monthList = $month = $this->yearMonth();

            return view('report.invoice_report', compact('invoices', 'customer', 'status', 'totalInvoice', 'totalDueInvoice', 'totalPaidInvoice', 'invoiceTotal', 'monthList', 'filter'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function billSummary(Request $request)
    {
        //        dd($request->all());
        if (\Auth::user()->can('bill report')) {

            $filter['vender'] = __('All');
            $filter['status'] = __('All');

            $vender = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $vender->prepend('Select Vendor', '');
            $status = Bill::$statues;

            $bills = Bill::selectRaw('bills.*,MONTH(send_date) as month,YEAR(send_date) as year');

            if ($request->status != '') {
                $bills->where('status', '=', $request->status);

                $filter['status'] = Bill::$statues[$request->status];
            } else {
                $bills->where('status', '!=', 0);
            }

            $bills->where('created_by', '=', \Auth::user()->creatorId());

            if (!empty($request->start_month) && !empty($request->end_month)) {
                $start = strtotime($request->start_month);
                $end = strtotime($request->end_month);
            } else {
                $start = strtotime(date('Y-01'));
                $end = strtotime(date('Y-12'));
            }

            $bills->where('bill_date', '>=', date('Y-m-01', $start))->where('bill_date', '<=', date('Y-m-t', $end));
            $filter['startDateRange'] = date('M-Y', $start);
            $filter['endDateRange'] = date('M-Y', $end);

            if (!empty($request->vender)) {
                $bills->where('vender_id', $request->vender);
                $vend = Vender::find($request->vender);

                $filter['vender'] = !empty($vend) ? $vend->name : '';
            }

            $bills = $bills->with(['vender', 'category'])->get();

            $totalBill = 0;
            $totalDueBill = 0;
            $billTotalArray = [];
            foreach ($bills as $bill) {
                // Get the total for the bill using DB::raw
                $total = \DB::table('bill_products')
                    ->where('bill_id', $bill->id)
                    ->select(\DB::raw('SUM(quantity * price) as total'))
                    ->first()
                    ->total;

                // Accumulate the totals
                $totalBill += $total;
                $totalDueBill += $bill->getDue();

                // Store the total in the array, grouped by month
                $billTotalArray[$bill->month][] = $total;
            }
            $totalPaidBill = $totalBill - $totalDueBill;

            for ($i = 1; $i <= 12; $i++) {
                $billTotal[] = array_key_exists($i, $billTotalArray) ? array_sum($billTotalArray[$i]) : 0;
            }

            $monthList = $month = $this->yearMonth();

            return view('report.bill_report', compact('bills', 'vender', 'status', 'totalBill', 'totalDueBill', 'totalPaidBill', 'billTotal', 'monthList', 'filter'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function accountStatement(Request $request)
    {
        if (\Auth::user()->can('statement report')) {

            $filter['account'] = __('All');
            $filter['type'] = __('Revenue');
            $reportData['revenues'] = '';
            $reportData['payments'] = '';
            $reportData['revenueAccounts'] = '';
            $reportData['paymentAccounts'] = '';

            $account = BankAccount::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('holder_name', 'id');
            $account->prepend('Select Account', '');

            $types = [
                'revenue' => __('Revenue'),
                'payment' => __('Payment'),
            ];

            if ($request->type == 'revenue' || !isset($request->type)) {

                $revenueAccounts = Revenue::select('bank_accounts.id', 'bank_accounts.holder_name', 'bank_accounts.bank_name')->leftjoin('bank_accounts', 'revenues.account_id', '=', 'bank_accounts.id')->groupBy('revenues.account_id')->selectRaw('sum(amount) as total')->where('revenues.created_by', '=', \Auth::user()->creatorId());

                $revenues = Revenue::where('revenues.created_by', '=', \Auth::user()->creatorId())->orderBy('id', 'desc');
            }

            if ($request->type == 'payment') {
                $paymentAccounts = Payment::select('bank_accounts.id', 'bank_accounts.holder_name', 'bank_accounts.bank_name')->leftjoin('bank_accounts', 'payments.account_id', '=', 'bank_accounts.id')->groupBy('payments.account_id')->selectRaw('sum(amount) as total')->where('payments.created_by', '=', \Auth::user()->creatorId());

                $payments = Payment::where('payments.created_by', '=', \Auth::user()->creatorId())->orderBy('id', 'desc');
            }

            if (!empty($request->start_month) && !empty($request->end_month)) {
                $start = strtotime($request->start_month);
                $end = strtotime($request->end_month);
            } else {
                $start = strtotime(date('Y-m'));
                $end = strtotime(date('Y-m', strtotime("-5 month")));
            }

            $currentdate = $start;
            while ($currentdate <= $end) {
                $data['month'] = date('m', $currentdate);
                $data['year'] = date('Y', $currentdate);

                if ($request->type == 'revenue' || !isset($request->type)) {
                    $revenues->Orwhere(
                        function ($query) use ($data) {
                            $query->whereMonth('date', $data['month'])->whereYear('date', $data['year']);
                            $query->where('revenues.created_by', '=', \Auth::user()->creatorId());
                        }
                    );

                    $revenueAccounts->Orwhere(
                        function ($query) use ($data) {
                            $query->whereMonth('date', $data['month'])->whereYear('date', $data['year']);
                            $query->where('revenues.created_by', '=', \Auth::user()->creatorId());
                        }
                    );
                }

                if ($request->type == 'payment') {
                    $paymentAccounts->Orwhere(
                        function ($query) use ($data) {
                            $query->whereMonth('date', $data['month'])->whereYear('date', $data['year']);
                            $query->where('payments.created_by', '=', \Auth::user()->creatorId());
                        }
                    );
                }

                $currentdate = strtotime('+1 month', $currentdate);
            }

            if (!empty($request->account)) {
                if ($request->type == 'revenue' || !isset($request->type)) {
                    $revenues->where('account_id', $request->account);
                    $revenues->where('revenues.created_by', '=', \Auth::user()->creatorId());
                    $revenueAccounts->where('account_id', $request->account);
                    $revenueAccounts->where('revenues.created_by', '=', \Auth::user()->creatorId());
                }

                if ($request->type == 'payment') {
                    $payments->where('account_id', $request->account);
                    $payments->where('payments.created_by', '=', \Auth::user()->creatorId());

                    $paymentAccounts->where('account_id', $request->account);
                    $paymentAccounts->where('payments.created_by', '=', \Auth::user()->creatorId());
                }

                $bankAccount = BankAccount::find($request->account);
                $filter['account'] = !empty($bankAccount) ? $bankAccount->holder_name . ' - ' . $bankAccount->bank_name : '';
                if ($bankAccount->holder_name == 'Cash') {
                    $filter['account'] = 'Cash';
                }
            }

            if ($request->type == 'revenue' || !isset($request->type)) {
                $reportData['revenues'] = $revenues->get();

                $revenueAccounts->where('revenues.created_by', '=', \Auth::user()->creatorId());
                $reportData['revenueAccounts'] = $revenueAccounts->get();
            }

            if ($request->type == 'payment') {
                $reportData['payments'] = $payments->get();

                $paymentAccounts->where('payments.created_by', '=', \Auth::user()->creatorId());
                $reportData['paymentAccounts'] = $paymentAccounts->get();
                $filter['type'] = __('Payment');
            }

            $filter['startDateRange'] = date('M-Y', $start);
            $filter['endDateRange'] = date('M-Y', $end);

            return view('report.statement_report', compact('reportData', 'account', 'types', 'filter'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    // public function balanceSheet(Request $request, $view = '')
    // {
    //     if (\Auth::user()->can('bill report')) {
    //         if (!empty($request->start_date) && !empty($request->end_date)) {
    //             $start = $request->start_date;
    //             $end = $request->end_date;
    //         } else {
    //             $start = date('Y-01-01');
    //             $end = date('Y-m-d', strtotime('+1 day'));
    //         }

    //         $types = ChartOfAccountType::where('created_by', \Auth::user()->creatorId())->whereIn('name', ['Assets', 'Liabilities', 'Equity'])->get();
    //         $chartAccounts = [];
    //         foreach ($types as $type) {
    //             $subTypes = ChartOfAccountSubType::where('type', $type->id)->get();

    //             $subTypeArray = [];
    //             foreach ($subTypes as $subType) {
    //                 $accounts = ChartOfAccount::where('created_by', \Auth::user()->creatorId())
    //                     ->where('type', $type->id)
    //                     ->where('sub_type', $subType->id)
    //                     ->get();

    //                 $accountArray = [];
    //                 $totalAmount = 0;
    //                 $debitTotal = 0;
    //                 $creditTotal = 0;
    //                 $accountSubType = '';
    //                 $totalBalance = 0;
    //                 foreach ($accounts as $account) {
    //                     $getAccount = ChartOfAccount::where('name', $account->name)->where('created_by', \Auth::user()->creatorId())->first();
    //                     if ($getAccount) {
    //                         $Balance = Utility::getAccountBalance($getAccount->id, $start, $end);
    //                         $totalBalance += $Balance;
    //                     }

    //                     if ($Balance != 0) {
    //                         $data['account_id'] = $account->id;
    //                         $data['account_code'] = $account->code;
    //                         $data['account_name'] = $account->name;
    //                         $data['totalCredit'] = 0;
    //                         $data['totalDebit'] = 0;
    //                         $data['netAmount'] = $Balance;
    //                         $accountArray[] = $data;

    //                         $creditTotal += $data['totalCredit'];
    //                         $debitTotal += $data['totalDebit'];
    //                         $totalAmount += $data['netAmount'];
    //                     }
    //                 }
    //                 $totalAccountArray = [];
    //                 if ($accountArray != []) {
    //                     $dataTotal['account_id'] = '';
    //                     $dataTotal['account_code'] = '';
    //                     $dataTotal['account_name'] = 'Total ' . $subType->name;
    //                     $dataTotal['totalCredit'] = $creditTotal;
    //                     $dataTotal['totalDebit'] = $debitTotal;
    //                     $dataTotal['netAmount'] = $totalAmount;
    //                     $accountArrayTotal[] = $dataTotal;

    //                     $totalAccountArray = array_merge($accountArray, $accountArrayTotal);
    //                 }
    //                     if ($totalAccountArray != []) {
    //                     $subTypeData['subType'] = ($totalAccountArray != []) ? $subType->name : '';
    //                     $subTypeData['account'] = $totalAccountArray;
    //                     $subTypeArray[] = ($subTypeData['account'] != [] && $subTypeData['subType'] != []) ? $subTypeData : [];
    //                 }

    //             }
    //             $chartAccounts[$type->name] = $subTypeArray;
    //         }
    //         $filter['startDateRange'] = $start;
    //         $filter['endDateRange'] = $end;

    //         if ($request->view == 'horizontal' || $view == 'horizontal') {
    //             return view('report.balance_sheet_horizontal', compact('filter', 'chartAccounts'));
    //         } elseif ($view == '' || $view == 'vertical') {
    //             return view('report.balance_sheet', compact('filter', 'chartAccounts'));
    //         } else {
    //             return redirect()->back();
    //         }
    //     } else {
    //         return redirect()->back()->with('error', __('Permission Denied.'));
    //     }
    // }
    public function balanceSheet(Request $request, $view = '')
    {
        if (\Auth::user()->can('bill report')) {
            // If both dates are provided, always use the exact selected range.
            if (!empty($request->start_date) && !empty($request->end_date)) {
                $start = $request->start_date;
                $end = $request->end_date;
            } elseif (!empty($request->end_date)) {
                // Backward compatibility for older UI that only sent "As Date".
                // Use first day of selected end_date's year instead of hardcoded 2025-01-01.
                $end = $request->end_date;
                $start = date('Y-01-01', strtotime($end));
            } else {
                // Default: Get data from start of last year to end of current month
                $currentYear = (int)date('Y');
                $currentMonth = (int)date('m');
                $lastYear = $currentYear - 1;
                
                // Start date: First day of last year (e.g., 2025-01-01)
                $start = date($lastYear . '-01-01');
                
                // End date: Last day of current month (e.g., 2026-01-31)
                $end = date('Y-m-t', strtotime($currentYear . '-' . $currentMonth . '-01')); // 't' gives last day of month
            }
            $types = ChartOfAccountType::where('created_by', \Auth::user()->creatorId())->whereIn('name', ['Assets', 'Liabilities', 'Equity'])->get();
            $totalAccounts = [];
            foreach ($types as $type) {
                $subTypes = ChartOfAccountSubType::where('type', $type->id)->get();
                $subTypeArray = [];
                foreach ($subTypes as $subType) {
                    $accounts = GeneralLedger::select('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name', \DB::raw('sum(debit) as totalDebit'), \DB::raw('sum(credit) as totalCredit'));
                    $accounts->leftjoin('chart_of_accounts', 'general_ledger.account', 'chart_of_accounts.id');
                    $accounts->leftjoin('chart_of_account_types', 'chart_of_accounts.type', 'chart_of_account_types.id');
                    $accounts->where('chart_of_accounts.type', $type->id);
                    $accounts->where('chart_of_accounts.sub_type', $subType->id);
                    $accounts->where('general_ledger.created_by', \Auth::user()->creatorId());
                    $accounts->where(function($query) use ($start, $end) {
                        $query->whereBetween('general_ledger.send_date', [$start, $end])
                              ->orWhereBetween('general_ledger.created_at', [$start, $end]);
                    });
                    $accounts->groupBy('account');
                    $accounts = $accounts->get()->toArray();
                    $totalBalance = 0;
                    $creditTotal = 0;
                    $debitTotal = 0;
                    $totalAmount = 0;
                    $accountArray = [];
                    foreach ($accounts as $account) {
                        $Balance = $account['totalCredit'] - $account['totalDebit'];
                        $totalBalance += $Balance;
                        if ($Balance != 0) {
                            $data['account_id'] = $account['id'];
                            $data['account_code'] = $account['code'];
                            $data['account_name'] = $account['name'];
                            $data['totalCredit'] = 0;
                            $data['totalDebit'] = 0;
                            $data['netAmount'] = $Balance;
                            $accountArray[] = $data;
                            $creditTotal += $data['totalCredit'];
                            $debitTotal += $data['totalDebit'];
                            $totalAmount += $data['netAmount'];
                        }
                    }
                    $totalAccountArray = [];
                    if ($accountArray != []) {
                        $dataTotal['account_id'] = '';
                        $dataTotal['account_code'] = '';
                        $dataTotal['account_name'] = 'Total ' . $subType->name;
                        $dataTotal['totalCredit'] = $creditTotal;
                        $dataTotal['totalDebit'] = $debitTotal;
                        $dataTotal['netAmount'] = $totalAmount;
                        $accountArrayTotal[] = $dataTotal;
                        $totalAccountArray = array_merge($accountArray, $accountArrayTotal);
                    }
                    if ($totalAccountArray != []) {
                        $subTypeData['subType'] = ($totalAccountArray != []) ? $subType->name : '';
                        $subTypeData['account'] = $totalAccountArray;
                        $subTypeArray[] = ($subTypeData['account'] != [] && $subTypeData['subType'] != []) ? $subTypeData : [];
                    }
                }
                $totalAccounts[$type->name] = $subTypeArray;
            }

            // Calculate Profit/Loss for the period
            $profitLoss = $this->calculateProfitLoss($start, $end);

            $filter['startDateRange'] = $start;
            $filter['endDateRange'] = $end;
            if ($request->view == 'horizontal' || $view == 'horizontal') {
                return view('report.balance_sheet_horizontal', compact('filter', 'totalAccounts', 'profitLoss'));
            } elseif ($view == '' || $view == 'vertical') {
                return view('report.balance_sheet', compact('filter', 'totalAccounts', 'profitLoss'));
            } else {
                return redirect()->back();
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    private function calculateProfitLoss($start, $end)
    {
        // Get Income accounts
        $incomeTypes = ChartOfAccountType::where('created_by', \Auth::user()->creatorId())->whereIn('name', ['Income'])->get();
        $totalIncome = 0;
        
        foreach ($incomeTypes as $type) {
            $accounts = GeneralLedger::select(\DB::raw('sum(credit) as totalCredit'), \DB::raw('sum(debit) as totalDebit'));
            $accounts->leftjoin('chart_of_accounts', 'general_ledger.account', 'chart_of_accounts.id');
            $accounts->where('chart_of_accounts.type', $type->id);
            $accounts->where('general_ledger.created_by', \Auth::user()->creatorId());
            $accounts->where(function($query) use ($start, $end) {
                $query->whereBetween('general_ledger.send_date', [$start, $end])
                      ->orWhereBetween('general_ledger.created_at', [$start, $end]);
            });
            $result = $accounts->first();
            $totalIncome += ($result->totalCredit - $result->totalDebit);
        }

        // Get Expenses accounts
        $expenseTypes = ChartOfAccountType::where('created_by', \Auth::user()->creatorId())->whereIn('name', ['Expenses', 'Costs of Goods Sold'])->get();
        $totalExpenses = 0;
        
        foreach ($expenseTypes as $type) {
            $accounts = GeneralLedger::select(\DB::raw('sum(credit) as totalCredit'), \DB::raw('sum(debit) as totalDebit'));
            $accounts->leftjoin('chart_of_accounts', 'general_ledger.account', 'chart_of_accounts.id');
            $accounts->where('chart_of_accounts.type', $type->id);
            $accounts->where('general_ledger.created_by', \Auth::user()->creatorId());
            $accounts->where(function($query) use ($start, $end) {
                $query->whereBetween('general_ledger.send_date', [$start, $end])
                      ->orWhereBetween('general_ledger.created_at', [$start, $end]);
            });
            $result = $accounts->first();
            $totalExpenses += ($result->totalDebit - $result->totalCredit);
        }

        return $totalIncome - $totalExpenses;
    }

    // public function ledgerSummary(Request $request, $account = '')
    // {
    //     if (\Auth::user()->can('ledger report')) {
    //         $accounts = ChartOfAccount::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
    //         $accounts->prepend('All', '');

    //         if (!empty($request->start_date) && !empty($request->end_date)) {
    //             $start = $request->start_date;
    //             $end = $request->end_date;
    //         } else {
    //             $start = date('Y-01-01');
    //             $end = date('Y-m-d', strtotime('+1 day'));
    //         }

    //         if (!empty($request->account)) {
    //             $accountss = ChartOfAccount::where('id', $request->account)->get();
    //         } else {
    //             $accountss = ChartOfAccount::where('created_by', \Auth::user()->creatorId())->get();
    //         }

    //         $balance = 0;
    //         $debit = 0;
    //         $credit = 0;

    //         // foreach($journalItems as $item)
    //         // {
    //         //     if($item->debit > 0)
    //         //     {
    //         //         $debit += $item->debit;
    //         //     }

    //         //     else
    //         //     {
    //         //         $credit += $item->credit;
    //         //     }

    //         //     $balance = $credit - $debit;
    //         // }

    //         $filter['balance'] = $balance;
    //         $filter['credit'] = $credit;
    //         $filter['debit'] = $debit;
    //         $filter['startDateRange'] = $start;
    //         $filter['endDateRange'] = $end;
    //         return view('report.ledger_summary', compact('filter', 'accountss', 'accounts'));
    //     } else {
    //         return redirect()->back()->with('error', __('Permission Denied.'));
    //     }
    // }

    public function ledgerSummary(Request $request, $account = '')
    {
        if (\Auth::user()->can('ledger report')) {

            if (!empty($request->start_date) && !empty($request->end_date)) {
                $start = $request->start_date;
                $end = $request->end_date;
            } else {
                $start = date('Y-m-01');
                $end = date('Y-m-t');
            }
            // Account can come from query string or route parameter (e.g. from Company Tax Report link)
            $accountId = $request->account ?? $request->route('account') ?? $account;
            if (!empty($accountId)) {
                $chart_accounts = ChartOfAccount::where('id', $accountId)->where('created_by', \Auth::user()->creatorId())->get();
                $accounts = ChartOfAccount::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            } else {
                $chart_accounts = ChartOfAccount::where('created_by', \Auth::user()->creatorId())->get();
                $accounts = $chart_accounts->pluck('name', 'id');
            }

            $balance = 0;
            $debit = 0;
            $credit = 0;
            $filter['balance'] = $balance;
            $filter['credit'] = $credit;
            $filter['debit'] = $debit;
            $filter['startDateRange'] = $start;
            $filter['endDateRange'] = $end;
            return view('report.ledger_summary', compact('filter', 'chart_accounts', 'accounts'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    // public function trialBalanceSummary(Request $request)
    // {
    //     if (\Auth::user()->can('trial balance report')) {
    //         if (!empty($request->start_date) && !empty($request->end_date)) {
    //             $start = $request->start_date;
    //             $end = $request->end_date;
    //         } else {
    //             $start = date('Y-01-01');
    //             $end = date('Y-m-d', strtotime('+1 day'));
    //         }

    //         $types = ChartOfAccountType::where('created_by', \Auth::user()->creatorId())->get();
    //         $chartAccounts = [];
    //         $totalAccounts = [];
    //         $totalAccount = [];

    //         foreach ($types as $type) {
    //             $total = Utility::trialBalance($type->id, $start, $end);
    //             $name = $type->name;
    //             if (isset($totalAccount[$name])) {
    //                 $totalAccount[$name]["totalCredit"] += $total["totalCredit"];
    //                 $totalAccount[$name]["totalDebit"] += $total["totalDebit"];
    //             } else {
    //                 $totalAccount[$name] = $total;
    //             }
    //         }
    //         foreach ($totalAccount as $category => $entries) {
    //             foreach ($entries as $entry) {
    //                 $name = $entry['name'];
    //                 if (!isset($totalAccounts[$category][$name])) {
    //                     $totalAccounts[$category][$name] = [
    //                         'id' => $entry['id'],
    //                         'code' => $entry['code'],
    //                         'name' => $name,
    //                         'totalDebit' => 0,
    //                         'totalCredit' => 0,
    //                     ];
    //                 }
    //                 // dd(0 + $entry['totalDebit']);
    //                 if($entry['totalDebit'] < 0)
    //                 {
    //                     $totalAccounts[$category][$name]['totalDebit'] += 0;
    //                     $totalAccounts[$category][$name]['totalCredit'] += -$entry['totalDebit'];
    //                 }
    //                 else
    //                 {
    //                     $totalAccounts[$category][$name]['totalDebit'] += $entry['totalDebit'];
    //                     $totalAccounts[$category][$name]['totalCredit'] += $entry['totalCredit'];
    //                 }

    //             }
    //         }
    //         $filter['startDateRange'] = $start;
    //         $filter['endDateRange'] = $end;

    //         return view('report.trial_balance', compact('filter', 'totalAccounts'));
    //     } else {
    //         return redirect()->back()->with('error', __('Permission Denied.'));
    //     }
    // }

    public function trialBalanceSummary(Request $request)
    {
        if (\Auth::user()->can('trial balance report')) {

            // Set date range
            // Set date range
            $start = !empty($request->start_date)
            ? $request->start_date
            : date('Y-m-01'); // First day of the current month

            $end = !empty($request->end_date)
            ? $request->end_date
            : date('Y-m-d', strtotime('+1 day')); // Tomorrow's date


            $types = ChartOfAccountType::where('created_by', \Auth::user()->creatorId())->get();

            $totalAccounts = [];
            $totalAccount = [];

            foreach ($types as $type) {
                $total = GeneralLedger::select(
                    'chart_of_accounts.id',
                    'chart_of_accounts.code',
                    'chart_of_accounts.name',
                    \DB::raw('SUM(debit) as totalDebit'),
                    \DB::raw('SUM(credit) as totalCredit')
                )
                    ->leftJoin('chart_of_accounts', 'general_ledger.account', '=', 'chart_of_accounts.id')
                    ->leftJoin('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
                    ->where('chart_of_accounts.type', $type->id)
                    ->where('general_ledger.created_by', \Auth::user()->creatorId())
                    ->whereBetween('general_ledger.send_date', [$start, $end])
                    ->groupBy('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name')
                    ->get()
                    ->toArray();

                     // Fetch previous period transactions (before start date)
                    $previousTotals = GeneralLedger::select(
                        'chart_of_accounts.id',
                        \DB::raw('SUM(debit) as prevDebit'),
                        \DB::raw('SUM(credit) as prevCredit')
                    )
                        ->leftJoin('chart_of_accounts', 'general_ledger.account', '=', 'chart_of_accounts.id')
                        ->leftJoin('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
                        ->where('chart_of_accounts.type', $type->id)
                        ->where('general_ledger.created_by', \Auth::user()->creatorId())
                        ->where('general_ledger.send_date', '<', $start) // Get transactions before the chosen start date
                        ->groupBy('chart_of_accounts.id')
                        ->get()
                        ->keyBy('id')
                        ->toArray();

                $name = $type->name;

                if (!isset($totalAccount[$name])) {
                    $totalAccount[$name] = [];
                }

                foreach ($total as $record) {
                    $accountId = $record['id'];
                    $accountName = $record['name'];
                    // Get previous balances if available
                    $prevDebit = $previousTotals[$accountId]['prevDebit'] ?? 0;
                    $prevCredit = $previousTotals[$accountId]['prevCredit'] ?? 0;
                    if (!isset($totalAccount[$name][$accountName])) {
                        $totalAccount[$name][$accountName] = [
                            'id' => $record['id'],
                            'code' => $record['code'],
                            'name' => $accountName,
                            'totalDebit' => 0,
                            'totalCredit' => 0,
                            'prevDebit' => $prevDebit,
                            'prevCredit' => $prevCredit,
                        ];
                    }

                    // Sum values correctly
                    $totalAccount[$name][$accountName]['totalDebit'] += $record['totalDebit'] ?? 0;
                    $totalAccount[$name][$accountName]['totalCredit'] += $record['totalCredit'] ?? 0;
                }
            }

            // Formatting the final output
            foreach ($totalAccount as $category => $entries) {
                foreach ($entries as $entry) {
                    $name = $entry['name'];

                    if (!isset($totalAccounts[$category][$name])) {
                        $totalAccounts[$category][$name] = [
                            'id' => $entry['id'],
                            'code' => $entry['code'],
                            'name' => $name,
                            'totalDebit' => 0,
                            'totalCredit' => 0,
                            'prevDebit' => $entry['prevDebit'],
                            'prevCredit' => $entry['prevCredit'],
                        ];
                    }

                    if ($entry['totalDebit'] < 0) {
                        $totalAccounts[$category][$name]['totalCredit'] += -$entry['totalDebit'];
                    } else {
                        $totalAccounts[$category][$name]['totalDebit'] += $entry['totalDebit'];
                        $totalAccounts[$category][$name]['totalCredit'] += $entry['totalCredit'];
                    }
                }
            }

            $filter = [
                'startDateRange' => $start,
                'endDateRange' => $end,
            ];

            return view('report.trial_balance', compact('filter', 'totalAccounts'));
        }

        return redirect()->back()->with('error', __('Permission Denied.'));
    }

    public function trialBalanceSummaryTotal(Request $request)
{
    if (\Auth::user()->can('trial balance report')) {

        // Set date range
        $start = !empty($request->start_date)
            ? $request->start_date
            : date('Y-m-01'); // First day of the current month

        $end = !empty($request->end_date)
            ? $request->end_date
            : date('Y-m-d', strtotime('+1 day')); // Tomorrow's date

        $types = ChartOfAccountType::where('created_by', \Auth::user()->creatorId())->get();
        $totalAccounts = [];

        foreach ($types as $type) {
            // Fetch all accounts (ensuring accounts with no transactions are also included)
            $accounts = ChartOfAccount::where('type', $type->id)
                ->where('created_by', \Auth::user()->creatorId())
                ->get()
                ->keyBy('id')
                ->toArray();

            // Fetch transactions for the selected period
            $total = GeneralLedger::select(
                'chart_of_accounts.id',
                'chart_of_accounts.code',
                'chart_of_accounts.name',
                \DB::raw('COALESCE(SUM(debit), 0) as totalDebit'),
                \DB::raw('COALESCE(SUM(credit), 0) as totalCredit')
            )
                ->leftJoin('chart_of_accounts', 'general_ledger.account', '=', 'chart_of_accounts.id')
                ->leftJoin('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
                ->where('chart_of_accounts.type', $type->id)
                ->where('general_ledger.created_by', \Auth::user()->creatorId())
                ->whereBetween('general_ledger.send_date', [$start, $end])
                ->groupBy('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name')
                ->get()
                ->keyBy('id')
                ->toArray();

            // Fetch previous period transactions (before start date)
            $previousTotals = GeneralLedger::select(
                'chart_of_accounts.id',
                \DB::raw('COALESCE(SUM(debit), 0) as prevDebit'),
                \DB::raw('COALESCE(SUM(credit), 0) as prevCredit')
            )
                ->leftJoin('chart_of_accounts', 'general_ledger.account', '=', 'chart_of_accounts.id')
                ->leftJoin('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
                ->where('chart_of_accounts.type', $type->id)
                ->where('general_ledger.created_by', \Auth::user()->creatorId())
                ->where('general_ledger.send_date', '<', $start)
                ->groupBy('chart_of_accounts.id')
                ->get()
                ->keyBy('id')
                ->toArray();

            $name = $type->name;

            if (!isset($totalAccounts[$name])) {
                $totalAccounts[$name] = [];
            }

            foreach ($accounts as $accountId => $account) {
                $accountName = $account['name'];
                $code = $account['code'];

                $totalDebit = $total[$accountId]['totalDebit'] ?? 0;
                $totalCredit = $total[$accountId]['totalCredit'] ?? 0;

                // Get previous balances if available
                $prevDebit = $previousTotals[$accountId]['prevDebit'] ?? 0;
                $prevCredit = $previousTotals[$accountId]['prevCredit'] ?? 0;

                $totalAccounts[$name][$accountName] = [
                    'id' => $accountId,
                    'code' => $code,
                    'name' => $accountName,
                    'totalDebit' => $totalDebit,
                    'totalCredit' => $totalCredit,
                    'prevDebit' => $prevDebit,
                    'prevCredit' => $prevCredit,
                ];
            }
        }

        $filter = [
            'startDateRange' => $start,
            'endDateRange' => $end,
        ];

        return view('report.trial_balance_total', compact('filter', 'totalAccounts'));
    }

    return redirect()->back()->with('error', __('Permission Denied.'));
}


    public function leave(Request $request)
    {

        if (\Auth::user()->can('manage report')) {

            $branch = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $branch->prepend('Select Branch', '');

            $department = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $department->prepend('Select Department', '');

            $filterYear['branch'] = __('All');
            $filterYear['department'] = __('All');
            $filterYear['type'] = __('Monthly');
            $filterYear['dateYearRange'] = date('M-Y');
            $employees = Employee::where('created_by', \Auth::user()->creatorId());
            if (!empty($request->branch)) {
                $employees->where('branch_id', $request->branch);
                $filterYear['branch'] = !empty(Branch::find($request->branch)) ? Branch::find($request->branch)->name : '';
            }
            if (!empty($request->department)) {
                $employees->where('department_id', $request->department);
                $filterYear['department'] = !empty(Department::find($request->department)) ? Department::find($request->department)->name : '';
            }

            $employees = $employees->get();

            $leaves = [];
            $totalApproved = $totalReject = $totalPending = 0;
            foreach ($employees as $employee) {

                $employeeLeave['id'] = $employee->id;
                $employeeLeave['employee_id'] = $employee->employee_id;
                $employeeLeave['employee'] = $employee->name;

                $approved = Leave::where('employee_id', $employee->id)->where('status', 'Approved');
                $reject = Leave::where('employee_id', $employee->id)->where('status', 'Reject');
                $pending = Leave::where('employee_id', $employee->id)->where('status', 'Pending');

                if ($request->type == 'monthly' && !empty($request->month)) {
                    $month = date('m', strtotime($request->month));
                    $year = date('Y', strtotime($request->month));

                    $approved->whereMonth('applied_on', $month)->whereYear('applied_on', $year);
                    $reject->whereMonth('applied_on', $month)->whereYear('applied_on', $year);
                    $pending->whereMonth('applied_on', $month)->whereYear('applied_on', $year);

                    $filterYear['dateYearRange'] = date('M-Y', strtotime($request->month));
                    $filterYear['type'] = __('Monthly');
                } elseif (!isset($request->type)) {
                    $month = date('m');
                    $year = date('Y');
                    $monthYear = date('Y-m');

                    $approved->whereMonth('applied_on', $month)->whereYear('applied_on', $year);
                    $reject->whereMonth('applied_on', $month)->whereYear('applied_on', $year);
                    $pending->whereMonth('applied_on', $month)->whereYear('applied_on', $year);

                    $filterYear['dateYearRange'] = date('M-Y', strtotime($monthYear));
                    $filterYear['type'] = __('Monthly');
                }

                if ($request->type == 'yearly' && !empty($request->year)) {
                    $approved->whereYear('applied_on', $request->year);
                    $reject->whereYear('applied_on', $request->year);
                    $pending->whereYear('applied_on', $request->year);

                    $filterYear['dateYearRange'] = $request->year;
                    $filterYear['type'] = __('Yearly');
                }

                $approved = $approved->count();
                $reject = $reject->count();
                $pending = $pending->count();

                $totalApproved += $approved;
                $totalReject += $reject;
                $totalPending += $pending;

                $employeeLeave['approved'] = $approved;
                $employeeLeave['reject'] = $reject;
                $employeeLeave['pending'] = $pending;

                $leaves[] = $employeeLeave;
            }

            $starting_year = date('Y', strtotime('-5 year'));
            $ending_year = date('Y', strtotime('+5 year'));

            $filterYear['starting_year'] = $starting_year;
            $filterYear['ending_year'] = $ending_year;

            $filter['totalApproved'] = $totalApproved;
            $filter['totalReject'] = $totalReject;
            $filter['totalPending'] = $totalPending;

            return view('report.leave', compact('department', 'branch', 'leaves', 'filterYear', 'filter'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function employeeLeave(Request $request, $employee_id, $status, $type, $month, $year)
    {
        if (\Auth::user()->can('manage report')) {
            $leaveTypes = LeaveType::where('created_by', \Auth::user()->creatorId())->get();
            $leaves = [];
            foreach ($leaveTypes as $leaveType) {
                $leave = new Leave();
                $leave->title = $leaveType->title;
                $totalLeave = Leave::where('employee_id', $employee_id)->where('status', $status)->where('leave_type_id', $leaveType->id);
                if ($type == 'yearly') {
                    $totalLeave->whereYear('applied_on', $year);
                } else {
                    $m = date('m', strtotime($month));
                    $y = date('Y', strtotime($month));

                    $totalLeave->whereMonth('applied_on', $m)->whereYear('applied_on', $y);
                }
                $totalLeave = $totalLeave->count();

                $leave->total = $totalLeave;
                $leaves[] = $leave;
            }

            $leaveData = Leave::where('employee_id', $employee_id)->where('status', $status);
            if ($type == 'yearly') {
                $leaveData->whereYear('applied_on', $year);
            } else {
                $m = date('m', strtotime($month));
                $y = date('Y', strtotime($month));

                $leaveData->whereMonth('applied_on', $m)->whereYear('applied_on', $y);
            }

            $leaveData = $leaveData->get();

            return view('report.leaveShow', compact('leaves', 'leaveData'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function monthlyAttendance(Request $request)
    {
        if (\Auth::user()->can('manage report')) {
            $branch = Branch::where('created_by', '=', \Auth::user()->creatorId())->get();
            $department = Department::where('created_by', '=', \Auth::user()->creatorId())->get();

            $data['branch'] = __('All');
            $data['department'] = __('All');

            $employees = Employee::select('id', 'name', 'required_latitude', 'required_longitude', 'startTime', 'endTime');
            if (!empty($request->employee_id) && $request->employee_id[0] != 0) {
                $employees->whereIn('id', $request->employee_id);
            }
            $employees = $employees->where('created_by', \Auth::user()->creatorId());

            if (!empty($request->branch)) {
                $employees->where('branch_id', $request->branch);
                $data['branch'] = !empty(Branch::find($request->branch)) ? Branch::find($request->branch)->name : '';
            }

            if (!empty($request->department)) {
                $employees->where('department_id', $request->department);
                $data['department'] = !empty(Department::find($request->department)) ? Department::find($request->department)->name : '';
            }

            $employees = $employees->get()->pluck('name', 'id');

            if (!empty($request->month)) {
                $currentdate = strtotime($request->month);
                $month = date('m', $currentdate);
                $year = date('Y', $currentdate);
                $curMonth = date('M-Y', strtotime($request->month));
            } else {
                $month = date('m');
                $year = date('Y');
                $curMonth = date('M-Y', strtotime($year . '-' . $month));
            }

            $num_of_days = date('t', mktime(0, 0, 0, $month, 1, $year));
            for ($i = 1; $i <= $num_of_days; $i++) {
                $dates[] = str_pad($i, 2, '0', STR_PAD_LEFT);
            }

            $employeesAttendance = [];
            $totalPresent = $totalLeave = $totalEarlyLeave = $totalOvertimeInstances = $onTimeAttendance = $onTimeLeft = 0;
            $ovetimeHours = $overtimeMins = $earlyleaveHours = $earlyleaveMins = $lateHours = $lateMins = 0;

            foreach ($employees as $id => $employee) {
                $attendances = [];
                $attendanceStatus = [];
                $presentDays = 0;
                $absentDays = 0;
                $totalLate = 0;
                $totalEarlyLeaving = 0;
                $totalOvertimeInstances = 0;
                $totalLeaveDays = 0;
                $correctCheckIns = 0;
                $correctCheckOuts = 0;

                $requiredLatitude = Employee::where('id', $id)->first()->required_latitude;
                $requiredLongitude = Employee::where('id', $id)->first()->required_longitude;
                $startTime = Employee::where('id', $id)->first()->startTime;
                $endTime = Employee::where('id', $id)->first()->endTime;

                foreach ($dates as $date) {
                    $dateFormat = $year . '-' . $month . '-' . $date;

                    if ($dateFormat <= date('Y-m-d')) {
                        $employeeAttendance = AttendanceEmployee::where('employee_id', $id)->where('date', $dateFormat)->first();
                        $checkInLocation = $employeeAttendance ? $employeeAttendance->latitudeIn . ', ' . $employeeAttendance->longitudeIn : 'N/A';
                        $checkOutLocation = $employeeAttendance ? $employeeAttendance->latitudeOut . ', ' . $employeeAttendance->longitudeOut : 'N/A';
                        $requiredLocation = $employeeAttendance ? $requiredLatitude . ', ' . $requiredLongitude : 'N/A';


                        $checkInStatus = '';
                        $checkOutStatus = '';
                        $onTimeCheckIn = false;
                        $onTimeCheckOut = false;

                        if (!empty($employeeAttendance) && $employeeAttendance->status != '') {
                            $attendanceStatus[$date] = 'P';
                            $presentDays += 1;

                            // Check if check-in is before or at the start time
                            if (strtotime($employeeAttendance->clock_in) <= strtotime($startTime)) {
                                $onTimeCheckIn = true;
                                $onTimeAttendance += 1;
                            }

                            // Check if check-out is before or at the end time
                            if (strtotime($employeeAttendance->clock_out) <= strtotime($endTime)) {
                                $onTimeCheckOut = true;
                                $onTimeLeft += 1;
                            }

                            if ($employeeAttendance->overtime > 0) {
                                $totalOvertimeInstances += 1;
                                $ovetimeHours += date('h', strtotime($employeeAttendance->overtime));
                                $overtimeMins += date('i', strtotime($employeeAttendance->overtime));
                            }

                            if ($employeeAttendance->early_leaving > 0) {
                                $totalEarlyLeaving += 1;
                                $earlyleaveHours += date('h', strtotime($employeeAttendance->early_leaving));
                                $earlyleaveMins += date('i', strtotime($employeeAttendance->early_leaving));
                            }

                            if ($employeeAttendance->late > 0) {
                                $totalLate += 1;
                                $lateHours += date('h', strtotime($employeeAttendance->late));
                                $lateMins += date('i', strtotime($employeeAttendance->late));
                            }

                            // Check check-in and check-out locations
                            if ($employeeAttendance->latitudeIn == $requiredLatitude && $employeeAttendance->longitudeIn == $requiredLongitude) {
                                $checkInStatus = 'Correct';
                                $correctCheckIns += 1;
                            } else {
                                $checkInStatus = 'Incorrect';
                            }

                            if ($employeeAttendance->latitudeOut == $requiredLatitude && $employeeAttendance->longitudeOut == $requiredLongitude) {
                                $checkOutStatus = 'Correct';
                                $correctCheckOuts += 1;
                            } else {
                                $checkOutStatus = 'Incorrect';
                            }

                            $attendanceStatus[$date] .= " (Check-In: $checkInStatus, Check-In Location: $checkInLocation, Check-Out: $checkOutStatus, Check-Out Location: $checkOutLocation Required Location: $requiredLocation)";
                        } elseif (!empty($employeeAttendance) && $employeeAttendance->status == 'Leave') {
                            $attendanceStatus[$date] = 'A';
                            $totalLeaveDays += 1;
                        } else {
                            $attendanceStatus[$date] = '';
                        }
                    } else {
                        $attendanceStatus[$date] = '';
                    }
                }

                $attendances['name'] = Employee::where('id', $id)->first()->name;
                $attendances['present_days'] = $presentDays;
                $attendances['absent_days'] = $num_of_days - $presentDays - $totalLeaveDays;
                $attendances['total_late'] = $totalLate;
                $attendances['total_early_leaving'] = $totalEarlyLeaving;
                $attendances['total_overtime_instances'] = $totalOvertimeInstances;
                $attendances['on_time_attendance'] = $onTimeAttendance;
                $attendances['on_time_left'] = $onTimeLeft;
                $attendances['total_days'] = $num_of_days;
                $attendances['totalLeaveDays'] = $totalLeaveDays;
                $attendances['status'] = $attendanceStatus;

                $employeesAttendance[] = $attendances;
            }

            $totalOverTime = $ovetimeHours + ($overtimeMins / 60);
            $totalEarlyleave = $earlyleaveHours + ($earlyleaveMins / 60);
            $totalLate = $lateHours + ($lateMins / 60);

            $data['totalOvertime'] = $totalOverTime;
            $data['totalEarlyLeave'] = $totalEarlyleave;
            $data['totalLate'] = $totalLate;
            $data['totalPresent'] = $totalPresent;
            $data['totalLeave'] = $totalLeave;
            $data['curMonth'] = $curMonth;
            // dd($employeesAttendance);
            return view('report.monthlyAttendance', compact('employeesAttendance', 'branch', 'department', 'dates', 'data'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function payroll(Request $request)
    {

        if (\Auth::user()->can('manage report')) {
            $branch = Branch::where('created_by', '=', \Auth::user()->creatorId())->get();
            $department = Department::where('created_by', '=', \Auth::user()->creatorId())->get();
            $employees = Employee::select('id', 'name');
            if (!empty($request->employee_id) && $request->employee_id[0] != 0) {
                $employees->where('id', $request->employee_id);
            }
            $employees = $employees->where('created_by', \Auth::user()->creatorId());

            $data['branch'] = __('All');
            $data['department'] = __('All');
            $filterYear['branch'] = __('All');
            $filterYear['department'] = __('All');
            $filterYear['type'] = __('Monthly');
            $filterYear['dateYearRange'] = '';

            $payslips = PaySlip::select('pay_slips.*', 'employees.name')->leftjoin('employees', 'pay_slips.employee_id', '=', 'employees.id')->where('pay_slips.created_by', \Auth::user()->creatorId());

            if ($request->type == 'monthly' && !empty($request->month)) {

                $payslips->where('salary_month', $request->month);

                $filterYear['dateYearRange'] = date('M-Y', strtotime($request->month));
                $filterYear['type'] = __('Monthly');
            } elseif (!isset($request->type)) {
                $month = date('Y-m');

                $payslips->where('salary_month', $month);

                $filterYear['dateYearRange'] = date('M-Y', strtotime($month));
                $filterYear['type'] = __('Monthly');
            }

            if ($request->type == 'yearly' && !empty($request->year)) {
                $startMonth = $request->year . '-01';
                $endMonth = $request->year . '-12';
                $payslips->where('salary_month', '>=', $startMonth)->where('salary_month', '<=', $endMonth);

                $filterYear['dateYearRange'] = $request->year;
                $filterYear['type'] = __('Yearly');
            }

            if (!empty($request->branch)) {
                $payslips->where('employees.branch_id', $request->branch);

                $filterYear['branch'] = !empty(Branch::find($request->branch)) ? Branch::find($request->branch)->name : '';
            }

            if (!empty($request->department)) {

                $payslips->where('employees.department_id', $request->department);

                $filterYear['department'] = !empty(Department::find($request->department)) ? Department::find($request->department)->name : '';
            }

            $employees = $employees->get()->pluck('name', 'id')->toArray();

            $payslips = $payslips->whereIn('name', $employees)->with(['employees'])->get();

            $totalBasicSalary = $totalNetSalary = $totalAllowance = $totalCommision = $totalLoan = $totalSaturationDeduction = $totalOtherPayment = $totalOverTime = 0;

            foreach ($payslips as $payslip) {
                $totalBasicSalary += $payslip->basic_salary;
                $totalNetSalary += $payslip->net_payble;

                $allowances = json_decode($payslip->allowance);
                foreach ($allowances as $allowance) {
                    $totalAllowance += $allowance->amount;
                }

                $commisions = json_decode($payslip->commission);
                foreach ($commisions as $commision) {
                    $totalCommision += $commision->amount;
                }

                $loans = json_decode($payslip->loan);
                foreach ($loans as $loan) {
                    $totalLoan += $loan->amount;
                }

                $saturationDeductions = json_decode($payslip->saturation_deduction);
                foreach ($saturationDeductions as $saturationDeduction) {
                    $totalSaturationDeduction += $saturationDeduction->amount;
                }

                $otherPayments = json_decode($payslip->other_payment);
                foreach ($otherPayments as $otherPayment) {
                    $totalOtherPayment += $otherPayment->amount;
                }

                $overtimes = json_decode($payslip->overtime);
                foreach ($overtimes as $overtime) {
                    $days = $overtime->number_of_days;
                    $hours = $overtime->hours;
                    $rate = $overtime->rate;

                    $totalOverTime += ($rate * $hours) * $days;
                }
            }

            $filterData['totalBasicSalary'] = $totalBasicSalary;
            $filterData['totalNetSalary'] = $totalNetSalary;
            $filterData['totalAllowance'] = $totalAllowance;
            $filterData['totalCommision'] = $totalCommision;
            $filterData['totalLoan'] = $totalLoan;
            $filterData['totalSaturationDeduction'] = $totalSaturationDeduction;
            $filterData['totalOtherPayment'] = $totalOtherPayment;
            $filterData['totalOverTime'] = $totalOverTime;

            $starting_year = date('Y', strtotime('-5 year'));
            $ending_year = date('Y', strtotime('+5 year'));

            $filterYear['starting_year'] = $starting_year;
            $filterYear['ending_year'] = $ending_year;

            return view('report.payroll', compact('payslips', 'filterData', 'branch', 'department', 'filterYear'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    //branch wise department get in Payroll report
    public function getPayrollDepartment(Request $request)
    {
        if ($request->branch_id == 0) {
            $departments = Department::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
        } else {
            $departments = Department::where('created_by', '=', \Auth::user()->creatorId())->where('branch_id', $request->branch_id)->get()->pluck('name', 'id')->toArray();
        }

        return response()->json($departments);
    }

    public function getPayrollEmployee(Request $request)
    {
        if (!$request->department_id) {
            $employees = Employee::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
        } else {
            $employees = Employee::where('created_by', '=', \Auth::user()->creatorId())->where('department_id', $request->department_id)->get()->pluck('name', 'id')->toArray();
        }
        return response()->json($employees);
    }

    public function exportCsv($filter_month, $branch, $department)
    {

        $data['branch'] = __('All');
        $data['department'] = __('All');
        $employees = Employee::select('id', 'name')->where('created_by', \Auth::user()->creatorId());
        if ($branch != 0) {
            $employees->where('branch_id', $branch);
            $data['branch'] = !empty(Branch::find($branch)) ? Branch::find($branch)->name : '';
        }

        if ($department != 0) {
            $employees->where('department_id', $department);
            $data['department'] = !empty(Department::find($department)) ? Department::find($department)->name : '';
        }

        $employees = $employees->get()->pluck('name', 'id');

        $currentdate = strtotime($filter_month);
        $month = date('m', $currentdate);
        $year = date('Y', $currentdate);
        $data['curMonth'] = date('M-Y', strtotime($filter_month));

        $fileName = $data['branch'] . ' ' . __('Branch') . ' ' . $data['curMonth'] . ' ' . __('Attendance Report of') . ' ' . $data['department'] . ' ' . __('Department') . ' ' . '.csv';

        $num_of_days = date('t', mktime(0, 0, 0, $month, 1, $year));
        for ($i = 1; $i <= $num_of_days; $i++) {
            $dates[] = str_pad($i, 2, '0', STR_PAD_LEFT);
        }

        foreach ($employees as $id => $employee) {
            $attendances['name'] = $employee;

            foreach ($dates as $date) {

                $dateFormat = $year . '-' . $month . '-' . $date;

                if ($dateFormat <= date('Y-m-d')) {
                    $employeeAttendance = AttendanceEmployee::where('employee_id', $id)->where('date', $dateFormat)->first();

                    if (!empty($employeeAttendance) && $employeeAttendance->status == 'Present') {
                        $attendanceStatus[$date] = 'P';
                    } elseif (!empty($employeeAttendance) && $employeeAttendance->status == 'Leave') {
                        $attendanceStatus[$date] = 'A';
                    } else {
                        $attendanceStatus[$date] = '-';
                    }
                } else {
                    $attendanceStatus[$date] = '-';
                }
                $attendances[$date] = $attendanceStatus[$date];
            }

            $employeesAttendance[] = $attendances;
        }

        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0",
        );

        $emp = array(
            'employee',
        );

        $columns = array_merge($emp, $dates);

        $callback = function () use ($employeesAttendance, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($employeesAttendance as $attendance) {
                fputcsv($file, str_replace('"', '', array_values($attendance)));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function productStock(Request $request)
    {
        if (\Auth::user()->can('stock report')) {
            $stocks = StockReport::with(['product'])->where('created_by', '=', \Auth::user()->creatorId())->get();
            return view('report.product_stock_report', compact('stocks'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function itemMaster(Request $request)
    {
        if (\Auth::user()->can('stock report')) {
            $creatorId = \Auth::user()->creatorId();
            
            // Base query for sub-products with product relationships
            $query = \App\Models\SubProduct::where('sub_products.created_by', $creatorId)
                ->where('sub_products.flag', '!=', 2)
                ->with(['productService.category', 'productService.brand', 'productService.subBrand', 'warehouse']);
            
            // Apply filters
            if ($request->filled('q')) {
                $q = trim($request->q);
                $query->where(function($subQ) use ($q) {
                    $subQ->where('sub_products.chassis_no', 'like', "%{$q}%")
                         ->orWhereHas('productService', function($prodQ) use ($q) {
                             $prodQ->where('name', 'like', "%{$q}%")
                                   ->orWhere('sku', 'like', "%{$q}%");
                         });
                });
            }
            
            if ($request->filled('category_id')) {
                $query->whereHas('productService', function($q) use ($request) {
                    $q->where('category_id', $request->category_id);
                });
            }
            
            if ($request->filled('product_id')) {
                $query->where('sub_products.product_id', $request->product_id);
            }
            
            if ($request->filled('warehouse_id')) {
                $query->where('sub_products.warehouse_id', $request->warehouse_id);
            }
            
            // Get sub-products
            $subProducts = $query->get();
            
            // Prepare data for report
            $reportData = [];
            
            foreach ($subProducts as $subProduct) {
                $product = $subProduct->productService;
                
                // Current quantity from sub_product
                $currentQty = $subProduct->quantity ?? 0;
                
                // Reserved qty (booked items)
                $reservedQty = ($subProduct->booked != 0) ? $currentQty : 0;
                
                // Free qty (not booked)
                $freeQty = ($subProduct->booked == 0) ? $currentQty : 0;
                
                // Average cost from product
                $avgCost = $product->avg_cost ?? 0;
                
                // Sell price from product
                $sellPrice = $product->sale_price ?? 0;
                
                // Calculate sell price with VAT
                $sellPriceWithVat = $sellPrice;
                if ($product->tax_id) {
                    $taxIds = explode(',', $product->tax_id);
                    $totalVatRate = 0;
                    foreach ($taxIds as $taxId) {
                        $taxId = trim($taxId);
                        if (!empty($taxId)) {
                            $tax = \App\Models\Tax::find($taxId);
                            if ($tax) {
                                $totalVatRate += (float) $tax->rate;
                            }
                        }
                    }
                    if ($totalVatRate > 0) {
                        $sellPriceWithVat = $sellPrice * (1 + ($totalVatRate / 100));
                    }
                }
                
                // Get sell price from POS or Invoice based on where item is sold or booked
                $soldPrice = null;
                $soldSource = null;
                
                // Check if item is sold/booked via Invoice
                if ($subProduct->invoice_id) {
                    $invoiceProduct = \App\Models\InvoiceProduct::where('sub_product_id', $subProduct->id)
                        ->where('invoice_id', $subProduct->invoice_id)
                        ->first();
                    if ($invoiceProduct && $invoiceProduct->price) {
                        $soldPrice = $invoiceProduct->price;
                        $soldSource = 'Invoice';
                    }
                }
                
                // Check if item is sold/booked via POS (if not found in invoice)
                if (!$soldPrice && $subProduct->pos_id) {
                    $posProduct = \App\Models\PosProduct::where('sub_product_id', $subProduct->id)
                        ->where('pos_id', $subProduct->pos_id)
                        ->first();
                    if ($posProduct && $posProduct->price) {
                        $soldPrice = $posProduct->price;
                        $soldSource = 'POS';
                    }
                }
                
                // If still not found, check booked items via relationships
                if (!$soldPrice && $subProduct->booked != 0) {
                    // Check invoice products for this sub-product
                    $invoiceProduct = \App\Models\InvoiceProduct::where('sub_product_id', $subProduct->id)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    if ($invoiceProduct && $invoiceProduct->price) {
                        $soldPrice = $invoiceProduct->price;
                        $soldSource = 'Invoice';
                    } else {
                        // Check POS products for this sub-product
                        $posProduct = \App\Models\PosProduct::where('sub_product_id', $subProduct->id)
                            ->orderBy('created_at', 'desc')
                            ->first();
                        if ($posProduct && $posProduct->price) {
                            $soldPrice = $posProduct->price;
                            $soldSource = 'POS';
                        }
                    }
                }
                
                $reportData[] = [
                    'sub_product_id' => $subProduct->id,
                    'part_no' => $subProduct->chassis_no ?? '',
                    'product_name' => $product->name ?? '',
                    'sku' => $product->sku ?? '',
                    'stock_qty' => $currentQty,
                    'reserved_qty' => $reservedQty,
                    'free_qty' => $freeQty,
                    'avg_cost' => $avgCost,
                    'sell_price' => $sellPrice,
                    'sell_price_with_vat' => $sellPriceWithVat,
                    'sold_price' => $soldPrice,
                    'sold_source' => $soldSource,
                    'product_id' => $product->id ?? null,
                ];
            }
            
            // Get custom fields for sub-products
            $customFields = \App\Models\CustomField::where('created_by', $creatorId)
                ->where('module', 'sub-product')
                ->get();
            
            // Get custom field values for all sub-products
            $customFieldValues = [];
            $subProductIds = $subProducts->pluck('id')->toArray();
            if (!empty($subProductIds)) {
                $customFieldValuesData = \App\Models\CustomFieldValue::whereIn('record_id', $subProductIds)
                    ->whereIn('field_id', $customFields->pluck('id'))
                    ->get()
                    ->groupBy('record_id')
                    ->map(function($values) {
                        return $values->keyBy('field_id')->map(function($item) {
                            return $item->value;
                        });
                    });
                
                foreach ($subProductIds as $subProductId) {
                    $customFieldValues[$subProductId] = $customFieldValuesData->get($subProductId, collect());
                }
            }
            
            // Get filter options
            $categories = \App\Models\ProductServiceCategory::where('created_by', $creatorId)
                ->pluck('name', 'id');
            $products = \App\Models\ProductService::where('created_by', $creatorId)
                ->pluck('name', 'id');
            $warehouses = \App\Models\warehouse::where('created_by', $creatorId)
                ->pluck('name', 'id');
            
            return view('report.item_master', compact('reportData', 'customFields', 'customFieldValues', 'categories', 'products', 'warehouses'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    //for export in account statement report
    public function export()
    {
        $name = 'account_statement' . date('Y-m-d i:h:s');
        $data = Excel::download(new AccountStatementExport(), $name . '.xlsx');

        return $data;
    }

    /**
     * Export customer statement report to Excel using current filter (customer, start_month, end_month, account).
     */
    public function exportCustomerStatement(Request $request)
    {
        if (!\Auth::user()->can('statement report')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
        $customer = $request->query('customer');
        $startMonth = $request->query('start_month');
        $endMonth = $request->query('end_month');
        $account = $request->query('account');
        $creatorId = \Auth::user()->creatorId();
        $name = 'customer_statement_' . date('Y-m-d_His') . '.xlsx';
        return Excel::download(
            new CustomerStatementExport($customer, $startMonth, $endMonth, $account, $creatorId),
            $name
        );
    }
    // for export in product stock report
    public function stock_export()
    {
        $name = 'Product_Stock' . date('Y-m-d i:h:s');
        $data = Excel::download(new ProductStockExport(), $name . '.xlsx');

        return $data;
    }

    // for export in payroll report
    public function PayrollReportExport(Request $request)
    {
        $name = 'Payroll_' . date('Y-m-d i:h:s');
        $data = \Excel::download(new PayrollExport(), $name . '.xlsx');

        return $data;
    }

    // for export in leave report
    public function LeaveReportExport()
    {
        $name = 'leave_' . date('Y-m-d i:h:s');
        $data = \Excel::download(new LeaveReportExport(), $name . '.xlsx');

        return $data;
    }

    //branch wise department get in monthly-attendance report
    public function getdepartment(Request $request)
    {
        if ($request->branch_id == 0) {
            $departments = Department::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
        } else {
            $departments = Department::where('created_by', '=', \Auth::user()->creatorId())->where('branch_id', $request->branch_id)->get()->pluck('name', 'id')->toArray();
        }

        return response()->json($departments);
    }

    public function getemployee(Request $request)
    {
        if (!$request->department_id) {
            $employees = Employee::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
        } else {
            $employees = Employee::where('created_by', '=', \Auth::user()->creatorId())->where('department_id', $request->department_id)->get()->pluck('name', 'id')->toArray();
        }
        return response()->json($employees);
    }

    public function leadreport(Request $request)
    {
        $user = \Auth::user();
        $leads = Lead::orderBy('id');
        $leads->where('created_by', \Auth::user()->creatorId());

        $user_week_lead = Lead::orderBy('Date')->whereBetween('Date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get()->groupBy(function ($item) {
            return $item->created_at->format('Y-m-d');
        });
        $carbaoDay = Carbon::now()->startOfWeek();

        $weeks = [];
        for ($i = 0; $i < 7; $i++) {
            $weeks[$carbaoDay->startOfWeek()->addDay($i)->format('Y-m-d')] = 0;
        }
        foreach ($user_week_lead as $name => $leads) {
            $weeks[$name] = $leads->count();
        }

        $devicearray = [];
        $devicearray['label'] = [];
        $devicearray['data'] = [];

        foreach ($weeks as $name => $leads) {
            $devicearray['label'][] = Carbon::parse($name)->format('l');
            $devicearray['data'][] = $leads;
        }
        $leads = Lead::where('created_by', '=', \Auth::user()->creatorId())->get();

        $lead_source = Source::where('created_by', \Auth::user()->id)->get();

        $leadsourceName = [];
        $leadsourceeData = [];
        foreach ($lead_source as $lead_source_data) {
            $lead_source = lead::where('created_by', \Auth::user()->id)->where('sources', $lead_source_data->id)->count();
            $leadsourceName[] = $lead_source_data->name;
            $leadsourceeData[] = $lead_source;
        }

        // monthly report

        $labels = [];
        $data = [];

        if (!empty($request->start_month) && !empty($request->end_month)) {
            $start = strtotime($request->start_month);
            $end = strtotime($request->end_month);
        } else {
            $start = strtotime(date('Y-01'));
            $end = strtotime(date('Y-12'));
        }

        $leads = Lead::orderBy('id');
        $leads->where('date', '>=', date('Y-m-01', $start))->where('date', '<=', date('Y-m-t', $end));
        $leads->where('created_by', \Auth::user()->creatorId());
        $leads = $leads->get();

        $currentdate = $start;
        
        //staff report
        if ($request->type == "staff_repport") {
            $form_date = date('Y-m-d H:i:s', strtotime($request->From_Date));
            $to_date = date('Y-m-d H:i:s', strtotime($request->To_Date));

            if (!empty($request->From_Date) && !empty($request->To_Date)) {

                $lead_user = User::where('created_by', \Auth::user()->id)->where('type', '!=', 'client')->get();
                $leaduserName = [];
                $leadusereData = [];
                foreach ($lead_user as $lead_user_data) {
                    $lead_user = UserLead::where('user_id', $lead_user_data->id)->whereBetween('created_at', [$form_date, $to_date])->count();
                    $leaduserName[] = $lead_user_data->name;
                    $leadusereData[] = $lead_user;
                }
                return response()->json(['data' => $leadusereData, 'name' => $leaduserName]);
            }
        } else {
            $lead_user = User::where('created_by', \Auth::user()->id)->where('type', '!=', 'client')->get();
            $leaduserName = [];
            $leadusereData = [];
            foreach ($lead_user as $lead_user_data) {
                $lead_user = UserLead::where('user_id', $lead_user_data->id)->count();
                $leaduserName[] = $lead_user_data->name;
                $leadusereData[] = $lead_user;
            }
        }
        while ($currentdate <= $end) {
            $month = date('m', $currentdate);
            $year = date('Y');

            if (!empty($request->start_month)) {
                $leadFilter = Lead::where('created_by', \Auth::user()->creatorId())->whereMonth('date', $request->start_month)->whereYear('date', $year)->get();
            } else {
                $leadFilter = Lead::where('created_by', \Auth::user()->creatorId())->whereMonth('date', $month)->whereYear('date', $year)->get();
                // dd($request->leadFilter);
            }

            $data[] = count($leadFilter);
            $labels[] = date('M Y', $currentdate);
            $currentdate = strtotime('+1 month', $currentdate);

            if (!empty($request->start_month)) {
                $cdate = '01-' . $request->start_month . '-' . $year;
                $mstart = strtotime($cdate);
                $labelss[] = date('M Y', $mstart);

                return response()->json(['data' => $data, 'name' => $labelss]);
            }
        }

        if (empty($request->start_month) && !empty($request->all())) {
            return response()->json(['data' => $data, 'name' => $labels]);
        }
        $filter['startDateRange'] = date('M-Y', $start);
        $filter['endDateRange'] = date('M-Y', $end);

        $monthList = $month = $this->yearMonth();


        $lead_pipeline = Pipeline::where('created_by', \Auth::user()->id)->get();

        $leadpipelineName = [];
        $leadpipelineeData = [];
        foreach ($lead_pipeline as $lead_pipeline_data) {
            $lead_pipeline = lead::where('created_by', \Auth::user()->id)->where('pipeline_id', $lead_pipeline_data->id)->count();
            $leadpipelineName[] = $lead_pipeline_data->name;
            $leadpipelineeData[] = $lead_pipeline;
        }

        return view('report.lead', compact('devicearray', 'leadsourceName', 'leadsourceeData', 'labels', 'data', 'filter', 'monthList', 'leads', 'leaduserName', 'leadusereData', 'user', 'leadpipelineName', 'leadpipelineeData'));
    }

    public function dealreport(Request $request)
    {
        $user = \Auth::user();
        $deals = Deal::orderBy('id');
        $deals->where('created_by', \Auth::user()->creatorId());

        $user_week_deal = Deal::orderBy('created_at')->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get()->groupBy(function ($item) {
            return $item->created_at->format('Y-m-d');
        });

        $carbaoDay = Carbon::now()->startOfWeek();
        $weeks = [];
        for ($i = 0; $i < 7; $i++) {
            $weeks[$carbaoDay->startOfWeek()->addDay($i)->format('Y-m-d')] = 0;
        }
        foreach ($user_week_deal as $name => $deals) {
            $weeks[$name] = $deals->count();
        }

        $devicearray = [];
        $devicearray['label'] = [];
        $devicearray['data'] = [];
        foreach ($weeks as $name => $deals) {
            $devicearray['label'][] = Carbon::parse($name)->format('l');
            $devicearray['data'][] = $deals;
        }
        $deals = Deal::where('created_by', '=', \Auth::user()->creatorId())->get();

        $deals_source = Source::where('created_by', \Auth::user()->id)->get();

        $dealsourceName = [];
        $dealsourceeData = [];
        foreach ($deals_source as $deals_source_data) {
            $deals_source = Deal::where('created_by', \Auth::user()->id)->where('sources', $deals_source_data->id)->count();
            $dealsourceName[] = $deals_source_data->name;
            $dealsourceeData[] = $deals_source;
        }
        if ($request->type == "deal_staff_repport") {
            $from_date = date('Y-m-d H:i:s', strtotime($request->From_Date));
            $to_date = date('Y-m-d H:i:s', strtotime($request->To_Date));

            if (!empty($request->From_Date) && !empty($request->To_Date)) {
                $user_deal = $this->deals();
                $dealUserData = [];
                $dealUserName = [];
                foreach ($user_deal as $user_deal_data) {

                    $user_deals = UserDeal::where('user_id', $user_deal_data->id)->whereBetween('created_at', [$from_date, $to_date])->count();
                    $dealUserName[] = $user_deal_data->name;
                    $dealUserData[] = $user_deals;
                }
                return response()->json(['data' => $dealUserData, 'name' => $dealUserName]);
            }
        } else {
            $user_deal = $this->deals();
            $dealUserData = [];
            $dealUserName = [];
            foreach ($user_deal as $user_deal_data) {
                $user_deals = UserDeal::where('user_id', $user_deal_data->id)->count();

                $dealUserName[] = $user_deal_data->name;
                $dealUserData[] = $user_deals;
            }
        }

        $deal_pipeline = Pipeline::where('created_by', \Auth::user()->id)->get();

        $dealpipelineName = [];
        $dealpipelineeData = [];
        foreach ($deal_pipeline as $deal_pipeline_data) {
            $deal_pipeline = Deal::where('created_by', \Auth::user()->id)->where('pipeline_id', $deal_pipeline_data->id)->count();
            $dealpipelineName[] = $deal_pipeline_data->name;
            $dealpipelineeData[] = $deal_pipeline;
        }

        if ($request->type == "client_repport") {

            $from_date1 = date('Y-m-d H:i:s', strtotime($request->from_date));
            $to_date1 = date('Y-m-d H:i:s', strtotime($request->to_date));
            if (!empty($request->from_date) && !empty($request->to_date)) {
                $client_deal = $this->deals();
                $dealClientData = [];
                $dealClientName = [];
                foreach ($client_deal as $client_deal_data) {

                    $deals_client = ClientDeal::where('client_id', $client_deal_data->id)->whereBetween('created_at', [$from_date1, $to_date1])->count();
                    $dealClientName[] = $client_deal_data->name;
                    $dealClientData[] = $deals_client;
                }
                return response()->json(['data' => $dealClientData, 'name' => $dealClientName]);
            }
        } else {
            $client_deal = $this->deals();
            $dealClientName = [];
            $dealClientData = [];
            foreach ($client_deal as $client_deal_data) {
                $deals_client = ClientDeal::where('client_id', $client_deal_data->id)->count();
                $dealClientName[] = $client_deal_data->name;
                $dealClientData[] = $deals_client;
            }
        }
        $labels = [];
        $data = [];

        if (!empty($request->start_month) && !empty($request->end_month)) {
            $start = strtotime($request->start_month);
            $end = strtotime($request->end_month);
        } else {
            $start = strtotime(date('Y-01'));
            $end = strtotime(date('Y-12'));
        }

        $deals = Deal::orderBy('id');
        $deals->where('created_at', '>=', date('Y-m-01', $start))->where('created_at', '<=', date('Y-m-t', $end));
        $deals->where('created_by', \Auth::user()->creatorId());
        $deals = $deals->get();

        $currentdate = $start;
        while ($currentdate <= $end) {
            $month = date('m', $currentdate);

            $year = date('Y');

            if (!empty($request->start_month)) {
                $dealFilter = Deal::where('created_by', \Auth::user()->creatorId())->whereMonth('created_at', $request->start_month)->whereYear('created_at', $year)->get();
            } else {
                $dealFilter = Deal::where('created_by', \Auth::user()->creatorId())->whereMonth('created_at', $month)->whereYear('created_at', $year)->get();
            }

            $data[] = count($dealFilter);
            $labels[] = date('M Y', $currentdate);
            $currentdate = strtotime('+1 month', $currentdate);

            if (!empty($request->start_month)) {
                $cdate = '01-' . $request->start_month . '-' . $year;
                $mstart = strtotime($cdate);
                $labelss[] = date('M Y', $mstart);

                return response()->json(['data' => $data, 'name' => $labelss]);
            }
        }
        if (empty($request->start_month) && !empty($request->all())) {
            return response()->json(['data' => $data, 'name' => $labels]);
        }
        $filter['startDateRange'] = date('M-Y', $start);
        $filter['endDateRange'] = date('M-Y', $end);

        $monthList = $month = $this->yearMonth();
        return view('report.deal', compact('devicearray', 'dealsourceName', 'dealsourceeData', 'dealUserData', 'dealUserName', 'dealpipelineName', 'dealpipelineeData', 'data', 'labels', 'dealClientName', 'dealClientData', 'monthList'));
    }

    private static $dealData = NULL;

    public function deals()
    {
        if (self::$dealData == null) {
            $deal = User::where('created_by', \Auth::user()->creatorId())->get();
            self::$dealData = $deal;
        }

        return self::$dealData;
    }

    public function warehouseReport()
    {
        if (\Auth::user()->can('manage pos')) {
            $warehouse = warehouse::where('created_by', \Auth::user()->id)->get();
            $totalWarehouse = warehouse::where('created_by', \Auth::user()->id)->count();
            $totalProduct = WarehouseProduct::where('created_by', '=', \Auth::user()->creatorId())->count();
            $warehousename = [];
            $warehouseProductData = [];
            foreach ($warehouse as $warehouse_data) {
                $warehouseGet = WarehouseProduct::where('created_by', \Auth::user()->id)->where('warehouse_id', $warehouse_data->id)->count();
                $warehousename[] = $warehouse_data->name;
                $warehouseProductData[] = $warehouseGet;
            }

            return view('report.warehouse', compact('warehouse', 'totalWarehouse', 'totalProduct', 'warehouseProductData', 'warehousename'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function purchaseDailyReport(Request $request)
    {
        if (\Auth::user()->can('manage pos')) {
            //        dd($request->all());
            $warehouse = warehouse::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $warehouse->prepend('All Warehouse', 0);
            $vendor = Vender::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $vendor->prepend('All Vendor', 0);
            $query = Purchase::where('created_by', '=', \Auth::user()->creatorId());
            if (!empty($request->warehouse)) {
                $query->where('warehouse_id', '=', $request->warehouse);
            }
            if (!empty($request->vendor)) {
                $query->where('vender_id', '=', $request->vendor);
            }

            $arrDuration = [];
            $data = [];
            if (!empty($request->start_date) && !empty($request->end_date)) {
                $first_date = $request->start_date;
                $end_date = $request->end_date;
            } else {
                $first_date = date('Y-m-d', strtotime('today - 30 days'));
                $end_date = date('Y-m-d', strtotime('today - 1 days'));
            }
            $query->whereBetween('purchase_date', [$first_date, $end_date]);
            $purchases = $query->get()->groupBy(
                function ($val) {
                    return Carbon::parse($val->purchase_date)->format('Y-m-d');
                }
            );
            $total = [];
            if (!empty($purchases) && count($purchases) > 0) {
                foreach ($purchases as $day => $onepurchase) {
                    $totals = 0;
                    foreach ($onepurchase as $purchase) {
                        $totals += $purchase->getTotal();
                    }
                    $total[$day] = $totals;
                }
            }
            $previous_days = strtotime("-1 month +1 days");
            for ($i = 0; $i < 30; $i++) {
                $previous_days = strtotime(date('Y-m-d', $previous_days) . " +1 day");
                $arrDuration[] = date('d-M', $previous_days);
                $date = date('Y-m-d', $previous_days);
                $data[] = isset($total[$date]) ? $total[$date] : 0;
            }

            $filter['startDate'] = $first_date;
            $filter['endDate'] = $end_date;
            $warehouses = warehouse::where('id', '=', $request->warehouse)->where('created_by', \Auth::user()->creatorId())->first();
            $filter['warehouse'] = !empty($warehouses) ? $warehouses->name : '';
            $vendors = Vender::where('id', '=', $request->vendor)->where('created_by', \Auth::user()->creatorId())->first();
            $filter['vendor'] = !empty($vendors) ? $vendors->name : '';

            return view('report.daily_purchase', compact('warehouse', 'vendor', 'arrDuration', 'data', 'filter'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function purchaseMonthlyReport(Request $request)
    {
        if (\Auth::user()->can('manage pos')) {
            $monthList = $this->yearMonth();
            $yearList = $this->yearList();
            $warehouse = warehouse::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $warehouse->prepend('All Warehouse', 0);
            $vendor = Vender::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $vendor->prepend('All Vendor', 0);
            $query = Purchase::where('created_by', '=', \Auth::user()->creatorId());
            if (!empty($request->warehouse)) {
                $query->where('warehouse_id', '=', $request->warehouse);
            }
            if (!empty($request->vendor)) {
                $query->where('vender_id', '=', $request->vendor);
            }
            $arrDuration = [];
            $data = [];
            if (!empty($request->year)) {
                $year = $request->year;
            } else {
                $year = date('Y');
            }
            $query->whereYear('purchase_date', $year);
            $purchases = $query->get()->groupBy(
                function ($val) {
                    return Carbon::parse($val->purchase_date)->format('m');
                }
            );
            $total = [];
            if (!empty($purchases) && count($purchases) > 0) {
                foreach ($purchases as $month => $onepurchase) {
                    $totals = 0;
                    foreach ($onepurchase as $purchase) {
                        $totals += $purchase->getTotal();
                    }
                    $total[$month] = $totals;
                }
            }
            for ($i = 0; $i < 12; $i++) {
                $arrDuration[] = date("my", strtotime(date('Y-m-01') . " -$i months"));
                $month = date("m", strtotime(date('Y-m-01') . " -$i months"));
                $data[] = isset($total[$month]) ? $total[$month] : 0;
            }

            $filter['startMonth'] = 'Jan-' . $year;
            $filter['endMonth'] = 'Dec-' . $year;
            $warehouses = warehouse::where('id', '=', $request->warehouse)->where('created_by', \Auth::user()->creatorId())->first();
            $filter['warehouse'] = !empty($warehouses) ? $warehouses->name : '';
            $vendors = Vender::where('id', '=', $request->vendor)->where('created_by', \Auth::user()->creatorId())->first();
            $filter['vendor'] = !empty($vendors) ? $vendors->name : '';

            return view('report.monthly_purchase', compact('monthList', 'yearList', 'warehouse', 'vendor', 'arrDuration', 'data', 'filter'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function posDailyReport(Request $request)
    {
        if (\Auth::user()->can('manage pos')) {
            $warehouse = warehouse::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $warehouse->prepend('All Warehouse', 0);

            $customer = Customer::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customer->prepend('All Customer', 0);
            $query = Pos::where('created_by', '=', \Auth::user()->creatorId());
            if (!empty($request->warehouse)) {
                $query->where('warehouse_id', '=', $request->warehouse);
            }
            if (!empty($request->customer)) {
                $query->where('customer_id', '=', $request->customer);
            }

            $arrDuration = [];
            $data = [];
            if (!empty($request->start_date) && !empty($request->end_date)) {
                $first_date = $request->start_date;
                $end_date = $request->end_date;
            } else {
                $first_date = date('Y-m-d', strtotime('today - 30 days'));
                $end_date = date('Y-m-d', strtotime('today - 1 days'));
            }
            $query->whereBetween('pos_date', [$first_date, $end_date]);
            $poses = $query->get()->groupBy(
                function ($val) {
                    return Carbon::parse($val->pos_date)->format('Y-m-d');
                }
            );
            $total = [];
            if (!empty($poses) && count($poses) > 0) {
                foreach ($poses as $day => $onepos) {
                    $totals = 0;
                    foreach ($onepos as $pos) {

                        $totals += $pos->getTotal();
                    }
                    $total[$day] = $totals;
                }
            }
            $previous_days = strtotime("-1 month +1 days");
            for ($i = 0; $i < 30; $i++) {
                $previous_days = strtotime(date('Y-m-d', $previous_days) . " +1 day");
                $arrDuration[] = date('d-M', $previous_days);
                $date = date('Y-m-d', $previous_days);
                $data[] = isset($total[$date]) ? $total[$date] : 0;
            }

            $filter['startDate'] = $first_date;
            $filter['endDate'] = $end_date;
            $warehouses = warehouse::where('id', '=', $request->warehouse)->where('created_by', \Auth::user()->creatorId())->first();
            $filter['warehouse'] = !empty($warehouses) ? $warehouses->name : '';
            $customers = Customer::where('id', '=', $request->customer)->where('created_by', \Auth::user()->creatorId())->first();
            $filter['customer'] = !empty($customers) ? $customers->name : '';

            return view('report.daily_pos', compact('warehouse', 'customer', 'arrDuration', 'data', 'filter'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function posMonthlyReport(Request $request)
    {
        if (\Auth::user()->can('manage pos')) {
            $monthList = $this->yearMonth();
            $yearList = $this->yearList();

            $warehouse = warehouse::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $warehouse->prepend('All Warehouse', 0);
            $customer = Customer::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customer->prepend('All Customer', 0);
            $query = Pos::where('created_by', '=', \Auth::user()->creatorId());
            if (!empty($request->warehouse)) {
                $query->where('warehouse_id', '=', $request->warehouse);
            }
            if (!empty($request->customer)) {
                $query->where('customer_id', '=', $request->customer);
            }
            $arrDuration = [];
            $data = [];
            if (!empty($request->year)) {
                $year = $request->year;
            } else {
                $year = date('Y');
            }
            $query->whereYear('pos_date', $year);
            $poses = $query->get()->groupBy(
                function ($val) {
                    return Carbon::parse($val->pos_date)->format('m');
                }
            );
            $total = [];
            if (!empty($poses) && count($poses) > 0) {
                foreach ($poses as $month => $onepos) {
                    $totals = 0;
                    foreach ($onepos as $pos) {
                        $totals += $pos->getTotal();
                    }
                    $total[$month] = $totals;
                }
            }
            for ($i = 0; $i < 12; $i++) {
                $arrDuration[] = date("my", strtotime(date('Y-m-01') . " -$i months"));
                $month = date("m", strtotime(date('Y-m-01') . " -$i months"));
                $data[] = isset($total[$month]) ? $total[$month] : 0;
            }

            $filter['startMonth'] = 'Jan-' . $year;
            $filter['endMonth'] = 'Dec-' . $year;
            $warehouses = warehouse::where('id', '=', $request->warehouse)->where('created_by', \Auth::user()->creatorId())->first();
            $filter['warehouse'] = !empty($warehouses) ? $warehouses->name : '';
            $customers = Customer::where('id', '=', $request->customer)->where('created_by', \Auth::user()->creatorId())->first();
            $filter['customer'] = !empty($customers) ? $customers->name : '';

            return view('report.monthly_pos', compact('monthList', 'yearList', 'warehouse', 'customer', 'arrDuration', 'data', 'filter'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function posVsPurchaseReport(Request $request)
    {
        if (\Auth::user()->can('manage pos')) {
            $data['monthList'] = $month = $this->yearMonth();
            $data['yearList'] = $this->yearList();

            if (isset($request->year)) {
                $year = $request->year;
            } else {
                $year = date('Y');
            }
            $data['currentYear'] = $year;

            // ------------------------------TOTAL POS-----------------------------------------------------------

            $posData = Pos::selectRaw('MONTH(pos_date) as month,YEAR(pos_date) as year,pos_id,id')
                ->where('created_by', \Auth::user()->creatorId());
            $posData->whereRAW('YEAR(pos_date) =?', [$year]);
            $posData = $posData->get();
            $posTotalArray = [];
            foreach ($posData as $pos) {
                $posTotalArray[$pos->month] = $pos->getTotal();
            }

            // ------------------------------ TOTAL PAYMENT-----------------------------------------------------------
            $purchaseData = Purchase::selectRaw('MONTH(send_date) as month,YEAR(send_date) as year,purchase_id,id')
                ->where('created_by', \Auth::user()->creatorId())
                ->where('status', '!=', 0);
            $purchaseData->whereRAW('YEAR(send_date) =?', [$year]);
            $purchaseData = $purchaseData->get();
            $purchaseTotalArray = [];
            foreach ($purchaseData as $purchase) {
                $purchaseTotalArray[$purchase->month] = $purchase->getTotal();
            }

            //            -----------------------------

            for ($i = 1; $i <= 12; $i++) {
                $PosTotal[] = array_key_exists($i, $posTotalArray) ? $posTotalArray[$i] : 0;
                $PurchaseTotal[] = array_key_exists($i, $purchaseTotalArray) ? $purchaseTotalArray[$i] : 0;
            }
            $totalPos = array_map(
                function () {
                    return array_sum(func_get_args());
                },
                $PosTotal
            );

            $totalPurchase = array_map(
                function () {
                    return array_sum(func_get_args());
                },
                $PurchaseTotal
            );

            $profits = [];
            $keys = array_keys($totalPos + $totalPurchase);
            foreach ($keys as $v) {
                $profits[$v] = number_format((empty($totalPos[$v]) ? 0 : $totalPos[$v]) - (empty($totalPurchase[$v]) ? 0 : $totalPurchase[$v]), 2);
            }

            $data['posTotal'] = $PosTotal;
            $data['purchaseTotal'] = $PurchaseTotal;
            $data['profits'] = $profits;
            $filter['startDateRange'] = 'Jan-' . $year;
            $filter['endDateRange'] = 'Dec-' . $year;

            return view('report.pos_vs_purchase', compact('filter'), $data);
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    // public function profitLoss(Request $request, $view = '')
    // {

    //     if (\Auth::user()->can('income vs expense report')) {

    //         if (!empty($request->start_date) && !empty($request->end_date)) {
    //             $start = $request->start_date;
    //             $end = $request->end_date;
    //         } else {
    //             $start = date('Y-01-01');
    //             $end = date('Y-m-d', strtotime('+1 day'));
    //         }
    //         $types = ChartOfAccountType::where('created_by', \Auth::user()->creatorId())->whereIn('name', ['Income', 'Expenses', 'Costs of Goods Sold'])->get();
    //         $chartAccounts = [];
    //         $subTypeArray = [];
    //         foreach ($types as $type) {
    //             $accounts = ChartOfAccount::where('created_by', \Auth::user()->creatorId())->where('type', $type->id)->get();
    //             $totalBalance = 0;
    //             $creditTotal = 0;
    //             $debitTotal = 0;
    //             $totalAmount = 0;
    //             $accountArray = [];

    //             foreach ($accounts as $account) {

    //                 $Balance = Utility::getAccountBalance($account->id, $start, $end);
    //                 $totalBalance += $Balance;

    //                 if ($Balance != 0) {
    //                     $data['account_id'] = $account->id;
    //                     $data['account_code'] = $account->code;
    //                     $data['account_name'] = $account->name;
    //                     $data['totalCredit'] = 0;
    //                     $data['totalDebit'] = 0;
    //                     $data['netAmount'] = $Balance;
    //                     $accountArray[] = $data;

    //                     $creditTotal += $data['totalCredit'];
    //                     $debitTotal += $data['totalDebit'];
    //                     $totalAmount += $data['netAmount'];
    //                 }
    //             }

    //             $totalAccountArray = [];

    //             if ($accountArray != []) {
    //                 $dataTotal['account_id'] = '';
    //                 $dataTotal['account_code'] = '';
    //                 $dataTotal['account_name'] = 'Total ' . $type->name;
    //                 $dataTotal['totalCredit'] = $creditTotal;
    //                 $dataTotal['totalDebit'] = $debitTotal;
    //                 $dataTotal['netAmount'] = $totalAmount;
    //                 $accountArray[] = $dataTotal;

    //             }

    //             if ($accountArray != []) {
    //                 $subTypeData['Type'] = ($accountArray != []) ? $type->name : '';
    //                 $subTypeData['account'] = $accountArray;
    //                 $subTypeArray[] = ($subTypeData['account'] != []) ? $subTypeData : [];
    //             }
    //             $chartAccounts = $subTypeArray;
    //         }

    //         $filter['startDateRange'] = $start;
    //         $filter['endDateRange'] = $end;

    //         if ($request->view == 'horizontal' || $view == 'horizontal') {
    //             return view('report.profit_loss_horizontal', compact('filter', 'chartAccounts'));
    //         } elseif ($view == '' || $view == 'vertical') {
    //             return view('report.profit_loss', compact('filter', 'chartAccounts'));
    //         } else {
    //             return redirect()->back();
    //         }

    //     } else {
    //         return redirect()->back()->with('error', __('Permission denied.'));
    //     }
    // }

    public function profitLoss(Request $request, $view = '')
    {
        if (\Auth::user()->can('income vs expense report')) {
            if (!empty($request->start_date) && !empty($request->end_date)) {
                $start = $request->start_date;
                $end = $request->end_date;
            } else {
                $start = date('Y-01-01');
                $end = date('Y-m-d', strtotime('+1 day'));
            }
            $types = ChartOfAccountType::where('created_by', \Auth::user()->creatorId())->whereIn('name', ['Income', 'Expenses', 'Costs of Goods Sold'])->get();
            $subTypeArray = [];
            $totalAccounts = [];
            foreach ($types as $type) {
                $accounts = GeneralLedger::select('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name', \DB::raw('sum(debit) as totalDebit'), \DB::raw('sum(credit) as totalCredit'));
                $accounts->leftjoin('chart_of_accounts', 'general_ledger.account', 'chart_of_accounts.id');
                $accounts->leftjoin('chart_of_account_types', 'chart_of_accounts.type', 'chart_of_account_types.id');
                $accounts->where('chart_of_accounts.type', $type->id);
                $accounts->where('general_ledger.created_by', \Auth::user()->creatorId());
                $accounts->where('general_ledger.send_date', '>=', $start);
                $accounts->where('general_ledger.send_date', '<=', $end);
                $accounts->groupBy('account');
                $accounts = $accounts->get()->toArray();
                $totalBalance = 0;
                $creditTotal = 0;
                $debitTotal = 0;
                $totalAmount = 0;
                $accountArray = [];
                foreach ($accounts as $account) {
                    $Balance = $account['totalCredit'] - $account['totalDebit'];
                    $totalBalance += $Balance;
                    if ($Balance != 0) {
                        $data['account_id'] = $account['id'];
                        $data['account_code'] = $account['code'];
                        $data['account_name'] = $account['name'];
                        $data['totalCredit'] = 0;
                        $data['totalDebit'] = 0;
                        $data['netAmount'] = $Balance;
                        $accountArray[] = $data;
                        $creditTotal += $data['totalCredit'];
                        $debitTotal += $data['totalDebit'];
                        $totalAmount += $data['netAmount'];
                    }
                }
                if ($accountArray != []) {
                    $dataTotal['account_id'] = '';
                    $dataTotal['account_code'] = '';
                    $dataTotal['account_name'] = 'Total ' . $type->name;
                    $dataTotal['totalCredit'] = $creditTotal;
                    $dataTotal['totalDebit'] = $debitTotal;
                    $dataTotal['netAmount'] = $totalAmount;
                    $accountArray[] = $dataTotal;
                }
                if ($accountArray != []) {
                    $subTypeData['Type'] = ($accountArray != []) ? $type->name : '';
                    $subTypeData['account'] = $accountArray;
                    $subTypeArray[] = ($subTypeData['account'] != []) ? $subTypeData : [];
                }
                $totalAccounts = $subTypeArray;
            }
            $filter['startDateRange'] = $start;
            $filter['endDateRange'] = $end;
            if ($request->view == 'horizontal' || $view == 'horizontal') {
                return view('report.profit_loss_horizontal', compact('filter', 'totalAccounts'));
            } elseif ($view == '' || $view == 'vertical') {
                return view('report.profit_loss', compact('filter', 'totalAccounts'));
            } else {
                return redirect()->back();
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function monthlyCashflow(Request $request)
    {
        if (\Auth::user()->can('loss & profit report')) {

            $data['monthList'] = $month = $this->yearMonth();
            $data['yearList'] = $this->yearList();

            if (isset($request->year)) {
                $year = $request->year;
            } else {
                $year = date('Y');
            }
            $data['currentYear'] = $year;

            // -------------------------------REVENUE INCOME-------------------------------------------------

            // ------------------------------REVENUE INCOME-----------------------------------
            $incomes = Revenue::selectRaw('sum(revenues.amount) as amount,MONTH(date) as month,YEAR(date) as year,category_id')
                ->leftjoin('product_service_categories', 'revenues.category_id', '=', 'product_service_categories.id')->where('product_service_categories.type', '=', 1);
            $incomes->where('revenues.created_by', '=', \Auth::user()->creatorId());
            $incomes->whereRAW('YEAR(date) =?', [$year]);

            if (!empty($request->category)) {
                $incomes->where('category_id', '=', $request->category);
                $cat = ProductServiceCategory::find($request->category);
                $filter['category'] = !empty($cat) ? $cat->name : '';
            }

            if (!empty($request->customer)) {
                $incomes->where('customer_id', '=', $request->customer);
                $cust = Customer::find($request->customer);
                $filter['customer'] = !empty($cust) ? $cust->name : '';
            }
            $incomes->groupBy('month', 'year', 'category_id');
            $incomes = $incomes->get();

            $tmpArray = [];
            foreach ($incomes as $income) {
                $tmpArray[$income->category_id][$income->month] = $income->amount;
            }
            $array = [];
            foreach ($tmpArray as $cat_id => $record) {
                $tmp = [];
                $tmp['category'] = !empty(ProductServiceCategory::where('id', '=', $cat_id)->first()) ? ProductServiceCategory::where('id', '=', $cat_id)->first()->name : '';
                $tmp['data'] = [];
                for ($i = 1; $i <= 12; $i++) {
                    $tmp['data'][$i] = array_key_exists($i, $record) ? $record[$i] : 0;
                }
                $array[] = $tmp;
            }

            $incomesData = Revenue::selectRaw('sum(revenues.amount) as amount,MONTH(date) as month,YEAR(date) as year');
            $incomesData->where('revenues.created_by', '=', \Auth::user()->creatorId());
            $incomesData->whereRAW('YEAR(date) =?', [$year]);

            if (!empty($request->category)) {
                $incomesData->where('category_id', '=', $request->category);
            }
            if (!empty($request->customer)) {
                $incomesData->where('customer_id', '=', $request->customer);
            }
            $incomesData->groupBy('month', 'year');
            $incomesData = $incomesData->get();
            $incomeArr = [];
            foreach ($incomesData as $k => $incomeData) {
                $incomeArr[$incomeData->month] = $incomeData->amount;
            }
            for ($i = 1; $i <= 12; $i++) {
                $incomeTotal[] = array_key_exists($i, $incomeArr) ? $incomeArr[$i] : 0;
            }

            //---------------------------INVOICE INCOME-----------------------------------------------

            $invoices = Invoice::selectRaw('MONTH(send_date) as month,YEAR(send_date) as year,category_id,invoice_id,id')
                ->where('created_by', \Auth::user()->creatorId())
                ->where('status', '!=', 0);

            $invoices->whereRAW('YEAR(send_date) =?', [$year]);

            if (!empty($request->customer)) {
                $invoices->where('customer_id', '=', $request->customer);
            }

            if (!empty($request->category)) {
                $invoices->where('category_id', '=', $request->category);
            }

            $invoices = $invoices->get();
            $invoiceTmpArray = [];
            foreach ($invoices as $invoice) {
                $item = Invoice::where('id', $invoice->id)->first();
                $invoiceTmpArray[$invoice->category_id][$invoice->month][] = $item->getTotal();
            }

            $invoiceArray = [];
            foreach ($invoiceTmpArray as $cat_id => $record) {

                $invoice = [];
                $productCtegory = ProductServiceCategory::where('id', '=', $cat_id)->first();
                $invoice['category'] = !empty($productCtegory) ? $productCtegory->name : '';
                $invoice['data'] = [];
                for ($i = 1; $i <= 12; $i++) {

                    $invoice['data'][$i] = array_key_exists($i, $record) ? array_sum($record[$i]) : 0;
                }
                $invoiceArray[] = $invoice;
            }

            $invoiceTotalArray = [];
            foreach ($invoices as $invoice) {
                $item = Invoice::where('id', $invoice->id)->first();
                $invoiceTotalArray[$invoice->month][] = $item->getTotal();
            }
            for ($i = 1; $i <= 12; $i++) {
                $invoiceTotal[] = array_key_exists($i, $invoiceTotalArray) ? array_sum($invoiceTotalArray[$i]) : 0;
            }

            $chartIncomeArr = array_map(
                function () {
                    return array_sum(func_get_args());
                },
                $incomeTotal,
                $invoiceTotal
            );

            $data['chartIncomeArr'] = $chartIncomeArr;
            $data['incomeArr'] = $array;
            $data['invoiceArray'] = $invoiceArray;

            //   -----------------------------------------PAYMENT EXPENSE ------------------------------------------------------------
            $expenses = Payment::selectRaw('sum(payments.amount) as amount,MONTH(date) as month,YEAR(date) as year,category_id')->leftjoin('product_service_categories', 'payments.category_id', '=', 'product_service_categories.id')->where('product_service_categories.type', '=', 2);
            $expenses->where('payments.created_by', '=', \Auth::user()->creatorId());
            $expenses->whereRAW('YEAR(date) =?', [$year]);

            if (!empty($request->category)) {
                $expenses->where('category_id', '=', $request->category);
                $cat = ProductServiceCategory::find($request->category);
                $filter['category'] = !empty($cat) ? $cat->name : '';
            }
            if (!empty($request->vender)) {
                $expenses->where('vender_id', '=', $request->vender);

                $vend = Vender::find($request->vender);
                $filter['vender'] = !empty($vend) ? $vend->name : '';
            }

            $expenses->groupBy('month', 'year', 'category_id');
            $expenses = $expenses->get();
            $tmpArray = [];
            foreach ($expenses as $expense) {
                $tmpArray[$expense->category_id][$expense->month] = $expense->amount;
            }
            $array = [];
            foreach ($tmpArray as $cat_id => $record) {
                $tmp = [];
                $tmp['category'] = !empty(ProductServiceCategory::where('id', '=', $cat_id)->first()) ? ProductServiceCategory::where('id', '=', $cat_id)->first()->name : '';
                $tmp['data'] = [];
                for ($i = 1; $i <= 12; $i++) {
                    $tmp['data'][$i] = array_key_exists($i, $record) ? $record[$i] : 0;
                }
                $array[] = $tmp;
            }
            $expensesData = Payment::selectRaw('sum(payments.amount) as amount,MONTH(date) as month,YEAR(date) as year');
            $expensesData->where('payments.created_by', '=', \Auth::user()->creatorId());
            $expensesData->whereRAW('YEAR(date) =?', [$year]);

            if (!empty($request->category)) {
                $expensesData->where('category_id', '=', $request->category);
            }
            if (!empty($request->vender)) {
                $expensesData->where('vender_id', '=', $request->vender);
            }
            $expensesData->groupBy('month', 'year');
            $expensesData = $expensesData->get();

            $expenseArr = [];
            foreach ($expensesData as $k => $expenseData) {
                $expenseArr[$expenseData->month] = $expenseData->amount;
            }
            for ($i = 1; $i <= 12; $i++) {
                $expenseTotal[] = array_key_exists($i, $expenseArr) ? $expenseArr[$i] : 0;
            }

            //     ------------------------------------BILL EXPENSE----------------------------------------------------

            $bills = Bill::selectRaw('MONTH(send_date) as month,YEAR(send_date) as year,category_id,bill_id,id')->where('created_by', \Auth::user()->creatorId())->where('status', '!=', 0);
            $bills->whereRAW('YEAR(send_date) =?', [$year]);

            if (!empty($request->vender)) {
                $bills->where('vender_id', '=', $request->vender);
            }

            if (!empty($request->category)) {
                $bills->where('category_id', '=', $request->category);
            }
            $bills = $bills->get();
            $billTmpArray = [];
            foreach ($bills as $bill) {
                $billTmpArray[$bill->category_id][$bill->month][] = $bill->getTotal();
            }

            $billArray = [];
            foreach ($billTmpArray as $cat_id => $record) {

                $bill = [];
                $productCategory = ProductServiceCategory::where('id', '=', $cat_id)->first();
                $bill['category'] = !empty($productCategory) ? $productCategory->name : '';
                $bill['data'] = [];
                for ($i = 1; $i <= 12; $i++) {

                    $bill['data'][$i] = array_key_exists($i, $record) ? array_sum($record[$i]) : 0;
                }
                $billArray[] = $bill;
            }

            $billTotalArray = [];
            foreach ($bills as $bill) {
                $billTotalArray[$bill->month][] = $bill->getTotal();
            }
            for ($i = 1; $i <= 12; $i++) {
                $billTotal[] = array_key_exists($i, $billTotalArray) ? array_sum($billTotalArray[$i]) : 0;
            }

            $chartExpenseArr = array_map(
                function () {
                    return array_sum(func_get_args());
                },
                $expenseTotal,
                $billTotal
            );

            $netProfit = [];
            $keys = array_keys($chartIncomeArr + $chartExpenseArr);
            foreach ($keys as $v) {
                $netProfit[$v] = (empty($chartIncomeArr[$v]) ? 0 : $chartIncomeArr[$v]) - (empty($chartExpenseArr[$v]) ? 0 : $chartExpenseArr[$v]);
            }

            $data['chartExpenseArr'] = $chartExpenseArr;
            $data['expenseArr'] = $array;
            $data['billArray'] = $billArray;

            $data['netProfitArray'] = $netProfit;
            $filter['startDateRange'] = 'Jan-' . $year;
            $filter['endDateRange'] = 'Dec-' . $year;

            return view('report.monthly_cashflow', compact('filter'), $data);
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function quarterlyCashflow(Request $request)
    {

        if (\Auth::user()->can('loss & profit report')) {
            $data['month'] = [
                'Jan-Mar',
                'Apr-Jun',
                'Jul-Sep',
                'Oct-Dec',
                'Total',
            ];
            $data['monthList'] = $month = $this->yearMonth();
            $data['yearList'] = $this->yearList();

            if (isset($request->year)) {
                $year = $request->year;
            } else {
                $year = date('Y');
            }
            $data['currentYear'] = $year;

            // -------------------------------REVENUE INCOME-------------------------------------------------

            $incomes = Revenue::selectRaw('sum(revenues.amount) as amount,MONTH(date) as month,YEAR(date) as year,category_id');
            $incomes->where('created_by', '=', \Auth::user()->creatorId());
            $incomes->whereRAW('YEAR(date) =?', [$year]);
            $incomes->groupBy('month', 'year', 'category_id');
            $incomes = $incomes->get();
            $tmpIncomeArray = [];
            foreach ($incomes as $income) {
                $tmpIncomeArray[$income->category_id][$income->month] = $income->amount;
            }

            $incomeCatAmount_1 = $incomeCatAmount_2 = $incomeCatAmount_3 = $incomeCatAmount_4 = 0;
            $revenueIncomeArray = array();
            foreach ($tmpIncomeArray as $cat_id => $record) {

                $tmp = [];
                $tmp['category'] = !empty(ProductServiceCategory::where('id', '=', $cat_id)->first()) ? ProductServiceCategory::where('id', '=', $cat_id)->first()->name : '';
                $sumData = [];
                for ($i = 1; $i <= 12; $i++) {
                    $sumData[] = array_key_exists($i, $record) ? $record[$i] : 0;
                }

                $month_1 = array_slice($sumData, 0, 3);
                $month_2 = array_slice($sumData, 3, 3);
                $month_3 = array_slice($sumData, 6, 3);
                $month_4 = array_slice($sumData, 9, 3);

                $incomeData[__('Jan-Mar')] = $sum_1 = array_sum($month_1);
                $incomeData[__('Apr-Jun')] = $sum_2 = array_sum($month_2);
                $incomeData[__('Jul-Sep')] = $sum_3 = array_sum($month_3);
                $incomeData[__('Oct-Dec')] = $sum_4 = array_sum($month_4);
                $incomeData[__('Total')] = array_sum(
                    array(
                        $sum_1,
                        $sum_2,
                        $sum_3,
                        $sum_4,
                    )
                );

                $incomeCatAmount_1 += $sum_1;
                $incomeCatAmount_2 += $sum_2;
                $incomeCatAmount_3 += $sum_3;
                $incomeCatAmount_4 += $sum_4;

                $data['month'] = array_keys($incomeData);
                $tmp['amount'] = array_values($incomeData);

                $revenueIncomeArray[] = $tmp;
            }

            $data['incomeCatAmount'] = $incomeCatAmount = [
                $incomeCatAmount_1,
                $incomeCatAmount_2,
                $incomeCatAmount_3,
                $incomeCatAmount_4,
                array_sum(
                    array(
                        $incomeCatAmount_1,
                        $incomeCatAmount_2,
                        $incomeCatAmount_3,
                        $incomeCatAmount_4,
                    )
                ),
            ];

            $data['revenueIncomeArray'] = $revenueIncomeArray;

            //-----------------------INVOICE INCOME---------------------------------------------

            $invoices = Invoice::selectRaw('MONTH(send_date) as month,YEAR(send_date) as year,category_id,invoice_id,id')->where('created_by', \Auth::user()->creatorId())->where('status', '!=', 0);
            $invoices->whereRAW('YEAR(send_date) =?', [$year]);
            if (!empty($request->customer)) {
                $invoices->where('customer_id', '=', $request->customer);
            }
            $invoices = $invoices->get();

            $invoiceTmpArray = [];
            foreach ($invoices as $invoice) {
                $invoiceTmpArray[$invoice->category_id][$invoice->month][] = $invoice->getDue();
            }

            $invoiceCatAmount_1 = $invoiceCatAmount_2 = $invoiceCatAmount_3 = $invoiceCatAmount_4 = 0;

            $invoiceIncomeArray = array();
            foreach ($invoiceTmpArray as $cat_id => $record) {

                $invoiceTmp = [];
                $invoiceTmp['category'] = !empty(ProductServiceCategory::where('id', '=', $cat_id)->first()) ? ProductServiceCategory::where('id', '=', $cat_id)->first()->name : '';
                $invoiceSumData = [];
                for ($i = 1; $i <= 12; $i++) {
                    $invoiceSumData[] = array_key_exists($i, $record) ? array_sum($record[$i]) : 0;
                }

                $month_1 = array_slice($invoiceSumData, 0, 3);
                $month_2 = array_slice($invoiceSumData, 3, 3);
                $month_3 = array_slice($invoiceSumData, 6, 3);
                $month_4 = array_slice($invoiceSumData, 9, 3);
                $invoiceIncomeData[__('Jan-Mar')] = $sum_1 = array_sum($month_1);
                $invoiceIncomeData[__('Apr-Jun')] = $sum_2 = array_sum($month_2);
                $invoiceIncomeData[__('Jul-Sep')] = $sum_3 = array_sum($month_3);
                $invoiceIncomeData[__('Oct-Dec')] = $sum_4 = array_sum($month_4);
                $invoiceIncomeData[__('Total')] = array_sum(
                    array(
                        $sum_1,
                        $sum_2,
                        $sum_3,
                        $sum_4,
                    )
                );
                $invoiceCatAmount_1 += $sum_1;
                $invoiceCatAmount_2 += $sum_2;
                $invoiceCatAmount_3 += $sum_3;
                $invoiceCatAmount_4 += $sum_4;

                $invoiceTmp['amount'] = array_values($invoiceIncomeData);

                $invoiceIncomeArray[] = $invoiceTmp;
            }

            $data['invoiceIncomeCatAmount'] = $invoiceIncomeCatAmount = [
                $invoiceCatAmount_1,
                $invoiceCatAmount_2,
                $invoiceCatAmount_3,
                $invoiceCatAmount_4,
                array_sum(
                    array(
                        $invoiceCatAmount_1,
                        $invoiceCatAmount_2,
                        $invoiceCatAmount_3,
                        $invoiceCatAmount_4,
                    )
                ),
            ];

            $data['invoiceIncomeArray'] = $invoiceIncomeArray;

            $data['totalIncome'] = $totalIncome = array_map(
                function () {
                    return array_sum(func_get_args());
                },
                $invoiceIncomeCatAmount,
                $incomeCatAmount
            );

            //---------------------------------PAYMENT EXPENSE-----------------------------------

            $expenses = Payment::selectRaw('sum(payments.amount) as amount,MONTH(date) as month,YEAR(date) as year,category_id');
            $expenses->where('created_by', '=', \Auth::user()->creatorId());
            $expenses->whereRAW('YEAR(date) =?', [$year]);
            $expenses->groupBy('month', 'year', 'category_id');
            $expenses = $expenses->get();

            $tmpExpenseArray = [];
            foreach ($expenses as $expense) {
                $tmpExpenseArray[$expense->category_id][$expense->month] = $expense->amount;
            }

            $expenseArray = [];
            $expenseCatAmount_1 = $expenseCatAmount_2 = $expenseCatAmount_3 = $expenseCatAmount_4 = 0;
            foreach ($tmpExpenseArray as $cat_id => $record) {
                $tmp = [];
                $tmp['category'] = !empty(ProductServiceCategory::where('id', '=', $cat_id)->first()) ? ProductServiceCategory::where('id', '=', $cat_id)->first()->name : '';
                $expenseSumData = [];
                for ($i = 1; $i <= 12; $i++) {
                    $expenseSumData[] = array_key_exists($i, $record) ? $record[$i] : 0;
                }

                $month_1 = array_slice($expenseSumData, 0, 3);
                $month_2 = array_slice($expenseSumData, 3, 3);
                $month_3 = array_slice($expenseSumData, 6, 3);
                $month_4 = array_slice($expenseSumData, 9, 3);

                $expenseData[__('Jan-Mar')] = $sum_1 = array_sum($month_1);
                $expenseData[__('Apr-Jun')] = $sum_2 = array_sum($month_2);
                $expenseData[__('Jul-Sep')] = $sum_3 = array_sum($month_3);
                $expenseData[__('Oct-Dec')] = $sum_4 = array_sum($month_4);
                $expenseData[__('Total')] = array_sum(
                    array(
                        $sum_1,
                        $sum_2,
                        $sum_3,
                        $sum_4,
                    )
                );

                $expenseCatAmount_1 += $sum_1;
                $expenseCatAmount_2 += $sum_2;
                $expenseCatAmount_3 += $sum_3;
                $expenseCatAmount_4 += $sum_4;

                $data['month'] = array_keys($expenseData);
                $tmp['amount'] = array_values($expenseData);

                $expenseArray[] = $tmp;
            }

            $data['expenseCatAmount'] = $expenseCatAmount = [
                $expenseCatAmount_1,
                $expenseCatAmount_2,
                $expenseCatAmount_3,
                $expenseCatAmount_4,
                array_sum(
                    array(
                        $expenseCatAmount_1,
                        $expenseCatAmount_2,
                        $expenseCatAmount_3,
                        $expenseCatAmount_4,
                    )
                ),
            ];
            $data['expenseArray'] = $expenseArray;

            //    ----------------------------EXPENSE BILL-----------------------------------------------------------------------

            $bills = Bill::selectRaw('MONTH(send_date) as month,YEAR(send_date) as year,category_id,bill_id,id')->where('created_by', \Auth::user()->creatorId())->where('status', '!=', 0);
            $bills->whereRAW('YEAR(send_date) =?', [$year]);
            if (!empty($request->customer)) {
                $bills->where('vender_id', '=', $request->vender);
            }
            $bills = $bills->get();
            $billTmpArray = [];
            foreach ($bills as $bill) {
                $billTmpArray[$bill->category_id][$bill->month][] = $bill->getTotal();
            }

            $billExpenseArray = [];
            $billExpenseCatAmount_1 = $billExpenseCatAmount_2 = $billExpenseCatAmount_3 = $billExpenseCatAmount_4 = 0;
            foreach ($billTmpArray as $cat_id => $record) {
                $billTmp = [];
                $billTmp['category'] = !empty(ProductServiceCategory::where('id', '=', $cat_id)->first()) ? ProductServiceCategory::where('id', '=', $cat_id)->first()->name : '';
                $billExpensSumData = [];
                for ($i = 1; $i <= 12; $i++) {
                    $billExpensSumData[] = array_key_exists($i, $record) ? array_sum($record[$i]) : 0;
                }

                $month_1 = array_slice($billExpensSumData, 0, 3);
                $month_2 = array_slice($billExpensSumData, 3, 3);
                $month_3 = array_slice($billExpensSumData, 6, 3);
                $month_4 = array_slice($billExpensSumData, 9, 3);

                $billExpenseData[__('Jan-Mar')] = $sum_1 = array_sum($month_1);
                $billExpenseData[__('Apr-Jun')] = $sum_2 = array_sum($month_2);
                $billExpenseData[__('Jul-Sep')] = $sum_3 = array_sum($month_3);
                $billExpenseData[__('Oct-Dec')] = $sum_4 = array_sum($month_4);
                $billExpenseData[__('Total')] = array_sum(
                    array(
                        $sum_1,
                        $sum_2,
                        $sum_3,
                        $sum_4,
                    )
                );

                $billExpenseCatAmount_1 += $sum_1;
                $billExpenseCatAmount_2 += $sum_2;
                $billExpenseCatAmount_3 += $sum_3;
                $billExpenseCatAmount_4 += $sum_4;

                $data['month'] = array_keys($billExpenseData);
                $billTmp['amount'] = array_values($billExpenseData);

                $billExpenseArray[] = $billTmp;
            }

            $data['billExpenseCatAmount'] = $billExpenseCatAmount = [
                $billExpenseCatAmount_1,
                $billExpenseCatAmount_2,
                $billExpenseCatAmount_3,
                $billExpenseCatAmount_4,
                array_sum(
                    array(
                        $billExpenseCatAmount_1,
                        $billExpenseCatAmount_2,
                        $billExpenseCatAmount_3,
                        $billExpenseCatAmount_4,
                    )
                ),
            ];

            $data['billExpenseArray'] = $billExpenseArray;

            $data['totalExpense'] = $totalExpense = array_map(
                function () {
                    return array_sum(func_get_args());
                },
                $billExpenseCatAmount,
                $expenseCatAmount
            );

            foreach ($totalIncome as $k => $income) {
                $netProfit[] = $income - $totalExpense[$k];
            }
            $data['netProfitArray'] = $netProfit;

            $filter['startDateRange'] = 'Jan-' . $year;
            $filter['endDateRange'] = 'Dec-' . $year;

            return view('report.quarterly_cashflow', compact('filter'), $data);
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function trialBalanceExport(Request $request)
{
    if (!empty($request->start_date) && !empty($request->end_date)) {
        $start = $request->start_date;
        $end = $request->end_date;
    } else {
        $start = date('Y-m-01');
        $end = date('Y-m-t', strtotime('+1 day'));
    }

    $types = ChartOfAccountType::where('created_by', \Auth::user()->creatorId())->get();
    $totalAccount = [];  // Ensure this is initialized properly
    $totalAccounts = [];

    foreach ($types as $type) {
        $total = GeneralLedger::select(
            'chart_of_accounts.id',
            'chart_of_accounts.code',
            'chart_of_accounts.name',
            \DB::raw('SUM(debit) as totalDebit'),
            \DB::raw('SUM(credit) as totalCredit')
        )
            ->leftJoin('chart_of_accounts', 'general_ledger.account', '=', 'chart_of_accounts.id')
            ->leftJoin('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
            ->where('chart_of_accounts.type', $type->id)
            ->where('general_ledger.created_by', \Auth::user()->creatorId())
            ->whereBetween('general_ledger.send_date', [$start, $end])
            ->groupBy('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name')
            ->get()
            ->toArray();

        $name = $type->name;

        if (!isset($totalAccount[$name])) {
            $totalAccount[$name] = [];
        }

        foreach ($total as $record) {
            $accountName = $record['name'];

            if (!isset($totalAccount[$name][$accountName])) {
                $totalAccount[$name][$accountName] = [
                    'id' => $record['id'],
                    'code' => $record['code'],
                    'name' => $accountName,
                    'totalDebit' => 0,
                    'totalCredit' => 0,
                ];
            }

            // Sum values correctly
            $totalAccount[$name][$accountName]['totalDebit'] += $record['totalDebit'] ?? 0;
            $totalAccount[$name][$accountName]['totalCredit'] += $record['totalCredit'] ?? 0;
        }
    }

    // Formatting the final output
    foreach ($totalAccount as $category => $entries) {
        foreach ($entries as $entry) {
            $name = $entry['name'];

            if (!isset($totalAccounts[$category][$name])) {
                $totalAccounts[$category][$name] = [
                    'id' => $entry['id'],
                    'code' => $entry['code'],
                    'name' => $name,
                    'totalDebit' => 0,
                    'totalCredit' => 0,
                ];
            }

            if ($entry['totalDebit'] < 0) {
                $totalAccounts[$category][$name]['totalCredit'] += -$entry['totalDebit'];
            } else {
                $totalAccounts[$category][$name]['totalDebit'] += $entry['totalDebit'];
                $totalAccounts[$category][$name]['totalCredit'] += $entry['totalCredit'];
            }
        }
    }


    $companyName = User::where('id', \Auth::user()->creatorId())->value('name');

    $name = 'trial_balance_' . date('Y-m-d_H-i-s');
    $data = Excel::download(new TrialBalancExport($totalAccounts, $start, $end, $companyName), $name . '.xlsx');
    ob_end_clean();

    return $data;
}
public function trialBalanceTotoalExport(Request $request)
{
    $start = !empty($request->start_date) ? $request->start_date : date('Y-m-01');
    $end = !empty($request->end_date) ? $request->end_date : date('Y-m-d', strtotime('+1 day'));

    $types = ChartOfAccountType::where('created_by', \Auth::user()->creatorId())->get();
    $totalAccounts = [];

    foreach ($types as $type) {
        // Fetch all accounts (ensuring accounts with no transactions are also included)
        $accounts = ChartOfAccount::where('type', $type->id)
            ->where('created_by', \Auth::user()->creatorId())
            ->get()
            ->keyBy('id');

        // Fetch transactions for the selected period
        $transactions = GeneralLedger::select(
            'chart_of_accounts.id',
            'chart_of_accounts.code',
            'chart_of_accounts.name',
            \DB::raw('COALESCE(SUM(debit), 0) as totalDebit'),
            \DB::raw('COALESCE(SUM(credit), 0) as totalCredit')
        )
            ->leftJoin('chart_of_accounts', 'general_ledger.account', '=', 'chart_of_accounts.id')
            ->where('chart_of_accounts.type', $type->id)
            ->where('general_ledger.created_by', \Auth::user()->creatorId())
            ->whereBetween('general_ledger.send_date', [$start, $end])
            ->groupBy('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name')
            ->get()
            ->keyBy('id');

        // Fetch previous period transactions (before start date)
        $previousTotals = GeneralLedger::select(
            'chart_of_accounts.id',
            \DB::raw('COALESCE(SUM(debit), 0) as prevDebit'),
            \DB::raw('COALESCE(SUM(credit), 0) as prevCredit')
        )
            ->leftJoin('chart_of_accounts', 'general_ledger.account', '=', 'chart_of_accounts.id')
            ->where('chart_of_accounts.type', $type->id)
            ->where('general_ledger.created_by', \Auth::user()->creatorId())
            ->where('general_ledger.send_date', '<', $start)
            ->groupBy('chart_of_accounts.id')
            ->get()
            ->keyBy('id');

        $name = $type->name;

        if (!isset($totalAccounts[$name])) {
            $totalAccounts[$name] = [];
        }

        foreach ($accounts as $accountId => $account) {
            $accountName = $account->name;
            $code = $account->code;

            $totalDebit = $transactions[$accountId]['totalDebit'] ?? 0;
            $totalCredit = $transactions[$accountId]['totalCredit'] ?? 0;
            $prevDebit = $previousTotals[$accountId]['prevDebit'] ?? 0;
            $prevCredit = $previousTotals[$accountId]['prevCredit'] ?? 0;

            $totalAccounts[$name][$accountName] = [
                'id' => $accountId,
                'code' => $code,
                'name' => $accountName,
                'prevDebit' => $prevDebit,
                'prevCredit' => $prevCredit,
                'totalDebit' => $totalDebit,
                'totalCredit' => $totalCredit,
            ];
        }
    }

    $companyName = User::where('id', \Auth::user()->creatorId())->value('name');
    $filename = 'trial_balance_total_' . date('Y-m-d_H-i-s') . '.xlsx';

    return Excel::download(new TrialBalancTotalExport($totalAccounts, $start, $end, $companyName), $filename);
}



    public function balanceSheetExport(Request $request)
    {
        // If end_date (As Date) is provided, filter from beginning to that date
        if (!empty($request->end_date)) {
            // Set start date to earliest possible date (beginning of time)
            $start = '2025-01-01';
            $end = $request->end_date;
        } elseif (!empty($request->start_date) && !empty($request->end_date)) {
            // Legacy support: if both dates provided, use them as range
            $start = $request->start_date;
            $end = $request->end_date;
        } else {
            $start = date('Y-m-01');
            $end = date('Y-m-t', strtotime('+1 day'));
        }

        $types = ChartOfAccountType::where('created_by', \Auth::user()->creatorId())->whereIn('name', ['Assets', 'Liabilities', 'Equity'])->get();
        $chartAccounts = [];
        foreach ($types as $type) {
            $subTypes = ChartOfAccountSubType::where('type', $type->id)->get();
            $subTypeArray = [];
            foreach ($subTypes as $subType) {
                $accounts = GeneralLedger::select('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name', \DB::raw('sum(debit) as totalDebit'), \DB::raw('sum(credit) as totalCredit'));
                $accounts->leftjoin('chart_of_accounts', 'general_ledger.account', 'chart_of_accounts.id');
                $accounts->leftjoin('chart_of_account_types', 'chart_of_accounts.type', 'chart_of_account_types.id');
                $accounts->where('chart_of_accounts.type', $type->id);
                $accounts->where('chart_of_accounts.sub_type', $subType->id);
                $accounts->where('general_ledger.created_by', \Auth::user()->creatorId());
                $accounts->where('general_ledger.send_date', '>=', $start);
                $accounts->where('general_ledger.send_date', '<=', $end);
                $accounts->groupBy('account');
                $accounts = $accounts->get()->toArray();
                $totalBalance = 0;
                $creditTotal = 0;
                $debitTotal = 0;
                $totalAmount = 0;
                $accountArray = [];
                foreach ($accounts as $account) {
                    $Balance = $account['totalCredit'] - $account['totalDebit'];
                    $totalBalance += $Balance;
                    if ($Balance != 0) {
                        $data['account_id'] = $account['id'];
                        $data['account_code'] = $account['code'];
                        $data['account_name'] = $account['name'];
                        $data['totalCredit'] = 0;
                        $data['totalDebit'] = 0;
                        $data['netAmount'] = $Balance;
                        $accountArray[] = $data;
                        $creditTotal += $data['totalCredit'];
                        $debitTotal += $data['totalDebit'];
                        $totalAmount += $data['netAmount'];
                    }
                }
                $totalAccountArray = [];
                if ($accountArray != []) {
                    $dataTotal['account_id'] = '';
                    $dataTotal['account_code'] = '';
                    $dataTotal['account_name'] = 'Total ' . $subType->name;
                    $dataTotal['totalCredit'] = $creditTotal;
                    $dataTotal['totalDebit'] = $debitTotal;
                    $dataTotal['netAmount'] = $totalAmount;
                    $accountArrayTotal[] = $dataTotal;
                    $totalAccountArray = array_merge($accountArray, $accountArrayTotal);
                }
                if ($totalAccountArray != []) {
                    $subTypeData['subType'] = ($totalAccountArray != []) ? $subType->name : '';
                    $subTypeData['account'] = $totalAccountArray;
                    $subTypeArray[] = ($subTypeData['account'] != [] && $subTypeData['subType'] != []) ? $subTypeData : [];
                }
            }
            $totalAccounts[$type->name] = $subTypeArray;
        }

        // Calculate Profit/Loss for the period
        $profitLoss = $this->calculateProfitLoss($start, $end);

        $companyName = User::where('id', \Auth::user()->creatorId())->first();
        $companyName = $companyName->name;

        $name = 'balance_sheet_' . date('Y-m-d i:h:s');
        $data = Excel::download(new BalanceSheetExport($totalAccounts, $start, $end, $companyName, $profitLoss), $name . '.xlsx');
        ob_end_clean();

        return $data;
    }


    public function profitLossExport(Request $request)
    {

        if (\Auth::user()->can('income vs expense report')) {

            if (!empty($request->start_date) && !empty($request->end_date)) {
                $start = $request->start_date;
                $end = $request->end_date;
            } else {
                $start = date('Y-01-01');
                $end = date('Y-m-d', strtotime('+1 day'));
            }

            $types = ChartOfAccountType::where('created_by', \Auth::user()->creatorId())->whereIn('name', ['Income', 'Expenses', 'Costs of Goods Sold'])->get();
            $subTypeArray = [];
            $totalAccounts = [];
            foreach ($types as $type) {
                $accounts = GeneralLedger::select('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name', \DB::raw('sum(debit) as totalDebit'), \DB::raw('sum(credit) as totalCredit'));
                $accounts->leftjoin('chart_of_accounts', 'general_ledger.account', 'chart_of_accounts.id');
                $accounts->leftjoin('chart_of_account_types', 'chart_of_accounts.type', 'chart_of_account_types.id');
                $accounts->where('chart_of_accounts.type', $type->id);
                $accounts->where('general_ledger.created_by', \Auth::user()->creatorId());
                $accounts->where('general_ledger.send_date', '>=', $start);
                $accounts->where('general_ledger.send_date', '<=', $end);
                $accounts->groupBy('account');
                $accounts = $accounts->get()->toArray();
                $totalBalance = 0;
                $creditTotal = 0;
                $debitTotal = 0;
                $totalAmount = 0;
                $accountArray = [];
                foreach ($accounts as $account) {
                    $Balance = $account['totalCredit'] - $account['totalDebit'];
                    $totalBalance += $Balance;
                    if ($Balance != 0) {
                        $data['account_id'] = $account['id'];
                        $data['account_code'] = $account['code'];
                        $data['account_name'] = $account['name'];
                        $data['totalCredit'] = 0;
                        $data['totalDebit'] = 0;
                        $data['netAmount'] = $Balance;
                        $accountArray[] = $data;
                        $creditTotal += $data['totalCredit'];
                        $debitTotal += $data['totalDebit'];
                        $totalAmount += $data['netAmount'];
                    }
                }
                if ($accountArray != []) {
                    $dataTotal['account_id'] = '';
                    $dataTotal['account_code'] = '';
                    $dataTotal['account_name'] = 'Total ' . $type->name;
                    $dataTotal['totalCredit'] = $creditTotal;
                    $dataTotal['totalDebit'] = $debitTotal;
                    $dataTotal['netAmount'] = $totalAmount;
                    $accountArray[] = $dataTotal;
                }
                if ($accountArray != []) {
                    $subTypeData['Type'] = ($accountArray != []) ? $type->name : '';
                    $subTypeData['account'] = $accountArray;
                    $subTypeArray[] = ($subTypeData['account'] != []) ? $subTypeData : [];
                }
                $totalAccounts = $subTypeArray;
            }
            $companyName = User::where('id', \Auth::user()->creatorId())->first();
            $companyName = $companyName->name;

            $name = 'profit & loss_' . date('Y-m-d i:h:s');
            $data = Excel::download(new ProfitLossExport($totalAccounts, $start, $end, $companyName), $name . '.xlsx');
            ob_end_clean();

            return $data;
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }



    public function salesReport(Request $request)
    {
        if (!empty($request->start_date) && !empty($request->end_date)) {
            $start = $request->start_date;
            $end = $request->end_date;
        } else {
            $start = date('Y-01-01');
            $end = date('Y-m-d', strtotime('+1 day'));
        }
        $invoiceItems = InvoiceProduct::select('product_services.name', \DB::raw('sum(invoice_products.quantity) as quantity'), \DB::raw('sum(invoice_products.price * invoice_products.quantity) as price'), \DB::raw('sum(invoice_products.price)/sum(invoice_products.quantity) as avg_price'));
        $invoiceItems->leftjoin('product_services', 'product_services.id', 'invoice_products.product_id');
        $invoiceItems->leftjoin('invoices', 'invoices.id', 'invoice_products.invoice_id');
        $invoiceItems->where('product_services.created_by', \Auth::user()->creatorId());
        $invoiceItems->where('invoices.issue_date', '>=', $start);
        $invoiceItems->where('invoices.issue_date', '<=', $end);
        $invoiceItems->groupBy('invoice_products.product_id');
        $invoiceItems = $invoiceItems->get()->toArray();

        $invoiceCustomeres = Invoice::select('customers.name', \DB::raw('count(DISTINCT invoices.customer_id, invoice_products.invoice_id) as invoice_count'))
            ->selectRaw('sum((invoice_products.price * invoice_products.quantity) - invoice_products.discount) as price')
            ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM invoice_products
             LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoice_products.tax) > 0
             WHERE invoice_products.invoice_id = invoices.id) as total_tax')
            ->leftJoin('customers', 'customers.id', 'invoices.customer_id')
            ->leftJoin('invoice_products', 'invoice_products.invoice_id', 'invoices.id')
            ->where('invoices.created_by', \Auth::user()->creatorId())
            ->where('invoices.issue_date', '>=', $start)
            ->where('invoices.issue_date', '<=', $end)
            ->groupBy('invoices.invoice_id')
            ->get()
            ->toArray();
        $mergedArray = [];
        foreach ($invoiceCustomeres as $item) {
            $name = $item["name"];

            if (!isset($mergedArray[$name])) {
                $mergedArray[$name] = [
                    "name" => $name,
                    "invoice_count" => 0,
                    "price" => 0.0,
                    "total_tax" => 0.0,
                ];
            }

            $mergedArray[$name]["invoice_count"] += $item["invoice_count"];
            $mergedArray[$name]["price"] += $item["price"];
            $mergedArray[$name]["total_tax"] += $item["total_tax"];
        }
        $invoiceCustomers = array_values($mergedArray);

        $filter['startDateRange'] = $start;
        $filter['endDateRange'] = $end;

        return view('report.sales_report', compact('filter', 'invoiceItems', 'invoiceCustomers'));
    }

    public function salesReportExport(Request $request)
    {
        if (!empty($request->start_date) && !empty($request->end_date)) {
            $start = $request->start_date;
            $end = $request->end_date;
        } else {
            $start = date('Y-01-01');
            $end = date('Y-m-d', strtotime('+1 day'));
        }
        if ($request->report == '#item') {
            $invoiceItems = InvoiceProduct::select('product_services.name', \DB::raw('sum(invoice_products.quantity) as quantity'), \DB::raw('sum(invoice_products.price * invoice_products.quantity) as price'), \DB::raw('sum(invoice_products.price)/sum(invoice_products.quantity) as avg_price'));
            $invoiceItems->leftjoin('product_services', 'product_services.id', 'invoice_products.product_id');
            $invoiceItems->leftjoin('invoices', 'invoices.id', 'invoice_products.invoice_id');
            $invoiceItems->where('product_services.created_by', \Auth::user()->creatorId());
            $invoiceItems->where('invoices.issue_date', '>=', $start);
            $invoiceItems->where('invoices.issue_date', '<=', $end);
            $invoiceItems->groupBy('invoice_products.product_id');
            $invoiceItems = $invoiceItems->get()->toArray();

            $reportName = 'Item';
        } else {
            $invoiceCustomeres = Invoice::select('customers.name', \DB::raw('count(DISTINCT invoices.customer_id, invoice_products.invoice_id) as invoice_count'))
                ->selectRaw('sum((invoice_products.price * invoice_products.quantity) - invoice_products.discount) as price')
                ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM invoice_products
             LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoice_products.tax) > 0
             WHERE invoice_products.invoice_id = invoices.id) as total_tax')
                ->leftJoin('customers', 'customers.id', 'invoices.customer_id')
                ->leftJoin('invoice_products', 'invoice_products.invoice_id', 'invoices.id')
                ->where('invoices.created_by', \Auth::user()->creatorId())
                ->where('invoices.issue_date', '>=', $start)
                ->where('invoices.issue_date', '<=', $end)
                ->groupBy('invoices.invoice_id')
                ->get()
                ->toArray();
            $mergedArray = [];
            foreach ($invoiceCustomeres as $item) {
                $name = $item["name"];

                if (!isset($mergedArray[$name])) {
                    $mergedArray[$name] = [
                        "name" => $name,
                        "invoice_count" => 0,
                        "price" => 0.0,
                        "total_tax" => 0.0,
                    ];
                }

                $mergedArray[$name]["invoice_count"] += $item["invoice_count"];
                $mergedArray[$name]["price"] += $item["price"];
                $mergedArray[$name]["total_tax"] += $item["total_tax"];
            }
            $invoiceItems = array_values($mergedArray);

            $reportName = 'Customer';
        }
        $companyName = User::where('id', \Auth::user()->creatorId())->first();
        $companyName = $companyName->name;

        $name = 'Sales By ' . $reportName . '_ ' . date('Y-m-d i:h:s');
        $data = Excel::download(new SalesReportExport($invoiceItems, $start, $end, $companyName, $reportName), $name . '.xlsx');
        ob_end_clean();

        return $data;
    }

    public function salesReportPrint(Request $request)
    {
        if (!empty($request->start_date) && !empty($request->end_date)) {
            $start = $request->start_date;
            $end = $request->end_date;
        } else {
            $start = date('Y-01-01');
            $end = date('Y-m-d', strtotime('+1 day'));
        }

        $invoiceItems = InvoiceProduct::select('product_services.name', \DB::raw('sum(invoice_products.quantity) as quantity'), \DB::raw('sum(invoice_products.price * invoice_products.quantity) as price'), \DB::raw('sum(invoice_products.price)/sum(invoice_products.quantity) as avg_price'));
        $invoiceItems->leftjoin('product_services', 'product_services.id', 'invoice_products.product_id');
        $invoiceItems->leftjoin('invoices', 'invoices.id', 'invoice_products.invoice_id');
        $invoiceItems->where('product_services.created_by', \Auth::user()->creatorId());
        $invoiceItems->where('invoices.issue_date', '>=', $start);
        $invoiceItems->where('invoices.issue_date', '<=', $end);
        $invoiceItems->groupBy('invoice_products.product_id');
        $invoiceItems = $invoiceItems->get()->toArray();

        $invoiceCustomeres = Invoice::select('customers.name', \DB::raw('count(DISTINCT invoices.customer_id, invoice_products.invoice_id) as invoice_count'))
            ->selectRaw('sum((invoice_products.price * invoice_products.quantity) - invoice_products.discount) as price')
            ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM invoice_products
             LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoice_products.tax) > 0
             WHERE invoice_products.invoice_id = invoices.id) as total_tax')
            ->leftJoin('customers', 'customers.id', 'invoices.customer_id')
            ->leftJoin('invoice_products', 'invoice_products.invoice_id', 'invoices.id')
            ->where('invoices.created_by', \Auth::user()->creatorId())
            ->where('invoices.issue_date', '>=', $start)
            ->where('invoices.issue_date', '<=', $end)
            ->groupBy('invoices.invoice_id')
            ->get()
            ->toArray();
        $mergedArray = [];
        foreach ($invoiceCustomeres as $item) {
            $name = $item["name"];

            if (!isset($mergedArray[$name])) {
                $mergedArray[$name] = [
                    "name" => $name,
                    "invoice_count" => 0,
                    "price" => 0.0,
                    "total_tax" => 0.0,
                ];
            }

            $mergedArray[$name]["invoice_count"] += $item["invoice_count"];
            $mergedArray[$name]["price"] += $item["price"];
            $mergedArray[$name]["total_tax"] += $item["total_tax"];
        }
        $invoiceCustomers = array_values($mergedArray);

        $reportName = $request->report;

        return view('report.sales_report_receipt', compact('filter', 'invoiceItems', 'invoiceCustomers', 'reportName'));
    }

    public function ReceivablesReport(Request $request)
    {
        $creatorId = \Auth::user()->creatorId();
        $customerId = $request->filled('customer') ? (int) $request->customer : null;

        if (!empty($request->start_date) && !empty($request->end_date)) {
            $start = $request->start_date;
            $end = $request->end_date;
        } else {
            $start = date('Y-01-01');
            $end = date('Y-m-d', strtotime('+1 day'));
        }

        // $receivableCustomers = Invoice::select('customers.name')
        // ->selectRaw('SUM((invoice_products.price * invoice_products.quantity) - invoice_products.discount) as price')
        // ->selectRaw('SUM(invoice_payments.amount) as pay_price')
        // ->selectRaw('(
        //     SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) 
        //     FROM invoice_products
        //     LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoices.tax_id) > 0
        //     WHERE invoice_products.invoice_id = invoices.id
        // ) as total_tax')
        // ->selectRaw('(
        //     SELECT SUM(credit_notes.amount) 
        //     FROM credit_notes 
        //     WHERE credit_notes.invoice = invoices.id
        // ) as credit_price')
        // ->selectRaw('(
        //     SELECT SUM(invoice_expenses.amount) 
        //     FROM invoice_expenses 
        //     WHERE invoice_expenses.invoice_id = invoices.id
        // ) as expense_price') // ← this line is new
        // ->leftJoin('customers', 'customers.id', 'invoices.customer_id')
        // ->leftJoin('invoice_payments', 'invoice_payments.invoice_id', 'invoices.id')
        // ->leftJoin('invoice_products', 'invoice_products.invoice_id', 'invoices.id')
        // ->where('invoices.created_by', \Auth::user()->creatorId())
        // ->where('invoices.issue_date', '>=', $start)
        // ->where('invoices.issue_date', '<=', $end)
        // ->groupBy('invoices.invoice_id')
        // ->get()
        // ->toArray();

        $receivablesAccountId = ChartOfAccount::where('created_by', $creatorId)
            ->where('name', '=', 'Account Receivables')
            ->first()
            ?->id;

        // Build current-period totals per customer (between $start and $end)
        $currentTotalsSub = DB::table('general_ledger')
            ->where('created_by', $creatorId)
            ->whereNotNull('user_id')
            ->where('account', $receivablesAccountId)
            ->whereBetween('send_date', [$start, $end])
            ->when($customerId, function ($q) use ($customerId) {
                $q->where('user_id', $customerId);
            })
            ->selectRaw('user_id as customer_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->groupBy('user_id');

        // Build previous-balance totals per customer (before $start)
        $previousTotalsSub = DB::table('general_ledger')
            ->where('created_by', $creatorId)
            ->whereNotNull('user_id')
            ->where('account', $receivablesAccountId)
            ->where('send_date', '<', $start)
            ->when($customerId, function ($q) use ($customerId) {
                $q->where('user_id', $customerId);
            })
            ->selectRaw('user_id as customer_id, SUM(debit - credit) as previous_balance')
            ->groupBy('user_id');

        // Join customers with both aggregates so we can include:
        // - customers with current-period activity
        // - customers with zero current activity but non-zero previous balance
        $receivableCustomers = DB::table('customers')
            ->where('customers.created_by', $creatorId)
            ->when($customerId, function ($q) use ($customerId) {
                $q->where('customers.id', $customerId);
            })
            ->leftJoinSub($currentTotalsSub, 'gl_curr', function ($join) {
                $join->on('gl_curr.customer_id', '=', 'customers.id');
            })
            ->leftJoinSub($previousTotalsSub, 'gl_prev', function ($join) {
                $join->on('gl_prev.customer_id', '=', 'customers.id');
            })
            ->selectRaw('
                customers.id as customer_id,
                customers.name as customer_name,
                COALESCE(gl_curr.total_debit, 0) as total_debit,
                COALESCE(gl_curr.total_credit, 0) as total_credit,
                COALESCE(gl_prev.previous_balance, 0) as previous_balance
            ')
            ->whereRaw('(
                COALESCE(gl_curr.total_debit, 0) - COALESCE(gl_curr.total_credit, 0) != 0
                OR COALESCE(gl_prev.previous_balance, 0) != 0
            )')
            ->orderBy('customers.name')
            ->get()
            ->toArray();

        $receivableSummariesInvoice = Invoice::select('customers.name')
        ->selectRaw('invoices.invoice_id as invoice')
        ->selectRaw('(
            SELECT SUM(price * quantity - discount)
            FROM invoice_products
            WHERE invoice_products.invoice_id = invoices.id
        ) as price')
        ->selectRaw('(
            SELECT SUM(amount)
            FROM invoice_payments
            WHERE invoice_payments.invoice_id = invoices.id
        ) as pay_price')
        ->selectRaw('(
            SELECT MAX(customer_payments.status)
            FROM invoice_payments
            INNER JOIN customer_payments ON customer_payments.id = invoice_payments.payment_id
            WHERE invoice_payments.invoice_id = invoices.id
        ) as customer_payment_status')
        ->selectRaw('(
            SELECT SUM((price * quantity - discount) * (taxes.rate / 100))
            FROM invoice_products
            LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoices.tax_id) > 0
            WHERE invoice_products.invoice_id = invoices.id
        ) as total_tax')
        ->selectRaw('(
            SELECT SUM(amount)
            FROM invoice_expenses
            WHERE invoice_expenses.invoice_id = invoices.id
        ) as total_expense')
        ->selectRaw('invoices.issue_date')
        ->selectRaw('invoices.status')
        ->selectRaw('invoices.payment_status')
        ->leftJoin('customers', 'customers.id', '=', 'invoices.customer_id')
        ->where('invoices.created_by', $creatorId)
        ->whereBetween('invoices.issue_date', [$start, $end])
        ->when($customerId, function ($q) use ($customerId) {
            $q->where('invoices.customer_id', $customerId);
        })
        ->groupBy('invoices.id', 'customers.name', 'invoices.issue_date', 'invoices.status', 'invoices.payment_status')
        ->get()
        ->toArray();

        $receivableSummariesCredit = CreditNote::select('customers.name')
            ->selectRaw('null as invoice')
            ->selectRaw('(credit_notes.amount) as price')
            ->selectRaw('0 as pay_price')
            ->selectRaw('0 as total_tax')
            ->selectRaw('null as payment_status')
            ->selectRaw('null as customer_payment_status')
            ->selectRaw('credit_notes.date as issue_date')
            ->selectRaw('5 as status')
            ->leftJoin('customers', 'customers.id', 'credit_notes.customer')
            ->leftJoin('invoice_products', 'invoice_products.invoice_id', 'credit_notes.invoice')
            ->leftJoin('invoices', 'invoices.id', 'credit_notes.invoice')
            ->where('invoices.created_by', $creatorId)
            ->where('credit_notes.date', '>=', $start)
            ->where('credit_notes.date', '<=', $end)
            ->when($customerId, function ($q) use ($customerId) {
                $q->where('credit_notes.customer', $customerId);
            })
            ->groupBy('credit_notes.id')
            ->get()
            ->toArray();

        $receivableSummaries = (array_merge($receivableSummariesCredit, $receivableSummariesInvoice));

        $receivableDetailsInvoice = Invoice::select('customers.name')
        ->selectRaw('invoices.invoice_id as invoice')
        ->selectRaw('SUM(invoice_products.price - invoice_products.discount) as price')
        ->selectRaw('invoice_products.quantity as quantity')
        ->selectRaw('invoice_products.discount as discount')
        ->selectRaw('product_services.name as product_name')
        ->selectRaw('SUM(invoice_expenses_sum.total_expense) as total_expense')
        ->selectRaw('(
            SELECT SUM((invoice_products.price * invoice_products.quantity - invoice_products.discount) * (taxes.rate / 100))
            FROM invoice_products
            LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoices.tax_id) > 0
            WHERE invoice_products.invoice_id = invoices.id
        ) as total_tax')
        ->selectRaw('invoices.issue_date as issue_date')
        ->selectRaw('invoices.status as status')
        ->selectRaw('invoices.payment_status as payment_status')
        ->selectRaw('(
            SELECT MAX(customer_payments.status)
            FROM invoice_payments
            INNER JOIN customer_payments ON customer_payments.id = invoice_payments.payment_id
            WHERE invoice_payments.invoice_id = invoices.id
        ) as customer_payment_status')
        ->leftJoin('customers', 'customers.id', '=', 'invoices.customer_id')
        ->leftJoin('invoice_products', 'invoice_products.invoice_id', '=', 'invoices.id')
        ->leftJoin('product_services', 'product_services.id', '=', 'invoice_products.product_id')
        // Replace invoice_expenses join with a subquery join
        ->leftJoin(\DB::raw('(SELECT invoice_id, SUM(amount) as total_expense FROM invoice_expenses GROUP BY invoice_id) as invoice_expenses_sum'), function($join) {
            $join->on('invoice_expenses_sum.invoice_id', '=', 'invoices.id');
        })
        ->where('invoices.created_by', $creatorId)
        ->whereBetween('invoices.issue_date', [$start, $end])
        ->when($customerId, function ($q) use ($customerId) {
            $q->where('invoices.customer_id', $customerId);
        })
        ->groupBy('invoices.id', 'invoice_products.quantity', 'invoice_products.discount', 'product_services.name', 'invoices.invoice_id', 'invoices.issue_date', 'invoices.status', 'invoices.payment_status', 'customers.name')
        ->get()
        ->toArray();

        $receivableDetailsCredit = CreditNote::select('customers.name')
            ->selectRaw('null as invoice')
            ->selectRaw('(credit_notes.id) as invoices')
            ->selectRaw('(credit_notes.amount) as price')
            ->selectRaw('(product_services.name) as product_name')
            ->selectRaw('null as payment_status')
            ->selectRaw('null as customer_payment_status')
            ->selectRaw('credit_notes.date as issue_date')
            ->selectRaw('5 as status')
            ->leftJoin('customers', 'customers.id', 'credit_notes.customer')
            ->leftJoin('invoice_products', 'invoice_products.invoice_id', 'credit_notes.invoice')
            ->leftJoin('product_services', 'product_services.id', 'invoice_products.product_id')
            ->leftJoin('invoices', 'invoices.id', 'credit_notes.invoice')
            ->where('invoices.created_by', $creatorId)
            ->where('credit_notes.date', '>=', $start)
            ->where('credit_notes.date', '<=', $end)
            ->when($customerId, function ($q) use ($customerId) {
                $q->where('credit_notes.customer', $customerId);
            })
            ->groupBy('credit_notes.id', 'product_services.name')
            ->get()
            ->toArray();

        $mergedArray = [];
        foreach ($receivableDetailsCredit as $item) {
            $invoices = $item["invoices"];

            if (!isset($mergedArray[$invoices])) {
                $mergedArray[$invoices] = [
                    "name" => $item["name"],
                    "invoice" => $item["invoice"],
                    "invoices" => $invoices,
                    "price" => $item["price"] - $item["discount"],
                    "total_expense"=> $item["total_expense"],
                    "total_tax"=> $item["total_tax"],
                    "quantity" => 0,
                    "product_name" => "",
                    "issue_date" => "",
                    "status" => 0,
                ];
            }

            if (!strstr($mergedArray[$invoices]["product_name"], $item["product_name"])) {
                if ($mergedArray[$invoices]["product_name"] !== "") {
                    $mergedArray[$invoices]["product_name"] .= ", ";
                }
                $mergedArray[$invoices]["product_name"] .= $item["product_name"];
            }

            $mergedArray[$invoices]["issue_date"] = $item["issue_date"];
            $mergedArray[$invoices]["status"] = $item["status"];
        }

        $receivableDetailsCredits = array_values($mergedArray);

        $receivableDetails = (array_merge($receivableDetailsInvoice, $receivableDetailsCredits));

        $agingSummary = Invoice::select(
            'customers.name',
            'invoices.due_date as due_date',
            'invoices.status as status',
            'invoices.invoice_id as invoice_id',
            'invoices.payment_status as payment_status'
        )
        ->selectRaw('(
            SELECT SUM((invoice_products.price * invoice_products.quantity) - invoice_products.discount)
            FROM invoice_products
            WHERE invoice_products.invoice_id = invoices.id
        ) as price')
        ->selectRaw('(
            SELECT SUM(invoice_payments.amount)
            FROM invoice_payments
            WHERE invoice_payments.invoice_id = invoices.id
        ) as pay_price')
        ->selectRaw('(
            SELECT SUM((invoice_products.price * invoice_products.quantity - invoice_products.discount) * (taxes.rate / 100))
            FROM invoice_products
            LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoices.tax_id) > 0
            WHERE invoice_products.invoice_id = invoices.id
        ) as total_tax')
        ->selectRaw('(
            SELECT SUM(credit_notes.amount)
            FROM credit_notes
            WHERE credit_notes.invoice = invoices.id
        ) as credit_price')
        ->selectRaw('(
            SELECT SUM(invoice_expenses.amount)
            FROM invoice_expenses
            WHERE invoice_expenses.invoice_id = invoices.id
        ) as expense_amount')
        ->leftJoin('customers', 'customers.id', '=', 'invoices.customer_id')
        ->where('invoices.created_by', $creatorId)
        ->whereBetween('invoices.issue_date', [$start, $end])
        ->when($customerId, function ($q) use ($customerId) {
            $q->where('invoices.customer_id', $customerId);
        })
        ->groupBy('invoices.id')
        ->get()
        ->toArray();

        $agingSummaries = [];

        $today = date("Y-m-d");
        foreach ($agingSummary as $item) {
            $name = $item["name"];
            $price = floatval(($item["price"] + $item['total_tax'] + $item['expense_amount']) - ($item['pay_price'] + $item['credit_price']));
            $dueDate = $item["due_date"];

            if (!isset($agingSummaries[$name])) {
                $agingSummaries[$name] = [
                    'current' => 0.0,
                    "1_15_days" => 0.0,
                    "16_30_days" => 0.0,
                    "31_45_days" => 0.0,
                    "greater_than_45_days" => 0.0,
                    "total_due" => 0.0,
                ];
            }

            $daysDifference = date_diff(date_create($dueDate), date_create($today));
            $daysDifference = $daysDifference->format("%R%a");

            if ($daysDifference <= 0) {
                $agingSummaries[$name]["current"] += $price;
            } elseif ($daysDifference >= 1 && $daysDifference <= 15) {
                $agingSummaries[$name]["1_15_days"] += $price;
            } elseif ($daysDifference >= 16 && $daysDifference <= 30) {
                $agingSummaries[$name]["16_30_days"] += $price;
            } elseif ($daysDifference >= 31 && $daysDifference <= 45) {
                $agingSummaries[$name]["31_45_days"] += $price;
            } elseif ($daysDifference > 45) {
                $agingSummaries[$name]["greater_than_45_days"] += $price;
            }

            $agingSummaries[$name]["total_due"] += $price;
        }

        $currents = [];
        $days1to15 = [];
        $days16to30 = [];
        $days31to45 = [];
        $moreThan45 = [];

        foreach ($agingSummary as $item) {
            $dueDate = $item["due_date"];
            $price = floatval($item["price"]);
            $expense_amount = floatval($item["expense_amount"]);
            $total_tax = floatval($item["total_tax"]);
            $credit_price = floatval($item["credit_price"]);
            $payPrice = $item["pay_price"] ? floatval($item["pay_price"]) : 0;

            $daysDifference = date_diff(date_create($dueDate), date_create($today));
            $daysDifference = $daysDifference->format("%R%a");
            $balanceDue = ($price + $total_tax +  $expense_amount ) - ($payPrice + $credit_price);
            $totalPrice = $price + $total_tax +  $expense_amount ;
            if ($daysDifference <= 0) {
                $item["total_price"] = $totalPrice;
                $item["balance_due"] = $balanceDue;
                $currents[] = $item;
            } elseif ($daysDifference >= 1 && $daysDifference <= 15) {
                $item["total_price"] = $totalPrice;
                $item["balance_due"] = $balanceDue;
                $item['age']         = intval(str_replace(array('+', '-'), '', $daysDifference));
                $days1to15[] = $item;
            } elseif ($daysDifference >= 16 && $daysDifference <= 30) {
                $item["total_price"] = $totalPrice;
                $item["balance_due"] = $balanceDue;
                $item['age']         = intval(str_replace(array('+', '-'), '', $daysDifference));
                $days16to30[] = $item;
            } elseif ($daysDifference >= 31 && $daysDifference <= 45) {
                $item["total_price"] = $totalPrice;
                $item["balance_due"] = $balanceDue;
                $item['age']         = intval(str_replace(array('+', '-'), '', $daysDifference));
                $days31to45[] = $item;
            } else {
                $item["total_price"] = $totalPrice;
                $item["balance_due"] = $balanceDue;
                $item['age']         = intval(str_replace(array('+', '-'), '', $daysDifference));
                $moreThan45[] = $item;
            }
        }

        $filter['startDateRange'] = $start;
        $filter['endDateRange'] = $end;
        $filter['customer'] = $customerId ?: '';

        $customers = Customer::where('created_by', $creatorId)->pluck('name', 'id');
        $customers->prepend(__('All'), '');

        return view('report.receivable_report', compact('filter', 'customers', 'receivableCustomers', 'receivableSummaries', 'receivableDetails', 'agingSummaries', 'currents', 'days1to15', 'days16to30', 'days31to45', 'moreThan45'));
    }

    public function ReceivablesExport(Request $request)
    {
        if (!empty($request->start_date) && !empty($request->end_date)) {
            $start = $request->start_date;
            $end = $request->end_date;
        } else {
            $start = date('Y-01-01');
            $end = date('Y-m-d', strtotime('+1 day'));
        }

        $receivableCustomers = Invoice::select('customers.name')
            ->selectRaw('sum((invoice_products.price * invoice_products.quantity) - invoice_products.discount) as price')
            ->selectRaw('sum((invoice_payments.amount)) as pay_price')
            ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM invoice_products
             LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoice_products.tax) > 0
             WHERE invoice_products.invoice_id = invoices.id) as total_tax')
            ->selectRaw('(SELECT SUM(credit_notes.amount) FROM credit_notes
             WHERE credit_notes.invoice = invoices.id) as credit_price')
            ->leftJoin('customers', 'customers.id', 'invoices.customer_id')
            ->leftJoin('invoice_payments', 'invoice_payments.invoice_id', 'invoices.id')
            ->leftJoin('invoice_products', 'invoice_products.invoice_id', 'invoices.id')
            ->where('invoices.created_by', \Auth::user()->creatorId())
            ->where('invoices.issue_date', '>=', $start)
            ->where('invoices.issue_date', '<=', $end)
            ->groupBy('invoices.invoice_id')
            ->get()
            ->toArray();

        $companyName = User::where('id', \Auth::user()->creatorId())->first();
        $companyName = $companyName->name;

        $name = 'Receivable Report_ ' . date('Y-m-d i:h:s');
        $data = Excel::download(new ReceivableExport($receivableCustomers, $start, $end, $companyName), $name . '.xlsx');
        ob_end_clean();

        return $data;
    }

    public function ReceivablesPrint(Request $request)
    {
        if (!empty($request->start_date) && !empty($request->end_date)) {
            $start = $request->start_date;
            $end = $request->end_date;
        } else {
            $start = date('Y-01-01');
            $end = date('Y-m-d', strtotime('+1 day'));
        }
        $receivableCustomers = Invoice::select('customers.name')
            ->selectRaw('sum((invoice_products.price * invoice_products.quantity) - invoice_products.discount) as price')
            ->selectRaw('sum((invoice_payments.amount)) as pay_price')
            ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM invoice_products
             LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoices.tax_id) > 0
             WHERE invoice_products.invoice_id = invoices.id) as total_tax')
            ->selectRaw('(SELECT SUM(credit_notes.amount) FROM credit_notes
             WHERE credit_notes.invoice = invoices.id) as credit_price')
            ->leftJoin('customers', 'customers.id', 'invoices.customer_id')
            ->leftJoin('invoice_payments', 'invoice_payments.invoice_id', 'invoices.id')
            ->leftJoin('invoice_products', 'invoice_products.invoice_id', 'invoices.id')
            ->where('invoices.created_by', \Auth::user()->creatorId())
            ->where('invoices.issue_date', '>=', $start)
            ->where('invoices.issue_date', '<=', $end)
            ->groupBy('invoices.invoice_id')
            ->get()
            ->toArray();

        $receivableSummariesInvoice = Invoice::select('customers.name')
            ->selectRaw('(invoices.invoice_id) as invoice')
            ->selectRaw('sum((invoice_products.price * invoice_products.quantity) - invoice_products.discount) as price')
            ->selectRaw('sum((invoice_payments.amount)) as pay_price')
            ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM invoice_products
             LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoices.tax_id) > 0
             WHERE invoice_products.invoice_id = invoices.id) as total_tax')
            ->selectRaw('invoices.issue_date as issue_date')
            ->selectRaw('invoices.status as status')
            ->leftJoin('customers', 'customers.id', 'invoices.customer_id')
            ->leftJoin('invoice_payments', 'invoice_payments.invoice_id', 'invoices.id')
            ->leftJoin('invoice_products', 'invoice_products.invoice_id', 'invoices.id')
            ->where('invoices.created_by', \Auth::user()->creatorId())
            ->where('invoices.issue_date', '>=', $start)
            ->where('invoices.issue_date', '<=', $end)
            ->groupBy('invoices.invoice_id')
            ->get()
            ->toArray();

        $receivableSummariesCredit = CreditNote::select('customers.name')
            ->selectRaw('null as invoice')
            ->selectRaw('(credit_notes.amount) as price')
            ->selectRaw('0 as pay_price')
            ->selectRaw('0 as total_tax')
            ->selectRaw('credit_notes.date as issue_date')
            ->selectRaw('5 as status')
            ->leftJoin('customers', 'customers.id', 'credit_notes.customer')
            ->leftJoin('invoice_products', 'invoice_products.invoice_id', 'credit_notes.invoice')
            ->leftJoin('invoices', 'invoices.id', 'credit_notes.invoice')
            ->where('invoices.created_by', \Auth::user()->creatorId())
            ->where('credit_notes.date', '>=', $start)
            ->where('credit_notes.date', '<=', $end)
            ->groupBy('credit_notes.id')
            ->get()
            ->toArray();

        $receivableSummaries = (array_merge($receivableSummariesCredit, $receivableSummariesInvoice));

        $receivableDetailsInvoice = Invoice::select('customers.name')
            ->selectRaw('(invoices.invoice_id) as invoice')
            ->selectRaw('sum(invoice_products.price) as price')
            ->selectRaw('(invoice_products.quantity) as quantity')
            ->selectRaw('(product_services.name) as product_name')
            ->selectRaw('invoices.issue_date as issue_date')
            ->selectRaw('invoices.status as status')
            ->leftJoin('customers', 'customers.id', 'invoices.customer_id')
            ->leftJoin('invoice_products', 'invoice_products.invoice_id', 'invoices.id')
            ->leftJoin('product_services', 'product_services.id', 'invoice_products.product_id')
            ->where('invoices.created_by', \Auth::user()->creatorId())
            ->where('invoices.issue_date', '>=', $start)
            ->where('invoices.issue_date', '<=', $end)
            ->groupBy('invoices.invoice_id', 'product_services.name')
            ->get()
            ->toArray();

        $receivableDetailsCredit = CreditNote::select('customers.name')
            ->selectRaw('null as invoice')
            ->selectRaw('(credit_notes.id) as invoices')
            ->selectRaw('(credit_notes.amount) as price')
            ->selectRaw('(product_services.name) as product_name')
            ->selectRaw('credit_notes.date as issue_date')
            ->selectRaw('5 as status')
            ->leftJoin('customers', 'customers.id', 'credit_notes.customer')
            ->leftJoin('invoice_products', 'invoice_products.invoice_id', 'credit_notes.invoice')
            ->leftJoin('product_services', 'product_services.id', 'invoice_products.product_id')
            ->leftJoin('invoices', 'invoices.id', 'credit_notes.invoice')
            ->where('invoices.created_by', \Auth::user()->creatorId())
            ->where('credit_notes.date', '>=', $start)
            ->where('credit_notes.date', '<=', $end)
            ->groupBy('credit_notes.id', 'product_services.name')
            ->get()
            ->toArray();

        $mergedArray = [];
        foreach ($receivableDetailsCredit as $item) {
            $invoices = $item["invoices"];

            if (!isset($mergedArray[$invoices])) {
                $mergedArray[$invoices] = [
                    "name" => $item["name"],
                    "invoice" => $item["invoice"],
                    "invoices" => $invoices,
                    "price" => $item["price"],
                    "quantity" => 0,
                    "product_name" => "",
                    "issue_date" => "",
                    "status" => 0,
                ];
            }

            if (!strstr($mergedArray[$invoices]["product_name"], $item["product_name"])) {
                if ($mergedArray[$invoices]["product_name"] !== "") {
                    $mergedArray[$invoices]["product_name"] .= ", ";
                }
                $mergedArray[$invoices]["product_name"] .= $item["product_name"];
            }

            $mergedArray[$invoices]["issue_date"] = $item["issue_date"];
            $mergedArray[$invoices]["status"] = $item["status"];
        }

        $receivableDetailsCredits = array_values($mergedArray);

        $receivableDetails = (array_merge($receivableDetailsInvoice, $receivableDetailsCredits));

        $agingSummary = Invoice::select('customers.name', 'invoices.due_date as due_date', 'invoices.status as status', 'invoices.invoice_id as invoice_id')
            ->selectRaw('sum((invoice_products.price * invoice_products.quantity) - invoice_products.discount) as price')
            ->selectRaw('sum((invoice_payments.amount)) as pay_price')
            ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM invoice_products
             LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoice_products.tax) > 0
             WHERE invoice_products.invoice_id = invoices.id) as total_tax')
            ->selectRaw('(SELECT SUM(credit_notes.amount) FROM credit_notes
             WHERE credit_notes.invoice = invoices.id) as credit_price')
            ->leftJoin('customers', 'customers.id', 'invoices.customer_id')
            ->leftJoin('invoice_payments', 'invoice_payments.invoice_id', 'invoices.id')
            ->leftJoin('invoice_products', 'invoice_products.invoice_id', 'invoices.id')
            ->where('invoices.created_by', \Auth::user()->creatorId())
            ->where('invoices.issue_date', '>=', $start)
            ->where('invoices.issue_date', '<=', $end)
            ->groupBy('invoices.invoice_id')
            ->get()
            ->toArray();

        $agingSummaries = [];

        $today = date("Y-m-d");
        foreach ($agingSummary as $item) {
            $name = $item["name"];
            $price = floatval(($item["price"] + $item['total_tax']) - ($item['pay_price'] + $item['credit_price']));
            $dueDate = $item["due_date"];

            if (!isset($agingSummaries[$name])) {
                $agingSummaries[$name] = [
                    'current' => 0.0,
                    "1_15_days" => 0.0,
                    "16_30_days" => 0.0,
                    "31_45_days" => 0.0,
                    "greater_than_45_days" => 0.0,
                    "total_due" => 0.0,
                ];
            }

            $daysDifference = date_diff(date_create($dueDate), date_create($today));
            $daysDifference = $daysDifference->format("%R%a");

            if ($daysDifference <= 0) {
                $agingSummaries[$name]["current"] += $price;
            } elseif ($daysDifference >= 1 && $daysDifference <= 15) {
                $agingSummaries[$name]["1_15_days"] += $price;
            } elseif ($daysDifference >= 16 && $daysDifference <= 30) {
                $agingSummaries[$name]["16_30_days"] += $price;
            } elseif ($daysDifference >= 31 && $daysDifference <= 45) {
                $agingSummaries[$name]["31_45_days"] += $price;
            } elseif ($daysDifference > 45) {
                $agingSummaries[$name]["greater_than_45_days"] += $price;
            }

            $agingSummaries[$name]["total_due"] += $price;
        }

        $currents = [];
        $days1to15 = [];
        $days16to30 = [];
        $days31to45 = [];
        $moreThan45 = [];

        foreach ($agingSummary as $item) {
            $dueDate = $item["due_date"];
            $price = floatval($item["price"]);
            $total_tax = floatval($item["total_tax"]);
            $credit_price = floatval($item["credit_price"]);
            $payPrice = $item["pay_price"] ? floatval($item["pay_price"]) : 0;

            $daysDifference = date_diff(date_create($dueDate), date_create($today));
            $daysDifference = $daysDifference->format("%R%a");
            $balanceDue = ($price + $total_tax) - ($payPrice + $credit_price);
            $totalPrice = $price + $total_tax;
            if ($daysDifference <= 0) {
                $item["total_price"] = $totalPrice;
                $item["balance_due"] = $balanceDue;
                $currents[] = $item;
            } elseif ($daysDifference >= 1 && $daysDifference <= 15) {
                $item["total_price"] = $totalPrice;
                $item["balance_due"] = $balanceDue;
                $item['age']         = intval(str_replace(array('+', '-'), '', $daysDifference));
                $days1to15[] = $item;
            } elseif ($daysDifference >= 16 && $daysDifference <= 30) {
                $item["total_price"] = $totalPrice;
                $item["balance_due"] = $balanceDue;
                $item['age']         = intval(str_replace(array('+', '-'), '', $daysDifference));
                $days16to30[] = $item;
            } elseif ($daysDifference >= 31 && $daysDifference <= 45) {
                $item["total_price"] = $totalPrice;
                $item["balance_due"] = $balanceDue;
                $item['age']         = intval(str_replace(array('+', '-'), '', $daysDifference));
                $days31to45[] = $item;
            } else {
                $item["total_price"] = $totalPrice;
                $item["balance_due"] = $balanceDue;
                $item['age']         = intval(str_replace(array('+', '-'), '', $daysDifference));
                $moreThan45[] = $item;
            }
        }

        $filter['startDateRange'] = $start;
        $filter['endDateRange'] = $end;
        $reportName = $request->report;

        return view('report.receivable_report_receipt', compact(
            'filter',
            'receivableCustomers',
            'receivableSummaries',
            'moreThan45',
            'days31to45',
            'days16to30',
            'days1to15',
            'currents',
            'reportName',
            'receivableDetails',
            'agingSummaries'
        ));
    }

    public function PayablesReport(Request $request)
    {
        if (!empty($request->start_date) && !empty($request->end_date)) {
            $start = $request->start_date;
            $end = $request->end_date;
        } else {
            $start = date('Y-01-01');
            $end = date('Y-m-d', strtotime('+1 day'));
        }

        $payableVendors = DB::table('general_ledger')
            ->select('venders.name')
            ->selectRaw('SUM(general_ledger.debit) as total_debit')
            ->selectRaw('SUM(general_ledger.credit) as total_credit')
            ->selectRaw('(SUM(general_ledger.debit) - SUM(general_ledger.credit)) as balance')
            ->Join('venders', 'venders.id', '=', 'general_ledger.user_id')
            ->where('general_ledger.created_by', \Auth::user()->creatorId())
            ->where('general_ledger.user_id','!=', 0)
            ->where('general_ledger.account',ChartOfAccount::where('created_by', \Auth::user()->creatorId())->where('name', '=', 'Account Payable')->first()->id)
            // ->where(function ($query) {
            //     $query->where('general_ledger.type', 'LIKE', '%BILL%')
            //         ->orWhere('general_ledger.type', 'LIKE', '%Vendor%')
            //         ->orWhere('general_ledger.reference', 'LIKE', '%Payment%')
            //         ->orWhere('general_ledger.reference', 'LIKE', '%Expense%')
            //         ->orWhere('general_ledger.reference', 'LIKE', '%Expense Payment%')
            //         ->orWhere('general_ledger.reference', 'LIKE', '%Bill%')
            //         ->orWhere('general_ledger.type', 'LIKE', '%Debit Note%');
            // })
            ->whereBetween('general_ledger.send_date', [$start, $end])
            ->whereNotNull('venders.name')
            ->groupBy('venders.id')
            ->havingRaw('(SUM(general_ledger.debit) - SUM(general_ledger.credit)) != 0')
            ->get()
            ->toArray();

        $payableSummariesBill = Bill::select('venders.name')
            ->selectRaw('(bills.bill_id) as bill')
            ->selectRaw('(bills.type) as type')
            ->selectRaw('sum((bill_products.price * bill_products.quantity) - bill_products.discount) + COALESCE(sum(bill_accounts.price), 0) as price')
            ->selectRaw('sum((bill_payments.amount)) as pay_price')
            ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM bill_products
         LEFT JOIN taxes ON FIND_IN_SET(taxes.id, bills.tax_id) > 0
         WHERE bill_products.bill_id = bills.id) as total_tax')
            ->selectRaw('bills.bill_date as bill_date')
            ->selectRaw('bills.status as status')
            ->leftJoin('venders', 'venders.id', 'bills.vender_id')
            ->leftJoin('bill_payments', 'bill_payments.bill_id', 'bills.id')
            ->leftJoin('bill_products', 'bill_products.bill_id', 'bills.id')
            ->leftJoin('bill_accounts', 'bill_accounts.ref_id', 'bills.id')
            ->where('bills.created_by', \Auth::user()->creatorId())
            ->whereNotIn('bills.user_type', ['employee', 'customer'])
            ->where('bills.bill_date', '>=', $start)
            ->where('bills.bill_date', '<=', $end)
            ->groupBy('bills.id')
            ->get()
            ->toArray();

        $payableSummariesDebit = DebitNote::select('venders.name')
            ->selectRaw('null as bill')
            ->selectRaw('debit_notes.amount as price')
            ->selectRaw('0 as pay_price')
            ->selectRaw('0 as total_tax')
            ->selectRaw('debit_notes.date as bill_date')
            ->selectRaw('5 as status')
            ->leftJoin('venders', 'venders.id', 'debit_notes.vendor')
            ->leftJoin('bill_products', 'bill_products.bill_id', 'debit_notes.bill')
            ->leftJoin('bills', 'bills.id', 'debit_notes.bill')
            ->where('bills.created_by', \Auth::user()->creatorId())
            ->where('debit_notes.date', '>=', $start)
            ->where('debit_notes.date', '<=', $end)
            ->groupBy('debit_notes.id')
            ->get()
            ->toArray();

        $payableSummaries = (array_merge($payableSummariesDebit, $payableSummariesBill));

        $payableDetailsBill = Bill::select('venders.name')
            ->selectRaw('(bills.bill_id) as bill')
            ->selectRaw('(bills.type) as type')
            ->selectRaw('sum(bill_products.price) + COALESCE(sum(bill_accounts.price), 0) as price')
            ->selectRaw('(bill_products.quantity) as quantity')
            ->selectRaw('(product_services.name) as product_name')
            ->selectRaw('bills.bill_date as bill_date')
            ->selectRaw('bills.status as status')
            ->leftJoin('venders', 'venders.id', 'bills.vender_id')
            ->leftJoin('bill_products', 'bill_products.bill_id', 'bills.id')
            ->leftJoin('product_services', 'product_services.id', 'bill_products.product_id')
            ->leftJoin('bill_accounts', 'bill_accounts.ref_id', 'bills.id')
            ->where('bills.created_by', \Auth::user()->creatorId())
            ->whereNotIn('bills.user_type', ['employee', 'customer'])
            ->where('bills.bill_date', '>=', $start)
            ->where('bills.bill_date', '<=', $end)
            ->groupBy('bills.bill_id', 'product_services.name')
            ->get()
            ->toArray();

        $payableDetailsDebit = DebitNote::select('venders.name')
            ->selectRaw('null as bill')
            ->selectRaw('(debit_notes.id) as bills')
            ->selectRaw('(debit_notes.amount) as price')
            ->selectRaw('(product_services.name) as product_name')
            ->selectRaw('debit_notes.date as bill_date')
            ->selectRaw('5 as status')
            ->leftJoin('venders', 'venders.id', 'debit_notes.vendor')
            ->leftJoin('bill_products', 'bill_products.bill_id', 'debit_notes.bill')
            ->leftJoin('product_services', 'product_services.id', 'bill_products.product_id')
            ->leftJoin('bills', 'bills.id', 'debit_notes.bill')
            ->where('bills.created_by', \Auth::user()->creatorId())
            ->where('debit_notes.date', '>=', $start)
            ->where('debit_notes.date', '<=', $end)
            ->groupBy('debit_notes.id', 'product_services.name')
            ->get()
            ->toArray();

        $mergedArray = [];
        foreach ($payableDetailsDebit as $item) {
            $invoices = $item["bills"];

            if (!isset($mergedArray[$invoices])) {
                $mergedArray[$invoices] = [
                    "name" => $item["name"],
                    "bill" => $item["bill"],
                    "bills" => $invoices,
                    "price" => $item["price"],
                    "quantity" => 0,
                    "product_name" => "",
                    "bill_date" => "",
                    "status" => 0,
                ];
            }

            if (!strstr($mergedArray[$invoices]["product_name"], $item["product_name"])) {
                if ($mergedArray[$invoices]["product_name"] !== "") {
                    $mergedArray[$invoices]["product_name"] .= ", ";
                }
                $mergedArray[$invoices]["product_name"] .= $item["product_name"];
            }

            $mergedArray[$invoices]["bill_date"] = $item["bill_date"];
            $mergedArray[$invoices]["status"] = $item["status"];
        }

        $payableDetailsDebits = array_values($mergedArray);

        $payableDetails = (array_merge($payableDetailsBill, $payableDetailsDebits));

        $filter['startDateRange'] = $start;
        $filter['endDateRange'] = $end;

        return view('report.payable_report', compact('filter', 'payableVendors', 'payableSummaries', 'payableDetails'));
    }

    public function PayablesPrint(Request $request)
    {
        if (!empty($request->start_date) && !empty($request->end_date)) {
            $start = $request->start_date;
            $end = $request->end_date;
        } else {
            $start = date('Y-01-01');
            $end = date('Y-m-d', strtotime('+1 day'));
        }

        $payableVendors = DB::table('general_ledger')
            ->select('venders.name')
            ->selectRaw('SUM(general_ledger.debit) as total_debit')
            ->selectRaw('SUM(general_ledger.credit) as total_credit')
            ->selectRaw('(SUM(general_ledger.debit) - SUM(general_ledger.credit)) as balance')
            ->innerJoin('venders', 'venders.id', '=', 'general_ledger.user_id')
            ->where('general_ledger.created_by', \Auth::user()->creatorId())
            ->where('general_ledger.user_type', 'vendor')
            ->where(function ($query) {
                $query->where('general_ledger.type', 'LIKE', '%BILL%')
                    ->orWhere('general_ledger.type', 'LIKE', '%Vendor%')
                    ->orWhere('general_ledger.reference', 'LIKE', '%Payment%')
                    ->orWhere('general_ledger.reference', 'LIKE', '%Expense%')
                    ->orWhere('general_ledger.reference', 'LIKE', '%Bill%')
                    ->orWhere('general_ledger.type', 'LIKE', '%Debit Note%');
            })
            ->whereBetween('general_ledger.send_date', [$start, $end])
            ->whereNotNull('venders.name')
            ->groupBy('venders.id', 'venders.name')
            ->havingRaw('(SUM(general_ledger.debit) - SUM(general_ledger.credit)) != 0')
            ->get()
            ->toArray();

        $payableSummariesBill = Bill::select('venders.name')
            ->selectRaw('(bills.bill_id) as bill')
            ->selectRaw('(bills.type) as type')
            ->selectRaw("CASE WHEN bills.type = 'Expense' THEN (sum((bill_products.price * bill_products.quantity) - bill_products.discount) + COALESCE(sum(bill_accounts.price), 0) + (SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM bill_products LEFT JOIN taxes ON FIND_IN_SET(taxes.id, bill_products.tax) > 0 WHERE bill_products.bill_id = bills.id)) ELSE (sum((bill_products.price * bill_products.quantity) - bill_products.discount) + COALESCE(sum(bill_accounts.price), 0)) END as price")
            ->selectRaw('sum((bill_payments.amount)) as pay_price')
            ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM bill_products
         LEFT JOIN taxes ON FIND_IN_SET(taxes.id, bill_products.tax) > 0
         WHERE bill_products.bill_id = bills.id) as total_tax')
            ->selectRaw('bills.bill_date as bill_date')
            ->selectRaw('bills.status as status')
            ->leftJoin('venders', 'venders.id', 'bills.vender_id')
            ->leftJoin('bill_payments', 'bill_payments.bill_id', 'bills.id')
            ->leftJoin('bill_products', 'bill_products.bill_id', 'bills.id')
            ->leftJoin('bill_accounts', 'bill_accounts.ref_id', 'bills.id')
            ->where('bills.created_by', \Auth::user()->creatorId())
            ->whereNotIn('bills.user_type', ['employee', 'customer'])
            ->where('bills.bill_date', '>=', $start)
            ->where('bills.bill_date', '<=', $end)
            ->groupBy('bills.id')
            ->get()
            ->toArray();

        $payableSummariesDebit = DebitNote::select('venders.name')
            ->selectRaw('null as bill')
            ->selectRaw('debit_notes.amount as price')
            ->selectRaw('0 as pay_price')
            ->selectRaw('0 as total_tax')
            ->selectRaw('debit_notes.date as bill_date')
            ->selectRaw('5 as status')
            ->leftJoin('venders', 'venders.id', 'debit_notes.vendor')
            ->leftJoin('bill_products', 'bill_products.bill_id', 'debit_notes.bill')
            ->leftJoin('bills', 'bills.id', 'debit_notes.bill')
            ->where('bills.created_by', \Auth::user()->creatorId())
            ->where('debit_notes.date', '>=', $start)
            ->where('debit_notes.date', '<=', $end)
            ->groupBy('debit_notes.id')
            ->get()
            ->toArray();

        $payableSummaries = (array_merge($payableSummariesDebit, $payableSummariesBill));

        $payableDetailsBill = Bill::select('venders.name')
            ->selectRaw('(bills.bill_id) as bill')
            ->selectRaw('(bills.type) as type')
            ->selectRaw('sum(bill_products.price) + COALESCE(sum(bill_accounts.price), 0) as price')
            ->selectRaw('(bill_products.quantity) as quantity')
            ->selectRaw('(product_services.name) as product_name')
            ->selectRaw('bills.bill_date as bill_date')
            ->selectRaw('bills.status as status')
            ->leftJoin('venders', 'venders.id', 'bills.vender_id')
            ->leftJoin('bill_products', 'bill_products.bill_id', 'bills.id')
            ->leftJoin('product_services', 'product_services.id', 'bill_products.product_id')
            ->leftJoin('bill_accounts', 'bill_accounts.ref_id', 'bills.id')
            ->where('bills.created_by', \Auth::user()->creatorId())
            ->whereNotIn('bills.user_type', ['employee', 'customer'])
            ->where('bills.bill_date', '>=', $start)
            ->where('bills.bill_date', '<=', $end)
            ->groupBy('bills.bill_id', 'product_services.name')
            ->get()
            ->toArray();

        $payableDetailsDebit = DebitNote::select('venders.name')
            ->selectRaw('null as bill')
            ->selectRaw('(debit_notes.id) as bills')
            ->selectRaw('(debit_notes.amount) as price')
            ->selectRaw('(product_services.name) as product_name')
            ->selectRaw('debit_notes.date as bill_date')
            ->selectRaw('5 as status')
            ->leftJoin('venders', 'venders.id', 'debit_notes.vendor')
            ->leftJoin('bill_products', 'bill_products.bill_id', 'debit_notes.bill')
            ->leftJoin('product_services', 'product_services.id', 'bill_products.product_id')
            ->leftJoin('bill_accounts', 'bill_accounts.ref_id', 'bills.id')
            ->leftJoin('bills', 'bills.id', 'debit_notes.bill')
            ->where('bills.created_by', \Auth::user()->creatorId())
            ->where('debit_notes.date', '>=', $start)
            ->where('debit_notes.date', '<=', $end)
            ->groupBy('debit_notes.id', 'product_services.name')
            ->get()
            ->toArray();

        $mergedArray = [];
        foreach ($payableDetailsDebit as $item) {
            $invoices = $item["bills"];

            if (!isset($mergedArray[$invoices])) {
                $mergedArray[$invoices] = [
                    "name" => $item["name"],
                    "bill" => $item["bill"],
                    "bills" => $invoices,
                    "price" => $item["price"],
                    "quantity" => 0,
                    "product_name" => "",
                    "bill_date" => "",
                    "status" => 0,
                ];
            }

            if (!strstr($mergedArray[$invoices]["product_name"], $item["product_name"])) {
                if ($mergedArray[$invoices]["product_name"] !== "") {
                    $mergedArray[$invoices]["product_name"] .= ", ";
                }
                $mergedArray[$invoices]["product_name"] .= $item["product_name"];
            }

            $mergedArray[$invoices]["bill_date"] = $item["bill_date"];
            $mergedArray[$invoices]["status"] = $item["status"];
        }

        $payableDetailsDebits = array_values($mergedArray);

        $payableDetails = (array_merge($payableDetailsBill, $payableDetailsDebits));

        $filter['startDateRange'] = $start;
        $filter['endDateRange'] = $end;
        $reportName = $request->report;

        return view('report.payable_report_receipt', compact('filter', 'reportName', 'payableVendors', 'payableSummaries', 'payableDetails'));
    }


    public function GledgerSummary(Request $request, $account = '')
    {
        try {

            if (\Auth::user()->can('ledger report')) {
                if (!empty($request->start_date) && !empty($request->end_date)) {
                    $start = $request->start_date;
                    $end = $request->end_date;

                    if (!empty($request->account_id)) {
                        $chart_accounts = ChartOfAccount::where('id', $request->account_id)
                            ->where('created_by', \Auth::user()->creatorId())
                            ->get();
                        $accounts = $chart_accounts->pluck('name', 'id');
                        $accountIds = $chart_accounts->pluck('id');
                        $generalLedgerData = GeneralLedger::selectRaw('vid, account, ref_id , type,user_id, SUM(credit) as total_credit, SUM(debit) as total_debit ,created_at,updated_at,send_date,deleted_qty,sub_product_id,user_type,reference,ref_number,payment_id')
                            ->whereIn('account', $accountIds)
                            ->whereBetween('send_date', [$start, $end])
                            ->where('created_by', \Auth::user()->creatorId())
                            ->groupBy('vid', 'account')
                            ->orderBy('general_ledger.send_date', 'ASC')
                            ->orderBy('general_ledger.vid', 'ASC')
                            ->get();
                        // GeneralLedger::whereIn('account', $accountIds)
                        //     ->whereBetween('created_at', [$start, $end])
                        //     ->get();
                        $chart_accounts = ChartOfAccount::where('created_by', \Auth::user()->creatorId())->get();
                        $accounts = $chart_accounts->pluck('name', 'id');
                    } else {
                        $chart_accounts = ChartOfAccount::where('created_by', \Auth::user()->creatorId())->get();
                        $accounts = $chart_accounts->pluck('name', 'id');
                        $generalLedgerData = GeneralLedger::selectRaw('vid, account, ref_id , type,user_id, SUM(credit) as total_credit, SUM(debit) as total_debit ,created_at,updated_at,send_date,deleted_qty,sub_product_id,user_type,reference,ref_number,payment_id')
                            ->whereBetween('send_date', [$start, $end])
                            ->where('created_by', \Auth::user()->creatorId())
                            ->groupBy('vid', 'account')
                            ->orderBy('general_ledger.send_date', 'ASC')
                            ->orderBy('general_ledger.vid', 'ASC')
                            ->get();

                        // GeneralLedger::whereBetween('created_at', [$start, $end])->get();
                    }
                } elseif (!empty($request->account_id)) {
                    $start = date('Y-m-01');
                    $end = date('Y-m-t');

                    $chart_accounts = ChartOfAccount::where('id', $request->account_id)
                        ->where('created_by', \Auth::user()->creatorId())
                        ->get();
                    $accounts = $chart_accounts->pluck('name', 'id');
                    $accountIds = $chart_accounts->pluck('id');
                    $generalLedgerData = GeneralLedger::selectRaw('vid, account, ref_id , type,user_id, SUM(credit) as total_credit, SUM(debit) as total_debit ,created_at,updated_at,send_date,deleted_qty,sub_product_id,user_type,reference,ref_number,payment_id')
                        ->whereIn('account', $accountIds)
                        ->where('created_by', \Auth::user()->creatorId())
                        ->groupBy('vid', 'account')
                        ->orderBy('general_ledger.send_date', 'ASC')
                        ->orderBy('general_ledger.vid', 'ASC')
                        ->get();

                    // GeneralLedger::whereIn('account', $accountIds)->get();
                } else {
                    $start = date('Y-m-01');
                    $end = date('Y-m-t');
                    $chart_accounts = ChartOfAccount::where('created_by', \Auth::user()->creatorId())->get();
                    $accounts = $chart_accounts->pluck('name', 'id');
                    $generalLedgerData = GeneralLedger::selectRaw('vid, account, ref_id , type,user_id, SUM(credit) as total_credit, SUM(debit) as total_debit ,created_at,updated_at,send_date,deleted_qty,sub_product_id,user_type,reference,ref_number,payment_id')
                        ->where('created_by', \Auth::user()->creatorId())
                        ->whereBetween('send_date', [$start, $end])
                        ->groupBy('vid', 'account')
                        ->orderBy('general_ledger.send_date', 'ASC')
                        ->orderBy('general_ledger.vid', 'ASC')
                        ->get();
                    //  GeneralLedger::all();
                }


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
            // Log the error for debugging
            // Log::error('Error fetching general ledger data: ' . $e->getMessage());

            // Return a user-friendly error message
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Shared data for customer statement screen and PDF export.
     *
     * @return array{reportData: array, filter: array, previousBalance: float|int}
     */
    protected function prepareCustomerStatementReportData(Request $request): array
    {
        $creatorId = Auth::user()->creatorId();

        $filter['account'] = __('All');
        $customerId = $request->customer;

        if ($customerId === null) {
            $customerRecord = Customer::where('created_by', '=', $creatorId)->first();
            $customerId = $customerRecord ? $customerRecord->id : null;
        }

        $filter['customer'] = $customerId;

        $arAccount = ChartOfAccount::where('created_by', $creatorId)->where('name', '=', 'Account Receivables')->first();
        if (!$arAccount) {
            if (!empty($request->start_month) && !empty($request->end_month)) {
                $start = strtotime($request->start_month);
                $end = strtotime($request->end_month);
            } else {
                $start = strtotime(date('Y-m'));
                $end = strtotime(date('Y-m', strtotime('-5 month')));
            }
            $filter['startDateRange'] = date('M-Y', $start);
            $filter['endDateRange'] = date('M-Y', $end);
            $reportData = ['general_ledger' => collect()];

            return [
                'reportData' => $reportData,
                'filter' => $filter,
                'previousBalance' => 0,
            ];
        }

        if (isset($request->customer)) {
            $general_ledger = GeneralLedger::where('general_ledger.created_by', '=', $creatorId)->where('general_ledger.user_id', intval($request->customer))
                ->leftJoin('customers', function ($join) use ($creatorId) {
                    $join->on('customers.id', '=', 'general_ledger.user_id')
                        ->where('customers.created_by', '=', $creatorId);
                })
                ->where('general_ledger.account', $arAccount->id)
                ->selectRaw('general_ledger.vid, general_ledger.account, general_ledger.ref_id , general_ledger.type, general_ledger.user_id, SUM(general_ledger.credit) as total_credit, SUM(general_ledger.debit) as total_debit , general_ledger.created_at, general_ledger.updated_at, general_ledger.send_date, general_ledger.reference, general_ledger.payment_id, general_ledger.ref_number')
                ->groupBy('general_ledger.vid')
                ->orderBy('general_ledger.id', 'ASC');
        } else {
            $general_ledger = GeneralLedger::where('general_ledger.created_by', '=', $creatorId)->where('general_ledger.user_id', $filter['customer'])
                ->leftJoin('customers', function ($join) use ($creatorId) {
                    $join->on('customers.id', '=', 'general_ledger.user_id')
                        ->where('customers.created_by', '=', $creatorId);
                })
                ->where('general_ledger.account', $arAccount->id)
                ->selectRaw('general_ledger.vid, general_ledger.account, general_ledger.ref_id , general_ledger.type, general_ledger.user_id, SUM(general_ledger.credit) as total_credit, SUM(general_ledger.debit) as total_debit , general_ledger.created_at, general_ledger.updated_at, general_ledger.send_date, general_ledger.reference, general_ledger.payment_id, general_ledger.ref_number')
                ->groupBy('general_ledger.vid')
                ->orderBy('general_ledger.id', 'ASC');
        }

        if (!empty($request->start_month) && !empty($request->end_month)) {
            $start = new DateTime($request->start_month);
            $end = new DateTime($request->end_month);
            $end->modify('last day of this month');
        } else {
            $start = new DateTime('first day of 6 months ago');
            $end = new DateTime('last day of this month');
        }

        $general_ledger->where(function ($query) use ($start, $end) {
            $query->whereBetween('send_date', [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]);
        })
            ->where('general_ledger.created_by', $creatorId);

        if (!empty($request->account) && $request->account != '') {
            $data_account = $request->account;
            $general_ledger->where(
                function ($query) use ($data_account, $creatorId) {
                    $query->where('account', $data_account);
                    $query->where('general_ledger.created_by', '=', $creatorId);
                }
            );

            $bankAccount = ChartOfAccount::where('id', $request->account)->where('created_by', $creatorId)->first();
            $filter['account'] = !empty($bankAccount) ? $bankAccount->code . ' - ' . $bankAccount->name : '';
        }
        $reportData['general_ledger'] = $general_ledger->get();
        if (!empty($request->start_month) && !empty($request->end_month)) {
            $start = strtotime($request->start_month);
            $end = strtotime($request->end_month);
        } else {
            $start = strtotime(date('Y-m'));
            $end = strtotime(date('Y-m', strtotime('-5 month')));
        }
        $filter['startDateRange'] = date('M-Y', $start);
        $filter['endDateRange'] = date('M-Y', $end);
        $startDate = Carbon::createFromFormat('M-Y', $filter['startDateRange']);

        $previousTransactions = GeneralLedger::where('general_ledger.created_by', '=', $creatorId)
            ->where('general_ledger.user_id', $filter['customer'])
            ->leftJoin('customers', function ($join) use ($creatorId) {
                $join->on('customers.id', '=', 'general_ledger.user_id')
                    ->where('customers.created_by', '=', $creatorId);
            })
            ->whereRaw("DATE_FORMAT(general_ledger.send_date, '%Y-%m') < ?", [$startDate->format('Y-m')])
            ->where('general_ledger.account', $arAccount->id)
            ->selectRaw('SUM(general_ledger.debit) as total_debit, SUM(general_ledger.credit) as total_credit')
            ->first();
        $previousBalance = $previousTransactions->total_debit - $previousTransactions->total_credit;

        return [
            'reportData' => $reportData,
            'filter' => $filter,
            'previousBalance' => $previousBalance,
        ];
    }

    /**
     * Resolve logo for DomPDF: local file path when stored under public/documents, else remote URL (invoice logo / storage).
     */
    protected function resolveCompanyLogoUrlForPdf(int $creatorId): string
    {
        $settings = Utility::settings();
        $settingsTenant = Utility::settingsById($creatorId);
        if (!is_array($settingsTenant)) {
            $settingsTenant = [];
        }
        $settings = array_merge($settings, $settingsTenant);

        $invoice_logo = $settingsTenant['invoice_logo'] ?? '';
        if (!empty($invoice_logo)) {
            return Utility::get_file('invoice_logo/') . $invoice_logo;
        }

        $company_logo = $settings['company_logo_dark'] ?? '';
        $company_logos = $settings['company_logo_light'] ?? '';
        if (($settings['cust_darklayout'] ?? '') == 'on') {
            $file = !empty($company_logos) ? $company_logos : 'logo-dark.png';
        } else {
            $file = !empty($company_logo) ? $company_logo : 'logo-dark.png';
        }

        $local = public_path('documents' . DIRECTORY_SEPARATOR . $file);
        if (is_file($local)) {
            return str_replace('\\', '/', $local);
        }

        return url('documents/' . $file);
    }

    public function customerStatementPdf(Request $request)
    {
        if (!Auth::user()->can('statement report')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $payload = $this->prepareCustomerStatementReportData($request);
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $settings = Utility::settings();
        $settingsTenant = Utility::settingsById($creatorId);
        if (is_array($settingsTenant)) {
            $settings = array_merge($settings, $settingsTenant);
        }

        $color = '#' . ($settings['invoice_color'] ?? 'ffffff');
        $font_color = Utility::getFontColor($color);
        $logoUrl = $this->resolveCompanyLogoUrlForPdf($creatorId);

        $filter = $payload['filter'];
        $slug = preg_replace('/[^a-zA-Z0-9_-]+/', '_', ($filter['startDateRange'] ?? '') . '_' . ($filter['endDateRange'] ?? ''));
        $filename = 'customer_statement_' . $slug . '.pdf';

        return Pdf::loadView('report.statement_report_customer_pdf', array_merge($payload, compact('settings', 'font_color', 'color', 'logoUrl', 'user')))
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    public function customerStatement(Request $request)
    {
        if (\Auth::user()->can('statement report')) {
            $chart_accounts = ChartOfAccount::where('created_by', \Auth::user()->creatorId())->get();
            $account = $chart_accounts->pluck('name', 'id');
            $account->prepend('Select Account', '');
            $customer = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customer->prepend('Select Customer', '');

            $payload = $this->prepareCustomerStatementReportData($request);

            return view('report.statement_report_customer', array_merge($payload, compact('account', 'customer')));
        }

        return redirect()->back()->with('error', __('Permission Denied.'));
    }

    public function vendorStatement(Request $request)
    {

        if (\Auth::user()->can('statement report')) {

            $filter['account'] = __('All');
            $vendor = $request->vendor;

            if ($vendor === null) {
                $vendor = Vender::where('created_by', '=', \Auth::user()->creatorId())->first();
                $vendor = $vendor ? $vendor->id : null;
            }

            $filter['vendor'] = $vendor;


            $chart_accounts = ChartOfAccount::where('created_by', \Auth::user()->creatorId())->get();
            $account = $chart_accounts->pluck('name', 'id');
            $account->prepend('Select Account', '');
            $vendor = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $vendor->prepend('Select Vendor', '');
            if (isset($request->vendor)) {
                $general_ledger = GeneralLedger::where('general_ledger.created_by', \Auth::user()->creatorId())
                    ->where('general_ledger.user_id', intval($request->vendor))
                    ->leftJoin('venders', function($join) {
                        $join->on('venders.id', '=', 'general_ledger.user_id')
                             ->where('venders.created_by', '=', \Auth::user()->creatorId());
                    })
                    ->where('general_ledger.account',ChartOfAccount::where('created_by', \Auth::user()->creatorId())->where('name', '=', 'Account Payable')->first()->id)
                    // ->where(function ($query) {
                    //     $query->where('general_ledger.type', 'LIKE', '%BILL%')
                    //         ->orWhere('general_ledger.type', 'LIKE', '%Vendor%')
                    //         ->orWhere('general_ledger.reference', 'LIKE', 'Payment')
                    //         ->orWhere('general_ledger.reference', 'LIKE', 'Delete Payment')
                    //         ->orWhere('general_ledger.reference', 'LIKE', '%opening balance%')
                    //         ->orWhere('general_ledger.user_type', 'LIKE', '%vendor%')
                    //         ->orWhere('general_ledger.type', 'LIKE', '%Debit Note%');
                    // })
                    ->selectRaw('general_ledger.vid, general_ledger.account, general_ledger.ref_id , general_ledger.type, general_ledger.user_id, SUM(general_ledger.credit) as total_credit, SUM(general_ledger.debit) as total_debit , general_ledger.created_at, general_ledger.updated_at, general_ledger.send_date, general_ledger.reference, general_ledger.payment_id, general_ledger.ref_number')
                    ->groupBy('general_ledger.vid')
                    ->orderBy('general_ledger.id', 'ASC');
            } else {
                $general_ledger = GeneralLedger::where('general_ledger.created_by', \Auth::user()->creatorId())
                    ->where('general_ledger.user_id', $filter['vendor'])
                    ->leftJoin('venders', function($join) {
                        $join->on('venders.id', '=', 'general_ledger.user_id')
                             ->where('venders.created_by', '=', \Auth::user()->creatorId());
                    })
                    ->where('general_ledger.account',ChartOfAccount::where('created_by', \Auth::user()->creatorId())->where('name', '=', 'Account Payable')->first()->id)
                    // ->where(function ($query) {
                    //     $query->where('general_ledger.type', 'LIKE', '%BILL%')
                    //         ->orWhere('general_ledger.type', 'LIKE', '%Vendor%')
                    //         ->orWhere('general_ledger.reference', 'LIKE', 'Payment')
                    //         ->orWhere('general_ledger.reference', 'LIKE', 'Delete Payment')
                    //         ->orWhere('general_ledger.reference', 'LIKE', '%opening balance%')
                    //         ->orWhere('general_ledger.user_type', 'LIKE', '%vendor%')
                    //         ->orWhere('general_ledger.type', 'LIKE', '%Debit Note%');
                    // })
                    ->selectRaw('general_ledger.vid, general_ledger.account, general_ledger.ref_id , general_ledger.type, general_ledger.user_id, SUM(general_ledger.credit) as total_credit, SUM(general_ledger.debit) as total_debit , general_ledger.created_at, general_ledger.updated_at, general_ledger.send_date, general_ledger.reference, general_ledger.payment_id, general_ledger.ref_number')
                    ->groupBy('general_ledger.vid')
                    ->orderBy('general_ledger.id', 'ASC');
            }


            if (!empty($request->start_month) && !empty($request->end_month)) {
                $start = new DateTime($request->start_month);
                $end = new DateTime($request->end_month);
                $end->modify('last day of this month');
            } else {
                $start = new DateTime('first day of 6 months ago');
                $end = new DateTime('last day of this month');
            }

            $general_ledger->where(function ($query) use ($start, $end) {
                $query->whereBetween('send_date', [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]);
            })
                ->where('general_ledger.created_by', \Auth::user()->creatorId());

            if (!empty($request->account) && $request->account != '') {
                $data_account = $request->account;
                $general_ledger->where(
                    function ($query) use ($data_account) {
                        $query->where('account', $data_account);
                        $query->where('general_ledger.created_by', '=', \Auth::user()->creatorId());
                    }
                );

                $bankAccount = ChartOfAccount::find($request->account);
                $filter['account'] = !empty($bankAccount) ? $bankAccount->code . ' - ' . $bankAccount->name : '';
            }
            $reportData['general_ledger'] = $general_ledger->get();
            if (!empty($request->start_month) && !empty($request->end_month)) {
                $start = strtotime($request->start_month);
                $end = strtotime($request->end_month);
            } else {
                $start = strtotime(date('Y-m'));
                $end = strtotime(date('Y-m', strtotime("-5 month")));
            }
            $filter['startDateRange'] = date('M-Y', $start);
            $filter['endDateRange'] = date('M-Y', $end);
            // Convert 'M-Y' format to Carbon instance
            $startDate = \Carbon\Carbon::createFromFormat('M-Y', $filter['startDateRange']);

            // Get the end of the previous month
            $endDate = $startDate->copy()->subMonth()->endOfMonth();
            $previousTransactions = GeneralLedger::where('general_ledger.created_by', '=', \Auth::user()->creatorId())
                ->where('general_ledger.user_id', $filter['vendor'])
                ->leftJoin('venders', function($join) {
                    $join->on('venders.id', '=', 'general_ledger.user_id')
                         ->where('venders.created_by', '=', \Auth::user()->creatorId());
                })
                ->whereRaw("DATE_FORMAT(general_ledger.send_date, '%Y-%m') < ?", [$startDate->format('Y-m')])
                ->where('general_ledger.account',ChartOfAccount::where('created_by', \Auth::user()->creatorId())->where('name', '=', 'Account Payable')->first()->id)
                // ->where(function ($query) {
                //     $query->where('general_ledger.type', 'LIKE', '%BILL%')
                //         ->orWhere('general_ledger.type', 'LIKE', '%Vendor%')
                //         ->orWhere('general_ledger.reference', 'LIKE', 'Payment')
                //         ->orWhere('general_ledger.reference', 'LIKE', 'Delete Payment')
                //         ->orWhere('general_ledger.reference', 'LIKE', '%opening balance%')
                //         ->orWhere('general_ledger.user_type', 'LIKE', '%vendor%')
                //         ->orWhere('general_ledger.type', 'LIKE', '%Debit Note%');
                // })
                ->selectRaw('SUM(general_ledger.debit) as total_debit, SUM(general_ledger.credit) as total_credit')
                ->first();
            $previousBalance = $previousTransactions->total_debit - $previousTransactions->total_credit;

            return view('report.statement_report_vendor', compact('reportData', 'account', 'vendor', 'filter', 'previousBalance'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function EmployeeStatement(Request $request)
    {

        if (\Auth::user()->can('statement report')) {

            $filter['account'] = __('All');
            $employee = $request->employee;

            if ($employee === null) {
                $employeeRecord = Vender::where('created_by', '=', \Auth::user()->creatorId())->first();
                $employee = $employeeRecord ? $employeeRecord->id : null;
            }

            $filter['employee'] = $employee;


            $chart_accounts = ChartOfAccount::where('created_by', \Auth::user()->creatorId())->get();
            $account = $chart_accounts->pluck('name', 'id');
            $account->prepend('Select Account', '');
            $employee = Employee::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $employee->prepend('Select Employee', '');
            if (isset($request->employee)) {
                $general_ledger = GeneralLedger::where('created_by', \Auth::user()->creatorId())
                    ->where('user_id', intval($request->employee))
                    ->where(function ($query) {
                        $query->where('type', 'LIKE', '%EXP%')
                            ->Where('user_type', 'LIKE', '%employee%')
                            ->orWhere('type', 'LIKE', '%Employee%')
                            ->orWhere('type', 'LIKE', '%Debit Note%');
                    })
                    ->selectRaw('vid, account, ref_id , type,user_id, SUM(credit) as total_credit, SUM(debit) as total_debit ,created_at,updated_at,send_date')
                    ->groupBy('vid')
                    ->orderBy('id', 'ASC');
            } else {
                $general_ledger = GeneralLedger::where('created_by', \Auth::user()->creatorId())
                    ->where('user_id', $filter['employee'])
                    ->where(function ($query) {
                        $query->where('type', 'LIKE', '%EXP%')
                            ->Where('user_type', 'LIKE', '%employee%')
                            ->orWhere('type', 'LIKE', '%Employee%')
                            ->orWhere('type', 'LIKE', '%Debit Note%');
                    })
                    ->selectRaw('vid, account, ref_id , type,user_id, SUM(credit) as total_credit, SUM(debit) as total_debit ,created_at,updated_at,send_date')
                    ->groupBy('vid')
                    ->orderBy('id', 'ASC');
            }


            if (!empty($request->start_month) && !empty($request->end_month)) {
                $start = new DateTime($request->start_month);
                $end = new DateTime($request->end_month);
                $end->modify('last day of this month');
            } else {
                $start = new DateTime('first day of 6 months ago');
                $end = new DateTime('last day of this month');
            }

            $general_ledger->where(function ($query) use ($start, $end) {
                $query->whereBetween('send_date', [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]);
            })
                ->where('general_ledger.created_by', \Auth::user()->creatorId());

            if (!empty($request->account) && $request->account != '') {
                $data_account = $request->account;
                $general_ledger->where(
                    function ($query) use ($data_account) {
                        $query->where('account', $data_account);
                        $query->where('general_ledger.created_by', '=', \Auth::user()->creatorId());
                    }
                );

                $bankAccount = ChartOfAccount::find($request->account);
                $filter['account'] = !empty($bankAccount) ? $bankAccount->code . ' - ' . $bankAccount->name : '';
            }
            $reportData['general_ledger'] = $general_ledger->get();
            if (!empty($request->start_month) && !empty($request->end_month)) {
                $start = strtotime($request->start_month);
                $end = strtotime($request->end_month);
            } else {
                $start = strtotime(date('Y-m'));
                $end = strtotime(date('Y-m', strtotime("-5 month")));
            }
            $filter['startDateRange'] = date('M-Y', $start);
            $filter['endDateRange'] = date('M-Y', $end);
            // Convert 'M-Y' format to Carbon instance
            $startDate = \Carbon\Carbon::createFromFormat('M-Y', $filter['startDateRange']);

            // Get the end of the previous month
            $endDate = $startDate->copy()->subMonth()->endOfMonth();
            $previousTransactions = GeneralLedger::where('created_by', '=', \Auth::user()->creatorId())
                ->where('user_id', $filter['employee'])
                ->whereRaw("DATE_FORMAT(send_date, '%Y-%m') < ?", [$startDate->format('Y-m')])
                ->where(function ($query) {
                    $query->where('type', 'LIKE', '%EXP%')
                        ->Where('user_type', 'LIKE', '%employee%')
                        ->orWhere('type', 'LIKE', '%Employee%')
                        ->orWhere('type', 'LIKE', '%Debit Note%');
                })
                ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->first();
            $previousBalance = $previousTransactions->total_debit - $previousTransactions->total_credit;

            return view('report.statement_report_employee', compact('reportData', 'account', 'employee', 'filter', 'previousBalance'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function ledgerSummaryExport(Request $request)
    {
        if (\Auth::user()->can('ledger report')) {
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $accountId = $request->get('account');

            // Set default dates if not provided
            if (empty($startDate)) {
                $startDate = date('Y-m-01');
            }
            if (empty($endDate)) {
                $endDate = date('Y-m-t');
            }

            $name = 'ledger_summary_' . date('Y-m-d_H-i-s');

            return Excel::download(new LedgerSummaryExport($startDate, $endDate, $accountId), $name . '.xlsx');
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function Gledgerexport(Request $request)
    {
        if (\Auth::user()->can('ledger report')) {
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $account = $request->get('account');

            // Set default dates if not provided
            if (empty($startDate)) {
                $startDate = date('Y-m-01');
            }
            if (empty($endDate)) {
                $endDate = date('Y-m-t');
            }

            $name = 'general_ledger_' . date('Y-m-d_H-i-s');

            return Excel::download(new GledgerExport($startDate, $endDate, $account), $name . '.xlsx');
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function attendanceReport(Request $request)
    {
        $employee = Employee::where('user_id', '=', Auth::user()->id)->first();
        $attendances = AttendanceEmployee::where('employee_id', $employee->id)
            ->whereBetween('date', [$request->start_date, $request->end_date])
            ->get();

        return response()->json($attendances, 200);
    }
    public function getAllEmployeeAttendance(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        // Ensure that only admins can access this endpoint
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }

    /**
     * Company Tax Report - Based on General Ledger
     *
     * For each tax chart of account:
     *  - Total Tax         = SUM(debit) - SUM(credit) from general_ledger for that account (company, date range)
     *  - Total Without Tax = Total Tax / (tax rate / 100)
     *
     * Output (Credit Side): taxes where Total Tax < 0 (net credit)
     * Input (Debit Side):   taxes where Total Tax > 0 (net debit)
     */
    public function companyTaxReport(Request $request)
    {
        if (!\Auth::user()->can('bill report')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        // Date range (default: last 3 months)
        if (!empty($request->start_date) && !empty($request->end_date)) {
            $start = $request->start_date;
            $end   = $request->end_date;
        } else {
            $end   = date('Y-m-d');
            $start = date('Y-m-d', strtotime('-3 months'));
        }

        $filter['startDateRange'] = $start;
        $filter['endDateRange']   = $end;

        $createdBy = \Auth::user()->creatorId();

        // All taxes with chart accounts
        $taxes = Tax::where('created_by', $createdBy)
            ->whereNotNull('chart_account_id')
            ->with('chartAccount')
            ->get();

        $inputData  = [];
        $outputData = [];

        foreach ($taxes as $tax) {
            $taxName = $tax->name . ' (' . $tax->rate . '%)';

            // Total Tax = sum of debit - sum of credit from ledger for this tax chart account
            $ledgerSums = GeneralLedger::where('created_by', $createdBy)
                ->where('account', $tax->chart_account_id)
                ->whereBetween('send_date', [$start, $end])
                ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
                ->first();

            $totalTax = (float) ($ledgerSums->total_debit ?? 0) - (float) ($ledgerSums->total_credit ?? 0);
            $totalTax = round($totalTax, 2);

            // Total Without Tax = Total Tax / (tax rate / 100); avoid division by zero
            $rate = (float) $tax->rate;
            $totalWithoutTax = ($rate != 0) ? round($totalTax / ($rate / 100), 2) : 0;

            $baseRow = [
                'tax_name'           => $taxName,
                'tax_id'             => $tax->id,
                'tax_rate'           => $tax->rate,
                'chart_account_id'   => $tax->chart_account_id,
                'chart_account_name' => $tax->chartAccount ? $tax->chartAccount->name : '-',
                'chart_account_code' => $tax->chartAccount ? $tax->chartAccount->code : '-',
                'total_without_tax'  => $totalWithoutTax,
                'total_tax'          => $totalTax,
            ];

            // Output (Credit Side): net credit (total_tax < 0) — show as positive for display
            if ($totalTax < 0) {
                $inputData[$taxName] = [
                    'tax_name'           => $baseRow['tax_name'],
                    'tax_id'             => $baseRow['tax_id'],
                    'tax_rate'           => $baseRow['tax_rate'],
                    'chart_account_id'   => $baseRow['chart_account_id'],
                    'chart_account_name' => $baseRow['chart_account_name'],
                    'chart_account_code' => $baseRow['chart_account_code'],
                    'total_without_tax'  => abs($totalWithoutTax),
                    'total_tax'          => abs($totalTax),
                ];
            }

            // Input (Debit Side): net debit (total_tax > 0)
            if ($totalTax > 0) {
                $outputData[$taxName] = $baseRow;
            }
        }

        ksort($inputData);
        ksort($outputData);

        return view('report.company_tax_report', [
            'filter'     => $filter,
            'inputData'  => $inputData,
            'outputData' => $outputData,
        ]);
    }
}
