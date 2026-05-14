<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class NewModulePermissionsSeeder extends Seeder
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

        // Define new permissions
        $newPermissions = [
            // Voucher permissions
            'view voucher',
            'create voucher',
            'update voucher',
            'delete voucher',
            
            // POS Refund permissions
            'view pos refund',
            'create pos refund',
            'update pos refund',
            'delete pos refund',
            
            // Price List permissions
            'view price list',
            'create price list',
            'update price list',
            'delete price list',
            
            // Combo permissions
            'view combo',
            'create combo',
            'update combo',
            'delete combo',
            
            // Stock permissions
            'view stock',
            'create stock',
            'update stock',
            'delete stock',
            
            // Transfer permissions
            'view transfer',
            'create transfer',
            'update transfer',
            'delete transfer',

            // PRO permissions
            'view pro',
            'create pro',
            'update pro',
            'delete pro',

            // ASN permissions
            'view asn',
            'create asn',
            'update asn',
            'delete asn',

            // GRN permissions
            'view grn',
            'create grn',
            // Use "edit grn" to match role edit screen, keep "update grn" for backward compatibility
            'edit grn',
            'update grn',
            'delete grn',

            // Sale order permissions
            'view sale order',
            'create sale order',
            'update sale order',
            'delete sale order',

            // Advance sale order permissions
            'view advance sale order',
            'create advance sale order',
            'edit advance sale order',
            'update advance sale order',
            'delete advance sale order',

            // Picking permissions
            'view picking',
            'create picking',
            'update picking',
            'delete picking',

            // Packing permissions
            'view packing',
            'create packing',
            'update packing',
            'delete packing',

            // Employee Task Master (HRM)
            'view task master',
            'manage task master',
            'create task master',
            'edit task master',
            'delete task master',

            'manage daily task log',
        ];

        // Create permissions if they don't exist
        foreach ($newPermissions as $permission) {
            $existingPermission = Permission::where('name', $permission)->first();
            if (!$existingPermission) {
                Permission::create(['name' => $permission]);
                $this->command->info("Created permission: {$permission}");
            } else {
                $this->command->warn("Permission already exists: {$permission}");
            }
        }

        // Assign all permissions to company role (if exists)
        $companyRole = Role::where('name', 'company')->first();
        if ($companyRole) {
            foreach ($newPermissions as $permission) {
                if (!$companyRole->hasPermissionTo($permission)) {
                    $companyRole->givePermissionTo($permission);
                    $this->command->info("Assigned permission '{$permission}' to company role");
                }
            }
        }

        // Assign all permissions to super admin role (if exists)
        $superAdminRole = Role::where('name', 'super admin')->first();
        if ($superAdminRole) {
            foreach ($newPermissions as $permission) {
                if (!$superAdminRole->hasPermissionTo($permission)) {
                    $superAdminRole->givePermissionTo($permission);
                    $this->command->info("Assigned permission '{$permission}' to super admin role");
                }
            }
        }

        $this->command->info('New module permissions seeded successfully!');
    }
}

