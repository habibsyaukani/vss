<?php

namespace App\Services;

use App\Models\HealingLog;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * SystemHealer - Auto-fix system issues
 * 
 * Capabilities:
 * 1. Refresh API token automatically
 * 2. Restart stuck scheduler (via command suggestion)
 * 3. Clear failed jobs
 * 4. Log all healing attempts
 */
class SystemHealer
{
    /**
     * Attempt to heal API token issues
     */
    public function healAPIToken(): array
    {
        $startTime = microtime(true);
        $detectedAt = now();

        $healingLog = HealingLog::create([
            'issue_type' => 'api_token',
            'severity' => 'warning',
            'problem_description' => 'API token expired or never refreshed',
            'healing_action' => 'refresh_api_token',
            'status' => 'attempted',
            'detected_at' => $detectedAt,
        ]);

        try {
            // Call the authentication command
            Artisan::call('howen:test-auth');
            $output = Artisan::output();

            // Update system setting
            SystemSetting::set('last_token_refresh', now()->toDateTimeString());

            // Calculate execution time
            $executionTime = (int)((microtime(true) - $startTime) * 1000);

            $healingLog->update([
                'status' => 'success',
                'result_message' => 'API token refreshed successfully. ' . trim($output),
                'healed_at' => now(),
                'execution_time_ms' => $executionTime,
            ]);

            SystemLogger::success('HEALING', 'Auto-healed API token issue', [
                'execution_time_ms' => $executionTime,
            ]);

            return [
                'success' => true,
                'message' => 'API token refreshed successfully',
                'execution_time_ms' => $executionTime,
            ];

        } catch (\Exception $e) {
            $executionTime = (int)((microtime(true) - $startTime) * 1000);

            $healingLog->update([
                'status' => 'failed',
                'result_message' => 'Failed to refresh token: ' . $e->getMessage(),
                'healed_at' => now(),
                'execution_time_ms' => $executionTime,
            ]);

            SystemLogger::error(
                'HEALING',
                'Failed to auto-heal API token',
                ['hint' => 'Check HOWEN_USERNAME and HOWEN_PASSWORD in .env'],
                $e
            );

            return [
                'success' => false,
                'message' => 'Failed to refresh token: ' . $e->getMessage(),
                'execution_time_ms' => $executionTime,
            ];
        }
    }

    /**
     * Clear failed jobs from queue
     */
    public function healFailedJobs(): array
    {
        $startTime = microtime(true);
        $detectedAt = now();

        $healingLog = HealingLog::create([
            'issue_type' => 'failed_jobs',
            'severity' => 'warning',
            'problem_description' => 'Multiple failed jobs detected in queue',
            'healing_action' => 'clear_failed_jobs',
            'status' => 'attempted',
            'detected_at' => $detectedAt,
        ]);

        try {
            // Get count before clearing
            $failedCount = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();

            // Clear failed jobs
            Artisan::call('queue:flush');

            $executionTime = (int)((microtime(true) - $startTime) * 1000);

            $healingLog->update([
                'status' => 'success',
                'result_message' => "Cleared {$failedCount} failed jobs from queue",
                'healed_at' => now(),
                'execution_time_ms' => $executionTime,
            ]);

            SystemLogger::success('HEALING', 'Auto-cleared failed jobs', [
                'failed_jobs_cleared' => $failedCount,
                'execution_time_ms' => $executionTime,
            ]);

            return [
                'success' => true,
                'message' => "Cleared {$failedCount} failed jobs",
                'execution_time_ms' => $executionTime,
            ];

        } catch (\Exception $e) {
            $executionTime = (int)((microtime(true) - $startTime) * 1000);

            $healingLog->update([
                'status' => 'failed',
                'result_message' => 'Failed to clear jobs: ' . $e->getMessage(),
                'healed_at' => now(),
                'execution_time_ms' => $executionTime,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to clear jobs: ' . $e->getMessage(),
                'execution_time_ms' => $executionTime,
            ];
        }
    }

    /**
     * Attempt to restart queue worker (via System Control API)
     */
    public function healQueueWorker(): array
    {
        $startTime = microtime(true);
        $detectedAt = now();

        $healingLog = HealingLog::create([
            'issue_type' => 'queue_worker',
            'severity' => 'warning',
            'problem_description' => 'Queue worker appears inactive',
            'healing_action' => 'restart_queue_worker',
            'status' => 'attempted',
            'detected_at' => $detectedAt,
        ]);

        try {
            // Call queue:restart command
            Artisan::call('queue:restart');

            $executionTime = (int)((microtime(true) - $startTime) * 1000);

            $healingLog->update([
                'status' => 'success',
                'result_message' => 'Queue worker restart signal sent. Worker should restart on next job.',
                'healed_at' => now(),
                'execution_time_ms' => $executionTime,
            ]);

            SystemLogger::success('HEALING', 'Queue worker restart triggered', [
                'execution_time_ms' => $executionTime,
            ]);

            return [
                'success' => true,
                'message' => 'Queue worker restart triggered',
                'execution_time_ms' => $executionTime,
            ];

        } catch (\Exception $e) {
            $executionTime = (int)((microtime(true) - $startTime) * 1000);

            $healingLog->update([
                'status' => 'failed',
                'result_message' => 'Failed to restart queue: ' . $e->getMessage(),
                'healed_at' => now(),
                'execution_time_ms' => $executionTime,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to restart queue: ' . $e->getMessage(),
                'execution_time_ms' => $executionTime,
            ];
        }
    }

    /**
     * Log manual healing attempt (for issues that require manual intervention)
     */
    public function logManualHealing(string $issueType, string $problem, string $suggestion): HealingLog
    {
        return HealingLog::create([
            'issue_type' => $issueType,
            'severity' => 'critical',
            'problem_description' => $problem,
            'healing_action' => 'manual_intervention_required',
            'status' => 'attempted',
            'result_message' => 'Requires manual action: ' . $suggestion,
            'detected_at' => now(),
        ]);
    }

    /**
     * Get healing statistics
     */
    public function getHealingStats(): array
    {
        $last24h = HealingLog::where('created_at', '>=', now()->subHours(24))->get();

        return [
            'total_attempts' => $last24h->count(),
            'successful' => $last24h->where('status', 'success')->count(),
            'failed' => $last24h->where('status', 'failed')->count(),
            'success_rate' => $last24h->count() > 0 
                ? round(($last24h->where('status', 'success')->count() / $last24h->count()) * 100, 1)
                : 100.0,
            'average_healing_time_ms' => $last24h->where('status', 'success')->avg('execution_time_ms'),
        ];
    }
}
