<?php

namespace App\Http\Controllers;

use App\Imports\AttendanceImport;
use App\Models\AttendanceEmployee;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\IpRestrict;
use App\Models\macRestrict;
use App\Models\User;
use App\Models\Leave;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use DB;
use DateTime;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceExport;

class AttendanceEmployeeController extends Controller
{
    /**
     * HR users see the same company-wide attendance list as company/client (tenant-scoped).
     */
    private function userHasHrRole($user): bool
    {
        if (! method_exists($user, 'getRoleNames')) {
            return false;
        }

        $roleNames = $user->getRoleNames()
            ->map(function ($name) {
                return strtolower((string) $name);
            })
            ->all();

        return in_array('hr', $roleNames, true);
    }

    private function attendanceBelongsToCurrentTenant(AttendanceEmployee $attendance, $user): bool
    {
        return Employee::query()
            ->where('id', $attendance->employee_id)
            ->where('created_by', $user->creatorId())
            ->exists();
    }

    private function userCanViewAllAttendanceRecords($user): bool
    {
        if (in_array($user->type, ['client', 'company'], true)) {
            return true;
        }
        if (strtolower((string) $user->type) === 'hr') {
            return true;
        }

        return $this->userHasHrRole($user);
    }

    /**
     * Apply date filter: monthly range, specific day, or default to today.
     */
    private function applyAttendanceDateFilter($query, Request $request): void
    {
        if ($request->type == 'monthly' && !empty($request->month)) {
            list($year, $month) = explode('-', $request->month);
            $start_date = Carbon::create((int) $year, (int) $month, 1)->startOfMonth()->format('Y-m-d');
            $end_date = Carbon::create((int) $year, (int) $month, 1)->copy()->endOfMonth()->format('Y-m-d');
            $query->whereBetween('date', [$start_date, $end_date]);
        } elseif ($request->type == 'daily' && !empty($request->date)) {
            $query->where('date', $request->date);
        } else {
            $query->where('date', Carbon::today()->toDateString());
        }
    }

    /**
     * Resolve the date used for missing check-in list.
     * Only daily/default view gets this list (monthly is skipped).
     */
    private function resolveMissingCheckInDate(Request $request): ?string
    {
        if ($request->type === 'monthly') {
            return null;
        }

        return !empty($request->date) ? $request->date : Carbon::today()->toDateString();
    }

