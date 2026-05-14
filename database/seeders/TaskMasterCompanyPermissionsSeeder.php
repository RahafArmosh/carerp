<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Ensures Task Master permissions exist and assigns them to the company role by default.
 * Safe to run multiple times (e.g. existing installs that missed the migration).
 */
class TaskMasterCompanyPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $names = [
            'view task master',
            'manage task master',
            'create task master',
            'edit task master',
            'delete task master',
        ];

        foreach ($names as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web']
            );
        }

        $companyRole = Role::where('name', 'company')->first();
        if ($companyRole) {
            foreach ($names as $name) {
                if (!$companyRole->hasPermissionTo($name)) {
                    $companyRole->givePermissionTo($name);
                }
            }
            if (isset($this->command)) {
                $this->command->info('Task Master permissions assigned to company role.');
            }
        }

        $superAdminRole = Role::where('name', 'super admin')->first();
        if ($superAdminRole) {
            foreach ($names as $name) {
                if (!$superAdminRole->hasPermissionTo($name)) {
                    $superAdminRole->givePermissionTo($name);
                }
            }
            if (isset($this->command)) {
                $this->command->info('Task Master permissions assigned to super admin role.');
            }
        }
    }
}
