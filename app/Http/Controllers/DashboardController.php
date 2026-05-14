<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AttendanceEmployee;
use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\Bug;
use App\Models\BugStatus;
use App\Models\Contract;
use App\Models\Deal;
use App\Models\DealTask;
use App\Models\Employee;
use App\Models\Event;
use App\Models\Expense;
use App\Models\Goal;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\Meeting;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Pos;
use App\Models\ProductServiceCategory;
use App\Models\ProductServiceUnit;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Purchase;
use App\Models\Revenue;
use App\Models\Stage;
use App\Models\Tax;
use App\Models\Timesheet;
use App\Models\TimeTracker;
use App\Models\Trainer;
use App\Models\Training;
use App\Models\User;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }


    public function landingPage()
    {
        // if (!file_exists(storage_path() . "/installed")) {
        //     header('location:install');
        //     die;
        // }

        $adminSettings = Utility::settings();
        if ($adminSettings['display_landing_page'] == 'on' && \Schema::hasTable('landing_page_settings')) {

            return view('landingpage::layouts.landingpage' , compact('adminSettings'));

        } else {
            return redirect('login');
        }
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function account_dashboard_index()
    {

        if (Auth::check()) {

            if (Auth::user()->type == 'super admin') {
                return redirect()->route('client.dashboard.view');
            } elseif (Auth::user()->type == 'client') {
                return redirect()->route('client.dashboard.view');
            } else {
                // Check permission with error handling for cache write failures
                $hasPermission = false;
                try {
                    $hasPermission = \Auth::user()->can('show account dashboard');
                } catch (\Exception $e) {
                    // If cache write fails, check permission directly via roles without caching
                    try {
                        $hasPermission = \Auth::user()->hasPermissionTo('show account dashboard');
                    } catch (\Exception $e2) {
                        // Last resort: check if user has the permission via roles directly
                        $user = \Auth::user();
                        $hasPermission = $user->roles()->whereHas('permissions', function($q) {
                            $q->where('name', 'show account dashboard');
                        })->exists();
                    }
                }
                
                if ($hasPermission) {
                    $data['latestIncome'] = Revenue::with(['customer'])->where('created_by', '=', \Auth::user()->creatorId())->orderBy('id', 'desc')->limit(5)->get();
                    $data['latestExpense'] = Payment::with(['vender'])->where('created_by', '=', \Auth::user()->creatorId())->orderBy('id', 'desc')->limit(5)->get();
                    $currentYer = date('Y');

                    $incomeCategory = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())
                        ->where('type', '=', 'income')->get();

                    $inColor = array();
                    $inCategory = array();
                    $inAmount = array();
                    for ($i = 0; $i < count($incomeCategory); $i++) {
                        $inColor[] = $this->categoryChartPaletteColor($i);
                        $inCategory[] = $incomeCategory[$i]->name;
                        $inAmount[] = $incomeCategory[$i]->incomeCategoryRevenueAmount();
                    }

                    $data['incomeCategoryColor'] = $inColor;
                    $data['incomeCategory'] = $inCategory;
                    $data['incomeCatAmount'] = $inAmount;

                    $expenseCategory = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())
                        ->where('type', '=', 'expense')->get();
                    $exColor = array();
                    $exCategory = array();
                    $exAmount = array();
                    for ($i = 0; $i < count($expenseCategory); $i++) {
                        $exColor[] = $this->categoryChartPaletteColor($i);
                        $exCategory[] = $expenseCategory[$i]->name;
                        $exAmount[] = $expenseCategory[$i]->expenseCategoryAmount();
                    }

                    $data['expenseCategoryColor'] = $exColor;
                    $data['expenseCategory'] = $exCategory;
                    $data['expenseCatAmount'] = $exAmount;

                    $data['incExpBarChartData'] = \Auth::user()->getincExpBarChartData();
                    //                dd( $data['incExpBarChartData']);
                    $data['incExpLineChartData'] = \Auth::user()->getIncExpLineChartDate();

                    $data['currentYear'] = date('Y');
                    $data['currentMonth'] = date('M');

                    $constant['taxes'] = Tax::where('created_by', \Auth::user()->creatorId())->count();
                    $constant['category'] = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->count();
                    $constant['units'] = ProductServiceUnit::where('created_by', \Auth::user()->creatorId())->count();
                    $constant['bankAccount'] = BankAccount::where('created_by', \Auth::user()->creatorId())->count();
                    $data['constant'] = $constant;
                    $data['bankAccountDetail'] = BankAccount::where('bank_accounts.created_by', '=', \Auth::user()->creatorId())
                        ->leftJoin('general_ledger', function($join) {
                            $join->on('general_ledger.account', '=', 'bank_accounts.chart_account_id')
                                 ->where('general_ledger.created_by', '=', \Auth::user()->creatorId());
                        })
                        ->select('bank_accounts.*')
                        ->selectRaw('COALESCE(SUM(general_ledger.debit) - SUM(general_ledger.credit), 0) as calculated_balance')
                        ->groupBy('bank_accounts.id')
                        ->get();
                    $data['recentInvoice'] = Invoice::join('customers', 'invoices.customer_id', '=', 'customers.id')
                        ->where('invoices.created_by', '=', \Auth::user()->creatorId())
                        ->orderBy('invoices.id', 'desc')
                        ->limit(5)
                        ->select('invoices.*', 'customers.name as customer_name')
                        ->get();

                    $data['weeklyInvoice'] = \Auth::user()->weeklyInvoice();
                    $data['monthlyInvoice'] = \Auth::user()->monthlyInvoice();
                    $data['recentBill'] = Bill::with('vender:id,name')
                    ->where('created_by', \Auth::user()->creatorId())
                    ->orderBy('id', 'desc')
                    ->limit(5)
                    ->select([
                        'id',
                        'bill_id',
                        'vender_id',
                        'bill_date',
                        'due_date',
                        'status',
                        \DB::raw('(SELECT SUM(quantity * price) FROM bill_products WHERE bill_products.bill_id = bills.id) as total')
                    ])
                    ->get();

                    $data['weeklyBill'] = \Auth::user()->weeklyBill();
                    $data['monthlyBill'] = \Auth::user()->monthlyBill();
                    $data['goals'] = Goal::where('created_by', '=', \Auth::user()->creatorId())->where('is_display', 1)->get();

                    //Storage limit
                    $data['users'] = User::find(\Auth::user()->creatorId());
                    $data['plan'] = Plan::getPlan(\Auth::user()->show_dashboard());
                    if ($data['plan']->storage_limit > 0) {
                        $data['storage_limit'] = ($data['users']->storage_limit / $data['plan']->storage_limit) * 100;
                    } else {
                        $data['storage_limit'] = 0;
                    }

                    return view('dashboard.account-dashboard', $data);
                } else {
                    // User doesn't have "show account dashboard" permission
                    // Try to redirect to another dashboard they might have access to
                    if (\Auth::user()->can('show hrm dashboard')) {
                        return $this->hrm_dashboard_index();
                    } elseif (\Auth::user()->can('show crm dashboard')) {
                        return $this->crm_dashboard_index();
                    } elseif (\Auth::user()->can('show project dashboard')) {
                        return $this->project_dashboard_index();
                    } elseif (\Auth::user()->can('show pos dashboard')) {
                        return $this->pos_dashboard_index();
                    } else {
                        // No dashboard permissions - redirect to profile page instead of login to avoid redirect loop
                        return redirect()->route('profile')->with('error', __('You do not have permission to access any dashboard. Please contact your administrator to assign the necessary permissions.'));
                    }
                }

            }
        } else {

                return redirect('login');

            }
        }


    public function project_dashboard_index()
    {
        $user = Auth::user();

        if (\Auth::user()->can('show project dashboard')) {
            if ($user->type == 'admin') {
                return view('admin.dashboard');
            } else {
                $home_data = [];
//                dd($user->projects());

                $user_projects = $user->projects()->pluck('project_id')->toArray();

                $project_tasks = ProjectTask::whereIn('project_id', $user_projects)->get();
                $project_expense = Expense::whereIn('project_id', $user_projects)->get();
                $seven_days = Utility::getLastSevenDays();

                // Total Projects
                $complete_project = $user->projects()->where('status', 'LIKE', 'complete')->count();
                $home_data['total_project'] = [
                    'total' => count($user_projects),
                    'percentage' => Utility::getPercentage($complete_project, count($user_projects)),
                ];

                // Total Tasks
                $complete_task = ProjectTask::where('is_complete', '=', 1)->whereRaw("find_in_set('" . $user->id . "',assign_to)")->whereIn('project_id', $user_projects)->count();
                $home_data['total_task'] = [
                    'total' => $project_tasks->count(),
                    'percentage' => Utility::getPercentage($complete_task, $project_tasks->count()),
                ];

                // Total Expense
                $total_expense = 0;
                $total_project_amount = 0;
                foreach ($user->projects as $pr) {
                    $total_project_amount += $pr->budget;
                }
                foreach ($project_expense as $expense) {
                    $total_expense += $expense->amount;
                }
                $home_data['total_expense'] = [
                    'total' => $project_expense->count(),
                    'percentage' => Utility::getPercentage($total_expense, $total_project_amount),
                ];

                // Total Users
                $home_data['total_user'] = Auth::user()->contacts->count();

                // Tasks Overview Chart & Timesheet Log Chart
                $task_overview = [];
                $timesheet_logged = [];
                foreach ($seven_days as $date => $day) {
                    // Task
                    $task_overview[$day] = ProjectTask::where('is_complete', '=', 1)->where('marked_at', 'LIKE', $date)->whereIn('project_id', $user_projects)->count();

                    // Timesheet
                    $time = Timesheet::whereIn('project_id', $user_projects)->where('date', 'LIKE', $date)->pluck('time')->toArray();
                    $timesheet_logged[$day] = str_replace(':', '.', Utility::calculateTimesheetHours($time));
                }

                $home_data['task_overview'] = $task_overview;
                $home_data['timesheet_logged'] = $timesheet_logged;

                // Project Status
                $total_project = count($user_projects);

                $project_status = [];
                foreach (Project::$project_status as $k => $v) {

                    $project_status[$k]['total'] = $user->projects->where('status', 'LIKE', $k)->count();
//                    dd($project_status[$k]['total']    );
                    $project_status[$k]['percentage'] = Utility::getPercentage($project_status[$k]['total'], $total_project);
                }
                $home_data['project_status'] = $project_status;

                // Top Due Project
                $home_data['due_project'] = $user->projects()->orderBy('end_date', 'DESC')->limit(5)->get();

                // Top Due Tasks
                $home_data['due_tasks'] = ProjectTask::where('is_complete', '=', 0)->whereIn('project_id', $user_projects)->orderBy('end_date', 'DESC')->limit(5)->get();

                $home_data['last_tasks'] = ProjectTask::whereIn('project_id', $user_projects)->orderBy('end_date', 'DESC')->limit(5)->get();

                return view('dashboard.project-dashboard', compact('home_data'));
            }
        } else {

            return $this->crm_dashboard_index();
        }
    }

    public function hrm_dashboard_index()
    {

        if (Auth::check()) {

            if (\Auth::user()->can('show hrm dashboard')) {

                $user = Auth::user();

                if ($user->type != 'client' && $user->type != 'company') {
                    $emp = Employee::where('user_id', '=', $user->id)->first();

                    $announcements = Announcement::orderBy('announcements.id', 'desc')->take(5)->leftjoin('announcement_employees', 'announcements.id', '=', 'announcement_employees.announcement_id')->where('announcement_employees.employee_id', '=', $emp->id)->orWhere(function ($q) {
                        $q->where('announcements.department_id', '["0"]')->where('announcements.employee_id', '["0"]');
                    })->get();

                    $employees = Employee::get();
                    $meetings = Meeting::orderBy('meetings.id', 'desc')->take(5)->leftjoin('meeting_employees', 'meetings.id', '=', 'meeting_employees.meeting_id')->where('meeting_employees.employee_id', '=', $emp->id)->orWhere(function ($q) {
                        $q->where('meetings.department_id', '["0"]')->where('meetings.employee_id', '["0"]');
                    })->get();
                    $events = Event::leftjoin('event_employees', 'events.id', '=', 'event_employees.event_id')->where('event_employees.employee_id', '=', $emp->id)->orWhere(function ($q) {
                        $q->where('events.department_id', '["0"]')->where('events.employee_id', '["0"]');
                    })->get();

                    $arrEvents = [];
                    foreach ($events as $event) {

                        $arr['id'] = $event['id'];
                        $arr['title'] = $event['title'];
                        $arr['start'] = $event['start_date'];
                        $arr['end'] = $event['end_date'];
                        $arr['backgroundColor'] = $event['color'];
                        $arr['borderColor'] = "#fff";
                        $arr['textColor'] = "white";
                        $arrEvents[] = $arr;
                    }

                    $date = date("Y-m-d");
                    $time = date("H:i:s");
                    $employeeAttendance = AttendanceEmployee::orderBy('id', 'desc')->where('employee_id', '=', !empty(\Auth::user()->employee)?\Auth::user()->employee->id : 0)->where('date', '=', $date)->first();

                    $officeTime['startTime'] = Utility::getValByName('company_start_time');
                    $officeTime['endTime'] = Utility::getValByName('company_end_time');

                    return view('dashboard.dashboard', compact('arrEvents', 'announcements', 'employees', 'meetings', 'employeeAttendance', 'officeTime'));
                } else if ($user->type == 'super admin') {
                    $user = \Auth::user();
                    $user['total_user'] = $user->countCompany();
                    $user['total_paid_user'] = $user->countPaidCompany();
                    $user['total_orders'] = Order::total_orders();
                    $user['total_orders_price'] = Order::total_orders_price();
                    $user['total_plan'] = Plan::total_plan();
                    $user['most_purchese_plan'] = (!empty(Plan::most_purchese_plan()) ? Plan::most_purchese_plan()->name : '');

                    $chartData = $this->getOrderChart(['duration' => 'week']);

                    return view('dashboard.super_admin', compact('user', 'chartData'));
                } else {
                    $events = Event::where('created_by', '=', \Auth::user()->creatorId())->get();
                    $arrEvents = [];

                    foreach ($events as $event) {
                        $arr['id'] = $event['id'];
                        $arr['title'] = $event['title'];
                        $arr['start'] = $event['start_date'];
                        $arr['end'] = $event['end_date'];

                        $arr['backgroundColor'] = $event['color'];
                        $arr['borderColor'] = "#fff";
                        $arr['textColor'] = "white";
                        $arr['url'] = route('event.edit', $event['id']);

                        $arrEvents[] = $arr;
                    }

                    $announcements = Announcement::orderBy('announcements.id', 'desc')->take(5)->where('created_by', '=', \Auth::user()->creatorId())->get();

                    // $emp           = User::where('type', '!=', 'client')->where('type', '!=', 'company')->where('created_by', '=', \Auth::user()->creatorId())->get();
                    // $countEmployee = count($emp);

                    $user = User::where('type', '!=', 'client')->where('type', '!=', 'company')->where('created_by', '=', \Auth::user()->creatorId())->get();
                    $countUser = count($user);

                    $countTrainer = Trainer::where('created_by', '=', \Auth::user()->creatorId())->count();
                    $onGoingTraining = Training::where('status', '=', 1)->where('created_by', '=', \Auth::user()->creatorId())->count();
                    $doneTraining = Training::where('status', '=', 2)->where('created_by', '=', \Auth::user()->creatorId())->count();

                    $currentDate = date('Y-m-d');

                    $employees = User::where('type', '=', 'client')->where('created_by', '=', \Auth::user()->creatorId())->get();
                    $countClient = count($employees);
                    $notClockIn = AttendanceEmployee::where('date', '=', $currentDate)->get()->pluck('employee_id');

                    $notClockIns = Employee::where('created_by', '=', \Auth::user()->creatorId())->whereNotIn('id', $notClockIn)->get();
                    $activeJob = Job::where('status', 'active')->where('created_by', '=', \Auth::user()->creatorId())->count();
                    $inActiveJOb = Job::where('status', 'in_active')->where('created_by', '=', \Auth::user()->creatorId())->count();

                    $meetings = Meeting::where('created_by', '=', \Auth::user()->creatorId())->limit(5)->get();

                    return view('dashboard.dashboard', compact('arrEvents', 'onGoingTraining', 'activeJob', 'inActiveJOb', 'doneTraining', 'announcements', 'employees', 'meetings', 'countTrainer', 'countClient', 'countUser', 'notClockIns'));
                }
            } else {

                return $this->project_dashboard_index();
            }
        } else {
            if (!file_exists(storage_path() . "/installed")) {
                header('location:install');
                die;
            } else {
                $settings = Utility::settings();
                if ($settings['display_landing_page'] == 'on') {
                    $plans = Plan::get();

                    return view('layouts.landing', compact('plans'));
                } else {
                    return redirect('login');
                }

            }
        }
    }

    public function crm_dashboard_index()
    {
        $user = Auth::user();
        if (\Auth::user()->can('show crm dashboard')) {
            if ($user->type == 'admin') {
                return view('admin.dashboard');
            } else {
                $crm_data = [];


               if ($user->type == 'Sales') {
                    // Sales: only their assigned leads and deals

                    $leads = Lead::select('leads.*')
                        ->join('user_leads', 'user_leads.lead_id', '=', 'leads.id')
                        ->where('user_leads.user_id', $user->id)
                        ->get();

                    $deals = Deal::select('deals.*')
                        ->join('user_deals', 'user_deals.deal_id', '=', 'deals.id')
                        ->where('user_deals.user_id', $user->id)
                        ->get();

                } elseif ($user->type == 'manager') {
                    // Manager: their own + their users' leads and deals

                    $managedUserIds = \App\Models\User::where('manager_id', $user->id)->pluck('id');
                    $allUserIds = $managedUserIds->push($user->id); // include self

                    $leads = Lead::select('leads.*')
                        ->join('user_leads', 'user_leads.lead_id', '=', 'leads.id')
                        ->whereIn('user_leads.user_id', $allUserIds)
                        ->get();

                    $deals = Deal::select('deals.*')
                        ->join('user_deals', 'user_deals.deal_id', '=', 'deals.id')
                        ->whereIn('user_deals.user_id', $allUserIds)
                        ->get();
                }else{
                    $leads = Lead::where('created_by', $user->creatorId())->get();
                    $deals = Deal::where('created_by', $user->creatorId())->get();
                }


                //count data
                $crm_data['total_leads'] = $total_leads = count($leads);
                $crm_data['total_deals'] = $total_deals = count($deals);
                $crm_data['total_contracts'] = Contract::where('created_by', \Auth::user()->creatorId())->count();

                //lead status
//                $user_leads   = $leads->pluck('lead_id')->toArray();
                $total_leads = count($leads);
                $lead_status = [];
                $status = LeadStage::select('lead_stages.*', 'pipelines.name as pipeline')
                    ->join('pipelines', 'pipelines.id', '=', 'lead_stages.pipeline_id')
                    ->where('pipelines.created_by', '=', \Auth::user()->creatorId())
                    ->where('lead_stages.created_by', '=', \Auth::user()->creatorId())
                    ->orderBy('lead_stages.pipeline_id')->get();

                foreach ($status as $k => $v) {
                    $lead_status[$k]['lead_stage'] = $v->name;
                    $lead_status[$k]['lead_total'] = count($v->lead()->get());
                    $lead_status[$k]['lead_percentage'] = Utility::getCrmPercentage($lead_status[$k]['lead_total'], $total_leads);

                }

                $crm_data['lead_status'] = $lead_status;

                //deal status
//                $user_deal   = $deals->pluck('deal_id')->toArray();
                $total_deals = count($deals);
                $deal_status = [];
                $dealstatuss = Stage::select('stages.*', 'pipelines.name as pipeline')
                    ->join('pipelines', 'pipelines.id', '=', 'stages.pipeline_id')
                    ->where('pipelines.created_by', '=', \Auth::user()->creatorId())
                    ->where('stages.created_by', '=', \Auth::user()->creatorId())
                    ->orderBy('stages.pipeline_id')->get();
                foreach ($dealstatuss as $k => $v) {
                    $deal_status[$k]['deal_stage'] = $v->name;
                    $deal_status[$k]['deal_total'] = count($v->deals());
                    $deal_status[$k]['deal_percentage'] = Utility::getCrmPercentage($deal_status[$k]['deal_total'], $total_deals);
                }
                $crm_data['deal_status'] = $deal_status;

                $crm_data['latestContract'] = Contract::where('created_by', '=', \Auth::user()->creatorId())->orderBy('id', 'desc')->limit(5)->with(['clients', 'projects', 'types'])->get();

                return view('dashboard.crm-dashboard', compact('crm_data'));
            }
        } else {
            return $this->account_dashboard_index();
        }
    }

    public function pos_dashboard_index()
    {
        $user = Auth::user();
        if (\Auth::user()->can('show pos dashboard')) {
            if ($user->type == 'admin') {
                return view('admin.dashboard');
            } else {
                $pos_data = [];
                $pos_data['monthlyPosAmount'] = Pos::totalPosAmount(true);
                $pos_data['totalPosAmount'] = Pos::totalPosAmount();
                $pos_data['monthlyPurchaseAmount'] = Purchase::totalPurchaseAmount(true);
                $pos_data['totalPurchaseAmount'] = Purchase::totalPurchaseAmount();

                $purchasesArray = Purchase::getPurchaseReportChart();
                $posesArray = Pos::getPosReportChart();

                return view('dashboard.pos-dashboard', compact('pos_data', 'purchasesArray', 'posesArray'));
            }
        } else {
            return $this->account_dashboard_index();
        }
    }

    // Load Dashboard user's using ajax
    public function filterView(Request $request)
    {
        $usr = Auth::user();
        $users = User::where('id', '!=', $usr->id);

        if ($request->ajax()) {
            if (!empty($request->keyword)) {
                $users->where('name', 'LIKE', $request->keyword . '%')->orWhereRaw('FIND_IN_SET("' . $request->keyword . '",skills)');
            }

            $users = $users->get();
            $returnHTML = view('dashboard.view', compact('users'))->render();

            return response()->json([
                'success' => true,
                'html' => $returnHTML,
            ]);
        }
    }

    public function clientView()
    {

        if (Auth::check()) {
            if (Auth::user()->type == 'super admin') {
                $user = \Auth::user();
                $user['total_user'] = $user->countCompany();
                $user['total_paid_user'] = $user->countPaidCompany();
                $user['total_orders'] = Order::total_orders();
                $user['total_orders_price'] = Order::total_orders_price();
                $user['total_plan'] = Plan::total_plan();
                $user['most_purchese_plan'] = (!empty(Plan::most_purchese_plan()) ? Plan::most_purchese_plan()->total : 0);
                // $user['most_purchese_plan'] = Plan::most_purchese_plan()->total;
                $chartData = $this->getOrderChart(['duration' => 'week']);

                return view('dashboard.super_admin', compact('user', 'chartData'));

            } elseif (Auth::user()->type == 'client') {
                $transdate = date('Y-m-d', time());
                $currentYear = date('Y');

                $calenderTasks = [];
                $chartData = [];
                $arrCount = [];
                $arrErr = [];
                $m = date("m");
                $de = date("d");
                $y = date("Y");
                $format = 'Y-m-d';
                $user = \Auth::user();
                if (\Auth::user()->can('View Task')) {
                    $company_setting = Utility::settings();
                }
                $arrTemp = [];
                for ($i = 0; $i <= 7 - 1; $i++) {
                    $date = date($format, mktime(0, 0, 0, $m, ($de - $i), $y));
                    $arrTemp['date'][] = __(date('D', strtotime($date)));
                    $arrTemp['invoice'][] = 10;
                    $arrTemp['payment'][] = 20;
                }

                $chartData = $arrTemp;

                foreach ($user->clientDeals as $deal) {
                    foreach ($deal->tasks as $task) {
                        $calenderTasks[] = [
                            'title' => $task->name,
                            'start' => $task->date,
                            'url' => route('deals.tasks.show', [
                                $deal->id,
                                $task->id,
                            ]),
                            'className' => ($task->status) ? 'bg-primary border-primary' : 'bg-warning border-warning',
                        ];
                    }

                    $calenderTasks[] = [
                        'title' => $deal->name,
                        'start' => $deal->created_at->format('Y-m-d'),
                        'url' => route('deals.show', [$deal->id]),
                        'className' => 'deal bg-primary border-primary',
                    ];
                }
                $client_deal = $user->clientDeals->pluck('id');

                $arrCount['deal'] = !empty($user->clientDeals) ? $user->clientDeals->count() : 0;

                if (!empty($client_deal->first())) {

                    $arrCount['task'] = DealTask::whereIn('deal_id', [$client_deal->first()])->count();

                } else {
                    $arrCount['task'] = 0;
                }

                $project['projects'] = Project::where('client_id', '=', Auth::user()->id)->where('created_by', \Auth::user()->creatorId())->where('end_date', '>', date('Y-m-d'))->limit(5)->orderBy('end_date')->get();
                $project['projects_count'] = count($project['projects']);
                $user_projects = Project::where('client_id', \Auth::user()->id)->pluck('id', 'id')->toArray();
                $tasks = ProjectTask::whereIn('project_id', $user_projects)->where('created_by', \Auth::user()->creatorId())->get();
                $project['projects_tasks_count'] = count($tasks);
                $project['project_budget'] = Project::where('client_id', Auth::user()->id)->sum('budget');

                $project_last_stages = Auth::user()->last_projectstage();
                $project_last_stage = (!empty($project_last_stages) ? $project_last_stages->id : 0);
                $project['total_project'] = Auth::user()->user_project();
                $total_project_task = Auth::user()->created_total_project_task();
                $allProject = Project::where('client_id', \Auth::user()->id)->where('created_by', \Auth::user()->creatorId())->get();
                $allProjectCount = count($allProject);

                $bugs = Bug::whereIn('project_id', $user_projects)->where('created_by', \Auth::user()->creatorId())->get();
                $project['projects_bugs_count'] = count($bugs);
                $bug_last_stage = BugStatus::orderBy('order', 'DESC')->first();
                $completed_bugs = Bug::whereIn('project_id', $user_projects)->where('status', $bug_last_stage->id)->where('created_by', \Auth::user()->creatorId())->get();
                $allBugCount = count($bugs);
                $completedBugCount = count($completed_bugs);
                $project['project_bug_percentage'] = ($allBugCount != 0) ? intval(($completedBugCount / $allBugCount) * 100) : 0;
                $complete_task = Auth::user()->project_complete_task($project_last_stage);
                $completed_project = Project::where('client_id', \Auth::user()->id)->where('status', 'complete')->where('created_by', \Auth::user()->creatorId())->get();
                $completed_project_count = count($completed_project);
                $project['project_percentage'] = ($allProjectCount != 0) ? intval(($completed_project_count / $allProjectCount) * 100) : 0;
                $project['project_task_percentage'] = ($total_project_task != 0) ? intval(($complete_task / $total_project_task) * 100) : 0;
                $invoice = [];
                $top_due_invoice = [];
                $invoice['total_invoice'] = 5;
                $complete_invoice = 0;
                $total_due_amount = 0;
                $top_due_invoice = array();
                $pay_amount = 0;

                if (Auth::user()->type == 'client') {
                    if (!empty($project['project_budget'])) {
                        $project['client_project_budget_due_per'] = intval(($pay_amount / $project['project_budget']) * 100);
                    } else {
                        $project['client_project_budget_due_per'] = 0;
                    }

                }

                $top_tasks = Auth::user()->created_top_due_task();
                $users['staff'] = User::where('created_by', '=', Auth::user()->creatorId())->count();
                $users['user'] = User::where('created_by', '=', Auth::user()->creatorId())->where('type', '!=', 'client')->count();
                $users['client'] = User::where('created_by', '=', Auth::user()->creatorId())->where('type', '=', 'client')->count();
                $project_status = array_values(Project::$project_status);
                $projectData = \App\Models\Project::getProjectStatus();

                $taskData = \App\Models\TaskStage::getChartData();

                return view('dashboard.clientView', compact('calenderTasks', 'arrErr', 'arrCount', 'chartData', 'project', 'invoice', 'top_tasks', 'top_due_invoice', 'users', 'project_status', 'projectData', 'taskData', 'transdate', 'currentYear'));
            }
        }
    }

    public function getOrderChart($arrParam)
    {
        $arrDuration = [];
        if ($arrParam['duration']) {
            if ($arrParam['duration'] == 'week') {
                $previous_week = strtotime("-2 week +1 day");
                for ($i = 0; $i < 14; $i++) {
                    $arrDuration[date('Y-m-d', $previous_week)] = date('d-M', $previous_week);
                    $previous_week = strtotime(date('Y-m-d', $previous_week) . " +1 day");
                }
            }
        }

        $arrTask = [];
        $arrTask['label'] = [];
        $arrTask['data'] = [];
        foreach ($arrDuration as $date => $label) {

            $data = Order::select(\DB::raw('count(*) as total'))->whereDate('created_at', '=', $date)->first();
            $arrTask['label'][] = $label;
            $arrTask['data'][] = $data->total;
        }

        return $arrTask;
    }

    public function stopTracker(Request $request)
    {
        if (Auth::user()->isClient()) {
            return Utility::error_res(__('Permission denied.'));
        }
        $validatorArray = [
            'name' => 'required|max:120',
            'project_id' => 'required|integer',
        ];
        $validator = Validator::make(
            $request->all(), $validatorArray
        );
        if ($validator->fails()) {
            return Utility::error_res($validator->errors()->first());
        }
        $tracker = TimeTracker::where('created_by', '=', Auth::user()->id)->where('is_active', '=', 1)->first();
        if ($tracker) {
            $tracker->end_time = $request->has('end_time') ? $request->input('end_time') : date("Y-m-d H:i:s");
            $tracker->is_active = 0;
            $tracker->total_time = Utility::diffance_to_time($tracker->start_time, $tracker->end_time);
            $tracker->save();

            return Utility::success_res(__('Add Time successfully.'));
        }

        return Utility::error_res('Tracker not found.');
    }

    /**
     * Stock Overview page - grouped by product
     * Returns stock data similar to stock report but grouped by product
     */
    public function stockOverview(Request $request)
    {
        if (!\Auth::user()->can('show account dashboard')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        // Get filter data similar to stock report
        $categories = \App\Models\ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->pluck('name', 'id');
        $products = \App\Models\ProductService::where('created_by', \Auth::user()->creatorId())->pluck('name', 'id');
        $warehouses = DB::table('warehouses')->where('created_by', \Auth::user()->creatorId())->orderBy('name')->get(['id','name']);

        $stockOverview = $this->getStockOverviewData($request);

        return view('dashboard.stock-overview', compact('stockOverview', 'categories', 'products', 'warehouses'));
    }

    /**
     * Export Stock Overview to Excel
     */
    public function stockOverviewExport(Request $request)
    {
        if (!\Auth::user()->can('show account dashboard')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        try {
            // Get filters from request
            $filters = [
                'q' => $request->get('q'),
                'category_id' => $request->get('category_id'),
                'product_id' => $request->get('product_id'),
                'warehouse_id' => $request->get('warehouse_id'),
            ];

            // Remove empty filters
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            $name = 'stock_overview_' . date('Y-m-d_H-i-s');
            $data = \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\StockOverviewExport(\Auth::user()->creatorId(), $filters), 
                $name . '.xlsx'
            );

            return $data;
        } catch (\Exception $e) {
            \Log::error('Stock Overview export failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => \Auth::user()->creatorId()
            ]);

            return redirect()->back()->with('error', __('Export failed: ') . $e->getMessage());
        }
    }

    /**
     * Get Stock Overview grouped by product
     * Returns stock data similar to stock report but grouped by product
     */
    private function getStockOverviewData($request = null)
    {
        $creatorId = \Auth::user()->creatorId();
        
        // Build query
        $query = \App\Models\SubProduct::where('sub_products.created_by', $creatorId)
            ->join('product_services', 'sub_products.product_id', '=', 'product_services.id')
            ->leftJoin('product_service_categories', 'product_services.category_id', '=', 'product_service_categories.id')
            ->leftJoin('brands', 'product_services.brand_id', '=', 'brands.id')
            ->leftJoin('sub_brands', 'product_services.sub_brand_id', '=', 'sub_brands.id')
            ->leftJoin('warehouses', 'sub_products.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('countries', 'warehouses.country_id', '=', 'countries.id');

        // Apply filters
        if ($request) {
            // Search filter
            if ($request->filled('q')) {
                $q = trim($request->q);
                $query->where(function($subQ) use ($q) {
                    $subQ->where('product_services.name', 'like', "%{$q}%")
                         ->orWhere('product_services.sku', 'like', "%{$q}%")
                         ->orWhere('brands.name', 'like', "%{$q}%")
                         ->orWhere('sub_brands.name', 'like', "%{$q}%");
                });
            }

            // Category filter
            if ($request->filled('category_id')) {
                $query->where('product_services.category_id', $request->category_id);
            }

            // Product filter
            if ($request->filled('product_id')) {
                $query->where('product_services.id', $request->product_id);
            }

            // Warehouse filter
            if ($request->filled('warehouse_id')) {
                $query->where('sub_products.warehouse_id', $request->warehouse_id);
            }
        }
        
        // Get stock data grouped by product
        $stockData = $query->select(
                'product_services.id as product_id',
                'product_services.name as product_name',
                'product_services.sku',
                'product_service_categories.name as category_name',
                'brands.name as brand_name',
                'sub_brands.name as sub_brand_name',
                DB::raw('SUM(sub_products.quantity) as total_quantity'),
                DB::raw('COUNT(DISTINCT sub_products.id) as sub_product_count'),
                DB::raw('SUM(CASE WHEN sub_products.booked = 0 THEN sub_products.quantity ELSE 0 END) as free_quantity'),
                DB::raw('SUM(CASE WHEN sub_products.booked != 0 THEN sub_products.quantity ELSE 0 END) as booked_quantity'),
                DB::raw('AVG(sub_products.sale_price) as avg_sale_price'),
                DB::raw('AVG(sub_products.purchase_price) as avg_purchase_price')
            )
            ->groupBy(
                'product_services.id',
                'product_services.name',
                'product_services.sku',
                'product_service_categories.name',
                'brands.name',
                'sub_brands.name'
            )
            ->orderBy('product_services.name')
            ->get();

        // Sell qty: for each product, sum quantity sold from POS + Invoices (optionally filtered by warehouse)
        $productIds = $stockData->pluck('product_id')->unique();
        $posSold = collect();
        $invoiceSold = collect();
        if ($productIds->isNotEmpty()) {
            $posQuery = \App\Models\PosProduct::join('pos', 'pos_products.pos_id', '=', 'pos.id')
                ->where('pos.created_by', $creatorId)
                ->whereNull('pos.deleted_at')
                ->whereIn('pos_products.product_id', $productIds)
                ->groupBy('pos_products.product_id')
                ->selectRaw('pos_products.product_id, SUM(pos_products.quantity) as qty');
            if ($request && $request->filled('warehouse_id')) {
                $posQuery->where('pos.warehouse_id', $request->warehouse_id);
            }
            $posSold = $posQuery->get()->keyBy('product_id');

            $invoiceQuery = \App\Models\InvoiceProduct::join('invoices', 'invoice_products.invoice_id', '=', 'invoices.id')
                ->join('sub_products', 'invoice_products.sub_product_id', '=', 'sub_products.id')
                ->where('invoices.created_by', $creatorId)
                ->whereNull('invoice_products.deleted_at')
                ->whereNull('invoices.deleted_at')
                ->whereIn('invoice_products.product_id', $productIds)
                ->groupBy('invoice_products.product_id')
                ->selectRaw('invoice_products.product_id, SUM(invoice_products.quantity) as qty');
            if ($request && $request->filled('warehouse_id')) {
                $invoiceQuery->where('sub_products.warehouse_id', $request->warehouse_id);
            }
            $invoiceSold = $invoiceQuery->get()->keyBy('product_id');
        }

        foreach ($stockData as $item) {
            $item->sell_qty = ($posSold->get($item->product_id)?->qty ?? 0) + ($invoiceSold->get($item->product_id)?->qty ?? 0);
        }

        return $stockData;
    }

    /**
     * Sell Overview Report
     * Shows products sold from POS and Invoices grouped by product
     */
    public function sellOverview(Request $request)
    {
        if (!\Auth::user()->can('show account dashboard')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        if (!$request->filled('date_from') && !$request->filled('date_to')) {
            $request->merge($this->defaultSellOverviewMonthDateRange());
        }

        // Get filter data similar to stock report
        $categories = \App\Models\ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->pluck('name', 'id');
        $products = \App\Models\ProductService::where('created_by', \Auth::user()->creatorId())->pluck('name', 'id');
        $warehouses = DB::table('warehouses')->where('created_by', \Auth::user()->creatorId())->orderBy('name')->get(['id','name']);

        $sellOverview = $this->getSellOverviewData($request);
        $refundTotals = $this->getSellOverviewRefundTotals($request);

        return view('dashboard.sell-overview', compact('sellOverview', 'categories', 'products', 'warehouses', 'refundTotals'));
    }

    /**
     * Default date range for Sell Overview: current calendar month (single month).
     *
     * @return array{date_from: string, date_to: string}
     */
    private function defaultSellOverviewMonthDateRange(): array
    {
        return [
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->endOfMonth()->format('Y-m-d'),
        ];
    }

    /**
     * Get Sell Overview Data
     * Retrieves sold products from POS and Invoices grouped by product
     */
    private function getSellOverviewData($request = null)
    {
        $creatorId = \Auth::user()->creatorId();
        
        // Get POS refund data grouped by product (for net sell/net cost/profit-loss)
        $refundQuery = \App\Models\PosRefundItem::join('pos_refunds', 'pos_refund_items.refund_id', '=', 'pos_refunds.id')
            ->leftJoin('pos_products', 'pos_refund_items.pos_products_id', '=', 'pos_products.id')
            ->leftJoin('pos', 'pos_refunds.pos_id', '=', 'pos.id')
            ->leftJoin('product_services', 'pos_products.product_id', '=', 'product_services.id')
            ->leftJoin('brands', 'product_services.brand_id', '=', 'brands.id')
            ->leftJoin('sub_brands', 'product_services.sub_brand_id', '=', 'sub_brands.id')
            ->leftJoin('sub_products', 'pos_products.sub_product_id', '=', 'sub_products.id')
            ->leftJoin('stock_movements', function ($join) use ($creatorId) {
                $join->on('stock_movements.pos_id', '=', 'pos_refunds.pos_id')
                    ->on('stock_movements.sub_product_id', '=', 'pos_products.sub_product_id')
                    ->where('stock_movements.activity', '=', 'Sale via POS')
                    ->where('stock_movements.created_by', '=', $creatorId);
            })
            ->where('pos_refunds.created_by', $creatorId)
            ->whereNull('pos.deleted_at');
        
        // Get POS products sold
        $posQuery = \App\Models\PosProduct::join('pos', 'pos_products.pos_id', '=', 'pos.id')
            ->join('product_services', 'pos_products.product_id', '=', 'product_services.id')
            ->leftJoin('product_service_categories', 'product_services.category_id', '=', 'product_service_categories.id')
            ->leftJoin('brands', 'product_services.brand_id', '=', 'brands.id')
            ->leftJoin('sub_brands', 'product_services.sub_brand_id', '=', 'sub_brands.id')
            ->leftJoin('warehouses', 'pos.warehouse_id', '=', 'warehouses.id')
            ->where('pos.created_by', $creatorId)
            ->where('product_services.created_by', $creatorId)
            ->whereNull('pos.deleted_at'); // Exclude deleted POS

        // Get Invoice products sold
        // Note: Invoices don't have warehouse_id - warehouse comes from sub_products
        $invoiceQuery = \App\Models\InvoiceProduct::join('invoices', 'invoice_products.invoice_id', '=', 'invoices.id')
            ->join('product_services', 'invoice_products.product_id', '=', 'product_services.id')
            ->leftJoin('sub_products', 'invoice_products.sub_product_id', '=', 'sub_products.id')
            ->leftJoin('product_service_categories', 'product_services.category_id', '=', 'product_service_categories.id')
            ->leftJoin('brands', 'product_services.brand_id', '=', 'brands.id')
            ->leftJoin('sub_brands', 'product_services.sub_brand_id', '=', 'sub_brands.id')
            ->leftJoin('warehouses', 'sub_products.warehouse_id', '=', 'warehouses.id')
            ->where('invoices.created_by', $creatorId)
            ->where('product_services.created_by', $creatorId)
            ->whereNull('invoice_products.deleted_at') // Exclude deleted invoice products
            ->whereNull('invoices.deleted_at'); // Exclude deleted invoices

        // Apply filters
        if ($request) {
            // Search filter
            if ($request->filled('q')) {
                $q = trim($request->q);
                $posQuery->where(function($subQ) use ($q) {
                    $subQ->where('product_services.name', 'like', "%{$q}%")
                         ->orWhere('product_services.sku', 'like', "%{$q}%")
                         ->orWhere('brands.name', 'like', "%{$q}%")
                         ->orWhere('sub_brands.name', 'like', "%{$q}%");
                });
                $invoiceQuery->where(function($subQ) use ($q) {
                    $subQ->where('product_services.name', 'like', "%{$q}%")
                         ->orWhere('product_services.sku', 'like', "%{$q}%")
                         ->orWhere('brands.name', 'like', "%{$q}%")
                         ->orWhere('sub_brands.name', 'like', "%{$q}%");
                });
                $refundQuery->where(function($subQ) use ($q) {
                    $subQ->where('product_services.name', 'like', "%{$q}%")
                         ->orWhere('product_services.sku', 'like', "%{$q}%")
                         ->orWhere('brands.name', 'like', "%{$q}%")
                         ->orWhere('sub_brands.name', 'like', "%{$q}%");
                });
            }

            // Category filter
            if ($request->filled('category_id')) {
                $posQuery->where('product_services.category_id', $request->category_id);
                $invoiceQuery->where('product_services.category_id', $request->category_id);
                $refundQuery->where('product_services.category_id', $request->category_id);
            }

            // Product filter
            if ($request->filled('product_id')) {
                $posQuery->where('product_services.id', $request->product_id);
                $invoiceQuery->where('product_services.id', $request->product_id);
                $refundQuery->where('product_services.id', $request->product_id);
            }

            // Warehouse filter
            if ($request->filled('warehouse_id')) {
                $posQuery->where('pos.warehouse_id', $request->warehouse_id);
                $invoiceQuery->where('sub_products.warehouse_id', $request->warehouse_id);
                $refundQuery->where('pos.warehouse_id', $request->warehouse_id);
            }

            // Date range filter
            if ($request->filled('date_from')) {
                $posQuery->where('pos.pos_date', '>=', $request->date_from);
                $invoiceQuery->where('invoices.issue_date', '>=', $request->date_from);
                $refundQuery->whereDate('pos_refunds.created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $posQuery->where('pos.pos_date', '<=', $request->date_to);
                $invoiceQuery->where('invoices.issue_date', '<=', $request->date_to);
                $refundQuery->whereDate('pos_refunds.created_at', '<=', $request->date_to);
            }
        }

        $refundData = $refundQuery->select(
                'product_services.id as product_id',
                DB::raw("COALESCE(SUM(
                    (
                        CASE
                            WHEN (pos_products.compo_id IS NOT NULL AND pos_products.compo_id != 0 AND pos_products.compo_id != '0' AND pos_products.combo_price IS NOT NULL)
                            THEN (pos_products.combo_price - (pos_products.combo_price * COALESCE(pos_products.discount, 0) / 100))
                            ELSE (pos_products.price - (pos_products.price * COALESCE(pos_products.discount, 0) / 100))
                        END
                    ) * COALESCE(pos_refund_items.quantity, 0)
                ), 0) as total_sell_refund"),
                DB::raw('COALESCE(SUM(COALESCE(stock_movements.avg_cost, sub_products.purchase_price, 0) * COALESCE(pos_refund_items.quantity, 0)), 0) as total_cost_refund')
            )
            ->groupBy('product_services.id')
            ->get()
            ->keyBy('product_id');

        // Get POS sold data grouped by product
        // Calculate actual selling price: use combo_price if combo exists, otherwise price, then apply discount
        $posData = $posQuery->select(
                'product_services.id as product_id',
                'product_services.name as product_name',
                'product_services.sku',
                'product_service_categories.name as category_name',
                'brands.name as brand_name',
                'sub_brands.name as sub_brand_name',
                DB::raw('SUM(pos_products.quantity) as pos_sell_qty'),
                DB::raw('COUNT(DISTINCT pos_products.sub_product_id) as pos_sub_product_count'),
                DB::raw('COUNT(DISTINCT pos_products.pos_id) as pos_count'),
                DB::raw('CASE 
                    WHEN SUM(pos_products.quantity) > 0 THEN
                        SUM(
                            CASE 
                                WHEN (pos_products.compo_id IS NOT NULL AND pos_products.compo_id != 0 AND pos_products.compo_id != "0" AND pos_products.combo_price IS NOT NULL)
                                THEN (pos_products.combo_price - (pos_products.combo_price * COALESCE(pos_products.discount, 0) / 100)) * pos_products.quantity
                                ELSE (pos_products.price - (pos_products.price * COALESCE(pos_products.discount, 0) / 100)) * pos_products.quantity
                            END
                        ) / SUM(pos_products.quantity)
                    ELSE 0
                END as avg_pos_price')
            )
            ->groupBy(
                'product_services.id',
                'product_services.name',
                'product_services.sku',
                'product_service_categories.name',
                'brands.name',
                'sub_brands.name'
            )
            ->get()
            ->keyBy('product_id');

        // Get Invoice sold data grouped by product
        // Calculate actual selling price: price after discount
        $invoiceData = $invoiceQuery->select(
                'product_services.id as product_id',
                'product_services.name as product_name',
                'product_services.sku',
                'product_service_categories.name as category_name',
                'brands.name as brand_name',
                'sub_brands.name as sub_brand_name',
                DB::raw('SUM(invoice_products.quantity) as invoice_sell_qty'),
                DB::raw('COUNT(DISTINCT invoice_products.sub_product_id) as invoice_sub_product_count'),
                DB::raw('COUNT(DISTINCT invoice_products.invoice_id) as invoice_count'),
                DB::raw('CASE 
                    WHEN SUM(invoice_products.quantity) > 0 THEN
                        SUM(
                            (invoice_products.price - (invoice_products.price * COALESCE(invoice_products.discount, 0) / 100)) * invoice_products.quantity
                        ) / SUM(invoice_products.quantity)
                    ELSE 0
                END as avg_invoice_price')
            )
            ->groupBy(
                'product_services.id',
                'product_services.name',
                'product_services.sku',
                'product_service_categories.name',
                'brands.name',
                'sub_brands.name'
            )
            ->get()
            ->keyBy('product_id');

        // Merge POS and Invoice data
        $allProductIds = $posData->keys()->merge($invoiceData->keys())->merge($refundData->keys())->unique();
        $mergedData = collect();

        foreach ($allProductIds as $productId) {
            $posItem = $posData->get($productId);
            $invoiceItem = $invoiceData->get($productId);

            // Sum of (item cost × qty) for lines with resolvable cost; denominator for avg is Total Sell Qty (below)
            $totalCostQty = 0;
            $posQty = $posItem?->pos_sell_qty ?? 0;
            $invoiceQty = $invoiceItem?->invoice_sell_qty ?? 0;
            $totalQty = $posQty + $invoiceQty;

            // Get POS products sold - use avg_cost from stock_movements (activity = 'Sale via POS'), fallback to purchase_price
            $posProducts = \App\Models\PosProduct::join('pos', 'pos_products.pos_id', '=', 'pos.id')
                ->join('sub_products', 'pos_products.sub_product_id', '=', 'sub_products.id')
                ->leftJoin('stock_movements', function($join) use ($creatorId) {
                    $join->on('stock_movements.pos_id', '=', 'pos.id')
                         ->on('stock_movements.sub_product_id', '=', 'pos_products.sub_product_id')
                         ->where('stock_movements.activity', '=', 'Sale via POS')
                         ->where('stock_movements.created_by', '=', $creatorId);
                })
                ->where('pos_products.product_id', $productId)
                ->where('pos.created_by', $creatorId)
                ->whereNull('pos.deleted_at');
            
            // Apply same filters as main query
            if ($request) {
                if ($request->filled('warehouse_id')) {
                    $posProducts->where('pos.warehouse_id', $request->warehouse_id);
                }
                if ($request->filled('date_from')) {
                    $posProducts->where('pos.pos_date', '>=', $request->date_from);
                }
                if ($request->filled('date_to')) {
                    $posProducts->where('pos.pos_date', '<=', $request->date_to);
                }
            }
            
            $posProducts = $posProducts->select(
                    'pos_products.quantity',
                    'stock_movements.avg_cost as pos_avg_cost',
                    'sub_products.purchase_price'
                )
                ->get();
            
            foreach ($posProducts as $posProduct) {
                $itemQty = $posProduct->quantity ?? 0;
                if ($itemQty > 0) {
                    $itemCost = 0;
                    // For POS products, use avg_cost from stock movements
                    if (isset($posProduct->pos_avg_cost) && $posProduct->pos_avg_cost > 0) {
                        $itemCost = $posProduct->pos_avg_cost;
                    } elseif (isset($posProduct->purchase_price) && $posProduct->purchase_price > 0) {
                        // Fallback to purchase_price if avg_cost not available
                        $itemCost = $posProduct->purchase_price;
                    }
                    
                    if ($itemCost > 0) {
                        $totalCostQty += ($itemCost * $itemQty);
                    }
                }
            }
            
            // Get Invoice products sold - use purchase_price from sub_products
            $invoiceProducts = \App\Models\InvoiceProduct::join('invoices', 'invoice_products.invoice_id', '=', 'invoices.id')
                ->join('sub_products', 'invoice_products.sub_product_id', '=', 'sub_products.id')
                ->where('invoice_products.product_id', $productId)
                ->where('invoices.created_by', $creatorId)
                ->whereNull('invoice_products.deleted_at')
                ->whereNull('invoices.deleted_at');
            
            // Apply same filters as main query
            if ($request) {
                if ($request->filled('warehouse_id')) {
                    $invoiceProducts->where('sub_products.warehouse_id', $request->warehouse_id);
                }
                if ($request->filled('date_from')) {
                    $invoiceProducts->where('invoices.issue_date', '>=', $request->date_from);
                }
                if ($request->filled('date_to')) {
                    $invoiceProducts->where('invoices.issue_date', '<=', $request->date_to);
                }
            }
            
            $invoiceProducts = $invoiceProducts->select(
                    'invoice_products.quantity',
                    'sub_products.purchase_price'
                )
                ->get();
            
            foreach ($invoiceProducts as $invoiceProduct) {
                $itemQty = $invoiceProduct->quantity ?? 0;
                if ($itemQty > 0) {
                    $itemCost = 0;
                    // For Invoice products, use purchase_price
                    if (isset($invoiceProduct->purchase_price) && $invoiceProduct->purchase_price > 0) {
                        $itemCost = $invoiceProduct->purchase_price;
                    }
                    
                    if ($itemCost > 0) {
                        $totalCostQty += ($itemCost * $itemQty);
                    }
                }
            }
            
            // Avg cost = sum(item cost × qty) / Total Sell Qty (same base as POS + Invoice sell qty columns)
            $avgCost = ($totalQty > 0) ? ($totalCostQty / $totalQty) : 0;

            // Calculate weighted average selling price
            $avgPosPrice = $posItem?->avg_pos_price ?? 0;
            $avgInvoicePrice = $invoiceItem?->avg_invoice_price ?? 0;
            
            $weightedAvgPrice = 0;
            if ($totalQty > 0) {
                $weightedAvgPrice = (($posQty * $avgPosPrice) + ($invoiceQty * $avgInvoicePrice)) / $totalQty;
            }
            
            // Calculate totals
            $totalSell = $totalQty * $weightedAvgPrice;
            $totalCost = $totalQty * $avgCost;
            $sellRefund = (float) ($refundData->get($productId)?->total_sell_refund ?? 0);
            $costRefund = (float) ($refundData->get($productId)?->total_cost_refund ?? 0);
            $netSell = $totalSell - $sellRefund;
            $netCost = $totalCost - $costRefund;
            $profit = $netSell - $netCost;

            // Available qty: sum of sub_products.quantity for this product (remaining stock)
            $availableQtyQuery = \App\Models\SubProduct::where('product_id', $productId)
                ->where('created_by', $creatorId);
            if ($request && $request->filled('warehouse_id')) {
                $availableQtyQuery->where('warehouse_id', $request->warehouse_id);
            }
            $available_qty = (float) $availableQtyQuery->sum('quantity');

            $mergedItem = (object)[
                'product_id' => $productId,
                'available_qty' => $available_qty,
                'product_name' => $posItem?->product_name ?? $invoiceItem?->product_name ?? null,
                'sku' => $posItem?->sku ?? $invoiceItem?->sku ?? null,
                'category_name' => $posItem?->category_name ?? $invoiceItem?->category_name ?? null,
                'brand_name' => $posItem?->brand_name ?? $invoiceItem?->brand_name ?? null,
                'sub_brand_name' => $posItem?->sub_brand_name ?? $invoiceItem?->sub_brand_name ?? null,
                'pos_sell_qty' => $posQty,
                'invoice_sell_qty' => $invoiceQty,
                'total_sell_qty' => $totalQty,
                'pos_sub_product_count' => $posItem?->pos_sub_product_count ?? 0,
                'invoice_sub_product_count' => $invoiceItem?->invoice_sub_product_count ?? 0,
                'total_sub_product_count' => ($posItem?->pos_sub_product_count ?? 0) + ($invoiceItem?->invoice_sub_product_count ?? 0),
                'pos_count' => $posItem?->pos_count ?? 0,
                'invoice_count' => $invoiceItem?->invoice_count ?? 0,
                'avg_pos_price' => $avgPosPrice,
                'avg_invoice_price' => $avgInvoicePrice,
                'avg_cost' => $avgCost,
                'total_sell' => $totalSell,
                'total_cost' => $totalCost,
                'total_sell_refund' => $sellRefund,
                'total_cost_refund' => $costRefund,
                'net_sell' => $netSell,
                'net_cost' => $netCost,
                'profit' => $profit,
            ];

            $mergedData->push($mergedItem);
        }

        return $mergedData->sortBy('product_name')->values();
    }

    /**
     * Get Sell Overview Refund Totals
     * Calculates total refunded sell amount and total refunded cost
     * for POS refunds using same filters as sell overview.
     */
    private function getSellOverviewRefundTotals($request = null)
    {
        $creatorId = \Auth::user()->creatorId();

        $refundQuery = \App\Models\PosRefundItem::join('pos_refunds', 'pos_refund_items.refund_id', '=', 'pos_refunds.id')
            ->leftJoin('pos_products', 'pos_refund_items.pos_products_id', '=', 'pos_products.id')
            ->leftJoin('pos', 'pos_refunds.pos_id', '=', 'pos.id')
            ->leftJoin('product_services', 'pos_products.product_id', '=', 'product_services.id')
            ->leftJoin('brands', 'product_services.brand_id', '=', 'brands.id')
            ->leftJoin('sub_brands', 'product_services.sub_brand_id', '=', 'sub_brands.id')
            ->leftJoin('sub_products', 'pos_products.sub_product_id', '=', 'sub_products.id')
            ->leftJoin('stock_movements', function ($join) use ($creatorId) {
                $join->on('stock_movements.pos_id', '=', 'pos_refunds.pos_id')
                    ->on('stock_movements.sub_product_id', '=', 'pos_products.sub_product_id')
                    ->where('stock_movements.activity', '=', 'Sale via POS')
                    ->where('stock_movements.created_by', '=', $creatorId);
            })
            ->where('pos_refunds.created_by', $creatorId)
            ->whereNull('pos.deleted_at');

        if ($request) {
            if ($request->filled('q')) {
                $q = trim($request->q);
                $refundQuery->where(function ($subQ) use ($q) {
                    $subQ->where('product_services.name', 'like', "%{$q}%")
                        ->orWhere('product_services.sku', 'like', "%{$q}%")
                        ->orWhere('brands.name', 'like', "%{$q}%")
                        ->orWhere('sub_brands.name', 'like', "%{$q}%");
                });
            }

            if ($request->filled('category_id')) {
                $refundQuery->where('product_services.category_id', $request->category_id);
            }

            if ($request->filled('product_id')) {
                $refundQuery->where('product_services.id', $request->product_id);
            }

            if ($request->filled('warehouse_id')) {
                $refundQuery->where('pos.warehouse_id', $request->warehouse_id);
            }

            if ($request->filled('date_from')) {
                $refundQuery->whereDate('pos_refunds.created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $refundQuery->whereDate('pos_refunds.created_at', '<=', $request->date_to);
            }
        }

        // Sell refund must come from pos_refunds.total_amount (one value per refund header).
        // Since base query is item-level, aggregate refund headers first to avoid double-counting.
        $refundHeaderSubQuery = (clone $refundQuery)
            ->select('pos_refunds.id', 'pos_refunds.total_amount')
            ->groupBy('pos_refunds.id', 'pos_refunds.total_amount');

        $totalSellRefund = DB::query()
            ->fromSub($refundHeaderSubQuery, 'refund_headers')
            ->selectRaw('COALESCE(SUM(refund_headers.total_amount), 0) as total_sell_refund')
            ->value('total_sell_refund');

        $totalCostRefund = (clone $refundQuery)
            ->selectRaw('COALESCE(SUM(COALESCE(stock_movements.avg_cost, sub_products.purchase_price, 0) * COALESCE(pos_refund_items.quantity, 0)), 0) as total_cost_refund')
            ->value('total_cost_refund');

        return [
            'total_sell_refund' => (float) (($totalSellRefund ?? 0) / 1.05),
            'total_cost_refund' => (float) ($totalCostRefund ?? 0),
        ];
    }

    /**
     * Get Sold Sub-Products Details
     * Shows which sub-products were sold and in which POS/Invoice
     */
    public function sellOverviewDetails(Request $request)
    {
        if (!\Auth::user()->can('show account dashboard')) {
            return response()->json(['error' => __('Permission Denied.')], 403);
        }

        $productId = $request->get('product_id');
        $warehouseId = $request->get('warehouse_id');
        $creatorId = \Auth::user()->creatorId();

        if (!$productId) {
            return response()->json(['error' => __('Product ID is required')], 400);
        }

        // Get POS products sold for this product
        // Include combo_price to calculate actual selling price
        // Join with stock_movements to get avg_cost for POS sales
        $posProducts = \App\Models\PosProduct::join('pos', 'pos_products.pos_id', '=', 'pos.id')
            ->join('sub_products', 'pos_products.sub_product_id', '=', 'sub_products.id')
            ->leftJoin('warehouses', 'pos.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('stock_movements', function($join) use ($creatorId) {
                $join->on('stock_movements.pos_id', '=', 'pos.id')
                     ->on('stock_movements.sub_product_id', '=', 'pos_products.sub_product_id')
                     ->where('stock_movements.activity', '=', 'Sale via POS')
                     ->where('stock_movements.created_by', '=', $creatorId);
            })
            ->where('pos_products.product_id', $productId)
            ->where('pos.created_by', $creatorId)
            ->whereNull('pos.deleted_at')
            ->select(
                'pos_products.id',
                'pos_products.sub_product_id',
                'pos_products.quantity',
                'pos_products.price',
                'pos_products.discount',
                'pos_products.compo_id',
                'pos_products.combo_price',
                'pos.id as pos_id',
                'pos.pos_id as pos_number',
                'pos.pos_date',
                'sub_products.chassis_no',
                'sub_products.purchase_price',
                'stock_movements.avg_cost as pos_avg_cost',
                'warehouses.name as warehouse_name',
                DB::raw("'POS' as source_type"),
                // Calculate actual selling price: use combo_price if combo exists, otherwise price, then apply discount
                DB::raw("CASE 
                    WHEN (pos_products.compo_id IS NOT NULL AND pos_products.compo_id != 0 AND pos_products.compo_id != '0' AND pos_products.combo_price IS NOT NULL)
                    THEN (pos_products.combo_price - (pos_products.combo_price * COALESCE(pos_products.discount, 0) / 100))
                    ELSE (pos_products.price - (pos_products.price * COALESCE(pos_products.discount, 0) / 100))
                END as actual_price")
            );

        if ($warehouseId) {
            $posProducts->where('pos.warehouse_id', $warehouseId);
        }

        // Date range filter for POS
        if ($request->filled('date_from')) {
            $posProducts->where('pos.pos_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $posProducts->where('pos.pos_date', '<=', $request->date_to);
        }

        $posProducts = $posProducts->get();

        // Get Invoice products sold for this product
        // Note: Invoices don't have warehouse_id - warehouse comes from sub_products
        $invoiceProducts = \App\Models\InvoiceProduct::join('invoices', 'invoice_products.invoice_id', '=', 'invoices.id')
            ->join('sub_products', 'invoice_products.sub_product_id', '=', 'sub_products.id')
            ->leftJoin('warehouses', 'sub_products.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('stock_movements', function($join) use ($creatorId) {
                $join->on('stock_movements.invoice_id', '=', 'invoices.id')
                     ->on('stock_movements.sub_product_id', '=', 'invoice_products.sub_product_id')
                     ->where('stock_movements.activity', '=', 'Sale via Invoice')
                     ->where('stock_movements.created_by', '=', $creatorId);
            })
            ->where('invoice_products.product_id', $productId)
            ->where('invoices.created_by', $creatorId)
            ->whereNull('invoice_products.deleted_at')
            ->whereNull('invoices.deleted_at')
            ->select(
                'invoice_products.id',
                'invoice_products.sub_product_id',
                'invoice_products.quantity',
                'invoice_products.price',
                'invoice_products.discount',
                'invoices.id as invoice_id',
                'invoices.invoice_id as invoice_number',
                'invoices.issue_date as invoice_date',
                'sub_products.chassis_no',
                'sub_products.purchase_price',
                'stock_movements.avg_cost as invoice_avg_cost',
                'warehouses.name as warehouse_name',
                DB::raw("'Invoice' as source_type"),
                // Calculate actual selling price: price after discount
                DB::raw("(invoice_products.price - (invoice_products.price * COALESCE(invoice_products.discount, 0) / 100)) as actual_price")
            );

        if ($warehouseId) {
            $invoiceProducts->where('sub_products.warehouse_id', $warehouseId);
        }

        // Date range filter for Invoices
        if ($request->filled('date_from')) {
            $invoiceProducts->where('invoices.issue_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $invoiceProducts->where('invoices.issue_date', '<=', $request->date_to);
        }

        $invoiceProducts = $invoiceProducts->get();

        // Combine and sort by date
        $allProducts = $posProducts->merge($invoiceProducts)->sortByDesc(function($item) {
            return $item->pos_date ?? $item->invoice_date ?? now();
        });

        // Sum(cost × qty) with known cost; divide by total sold qty (all rows) to match main Sell Overview Avg Cost column
        $totalCost = 0;
        $totalSellQtyAll = 0;

        foreach ($allProducts as $product) {
            $qty = isset($product->quantity) ? (float) $product->quantity : 0;
            if ($qty <= 0) {
                continue;
            }
            $totalSellQtyAll += $qty;

            $cost = 0;
            if ($product->source_type == 'POS') {
                if (isset($product->pos_avg_cost) && $product->pos_avg_cost > 0) {
                    $cost = $product->pos_avg_cost;
                } elseif (isset($product->purchase_price) && $product->purchase_price > 0) {
                    $cost = $product->purchase_price;
                }
            } else {
                if (isset($product->invoice_avg_cost) && $product->invoice_avg_cost > 0) {
                    $cost = $product->invoice_avg_cost;
                } elseif (isset($product->purchase_price) && $product->purchase_price > 0) {
                    $cost = $product->purchase_price;
                }
            }

            if ($cost > 0) {
                $totalCost += ($cost * $qty);
            }
        }

        $avgCost = ($totalSellQtyAll > 0) ? ($totalCost / $totalSellQtyAll) : 0;

        $html = view('dashboard.sell-overview-details', [
            'products' => $allProducts,
            'productId' => $productId,
            'avgCost' => $avgCost
        ])->render();

        return response()->json(['html' => $html]);
    }

    /**
     * Export Sell Overview to Excel
     */
    public function sellOverviewExport(Request $request)
    {
        if (!\Auth::user()->can('show account dashboard')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        try {
            // Prepare filters for export (include all filter parameters)
            $filters = $request->only(['q', 'category_id', 'product_id', 'warehouse_id', 'date_from', 'date_to']);

            // Remove empty filters
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            if (empty($filters['date_from']) && empty($filters['date_to'])) {
                $filters = array_merge($filters, $this->defaultSellOverviewMonthDateRange());
            }

            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            $name = 'sell_overview_' . date('Y-m-d_H-i-s');
            $data = \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\SellOverviewExport(\Auth::user()->creatorId(), $filters), 
                $name . '.xlsx'
            );

            return $data;
        } catch (\Exception $e) {
            \Log::error('Sell Overview export failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => \Auth::user()->creatorId()
            ]);

            return redirect()->back()->with('error', __('Export failed: ') . $e->getMessage());
        }
    }

    private function categoryChartPaletteColor(int $index): string
    {
        $palette = [
            '#6366f1', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6',
            '#06b6d4', '#ec4899', '#84cc16', '#f97316', '#14b8a6',
            '#a855f7', '#0ea5e9', '#eab308', '#64748b',
        ];

        return $palette[$index % count($palette)];
    }

}
