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

            $allStats = DB::table('idle_alarms')
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
            ->orderBy('created_at', 'desc')
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

        $rows = DB::table('idle_alarms')
            ->whereRaw('`starting_time` >= ?', [$since])
            ->selectRaw(
                'YEAR(starting_time)  AS yr,' .
                ' MONTH(starting_time) AS mo,' .
                ' DAY(starting_time)   AS dy,' .
                ' HOUR(starting_time)  AS hr,' .
                ' COUNT(*)             AS cnt'
            )
            ->groupByRaw('YEAR(starting_time), MONTH(starting_time), DAY(starting_time), HOUR(starting_time)')
            ->orderByRaw('YEAR(starting_time), MONTH(starting_time), DAY(starting_time), HOUR(starting_time)')
            ->get();

        $indexed = [];
        foreach ($rows as $row) {
            $key = str_pad($row->hr, 2, '0', STR_PAD_LEFT);
            $indexed[$key] = $row->cnt;
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

        $rows = DB::table('idle_alarms')
            ->whereRaw('`starting_time` >= ?', [$since])
            ->selectRaw('DATE(starting_time) AS day_date, COUNT(*) AS cnt')
            ->groupByRaw('DATE(starting_time)')
            ->orderByRaw('DATE(starting_time)')
            ->get()
            ->keyBy('day_date');

        $days   = [];
        $counts = [];

        for ($i = 6; $i >= 0; $i--) {
            $date     = now()->subDays($i);
            $days[]   = $date->format('D');
            $counts[] = $rows->get($date->format('Y-m-d'))->cnt ?? 0;
        }

        return ['days' => $days, 'counts' => $counts];
    }

    /**
     * Get top N devices with most idle alarms
     */
    private function getTopDevices($limit = 10)
    {
        return IdleAlarm::select(
                'device_name',
                'device_id',
                DB::raw('COUNT(*) as total_idle'),
                DB::raw('SUM(duration_minutes) as total_duration'),
                DB::raw('MAX(starting_time) as last_seen')
            )
            ->groupBy('device_name', 'device_id')
            ->orderBy('total_idle', 'desc')
            ->limit($limit)
            ->get();
    }
}
