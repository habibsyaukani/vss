<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Jobs\CleanupOldRawDataJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class SystemControlController extends Controller
{
    /**
     * Show system control center
     */
    public function index()
    {
        $settings = [
            'cleanup_enabled' => SystemSetting::get('cleanup_enabled', true),
            'cleanup_retention_days' => SystemSetting::get('cleanup_retention_days', 30),
            'cleanup_last_run' => SystemSetting::get('cleanup_last_run'),
            'cleanup_schedule' => SystemSetting::get('cleanup_schedule', 'monthly'),
        ];

        // Get statistics
        $stats = $this->getCleanupStats();

        return view('admin.system-control', compact('settings', 'stats'));
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
        $retentionDays = SystemSetting::getCleanupRetentionDays();
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
     * Get status for AJAX refresh
     */
    public function getStatus()
    {
        $settings = [
            'cleanup_enabled' => SystemSetting::get('cleanup_enabled', true),
            'cleanup_last_run' => SystemSetting::get('cleanup_last_run'),
        ];

        $stats = $this->getCleanupStats();

        return response()->json([
            'settings' => $settings,
            'stats' => $stats,
        ]);
    }
}
