<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ResetApiUserPassword extends Command
{
    protected $signature = 'api:reset-password {email} {password}';

    protected $description = 'Reset password for an API user';

    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found!");
            return 1;
        }

        $user->password = Hash::make($password);
        $user->save();

        $this->info("✅ Password updated successfully for user: {$user->name} ({$user->email})");
        $this->info("User ID: {$user->id}");
        $this->info("Company ID: {$user->creatorId()}");

        return 0;
    }
}

