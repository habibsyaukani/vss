<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DataPullController extends Controller
{
    /**
     * Show data pull page
     */
    public function index()
    {
        $stats = [
            'total_mei' => DB::table('idle_alarms')
                ->whereBetween('starting_time', ['2026-05-01 00:00:00', '2026-05-31 23:59:59'])
                ->count(),
            'total_juni' => DB::table('idle_alarms')
                ->whereBetween('starting_time', ['2026-06-01 00:00:00', '2026-06-30 23:59:59'])
                ->count(),
            'total_all' => DB::table('idle_alarms')->count(),
        ];

        $lastPull = DB::table('system_settings')->where('key', 'last_realtime_pull')->value('value');
        $stats['last_pull'] = $lastPull ? Carbon::parse($lastPull)->format('Y-m-d H:i:s') : 'Never';

        return view('admin.data-pull', compact('stats'));
    }

    /**
     * Execute data pull
     */
    public function execute(Request $request)
    {
        set_time_limit(600); // 10 minutes
        ini_set('max_execution_time', 600);
        
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'pages' => 'nullable|integer|min:1|max:200',
            'concurrency' => 'nullable|integer|min:1|max:10',
        ]);

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $pages = $request->input('pages', 100);
        $concurrency = $request->input('concurrency', 5);

        try {
            if ($concurrency > 1) {
                // Parallel mode (synchronous fetch)
                $command = sprintf(
                    'howen:pull-alarms-date-range --from=%s --to=%s --pages=%d --parallel --concurrency=%d --wait',
                    $fromDate,
                    $toDate,
                    $pages,
                    $concurrency
                );
            } else {
                // Sequential mode (synchronous with wait to avoid queue issues)
                $command = sprintf(
                    'howen:pull-alarms-date-range --from=%s --to=%s --pages=%d --wait',
                    $fromDate,
                    $toDate,
                    $pages
                );
            }

            Artisan::call($command);
            $output = Artisan::output();

            // Jangan panggil process-idle-alarms di sini karena raw data belum selesai ditarik (masih di antrean)
            // ProcessIdleAlarmJob sudah dipanggil otomatis di dalam ImportAlarmPageJob setelah selesai narik per halaman.
            $processOutput = "Proses idle alarm akan berjalan otomatis di background setelah setiap halaman selesai ditarik.";

            $stats = [
                'total_mei' => DB::table('idle_alarms')
                    ->whereBetween('starting_time', ['2026-05-01 00:00:00', '2026-05-31 23:59:59'])
                    ->count(),
                'total_juni' => DB::table('idle_alarms')
                    ->whereBetween('starting_time', ['2026-06-01 00:00:00', '2026-06-30 23:59:59'])
                    ->count(),
                'total_all' => DB::table('idle_alarms')->count(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Penarikan data berhasil dimasukkan ke antrean! Data akan ditarik secara perlahan di latar belakang untuk menghindari blokir. Silakan pantau perubahan data (refresh) dalam 5-15 menit ke depan.',
                'output' => $output,
                'process_output' => $processOutput,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current statistics (AJAX)
     */
    public function statistics()
    {
        $stats = [
            'total_mei' => DB::table('idle_alarms')
                ->whereBetween('starting_time', ['2026-05-01 00:00:00', '2026-05-31 23:59:59'])
                ->count(),
            'total_juni' => DB::table('idle_alarms')
                ->whereBetween('starting_time', ['2026-06-01 00:00:00', '2026-06-30 23:59:59'])
                ->count(),
            'total_all' => DB::table('idle_alarms')->count(),
            'last_pull' => DB::table('system_settings')
                ->where('key', 'last_realtime_pull')
                ->value('value'),
        ];

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
