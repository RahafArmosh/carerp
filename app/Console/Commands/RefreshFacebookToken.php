<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\FacebookToken;

class RefreshFacebookToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'facebook:refresh-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh Facebook long-lived user and page token';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Refreshing Facebook tokens...');

        // 1. Get current token from DB
        $currentToken = FacebookToken::latest()->first();

        if (!$currentToken) {
            $this->error('No existing token found.');
            return 1;
        }

        // 2. Exchange for long-lived user token
        $response = Http::get('https://graph.facebook.com/v22.0/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => '1412380903378465',
            'client_secret' => '836be3e89b0d8e70d4a1304f369e3ccd',
            'fb_exchange_token' => $currentToken->user_token,
        ]);

        if (!$response->successful()) {
            $this->error('User token refresh failed: ' . $response->body());
            return 1;
        }

        $newUserToken = $response['access_token'];

        // 3. Get page access token
        $pageId = '750475655068357'; // Replace with your actual Page ID

        $pageResponse = Http::get("https://graph.facebook.com/v22.0/{$pageId}", [
            'fields' => 'access_token',
            'access_token' => $newUserToken,
        ]);

        if (!$pageResponse->successful()) {
            $this->error('Page token fetch failed: ' . $pageResponse->body());
            return 1;
        }

        $pageAccessToken = $pageResponse->json('access_token');

        // 4. Save both tokens in DB
        FacebookToken::create([
            'user_token' => $newUserToken,
            'page_token' => $pageAccessToken,
            'expires_at' => now()->addDays(60),
        ]);

        $this->info('✅ Token refreshed and saved successfully.');
        return 0;
    }
}
