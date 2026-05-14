<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FixUserLogin extends Command
{
    protected $signature = 'user:fix-login {email} {--fix : Automatically fix issues}';
    protected $description = 'Diagnose and fix user login issues';

    public function handle()
    {
        $email = $this->argument('email');
        $fix = $this->option('fix');

        $this->info("=== User Login Diagnostic Tool ===");
        $this->info("Checking user: {$email}\n");

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User not found with email: {$email}");
            return 1;
        }

        $this->info("User found: {$user->name} (ID: {$user->id})");
        $this->info("User Type: {$user->type}");
        $this->info("Created By: {$user->created_by}\n");

        $issues = [];
        $fixed = [];

        // Check 1: is_active
        $this->line("1. Checking is_active status...");
        if ($user->is_active == 0) {
            $this->warn("   ❌ User is inactive (is_active = 0)");
            $issues[] = 'is_active';
            if ($fix) {
                $user->is_active = 1;
                $user->save();
                $fixed[] = 'is_active';
                $this->info("   ✅ Fixed: Set is_active to 1");
            }
        } else {
            $this->info("   ✅ User is active (is_active = {$user->is_active})");
        }

        // Check 2: delete_status
        $this->line("2. Checking delete_status...");
        if ($user->delete_status == 0) {
            $this->warn("   ❌ User is marked as deleted (delete_status = 0)");
            $issues[] = 'delete_status';
            if ($fix) {
                $user->delete_status = 1;
                $user->save();
                $fixed[] = 'delete_status';
                $this->info("   ✅ Fixed: Set delete_status to 1");
            }
        } else {
            $this->info("   ✅ User is not deleted (delete_status = {$user->delete_status})");
        }

        // Check 3: is_disable
        $this->line("3. Checking is_disable status...");
        if ($user->is_disable == 0) {
            $this->warn("   ❌ User account is disabled (is_disable = 0)");
            $issues[] = 'is_disable';
            if ($fix) {
                $user->is_disable = 1;
                $user->save();
                $fixed[] = 'is_disable';
                $this->info("   ✅ Fixed: Set is_disable to 1");
            }
        } else {
            $this->info("   ✅ User account is enabled (is_disable = {$user->is_disable})");
        }

        // Check 4: email_verified_at
        $this->line("4. Checking email verification...");
        if (empty($user->email_verified_at)) {
            $this->warn("   ❌ Email is not verified (email_verified_at is null)");
            $issues[] = 'email_verified_at';
            if ($fix) {
                $user->email_verified_at = now();
                $user->save();
                $fixed[] = 'email_verified_at';
                $this->info("   ✅ Fixed: Set email_verified_at to current timestamp");
            }
        } else {
            $this->info("   ✅ Email is verified ({$user->email_verified_at})");
        }

        // Check 5: User type
        $this->line("5. Checking user type...");
        $validTypes = ['company', 'super admin', 'client', 'employee', 'user', 'admin', 'manager', 'Sales'];
        if (empty($user->type) || !in_array($user->type, $validTypes)) {
            $this->warn("   ❌ User type is invalid or empty: '{$user->type}'");
            $issues[] = 'type';
            if ($fix && empty($user->type)) {
                // Set a default type based on created_by
                if ($user->created_by == $user->id || empty($user->created_by)) {
                    $user->type = 'company';
                } else {
                    $user->type = 'employee';
                }
                $user->save();
                $fixed[] = 'type';
                $this->info("   ✅ Fixed: Set type to '{$user->type}'");
            }
        } else {
            $this->info("   ✅ User type is valid: '{$user->type}'");
        }

        // Check 6: Dashboard permissions
        $this->line("6. Checking dashboard permissions...");
        $dashboardPermissions = [
            'show account dashboard',
            'show hrm dashboard',
            'show crm dashboard',
            'show project dashboard',
            'show pos dashboard'
        ];

        $hasAnyDashboardPermission = false;
        $userPermissions = [];

        foreach ($dashboardPermissions as $permission) {
            if ($user->can($permission)) {
                $hasAnyDashboardPermission = true;
                $userPermissions[] = $permission;
            }
        }

        if (!$hasAnyDashboardPermission) {
            $this->warn("   ❌ User has NO dashboard permissions");
            $issues[] = 'dashboard_permissions';
            
            if ($fix) {
                // Try to find the permission
                $permission = Permission::where('name', 'show account dashboard')->first();
                
                if ($permission) {
                    // Check if user has a role
                    $roles = $user->roles;
                    
                    if ($roles->isEmpty()) {
                        $this->warn("   ⚠️  User has no roles assigned. Cannot assign permission directly.");
                        $this->info("   💡 Suggestion: Assign a role to this user that has dashboard permissions.");
                    } else {
                        // Assign permission to the first role
                        $role = $roles->first();
                        if (!$role->hasPermissionTo($permission)) {
                            $role->givePermissionTo($permission);
                            $this->info("   ✅ Fixed: Added 'show account dashboard' permission to role '{$role->name}'");
                            $fixed[] = 'dashboard_permissions';
                        } else {
                            $this->info("   ℹ️  Role '{$role->name}' already has this permission, but user might need to refresh.");
                        }
                    }
                } else {
                    $this->error("   ❌ Permission 'show account dashboard' not found in database!");
                    $this->info("   💡 You may need to run: php artisan db:seed --class=UsersTableSeeder");
                }
            }
        } else {
            $this->info("   ✅ User has dashboard permissions:");
            foreach ($userPermissions as $perm) {
                $this->line("      - {$perm}");
            }
        }

        // Check 7: Roles
        $this->line("7. Checking user roles...");
        $roles = $user->roles;
        if ($roles->isEmpty()) {
            $this->warn("   ❌ User has no roles assigned");
            $issues[] = 'roles';
            
            if ($fix) {
                // Try to find a default role based on user type
                $defaultRoleName = null;
                if ($user->type == 'company') {
                    $defaultRoleName = 'company';
                } elseif ($user->type == 'employee' || $user->type == 'user') {
                    $defaultRoleName = 'employee';
                }
                
                if ($defaultRoleName) {
                    $role = Role::where('name', $defaultRoleName)
                        ->where('created_by', $user->creatorId())
                        ->first();
                    
                    if ($role) {
                        $user->assignRole($role);
                        $this->info("   ✅ Fixed: Assigned role '{$role->name}' to user");
                        $fixed[] = 'roles';
                    } else {
                        $this->warn("   ⚠️  Default role '{$defaultRoleName}' not found for creator ID {$user->creatorId()}");
                    }
                }
            }
        } else {
            $this->info("   ✅ User has roles:");
            foreach ($roles as $role) {
                $this->line("      - {$role->name} (ID: {$role->id})");
            }
        }

        // Summary
        $this->newLine();
        $this->info("=== SUMMARY ===");
        
        if (empty($issues)) {
            $this->info("✅ No issues found! User should be able to login.");
        } else {
            $this->warn("⚠️  Found " . count($issues) . " issue(s):");
            foreach ($issues as $issue) {
                $this->line("   - {$issue}");
            }
            
            if ($fix && !empty($fixed)) {
                $this->newLine();
                $this->info("✅ Fixed " . count($fixed) . " issue(s):");
                foreach ($fixed as $fixItem) {
                    $this->line("   - {$fixItem}");
                }
                $this->newLine();
                $this->info("💡 User should now be able to login. Please test the login.");
            } else {
                $this->newLine();
                $this->info("💡 To automatically fix these issues, run:");
                $this->line("   php artisan user:fix-login {$email} --fix");
            }
        }

        return 0;
    }
}

