<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class CreateSaleOrderPermissionSeeder extends Seeder
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
            ['name' => 'create sale order'],
            ['guard_name' => 'web']
        );

        $this->command->info("Permission 'create sale order' created or already exists.");

        // Assign permission to company role (if exists)
        $companyRole = Role::where('name', 'company')->first();
        if ($companyRole) {
            if (!$companyRole->hasPermissionTo($permission)) {
                $companyRole->givePermissionTo($permission);
                $this->command->info("Assigned permission 'create sale order' to company role.");
            } else {
                $this->command->warn("Company role already has permission 'create sale order'.");
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
            $this->command->info("Assigned permission 'create sale order' to {$assignedCount} company user(s).");
        } else {
            $this->command->info("All company users already have permission 'create sale order'.");
        }

        // Also assign to super admin role (if exists)
        $superAdminRole = Role::where('name', 'super admin')->first();
        if ($superAdminRole) {
            if (!$superAdminRole->hasPermissionTo($permission)) {
                $superAdminRole->givePermissionTo($permission);
                $this->command->info("Assigned permission 'create sale order' to super admin role.");
            } else {
                $this->command->warn("Super admin role already has permission 'create sale order'.");
            }
        } else {
            $this->command->warn("Super admin role not found. Skipping role assignment.");
        }

        $this->command->info('Create sale order permission seeded successfully!');
    }
}
