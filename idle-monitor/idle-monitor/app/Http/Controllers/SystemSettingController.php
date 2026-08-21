<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\ImportLog;
use Illuminate\Support\Facades\DB;

class SystemSettingController extends Controller
{
    /**
     * Show system settings page
     */
    public function index()
    {
        // Get all system settings
        $settings = [
            'last_alarm_sync' => SystemSetting::get('last_alarm_sync'),
            'last_device_sync' => SystemSetting::get('last_device_sync'),
            'last_token_refresh' => SystemSetting::get('last_token_refresh'),
        ];

        // Get latest import logs for status
        $latestImports = ImportLog::orderBy('finished_at', 'desc')
            ->limit(5)
            ->get();

        // Calculate API status
        $apiStatus = $this->getApiStatus();

        return view('admin.system-setting.index', compact('settings', 'latestImports', 'apiStatus'));
    }

    /**
     * Get API status
     */
    private function getApiStatus()
    {
        $lastSync = SystemSetting::get('last_alarm_sync');

        if (!$lastSync) {
            return [
                'status' => 'unknown',
                'message' => 'No sync data available',
                'color' => 'secondary',
            ];
        }

        $lastSyncTime = strtotime($lastSync);
        $currentTime = time();
        $diffMinutes = ($currentTime - $lastSyncTime) / 60;

        if ($diffMinutes < 5) {
            return [
                'status' => 'connected',
                'message' => 'API connected (last check: ' . round($diffMinutes) . ' min ago)',
                'color' => 'success',
            ];
        } elseif ($diffMinutes < 30) {
            return [
                'status' => 'warning',
                'message' => 'API may be slow (last check: ' . round($diffMinutes) . ' min ago)',
                'color' => 'warning',
            ];
        } else {
            return [
                'status' => 'disconnected',
                'message' => 'API may be offline (last check: ' . round($diffMinutes) . ' min ago)',
                'color' => 'danger',
            ];
        }
    }
}
