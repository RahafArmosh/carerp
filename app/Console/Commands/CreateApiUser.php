<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Plan;
use App\Models\Utility;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CreateApiUser extends Command
{
    protected $signature = 'api:create-user 
                            {email : The email address for the API user}
                            {password : The password for the API user}
                            {--company_id= : The company ID (created_by). If not provided, will prompt}
                            {--name= : The name for the user (defaults to email)}';

    protected $description = 'Create an API user for POS API access';

    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');
        $companyId = $this->option('company_id');
        $name = $this->option('name') ?: $email;

        // Check if user already exists
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            $this->error("User with email {$email} already exists!");
            $this->info("User ID: {$existingUser->id}");
            $this->info("Company ID: {$existingUser->creatorId()}");
            return 1;
        }

        // Get company ID
        if (!$companyId) {
            $this->info("Available companies:");
            $companies = User::where('type', 'company')
                ->orWhere('type', 'super admin')
                ->select('id', 'name', 'email', 'type')
                ->get();
            
            foreach ($companies as $company) {
                $this->line("  ID: {$company->id} - {$company->name} ({$company->email}) - Type: {$company->type}");
            }
            
            $companyId = $this->ask('Enter the Company ID (created_by)');
        }

        $company = User::find($companyId);
        if (!$company || ($company->type != 'company' && $company->type != 'super admin')) {
            $this->error("Invalid company ID. Must be a company or super admin user.");
            return 1;
        }

        // Get default language
        $defaultLanguage = DB::table('settings')
            ->where('name', 'default_language')
            ->where('created_by', $companyId)
            ->value('value') ?: 'en';

        // Get plan
        $plan = Plan::where('id', $company->plan)->first();
        if (!$plan) {
            $plan = Plan::first();
        }

        // Check user limit
        $totalUsers = User::where('created_by', $companyId)
            ->where('type', '!=', 'client')
            ->count();

        if ($plan->max_users != -1 && $totalUsers >= $plan->max_users) {
            $this->error("User limit reached for this company. Max users: {$plan->max_users}");
            return 1;
        }

        // Find or create employee role
        $role = Role::where('name', 'employee')
            ->where('created_by', $companyId)
            ->first();

        if (!$role) {
            // Try to find any role for this company
            $role = Role::where('created_by', $companyId)
                ->where('name', '!=', 'client')
                ->first();
        }

        if (!$role) {
            $this->warn("No suitable role found. Creating user without role assignment.");
        }

        // Create user
        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'type' => $role ? $role->name : 'employee',
                'created_by' => $companyId,
                'lang' => $defaultLanguage,
                'email_verified_at' => now(),
                'is_active' => 1,
                'plan' => $plan->id ?? 1,
            ]);

            // Assign role if found
            if ($role) {
                $user->assignRole($role);
                $this->info("Assigned role: {$role->name}");
            }

            // Create employee details if not client
            if ($user->type != 'client') {
                Utility::employeeDetails($user->id, $companyId);
                $this->info("Created employee details");
            }

            DB::commit();

            $this->info("\n✅ User created successfully!");
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $user->id],
                    ['Name', $user->name],
                    ['Email', $user->email],
                    ['Type', $user->type],
                    ['Company ID', $user->creatorId()],
                    ['Role', $role ? $role->name : 'None'],
                    ['Is Active', $user->is_active ? 'Yes' : 'No'],
                ]
            );

            $this->info("\n📝 To get a token, use the POS API login endpoint:");
            $this->line("POST /api/pos-api/login");
            $this->line("Body: {");
            $this->line("  \"email\": \"{$email}\",");
            $this->line("  \"password\": \"{$password}\"");
            $this->line("}");

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error creating user: " . $e->getMessage());
            return 1;
        }
    }
}

