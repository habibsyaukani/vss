<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IdleAlarm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * GET /api/dashboard
     * Summary dashboard dengan statistik idle alarm
     */
    public function index()
    {
        // Hanya idle alarms yang sudah CLOSED (selesai)
        $todayIdle = IdleAlarm::where('alarm_status', 'CLOSED')
            ->whereDate('starting_time', today())
            ->count();

        $totalIdle = IdleAlarm::where('alarm_status', 'CLOSED')->count();
        
        $avgDuration = IdleAlarm::where('alarm_status', 'CLOSED')
            ->avg('duration_minutes') ?? 0;

        $totalDurationMinutes = IdleAlarm::where('alarm_status', 'CLOSED')
            ->sum('duration_minutes') ?? 0;

        $uniqueDevices = IdleAlarm::where('alarm_status', 'CLOSED')
            ->distinct('device_id')
            ->count('device_id');

        return response()->json([
            'success' => true,
            'data' => [
                'today_idle_count' => $todayIdle,
                'total_idle_count' => $totalIdle,
                'avg_duration_minutes' => round($avgDuration, 2),
                'total_duration_hours' => round($totalDurationMinutes / 60, 2),
                'unique_devices' => $uniqueDevices,
                'timestamp' => now()->toIso8601String(),
            ]
        ]);
    }

    /**
     * GET /api/dashboard/statistics
     * Statistik mendetail: by group, by device, by date range
     */
    public function statistics(Request $request)
    {
        $startDate = $request->query('start_date', now()->subDays(30)->startOfDay());
        $endDate = $request->query('end_date', now()->endOfDay());

        // By group
        $byGroup = IdleAlarm::where('alarm_status', 'CLOSED')
            ->whereBetween('starting_time', [$startDate, $endDate])
            ->select('device_id')
            ->with('device:id,device_id,device_name,group_name')
            ->get()
            ->groupBy('device.group_name')
            ->map(function ($group) {
                return [
                    'group_name' => $group->first()?->device?->group_name ?? 'Unknown',
                    'count' => $group->count(),
                    'total_duration_minutes' => $group->sum('duration_minutes'),
                ];
            });

        // By device (top 10)
        $byDevice = IdleAlarm::where('alarm_status', 'CLOSED')
            ->whereBetween('starting_time', [$startDate, $endDate])
            ->groupBy('device_id', 'device_name')
            ->selectRaw('device_id, device_name, COUNT(*) as idle_count, SUM(duration_minutes) as total_duration')
            ->orderByDesc('total_duration')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'date_range' => [
                    'start' => $startDate->toIso8601String(),
                    'end' => $endDate->toIso8601String(),
                ],
                'by_group' => $byGroup,
                'by_device' => $byDevice,
                'timestamp' => now()->toIso8601String(),
            ]
        ]);
    }

    /**
     * GET /api/dashboard/recent
     * Alarm terbaru (default: 50 records)
     */
    public function recentAlarms(Request $request)
    {
        $limit = $request->query('limit', 50);
        $limit = min($limit, 500); // Max 500

        $alarms = IdleAlarm::where('alarm_status', 'CLOSED')
            ->orderByDesc('starting_time')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $alarms->count(),
                'alarms' => $alarms,
                'timestamp' => now()->toIso8601String(),
            ]
        ]);
    }
}
