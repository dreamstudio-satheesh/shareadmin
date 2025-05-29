<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run every minute
       // $schedule->command('sync:positions')->everyMinute()->withoutOverlapping();
      //  $schedule->command('monitor:orders')->everyMinute()->withoutOverlapping();


        // Launch tick broadcasting once per trading day at 09:15
      //  $schedule->command('ticks:broadcast')->dailyAt('09:15');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
