<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class MarkApiOnlyUser extends Command
{
    protected $signature = 'api:mark-api-only {email} {--remove : Remove API-only restriction}';

    protected $description = 'Mark a user as API-only (prevent web login)';

    public function handle()
    {
        $email = $this->argument('email');
        $remove = $this->option('remove');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found!");
            return 1;
        }

        $user->api_only = !$remove;
        $user->save();

        if ($remove) {
            $this->info("✅ Removed API-only restriction for user: {$user->name} ({$user->email})");
        } else {
            $this->info("✅ Marked user as API-only: {$user->name} ({$user->email})");
            $this->warn("⚠️  This user can now ONLY login via API, not through web interface.");
        }

        return 0;
    }
}

