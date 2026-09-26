<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes & Scheduled Work
|--------------------------------------------------------------------------
|
| `clinic:backup` (App\Console\Commands\BackupLocal) was written but never
| scheduled, so the "external backup" the clinic relies on never actually ran.
|
| It writes a SQL dump plus a zip of the X-ray files to the path in
| BACKUP_PATH (usually a USB drive). For it to fire, the scheduler must be
| running on the clinic machine:
|
|   * dev / staff PC:  php artisan schedule:work
|   * production:      one cron entry —
|                      * * * * * cd /path/to/backend && php artisan schedule:run
|
| `withoutOverlapping` keeps a slow USB write from stacking up, and failures are
| logged by the scheduler so a missing drive is visible in storage/logs.
|
*/

Schedule::command('clinic:backup')
    ->dailyAt('21:00')
    ->withoutOverlapping();
