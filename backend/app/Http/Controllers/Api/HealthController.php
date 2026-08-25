<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $deployFile = storage_path('app/deploy.json');
        $deploy = is_file($deployFile)
            ? json_decode((string) file_get_contents($deployFile), true)
            : [];

        $gitCommit = $deploy['commit'] ?? trim(shell_exec('git rev-parse --short HEAD 2>/dev/null') ?? 'unknown');
        $gitDate = $deploy['deployed_at'] ?? trim(shell_exec('git log -1 --format=%cI 2>/dev/null') ?? 'unknown');

        try {
            $connection = DB::select('SELECT 1');
            $dbOk = ! empty($connection);
            $latestMigration = DB::table('migrations')->orderByDesc('id')->value('migration');
            $dbError = null;
        } catch (\Throwable $e) {
            $dbOk = false;
            $latestMigration = null;
            $dbError = $e->getMessage();
        }

        return response()->json([
            'status' => $dbOk ? 'ok' : 'degraded',
            'app' => config('app.name'),
            'env' => config('app.env'),
            'laravel' => app()->version(),
            'php' => PHP_VERSION,
            'deploy' => [
                'commit' => $gitCommit,
                'deployed_at' => $gitDate,
            ],
            'database' => [
                'connected' => $dbOk,
                'latest_migration' => $latestMigration,
                'error' => $dbError,
            ],
            'time' => now()->toIso8601String(),
        ], $dbOk ? 200 : 503);
    }
}
