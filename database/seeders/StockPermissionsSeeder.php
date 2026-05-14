<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class StockPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Create permissions
        $viewAll = Permission::firstOrCreate(['name' => 'view_all_stock_columns']);
        $viewLimited = Permission::firstOrCreate(['name' => 'view_limited_stock_columns']);

        // Assign permissions based on role
        $admins = User::role('admin')->get();
        $companies = User::role('company')->get();

        foreach ($admins as $admin) {
            $admin->givePermissionTo($viewAll);
        }

        foreach ($companies as $companyUser) {
            $companyUser->givePermissionTo($viewLimited);
        }

        $this->command->info('Stock permissions created and assigned based on roles!');
    }
}

