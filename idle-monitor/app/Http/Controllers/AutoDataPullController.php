<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class AutoDataPullController extends Controller
{
    public function index()
    {
        $settings = [
            'enabled' => SystemSetting::get('auto_pull_enabled', false),
            'interval' => SystemSetting::get('auto_pull_interval', 30),
            'next_type' => SystemSetting::get('auto_pull_next_type', 'idle'),
            'last_run' => SystemSetting::get('auto_pull_last_run'),
            'last_success' => SystemSetting::get('auto_pull_last_success'),
            'last_error' => SystemSetting::get('auto_pull_last_error'),
            'last_error_at' => SystemSetting::get('auto_pull_last_error_at'),
            'idle_last_count' => SystemSetting::get('auto_pull_idle_last_count', 0),
            'gps_last_count' => SystemSetting::get('auto_pull_gps_last_count', 0),
        ];

        return view('admin.auto-data-pull.index', compact('settings'));
    }

    public function toggle(Request $request)
    {
        $enabled = $request->input('enabled', false);
        
        SystemSetting::set('auto_pull_enabled', $enabled);
        
        if ($enabled) {
            // Reset next type to idle when enabling
            SystemSetting::set('auto_pull_next_type', 'idle');
            
            $message = 'Auto Data Pull has been ENABLED. System will pull data alternately every 30 minutes.';
        } else {
            $message = 'Auto Data Pull has been DISABLED.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'enabled' => $enabled
        ]);
    }

    public function updateInterval(Request $request)
    {
        $request->validate([
            'interval' => 'required|integer|min:5|max:1440'
        ]);

        $interval = $request->input('interval');
        SystemSetting::set('auto_pull_interval', $interval);

        return response()->json([
            'success' => true,
            'message' => "Interval updated to {$interval} minutes"
        ]);
    }

    public function runNow(Request $request)
    {
        try {
            // Run the command in background queue (non-blocking)
            Artisan::queue('auto-pull:run');
            
            return response()->json([
                'success' => true,
                'message' => 'Auto pull started in background. Check status below for progress.',
                'output' => 'Manual auto-pull process queued successfully. Refresh page to see progress.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to queue auto pull: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getStatus(Request $request)
    {
        $settings = [
            'enabled' => SystemSetting::get('auto_pull_enabled', false),
            'interval' => SystemSetting::get('auto_pull_interval', 30),
            'next_type' => SystemSetting::get('auto_pull_next_type', 'idle'),
            'last_run' => SystemSetting::get('auto_pull_last_run'),
            'last_success' => SystemSetting::get('auto_pull_last_success'),
            'last_error' => SystemSetting::get('auto_pull_last_error'),
            'last_error_at' => SystemSetting::get('auto_pull_last_error_at'),
            'idle_last_count' => SystemSetting::get('auto_pull_idle_last_count', 0),
            'gps_last_count' => SystemSetting::get('auto_pull_gps_last_count', 0),
            
            // Progress tracking
            'status' => SystemSetting::get('auto_pull_status', 'waiting'), // waiting/running/completed/error
            'current_type' => SystemSetting::get('auto_pull_current_type', null), // idle/gps
            'progress_percent' => SystemSetting::get('auto_pull_progress_percent', 0),
            'progress_message' => SystemSetting::get('auto_pull_progress_message', ''),
            'current_records' => SystemSetting::get('auto_pull_current_records', 0),
            'started_at' => SystemSetting::get('auto_pull_started_at'),
            'next_in_minutes' => SystemSetting::get('auto_pull_next_in_minutes', 0),
        ];

        // Calculate next run time
        if ($settings['last_run']) {
            $lastRunTime = Carbon::parse($settings['last_run']);
            $nextRunTime = $lastRunTime->addMinutes($settings['interval']);
            $settings['next_run'] = $nextRunTime->toDateTimeString();
            $settings['next_run_human'] = $nextRunTime->diffForHumans();
            $minutesUntil = now()->diffInMinutes($nextRunTime, false);
            $settings['minutes_until_next'] = max(0, $minutesUntil);
            
            // Format countdown
            if ($minutesUntil > 0) {
                $hours = floor($minutesUntil / 60);
                $mins = $minutesUntil % 60;
                $settings['countdown'] = $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";
            } else {
                $settings['countdown'] = 'Ready';
            }
        } else {
            $settings['next_run'] = null;
            $settings['next_run_human'] = 'Not scheduled yet';
            $settings['minutes_until_next'] = 0;
            $settings['countdown'] = 'Not started';
        }

        // Format timestamps for display
        if ($settings['last_run']) {
            $settings['last_run_human'] = Carbon::parse($settings['last_run'])->diffForHumans();
        }
        if ($settings['last_success']) {
            $settings['last_success_human'] = Carbon::parse($settings['last_success'])->diffForHumans();
        }
        if ($settings['last_error_at']) {
            $settings['last_error_at_human'] = Carbon::parse($settings['last_error_at'])->diffForHumans();
        }
        if ($settings['started_at']) {
            $settings['started_at_human'] = Carbon::parse($settings['started_at'])->diffForHumans();
            $settings['duration'] = Carbon::parse($settings['started_at'])->diffInSeconds(now()) . 's';
        }

        return response()->json($settings);
    }
}
