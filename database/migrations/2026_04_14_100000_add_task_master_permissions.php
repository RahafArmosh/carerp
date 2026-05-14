<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function permissionNames(): array
    {
        return [
            'view task master',
            'manage task master',
            'create task master',
            'edit task master',
            'delete task master',
        ];
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');

        foreach ($this->permissionNames() as $name) {
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
        }

        foreach (['company', 'super admin'] as $roleName) {
            $role = DB::table($tableNames['roles'])
                ->where('name', $roleName)
                ->first();

            if (!$role) {
                continue;
            }

            foreach ($this->permissionNames() as $name) {
                $permission = DB::table($tableNames['permissions'])
                    ->where('name', $name)
                    ->where('guard_name', 'web')
                    ->first();

                if (!$permission) {
                    continue;
                }

                $exists = DB::table($tableNames['role_has_permissions'])
                    ->where('role_id', $role->id)
                    ->where('permission_id', $permission->id)
                    ->exists();

                if (!$exists) {
                    DB::table($tableNames['role_has_permissions'])->insert([
                        'permission_id' => $permission->id,
                        'role_id' => $role->id,
                    ]);
                }
            }
        }

        $companyUsers = DB::table('users')->where('type', 'company')->get();

        foreach ($companyUsers as $user) {
            foreach ($this->permissionNames() as $name) {
                $permission = DB::table($tableNames['permissions'])
                    ->where('name', $name)
                    ->where('guard_name', 'web')
                    ->first();

                if (!$permission) {
                    continue;
                }

                $exists = DB::table($tableNames['model_has_permissions'])
                    ->where('model_type', 'App\Models\User')
                    ->where('model_id', $user->id)
                    ->where('permission_id', $permission->id)
                    ->exists();

                if (!$exists) {
                    DB::table($tableNames['model_has_permissions'])->insert([
                        'permission_id' => $permission->id,
                        'model_type' => 'App\Models\User',
                        'model_id' => $user->id,
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

        foreach ($this->permissionNames() as $name) {
            $permission = DB::table($tableNames['permissions'])
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->first();

            if (!$permission) {
                continue;
            }

            DB::table($tableNames['role_has_permissions'])
                ->where('permission_id', $permission->id)
                ->delete();

            DB::table($tableNames['model_has_permissions'])
                ->where('permission_id', $permission->id)
                ->delete();

            DB::table($tableNames['permissions'])
                ->where('id', $permission->id)
                ->delete();
        }
    }
};
