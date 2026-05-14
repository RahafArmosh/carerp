<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\TaskMaster;
use Illuminate\Http\Request;

class TaskMasterController extends Controller
{
    public function index()
    {
        if (!\Auth::user()->can('manage task master')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $taskMasters = TaskMaster::where('created_by', \Auth::user()->creatorId())
            ->with('department')
            ->orderBy('name')
            ->get();

        return view('task_master.index', compact('taskMasters'));
    }

    public function create()
    {
        if (!\Auth::user()->can('create task master')) {
            return response()->json(['error' => __('Permission denied.')], 401);
        }

        $departments = Department::where('created_by', \Auth::user()->creatorId())->orderBy('name')->pluck('name', 'id');
        $departments->prepend(__('All departments'), '');

        return view('task_master.create', compact('departments'));
    }

    public function store(Request $request)
    {
        if (!\Auth::user()->can('create task master')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $request->merge([
            'department_id' => $request->filled('department_id') ? $request->department_id : null,
        ]);

        $validator = \Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'department_id' => 'nullable|exists:departments,id',
            ]
        );

        if ($validator->fails()) {
            return redirect()->back()->withInput()->with('error', $validator->getMessageBag()->first());
        }

        if ($request->filled('department_id')) {
            $dept = Department::where('id', $request->department_id)
                ->where('created_by', \Auth::user()->creatorId())
                ->first();
            if (!$dept) {
                return redirect()->back()->withInput()->with('error', __('Invalid department.'));
            }
        }

        TaskMaster::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_predefined' => $request->has('is_predefined'),
            'created_by_employee_id' => null,
            'department_id' => $request->filled('department_id') ? (int) $request->department_id : null,
            'is_active' => $request->has('is_active'),
            'created_by' => \Auth::user()->creatorId(),
        ]);

        return redirect()->route('task-master.index')->with('success', __('Task master successfully created.'));
    }

    public function show(TaskMaster $task_master)
    {
        return redirect()->route('task-master.index');
    }

    public function edit(TaskMaster $task_master)
    {
        if (!\Auth::user()->can('edit task master')) {
            return response()->json(['error' => __('Permission denied.')], 401);
        }

        if ($task_master->created_by != \Auth::user()->creatorId()) {
            return response()->json(['error' => __('Permission denied.')], 401);
        }

        $departments = Department::where('created_by', \Auth::user()->creatorId())->orderBy('name')->pluck('name', 'id');
        $departments->prepend(__('All departments'), '');

        $taskMaster = $task_master;

        return view('task_master.edit', compact('taskMaster', 'departments'));
    }

    public function update(Request $request, TaskMaster $task_master)
    {
        if (!\Auth::user()->can('edit task master')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if ($task_master->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $request->merge([
            'department_id' => $request->filled('department_id') ? $request->department_id : null,
        ]);

        $validator = \Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'department_id' => 'nullable|exists:departments,id',
            ]
        );

        if ($validator->fails()) {
            return redirect()->back()->withInput()->with('error', $validator->getMessageBag()->first());
        }

        if ($request->filled('department_id')) {
            $dept = Department::where('id', $request->department_id)
                ->where('created_by', \Auth::user()->creatorId())
                ->first();
            if (!$dept) {
                return redirect()->back()->withInput()->with('error', __('Invalid department.'));
            }
        }

        $task_master->name = $request->name;
        $task_master->description = $request->description;
        $task_master->is_predefined = $request->has('is_predefined');
        $task_master->department_id = $request->filled('department_id') ? (int) $request->department_id : null;
        $task_master->is_active = $request->has('is_active');
        $task_master->save();

        return redirect()->route('task-master.index')->with('success', __('Task master successfully updated.'));
    }

    public function destroy(TaskMaster $task_master)
    {
        if (!\Auth::user()->can('delete task master')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if ($task_master->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $task_master->delete();

        return redirect()->route('task-master.index')->with('success', __('Task master successfully deleted.'));
    }
}
