<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class SystemControlController extends Controller
{
    /**
     * Show system control dashboard
     */
    public function index()
    {
        $queueStatus = $this->getQueueWorkerStatus();
        $realtimeStatus = $this->getRealtimePullStatus();
        
        return view('admin.system-control.index', compact('queueStatus', 'realtimeStatus'));
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
            'realtime' => $this->getRealtimePullStatus()
        ]);
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
