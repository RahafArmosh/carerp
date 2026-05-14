<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DailyLeave;
use App\Models\Employee;
use App\Models\AttendanceEmployee;
use Illuminate\Support\Facades\Auth;

class DailyLeaveController extends Controller
{

    public function index()
    {

        if (\Auth::user()->can('manage leave')) {
            if (\Auth::user()->type == 'Employee') {
                $user     = \Auth::user();
                $employee = Employee::where('user_id', '=', $user->id)->first();
                $leaves   = DailyLeave::where('employee_id', '=', $employee->id)->with(['employees'])->get();
            } else {
                $leaves = DailyLeave::with(['employees'])->get();
            }

            return view('earlyleave.index', compact('leaves'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create leave')) {
            if (Auth::user()->type == 'Employee') {
                $employees = Employee::where('user_id', '=', \Auth::user()->id)->get()->pluck('name', 'id');
            } else {
                $employees = Employee::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            }
            $leavetypes      = DailyLeave::where('created_by', '=', \Auth::user()->creatorId())->get();
            //            $leavetypes_days = LeaveType::where('created_by', '=', \Auth::user()->creatorId())->get();

            return view('earlyleave.create', compact('employees'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {

        if (\Auth::user()->can('create leave')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'date' => 'required',
                    'time' => 'required',
                    'leave_reason' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }


            $employee = Employee::where('user_id', '=', Auth::user()->id)->first();
            $Date = new \DateTime($request->date);

                $leave    = new DailyLeave();
                if (\Auth::user()->type == "Employee") {
                    $leave->employee_id = $employee->id;
                } else {
                    $leave->employee_id = $request->employee_id;
                }

                $leave->date       = $request->date;
                $leave->time       = $request->time;
                $leave->reason     = $request->leave_reason;
                $leave->status           = 'Pending';
                $leave->created_by       = \Auth::user()->creatorId();

                $leave->save();

                return redirect()->route('earlyleave.index')->with('success', __('Early Leave successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    // public function show(Leave $leave)
    // {
    //     return redirect()->route('leave.index');
    // }

    public function edit($id)
    {
        $leave = DailyLeave::find($id);
        if (\Auth::user()->can('edit leave')) {
            if ($leave->created_by == \Auth::user()->creatorId()) {
                $employees  = Employee::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');

                return view('earlyleave.edit', compact('leave', 'employees'));
            } else {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, $leave)
    {

        $leave = DailyLeave::find($leave);
        if (\Auth::user()->can('edit leave')) {
            if ($leave->created_by == Auth::user()->creatorId()) {
                $validator = \Validator::make(
                    $request->all(),
                    [

                        'time' => 'required',
                        'date' => 'required',
                        'leave_reason' => 'required',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }


                    $leave->employee_id      = $request->employee_id;
                    $leave->date       = $request->date;
                    $leave->time         = $request->time;
                    $leave->reason     = $request->leave_reason;
                    $leave->created_by     = Employee::where('id' , $request->employee_id)->first()->user_id;

                    $leave->save();

                    return redirect()->route('earlyleave.index')->with('success', __('early Leave successfully updated.'));

            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(DailyLeave $leave)
    {
        if (\Auth::user()->can('delete leave')) {
            if ($leave->created_by == \Auth::user()->creatorId()) {
                $leave->delete();

                return redirect()->route('earlyleave.index')->with('success', __('Early Leave successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }



    public function request_earlyLeave(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        $dailyLeave = DailyLeave::create([
            'employee_id' => Employee::where('user_id' , Auth::id())->first()->id,
            'date' => $request->date,
            'time'=> $request->time,
            'reason' => $request->reason,
            'created_by' => Auth::id(),
        ]);

        return response()->json($dailyLeave, 201);
    }

    public function request_my_early_leave()
    {
        $dailyLeaves = DailyLeave::where('employee_id', Auth::id())->get();

        return response()->json($dailyLeaves, 200);
    }

    public function update_early_leave(Request $request, $id)
    {
        $dailyLeave = DailyLeave::findOrFail($id);

        $this->authorize('update', $dailyLeave);

        $dailyLeave->update($request->only('status'));

        return response()->json($dailyLeave, 200);
    }

    public function show($id)
    {
        $dailyLeave = DailyLeave::findOrFail($id);

        return response()->json($dailyLeave, 200);
    }

    public function destroy_early_leave($id)
    {
        $dailyLeave = DailyLeave::findOrFail($id);

        $this->authorize('delete', $dailyLeave);

        $dailyLeave->delete();

        return response()->json(null, 204);
    }


    public function changeaction(Request $request)
    {

        $leave = DailyLeave::find($request->leave_id);

        $leave->status = $request->status;
        if ($leave->status == 'Approval') {
            $leave->status           = 'Approved';
        }

        $leave->save();
        $attendance = AttendanceEmployee::where('employee_id', $leave->employee_id)
            ->where('date', $leave->date)
            ->first();
        $attendance->update([
            'early_leaving' => $leave->time,
            ]);

        // //Send Email
        // $setings = Utility::settings();
        // if (!empty($employee->id)) {
        //     if ($setings['leave_status'] == 1) {

        //         $employee     = Employee::where('id', $leave->employee_id)->where('created_by', '=', \Auth::user()->creatorId())->first();
        //         $leave->name  = !empty($employee->name) ? $employee->name : '';
        //         $leave->email = !empty($employee->email) ? $employee->email : '';
        //         //            dd($leave);

        //         $actionArr = [

        //             'leave_name' => !empty($employee->name) ? $employee->name : '',
        //             'leave_status' => $leave->status,
        //             'leave_reason' =>  $leave->leave_reason,
        //             'leave_start_date' => $leave->start_date,
        //             'leave_end_date' => $leave->end_date,
        //             'total_leave_days' => $leave->total_leave_days,

        //         ];
        //         //            dd($actionArr);
        //         $resp = Utility::sendEmailTemplate('leave_action_sent', [$employee->id => $employee->email], $actionArr);


        //         return redirect()->route('leave.index')->with('success', __('Leave status successfully updated.') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
        //     }
        // }

        return redirect()->route('earlyleave.index')->with('success', __('Early Leave status successfully updated.'));
    }

    public function action($id)
    {
        $leave     = DailyLeave::find($id);
        $employee  = Employee::find($leave->employee_id);

        return view('earlyleave.action', compact('employee','leave'));
    }
}
