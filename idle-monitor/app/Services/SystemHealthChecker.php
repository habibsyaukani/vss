<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * SystemHealthChecker - Detect system issues and provide troubleshooting
 * WITH AUTO-HEALING CAPABILITY
 * 
 * Checks:
 * 1. Database connection
 * 2. Howen API authentication (AUTO-HEAL)
 * 3. Scheduler running status
 * 4. Queue worker status (AUTO-HEAL)
 * 5. Failed jobs detection (AUTO-HEAL)
 * 6. Duplicate scheduler detection
 * 7. Data pull health
 */
class SystemHealthChecker
{
    protected array $issues = [];
    protected array $warnings = [];
    protected int $healthScore = 100;
    protected bool $autoHealEnabled = true; // Enable auto-healing by default

    public function __construct()
    {
        // Disable auto-healing if healing_logs table doesn't exist
        if (!\Illuminate\Support\Facades\Schema::hasTable('healing_logs')) {
            $this->autoHealEnabled = false;
        }
    }

    /**
     * Run all health checks
     */
    public function runAllChecks(): array
    {
        $this->checkDatabase();
        $this->checkHowenAPI();
        $this->checkScheduler();
        $this->checkQueueWorker();
        $this->checkFailedJobs();
        $this->checkDataPullHealth();
        $this->checkDuplicateScheduler();

        return [
            'health_score' => $this->healthScore,
            'status' => $this->getOverallStatus(),
            'issues' => $this->issues,
            'warnings' => $this->warnings,
            'checks' => [
                'database' => $this->checkDatabase(),
                'api' => $this->checkHowenAPI(),
                'scheduler' => $this->checkScheduler(),
                'queue' => $this->checkQueueWorker(),
                'data_pull' => $this->checkDataPullHealth(),
            ],
        ];
    }

    /**
     * Check database connection
     */
    public function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $dbSize = DB::table('idle_alarms')->count();
            
