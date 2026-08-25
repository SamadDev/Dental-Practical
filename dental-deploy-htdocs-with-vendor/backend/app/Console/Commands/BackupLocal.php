<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use ZipArchive;

/**
 * Daily local backup:
 *   1) Dumps MySQL → .sql
 *   2) Zips X-rays directory
 *   3) Copies both to BACKUP_PATH (e.g., a mounted USB drive)
 *
 * Schedule in app/Console/Kernel.php:
 *   $schedule->command('clinic:backup')->dailyAt('23:00');
 */
class BackupLocal extends Command
{
    protected $signature   = 'clinic:backup {--path= : Override destination path}';
    protected $description = 'Export daily SQL dump + zipped X-rays to an external local path (e.g., USB).';

    public function handle(): int
    {
        $destination = $this->option('path')
            ?? env('BACKUP_PATH', storage_path('app/backups'));

        if (! File::isDirectory($destination)) {
            File::makeDirectory($destination, 0775, true);
        }

        $stamp   = now()->format('Y-m-d_His');
        $sqlFile = "{$destination}/db_{$stamp}.sql";
        $zipFile = "{$destination}/xrays_{$stamp}.zip";

        // --- 1. mysqldump ---
        $proc = new Process([
            'mysqldump',
            '-h', config('database.connections.mysql.host'),
            '-P', (string) config('database.connections.mysql.port'),
            '-u', config('database.connections.mysql.username'),
            '-p' . config('database.connections.mysql.password'),
            config('database.connections.mysql.database'),
        ]);
        $proc->setTimeout(600);
        $proc->run();

        if (! $proc->isSuccessful()) {
            $this->error('mysqldump failed: ' . $proc->getErrorOutput());
            return self::FAILURE;
        }

        File::put($sqlFile, $proc->getOutput());
        $this->info("✓ DB dump → {$sqlFile}");

        // --- 2. zip X-rays ---
        $xrayDir = storage_path('app/public/xrays');
        if (File::isDirectory($xrayDir)) {
            $zip = new ZipArchive();
            if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                foreach (File::allFiles($xrayDir) as $file) {
                    $zip->addFile($file->getPathname(), 'xrays/' . $file->getRelativePathname());
                }
                $zip->close();
                $this->info("✓ X-rays zipped → {$zipFile}");
            } else {
                $this->warn('Could not open zip archive for writing.');
            }
        } else {
            $this->warn("X-ray directory missing: {$xrayDir}");
        }

        return self::SUCCESS;
    }
}
