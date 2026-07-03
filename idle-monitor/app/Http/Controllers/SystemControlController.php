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
        $queueStatus = $this->getQueueWorkerStatus();
        $realtimeStatus = $this->getRealtimePullStatus();
        
        // Get cleanup settings
        $cleanupSettings = [
            'cleanup_enabled' => SystemSetting::get('cleanup_enabled', true),
            'cleanup_retention_days' => SystemSetting::get('cleanup_retention_days', 30),
            'cleanup_last_run' => SystemSetting::get('cleanup_last_run'),
            'cleanup_schedule' => SystemSetting::get('cleanup_schedule', 'monthly'),
        ];

        // Get cleanup statistics
        $cleanupStats = $this->getCleanupStats();
        
        return view('admin.system-control.index', compact('queueStatus', 'realtimeStatus', 'cleanupSettings', 'cleanupStats'));
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
        return response()->json([
            'queue' => $this->getQueueWorkerStatus(),
            'realtime' => $this->getRealtimePullStatus(),
            'cleanup' => [
                'settings' => [
                    'cleanup_enabled' => SystemSetting::get('cleanup_enabled', true),
                    'cleanup_last_run' => SystemSetting::get('cleanup_last_run'),
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
     * Get cleanup statistics
     */
    private function getCleanupStats(): array
    {
        $retentionDays = SystemSetting::get('cleanup_retention_days', 30);
        $cutoffDate = now()->subDays($retentionDays);

        // Count old records
        $alarmRawOld = DB::table('alarm_raw')
            ->where('created_at', '<', $cutoffDate)
            ->count();

        $gpsRawOld = 0;
        if (DB::getSchemaBuilder()->hasTable('gps_tracks_raw')) {
            $gpsRawOld = DB::table('gps_tracks_raw')
                ->where('created_at', '<', $cutoffDate)
                ->count();
        }

        // Count total records
        $alarmRawTotal = DB::table('alarm_raw')->count();
        $gpsRawTotal = 0;
        if (DB::getSchemaBuilder()->hasTable('gps_tracks_raw')) {
            $gpsRawTotal = DB::table('gps_tracks_raw')->count();
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
    }

    /**
     * Check if Queue Worker is running
     */
    private function isQueueWorkerRunning()
    {
        // Method 1: Check via WMIC (most reliable on Windows)
        exec('wmic process where "commandline like \'%queue:work%\' and name=\'php.exe\'" get processid 2>nul', $output);
        
        // Filter out empty lines and header
        $output = array_filter($output, function($line) {
            return trim($line) !== '' && !stripos($line, 'ProcessId');
        });
        
        if (count($output) > 0) {
            return true;
        }
        
        // Method 2: Check via tasklist
        exec('tasklist /FI "IMAGENAME eq php.exe" /FO CSV 2>nul | findstr /i "queue:work"', $output2);
        if (!empty($output2)) {
            return true;
        }
        
        return false;
    }

    /**
     * Check if Realtime Pull is running
     */
    private function isRealtimePullRunning()
    {
        // Method 1: Check via WMIC (most reliable on Windows)
        exec('wmic process where "commandline like \'%pull:realtime-loop%\' and name=\'php.exe\'" get processid 2>nul', $output);
        
        // Filter out empty lines and header
        $output = array_filter($output, function($line) {
            return trim($line) !== '' && !stripos($line, 'ProcessId');
        });
        
        if (count($output) > 0) {
            return true;
        }
        
        // Method 2: Check via tasklist
        exec('tasklist /FI "IMAGENAME eq php.exe" /FO CSV 2>nul | findstr /i "pull:realtime-loop"', $output2);
        if (!empty($output2)) {
            return true;
        }
        
        return false;
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