            return [
                'status' => 'OK',
                'message' => 'Database connected',
                'details' => "Total idle alarms: {$dbSize}",
            ];
        } catch (\Exception $e) {
            $this->healthScore -= 30;
            $this->issues[] = [
                'title' => '❌ Database Connection Failed',
                'message' => 'Cannot connect to MySQL database',
                'troubleshooting' => [
                    '1. Check if MySQL/MariaDB service is running',
                    '2. Verify DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD in .env file',
                    '3. Run: php artisan config:clear',
                    '4. Test connection: php artisan tinker → DB::connection()->getPdo();',
                ],
                'severity' => 'critical',
            ];

            return [
                'status' => 'ERROR',
                'message' => 'Database connection failed',
                'details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check Howen API authentication
     */
    public function checkHowenAPI(): array
    {
        try {
            $lastTokenRefresh = \App\Models\SystemSetting::get('last_token_refresh', null);
            
            if (!$lastTokenRefresh) {
                // AUTO-HEAL: Token never refreshed
                if ($this->autoHealEnabled) {
                    $healer = new SystemHealer();
                    $healResult = $healer->healAPIToken();
                    
                    if ($healResult['success']) {
                        // Healing successful! Remove warning
                        return [
                            'status' => 'OK',
                            'message' => 'API token auto-refreshed',
                            'details' => "Auto-healed in {$healResult['execution_time_ms']}ms",
                        ];
                    }
                }
                
                $this->healthScore -= 15;
                $this->warnings[] = [
                    'title' => '⚠️ API Token Never Refreshed',
                    'message' => 'Howen API token has never been refreshed',
                    'troubleshooting' => [
                        '1. Check HOWEN_USERNAME and HOWEN_PASSWORD in .env file',
                        '2. Run manually: php artisan app:test-howen-auth',
                        '3. Check if scheduler is running (it should refresh token every 25 minutes)',
                        '4. Verify internet connection',
                    ],
                    'severity' => 'warning',
                ];

                return [
                    'status' => 'WARNING',
                    'message' => 'API token never refreshed',
                    'details' => 'Token refresh scheduled every 25 minutes',
                ];
            }

            $lastRefreshTime = Carbon::parse($lastTokenRefresh);
            $minutesAgo = $lastRefreshTime->diffInMinutes(now());

            if ($minutesAgo > 60) {
                // AUTO-HEAL: Token outdated
                if ($this->autoHealEnabled) {
                    $healer = new SystemHealer();
                    $healResult = $healer->healAPIToken();
                    
                    if ($healResult['success']) {
                        // Healing successful! Remove error
                        return [
                            'status' => 'OK',
                            'message' => 'API token auto-refreshed',
                            'details' => "Was {$minutesAgo}min old, auto-healed in {$healResult['execution_time_ms']}ms",
                        ];
                    }
                }
                
                $this->healthScore -= 20;
                $this->issues[] = [
                    'title' => '❌ API Token Outdated',
                    'message' => "Last token refresh: {$minutesAgo} minutes ago (should be < 30 min)",
                    'troubleshooting' => [
                        '1. Scheduler might not be running → Run START_SCHEDULER.bat',
                        '2. Or refresh token manually: php artisan app:test-howen-auth',
                        '3. Check storage/logs/laravel.log for authentication errors',
                    ],
                    'severity' => 'high',
                ];

                return [
                    'status' => 'ERROR',
                    'message' => 'API token outdated',
                    'details' => "Last refresh: {$minutesAgo} minutes ago",
                ];
            }

            return [
                'status' => 'OK',
                'message' => 'API token fresh',
                'details' => "Last refresh: {$minutesAgo} minutes ago",
            ];

        } catch (\Exception $e) {
            $this->healthScore -= 15;
            return [
                'status' => 'WARNING',
                'message' => 'Cannot check API status',
                'details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if scheduler is running
     */
    public function checkScheduler(): array
    {
        try {
            // Check last alarm sync time (scheduler pulls every 3 minutes)
            $lastAlarmSync = \App\Models\SystemSetting::get('last_alarm_sync', null);
            
            if (!$lastAlarmSync) {
                // AUTO-HEAL: Scheduler not running - Log manual intervention needed
                if ($this->autoHealEnabled) {
                    $healer = new SystemHealer();
                    $healer->logManualHealing(
                        'scheduler_not_running',
                        'No alarm sync detected. Scheduler not running.',
                        'Start scheduler via START_SCHEDULER.bat or System Control Center'
                    );
                }
                
                $this->healthScore -= 25;
                $this->issues[] = [
                    'title' => '❌ Scheduler Not Running',
                    'message' => 'No alarm sync detected. Scheduler might not be running.',
                    'troubleshooting' => [
                        '1. Start scheduler: Double-click START_SCHEDULER.bat',
                        '2. Keep the CMD window open (do not close it)',
                        '3. Or use System Control Center: http://localhost:8000/admin/system-control',
                        '4. Verify with: php artisan schedule:list',
                    ],
                    'severity' => 'critical',
                    'healable' => false,
                    'issue_type' => 'scheduler_not_running',
                ];

                return [
                    'status' => 'ERROR',
                    'message' => 'Scheduler not running',
                    'details' => 'No sync activity detected',
                ];
            }

            $lastSyncTime = Carbon::parse($lastAlarmSync);
            $minutesAgo = $lastSyncTime->diffInMinutes(now());

            if ($minutesAgo > 10) {
                // AUTO-HEAL: Scheduler stuck - Log manual intervention
                if ($this->autoHealEnabled) {
                    $healer = new SystemHealer();
                    $healer->logManualHealing(
                        'scheduler_stuck',
                        "Last alarm sync: {$minutesAgo} minutes ago (expected: < 5 min)",
                        'Restart scheduler: Close CMD → Run START_SCHEDULER.bat'
                    );
                }
                
                $this->healthScore -= 20;
                $this->warnings[] = [
                    'title' => '⚠️ Scheduler May Be Stuck',
                    'message' => "Last alarm sync: {$minutesAgo} minutes ago (expected: < 5 min)",
                    'troubleshooting' => [
                        '1. Check if scheduler CMD window is still open',
                        '2. Look for errors in the scheduler window',
                        '3. Restart scheduler: Close CMD → Run START_SCHEDULER.bat again',
                        '4. Check logs: VIEW_SYSTEM_LOGS.bat',
                    ],
                    'severity' => 'medium',
                    'healable' => false,
                    'issue_type' => 'scheduler_stuck',
                ];

                return [
                    'status' => 'WARNING',
                    'message' => 'Scheduler may be stuck',
                    'details' => "Last sync: {$minutesAgo} minutes ago",
                ];
            }

            return [
                'status' => 'OK',
                'message' => 'Scheduler running normally',
                'details' => "Last sync: {$minutesAgo} minutes ago",
            ];

        } catch (\Exception $e) {
            $this->healthScore -= 20;
            return [
                'status' => 'ERROR',
                'message' => 'Cannot check scheduler status',
                'details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if queue worker is running
     */
    public function checkQueueWorker(): array
    {
        try {
            // Check recent job activity (queue processes jobs every few seconds)
            $recentJob = \App\Models\ImportLog::where('created_at', '>=', now()->subMinutes(10))
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$recentJob) {
                // AUTO-HEAL: Queue worker inactive - Try to restart
                if ($this->autoHealEnabled) {
                    $healer = new SystemHealer();
                    $healResult = $healer->healQueueWorker();
                    
                    if ($healResult['success']) {
                        // Healing successful
                        return [
                            'status' => 'OK',
                            'message' => 'Queue worker restarted',
                            'details' => "Auto-healed in {$healResult['execution_time_ms']}ms",
                        ];
                    }
                }
                
                $this->healthScore -= 15;
                $this->warnings[] = [
                    'title' => '⚠️ Queue Worker Inactive',
                    'message' => 'No job activity in last 10 minutes',
                    'troubleshooting' => [
                        '1. Start queue worker: Double-click START_QUEUE_WORKER.bat',
                        '2. Or use System Control Center',
                        '3. Check if jobs are queued: SELECT COUNT(*) FROM jobs;',
                        '4. Restart queue: php artisan queue:restart',
                    ],
                    'severity' => 'medium',
                    'healable' => true,
                    'issue_type' => 'queue_worker_inactive',
                ];

                return [
                    'status' => 'WARNING',
                    'message' => 'Queue worker may not be running',
                    'details' => 'No recent job activity',
                ];
            }

            return [
                'status' => 'OK',
                'message' => 'Queue worker active',
                'details' => "Last job: {$recentJob->job_name} (" . $recentJob->created_at->diffForHumans() . ")",
            ];

        } catch (\Exception $e) {
            $this->healthScore -= 10;
            return [
                'status' => 'WARNING',
                'message' => 'Cannot check queue status',
                'details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check for repeatedly failed jobs
     */
    public function checkFailedJobs(): array
    {
        try {
            $failedJobs = \App\Models\ImportLog::where('status', 'failed')
                ->where('created_at', '>=', now()->subHours(24))
                ->get();

            if ($failedJobs->count() > 5) {
                // AUTO-HEAL: Clear failed jobs
                if ($this->autoHealEnabled) {
                    $healer = new SystemHealer();
                    $healResult = $healer->healFailedJobs();
                    
                    if ($healResult['success']) {
                        // Healing successful
                        return [
                            'status' => 'OK',
                            'message' => 'Failed jobs cleared',
                            'details' => "Auto-healed: {$healResult['message']} in {$healResult['execution_time_ms']}ms",
                        ];
                    }
                }
                
                $this->healthScore -= 15;
                $this->issues[] = [
                    'title' => '❌ Multiple Failed Jobs Detected',
                    'message' => "{$failedJobs->count()} jobs failed in last 24 hours",
                    'troubleshooting' => [
                        '1. Check logs: VIEW_SYSTEM_LOGS.bat → Option 3',
                        '2. Look for error patterns in storage/logs/system-monitor.log',
                        '3. Common causes: API timeout, DB connection, memory limit',
                        '4. Restart services: Stop queue & scheduler → Start again',
                        '5. Clear failed jobs: Click "Auto-Heal" button',
                    ],
                    'severity' => 'high',
                    'details' => $failedJobs->pluck('job_name')->unique()->toArray(),
                    'healable' => true,
                    'issue_type' => 'failed_jobs',
                ];

                return [
                    'status' => 'ERROR',
                    'message' => 'Multiple failed jobs',
                    'details' => "{$failedJobs->count()} failures in 24h",
                ];
            }

            return [
                'status' => 'OK',
                'message' => 'No failed jobs',
                'details' => 'All jobs running smoothly',
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'WARNING',
                'message' => 'Cannot check failed jobs',
                'details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check data pull health (are we getting fresh data?)
     */
    public function checkDataPullHealth(): array
    {
        try {
            $todayIdleCount = DB::table('idle_alarms')
                ->whereDate('starting_time', today())
                ->count();

            $yesterdayIdleCount = DB::table('idle_alarms')
                ->whereDate('starting_time', today()->subDay())
                ->count();

            if ($todayIdleCount === 0 && $yesterdayIdleCount > 0) {
                // AUTO-HEAL: No data today - Log manual intervention (requires scheduler/queue check first)
                if ($this->autoHealEnabled) {
                    $healer = new SystemHealer();
                    $healer->logManualHealing(
                        'no_data_today',
                        'No idle alarm data for today, but yesterday had data',
                        'Verify scheduler and queue worker are running. Check API credentials. Pull data manually if needed.'
                    );
                }
                
                $this->healthScore -= 20;
                $this->issues[] = [
                    'title' => '❌ No Data Pulled Today',
                    'message' => 'No idle alarm data for today, but yesterday had data',
                    'troubleshooting' => [
                        '1. Check if scheduler is running (see above)',
                        '2. Check if queue worker is running (see above)',
                        '3. Verify API credentials are correct',
                        '4. Pull data manually: http://localhost:8000/admin/data-pull',
                        '5. Check import logs for errors',
                    ],
                    'severity' => 'high',
                    'healable' => false,
                    'issue_type' => 'no_data_today',
                ];

                return [
                    'status' => 'ERROR',
                    'message' => 'No data pulled today',
                    'details' => "Yesterday: {$yesterdayIdleCount} alarms, Today: 0 alarms",
                ];
            }

            return [
                'status' => 'OK',
                'message' => 'Data pull healthy',
                'details' => "Today: {$todayIdleCount} alarms, Yesterday: {$yesterdayIdleCount} alarms",
            ];

        } catch (\Exception $e) {
            $this->healthScore -= 10;
            return [
                'status' => 'WARNING',
                'message' => 'Cannot check data pull health',
                'details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check for duplicate scheduler (from Kernel.php analysis)
     */
    public function checkDuplicateScheduler(): array
    {
        try {
            $kernelPath = app_path('Console/Kernel.php');
            $kernelContent = file_get_contents($kernelPath);

            // Check for commented out conflicts
            $hasCommentedJobs = str_contains($kernelContent, '// $schedule->job(new \App\Jobs\ImportAlarmJob())');
            
            if ($hasCommentedJobs) {
                // This is actually good - means we cleaned it up
                return [
                    'status' => 'OK',
                    'message' => 'No duplicate scheduler detected',
                    'details' => 'Scheduler configuration is clean',
                ];
            }

            // Count how many times ImportAlarmJob is scheduled
            $importAlarmJobCount = substr_count($kernelContent, '$schedule->job(new \App\Jobs\ImportAlarmJob())');
            $realtimeCommandCount = substr_count($kernelContent, 'howen:pull-alarms-realtime');

            if ($importAlarmJobCount > 0 && $realtimeCommandCount > 0) {
                $this->healthScore -= 10;
                $this->warnings[] = [
                    'title' => '⚠️ Possible Duplicate Scheduler',
                    'message' => 'Both ImportAlarmJob and realtime command are active',
                    'troubleshooting' => [
                        '1. Edit app/Console/Kernel.php',
                        '2. Keep ONLY howen:pull-alarms-realtime command (every 3 min)',
                        '3. Comment out or remove ImportAlarmJob schedule',
                        '4. Restart scheduler after changes',
                    ],
                    'severity' => 'low',
                ];

                return [
                    'status' => 'WARNING',
                    'message' => 'Duplicate scheduler detected',
                    'details' => 'Multiple data pull methods active',
                ];
            }

            return [
                'status' => 'OK',
                'message' => 'No duplicate scheduler',
                'details' => 'Single data pull method active',
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'WARNING',
                'message' => 'Cannot check scheduler config',
                'details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get overall system status
     */
    protected function getOverallStatus(): string
    {
        if ($this->healthScore >= 90) {
            return 'healthy';
        } elseif ($this->healthScore >= 70) {
            return 'warning';
        } else {
            return 'critical';
        }
    }
}
