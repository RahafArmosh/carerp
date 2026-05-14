<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDailyLog;
use App\Models\EmployeeDailyLogTask;
use App\Models\TaskMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeDailyTaskController extends Controller
{
    protected function canAccessFeature(): bool
    {
        $user = Auth::user();
        if ($user->can('manage employee')) {
            return true;
        }
        if ($user->can('manage daily task log')) {
            return true;
        }
        if (($user->type === 'Employee' || $user->type === 'Sales') && $this->employeeForAuthUser()) {
            return true;
        }

        return false;
    }

    protected function employeeForAuthUser(): ?Employee
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        return Employee::where('user_id', $user->id)->first();
    }

    protected function subordinateEmployeeIds(Employee $manager): array
    {
        return Employee::where('manager_id', $manager->id)->where('created_by', $manager->created_by)->pluck('id')->all();
    }

    protected function isManager(Employee $employee): bool
    {
        return Employee::where('manager_id', $employee->id)->exists();
    }

    protected function canViewLog(EmployeeDailyLog $log, ?Employee $viewer, bool $isCompanyAdmin): bool
    {
        if ($log->created_by !== Auth::user()->creatorId()) {
            return false;
        }
        if ($isCompanyAdmin) {
            return true;
        }
        if (!$viewer) {
            return false;
        }
        if ((int) $log->employee_id === (int) $viewer->id) {
            return true;
        }
        if ($this->isManager($viewer) && (int) $log->manager_id === (int) $viewer->id) {
            return true;
        }
        if ($this->isManager($viewer)) {
            return in_array((int) $log->employee_id, $this->subordinateEmployeeIds($viewer), true);
        }

        return false;
    }

    protected function canModifyLog(EmployeeDailyLog $log, ?Employee $viewer, bool $isCompanyAdmin): bool
    {
        if ($log->created_by !== Auth::user()->creatorId()) {
            return false;
        }
        if ($isCompanyAdmin) {
            return true;
        }
        if (!$viewer) {
            return false;
        }

        return (int) $log->employee_id === (int) $viewer->id;
    }

    public function index(Request $request)
    {
        if (!$this->canAccessFeature()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $creatorId = Auth::user()->creatorId();
        $me = $this->employeeForAuthUser();
        $isCompanyAdmin = Auth::user()->can('manage employee') && Auth::user()->type !== 'Employee';

        $query = EmployeeDailyLog::query()
            ->with(['employee', 'department', 'tasks'])
            ->forTenant($creatorId);

        if ($isCompanyAdmin) {
            if ($request->filled('employee_id')) {
                $query->where('employee_id', (int) $request->employee_id);
            }
            if ($request->filled('department_id')) {
                $query->where('department_id', (int) $request->department_id);
            }
        } elseif ($me) {
            $ids = [(int) $me->id];
            if ($this->isManager($me)) {
                $ids = array_merge($ids, $this->subordinateEmployeeIds($me));
            }
            $query->whereIn('employee_id', array_unique($ids));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $logs = $query->orderByDesc('log_date')->orderByDesc('id')->paginate(20)->withQueryString();

        $employeesForFilter = collect();
        $departmentsForFilter = collect();
        if ($isCompanyAdmin) {
            $employeesForFilter = Employee::where('created_by', $creatorId)->orderBy('name')->pluck('name', 'id');
            $departmentsForFilter = Department::where('created_by', $creatorId)->orderBy('name')->pluck('name', 'id');
        }

        $showEmployeeColumn = $isCompanyAdmin || ($me && $this->isManager($me));

        return view('daily_tasks.index', compact('logs', 'me', 'isCompanyAdmin', 'employeesForFilter', 'departmentsForFilter', 'showEmployeeColumn'));
    }

    /**
     * Task lines report: task type, hours, minutes, grouped/sorted by employee (salesman).
     */
    public function report(Request $request)
    {
        if (!$this->canAccessFeature()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $creatorId = Auth::user()->creatorId();
        $me = $this->employeeForAuthUser();
        $isCompanyAdmin = Auth::user()->can('manage employee') && Auth::user()->type !== 'Employee';

        $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'employee_id' => 'nullable|integer',
            'department_id' => 'nullable|integer',
        ]);

        $fromDate = $request->get('from_date', now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->get('to_date', now()->format('Y-m-d'));
        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        if ($isCompanyAdmin) {
            $empQuery = Employee::where('created_by', $creatorId);
            if ($request->filled('employee_id')) {
                $empQuery->where('id', (int) $request->employee_id);
            }
            if ($request->filled('department_id')) {
                $empQuery->where('department_id', (int) $request->department_id);
            }
            $allowedIds = $empQuery->pluck('id')->all();
        } elseif ($me) {
            $ids = [(int) $me->id];
            if ($this->isManager($me)) {
                $ids = array_merge($ids, $this->subordinateEmployeeIds($me));
            }
            $allowedIds = array_unique($ids);
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $employeesForFilter = collect();
        $departmentsForFilter = collect();
        if ($isCompanyAdmin) {
            $employeesForFilter = Employee::where('created_by', $creatorId)->orderBy('name')->pluck('name', 'id');
            $departmentsForFilter = Department::where('created_by', $creatorId)->orderBy('name')->pluck('name', 'id');
        }

        $query = EmployeeDailyLogTask::query()
            ->join('employee_daily_logs', 'employee_daily_logs.id', '=', 'employee_daily_log_tasks.employee_daily_log_id')
            ->join('employees', 'employees.id', '=', 'employee_daily_logs.employee_id')
            ->leftJoin('task_masters', 'task_masters.id', '=', 'employee_daily_log_tasks.task_master_id')
            ->where('employee_daily_logs.created_by', $creatorId)
            ->whereDate('employee_daily_logs.log_date', '>=', $fromDate)
            ->whereDate('employee_daily_logs.log_date', '<=', $toDate)
            ->orderBy('employees.name')
            ->orderBy('employee_daily_logs.log_date')
            ->orderBy('employee_daily_log_tasks.display_order')
            ->orderBy('employee_daily_log_tasks.id')
            ->select([
                'employee_daily_log_tasks.hours',
                'employee_daily_log_tasks.minutes',
                'employee_daily_log_tasks.task_name',
                'task_masters.name as master_task_name',
                'employees.name as employee_name',
                'employee_daily_logs.log_date',
            ]);

        if (!empty($allowedIds)) {
            $query->whereIn('employee_daily_logs.employee_id', $allowedIds);
        } else {
            $query->whereRaw('0 = 1');
        }

        $rows = $query->paginate(100)->withQueryString();

        return view('daily_tasks.report', compact(
            'rows',
            'me',
            'isCompanyAdmin',
            'employeesForFilter',
            'departmentsForFilter',
            'fromDate',
            'toDate'
        ));
    }

    /**
     * Chart: X = task, Y = total time (grouped by employee).
     */
    public function chart(Request $request)
    {
        if (!$this->canAccessFeature()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $creatorId = Auth::user()->creatorId();
        $me = $this->employeeForAuthUser();
        $isCompanyAdmin = Auth::user()->can('manage employee') && Auth::user()->type !== 'Employee';

        $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'employee_id' => 'nullable|integer',
            'department_id' => 'nullable|integer',
            'task_master_id' => 'nullable',
        ]);

        $fromDate = $request->get('from_date', now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->get('to_date', now()->format('Y-m-d'));
        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        if ($isCompanyAdmin) {
            $empQuery = Employee::where('created_by', $creatorId);
            if ($request->filled('employee_id')) {
                $empQuery->where('id', (int) $request->employee_id);
            }
            if ($request->filled('department_id')) {
                $empQuery->where('department_id', (int) $request->department_id);
            }
            $allowedIds = $empQuery->pluck('id')->all();
        } elseif ($me) {
            $ids = [(int) $me->id];
            if ($this->isManager($me)) {
                $ids = array_merge($ids, $this->subordinateEmployeeIds($me));
            }
            $allowedIds = array_unique($ids);
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $employeesForFilter = collect();
        $departmentsForFilter = collect();
        if ($isCompanyAdmin) {
            $employeesForFilter = Employee::where('created_by', $creatorId)->orderBy('name')->pluck('name', 'id');
            $departmentsForFilter = Department::where('created_by', $creatorId)->orderBy('name')->pluck('name', 'id');
        }

        $taskMasterId = $request->get('task_master_id');
        $tasksForFilter = collect();
        if ($isCompanyAdmin && $request->filled('department_id')) {
            $taskOptionsQuery = EmployeeDailyLogTask::query()
                ->join('employee_daily_logs', 'employee_daily_logs.id', '=', 'employee_daily_log_tasks.employee_daily_log_id')
                ->join('employees', 'employees.id', '=', 'employee_daily_logs.employee_id')
                ->leftJoin('task_masters', 'task_masters.id', '=', 'employee_daily_log_tasks.task_master_id')
                ->where('employee_daily_logs.created_by', $creatorId)
                ->where('employee_daily_logs.department_id', (int) $request->department_id)
                ->whereDate('employee_daily_logs.log_date', '>=', $fromDate)
                ->whereDate('employee_daily_logs.log_date', '<=', $toDate);

            if (!empty($allowedIds)) {
                $taskOptionsQuery->whereIn('employee_daily_logs.employee_id', $allowedIds);
            } else {
                $taskOptionsQuery->whereRaw('0 = 1');
            }

            $taskOptions = $taskOptionsQuery
                ->select([
                    'employee_daily_log_tasks.task_master_id as task_master_id',
                    DB::raw("COALESCE(task_masters.name, employee_daily_log_tasks.task_name, 'Task') as task_label"),
                ])
                ->groupBy('employee_daily_log_tasks.task_master_id', 'task_label')
                ->orderBy('task_label')
                ->get();

            $tasksForFilter = $taskOptions->mapWithKeys(function ($row) {
                $key = $row->task_master_id ? (string) $row->task_master_id : 'custom';
                return [$key => (string) $row->task_label];
            });
        }

        $query = EmployeeDailyLogTask::query()
            ->join('employee_daily_logs', 'employee_daily_logs.id', '=', 'employee_daily_log_tasks.employee_daily_log_id')
            ->join('employees', 'employees.id', '=', 'employee_daily_logs.employee_id')
            ->leftJoin('task_masters', 'task_masters.id', '=', 'employee_daily_log_tasks.task_master_id')
            ->where('employee_daily_logs.created_by', $creatorId)
            ->whereDate('employee_daily_logs.log_date', '>=', $fromDate)
            ->whereDate('employee_daily_logs.log_date', '<=', $toDate)
            ->select([
                'employee_daily_logs.employee_id as employee_id',
                'employees.name as employee_name',
                DB::raw("COALESCE(task_masters.name, employee_daily_log_tasks.task_name, 'Task') as task_label"),
                DB::raw('SUM(employee_daily_log_tasks.duration_minutes) as total_minutes'),
            ])
            ->groupBy('employee_daily_logs.employee_id', 'employees.name', 'task_label')
            ->orderBy('employees.name')
            ->orderBy('task_label');

        if ($isCompanyAdmin && $request->filled('department_id')) {
            $query->where('employee_daily_logs.department_id', (int) $request->department_id);
        }

        if ($taskMasterId !== null && $taskMasterId !== '') {
            if ((string) $taskMasterId === 'custom') {
                $query->whereNull('employee_daily_log_tasks.task_master_id');
            } else {
                $query->where('employee_daily_log_tasks.task_master_id', (int) $taskMasterId);
            }
        }

        if (!empty($allowedIds)) {
            $query->whereIn('employee_daily_logs.employee_id', $allowedIds);
        } else {
            $query->whereRaw('0 = 1');
        }

        $rows = $query->get();

        $employeeNames = $rows->pluck('employee_name')->unique()->values();
        $employees = $employeeNames->all();

        $taskLabels = $rows->pluck('task_label')->unique()->values()->all();

        // Build a map: [task_label][employee_name] = hours
        $map = [];
        foreach ($rows as $r) {
            $emp = (string) $r->employee_name;
            $task = (string) $r->task_label;
            $hours = round(((int) $r->total_minutes) / 60, 2);
            $map[$task][$emp] = ($map[$task][$emp] ?? 0) + $hours;
        }

        $datasets = [];
        foreach ($employees as $emp) {
            $data = [];
            foreach ($taskLabels as $task) {
                $data[] = (float) (($map[$task][$emp] ?? 0));
            }
            $datasets[] = [
                'label' => $emp,
                'data' => $data,
            ];
        }

        return view('daily_tasks.chart', compact(
            'me',
            'isCompanyAdmin',
            'employeesForFilter',
            'departmentsForFilter',
            'tasksForFilter',
            'fromDate',
            'toDate',
            'taskLabels',
            'datasets'
        ));
    }

    public function create(Request $request)
    {
        if (!$this->canAccessFeature()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $creatorId = Auth::user()->creatorId();
        $me = $this->employeeForAuthUser();
        $isCompanyAdmin = Auth::user()->can('manage employee') && Auth::user()->type !== 'Employee';

        if (!$isCompanyAdmin && !$me) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $targetEmployee = $me;
        if ($isCompanyAdmin) {
            $eid = $request->get('employee_id');
            if ($eid) {
                $targetEmployee = Employee::where('id', $eid)->where('created_by', $creatorId)->first();
            }
            if (!$targetEmployee) {
                $targetEmployee = Employee::where('created_by', $creatorId)->orderBy('name')->first();
            }
        }

        if (!$targetEmployee) {
            return redirect()->route('daily-tasks.index')->with('error', __('No employees found. Create an employee first.'));
        }

        $taskMasters = TaskMaster::forDailyLogDropdown($targetEmployee)->get()->pluck('name', 'id');
        $employees = Employee::where('created_by', $creatorId)->orderBy('name')->pluck('name', 'id');

        $logDate = $request->get('log_date', now()->format('Y-m-d'));

        return view('daily_tasks.create', compact('taskMasters', 'targetEmployee', 'isCompanyAdmin', 'employees', 'logDate'));
    }

    /**
     * JSON: Task Master options for daily log, filtered by employee department (and tenant).
     */
    public function taskMastersForEmployee(Request $request)
    {
        if (!$this->canAccessFeature()) {
            return response()->json(['message' => __('Permission denied.')], 403);
        }

        $request->validate([
            'employee_id' => 'required|integer',
        ]);

        $creatorId = Auth::user()->creatorId();
        $employee = Employee::where('id', $request->employee_id)
            ->where('created_by', $creatorId)
            ->firstOrFail();

        $isCompanyAdmin = Auth::user()->can('manage employee') && Auth::user()->type !== 'Employee';
        $me = $this->employeeForAuthUser();

        if ($isCompanyAdmin) {
            // ok
        } elseif ($me && (int) $employee->id === (int) $me->id) {
            // ok
        } elseif ($me && in_array((int) $employee->id, $this->subordinateEmployeeIds($me), true)) {
            // manager viewing subordinate
        } else {
            return response()->json(['message' => __('Permission denied.')], 403);
        }

        $tasks = TaskMaster::forDailyLogDropdown($employee)->get()->pluck('name', 'id');

        return response()->json(['tasks' => $tasks]);
    }

    public function store(Request $request)
    {
        if (!$this->canAccessFeature()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $creatorId = Auth::user()->creatorId();
        $me = $this->employeeForAuthUser();
        $isCompanyAdmin = Auth::user()->can('manage employee') && Auth::user()->type !== 'Employee';

        $targetEmployee = $me;
        if ($isCompanyAdmin) {
            $request->validate([
                'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where(function ($q) use ($creatorId) {
                    $q->where('created_by', $creatorId);
                })],
            ]);
            $targetEmployee = Employee::where('id', $request->employee_id)->where('created_by', $creatorId)->firstOrFail();
        } elseif (!$me) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $request->validate([
            'log_date' => 'required|date',
            'day_notes' => 'nullable|string',
            'tasks' => 'required|array|min:1',
            'tasks.*.task_master_id' => 'nullable|string|max:20',
            'tasks.*.task_name' => 'nullable|string|max:255',
            'tasks.*.hours' => 'required|integer|min:0|max:24',
            'tasks.*.minutes' => 'required|integer|min:0|max:59',
            'tasks.*.notes' => 'nullable|string',
        ]);

        $existingLog = EmployeeDailyLog::where('created_by', $creatorId)
            ->where('employee_id', $targetEmployee->id)
            ->whereDate('log_date', $request->log_date)
            ->first();

        if ($existingLog) {
            return redirect()->back()->withInput()->with(
                'error',
                __('A task log is already created for this user on :date. Please edit the existing task log if you want to update it.', [
                    'date' => $request->log_date,
                ])
            );
        }

        foreach ($request->tasks as $row) {
            $taskMasterRaw = trim((string) ($row['task_master_id'] ?? ''));
            $hasMaster = $taskMasterRaw !== '' && $taskMasterRaw !== 'other';
            $hasName = !empty(trim((string) ($row['task_name'] ?? '')));
            if (!$hasMaster && !$hasName) {
                return redirect()->back()->withInput()->with('error', __('Each row must have a task from the list or a custom task name.'));
            }
        }

        $log = EmployeeDailyLog::firstOrCreateForEmployee($targetEmployee, $request->log_date, $creatorId);
        $log->department_id = $targetEmployee->department_id ?: null;
        $log->manager_id = $targetEmployee->manager_id ?: null;
        $log->day_notes = $request->day_notes;
        $log->save();

        $log->tasks()->delete();

        foreach (array_values($request->tasks) as $order => $row) {
            $taskMasterRaw = trim((string) ($row['task_master_id'] ?? ''));
            $taskMasterId = ctype_digit($taskMasterRaw) ? (int) $taskMasterRaw : null;
            $taskName = $taskMasterId ? '' : trim((string) ($row['task_name'] ?? ''));
            if ($taskMasterId) {
                $valid = TaskMaster::forDailyLogDropdown($targetEmployee)->where('id', $taskMasterId)->exists();
                if (!$valid) {
                    return redirect()->back()->withInput()->with('error', __('Invalid task selected.'));
                }
            }

            EmployeeDailyLogTask::create([
                'employee_daily_log_id' => $log->id,
                'task_master_id' => $taskMasterId,
                'task_name' => $taskName,
                'hours' => $row['hours'],
                'minutes' => $row['minutes'],
                'notes' => $row['notes'] ?? null,
                'display_order' => $order,
                'created_by' => $creatorId,
            ]);
        }

        return redirect()->route('daily-tasks.index')->with('success', __('Daily task log saved.'));
    }

    public function show(EmployeeDailyLog $employee_daily_log)
    {
        if (!$this->canAccessFeature()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $me = $this->employeeForAuthUser();
        $isCompanyAdmin = Auth::user()->can('manage employee') && Auth::user()->type !== 'Employee';

        if (!$this->canViewLog($employee_daily_log, $me, $isCompanyAdmin)) {
            return redirect()->route('daily-tasks.index')->with('error', __('Permission denied.'));
        }

        $employee_daily_log->load(['employee', 'department', 'tasks.taskMaster']);

        return view('daily_tasks.show', [
            'log' => $employee_daily_log,
            'canEdit' => $this->canModifyLog($employee_daily_log, $me, $isCompanyAdmin),
        ]);
    }

    public function edit(EmployeeDailyLog $employee_daily_log)
    {
        if (!$this->canAccessFeature()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $me = $this->employeeForAuthUser();
        $isCompanyAdmin = Auth::user()->can('manage employee') && Auth::user()->type !== 'Employee';

        if (!$this->canModifyLog($employee_daily_log, $me, $isCompanyAdmin)) {
            return redirect()->route('daily-tasks.show', $employee_daily_log)->with('error', __('You can only edit your own daily task log.'));
        }

        $targetEmployee = $employee_daily_log->employee;
        $taskMasters = TaskMaster::forDailyLogDropdown($targetEmployee)->get()->pluck('name', 'id');
        $employees = Employee::where('created_by', Auth::user()->creatorId())->orderBy('name')->pluck('name', 'id');

        $employee_daily_log->load('tasks');

        return view('daily_tasks.edit', [
            'log' => $employee_daily_log,
            'taskMasters' => $taskMasters,
            'targetEmployee' => $targetEmployee,
            'isCompanyAdmin' => $isCompanyAdmin,
            'employees' => $employees,
        ]);
    }

    public function update(Request $request, EmployeeDailyLog $employee_daily_log)
    {
        if (!$this->canAccessFeature()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $me = $this->employeeForAuthUser();
        $isCompanyAdmin = Auth::user()->can('manage employee') && Auth::user()->type !== 'Employee';

        if (!$this->canModifyLog($employee_daily_log, $me, $isCompanyAdmin)) {
            return redirect()->route('daily-tasks.show', $employee_daily_log)->with('error', __('You can only edit your own daily task log.'));
        }

        $targetEmployee = $employee_daily_log->employee;
        $creatorId = Auth::user()->creatorId();

        if ($isCompanyAdmin && $request->filled('employee_id')) {
            $request->validate([
                'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where(function ($q) use ($creatorId) {
                    $q->where('created_by', $creatorId);
                })],
            ]);
            $targetEmployee = Employee::where('id', $request->employee_id)->where('created_by', $creatorId)->firstOrFail();
        }

        if ($isCompanyAdmin && $targetEmployee->id !== $employee_daily_log->employee_id) {
            $duplicate = EmployeeDailyLog::where('employee_id', $targetEmployee->id)
                ->whereDate('log_date', $request->log_date)
                ->where('id', '!=', $employee_daily_log->id)
                ->exists();
            if ($duplicate) {
                return redirect()->back()->withInput()->with('error', __('This employee already has a log for that date.'));
            }
        }

        $request->validate([
            'log_date' => 'required|date',
            'day_notes' => 'nullable|string',
            'tasks' => 'required|array|min:1',
            'tasks.*.task_master_id' => 'nullable|string|max:20',
            'tasks.*.task_name' => 'nullable|string|max:255',
            'tasks.*.hours' => 'required|integer|min:0|max:24',
            'tasks.*.minutes' => 'required|integer|min:0|max:59',
            'tasks.*.notes' => 'nullable|string',
        ]);

        foreach ($request->tasks as $row) {
            $taskMasterRaw = trim((string) ($row['task_master_id'] ?? ''));
            $hasMaster = $taskMasterRaw !== '' && $taskMasterRaw !== 'other';
            $hasName = !empty(trim((string) ($row['task_name'] ?? '')));
            if (!$hasMaster && !$hasName) {
                return redirect()->back()->withInput()->with('error', __('Each row must have a task from the list or a custom task name.'));
            }
        }

        $employee_daily_log->log_date = $request->log_date;
        $employee_daily_log->employee_id = $targetEmployee->id;
        $employee_daily_log->department_id = $targetEmployee->department_id ?: null;
        $employee_daily_log->manager_id = $targetEmployee->manager_id ?: null;
        $employee_daily_log->day_notes = $request->day_notes;
        $employee_daily_log->save();

        $employee_daily_log->tasks()->delete();

        foreach (array_values($request->tasks) as $order => $row) {
            $taskMasterRaw = trim((string) ($row['task_master_id'] ?? ''));
            $taskMasterId = ctype_digit($taskMasterRaw) ? (int) $taskMasterRaw : null;
            $taskName = $taskMasterId ? '' : trim((string) ($row['task_name'] ?? ''));
            if ($taskMasterId) {
                $valid = TaskMaster::forDailyLogDropdown($targetEmployee)->where('id', $taskMasterId)->exists();
                if (!$valid) {
                    return redirect()->back()->withInput()->with('error', __('Invalid task selected.'));
                }
            }

            EmployeeDailyLogTask::create([
                'employee_daily_log_id' => $employee_daily_log->id,
                'task_master_id' => $taskMasterId,
                'task_name' => $taskName,
                'hours' => $row['hours'],
                'minutes' => $row['minutes'],
                'notes' => $row['notes'] ?? null,
                'display_order' => $order,
                'created_by' => $creatorId,
            ]);
        }

        return redirect()->route('daily-tasks.index')->with('success', __('Daily task log updated.'));
    }

    public function destroy(EmployeeDailyLog $employee_daily_log)
    {
        if (!$this->canAccessFeature()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $me = $this->employeeForAuthUser();
        $isCompanyAdmin = Auth::user()->can('manage employee') && Auth::user()->type !== 'Employee';

        if (!$this->canModifyLog($employee_daily_log, $me, $isCompanyAdmin)) {
            return redirect()->route('daily-tasks.show', $employee_daily_log)->with('error', __('You can only delete your own daily task log.'));
        }

        $employee_daily_log->delete();

        return redirect()->route('daily-tasks.index')->with('success', __('Daily task log deleted.'));
    }
}
