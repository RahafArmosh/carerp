<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TaskManagerController extends Controller
{
    /**
     * Employee task management hub (daily logs, master tasks — UI to be expanded).
     */
    public function index(Request $request)
    {
        if (!Gate::any(['manage employee', 'manage task master'])) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        return view('task_manager.index');
    }
}
