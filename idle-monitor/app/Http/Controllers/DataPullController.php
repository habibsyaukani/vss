<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DataPullController extends Controller
{
    /**
     * Show data pull page
     */
    public function index()
    {
        // Cache stats 5 menit - count() pada idle_alarms bisa lambat
        $stats = Cache::remember('datapull_stats', 300, function () {
            return [
                'total_mei' => DB::table('idle_alarms')
                    ->whereBetween('starting_time', ['2026-05-01 00:00:00', '2026-05-31 23:59:59'])
                    ->count(),
                'total_juni' => DB::table('idle_alarms')
                    ->whereBetween('starting_time', ['2026-06-01 00:00:00', '2026-06-30 23:59:59'])
                    ->count(),
                'total_all' => DB::table('idle_alarms')->count(),
            ];
        });

        $lastPull = DB::table('system_settings')->where('key', 'last_realtime_pull')->value('value');
        $stats['last_pull'] = $lastPull ? Carbon::parse($lastPull)->format('Y-m-d H:i:s') : 'Never';

        return view('admin.data-pull', compact('stats'));
    }

    /**
     * Execute data pull - NEW VERSION with auto-batch splitting
     */
    public function execute(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'batch_interval' => 'nullable|integer|min:1|max:6',
        ]);

        $date = $request->input('date');
        $batchInterval = $request->input('batch_interval', 3); // Default: 3 hours per batch

        try {
            // Generate unique session ID
            $sessionId = 'pull_' . uniqid() . '_' . time();

            Log::info("New data pull session initiated", [
                'session_id' => $sessionId,
                'date' => $date,
                'batch_interval' => $batchInterval,
            ]);

            // Dispatch orchestrator job to split & execute batches
            \App\Jobs\DataPullOrchestratorJob::dispatch($sessionId, $date, $batchInterval);

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'message' => 'Proses penarikan data dimulai di background. Data akan ditarik dalam beberapa batch.',
                'date' => $date,
                'estimated_batches' => ceil(24 / $batchInterval),
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to initiate data pull", [
                'error' => $e->getMessage(),
                'date' => $date,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get progress for a data pull session
     */
    public function progress(Request $request, string $sessionId)
    {
        try {
            $progress = \App\Models\DataPullBatch::getSessionProgress($sessionId);

            // Add completion status
            $progress['is_completed'] = \App\Models\DataPullBatch::isSessionCompleted($sessionId);

            // Calculate overall progress percentage
            $progress['progress_percentage'] = $progress['total_batches'] > 0
                ? round(($progress['completed'] / $progress['total_batches']) * 100, 1)
                : 0;

            // Calculate ETA (estimated time remaining)
            $completedBatches = \App\Models\DataPullBatch::where('session_id', $sessionId)
                ->where('status', 'completed')
                ->get();

            if ($completedBatches->count() > 0) {
                $avgDuration = $completedBatches->avg(function ($batch) {
                    return $batch->started_at && $batch->completed_at
                        ? $batch->started_at->diffInSeconds($batch->completed_at)
                        : 0;
                });

                $remainingBatches = $progress['pending'] + $progress['processing'];
                $etaSeconds = $avgDuration * $remainingBatches;

                $progress['eta_seconds'] = round($etaSeconds);
                $progress['eta_formatted'] = $this->formatDuration($etaSeconds);
            } else {
                $progress['eta_seconds'] = null;
                $progress['eta_formatted'] = 'Calculating...';
            }

            return response()->json($progress);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format duration in human readable format
     */
    private function formatDuration(float $seconds): string
    {
        if ($seconds < 60) {
            return round($seconds) . 's';
        } elseif ($seconds < 3600) {
            return floor($seconds / 60) . 'm ' . round($seconds % 60) . 's';
        } else {
            return floor($seconds / 3600) . 'h ' . floor(($seconds % 3600) / 60) . 'm';
        }
    }

    /**
     * Get current statistics (AJAX)
     */
    public function statistics()
    {
        $stats = Cache::remember('datapull_stats', 300, function () {
            return [
                'total_mei' => DB::table('idle_alarms')
                    ->whereBetween('starting_time', ['2026-05-01 00:00:00', '2026-05-31 23:59:59'])
                    ->count(),
                'total_juni' => DB::table('idle_alarms')
                    ->whereBetween('starting_time', ['2026-06-01 00:00:00', '2026-06-30 23:59:59'])
                    ->count(),
                'total_all' => $this->getApproximateCount('idle_alarms'),
            ];
        });
        
        $stats['last_pull'] = DB::table('system_settings')
                ->where('key', 'last_realtime_pull')
                ->value('value');

        return response()->json($stats);
    }

    // ================================================================
    // GPS TRACK PULL METHODS
    // ================================================================

    /**
     * Show GPS track pull page
     */
    public function gpsTrackIndex()
    {
        // Use cache for statistics (refresh every 30 seconds)
        $stats = cache()->remember('gps_track_stats', 30, function () {
            return [
                'total_juni' => $this->getApproximateCount('gps_tracks_raw', 'gps_time', '2026-06-01 00:00:00', '2026-06-30 23:59:59'),
                'total_devices' => DB::table('devices')
                    ->where('status', 'active')
                    ->whereNotNull('device_id')
                    ->count(), // This is fast, devices table is small
                'total_all' => $this->getApproximateCount('gps_tracks_raw'),
            ];
        });

        $lastPull = cache()->remember('last_gps_pull', 30, function () {
            return DB::table('system_settings')->where('key', 'last_gps_pull')->value('value');
        });
        
        $stats['last_pull'] = $lastPull ? Carbon::parse($lastPull)->format('Y-m-d H:i:s') : 'Never';

        return view('admin.gps-track-pull', compact('stats'));
    }

    /**
     * Execute GPS track pull
     */
    public function gpsTrackExecute(Request $request)
    {
        set_time_limit(600);
        ini_set('max_execution_time', 600);

        $request->validate([
            'date' => 'required|date',
            'device_filter' => 'nullable|string',
            'limit' => 'nullable|integer|min:0',
        ]);

        $date = $request->input('date');
        $deviceFilter = $request->input('device_filter', 'all');
        $limit = $request->input('limit', 0);

        try {
            $recordsSaved = 0;
            $output = '';

            // Jika dipanggil untuk 1 device saja (dari frontend loop), bypass Artisan untuk menghemat overhead booting
            if ($deviceFilter !== 'all' && strpos($deviceFilter, ',') === false) {
                $beginTime = Carbon::parse("{$date} 00:00:00");
                $endTime = Carbon::parse("{$date} 23:59:59");
                
                $authService = app(\App\Services\VssAuthService::class);
                $token = $authService->getToken();
                
                $syncService = app(\App\Services\GpsTrackSyncService::class);
                $result = $syncService->syncDevice(
                    $token,
                    $deviceFilter,
                    $beginTime->format('Y-m-d H:i:s'),
                    $endTime->format('Y-m-d H:i:s')
                );
                
                $recordsSaved = $result['total_saved'];
                $output = "Direct fetch completed. Total records saved: {$recordsSaved}\n";
            } else {
                // Eksekusi via command jika bulk/all
                $command = sprintf(
                    'vss:pull-gps-tracks --date=%s --devices=%s --limit=%d',
                    $date,
                    $deviceFilter ?: 'all',
                    $limit
                );

                Artisan::call($command);
                $output = Artisan::output();

                if (preg_match('/Total records saved: (\d+)/', $output, $matches)) {
                    $recordsSaved = (int)$matches[1];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'GPS Track pull completed successfully!',
                'output' => $output,
                'records_saved' => $recordsSaved,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get GPS track statistics (AJAX)
     */
    public function gpsTrackStatistics()
    {
        // Use cache for AJAX stats refresh (15 seconds cache)
        $stats = cache()->remember('gps_track_stats_ajax', 15, function () {
            return [
                'total_juni' => $this->getApproximateCount('gps_tracks_raw', 'gps_time', '2026-06-01 00:00:00', '2026-06-30 23:59:59'),
                'total_devices' => DB::table('devices')
                    ->where('status', 'active')
                    ->whereNotNull('device_id')
                    ->count(),
                'total_all' => $this->getApproximateCount('gps_tracks_raw'),
                'last_pull' => DB::table('system_settings')
                    ->where('key', 'last_gps_pull')
                    ->value('value'),
            ];
        });

        return response()->json($stats);
    }

    /**
     * Get Active Devices
     */
    public function getActiveDevices()
    {
        $devices = DB::table('devices')
            ->where('status', 'active')
            ->whereNotNull('device_id')
            ->select('id', 'device_name', 'device_id')
            ->get();
            
        return response()->json([
            'success' => true,
            'devices' => $devices
        ]);
    }

    /**
     * Get approximate count for large tables using EXPLAIN
     * Falls back to exact count if estimate not available
     */
    private function getApproximateCount(string $table, ?string $dateColumn = null, ?string $startDate = null, ?string $endDate = null): int
    {
        try {
            if ($dateColumn && $startDate && $endDate) {
                // For date range queries, use exact count with limit
                // But add index hint to make it faster
                return DB::table($table)
                    ->whereBetween($dateColumn, [$startDate, $endDate])
                    ->count();
            }

            // For total count, use MySQL table stats (very fast!)
            $result = DB::selectOne("
                SELECT TABLE_ROWS as approximate_count
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
            ", [$table]);

            return $result ? (int)$result->approximate_count : 0;

        } catch (\Exception $e) {
            // Fallback to regular count if error
            \Log::warning("Approximate count failed for {$table}: " . $e->getMessage());
            
            if ($dateColumn && $startDate && $endDate) {
                return DB::table($table)->whereBetween($dateColumn, [$startDate, $endDate])->count();
            }
            
            return DB::table($table)->count();
        }
    }
}
