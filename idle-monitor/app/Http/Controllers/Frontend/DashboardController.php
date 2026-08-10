<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\IdleAlarm;
use App\Models\GpsTrackRaw;
use App\Models\Device;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Show frontend dashboard — FAST load, speed data via AJAX
     */
    public function index()
    {
        session()->save();

        $today = Carbon::today()->toDateString();
        $start = $today . ' 00:00:00';
        $end   = $today . ' 23:59:59';

        // ── Idle stats hari ini (fast query, indexed on starting_time) ──
        $todayIdleCount = Cache::remember("dash_idle_count_{$today}", 60, function () use ($start, $end) {
            return IdleAlarm::where('starting_time', '>=', $start)
                ->where('starting_time', '<=', $end)
                ->count();
        });

        // ── Top 5 idle units hari ini ──
        $topIdleUnits = Cache::remember("dash_top_idle_{$today}", 60, function () use ($start, $end) {
            return IdleAlarm::select('device_name', 'device_id', DB::raw('COUNT(*) as event_count'))
                ->where('starting_time', '>=', $start)
                ->where('starting_time', '<=', $end)
                ->groupBy('device_name', 'device_id')
                ->orderByDesc('event_count')
                ->limit(5)
                ->get();
        });

        // ── Idle per fleet ──
        $idlePerFleet = Cache::remember("dash_idle_fleet_{$today}", 60, function () use ($start, $end) {
            $idleRaw = IdleAlarm::select('device_name', DB::raw('COUNT(*) as total'))
                ->where('starting_time', '>=', $start)
                ->where('starting_time', '<=', $end)
                ->groupBy('device_name')
                ->get();

            $map = [];
            foreach ($idleRaw as $r) {
                $parts = explode('-', $r->device_name ?? '');
                $fleet = isset($parts[1]) ? trim($parts[1]) : 'Other';
                $map[$fleet] = ($map[$fleet] ?? 0) + (int) $r->total;
            }
            arsort($map);
            return [
                'labels' => array_map(fn($f) => $f . ' - GPE', array_keys($map)),
                'counts' => array_values($map),
            ];
        });

        // ── Idle per day 7 hari ──
        $idlePerDay = Cache::remember("dash_idle_perday_{$today}", 60, function () {
            return $this->getIdlePerDayChart();
        });

        // ── Speed data: default nol, load via AJAX ──
        $stats = [
            'today_idle_count' => $todayIdleCount,
            'max_speed'        => '—',
            'avg_speed'        => '—',
        ];
        $topSpeedUnits = collect();
        $speedPerFleet = ['labels' => [], 'counts' => []];
        $speedPerDay   = ['days'   => [], 'counts' => []];

        return view('frontend.dashboard', compact(
            'stats', 'idlePerDay', 'topIdleUnits',
            'idlePerFleet', 'topSpeedUnits', 'speedPerFleet', 'speedPerDay'
        ));
    }

    /**
     * AJAX: Speed stats hari ini (dipanggil setelah halaman terbuka)
     */
    public function speedStats()
    {
        $today = Carbon::today()->toDateString();
        $start = $today . ' 00:00:00';
        $end   = $today . ' 23:59:59';

        $data = Cache::remember("dash_speed_all_{$today}", 120, function () use ($start, $end, $today) {
            // Stats
            $speedStats = GpsTrackRaw::where('gps_time', '>=', $start)
                ->where('gps_time', '<=', $end)
                ->where('speed', '>', 0)
                ->selectRaw('MAX(speed) as max_speed, AVG(speed) as avg_speed')
                ->first();

            // Top 5 speed units
            $topSpeedUnits = GpsTrackRaw::select('device_id', 'device_name', DB::raw('MAX(speed) as max_speed'))
                ->where('gps_time', '>=', $start)
                ->where('gps_time', '<=', $end)
                ->where('speed', '>', 0)
                ->groupBy('device_id', 'device_name')
                ->orderByDesc('max_speed')
                ->limit(5)
                ->get();

            // Speed per fleet
            $speedRaw = GpsTrackRaw::select('device_name', DB::raw('MAX(speed) as max_speed'))
                ->where('gps_time', '>=', $start)
                ->where('gps_time', '<=', $end)
                ->where('speed', '>', 0)
                ->whereNotNull('device_name')
                ->where('device_name', '!=', '')
                ->groupBy('device_name')
                ->get();

            $speedFleetMap = [];
            foreach ($speedRaw as $r) {
                $parts = explode('-', $r->device_name ?? '');
                $fleet = isset($parts[1]) ? trim($parts[1]) : 'Unknown';
                $maxSpd = (float) $r->max_speed;
                if (!isset($speedFleetMap[$fleet]) || $maxSpd > $speedFleetMap[$fleet]) {
                    $speedFleetMap[$fleet] = $maxSpd;
                }
            }
            arsort($speedFleetMap);

            // Speed per day 7 hari
            $speedPerDay = $this->getSpeedPerDayChart();

            return [
                'max_speed'     => number_format($speedStats->max_speed ?? 0, 1),
                'avg_speed'     => number_format($speedStats->avg_speed ?? 0, 1),
                'topSpeedUnits' => $topSpeedUnits,
                'speedPerFleet' => [
                    'labels' => array_map(fn($f) => $f . ' - GPE', array_keys($speedFleetMap)),
                    'counts' => array_map(fn($v) => round($v, 1), array_values($speedFleetMap)),
                ],
                'speedPerDay'   => $speedPerDay,
            ];
        });

        return response()->json($data);
    }

    /**
     * Build idle-per-day chart data using 7 fast range queries.
     */
    private function getIdlePerDayChart(): array
    {
        $result = ['days' => [], 'counts' => []];
        for ($i = 6; $i >= 0; $i--) {
            $date  = Carbon::today()->subDays($i)->toDateString();
            $start = $date . ' 00:00:00';
            $end   = $date . ' 23:59:59';
            $result['days'][]   = Carbon::parse($date)->format('d/m');
            $result['counts'][] = (int) IdleAlarm::where('starting_time', '>=', $start)
                ->where('starting_time', '<=', $end)->count();
        }
        return $result;
    }

    /**
     * Build speed-per-day chart data (skip empty historical dates).
     */
    private function getSpeedPerDayChart(): array
    {
        $result  = ['days' => [], 'counts' => []];
        $minDate = '2026-08-07';
        for ($i = 6; $i >= 0; $i--) {
            $date     = Carbon::today()->subDays($i)->toDateString();
            $maxSpeed = 0;
            if ($date >= $minDate) {
                $maxSpeed = GpsTrackRaw::where('gps_time', '>=', $date . ' 00:00:00')
                    ->where('gps_time', '<=', $date . ' 23:59:59')
                    ->where('speed', '>', 0)
                    ->max('speed') ?? 0;
            }
            $result['days'][]   = Carbon::parse($date)->format('d M');
            $result['counts'][] = round($maxSpeed, 1);
        }
        return $result;
    }
}
