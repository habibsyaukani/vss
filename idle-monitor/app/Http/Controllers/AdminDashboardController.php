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
        $today = Carbon::today();
        
        // Get statistics
        $stats = [
            'total_devices' => Device::count(),
            'total_idle_today' => IdleAlarm::whereDate('created_at', $today)->count(),
            'active_idle' => IdleAlarm::where('alarm_status', 'ALARM_END')->count(),
            'avg_duration' => IdleAlarm::whereDate('created_at', $today)->avg('duration_minutes') ?? 0,
            'total_alarm_today' => IdleAlarm::whereDate('created_at', $today)->count(),
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
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

            $count = IdleAlarm::whereDate('created_at', $time->toDateString())
                ->whereRaw('HOUR(created_at) = ?', [$time->hour])
                ->count();
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

            $count = IdleAlarm::whereDate('created_at', $date->toDateString())->count();
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
        return IdleAlarm::select('device_name', DB::raw('COUNT(*) as total_idle'), DB::raw('SUM(duration_minutes) as total_duration'))
            ->groupBy('device_name')
            ->orderBy('total_idle', 'desc')
            ->limit($limit)
            ->get();
    }
}
