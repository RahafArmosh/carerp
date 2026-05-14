<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $name = 'manage daily task log';

        $exists = DB::table($tableNames['permissions'])
            ->where('name', $name)
            ->where('guard_name', 'web')
            ->exists();

        if (!$exists) {
            DB::table($tableNames['permissions'])->insert([
                'name' => $name,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $permission = DB::table($tableNames['permissions'])
            ->where('name', $name)
            ->where('guard_name', 'web')
            ->first();

        if (!$permission) {
            return;
        }

        foreach (['company', 'super admin'] as $roleName) {
            $role = DB::table($tableNames['roles'])->where('name', $roleName)->first();
            if (!$role) {
                continue;
            }
            $rp = DB::table($tableNames['role_has_permissions'])
                ->where('role_id', $role->id)
                ->where('permission_id', $permission->id)
                ->exists();
            if (!$rp) {
                DB::table($tableNames['role_has_permissions'])->insert([
                    'permission_id' => $permission->id,
                    'role_id' => $role->id,
                ]);
            }
        }

        $employeeRoles = DB::table($tableNames['roles'])->where('name', 'Employee')->get();
        foreach ($employeeRoles as $role) {
            $rp = DB::table($tableNames['role_has_permissions'])
                ->where('role_id', $role->id)
                ->where('permission_id', $permission->id)
                ->exists();
            if (!$rp) {
                DB::table($tableNames['role_has_permissions'])->insert([
                    'permission_id' => $permission->id,
                    'role_id' => $role->id,
                ]);
            }
        }

        $companyUsers = DB::table('users')->where('type', 'company')->get();
        foreach ($companyUsers as $user) {
            $mp = DB::table($tableNames['model_has_permissions'])
                ->where('model_type', 'App\Models\User')
                ->where('model_id', $user->id)
                ->where('permission_id', $permission->id)
                ->exists();
            if (!$mp) {
                DB::table($tableNames['model_has_permissions'])->insert([
                    'permission_id' => $permission->id,
                    'model_type' => 'App\Models\User',
                    'model_id' => $user->id,
                ]);
            }
        }
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $permission = DB::table($tableNames['permissions'])
            ->where('name', 'manage daily task log')
            ->where('guard_name', 'web')
            ->first();

        if (!$permission) {
            return;
        }

        DB::table($tableNames['role_has_permissions'])->where('permission_id', $permission->id)->delete();
        DB::table($tableNames['model_has_permissions'])->where('permission_id', $permission->id)->delete();
        DB::table($tableNames['permissions'])->where('id', $permission->id)->delete();
    }
};
