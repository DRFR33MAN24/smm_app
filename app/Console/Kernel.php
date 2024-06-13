<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\UpdateProviderStatus;
use Illuminate\Support\Facades\Log;
class Kernel extends ConsoleKernel
{

       protected $commands = [

        UpdateProviderStatus::class,

    ];
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {





      \Artisan::call('update-provider:status');
        //$schedule->command('inspire')->hourly();
       // $schedule->command('update-provider:status')->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
