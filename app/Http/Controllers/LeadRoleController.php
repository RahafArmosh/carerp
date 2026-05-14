<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeadRole;
use App\Models\User;
use App\Models\LeadRoleCondition;
use App\Models\Pipeline;
use Illuminate\Support\Facades\Schema;

class LeadRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (\Auth::user()->can('manage lead role')) {
            if (\Auth::user()->type == 'company') {
                $leadRoles = LeadRole::with('conditions', 'user')->where('created_by', '=', \Auth::user()->creatorId())->get();
            } else {
                $leadRoles = LeadRole::with('conditions', 'user')->where('pipeline_id', '=', \Auth::user()->default_pipeline)->get();
            }

            return view('lead_roles.index', compact('leadRoles'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create lead role')) {
            if (\Auth::user()->type == 'company' || \Auth::user()->can('manage crm admin')) {
                $users = User::where('created_by', \Auth::user()->creatorId())->get();
            } else {
                $users = User::where('manager_id', \Auth::user()->id)->get();
            }
            $leadColumns = Schema::getColumnListing('leads');
            $pipeline = Pipeline::where('created_by', \Auth::user()->ownerId())->get();
            return view('lead_roles.create', compact('users', 'leadColumns', 'pipeline'));
        } else {
            return response()->json(['error' => __('Permission Denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create lead role')) {
            $request->validate([
                'name' => 'required|string',
                'assigned_user_id' => 'required|exists:users,id',
                'conditions' => 'required|array|min:1',
                'conditions.*.lead_column' => 'required|string',
                'conditions.*.operation' => 'required|in:=,!=,contains,not_contains,starts_with,ends_with,is_empty,is_not_empty',
                'conditions.*.value' => 'nullable|string',
                'conditions.*.logical_operator' => 'nullable|in:AND,OR',
            ]);

            // Save Lead Role
            $leadRole = new LeadRole();
            $leadRole->name = $request->name;
            $leadRole->assigned_user_id = $request->assigned_user_id;
            $leadRole->active = $request->has('active') ? 1 : 0;
            $leadRole->pipeline_id = $request->pipeline_id;
            $leadRole->created_by = 36;
            $leadRole->save();

            // Save Conditions
            foreach ($request->conditions as $condition) {
                LeadRoleCondition::create([
                    'lead_role_id' => $leadRole->id,
                    'lead_column' => $condition['lead_column'],
                    'operation' => $condition['operation'],
                    'value' => $condition['value'] ?? null,
                    'connector' => $condition['logical_operator'] ?? 'OR',
                ]);
            }

            return redirect()->route('lead_roles.index')->with('success', 'Lead Role created with conditions.');
        } else {
            return response()->json(['error' => __('Permission Denied.')], 401);
        }
    }

    public function show(LeadRole $leadRole)
    {
        return view('lead_roles.show', compact('leadRole'));
    }

    public function edit(LeadRole $leadRole)
    {
        if (\Auth::user()->can('edit lead role')) {
            if (\Auth::user()->type == 'company' || \Auth::user()->can('manage crm admin')) {
                $users = User::where('created_by', \Auth::user()->creatorId())->get();
            } else {
                $users = User::where( 'manager_id', \Auth::user()->id)->get();
            }
            $leadColumns = Schema::getColumnListing('leads');
            $pipeline = Pipeline::where('created_by', \Auth::user()->ownerId())->get();
            return view('lead_roles.edit', compact('leadRole', 'users', 'leadColumns', 'pipeline'));
        } else {
            return response()->json(['error' => __('Permission Denied.')], 401);
        }
    }

    public function update(Request $request, LeadRole $leadRole)
    {
        if (\Auth::user()->can('edit lead role')) {
            $request->validate([
                'name' => 'required|string',
                'assigned_user_id' => 'required|exists:users,id',
                'conditions' => 'required|array|min:1',
                'conditions.*.lead_column' => 'required|string',
                'conditions.*.operation' => 'required|string',
                'conditions.*.value' => 'nullable|string',
                'conditions.*.logical_operator' => 'nullable|in:AND,OR',
            ]);

            // Update main LeadRole data
            $leadRole->name = $request->name;
            $leadRole->assigned_user_id = $request->assigned_user_id;
            $leadRole->active = $request->has('active') ? 1 : 0;
            $leadRole->pipeline_id = $request->pipeline_id;
            $leadRole->save();

            // Remove existing conditions
            $leadRole->conditions()->delete();

            // Re-insert new conditions
            foreach ($request->conditions as $condition) {
                $leadRole->conditions()->create([
                    'lead_column' => $condition['lead_column'],
                    'operation' => $condition['operation'],
                    'value' => $condition['value'] ?? null,
                    'connector' => $condition['logical_operator'] ?? 'OR',
                ]);
            }

            return redirect()->route('lead_roles.index')->with('success', 'Lead Role updated.');
        } else {
            return response()->json(['error' => __('Permission Denied.')], 401);
        }
    }
    function destroyCondition(LeadRoleCondition $LeadRoleCondition)
    {
        if (\Auth::user()->can('delete lead role condition')) {
            $LeadRoleCondition->delete();
            return response()->json(['success' => true, 'message' => 'Condition deleted.']);
        } else {
            return response()->json([
                'success' => false,
                'message' => __('Permission Denied.')
            ], 401);
        }
    }
    public function destroy(LeadRole $leadRole)
    {
        if (\Auth::user()->can('delete lead role')) {
            $leadRole->conditions()->delete();
            $leadRole->delete();
            return redirect()->route('lead_roles.index')->with('success', 'Lead Role deleted.');
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}
