<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected $commands = [
        \App\Console\Commands\RefreshFacebookToken::class,
        \App\Console\Commands\BackfillLeadParsedQuantity::class,
        \App\Console\Commands\GenerateNoStockDealReminders::class,
    ];
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('facebook:refresh-token')->monthly();
        $schedule->command('facebook:fetch-leads')->everyFiveMinutes(); // or ->daily()
        $schedule->command('reminders:generate-no-stock')->dailyAt('02:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
