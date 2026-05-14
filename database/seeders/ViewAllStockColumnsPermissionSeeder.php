<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class ViewAllStockColumnsPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Clear permission cache
        \Artisan::call('cache:forget spatie.permission.cache');
        \Artisan::call('cache:clear');

        // Create or get the permission
        $permission = Permission::firstOrCreate(
            ['name' => 'view_all_stock_columns'],
            ['guard_name' => 'web']
        );

        $this->command->info("Permission 'view_all_stock_columns' created or already exists.");

        // Assign permission to company role (if exists)
        $companyRole = Role::where('name', 'company')->first();
        if ($companyRole) {
            if (!$companyRole->hasPermissionTo($permission)) {
                $companyRole->givePermissionTo($permission);
                $this->command->info("Assigned permission 'view_all_stock_columns' to company role.");
            } else {
                $this->command->warn("Company role already has permission 'view_all_stock_columns'.");
            }
        } else {
            $this->command->warn("Company role not found. Skipping role assignment.");
        }

        // Assign permission directly to all company users (by type)
        $companyUsers = User::where('type', 'company')->get();
        $assignedCount = 0;

        foreach ($companyUsers as $user) {
            if (!$user->hasPermissionTo($permission)) {
                $user->givePermissionTo($permission);
                $assignedCount++;
            }
        }

        if ($assignedCount > 0) {
            $this->command->info("Assigned permission 'view_all_stock_columns' to {$assignedCount} company user(s).");
        } else {
            $this->command->info("All company users already have permission 'view_all_stock_columns'.");
        }

        // Also assign to super admin role (if exists)
        $superAdminRole = Role::where('name', 'super admin')->first();
        if ($superAdminRole) {
            if (!$superAdminRole->hasPermissionTo($permission)) {
                $superAdminRole->givePermissionTo($permission);
                $this->command->info("Assigned permission 'view_all_stock_columns' to super admin role.");
            }
        }

        $this->command->info('View all stock columns permission seeded successfully!');
    }
}

