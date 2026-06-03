<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IdleAlarm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $todayIdle = IdleAlarm::whereDate('starting_time', today())->count();
        $activeIdle = IdleAlarm::where('alarm_status', 'active')->count();
        $avgDuration = IdleAlarm::avg('duration_minutes') ?? 0;

        return response()->json([
            'today_idle' => $todayIdle,
            'active_idle' => $activeIdle,
            'avg_duration' => round($avgDuration, 2)
        ]);
    }

    public function statistics(Request $request)
    {
        $startDate = $request->query('start_date', now()->subDays(30));
        $endDate = $request->query('end_date', now());

        $byType = IdleAlarm::whereBetween('starting_time', [$startDate, $endDate])
            ->groupBy('alarm_type')
            ->selectRaw('alarm_type, COUNT(*) as count')
            ->get();

        $byDevice = IdleAlarm::whereBetween('starting_time', [$startDate, $endDate])
            ->groupBy('device_id')
            ->selectRaw('device_id, device_name, COUNT(*) as count')
            ->limit(10)
            ->get();

        return response()->json([
            'by_type' => $byType,
            'by_device' => $byDevice
        ]);
    }

    public function recentAlarms(Request $request)
    {
        $limit = $request->query('limit', 50);

        $alarms = IdleAlarm::latest('starting_time')
            ->limit($limit)
            ->get();

        return response()->json($alarms);
    }
}
