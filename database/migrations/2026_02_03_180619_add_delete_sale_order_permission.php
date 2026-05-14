<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        
        // Check if permission already exists
        $permissionExists = DB::table($tableNames['permissions'])
            ->where('name', 'delete sale order')
            ->where('guard_name', 'web')
            ->exists();

        if (!$permissionExists) {
            DB::table($tableNames['permissions'])->insert([
                'name' => 'delete sale order',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Assign permission to company role (if exists)
        $companyRole = DB::table($tableNames['roles'])
            ->where('name', 'company')
            ->first();
        
        if ($companyRole) {
            $permission = DB::table($tableNames['permissions'])
                ->where('name', 'delete sale order')
                ->where('guard_name', 'web')
                ->first();
            
            if ($permission) {
                $rolePermissionExists = DB::table($tableNames['role_has_permissions'])
                    ->where('role_id', $companyRole->id)
                    ->where('permission_id', $permission->id)
                    ->exists();
                
                if (!$rolePermissionExists) {
                    DB::table($tableNames['role_has_permissions'])->insert([
                        'permission_id' => $permission->id,
                        'role_id' => $companyRole->id,
                    ]);
                }
            }
        }

        // Assign permission to super admin role (if exists)
        $superAdminRole = DB::table($tableNames['roles'])
            ->where('name', 'super admin')
            ->first();
        
        if ($superAdminRole) {
            $permission = DB::table($tableNames['permissions'])
                ->where('name', 'delete sale order')
                ->where('guard_name', 'web')
                ->first();
            
            if ($permission) {
                $rolePermissionExists = DB::table($tableNames['role_has_permissions'])
                    ->where('role_id', $superAdminRole->id)
                    ->where('permission_id', $permission->id)
                    ->exists();
                
                if (!$rolePermissionExists) {
                    DB::table($tableNames['role_has_permissions'])->insert([
                        'permission_id' => $permission->id,
                        'role_id' => $superAdminRole->id,
                    ]);
                }
            }
        }

        // Assign permission directly to all company users (by type)
        $companyUsers = DB::table('users')
            ->where('type', 'company')
            ->get();
        
        if ($companyUsers->count() > 0) {
            $permission = DB::table($tableNames['permissions'])
                ->where('name', 'delete sale order')
                ->where('guard_name', 'web')
                ->first();
            
            if ($permission) {
                foreach ($companyUsers as $user) {
                    $userPermissionExists = DB::table($tableNames['model_has_permissions'])
                        ->where('model_type', 'App\Models\User')
                        ->where('model_id', $user->id)
                        ->where('permission_id', $permission->id)
                        ->exists();
                    
                    if (!$userPermissionExists) {
                        DB::table($tableNames['model_has_permissions'])->insert([
                            'permission_id' => $permission->id,
                            'model_type' => 'App\Models\User',
                            'model_id' => $user->id,
                        ]);
                    }
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
            ->where('name', 'delete sale order')
            ->where('guard_name', 'web')
            ->first();
        
        if ($permission) {
            // Remove from role_has_permissions
            DB::table($tableNames['role_has_permissions'])
                ->where('permission_id', $permission->id)
                ->delete();
            
            // Remove from model_has_permissions
            DB::table($tableNames['model_has_permissions'])
                ->where('permission_id', $permission->id)
                ->delete();
            
            // Remove the permission
            DB::table($tableNames['permissions'])
                ->where('id', $permission->id)
                ->delete();
        }
    }
};
