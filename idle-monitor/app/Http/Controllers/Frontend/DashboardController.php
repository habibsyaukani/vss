<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\IdleAlarm;
use App\Models\GpsTrack;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Show frontend dashboard
     */
    public function index()
    {
        $today = Carbon::today();
        
        // Real speed stats from gps_tracks
        $maxSpeed = GpsTrack::where('gps_time', '>=', $today->format('Y-m-d') . ' 00:00:00')
            ->where('gps_time', '<=', $today->format('Y-m-d') . ' 23:59:59')
            ->max('speed') ?? 0;
        $avgSpeed = GpsTrack::where('gps_time', '>=', $today->format('Y-m-d') . ' 00:00:00')
            ->where('gps_time', '<=', $today->format('Y-m-d') . ' 23:59:59')
            ->where('speed', '>', 0)
            ->avg('speed') ?? 0;

        $stats = [
            'today_idle_count' => IdleAlarm::whereDate('starting_time', $today)->count(),
            'max_speed'        => number_format($maxSpeed, 1),
            'avg_speed'        => number_format($avgSpeed, 1),
        ];

        // Get idle per day (last 7 days) - for trend chart
        $idlePerDay = $this->getIdlePerDay();

        // Get top 5 idle units (today)
        $topIdleUnits = $this->getTopIdleUnits(5);

        // Get idle per fleet (today) - for donut chart
        $idlePerFleet = $this->getIdlePerFleet();

        // Get top 5 speed units (today)
        $topSpeedUnits = $this->getTopSpeedUnits(5);

        // Get speed per fleet (today) - for donut chart
        $speedPerFleet = $this->getSpeedPerFleet();

        // Get max speed per day (last 7 days)
        $speedPerDay = $this->getSpeedPerDay();

        return view('frontend.dashboard', compact(
            'stats',
            'idlePerDay',
            'topIdleUnits',
            'idlePerFleet',
            'topSpeedUnits',
            'speedPerFleet',
            'speedPerDay'
        ));
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
            $dayLabel = $date->format('d/m'); // Format: 05/05, 06/05, etc
            $days[] = $dayLabel;

            $count = IdleAlarm::whereDate('starting_time', $date->toDateString())->count();
            $counts[] = $count;
        }

        return [
            'days' => $days,
            'counts' => $counts,
        ];
    }

    /**
     * Get top N idle units (today)
     */
    private function getTopIdleUnits($limit = 5)
    {
        $today = Carbon::today();
        
        return IdleAlarm::select('device_name', 'device_id', DB::raw('COUNT(*) as event_count'))
            ->whereDate('starting_time', $today)
            ->groupBy('device_name', 'device_id')
            ->orderBy('event_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get idle per fleet (today) - for donut chart
     * Fleet = first part of device name (e.g., GPE-DT, GPE-FT, GPE-HD, etc.)
     */
    private function getIdlePerFleet()
    {
        $today = Carbon::today();
        
        // Extract fleet from device_name (e.g., GPE-DT-1232 -> DT)
        $alarms = IdleAlarm::select('device_name')
            ->whereDate('starting_time', $today)
            ->get();
        
        $fleetCounts = [];
        
        foreach ($alarms as $alarm) {
            // Extract series from device name (e.g., GPE-DT-1232 -> DT)
            $parts = explode('-', $alarm->device_name);
            if (count($parts) >= 2) {
                $fleet = $parts[1]; // DT, FT, HD, BUS, WT, etc.
                $fleetKey = $fleet . ' - GPE';
                
                if (!isset($fleetCounts[$fleetKey])) {
                    $fleetCounts[$fleetKey] = 0;
                }
                $fleetCounts[$fleetKey]++;
            }
        }
        
        // Sort by count descending
        arsort($fleetCounts);
        
        return [
            'labels' => array_keys($fleetCounts),
            'counts' => array_values($fleetCounts),
        ];
    }

    /**
     * Get top N speed units (today) - by max speed
     */
    private function getTopSpeedUnits($limit = 5)
    {
        $today = Carbon::today();

        return GpsTrack::select('device_name', 'device_id', DB::raw('MAX(speed) as max_speed'))
            ->where('gps_time', '>=', $today->format('Y-m-d') . ' 00:00:00')
            ->where('gps_time', '<=', $today->format('Y-m-d') . ' 23:59:59')
            ->where('speed', '>', 0)
            ->whereNotNull('device_name')
            ->groupBy('device_name', 'device_id')
            ->orderBy('max_speed', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get speed per fleet (today) - avg speed per fleet type
     */
    private function getSpeedPerFleet()
    {
        $today = Carbon::today();

        $tracks = GpsTrack::select('device_name', DB::raw('MAX(speed) as max_speed'))
            ->where('gps_time', '>=', $today->format('Y-m-d') . ' 00:00:00')
            ->where('gps_time', '<=', $today->format('Y-m-d') . ' 23:59:59')
            ->where('speed', '>', 0)
            ->whereNotNull('device_name')
            ->groupBy('device_name')
            ->get();

        $fleetSpeeds = [];
        foreach ($tracks as $track) {
            $parts = explode('-', $track->device_name);
            if (count($parts) >= 2) {
                $fleet = $parts[1] . ' - GPE';
                if (!isset($fleetSpeeds[$fleet])) {
                    $fleetSpeeds[$fleet] = [];
                }
                $fleetSpeeds[$fleet][] = $track->max_speed;
            }
        }

        $labels = [];
        $counts = [];
        foreach ($fleetSpeeds as $fleet => $speeds) {
            $labels[] = $fleet;
            $counts[] = round(max($speeds), 1); // max speed per fleet
        }

        // Sort by max speed desc
        array_multisort($counts, SORT_DESC, $labels);

        return ['labels' => $labels, 'counts' => $counts];
    }

    /**
     * Get max speed per day (last 7 days)
     */
    private function getSpeedPerDay()
    {
        $days = [];
        $counts = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $days[] = $date->format('d M');
            
            $maxSpeed = GpsTrack::where('gps_time', '>=', $date->format('Y-m-d') . ' 00:00:00')
                ->where('gps_time', '<=', $date->format('Y-m-d') . ' 23:59:59')
                ->max('speed') ?? 0;
                
            $counts[] = round($maxSpeed, 1);
        }

        return ['days' => $days, 'counts' => $counts];
    }
}
