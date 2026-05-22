<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use App\Models\DealReminder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Closure;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        DB::listen(function ($query) {
            if (app()->environment('production')) {
                URL::forceScheme('https');
            }
            if ($query->time > 500) { // log queries slower than 0.5 seconds
                Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time_ms' => $query->time,
                ]);
            }
        });
        Schema::defaultStringLength(191);

        View::composer('*', function ($view) {
            try {
                $user = Auth::user();
                if (!$user) {
                    return;
                }
                if ($user->type == 'company') {
                    $userIds = User::where('created_by', $user->id)->pluck('id');
                    $reminders = DealReminder::where(function($q) use ($userIds, $user) {
                        $q->whereIn('user_id', $userIds)->orWhere('user_id', $user->id);
                    })->where('is_done', false)->latest()->limit(5)->get();
                } elseif ($user->type == 'manager') {
                    $managed = User::where('manager_id', $user->id)->pluck('id')->push($user->id);
                    $reminders = DealReminder::whereIn('user_id', $managed)->where('is_done', false)->latest()->limit(5)->get();
                } else {
                    $reminders = DealReminder::where('user_id', $user->id)->where('is_done', false)->latest()->limit(5)->get();
                }
                $view->with('headerDealReminders', $reminders);
            } catch (\Throwable $e) {
                // silent
            }
        });
    }
    }


