<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class RolesToCurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::firstOrCreate(['name' => 'delete constant currency']);
        Permission::firstOrCreate(['name' => 'edit constant currency']);
        Permission::firstOrCreate(['name' => 'create constant currency']);
        $companyRole = Role::findByName('company');
        if ($companyRole) {
            $companyRole->givePermissionTo('delete constant currency');
            $companyRole->givePermissionTo('edit constant currency');
            $companyRole->givePermissionTo('create constant currency');
        } else {
            $companyRole = Role::create(['name' => 'company']);
            $companyRole->givePermissionTo('delete constant currency');
            $companyRole->givePermissionTo('edit constant currency');
            $companyRole->givePermissionTo('create constant currency');
        }
    }
}
