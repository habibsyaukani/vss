<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Jobs\CleanupOldRawDataJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SystemControlController extends Controller
{
    /**
     * Show system control dashboard
     */
    public function index()
    {
        try {
            // Load all needed settings in ONE query (was 8+ separate queries before)
            $allSettings = SystemSetting::getMany([
                'cleanup_enabled',
                'cleanup_retention_days',
                'cleanup_last_run',
                'cleanup_schedule',
                'queue_worker_status',
                'queue_worker_started_at',
                'realtime_pull_status',
                'realtime_pull_started_at',
            ]);

            $queueStatus   = $this->buildQueueWorkerStatus($allSettings);
            $realtimeStatus = $this->buildRealtimePullStatus($allSettings);

            // Get cleanup settings with defaults
            try {
                $cleanupSettings = [
                    'cleanup_enabled'        => $allSettings['cleanup_enabled']        ?? false,
                    'cleanup_retention_days' => $allSettings['cleanup_retention_days'] ?? 30,
                    'cleanup_last_run'       => $allSettings['cleanup_last_run']       ?? null,
                    'cleanup_schedule'       => $allSettings['cleanup_schedule']       ?? 'monthly',
                ];
            } catch (\Exception $e) {
                Log::error('Failed to get cleanup settings: ' . $e->getMessage());
                $cleanupSettings = [
                    'cleanup_enabled'        => false,
                    'cleanup_retention_days' => 30,
                    'cleanup_last_run'       => null,
                    'cleanup_schedule'       => 'monthly',
                ];
            }

            // Get cleanup statistics with try-catch for safety
            try {
                $cleanupStats = $this->getCleanupStats();
            } catch (\Exception $e) {
                Log::error('Failed to get cleanup stats: ' . $e->getMessage());
                $cleanupStats = [
                    'alarm_raw' => ['total' => 0, 'old' => 0, 'will_delete' => 0],
                    'gps_raw'   => ['total' => 0, 'old' => 0, 'will_delete' => 0],
                    'cutoff_date' => now()->subDays(30)->toDateTimeString(),
                ];
            }

            return view('admin.system-control.index', compact('queueStatus', 'realtimeStatus', 'cleanupSettings', 'cleanupStats'));
        } catch (\Exception $e) {
            Log::error('System Control Index Error: ' . $e->getMessage());
            return response()->view('errors.500', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Start Queue Worker
     */
    public function startQueueWorker()
    {
        try {
            // Check if already running
            if ($this->isQueueWorkerRunning()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Queue worker is already running!'
                ]);
            }

            // Start queue worker as background process
            $phpPath = 'C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe';
            $artisanPath = base_path('artisan');
            
            // Windows: Use START /B to run in background
            $command = "start /B {$phpPath} {$artisanPath} queue:work --tries=3 --timeout=3600 > nul 2>&1";
            
            pclose(popen($command, 'r'));
            
            // Wait a moment for process to start
            sleep(2);
            
            // Save status
            SystemSetting::set('queue_worker_status', 'running');
            SystemSetting::set('queue_worker_started_at', now()->toDateTimeString());
            
            Log::info('Queue worker started from backend');
            
            return response()->json([
                'success' => true,
                'message' => 'Queue worker started successfully!',
                'status' => $this->getQueueWorkerStatus()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to start queue worker: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to start queue worker: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stop Queue Worker
     */
    public function stopQueueWorker()
    {
        try {
            // Kill all PHP artisan queue:work processes
            $command = 'taskkill /F /FI "WINDOWTITLE eq *queue:work*" 2>nul';
            exec($command);
            
            // Alternative: Kill by command line match
            exec('wmic process where "commandline like \'%queue:work%\'" call terminate 2>nul');
            
            // Update status
            SystemSetting::set('queue_worker_status', 'stopped');
            SystemSetting::set('queue_worker_stopped_at', now()->toDateTimeString());
            
            Log::info('Queue worker stopped from backend');
            
            return response()->json([
                'success' => true,
                'message' => 'Queue worker stopped successfully!'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to stop queue worker: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to stop queue worker: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start Realtime Data Pull (continuous loop)
     */
    public function startRealtimePull()
    {
        try {
            // Check if already running
            if ($this->isRealtimePullRunning()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Realtime data pull is already running!'
                ]);
            }

            $phpPath = 'C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe';
            $artisanPath = base_path('artisan');
            
            // Start continuous realtime pull (every 3 minutes, loop forever)
            $command = "start /B {$phpPath} {$artisanPath} pull:realtime-loop > nul 2>&1";
            
            pclose(popen($command, 'r'));
            
            // Wait a moment for process to start
            sleep(2);
            
            // Save status
            SystemSetting::set('realtime_pull_status', 'running');
            SystemSetting::set('realtime_pull_started_at', now()->toDateTimeString());
            
            Log::info('Realtime data pull started from backend');
            
            return response()->json([
                'success' => true,
                'message' => 'Realtime data pull started! Pulling data every 3 minutes...',
                'status' => $this->getRealtimePullStatus()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to start realtime pull: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to start realtime pull: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stop Realtime Data Pull
     */
    public function stopRealtimePull()
    {
        try {
            // Kill realtime pull processes
            exec('taskkill /F /FI "WINDOWTITLE eq *pull:realtime-loop*" 2>nul');
            exec('wmic process where "commandline like \'%pull:realtime-loop%\'" call terminate 2>nul');
            
            // Update status
            SystemSetting::set('realtime_pull_status', 'stopped');
            SystemSetting::set('realtime_pull_stopped_at', now()->toDateTimeString());
            
            Log::info('Realtime data pull stopped from backend');
            
            return response()->json([
                'success' => true,
                'message' => 'Realtime data pull stopped successfully!'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to stop realtime pull: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to stop realtime pull: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get status via AJAX
     */
    public function getStatus()
    {
        // Load all settings in one query
        $allSettings = SystemSetting::getMany([
            'queue_worker_status', 'queue_worker_started_at',
            'realtime_pull_status', 'realtime_pull_started_at',
            'cleanup_enabled', 'cleanup_last_run',
        ]);

        return response()->json([
            'queue'   => $this->buildQueueWorkerStatus($allSettings),
            'realtime' => $this->buildRealtimePullStatus($allSettings),
            'cleanup' => [
                'settings' => [
                    'cleanup_enabled'  => $allSettings['cleanup_enabled'] ?? true,
                    'cleanup_last_run' => $allSettings['cleanup_last_run'] ?? null,
                ],
                'stats' => $this->getCleanupStats(),
            ]
        ]);
    }

    /**
     * Update cleanup settings
     */
    public function updateCleanupSettings(Request $request)
    {
        $validated = $request->validate([
            'cleanup_enabled' => 'required|boolean',
            'cleanup_retention_days' => 'required|integer|min:7|max:365',
            'cleanup_schedule' => 'required|in:daily,weekly,monthly',
        ]);

        SystemSetting::set('cleanup_enabled', $validated['cleanup_enabled']);
        SystemSetting::set('cleanup_retention_days', $validated['cleanup_retention_days']);
        SystemSetting::set('cleanup_schedule', $validated['cleanup_schedule']);

        return response()->json([
            'success' => true,
            'message' => 'Cleanup settings updated successfully',
        ]);
    }

    /**
     * Run cleanup manually
     */
    public function runCleanupManually()
    {
        try {
            // Dispatch cleanup job
            CleanupOldRawDataJob::dispatch();

            return response()->json([
                'success' => true,
                'message' => 'Cleanup job dispatched. Check logs for progress.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to dispatch cleanup job: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get cleanup statistics (with caching for performance)
     */
    private function getCleanupStats(): array
    {
        // Cache stats for 5 minutes (300 seconds) since we have optimized index queries
        return cache()->remember('cleanup_stats', 300, function () {
            $retentionDays = SystemSetting::get('cleanup_retention_days', 30);
            $cutoffDate = now()->subDays($retentionDays);

            // ── 1. For alarm_raw ──────────────────────────────────────────
            $alarmRawTotal = 0;
            $alarmRawOld = 0;

            try {
                // Estimate total rows using information_schema (0ms)
                $result = DB::selectOne("
                    SELECT TABLE_ROWS as estimated_count 
                    FROM information_schema.TABLES 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'alarm_raw'
                ");
                $alarmRawTotal = $result ? (int)$result->estimated_count : 0;

                // Exact count of old records using the new created_at index (extremely fast)
                $alarmRawOld = DB::table('alarm_raw')
                    ->where('created_at', '<', $cutoffDate)
                    ->count();
            } catch (\Exception $e) {
                Log::warning('Failed to get alarm_raw stats: ' . $e->getMessage());
            }

            // ── 2. For gps_tracks_raw ──────────────────────────────────────
            $gpsRawTotal = 0;
            $gpsRawOld = 0;
            
            if (DB::getSchemaBuilder()->hasTable('gps_tracks_raw')) {
                try {
                    // Estimate total rows using information_schema (0ms)
                    $result = DB::selectOne("
                        SELECT TABLE_ROWS as estimated_count 
                        FROM information_schema.TABLES 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'gps_tracks_raw'
                    ");
                    $gpsRawTotal = $result ? (int)$result->estimated_count : 0;
                    
                    // Exact count of old records using the new created_at index (extremely fast)
                    $gpsRawOld = DB::table('gps_tracks_raw')
                        ->where('created_at', '<', $cutoffDate)
                        ->count();
                } catch (\Exception $e) {
                    Log::warning('Failed to get gps_tracks_raw stats: ' . $e->getMessage());
                }
            }

            return [
                'alarm_raw' => [
                    'total' => $alarmRawTotal,
                    'old' => $alarmRawOld,
                    'will_delete' => $alarmRawOld,
                ],
                'gps_raw' => [
                    'total' => $gpsRawTotal,
                    'old' => $gpsRawOld,
                    'will_delete' => $gpsRawOld,
                ],
                'cutoff_date' => $cutoffDate->toDateTimeString(),
            ];
        });
    }

    /**
     * Get available months for manual cleanup
     * Returns months that are completed + 2 days buffer
     */
    public function getAvailableMonths()
    {
        try {
            $data = cache()->remember('available_months_for_cleanup', 300, function () {
                $today = now();
                $availableMonths = [];
                
                // Get earliest data date from database
                $earliestAlarm = DB::table('alarm_raw')->min('created_at');
                $earliestGps = DB::table('gps_tracks_raw')->min('created_at');
                
                $earliestDate = $earliestAlarm;
                if ($earliestGps && (!$earliestDate || $earliestGps < $earliestDate)) {
                    $earliestDate = $earliestGps;
                }
                
                if (!$earliestDate) {
                    return ['months' => []];
                }
                
                $startDate = \Carbon\Carbon::parse($earliestDate)->startOfMonth();
                $currentMonth = $today->copy()->startOfMonth();
                
                // Loop through months from earliest to current
                while ($startDate->lte($currentMonth)) {
                    $monthEnd = $startDate->copy()->endOfMonth();
                    $bufferDate = $monthEnd->copy()->addDays(2); // +2 days buffer
                    
                    // Only show months that have passed + 2 days buffer
                    if ($today->gte($bufferDate)) {
                        // Get count for this month using whereBetween for index optimization
                        $alarmCount = DB::table('alarm_raw')
                            ->whereBetween('created_at', [$startDate, $monthEnd])
                            ->count();
                        
                        $gpsCount = 0;
                        if (DB::getSchemaBuilder()->hasTable('gps_tracks_raw')) {
                            // Use fast subquery to check up to 10000 records
                            $sampleCount = DB::selectOne("
                                SELECT COUNT(*) as cnt 
                                FROM (
                                    SELECT 1 
                                    FROM gps_tracks_raw 
                                    WHERE created_at BETWEEN ? AND ? 
                                    LIMIT 10000
                                ) as sub
                            ", [$startDate->toDateTimeString(), $monthEnd->toDateTimeString()])->cnt;
                            
                            $gpsCount = $sampleCount >= 10000 ? '10000+' : $sampleCount;
                        }
                        
                        $availableMonths[] = [
                            'year' => $startDate->year,
                            'month' => $startDate->month,
                            'display' => $startDate->format('F Y'),
                            'alarm_count' => $alarmCount,
                            'gps_count' => $gpsCount,
                            'total_estimate' => is_numeric($gpsCount) ? $alarmCount + $gpsCount : $alarmCount,
                        ];
                    }
                    
                    $startDate->addMonth();
                }
                
                return ['months' => array_reverse($availableMonths)];
            });
            
            return response()->json($data);
            
        } catch (\Exception $e) {
            Log::error('Failed to get available months: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Preview cleanup for specific month
     */
    public function previewMonthCleanup(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);
        
        try {
            $year = $validated['year'];
            $month = $validated['month'];
            
            $monthStart = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $monthEnd = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth();
            
            // Count data in this month
            $alarmCount = DB::table('alarm_raw')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();
            
            $gpsCount = 0;
            if (DB::getSchemaBuilder()->hasTable('gps_tracks_raw')) {
                $gpsCount = DB::table('gps_tracks_raw')
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->count();
            }
            
            return response()->json([
                'success' => true,
                'month_display' => $monthStart->format('F Y'),
                'date_range' => $monthStart->format('Y-m-d') . ' to ' . $monthEnd->format('Y-m-d'),
                'alarm_raw_count' => $alarmCount,
                'gps_raw_count' => $gpsCount,
                'total_count' => $alarmCount + $gpsCount,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Execute cleanup for specific month
     */
    public function cleanupByMonth(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);
        
        try {
            $year = $validated['year'];
            $month = $validated['month'];
            
            // Validate: month must be completed + 2 days buffer
            $monthEnd = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth();
            $bufferDate = $monthEnd->copy()->addDays(2);
            
            if (now()->lt($bufferDate)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This month cannot be cleaned yet. Must wait until ' . $bufferDate->format('Y-m-d') . ' (2 days after month end)',
                ], 422);
            }
            
            // Dispatch job
            \App\Jobs\CleanupByMonthJob::dispatch($year, $month);
            
            return response()->json([
                'success' => true,
                'message' => 'Cleanup job for ' . $monthEnd->format('F Y') . ' has been dispatched to queue.',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to dispatch month cleanup: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to dispatch cleanup job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get cleanup progress
     */
    public function getCleanupProgress(Request $request)
    {
        $year = $request->query('year');
        $month = $request->query('month');
        
        if (!$year || !$month) {
            return response()->json(['error' => 'Missing year or month'], 400);
        }
        
        $cacheKey = "cleanup_progress_{$year}_{$month}";
        $progress = \Illuminate\Support\Facades\Cache::get($cacheKey);
        
        return response()->json([
            'progress' => $progress // returns null if job not started, or array with stats
        ]);
    }

    /**
     * Check if Queue Worker is running
     */
    private function isQueueWorkerRunning()
    {
        // OS process checks (wmic, tasklist) are extremely slow on this Windows environment
        // and cause the PHP single-threaded development server to hang indefinitely.
        // We will rely on the database flag instead.
        return \App\Models\SystemSetting::get('queue_worker_status') === 'running';
    }

    /**
     * Check if Realtime Pull is running
     */
    private function isRealtimePullRunning()
    {
        // OS process checks are extremely slow, rely on database flag
        return \App\Models\SystemSetting::get('realtime_pull_status') === 'running';
    }

    /**
     * Build Queue Worker status dari settings yang sudah di-load
     */
    private function buildQueueWorkerStatus(array $settings)
    {
        $savedStatus = $settings['queue_worker_status'] ?? 'stopped';
        $startedAt   = $settings['queue_worker_started_at'] ?? null;
        $isRunning   = ($savedStatus === 'running');

        return [
            'running'     => $isRunning,
            'status'      => $isRunning ? 'running' : 'stopped',
            'started_at'  => $startedAt,
            'badge_class' => $isRunning ? 'bg-success' : 'bg-secondary',
            'badge_text'  => $isRunning ? 'Running' : 'Stopped',
        ];
    }

    /**
     * Get Queue Worker status details
     */
    private function getQueueWorkerStatus()
    {
        // Check actual process status first
        $isRunning = $this->isQueueWorkerRunning();
        
        // Get saved status from database
        $savedStatus = SystemSetting::get('queue_worker_status', 'stopped');
        $startedAt = SystemSetting::get('queue_worker_started_at');
        
        // If database says running but process is not found, update to stopped
        if ($savedStatus === 'running' && !$isRunning) {
            SystemSetting::set('queue_worker_status', 'stopped');
            $savedStatus = 'stopped';
        }
        
        // If process is running but database says stopped, update to running
        if ($isRunning && $savedStatus === 'stopped') {
            SystemSetting::set('queue_worker_status', 'running');
            $savedStatus = 'running';
        }
        
        return [
            'running' => $isRunning,
            'status' => $isRunning ? 'running' : 'stopped',
            'started_at' => $startedAt,
            'badge_class' => $isRunning ? 'bg-success' : 'bg-secondary',
            'badge_text' => $isRunning ? 'Running' : 'Stopped'
        ];
    }

    /**
     * Build Realtime Pull status dari settings yang sudah di-load
     */
    private function buildRealtimePullStatus(array $settings)
    {
        $savedStatus = $settings['realtime_pull_status'] ?? 'stopped';
        $startedAt   = $settings['realtime_pull_started_at'] ?? null;
        $isRunning   = ($savedStatus === 'running');

        return [
            'running'     => $isRunning,
            'status'      => $isRunning ? 'running' : 'stopped',
            'started_at'  => $startedAt,
            'badge_class' => $isRunning ? 'bg-success' : 'bg-secondary',
            'badge_text'  => $isRunning ? 'Running' : 'Stopped',
        ];
    }

    /**
     * Get Realtime Pull status details
     */
    private function getRealtimePullStatus()
    {
        // Check actual process status first
        $isRunning = $this->isRealtimePullRunning();
        
        // Get saved status from database
        $savedStatus = SystemSetting::get('realtime_pull_status', 'stopped');
        $startedAt = SystemSetting::get('realtime_pull_started_at');
        
        // If database says running but process is not found, update to stopped
        if ($savedStatus === 'running' && !$isRunning) {
            SystemSetting::set('realtime_pull_status', 'stopped');
            $savedStatus = 'stopped';
        }
        
        // If process is running but database says stopped, update to running
        if ($isRunning && $savedStatus === 'stopped') {
            SystemSetting::set('realtime_pull_status', 'running');
            $savedStatus = 'running';
        }
        
        return [
            'running' => $isRunning,
            'status' => $isRunning ? 'running' : 'stopped',
            'started_at' => $startedAt,
            'badge_class' => $isRunning ? 'bg-success' : 'bg-secondary',
            'badge_text' => $isRunning ? 'Running' : 'Stopped'
        ];
    }
}