    public function index(Request $request)
    {

        if (\Auth::user()->can('manage attendance')) {

            $branch = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $branch->prepend('Select Branch', '');

            $department = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $department->prepend('Select Department', '');
            $perPage = 20;
            if (!$this->userCanViewAllAttendanceRecords(\Auth::user())) {

                $emp = !empty(\Auth::user()->employee) ? \Auth::user()->employee->id : 0;

                $attendanceEmployee = AttendanceEmployee::query()
                    ->where('employee_id', $emp)
                    ->with(['employee' => function ($q) {
                        $q->select('id', 'name');
                    }]);

                $this->applyAttendanceDateFilter($attendanceEmployee, $request);

                $attendanceEmployee = $attendanceEmployee->paginate($perPage)->withQueryString();
            } else {

                $employeeIdsQuery = Employee::query()
                    ->select('id')
                    ->where('created_by', \Auth::user()->creatorId());

                if (!empty($request->branch)) {
                    $employeeIdsQuery->where('branch_id', $request->branch);
                }

                if (!empty($request->department)) {
                    $employeeIdsQuery->where('department_id', $request->department);
                }
                if ($request->has('employee') && $request->employee != '') {
                    $employeeIdsQuery->where('user_id', $request->employee);
                }

                $attendanceEmployee = AttendanceEmployee::query()
                    ->whereIn('employee_id', $employeeIdsQuery)
                    ->with(['employee' => function ($q) {
                        $q->select('id', 'name');
                    }]);

                $this->applyAttendanceDateFilter($attendanceEmployee, $request);

                $attendanceEmployee = $attendanceEmployee->paginate($perPage)->withQueryString();
            }

            $missingCheckInEmployees = collect();
            $missingCheckInDate = $this->resolveMissingCheckInDate($request);
            if ($this->userCanViewAllAttendanceRecords(\Auth::user()) && $missingCheckInDate !== null) {
                $missingEmployeesQuery = Employee::query()
                    ->where('created_by', \Auth::user()->creatorId())
                    ->whereIn('branch_id', Branch::query()
                        ->where('created_by', \Auth::user()->creatorId())
                        ->where('name', 'Dubai')
                        ->select('id'));

                if (!empty($request->branch)) {
                    $missingEmployeesQuery->where('branch_id', $request->branch);
                }

                if (!empty($request->department)) {
                    $missingEmployeesQuery->where('department_id', $request->department);
                }

                if ($request->has('employee') && $request->employee != '') {
                    $missingEmployeesQuery->where('user_id', $request->employee);
                }

                $eligibleEmployeeIds = (clone $missingEmployeesQuery)->pluck('id');
                $checkedInEmployeeIds = AttendanceEmployee::query()
                    ->whereDate('date', $missingCheckInDate)
                    ->whereIn('employee_id', $eligibleEmployeeIds)
                    ->whereNotNull('clock_in')
                    ->where('clock_in', '!=', '00:00:00')
                    ->pluck('employee_id');

                $missingCheckInEmployees = (clone $missingEmployeesQuery)
                    ->whereNotIn('id', $checkedInEmployeeIds)
                    ->orderBy('name')
                    ->get(['id', 'name', 'branch_id']);
            }

            $employees = collect();
            if (\Auth::user()->type != 'employee' || $this->userHasHrRole(\Auth::user())) {
                $employees = Employee::query()
                    ->where('created_by', \Auth::user()->creatorId())
                    ->orderBy('name')
                    ->get(['id', 'name', 'user_id']);
            }

            $showAttendanceFilters = \Auth::user()->type != 'employee'
                || $this->userHasHrRole(\Auth::user());

            return view('attendance.index', compact('attendanceEmployee', 'branch', 'department', 'employees', 'showAttendanceFilters', 'missingCheckInEmployees', 'missingCheckInDate'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Update only the note field (used from attendance index).
     */
    public function updateNote(Request $request, AttendanceEmployee $attendance)
    {
        $user = \Auth::user();
        $isHrUser = strtolower((string) $user->type) === 'hr' || $this->userHasHrRole($user);

        if (!$user->can('edit attendance') && $user->type !== 'company' && !$isHrUser) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if (!$this->userCanModifyAttendanceRecord($attendance)) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $request->validate([
            'note' => 'nullable|string|max:2000',
        ]);

        $attendance->note = $request->input('note');
        $attendance->save();

        return redirect()->back()->with('success', __('Note saved.'));
    }

    /**
     * Whether the current user may change this attendance row (note / edits).
     */
    private function userCanModifyAttendanceRecord(AttendanceEmployee $attendance): bool
    {
        $user = \Auth::user();

        // Company users are allowed to maintain attendance notes from index.
        if ($user->type === 'company') {
            return true;
        }

        // HR users can maintain notes tenant-wide.
        if (strtolower((string) $user->type) === 'hr' || $this->userHasHrRole($user)) {
            return $this->attendanceBelongsToCurrentTenant($attendance, $user);
        }

        if ($user->type === 'client') {
            return $this->attendanceBelongsToCurrentTenant($attendance, $user);
        }

        $empId = !empty($user->employee) ? (int) $user->employee->id : 0;

        return $empId > 0 && (int) $attendance->employee_id === $empId;
    }

    public function create()
    {
        if (\Auth::user()->can('create attendance')) {
            $employees = User::where('created_by', '=', Auth::user()->creatorId())->where('type', '=', "employee")->get()->pluck('name', 'id');

            return view('attendance.create', compact('employees'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create attendance')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'employee_id' => 'required',
                    'date' => 'required',
                    'clock_in' => 'required',
                    'clock_out' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $startTime = Utility::getValByName('company_start_time');
            $endTime = Utility::getValByName('company_end_time');
            $attendance = AttendanceEmployee::where('employee_id', '=', $request->employee_id)->where('date', '=', $request->date)->where('clock_out', '=', '00:00:00')->get()->toArray();
            if ($attendance) {
                return redirect()->route('attendanceemployee.index')->with('error', __('Employee Attendance Already Created.'));
            } else {
                $date = date("Y-m-d");

                $totalLateSeconds = strtotime($request->clock_in) - strtotime($date . $startTime);

                $hours = floor($totalLateSeconds / 3600);
                $mins = floor($totalLateSeconds / 60 % 60);
                $secs = floor($totalLateSeconds % 60);

                $late = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

                //early Leaving
                $totalEarlyLeavingSeconds = strtotime($date . $endTime) - strtotime($request->clock_out);
                $hours = floor($totalEarlyLeavingSeconds / 3600);
                $mins = floor($totalEarlyLeavingSeconds / 60 % 60);
                $secs = floor($totalEarlyLeavingSeconds % 60);
                $earlyLeaving = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

                if (strtotime($request->clock_out) > strtotime($date . $endTime)) {
                    //Overtime
                    $totalOvertimeSeconds = strtotime($request->clock_out) - strtotime($date . $endTime);
                    $hours = floor($totalOvertimeSeconds / 3600);
                    $mins = floor($totalOvertimeSeconds / 60 % 60);
                    $secs = floor($totalOvertimeSeconds % 60);
                    $overtime = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
                } else {
                    $overtime = '00:00:00';
                }

                $employeeAttendance = new AttendanceEmployee();
                $employeeAttendance->employee_id = $request->employee_id;
                $employeeAttendance->date = $request->date;
                $employeeAttendance->status = 'Present';
                $employeeAttendance->clock_in = $request->clock_in . ':00';
                $employeeAttendance->clock_out = $request->clock_out . ':00';
                $employeeAttendance->late = $late;
                $employeeAttendance->early_leaving = $earlyLeaving;
                $employeeAttendance->overtime = $overtime;
                $employeeAttendance->total_rest = '00:00:00';
                $employeeAttendance->created_by = \Auth::user()->creatorId();
                $employeeAttendance->save();

                return redirect()->route('attendanceemployee.index')->with('success', __('Employee attendance successfully created.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show()
    {
        return redirect()->route('attendanceemployee.index');
    }

    public function edit($id)
    {
        if (\Auth::user()->can('edit attendance')) {
            $attendanceEmployee = AttendanceEmployee::where('id', $id)->first();
            $employees = Employee::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            return view('attendance.edit', compact('attendanceEmployee', 'employees'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function update(Request $request, $id)
    {
        //        dd($request->all());

        if (\Auth::user()->type == 'company' || \Auth::user()->type == 'HR') {
            $employeeId = AttendanceEmployee::where('employee_id', $request->employee_id)->first();
            $check = AttendanceEmployee::where('id', $id)->where('employee_id', '=', $request->employee_id)->where('date', $request->date)->first();
            // dd($check->date);

            $startTime = Utility::getValByName('company_start_time');
            $endTime = Utility::getValByName('company_end_time');

            $clockIn = $request->clock_in;
            $clockOut = $request->clock_out;

            if ($clockIn) {
                $status = "present";
            } else {
                $status = "leave";
            }

            $totalLateSeconds = strtotime($clockIn) - strtotime($startTime);

            $hours = floor($totalLateSeconds / 3600);
            $mins = floor($totalLateSeconds / 60 % 60);
            $secs = floor($totalLateSeconds % 60);
            $late = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

            $totalEarlyLeavingSeconds = strtotime($endTime) - strtotime($clockOut);
            $hours = floor($totalEarlyLeavingSeconds / 3600);
            $mins = floor($totalEarlyLeavingSeconds / 60 % 60);
            $secs = floor($totalEarlyLeavingSeconds % 60);
            $earlyLeaving = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

            if (strtotime($clockOut) > strtotime($endTime)) {
                //Overtime
                $totalOvertimeSeconds = strtotime($clockOut) - strtotime($endTime);
                $hours = floor($totalOvertimeSeconds / 3600);
                $mins = floor($totalOvertimeSeconds / 60 % 60);
                $secs = floor($totalOvertimeSeconds % 60);
                $overtime = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
            } else {
                $overtime = '00:00:00';
            }
            // dd($check->date == date('Y-m-d'));
            if ($check->date == date('Y-m-d')) {
                $check->update([
                    'late' => $late,
                    'early_leaving' => ($earlyLeaving > 0) ? $earlyLeaving : '00:00:00',
                    'overtime' => $overtime,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                ]);

                return redirect()->route('attendanceemployee.index')->with('success', __('Employee attendance successfully updated.'));
            } else {
                return redirect()->route('attendanceemployee.index')->with('error', __('you can only update current day attendance.'));
            }
        }

        //    dd($request->all());
        $employeeId = !empty(\Auth::user()->employee) ? \Auth::user()->employee->id : 0;
        $todayAttendance = AttendanceEmployee::where('employee_id', '=', $employeeId)->where('date', date('Y-m-d'))->first();
        //        dd($todayAttendance);
        //        if(!empty($todayAttendance) && $todayAttendance->clock_out == '00:00:00')
        //        if($todayAttendance->clock_out == '00:00:00')
        //        {

        $startTime = Utility::getValByName('company_start_time');
        $endTime = Utility::getValByName('company_end_time');

        if (Auth::user()->type == 'Employee') {

            $date = date("Y-m-d");
            $time = date("H:i:s");
            //                dd($time);
            //early Leaving
            $totalEarlyLeavingSeconds = strtotime($date . $endTime) - time();
            $hours = floor($totalEarlyLeavingSeconds / 3600);
            $mins = floor($totalEarlyLeavingSeconds / 60 % 60);
            $secs = floor($totalEarlyLeavingSeconds % 60);
            $earlyLeaving = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

            if (time() > strtotime($date . $endTime)) {
                //Overtime
                $totalOvertimeSeconds = time() - strtotime($date . $endTime);
                $hours = floor($totalOvertimeSeconds / 3600);
                $mins = floor($totalOvertimeSeconds / 60 % 60);
                $secs = floor($totalOvertimeSeconds % 60);
                $overtime = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
            } else {
                $overtime = '00:00:00';
            }

            //                $attendanceEmployee                = AttendanceEmployee::find($id);
            $attendanceEmployee['clock_out'] = $time;
            $attendanceEmployee['early_leaving'] = $earlyLeaving;
            $attendanceEmployee['overtime'] = $overtime;

            if (!empty($request->date)) {
                $attendanceEmployee['date'] = $request->date;
            }
            //                dd($attendanceEmployee);
            AttendanceEmployee::where('id', $id)->update($attendanceEmployee);
            //                $attendanceEmployee->save();

            return redirect()->route('hrm.dashboard')->with('success', __('Employee successfully clock Out.'));
        } else {
            $date = date("Y-m-d");
            $clockout_time = date("H:i:s");
            //late
            $totalLateSeconds = strtotime($clockout_time) - strtotime($date . $startTime);

            $hours = abs(floor($totalLateSeconds / 3600));
            $mins = abs(floor($totalLateSeconds / 60 % 60));
            $secs = abs(floor($totalLateSeconds % 60));

            $late = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

            //early Leaving
            $totalEarlyLeavingSeconds = strtotime($date . $endTime) - strtotime($clockout_time);
            $hours = floor($totalEarlyLeavingSeconds / 3600);
            $mins = floor($totalEarlyLeavingSeconds / 60 % 60);
            $secs = floor($totalEarlyLeavingSeconds % 60);
            $earlyLeaving = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

            if (strtotime($clockout_time) > strtotime($date . $endTime)) {
                //Overtime
                $totalOvertimeSeconds = strtotime($clockout_time) - strtotime($date . $endTime);
                $hours = floor($totalOvertimeSeconds / 3600);
                $mins = floor($totalOvertimeSeconds / 60 % 60);
                $secs = floor($totalOvertimeSeconds % 60);
                $overtime = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
            } else {
                $overtime = '00:00:00';
            }

            $attendanceEmployee = AttendanceEmployee::find($id);
            // $attendanceEmployee->employee_id   = $employeeId;
            // $attendanceEmployee->date          = $request->date;
            // $attendanceEmployee->clock_in      = $request->clock_in;
            $attendanceEmployee->clock_out = $clockout_time;
            $attendanceEmployee->late = $late;
            $attendanceEmployee->early_leaving = $earlyLeaving;
            $attendanceEmployee->overtime = $overtime;
            $attendanceEmployee->total_rest = '00:00:00';

            $attendanceEmployee->save();

            return redirect()->back()->with('success', __('Employee attendance successfully updated.'));
        }
        //        }
        //        else
        //        {
        //            return redirect()->back()->with('error', __('Employee are not allow multiple time clock in & clock for every day.'));
        //        }
    }

    public function destroy($id)
    {
        if (\Auth::user()->can('delete attendance')) {
            $attendance = AttendanceEmployee::where('id', $id)->first();

            $attendance->delete();

            return redirect()->route('attendanceemployee.index')->with('success', __('Attendance successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function attendance(Request $request)
    {
        $settings = Utility::settings();

        if ($settings['ip_restrict'] == 'on') {
            $userIp = request()->ip();
            $ip = IpRestrict::where('created_by', \Auth::user()->creatorId())->whereIn('ip', [$userIp])->first();
            if (!empty($ip)) {
                return redirect()->back()->with('error', __('This ip is not allowed to clock in & clock out.'));
            }
        }
        $employeeId = !empty(\Auth::user()->employee) ? \Auth::user()->employee->id : 0;

        $todayAttendance = AttendanceEmployee::where('employee_id', '=', $employeeId)->where('date', date('Y-m-d'))->orderBy('id', 'desc')->first();
        //        if(empty($todayAttendance))
        //        {

        $startTime = Utility::getValByName('company_start_time');
        $endTime = Utility::getValByName('company_end_time');

        $attendance = AttendanceEmployee::orderBy('id', 'desc')->where('employee_id', '=', $employeeId)->where('clock_out', '=', '00:00:00')->first();

        if ($attendance != null) {
            $attendance = AttendanceEmployee::find($attendance->id);
            $attendance->clock_out = $endTime;
            $attendance->save();
        }

        $date = date("Y-m-d");
        $time = date("H:i:s");

        if (!empty($todayAttendance)) {
            $startTime = $todayAttendance->clock_out;
        }
        //late

        $totalLateSeconds = time() - strtotime($date . $startTime);

        $hours = abs(floor($totalLateSeconds / 3600));
        $mins = abs(floor($totalLateSeconds / 60 % 60));
        $secs = abs(floor($totalLateSeconds % 60));

        $late = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

        $checkDb = AttendanceEmployee::where('employee_id', '=', \Auth::user()->id)->get()->toArray();

        if (empty($checkDb)) {
            $employeeAttendance = new AttendanceEmployee();
            $employeeAttendance->employee_id = $employeeId;
            $employeeAttendance->date = $date;
            $employeeAttendance->status = 'Present';
            $employeeAttendance->clock_in = $time;
            $employeeAttendance->clock_out = '00:00:00';
            $employeeAttendance->late = $late;
            $employeeAttendance->early_leaving = '00:00:00';
            $employeeAttendance->overtime = '00:00:00';
            $employeeAttendance->total_rest = '00:00:00';
            $employeeAttendance->created_by = \Auth::user()->id;

            $employeeAttendance->save();

            return redirect()->back()->with('success', __('Employee Successfully Clock In.'));
        }
        foreach ($checkDb as $check) {

            $employeeAttendance = new AttendanceEmployee();
            $employeeAttendance->employee_id = $employeeId;
            $employeeAttendance->date = $date;
            $employeeAttendance->status = 'Present';
            $employeeAttendance->clock_in = $time;
            $employeeAttendance->clock_out = '00:00:00';
            $employeeAttendance->late = $late;
            $employeeAttendance->early_leaving = '00:00:00';
            $employeeAttendance->overtime = '00:00:00';
            $employeeAttendance->total_rest = '00:00:00';
            $employeeAttendance->created_by = \Auth::user()->id;

            $employeeAttendance->save();

            return redirect()->back()->with('success', __('Employee Successfully Clock In.'));
        }
        //        }
        //        else
        //        {
        //            return redirect()->back()->with('error', __('Employee are not allow multiple time clock in & clock for every day.'));
        //        }
    }

    public function bulkAttendance(Request $request)
    {
        if (\Auth::user()->can('create attendance')) {

            $branch = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $branch->prepend('Select Branch', '');

            $department = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $department->prepend('Select Department', '');

            $employees = [];
            if (!empty($request->branch) && !empty($request->department)) {
                $employees = Employee::where('created_by', \Auth::user()->creatorId())->where('branch_id', $request->branch)->where('department_id', $request->department)->get();
            } else {
                $employees = Employee::where('created_by', \Auth::user()->creatorId())->where('branch_id', 1)->where('department_id', 1)->get();
            }

            return view('attendance.bulk', compact('employees', 'branch', 'department'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function bulkAttendanceData(Request $request)
    {

        if (\Auth::user()->can('create attendance')) {
            if (!empty($request->branch) && !empty($request->department)) {
                $startTime = Utility::getValByName('company_start_time');
                $endTime = Utility::getValByName('company_end_time');
                $date = $request->date;

                $employees = $request->employee_id;
                $atte = [];

                if (!empty($employees)) {
                    foreach ($employees as $employee) {
                        $present = 'present-' . $employee;
                        $in = 'in-' . $employee;
                        $out = 'out-' . $employee;
                        $atte[] = $present;
                        if ($request->$present == 'on') {

                            $in = date("H:i:s", strtotime($request->$in));
                            $out = date("H:i:s", strtotime($request->$out));

                            $totalLateSeconds = strtotime($in) - strtotime($startTime);

                            $hours = floor($totalLateSeconds / 3600);
                            $mins = floor($totalLateSeconds / 60 % 60);
                            $secs = floor($totalLateSeconds % 60);
                            $late = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

                            //early Leaving
                            $totalEarlyLeavingSeconds = strtotime($endTime) - strtotime($out);
                            $hours = floor($totalEarlyLeavingSeconds / 3600);
                            $mins = floor($totalEarlyLeavingSeconds / 60 % 60);
                            $secs = floor($totalEarlyLeavingSeconds % 60);
                            $earlyLeaving = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

                            if (strtotime($out) > strtotime($endTime)) {
                                //Overtime
                                $totalOvertimeSeconds = strtotime($out) - strtotime($endTime);
                                $hours = floor($totalOvertimeSeconds / 3600);
                                $mins = floor($totalOvertimeSeconds / 60 % 60);
                                $secs = floor($totalOvertimeSeconds % 60);
                                $overtime = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
                            } else {
                                $overtime = '00:00:00';
                            }
                            $attendance = AttendanceEmployee::where('employee_id', '=', $employee)->where('date', '=', $request->date)->first();

                            if (!empty($attendance)) {
                                $employeeAttendance = $attendance;
                            } else {
                                $employeeAttendance = new AttendanceEmployee();
                                $employeeAttendance->employee_id = $employee;
                                $employeeAttendance->created_by = \Auth::user()->creatorId();
                            }
                            $employeeAttendance->date = $request->date;
                            $employeeAttendance->status = 'Present';
                            $employeeAttendance->clock_in = $in;
                            $employeeAttendance->clock_out = $out;
                            $employeeAttendance->late = $late;
                            $employeeAttendance->early_leaving = ($earlyLeaving > 0) ? $earlyLeaving : '00:00:00';
                            $employeeAttendance->overtime = $overtime;
                            $employeeAttendance->total_rest = '00:00:00';
                            $employeeAttendance->save();
                        } else {
                            $attendance = AttendanceEmployee::where('employee_id', '=', $employee)->where('date', '=', $request->date)->first();

                            if (!empty($attendance)) {
                                $employeeAttendance = $attendance;
                            } else {
                                $employeeAttendance = new AttendanceEmployee();
                                $employeeAttendance->employee_id = $employee;
                                $employeeAttendance->created_by = \Auth::user()->creatorId();
                            }

                            $employeeAttendance->status = 'Leave';
                            $employeeAttendance->date = $request->date;
                            $employeeAttendance->clock_in = '00:00:00';
                            $employeeAttendance->clock_out = '00:00:00';
                            $employeeAttendance->late = '00:00:00';
                            $employeeAttendance->early_leaving = '00:00:00';
                            $employeeAttendance->overtime = '00:00:00';
                            $employeeAttendance->total_rest = '00:00:00';
                            $employeeAttendance->save();
                        }
                    }
                } else {
                    return redirect()->back()->with('error', __('Employee not found.'));
                }

                return redirect()->back()->with('success', __('Employee attendance successfully created.'));
            } else {
                return redirect()->back()->with('error', __('Branch & department field required.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    //for attendance employee report
    public function importFile()
    {
        return view('attendance.import');
    }

    public function import(Request $request)
    {
        $rules = [
            'file' => 'required|mimes:csv,txt,xlsx',
        ];
        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $attendance = (new AttendanceImport())->toArray(request()->file('file'))[0];

        $email_data = [];
        foreach ($attendance as $key => $employee) {
            if ($key != 0) {
                echo "<pre>";
                if ($employee != null && Employee::where('email', $employee[0])->where('created_by', \Auth::user()->creatorId())->exists()) {
                    $email = $employee[0];
                } else {
                    $email_data[] = $employee[0];
                }
            }
        }
        $totalattendance = count($attendance) - 1;
        $errorArray = [];

        $startTime = Utility::getValByName('company_start_time');
        $endTime = Utility::getValByName('company_end_time');

        if (!empty($attendanceData)) {
            $errorArray[] = $attendanceData;
        } else {
            foreach ($attendance as $key => $value) {
                if ($key != 0) {
                    $employeeData = Employee::where('email', $value[0])->where('created_by', \Auth::user()->creatorId())->first();
                    // $employeeId = 0;
                    if (!empty($employeeData)) {
                        $employeeId = $employeeData->id;

                        $clockIn = $value[2];
                        $clockOut = $value[3];

                        if ($clockIn) {
                            $status = "present";
                        } else {
                            $status = "leave";
                        }

                        $totalLateSeconds = strtotime($clockIn) - strtotime($startTime);

                        $hours = floor($totalLateSeconds / 3600);
                        $mins = floor($totalLateSeconds / 60 % 60);
                        $secs = floor($totalLateSeconds % 60);
                        $late = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

                        $totalEarlyLeavingSeconds = strtotime($endTime) - strtotime($clockOut);
                        $hours = floor($totalEarlyLeavingSeconds / 3600);
                        $mins = floor($totalEarlyLeavingSeconds / 60 % 60);
                        $secs = floor($totalEarlyLeavingSeconds % 60);
                        $earlyLeaving = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

                        if (strtotime($clockOut) > strtotime($endTime)) {
                            //Overtime
                            $totalOvertimeSeconds = strtotime($clockOut) - strtotime($endTime);
                            $hours = floor($totalOvertimeSeconds / 3600);
                            $mins = floor($totalOvertimeSeconds / 60 % 60);
                            $secs = floor($totalOvertimeSeconds % 60);
                            $overtime = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
                        } else {
                            $overtime = '00:00:00';
                        }

                        $check = AttendanceEmployee::where('employee_id', $employeeId)->where('date', $value[1])->first();
                        if ($check) {
                            $check->update([
                                'late' => $late,
                                'early_leaving' => ($earlyLeaving > 0) ? $earlyLeaving : '00:00:00',
                                'overtime' => $overtime,
                                'clock_in' => $value[2],
                                'clock_out' => $value[3],
                            ]);
                        } else {
                            $time_sheet = AttendanceEmployee::create([
                                'employee_id' => $employeeId,
                                'date' => $value[1],
                                'status' => $status,
                                'late' => $late,
                                'early_leaving' => ($earlyLeaving > 0) ? $earlyLeaving : '00:00:00',
                                'overtime' => $overtime,
                                'clock_in' => $value[2],
                                'clock_out' => $value[3],
                                'created_by' => \Auth::user()->id,
                            ]);
                        }
                    }
                } else {
                    $email_data = implode(' And ', $email_data);
                }
            }

            if (!empty($email_data)) {
                return redirect()->back()->with('status', 'This record is not import. ' . '</br>' . $email_data);
            } else {
                if (empty($errorArray)) {
                    $data['status'] = 'success';
                    $data['msg'] = __('Record successfully imported');
                } else {

                    $data['status'] = 'error';
                    $data['msg'] = count($errorArray) . ' ' . __('Record imported fail out of' . ' ' . $totalattendance . ' ' . 'record');

                    foreach ($errorArray as $errorData) {
                        $errorRecord[] = implode(',', $errorData->toArray());
                    }

                    \Session::put('errorArray', $errorRecord);
                }

                return redirect()->back()->with($data['status'], $data['msg']);
            }
        }
    }

    public function checkIn(Request $request)
    {
        $request->validate([
            'latitudeIn' => 'required',
            'longitudeIn' => 'required',
            'locationIn' => 'required',
        ]);

        $userMac = $request->mac;
        $mac = macRestrict::where('user_id', \Auth::id())->whereIn('mac', [$userMac])->first();
        // if ($mac == null || $mac->mac !== $request->mac) {
        //     return  response()->json(['error' => 'This mac is not allowed to clock in'], 401);
        // }
        try {
            $startTime = Employee::where('user_id', Auth::id())->first()->startTime; //Utility::getValByName('company_start_time');

            // $date = date("Y-m-d");

            //late

            // $totalLateSeconds = time() - strtotime($date . $startTime);

            // $hours = floor($totalLateSeconds / 3600);
            // $mins = abs(floor($totalLateSeconds / 60 % 60));
            // $secs = abs(floor($totalLateSeconds % 60));
            // $hours = $hours % 12;
            // $hours = $hours ? $hours : 12; // Handle midnight case
            // $late = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

            $now = Carbon::now()->format('h:i A'); // Current time in 12-hour format with AM/PM
            $start = Carbon::createFromFormat('h:i A', $startTime)->setDate(Carbon::now()->year, Carbon::now()->month, Carbon::now()->day);

            // Convert both times to Carbon instances
            $nowTime = Carbon::createFromFormat('h:i A', $now);
            $startTime = Carbon::createFromFormat('h:i A', $startTime)->setDate($nowTime->year, $nowTime->month, $nowTime->day);

            // Calculate the difference
            $diffInHours = $startTime->diffInHours($nowTime);
            $diffInMinutes = $startTime->diffInMinutes($nowTime) % 60; // Get the remaining minutes

            $late = sprintf('%02d:%02d:%02s', $diffInHours, $diffInMinutes, '00');
            $employeeAttendance = new AttendanceEmployee();
            $employeeAttendance->employee_id = Employee::where('user_id', Auth::id())->first()->id;
            $employeeAttendance->date = now()->toDateString();
            $employeeAttendance->status = $request->status;
            $employeeAttendance->clock_in = now()->toTimeString();
            $employeeAttendance->clock_out = '00:00:00';
            $employeeAttendance->late = $late;
            $employeeAttendance->early_leaving = '00:00:00';
            $employeeAttendance->overtime = '00:00:00';
            $employeeAttendance->total_rest = '00:00:00';
            $employeeAttendance->latitudeIn = $request->latitudeIn;
            $employeeAttendance->longitudeIn = $request->longitudeIn;
            $employeeAttendance->locationIn = $request->locationIn;
            $employeeAttendance->created_by = \Auth::user()->id;

            $employeeAttendance->save();
            return  response()->json(['success' => true, 'message' => 'Check-in successfully'], 201);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'error' => $e->getMessage()
                ],
            );
        }
    }

    /**
     * Upsert attendance by employee device + date. Resolves employee via employee_device_id (same as device check-in/out).
     * On **create**, status, clock_in, and clock_out are required. late, early_leaving, overtime, and total_rest are **computed** server-side.
     * On **update**, only non-empty request fields are applied (numeric 0 is allowed for latitude/longitude); derived times are recomputed.
     * Uses device API token (same as device check-in/out).
     */
    public function sync(Request $request)
    {
        $unauthorized = $this->assertDeviceApiToken($request);
        if ($unauthorized) {
            return $unauthorized;
        }

        $validator = Validator::make($request->all(), [
            'employee_device_id' => 'required|max:255',
            'date' => 'required|date',
            'status' => 'nullable|string|max:255',
            'clock_in' => 'nullable|string|max:32',
            'clock_out' => 'nullable|string|max:32',
            'latitudeIn' => 'nullable|numeric',
            'longitudeIn' => 'nullable|numeric',
            'latitudeOut' => 'nullable|numeric',
            'longitudeOut' => 'nullable|numeric',
            'locationIn' => 'nullable|string|max:500',
            'locationOut' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => __('Validation failed.'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $employee = $this->findEmployeeByDeviceId($request);
        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => __('No employee found for this device ID.'),
            ], 404);
        }

        $dateStr = Carbon::parse($request->date)->format('Y-m-d');
        $syncFields = $this->presentAttendanceSyncAttributes($request);

        $coreFields = ['status', 'clock_in', 'clock_out'];

        try {
            $attendance = AttendanceEmployee::firstOrNew([
                'employee_id' => $employee->id,
                'date' => $dateStr,
            ]);

            if (!$attendance->exists) {
                foreach ($coreFields as $field) {
                    if (!$request->has($field) || $request->input($field) === null || $request->input($field) === '') {
                        return response()->json([
                            'status' => 'error',
                            'message' => __('Validation failed.'),
                            'errors' => [
                                $field => [
                                    __('The :field field is required when creating an attendance record.', ['field' => $field]),
                                ],
                            ],
                        ], 422);
                    }
                }

                $attendance->fill($request->only($coreFields));

                foreach ($syncFields as $key => $value) {
                    if (!in_array($key, $coreFields, true)) {
                        $attendance->{$key} = $value;
                    }
                }
            } else {
                foreach ($syncFields as $key => $value) {
                    $attendance->{$key} = $value;
                }
            }

            $this->applyComputedAttendanceMetrics($attendance, $employee, $dateStr);

            $attendance->created_by = $this->resolveAttendanceCreatedBy($employee);

            $attendance->save();

            return response()->json([
                'status' => 'success',
                'data' => $attendance->fresh(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Request keys that have a value (skip null and ''); allow 0 for lat/long.
     *
     * @return array<string, mixed>
     */
    private function presentAttendanceSyncAttributes(Request $request): array
    {
        $keys = [
            'status',
            'clock_in',
            'clock_out',
            'latitudeIn',
            'longitudeIn',
            'latitudeOut',
            'longitudeOut',
            'locationIn',
            'locationOut',
        ];

        $out = [];
        foreach ($keys as $key) {
            if (!$request->has($key)) {
                continue;
            }
            $val = $request->input($key);
            if ($val === null || $val === '') {
                continue;
            }
            $out[$key] = $val;
        }

        return $out;
    }

    /**
     * late / early_leaving / overtime / total_rest derived from shift (employee start/end, else company settings) and clock_in/clock_out.
     */
    private function applyComputedAttendanceMetrics(AttendanceEmployee $attendance, Employee $employee, string $dateYmd): void
    {
        $metrics = $this->computeDerivedAttendanceMetrics(
            $employee,
            $dateYmd,
            $attendance->clock_in,
            $attendance->clock_out
        );
        $attendance->late = $metrics['late'];
        $attendance->early_leaving = $metrics['early_leaving'];
        $attendance->overtime = $metrics['overtime'];
        $attendance->total_rest = $metrics['total_rest'];
    }

    /**
     * @return array{late: string, early_leaving: string, overtime: string, total_rest: string}
     */
    private function computeDerivedAttendanceMetrics(Employee $employee, string $dateYmd, $clockIn, $clockOut): array
    {
        $zero = '00:00:00';

        // Always calculate against employee schedule when it exists.
        // Fallback defaults are used only when employee times are missing.
        $startRaw = trim((string) $employee->startTime);
        $endRaw = trim((string) $employee->endTime);
        if ($startRaw === '') {
            $startRaw = '09:00 AM';
        }
        if ($endRaw === '') {
            $endRaw = '06:00 PM';
        }

        try {
            $startAt = Carbon::parse($dateYmd . ' ' . trim((string) $startRaw));
            $endAt = Carbon::parse($dateYmd . ' ' . trim((string) $endRaw));
        } catch (\Exception $e) {
            return [
                'late' => $zero,
                'early_leaving' => $zero,
                'overtime' => $zero,
                'total_rest' => $zero,
            ];
        }

        $isOvernightShift = false;
        if ($endAt->lte($startAt)) {
            $isOvernightShift = true;
            $endAt->addDay();
        }

        $in = null;
        if ($clockIn !== null && $clockIn !== '' && (string) $clockIn !== '00:00:00') {
            try {
                $in = Carbon::parse($dateYmd . ' ' . trim((string) $clockIn));
                if ($isOvernightShift && $in->lt($startAt)) {
                    $in->addDay();
                }
            } catch (\Exception $e) {
                $in = null;
            }
        }

        $out = null;
        if ($clockOut !== null && $clockOut !== '' && (string) $clockOut !== '00:00:00') {
            try {
                $out = Carbon::parse($dateYmd . ' ' . trim((string) $clockOut));
                if ($isOvernightShift && $out->lt($startAt)) {
                    $out->addDay();
                }
            } catch (\Exception $e) {
                $out = null;
            }
        }

        $late = $zero;
        if ($in) {
            $lateSec = max(0, $in->getTimestamp() - $startAt->getTimestamp());
            $late = $this->secondsToHms((int) $lateSec);
        }

        $earlyLeaving = $zero;
        $overtime = $zero;
        if ($out) {
            if ($out->lt($endAt)) {
                $earlyLeaving = $this->secondsToHms((int) max(0, $endAt->getTimestamp() - $out->getTimestamp()));
            }
            if ($out->gt($endAt)) {
                $overtime = $this->secondsToHms((int) max(0, $out->getTimestamp() - $endAt->getTimestamp()));
            }
        }

        // Break/rest not tracked by sync API — align with manual entry default
        $totalRest = $zero;

        return [
            'late' => $late,
            'early_leaving' => $earlyLeaving,
            'overtime' => $overtime,
            'total_rest' => $totalRest,
        ];
    }

    private function secondsToHms(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }

    /**
     * Company owner (tenant) for attendance rows — from the employee record, not the API client.
     */
    private function resolveAttendanceCreatedBy(Employee $employee): ?int
    {
        if ($employee->created_by !== null && $employee->created_by !== '') {
            return (int) $employee->created_by;
        }

        return $employee->user_id ? (int) $employee->user_id : null;
    }

    /**
     * Check-in using static API token + employee_device_id (no Sanctum session).
     * Optional query/body: created_by (company owner user id) to scope employee in multi-tenant setups.
     */
    public function deviceCheckIn(Request $request)
    {
        
        $unauthorized = $this->assertDeviceApiToken($request);
        if ($unauthorized) {
            return $unauthorized;
        }

        $request->validate([
            'employee_device_id' => 'required|max:255',
            'latitudeIn' => 'required',
            'longitudeIn' => 'required',
            'locationIn' => 'required',
            'status' => 'nullable|string',
            'created_by' => 'nullable|integer',
        ]);

        $employee = $this->findEmployeeByDeviceId($request);
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => __('No employee found for this device ID.'),
            ], 404);
        }

        $startTimeStr = $employee->startTime;
        if (empty($startTimeStr)) {
            $startTimeStr = '09:00 AM';
        }

        try {
            $existing = AttendanceEmployee::where('employee_id', $employee->id)
                ->where('date', now()->toDateString())
                ->first();

            if ($existing && $existing->clock_in && $existing->clock_in !== '00:00:00' && $existing->clock_out === '00:00:00') {
                return response()->json([
                    'success' => false,
                    'message' => __('Already checked in today.'),
                ], 409);
            }

            $now = Carbon::now()->format('h:i A');
            $nowTime = Carbon::createFromFormat('h:i A', $now);
            $startCarbon = Carbon::createFromFormat('h:i A', $startTimeStr)->setDate($nowTime->year, $nowTime->month, $nowTime->day);

            $diffInHours = $startCarbon->diffInHours($nowTime);
            $diffInMinutes = $startCarbon->diffInMinutes($nowTime) % 60;
            $late = sprintf('%02d:%02d:%02d', $diffInHours, $diffInMinutes, 0);

            $employeeAttendance = new AttendanceEmployee();
            $employeeAttendance->employee_id = $employee->id;
            $employeeAttendance->date = now()->toDateString();
            $employeeAttendance->status = $request->input('status');
            $employeeAttendance->clock_in = now()->toTimeString();
            $employeeAttendance->clock_out = '00:00:00';
            $employeeAttendance->late = $late;
            $employeeAttendance->early_leaving = '00:00:00';
            $employeeAttendance->overtime = '00:00:00';
            $employeeAttendance->total_rest = '00:00:00';
            $employeeAttendance->latitudeIn = $request->latitudeIn;
            $employeeAttendance->longitudeIn = $request->longitudeIn;
            $employeeAttendance->locationIn = $request->locationIn;
            $employeeAttendance->created_by = $employee->user_id;

            $employeeAttendance->save();

            return response()->json([
                'success' => true,
                'message' => __('Check-in successfully'),
                'employee_id' => $employee->id,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check-out using static API token + employee_device_id (no Sanctum session).
     */
    public function deviceCheckOut(Request $request)
    {
        $unauthorized = $this->assertDeviceApiToken($request);
        if ($unauthorized) {
            return $unauthorized;
        }

        $request->validate([
            'employee_device_id' => 'required|max:255',
            'latitudeOut' => 'required',
            'longitudeOut' => 'required',
            'locationOut' => 'required',
            'created_by' => 'nullable|integer',
        ]);

        $employee = $this->findEmployeeByDeviceId($request);
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => __('No employee found for this device ID.'),
            ], 404);
        }

        $attendance = AttendanceEmployee::where('employee_id', $employee->id)
            ->where('date', now()->toDateString())
            ->first();

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => __('Check-in record not found'),
            ], 404);
        }

        if ($attendance->clock_out && $attendance->clock_out !== '00:00:00') {
            return response()->json([
                'success' => false,
                'message' => __('Already checked out today.'),
            ], 409);
        }

        $endTimeStr = $employee->endTime;
        if (empty($endTimeStr)) {
            $endTimeStr = '06:00 PM';
        }

        try {
            $now = Carbon::now()->format('h:i A');
            $nowTime = Carbon::createFromFormat('h:i A', $now);
            $endCarbon = Carbon::createFromFormat('h:i A', $endTimeStr)->setDate($nowTime->year, $nowTime->month, $nowTime->day);

            $earlyLeaving = '00:00:00';
            $overtime = '00:00:00';
            if ($nowTime->lessThan($endCarbon)) {
                $earlyLeaving = $endCarbon->diff($nowTime)->format('%H:%I:%S');
            } elseif ($nowTime->greaterThan($endCarbon)) {
                $overtime = $nowTime->diff($endCarbon)->format('%H:%I:%S');
            }

            $attendance->update([
                'clock_out' => now()->toTimeString(),
                'overtime' => $overtime,
                'early_leaving' => $earlyLeaving,
                'latitudeOut' => $request->latitudeOut,
                'longitudeOut' => $request->longitudeOut,
                'locationOut' => $request->locationOut,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Check-out successfully'),
                'employee_id' => $employee->id,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @return \Illuminate\Http\JsonResponse|null JSON response if unauthorized / misconfigured, null if OK
     */
    private function assertDeviceApiToken(Request $request): ?\Illuminate\Http\JsonResponse
    {
        $expected = 'sk-test-1234567890abcdef1234567890abcdef';
        if ($expected === null || $expected === '') {
            return response()->json([
                'success' => false,
                'message' => __('Device attendance API is not configured (ATTENDANCE_DEVICE_API_TOKEN).'),
            ], 503);
        }

        $token = $request->header('X-Attendance-Token')
            ?? $request->bearerToken()
            ?? $request->input('token');

        if (!is_string($token) || $token === '' || !hash_equals((string) $expected, $token)) {
            return response()->json([
                'success' => false,
                'message' => __('Invalid or missing token.'),
            ], 401);
        }

        return null;
    }

    private function findEmployeeByDeviceId(Request $request): ?Employee
    {
        $deviceId = trim((string) $request->input('employee_device_id'));
        if ($deviceId === '') {
            return null;
        }

        $q = Employee::query()->where('employee_device_id', $deviceId);
        if ($request->filled('created_by')) {
            $q->where('created_by', (int) $request->input('created_by'));
        }

        return $q->first();
    }

    public function checkOut(Request $request)
    {
        $userMac = $request->mac;
        $mac = macRestrict::where('user_id', \Auth::id())->whereIn('mac', [$userMac])->first();
        // if ($mac == null || $mac->mac !== $request->mac) {
        //     return  response()->json(['error' => 'This mac is not allowed to clock out.'], 401);
        // }

        $request->validate([
            'latitudeOut' => 'required',
            'longitudeOut' => 'required',
            'locationOut' => 'required',
        ]);
        $employee = Employee::where('user_id', Auth::id())->first();
        $attendance = AttendanceEmployee::where('employee_id', $employee->id)
            ->where('date', now()->toDateString())
            ->first();

        if (!$attendance) {
            return response()->json(['error' => 'Check-in record not found'], 404);
        }
        $date = date("Y-m-d");
        $endTime = Employee::where('user_id', Auth::id())->first()->endTime;
        // Combine date and endTime to create a DateTime object
        // $endDateTime = new DateTime($date . ' ' . $endTime);

        // Get current DateTime
        // $now = new DateTime();

        // Calculate the difference
        // $interval = $now->diff($endDateTime);

        // Calculate total difference in seconds
        // $totalLateSeconds = ($now->getTimestamp() - $endDateTime->getTimestamp());

        // $hours = floor(abs($totalLateSeconds) / 3600);
        // $mins = abs(floor(($totalLateSeconds % 3600) / 60));
        // $secs = abs($totalLateSeconds % 60);

        // Format time
        // $formattedTime = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

        // Current time in 12-hour format
        $now = Carbon::now()->format('h:i A');
        // Convert both times to Carbon instances
        $nowTime = Carbon::createFromFormat('h:i A', $now);
        $endTime = Carbon::createFromFormat('h:i A', $endTime)->setDate($nowTime->year, $nowTime->month, $nowTime->day);

        // Initialize variables for early leaving and overtime
        $earlyLeaving = "00:00:00";
        $overtime = "00:00:00";
        // Check if the employee left early or worked overtime
        if ($nowTime->lessThan($endTime)) {
            // Employee left early
            $earlyLeaving = $endTime->diff($nowTime)->format('%H:%I:%S');
        } elseif ($nowTime->greaterThan($endTime)) {
            // Employee worked overtime
            $overtime = $nowTime->diff($endTime)->format('%H:%I:%S');
        }
        $attendance->update([
            'clock_out' => now()->toTimeString(),
            'overtime' => $overtime,
            'early_leaving' => $earlyLeaving,
            'latitudeOut' => $request->latitudeOut,
            'longitudeOut' => $request->longitudeOut,
            'locationOut' => $request->locationOut,
        ]);

        // return response()->json($attendance, 200);
        return  response()->json(['success' => true, 'message' => 'Check-out successfully'], 201);
    }

    private function formatTime($seconds)
    {
        $hours = abs(floor($seconds / 3600));
        $mins = abs(floor($seconds / 60 % 60));
        $secs = abs(floor($seconds % 60));

        return sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
    }
    public function offlineCheckInOut(Request $request)
    {



        // Return a successful response
        return response()->json(['success' => true]);
    }
    public function IsCheckIn()
    {
        $employee = Employee::where('user_id', Auth::id())->first();
        $attendance = AttendanceEmployee::where('employee_id', $employee->id)
            ->where('date', now()->toDateString())
            ->where(function ($query) {
                $query->whereNotNull('clock_in')
                    ->where('clock_in', '!=', '00:00:00');
            })
            ->first();
        if (!$attendance) {
            return response()->json(['success' => false]);
        } else {
            // Return a successful response
            return response()->json(['success' => true, 'checkin_date' => $attendance->clock_in]);
        }
    }

    public function IsCheckOut()
    {
        $employee = Employee::where('user_id', Auth::id())->first();
        $attendance = AttendanceEmployee::where('employee_id', $employee->id)
            ->where('date', now()->toDateString())
            ->where(function ($query) {
                $query->whereNotNull('clock_out')
                    ->where('clock_out', '!=', '00:00:00');
            })
            ->first();
        if (!$attendance) {
            return response()->json(['success' => false]);
        } else {
            // Return a successful response
            return response()->json(['success' => true, 'checkout_date' => $attendance->clock_out]);
        }
    }

    public function getCurrentMonthStatistics(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $stats = AttendanceEmployee::whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->select(
                DB::raw('SUM(TIME_TO_SEC(late)) as total_late_seconds'),
                DB::raw('SUM(TIME_TO_SEC(overtime)) as total_overtime_seconds'),
                DB::raw('SUM(TIME_TO_SEC(early_leaving)) as total_early_leaving_seconds')
            )
            ->first();

        return response()->json([
            'total_late_hours' => gmdate('H:i:s', $stats->total_late_seconds),
            'total_overtime_hours' => gmdate('H:i:s', $stats->total_overtime_seconds),
            'total_early_leaving_hours' => gmdate('H:i:s', $stats->total_early_leaving_seconds),
        ]);
    }

    public function monthlyReport(Request $request)
    {

        $employee = Employee::where('user_id', '=', Auth::user()->id)->first();
        // Get detailed attendance records for the month
        $attendanceDetails = AttendanceEmployee::where('employee_id', $employee->id)
            ->whereBetween('date',  [$request->start_date, $request->end_date])
            ->orderBy('date')
            ->get();
        //
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $totalDays = ($endDate->diffInDays($startDate) + 1) * -1;
        $dates = [];

        while ($startDate->lte($endDate)) {
            $dates[] = $startDate->format('Y-m-d');
            $startDate->addDay();
        }
        $attendanceRecords = AttendanceEmployee::whereBetween('date', [$request->start_date, $request->end_date])
            ->get(['date']);

        $attendedDates = $attendanceRecords->pluck('date')->map(function ($date) {
            return Carbon::parse($date)->format('Y-m-d');
        })->toArray();
        $absentDays = array_diff($dates, $attendedDates);
        $totalAbsentDays = count($absentDays);
        $startTime = $employee->startTime != null ? $employee->startTime : '09:00:00';
        $endTime  = $employee->endTime  != null ? $employee->endTime  : '07:00:00';
        $totalLeaveDays = Leave::where('employee_id', $employee->id)
            ->sum('total_leave_days');
        // Summarize the attendance data
        $summary = AttendanceEmployee::selectRaw('

                sum(case when status = "present" then 1 else 0 end) as present_days,
                sum(case when status = "absent" then 1 else 0 end) as absent_days,
                sum(case when late > 0 then 1 else 0 end) as total_late,
                sum(case when early_leaving > 0 then 1 else 0 end) as total_early_leaving,
                sum(case when overtime > 0 then 1 else 0 end) as total_overtime_instances,
                sum(case when clock_in <= "' . $startTime . '" then 1 else 0 end) as on_time_attendance,
                sum(case when clock_out <= "' . $endTime . '" then 1 else 0 end) as on_time_left
            ')
            ->whereBetween('date', [$request->start_date, $request->end_date])
            ->first();
        $summary->absent_days = $totalAbsentDays;
        $summary->total_days = $totalDays;
        $summary->totalLeaveDays = $totalLeaveDays;
        return response()->json([
            'success' => true,
            'data' => [
                'details' => $attendanceDetails,
                'summary' => $summary,
            ],
        ]);
    }

    public function calculateTimeDifference($endTime)
    {
        $now = Carbon::now();
        $start =  Carbon::createFromFormat('H:i', $endTime)->setDate($now->year, $now->month, $now->day);

        $diffInHours = $start->diffInHours($now);
        $diffInMinutes = $start->diffInMinutes($now) % 60; // Get the remaining minutes

        return sprintf('%02d:%02d:%02s', $diffInHours, $diffInMinutes, '00');
    }

    public function attendanceExport(Request $request)
    {
        return Excel::download(new AttendanceExport($request->type, $request->month, $request->date, $request->branch, $request->employee, $request->department), 'attendance.xlsx');
    }

}
