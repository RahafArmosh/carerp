<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $permissionTable = $tableNames['permissions'];
        $roleTable = $tableNames['roles'];
        $rolePermissionTable = $tableNames['role_has_permissions'];

        $permissionNames = [
            'view advance sale order',
            'create advance sale order',
            'edit advance sale order',
            'delete advance sale order',
        ];

        foreach ($permissionNames as $permissionName) {
            $exists = DB::table($permissionTable)
                ->where('name', $permissionName)
                ->where('guard_name', 'web')
                ->exists();

            if (!$exists) {
                DB::table($permissionTable)->insert([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $roles = DB::table($roleTable)
            ->whereIn('name', ['company', 'super admin'])
            ->get(['id', 'name']);

        foreach ($roles as $role) {
            foreach ($permissionNames as $permissionName) {
                $permission = DB::table($permissionTable)
                    ->where('name', $permissionName)
                    ->where('guard_name', 'web')
                    ->first();

                if (!$permission) {
                    continue;
                }

                $assigned = DB::table($rolePermissionTable)
                    ->where('role_id', $role->id)
                    ->where('permission_id', $permission->id)
                    ->exists();

                if (!$assigned) {
                    DB::table($rolePermissionTable)->insert([
                        'role_id' => $role->id,
                        'permission_id' => $permission->id,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $permissionTable = $tableNames['permissions'];
        $rolePermissionTable = $tableNames['role_has_permissions'];

        $permissionNames = [
            'view advance sale order',
            'create advance sale order',
            'edit advance sale order',
            'delete advance sale order',
        ];

        $permissionIds = DB::table($permissionTable)
            ->whereIn('name', $permissionNames)
            ->where('guard_name', 'web')
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table($rolePermissionTable)
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        DB::table($permissionTable)
            ->whereIn('name', $permissionNames)
            ->where('guard_name', 'web')
            ->delete();
    }
};

