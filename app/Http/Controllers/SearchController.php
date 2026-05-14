<?php
namespace App\Http\Controllers;

use App\Models\Product; // Import relevant models
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('query');

        // Check if the query contains the word "product"
        if (strpos(strtolower($query), 'bill') !== false) {
            return redirect()->route('bill.index'); // Redirect to products index page
        } elseif (strpos(strtolower($query), 'invoice') !== false) {
            return redirect()->route('invoice.index'); // Redirect to users index page
        } else {
            // Handle other search queries or display a message
            return redirect()->back()->with('error', 'No results found for the query: ' . $query);
        }
    }

    public function autocomplete(Request $request)
    {
        $query = strtolower($request->input('query', ''));
        
        // Define menu items with their routes and labels (mirrors main menu)
        $menuItems = [
            // Dashboards / Overviews
            ['label' => __('Dashboard'), 'route' => 'dashboard', 'url' => route('dashboard')],
            ['label' => __('Stock Overview'), 'route' => 'stock.overview', 'url' => route('stock.overview')],
            ['label' => __('Sell Overview'), 'route' => 'sell.overview', 'url' => route('sell.overview')],
            ['label' => __('HRM Overview'), 'route' => 'hrm.dashboard', 'url' => route('hrm.dashboard')],
            ['label' => __('CRM Overview'), 'route' => 'crm.dashboard', 'url' => route('crm.dashboard')],
            ['label' => __('Project Dashboard'), 'route' => 'project.dashboard', 'url' => route('project.dashboard')],
            ['label' => __('POS Overview'), 'route' => 'pos.dashboard', 'url' => route('pos.dashboard')],

            // Core accounting
            ['label' => __('Bills'), 'route' => 'bill.index', 'url' => route('bill.index')],
            ['label' => __('Invoices'), 'route' => 'invoice.index', 'url' => route('invoice.index')],
            ['label' => __('Customers'), 'route' => 'customer.index', 'url' => route('customer.index')],
            ['label' => __('Vendors'), 'route' => 'vender.index', 'url' => route('vender.index')],
            ['label' => __('Products'), 'route' => 'productservice.index', 'url' => route('productservice.index')],
            ['label' => __('Payments'), 'route' => 'payment.index', 'url' => route('payment.index')],
            ['label' => __('Customer Payments'), 'route' => 'customerpayment.index', 'url' => route('customerpayment.index')],
            ['label' => __('Journal Entries'), 'route' => 'journal-entry.index', 'url' => route('journal-entry.index')],
            ['label' => __('Chart of Accounts'), 'route' => 'chart-of-account.index', 'url' => route('chart-of-account.index')],

            // Accounting reports
            ['label' => __('General Ledger'), 'route' => 'report.Gledger', 'url' => route('report.Gledger')],
            ['label' => __('Ledger Summary'), 'route' => 'report.ledger', 'url' => route('report.ledger')],
            ['label' => __('Account Statement'), 'route' => 'report.account.statement', 'url' => route('report.account.statement')],
            ['label' => __('Customer Statement'), 'route' => 'report.customer.statement', 'url' => route('report.customer.statement')],
            ['label' => __('Vendor Statement'), 'route' => 'report.vendor.statement', 'url' => route('report.vendor.statement')],
            ['label' => __('Employee Statement'), 'route' => 'report.employee.statement', 'url' => route('report.employee.statement')],
            ['label' => __('Invoice Summary'), 'route' => 'report.invoice.summary', 'url' => route('report.invoice.summary')],
            ['label' => __('Bill Summary'), 'route' => 'report.bill.summary', 'url' => route('report.bill.summary')],
            ['label' => __('Income Summary'), 'route' => 'report.income.summary', 'url' => route('report.income.summary')],
            ['label' => __('Expense Summary'), 'route' => 'report.expense.summary', 'url' => route('report.expense.summary')],
            ['label' => __('Income vs Expense Summary'), 'route' => 'report.income.vs.expense.summary', 'url' => route('report.income.vs.expense.summary')],
            ['label' => __('Tax Summary'), 'route' => 'report.tax.summary', 'url' => route('report.tax.summary')],
            ['label' => __('Receivables'), 'route' => 'report.receivables', 'url' => route('report.receivables')],
            ['label' => __('Payables'), 'route' => 'report.payables', 'url' => route('report.payables')],
            ['label' => __('Monthly Cash Flow'), 'route' => 'report.monthly.cashflow', 'url' => route('report.monthly.cashflow')],

            // Stock & product reports
            ['label' => __('Stock Report'), 'route' => 'subproduct.stock_report', 'url' => route('subproduct.stock_report')],
            ['label' => __('Sell Report'), 'route' => 'subproduct.sell_report', 'url' => route('subproduct.sell_report')],
            ['label' => __('Stock Movement Report'), 'route' => 'subproduct.stock_movement_report', 'url' => route('subproduct.stock_movement_report')],
            ['label' => __('Product Stock Report'), 'route' => 'report.product.stock.report', 'url' => route('report.product.stock.report')],
            ['label' => __('Item Master'), 'route' => 'report.item.master', 'url' => route('report.item.master')],
            ['label' => __('Warehouse Report'), 'route' => 'report.warehouse', 'url' => route('report.warehouse')],
            ['label' => __('Purchase Daily/Monthly Report'), 'route' => 'report.daily.purchase', 'url' => route('report.daily.purchase')],
            ['label' => __('POS Daily/Monthly Report'), 'route' => 'report.daily.pos', 'url' => route('report.daily.pos')],
            ['label' => __('POS vs Purchase Report'), 'route' => 'report.pos.vs.purchase', 'url' => route('report.pos.vs.purchase')],

            // Transactions
            ['label' => __('Transactions'), 'route' => 'transaction.index', 'url' => route('transaction.index')],

            // CRM / Sales pipeline
            ['label' => __('Leads'), 'route' => 'leads.list', 'url' => route('leads.list')],
            ['label' => __('Deals'), 'route' => 'deals.list', 'url' => route('deals.list')],
            ['label' => __('Lead Report'), 'route' => 'report.lead', 'url' => route('report.lead')],
            ['label' => __('Deal Report'), 'route' => 'report.deal', 'url' => route('report.deal')],

            // Projects & tasks
            ['label' => __('Projects'), 'route' => 'projects.index', 'url' => route('projects.index')],
            ['label' => __('Tasks'), 'route' => 'taskBoard.view', 'url' => route('taskBoard.view', 'list')],

            // HRM: employees, payroll, attendance, jobs, etc.
            ['label' => __('Employees'), 'route' => 'employee.index', 'url' => route('employee.index')],
            ['label' => __('Employee Payments'), 'route' => 'employeepayment.index', 'url' => route('employeepayment.index')],
            ['label' => __('Set Salary'), 'route' => 'setsalary.index', 'url' => route('setsalary.index')],
            ['label' => __('Payslips'), 'route' => 'payslip.index', 'url' => route('payslip.index')],
            ['label' => __('Manage Leave'), 'route' => 'leave.index', 'url' => route('leave.index')],
            ['label' => __('Manage Early Leave'), 'route' => 'earlyleave.index', 'url' => route('earlyleave.index')],
            ['label' => __('Mark Attendance'), 'route' => 'attendanceemployee.index', 'url' => route('attendanceemployee.index')],
            ['label' => __('Bulk Attendance'), 'route' => 'attendanceemployee.bulkattendance', 'url' => route('attendanceemployee.bulkattendance')],
            ['label' => __('Payroll Report'), 'route' => 'report.payroll', 'url' => route('report.payroll')],
            ['label' => __('Leave Report'), 'route' => 'report.leave', 'url' => route('report.leave')],
            ['label' => __('Monthly Attendance Report'), 'route' => 'report.monthly.attendance', 'url' => route('report.monthly.attendance')],
            ['label' => __('Indicators'), 'route' => 'indicator.index', 'url' => route('indicator.index')],
            ['label' => __('Appraisals'), 'route' => 'appraisal.index', 'url' => route('appraisal.index')],
            ['label' => __('Goal Tracking'), 'route' => 'goaltracking.index', 'url' => route('goaltracking.index')],
            ['label' => __('Trainings'), 'route' => 'training.index', 'url' => route('training.index')],
            ['label' => __('Trainers'), 'route' => 'trainer.index', 'url' => route('trainer.index')],
            ['label' => __('Jobs'), 'route' => 'job.index', 'url' => route('job.index')],
            ['label' => __('Job Create'), 'route' => 'job.create', 'url' => route('job.create')],
            ['label' => __('Job Applications'), 'route' => 'job-application.index', 'url' => route('job-application.index')],
            ['label' => __('Job Application Candidates'), 'route' => 'job.application.candidate', 'url' => route('job.application.candidate')],
            ['label' => __('Job On-boarding'), 'route' => 'job.on.board', 'url' => route('job.on.board')],
            ['label' => __('Custom Questions'), 'route' => 'custom-question.index', 'url' => route('custom-question.index')],
            ['label' => __('Interview Schedule'), 'route' => 'interview-schedule.index', 'url' => route('interview-schedule.index')],
            ['label' => __('Awards'), 'route' => 'award.index', 'url' => route('award.index')],
            ['label' => __('Transfers'), 'route' => 'transfer.index', 'url' => route('transfer.index')],
            ['label' => __('Resignations'), 'route' => 'resignation.index', 'url' => route('resignation.index')],
            ['label' => __('Trips'), 'route' => 'travel.index', 'url' => route('travel.index')],
            ['label' => __('Promotions'), 'route' => 'promotion.index', 'url' => route('promotion.index')],
            ['label' => __('Complaints'), 'route' => 'complaint.index', 'url' => route('complaint.index')],
            ['label' => __('Warnings'), 'route' => 'warning.index', 'url' => route('warning.index')],
            ['label' => __('Terminations'), 'route' => 'termination.index', 'url' => route('termination.index')],
            ['label' => __('Announcements'), 'route' => 'announcement.index', 'url' => route('announcement.index')],
            ['label' => __('Holidays'), 'route' => 'holiday.index', 'url' => route('holiday.index')],
            ['label' => __('Events'), 'route' => 'event.index', 'url' => route('event.index')],
            ['label' => __('Meetings'), 'route' => 'meeting.index', 'url' => route('meeting.index')],
            ['label' => __('Employees Asset'), 'route' => 'account-assets.index', 'url' => route('account-assets.index')],
            ['label' => __('Document Setup'), 'route' => 'document-upload.index', 'url' => route('document-upload.index')],
            ['label' => __('Company Policy'), 'route' => 'company-policy.index', 'url' => route('company-policy.index')],
            ['label' => __('HRM System Setup'), 'route' => 'branch.index', 'url' => route('branch.index')],

            // Banking
            ['label' => __('Bank Accounts'), 'route' => 'bank-account.index', 'url' => route('bank-account.index')],
            ['label' => __('Bank Transfers'), 'route' => 'bank-transfer.index', 'url' => route('bank-transfer.index')],

            // Sales & purchases (ERP)
            ['label' => __('Estimates'), 'route' => 'proposal.index', 'url' => route('proposal.index')],
            ['label' => __('Sale Orders'), 'route' => 'saleorder.index', 'url' => route('saleorder.index')],
            ['label' => __('Pick Lists'), 'route' => 'picklist.index', 'url' => route('picklist.index')],
            ['label' => __('Packing Lists'), 'route' => 'packinglist.index', 'url' => route('packinglist.index')],
            ['label' => __('Rent Invoices'), 'route' => 'rentinvoice.index', 'url' => route('rentinvoice.index')],
            ['label' => __('Revenue'), 'route' => 'revenue.index', 'url' => route('revenue.index')],
            ['label' => __('Customer Refunds'), 'route' => 'customerrefund.index', 'url' => route('customerrefund.index')],
            ['label' => __('Credit Notes'), 'route' => 'credit.note', 'url' => route('credit.note')],
            ['label' => __('Pricing Types'), 'route' => 'pricing-list-types.index', 'url' => route('pricing-list-types.index')],
            ['label' => __('Pricing Lists'), 'route' => 'pricing-lists.index', 'url' => route('pricing-lists.index')],
            ['label' => __('Quotations'), 'route' => 'quotations.index', 'url' => route('quotations.index')],
            ['label' => __('PRO'), 'route' => 'pro.index', 'url' => route('pro.index')],
            ['label' => __('ASN'), 'route' => 'asn.index', 'url' => route('asn.index')],
            ['label' => __('GRN'), 'route' => 'grn.index', 'url' => route('grn.index')],
            ['label' => __('Manufacturers'), 'route' => 'car_accessories.index', 'url' => route('car_accessories.index')],
            ['label' => __('Expenses'), 'route' => 'expense.index', 'url' => route('expense.index')],
            ['label' => __('Service Bill'), 'route' => 'simple-expense.index', 'url' => route('simple-expense.index')],
            ['label' => __('Service Bill Payments'), 'route' => 'simple-expense-payments.index', 'url' => route('simple-expense-payments.index')],
            ['label' => __('Direct Expense'), 'route' => 'direct_expenses.index', 'url' => route('direct_expenses.index')],

            // Users & profile
            ['label' => __('Users'), 'route' => 'users', 'url' => route('users')],
            ['label' => __('Profile'), 'route' => 'profile', 'url' => route('profile')],
        ];

        // Filter menu items based on query and user permissions
        $filtered = collect($menuItems)->filter(function ($item) use ($query) {
            $label = strtolower($item['label']);
            return empty($query) || strpos($label, $query) !== false;
        })->take(10)->values();

        return response()->json($filtered);
    }
}
