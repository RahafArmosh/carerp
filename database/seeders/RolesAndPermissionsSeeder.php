<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::firstOrCreate(['name' => 'delete lead role condition']);
        Permission::firstOrCreate(['name' => 'delete transfer']);
        $companyRole = Role::findByName('company');
        if ($companyRole) {
            $companyRole->givePermissionTo('delete lead role condition');
            $companyRole->givePermissionTo('delete transfer');
        } else {
            $companyRole = Role::create(['name' => 'company']);
            $companyRole->givePermissionTo('delete lead role condition');
            $companyRole->givePermissionTo('delete transfer');
        }
    }
}
