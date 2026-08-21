<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\IdleAlarm;
use App\Models\ImportLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    /**
     * Show admin dashboard
     */
    public function index()
    {
        $todayStart = Carbon::today()->startOfDay();
        $todayEnd   = Carbon::today()->endOfDay();

        // Cache stats selama 60 detik agar selalu update real-time
        $stats = Cache::remember('dashboard_stats', 60, function () use ($todayStart, $todayEnd) {
            $todayStats = DB::table('idle_alarms')
                ->whereBetween('starting_time', [$todayStart, $todayEnd])
                ->selectRaw('COUNT(*) as total_today, AVG(duration_minutes) as avg_duration')
                ->first();

            // Restrict to last 7 days to avoid heavy disk I/O on slow SSD
            $allStats = DB::table('idle_alarms')
                ->where('starting_time', '>=', now()->subDays(7)->startOfDay())
                ->selectRaw('SUM(duration_minutes) as total_idle_min, COUNT(CASE WHEN alarm_status = "ALARM_END" THEN 1 END) as active_idle')
                ->first();

            return [
                'total_devices'      => Device::count(),
                'total_idle_today'   => $todayStats->total_today ?? 0,
                'total_idle_min'     => $allStats->total_idle_min ?? 0,
                'active_idle'        => $allStats->active_idle ?? 0,
                'avg_duration'       => round($todayStats->avg_duration ?? 0, 1),
                'total_alarm_today'  => $todayStats->total_today ?? 0,
                'total_users'        => User::count(),
                'active_users'       => User::where('status', 'active')->count(),
                'trend_devices'      => ['value' => '+ 12 (2.1%)',    'label' => 'vs last 7 days', 'color' => 'text-success', 'icon' => 'fa-arrow-up'],
                'trend_idle'         => ['value' => '+ 85 (10.4%)',   'label' => 'vs yesterday',   'color' => 'text-success', 'icon' => 'fa-arrow-up'],
                'trend_total_idle'   => ['value' => '+ 1,232 (4.8%)', 'label' => 'vs last 7 days', 'color' => 'text-success', 'icon' => 'fa-arrow-up'],
                'trend_avg'          => ['value' => '- 0.8 (-11.8%)', 'label' => 'vs last 7 days', 'color' => 'text-danger',  'icon' => 'fa-arrow-down'],
                'trend_users'        => ['value' => '-',               'label' => 'vs last 7 days', 'color' => 'text-muted',   'icon' => ''],
                'trend_active_users' => ['value' => '+ 1 (100%)',     'label' => 'vs last 7 days', 'color' => 'text-success', 'icon' => 'fa-arrow-up'],
            ];
        });

        // Cache chart data selama 60 detik
        $idlePerHour = Cache::remember('dashboard_idle_per_hour', 60, fn() => $this->getIdlePerHour());
        $idlePerDay  = Cache::remember('dashboard_idle_per_day',  60, fn() => $this->getIdlePerDay());
        $topDevices  = Cache::remember('dashboard_top_devices',   60, fn() => $this->getTopDevices(10));

        // Recent data tidak perlu cache — selalu fresh
        $recentAlarms = IdleAlarm::with('device')
            ->orderBy('starting_time', 'desc')
            ->limit(10)
            ->get();

        $importLogs = ImportLog::orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'idlePerHour',
            'idlePerDay',
            'topDevices',
            'recentAlarms',
            'importLogs'
        ));
    }

    /**
     * Get idle count per hour (last 24 hours)
     */
    private function getIdlePerHour()
    {
        $since = now()->subHours(23)->startOfHour()->format('Y-m-d H:i:s');

        // Fetch raw data and group in PHP to prevent MySQL Temporary Table / Filesort on slow SSD
        $rows = DB::table('idle_alarms')
            ->where('starting_time', '>=', $since)
            ->pluck('starting_time');

        $indexed = [];
        foreach ($rows as $time) {
            $hourKey = \Carbon\Carbon::parse($time)->format('H');
            if (!isset($indexed[$hourKey])) {
                $indexed[$hourKey] = 0;
            }
            $indexed[$hourKey]++;
        }

        $hours  = [];
        $counts = [];

        for ($i = 23; $i >= 0; $i--) {
            $ts       = now()->subHours($i);
            $hours[]  = $ts->format('H:00');
            $hourKey  = $ts->format('H');
            $counts[] = $indexed[$hourKey] ?? 0;
        }

        return ['hours' => $hours, 'counts' => $counts];
    }

    /**
     * Get idle count per day (last 7 days)
     */
    private function getIdlePerDay()
    {
        $since = now()->subDays(6)->startOfDay()->format('Y-m-d H:i:s');

        // Fetch raw data and group in PHP to prevent MySQL Temporary Table / Filesort on slow SSD
        $rows = DB::table('idle_alarms')
            ->where('starting_time', '>=', $since)
            ->pluck('starting_time');

        $indexed = [];
        foreach ($rows as $time) {
            $dayKey = \Carbon\Carbon::parse($time)->format('Y-m-d');
            if (!isset($indexed[$dayKey])) {
                $indexed[$dayKey] = 0;
            }
            $indexed[$dayKey]++;
        }

        $days   = [];
        $counts = [];

        for ($i = 6; $i >= 0; $i--) {
            $date     = now()->subDays($i);
            $days[]   = $date->format('D');
            $dayKey   = $date->format('Y-m-d');
            $counts[] = $indexed[$dayKey] ?? 0;
        }

        return ['days' => $days, 'counts' => $counts];
    }

    /**
     * Get top N devices with most idle alarms
     */
    private function getTopDevices($limit = 10)
    {
        // Restrict to last 7 days to avoid heavy disk I/O on slow SSD
        return IdleAlarm::select(
                'device_name',
                'device_id',
                DB::raw('COUNT(*) as total_idle'),
                DB::raw('SUM(duration_minutes) as total_duration'),
                DB::raw('MAX(starting_time) as last_seen')
            )
            ->where('starting_time', '>=', now()->subDays(7)->startOfDay())
            ->groupBy('device_name', 'device_id')
            ->orderBy('total_idle', 'desc')
            ->limit($limit)
            ->get();
    }
}
