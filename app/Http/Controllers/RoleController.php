<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Auth;

class RoleController extends Controller
{

    public function index()
    {
        if(\Auth::user()->can('manage role'))
        {

            $roles = Role::where('created_by', '=', \Auth::user()->creatorId())->get();

            return view('role.index')->with('roles', $roles);
        }
        else
        {
            return redirect()->back()->with('error', 'Permission denied.');
        }

    }


    public function create()
    {
        if(\Auth::user()->can('create role'))
        {
            $user = \Auth::user();
            if($user->type == 'super admin')
            {
                $permissions = Permission::all()->pluck('name', 'id')->toArray();
            }
            else
            {
                $permissions = new Collection();
                foreach($user->roles as $role)
                {
                    $permissions = $permissions->merge($role->permissions);
                }
                $permissions = $permissions->pluck('name', 'id')->toArray();
            }

            $convertLeadPermission = Permission::where('name', 'convert lead to deal')->first();
            if($convertLeadPermission)
            {
                $permissions[$convertLeadPermission->id] = $convertLeadPermission->name;
            }

            // Ensure Advance Sale Order permissions are available in role screens.
            // Company users may not have these in their current role yet, but still need to assign them.
            $advanceSaleOrderPermissions = Permission::whereIn('name', [
                'view advance sale order',
                'create advance sale order',
                'edit advance sale order',
                'delete advance sale order',
            ])->get();
            foreach($advanceSaleOrderPermissions as $advanceSaleOrderPermission)
            {
                $permissions[$advanceSaleOrderPermission->id] = $advanceSaleOrderPermission->name;
            }

            // Ensure accounting report permissions are assignable in role screen.
            $accountingReportPermissionNames = [
                'manage report',
                'income report',
                'expense report',
                'income vs expense report',
                'tax report',
                'invoice report',
                'bill report',
                'stock report',
                'loss & profit report',
                'statement report',
                'balance sheet report',
                'ledger report',
                'trial balance report',
                'general ledger report',
            ];
            foreach($accountingReportPermissionNames as $permissionName)
            {
                Permission::firstOrCreate(
                    ['name' => $permissionName, 'guard_name' => 'web'],
                    ['name' => $permissionName, 'guard_name' => 'web']
                );
            }
            $accountingReportPermissions = Permission::whereIn('name', $accountingReportPermissionNames)->get();
            foreach($accountingReportPermissions as $accountingReportPermission)
            {
                $permissions[$accountingReportPermission->id] = $accountingReportPermission->name;
            }

            return view('role.create', ['permissions' => $permissions]);
        }
        else
        {
            return redirect()->back()->with('error', 'Permission denied.');
        }

    }


    public function store(Request $request)
    {
        if(\Auth::user()->can('create role'))
        {
            $validator = \Validator::make(
                $request->all(), [
                                   'name' => 'required|max:100|unique:roles,name,NULL,id,created_by,' . \Auth::user()->creatorId(),
                                   'permissions' => 'required',
                               ]
            );

            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $name             = $request['name'];
            $role             = new Role();
            $role->name       = $name;
            $role->created_by = \Auth::user()->creatorId();
            $permissions      = $request['permissions'];
            $role->save();

            foreach($permissions as $permission)
            {
                $p = Permission::where('id', '=', $permission)->firstOrFail();
                $role->givePermissionTo($p);
            }

            return redirect()->route('roles.index')->with(
                'Role successfully created.', 'Role ' . $role->name . ' added!'
            );
        }
        else
        {
            return redirect()->back()->with('error', 'Permission denied.');
        }


    }

    public function edit(Role $role)
    {
        if(\Auth::user()->can('edit role'))
        {

            $user = \Auth::user();
            if($user->type == 'super admin')
            {
                $permissions = Permission::all()->pluck('name', 'id')->toArray();
            }
            else
            {
                $permissions = new Collection();
                foreach($user->roles as $role1)
                {
                    $permissions = $permissions->merge($role1->permissions);
                }
                $permissions = $permissions->pluck('name', 'id')->toArray();
            }

            $convertLeadPermission = Permission::where('name', 'convert lead to deal')->first();
            if($convertLeadPermission)
            {
                $permissions[$convertLeadPermission->id] = $convertLeadPermission->name;
            }

            // Ensure Advance Sale Order permissions are available in role screens.
            // Company users may not have these in their current role yet, but still need to assign them.
            $advanceSaleOrderPermissions = Permission::whereIn('name', [
                'view advance sale order',
                'create advance sale order',
                'edit advance sale order',
                'delete advance sale order',
            ])->get();
            foreach($advanceSaleOrderPermissions as $advanceSaleOrderPermission)
            {
                $permissions[$advanceSaleOrderPermission->id] = $advanceSaleOrderPermission->name;
            }

            // Ensure accounting report permissions are assignable in role screen.
            $accountingReportPermissionNames = [
                'manage report',
                'income report',
                'expense report',
                'income vs expense report',
                'tax report',
                'invoice report',
                'bill report',
                'stock report',
                'loss & profit report',
                'statement report',
                'balance sheet report',
                'ledger report',
                'trial balance report',
                'general ledger report',
            ];
            foreach($accountingReportPermissionNames as $permissionName)
            {
                Permission::firstOrCreate(
                    ['name' => $permissionName, 'guard_name' => 'web'],
                    ['name' => $permissionName, 'guard_name' => 'web']
                );
            }
            $accountingReportPermissions = Permission::whereIn('name', $accountingReportPermissionNames)->get();
            foreach($accountingReportPermissions as $accountingReportPermission)
            {
                $permissions[$accountingReportPermission->id] = $accountingReportPermission->name;
            }

            return view('role.edit', compact('role', 'permissions'));
        }
        else
        {
            return redirect()->back()->with('error', 'Permission denied.');
        }


    }

    public function update(Request $request, Role $role)
    {

        if(\Auth::user()->can('edit role'))
        {
            $validator = \Validator::make(
                $request->all(), [
                                   'name' => 'required|max:100|unique:roles,name,' . $role['id'] . ',id,created_by,' . \Auth::user()->creatorId(),
                                   'permissions' => 'required',
                               ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $input       = $request->except(['permissions']);
            $permissions = $request['permissions'];
            $role->fill($input)->save();

            $p_all = Permission::all();

            foreach($p_all as $p)
            {
                $role->revokePermissionTo($p);
            }

            foreach($permissions as $permission)
            {

                $p = Permission::where('id', '=', $permission)->firstOrFail();
                $role->givePermissionTo($p);
            }

            return redirect()->route('roles.index')->with(
                'Role successfully updated.', 'Role ' . $role->name . ' updated!'
            );
        }
        else
        {
            return redirect()->back()->with('error', 'Permission denied.');
        }

    }


    public function destroy(Role $role)
    {
        if(\Auth::user()->can('delete role'))
        {
            $role->delete();

            return redirect()->route('roles.index')->with(
                'success', 'Role successfully deleted.'
            );
        }
        else
        {
            return redirect()->back()->with('error', 'Permission denied.');
        }


    }
}
