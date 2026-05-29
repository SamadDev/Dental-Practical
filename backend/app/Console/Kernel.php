<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Daily 23:00 backup → .sql dump + xrays.zip on configured BACKUP_PATH.
        $schedule->command('clinic:backup')
            ->dailyAt('23:00')
            ->onFailure(fn () => logger()->error('Daily clinic backup failed'));
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
