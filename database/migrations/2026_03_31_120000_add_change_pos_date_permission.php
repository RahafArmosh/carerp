<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');

        $permissionName = 'change pos date';
        $guardName = 'web';

        $permission = DB::table($tableNames['permissions'])
            ->where('name', $permissionName)
            ->where('guard_name', $guardName)
            ->first();

        if (!$permission) {
            DB::table($tableNames['permissions'])->insert([
                'name' => $permissionName,
                'guard_name' => $guardName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $permission = DB::table($tableNames['permissions'])
                ->where('name', $permissionName)
                ->where('guard_name', $guardName)
                ->first();
        }

        if ($permission) {
            $roleIds = DB::table($tableNames['roles'])
                ->whereIn('name', ['company', 'super admin'])
                ->where('guard_name', $guardName)
                ->pluck('id');

            foreach ($roleIds as $roleId) {
                $rolePermissionExists = DB::table($tableNames['role_has_permissions'])
                    ->where('permission_id', $permission->id)
                    ->where('role_id', $roleId)
                    ->exists();

                if (!$rolePermissionExists) {
                    DB::table($tableNames['role_has_permissions'])->insert([
                        'permission_id' => $permission->id,
                        'role_id' => $roleId,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');

        $permission = DB::table($tableNames['permissions'])
            ->where('name', 'change pos date')
            ->where('guard_name', 'web')
            ->first();

        if ($permission) {
            DB::table($tableNames['role_has_permissions'])
                ->where('permission_id', $permission->id)
                ->delete();

            DB::table($tableNames['permissions'])
                ->where('id', $permission->id)
                ->delete();
        }
    }
};
