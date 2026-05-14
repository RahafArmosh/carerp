<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DailyTaskLogPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $name = 'manage daily task log';
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

        foreach (['company', 'super admin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role && !$role->hasPermissionTo($name)) {
                $role->givePermissionTo($name);
            }
        }

        foreach (Role::where('name', 'Employee')->get() as $role) {
            if (!$role->hasPermissionTo($name)) {
                $role->givePermissionTo($name);
            }
        }

        if (isset($this->command)) {
            $this->command->info('Daily task log permission synced.');
        }
    }
}
