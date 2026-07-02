<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\IdleAlarm;
use App\Models\ImportLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    /**
     * Show admin dashboard
     */
    public function index()
    {
        $todayStart = Carbon::today()->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();
        
        // Get statistics
        $stats = [
            'total_devices' => Device::count(),
            'total_idle_today' => IdleAlarm::whereBetween('created_at', [$todayStart, $todayEnd])->count(),
            'total_idle_min' => IdleAlarm::sum('duration_minutes') ?? 0,
            'active_idle' => IdleAlarm::where('alarm_status', 'ALARM_END')->count(),
            'avg_duration' => IdleAlarm::whereBetween('created_at', [$todayStart, $todayEnd])->avg('duration_minutes') ?? 0,
            'total_alarm_today' => IdleAlarm::whereBetween('created_at', [$todayStart, $todayEnd])->count(),
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            // Mock Trends
            'trend_devices' => ['value' => '+ 12 (2.1%)', 'label' => 'vs last 7 days', 'color' => 'text-success', 'icon' => 'fa-arrow-up'],
            'trend_idle' => ['value' => '+ 85 (10.4%)', 'label' => 'vs yesterday', 'color' => 'text-success', 'icon' => 'fa-arrow-up'],
            'trend_total_idle' => ['value' => '+ 1,232 (4.8%)', 'label' => 'vs last 7 days', 'color' => 'text-success', 'icon' => 'fa-arrow-up'],
            'trend_avg' => ['value' => '- 0.8 (-11.8%)', 'label' => 'vs last 7 days', 'color' => 'text-danger', 'icon' => 'fa-arrow-down'],
            'trend_users' => ['value' => '-', 'label' => 'vs last 7 days', 'color' => 'text-muted', 'icon' => ''],
            'trend_active_users' => ['value' => '+ 1 (100%)', 'label' => 'vs last 7 days', 'color' => 'text-success', 'icon' => 'fa-arrow-up'],
        ];

        // Get idle per hour (last 24 hours)
        $idlePerHour = $this->getIdlePerHour();

        // Get idle per day (last 7 days)
        $idlePerDay = $this->getIdlePerDay();

        // Get top 10 devices with most idle
        $topDevices = $this->getTopDevices(10);

        // Get recent alarms
        $recentAlarms = IdleAlarm::with('device')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get import logs
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
        $hours = [];
        $counts = [];

        for ($i = 23; $i >= 0; $i--) {
            $time = now()->subHours($i);
            $hour = $time->format('H:00');
            $hours[] = $hour;

            $startOfHour = $time->copy()->startOfHour();
            $endOfHour = $time->copy()->endOfHour();
            
            $count = IdleAlarm::whereBetween('created_at', [$startOfHour, $endOfHour])->count();
            $counts[] = $count;
        }

        return [
            'hours' => $hours,
            'counts' => $counts,
        ];
    }

    /**
     * Get idle count per day (last 7 days)
     */
    private function getIdlePerDay()
    {
        $days = [];
        $counts = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dayName = $date->format('D');
            $days[] = $dayName;

            $startOfDay = $date->copy()->startOfDay();
            $endOfDay = $date->copy()->endOfDay();
            
            $count = IdleAlarm::whereBetween('created_at', [$startOfDay, $endOfDay])->count();
            $counts[] = $count;
        }

        return [
            'days' => $days,
            'counts' => $counts,
        ];
    }

    /**
     * Get top N devices with most idle alarms
     */
    private function getTopDevices($limit = 10)
    {
        return IdleAlarm::select('device_name', DB::raw('COUNT(*) as total_idle'), DB::raw('SUM(duration_minutes) as total_duration'), DB::raw('MAX(created_at) as last_seen'))
            ->groupBy('device_name')
            ->orderBy('total_idle', 'desc')
            ->limit($limit)
            ->get();
    }
}
