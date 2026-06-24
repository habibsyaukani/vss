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
                ->whereRaw('MONTH(starting_time) = 5 AND YEAR(starting_time) = 2026')
                ->count(),
            'total_juni' => DB::table('idle_alarms')
                ->whereRaw('MONTH(starting_time) = 6 AND YEAR(starting_time) = 2026')
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
            // FORCE ALWAYS SEQUENTIAL & ASYNC (Abaikan input dari frontend untuk sementara demi keamanan)
            // Tanpa --wait agar ditaruh di antrean (Queue) sehingga tidak terjadi Error 600 seconds timeout
            $command = sprintf(
                'howen:pull-alarms-date-range --from=%s --to=%s --pages=%d',
                $fromDate,
                $toDate,
                $pages
            );

            Artisan::call($command);
            $output = Artisan::output();

            // Jangan panggil process-idle-alarms di sini karena raw data belum selesai ditarik (masih di antrean)
            // ProcessIdleAlarmJob sudah dipanggil otomatis di dalam ImportAlarmPageJob setelah selesai narik per halaman.
            $processOutput = "Proses idle alarm akan berjalan otomatis di background setelah setiap halaman selesai ditarik.";

            $stats = [
                'total_mei' => DB::table('idle_alarms')
                    ->whereRaw('MONTH(starting_time) = 5 AND YEAR(starting_time) = 2026')
                    ->count(),
                'total_juni' => DB::table('idle_alarms')
                    ->whereRaw('MONTH(starting_time) = 6 AND YEAR(starting_time) = 2026')
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
                ->whereRaw('MONTH(starting_time) = 5 AND YEAR(starting_time) = 2026')
                ->count(),
            'total_juni' => DB::table('idle_alarms')
                ->whereRaw('MONTH(starting_time) = 6 AND YEAR(starting_time) = 2026')
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
        // Get data statistics
        $stats = [
            'total_juni' => DB::table('gps_tracks_raw')
                ->whereRaw('MONTH(gps_time) = 6 AND YEAR(gps_time) = 2026')
                ->count(),
            'total_devices' => DB::table('devices')
                ->where('status', 'active')
                ->whereNotNull('device_id')
                ->count(),
            'total_all' => DB::table('gps_tracks_raw')->count(),
        ];

        $lastPull = DB::table('system_settings')->where('key', 'last_gps_pull')->value('value');
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
            // Dispatched asynchronously in real world, but for now we call it directly or run via artisan
            // Since this takes a long time, we should dispatch a job or run artisan command in background
            $command = sprintf(
                'vss:pull-gps-tracks --date=%s --devices=%s --limit=%d',
                $date,
                $deviceFilter ?: 'all',
                $limit
            );

            // Execute the artisan command
            Artisan::call($command);
            $output = Artisan::output();

            $recordsSaved = 0;
            if (preg_match('/Total records saved: (\d+)/', $output, $matches)) {
                $recordsSaved = (int)$matches[1];
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
        $stats = [
            'total_juni' => DB::table('gps_tracks_raw')
                ->whereRaw('MONTH(gps_time) = 6 AND YEAR(gps_time) = 2026')
                ->count(),
            'total_devices' => DB::table('devices')
                ->where('status', 'active')
                ->whereNotNull('device_id')
                ->count(),
            'total_all' => DB::table('gps_tracks_raw')->count(),
            'last_pull' => DB::table('system_settings')
                ->where('key', 'last_gps_pull')
                ->value('value'),
        ];

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
}
