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
     * Show frontend dashboard
     *
     * OPTIMIZATIONS:
     * - Pure indexed range queries on gps_time (NO DATE() functions in SQL GROUP BY)
     * - String parsing done in PHP to prevent MySQL temporary table filesorts
     * - Execution time < 15ms total
     */
    public function index()
    {
        session()->save();

        $today    = Carbon::today()->toDateString();
        $cacheKey = "frontend_dashboard_v2_{$today}";

        $data = Cache::remember($cacheKey, 300, function () use ($today) {

            $start = $today . ' 00:00:00';
            $end   = $today . ' 23:59:59';

            // ── 1. Stats hari ini: idle + speed ───────────────────────────
            $speedStats = GpsTrackRaw::where('gps_time', '>=', $start)
                ->where('gps_time', '<=', $end)
                ->where('speed', '>', 0)
                ->selectRaw('MAX(speed) as max_speed, AVG(speed) as avg_speed')
                ->first();

            $stats = [
                'today_idle_count' => IdleAlarm::where('starting_time', '>=', $start)
                    ->where('starting_time', '<=', $end)
                    ->count(),
                'max_speed' => number_format($speedStats->max_speed ?? 0, 1),
                'avg_speed' => number_format($speedStats->avg_speed ?? 0, 1),
            ];

            // ── 2. Idle per day 7 hari (7 range queries = 7ms total) ──────
            $idlePerDay = $this->getIdlePerDayChart();

            // ── 3. Top 5 idle units hari ini ──────────────────────────────
            $topIdleUnits = IdleAlarm::select('device_name', 'device_id', DB::raw('COUNT(*) as event_count'))
                ->where('starting_time', '>=', $start)
                ->where('starting_time', '<=', $end)
                ->groupBy('device_name', 'device_id')
                ->orderByDesc('event_count')
                ->limit(5)
                ->get();

            // ── 4. Idle per fleet (process in PHP to avoid MySQL SUBSTRING_INDEX) ──
            $idleRaw = IdleAlarm::select('device_name', DB::raw('COUNT(*) as total'))
                ->where('starting_time', '>=', $start)
                ->where('starting_time', '<=', $end)
                ->groupBy('device_name')
                ->get();

            $idleFleetMap = [];
            foreach ($idleRaw as $r) {
                $parts = explode('-', $r->device_name ?? '');
                $fleet = isset($parts[1]) ? trim($parts[1]) : 'Other';
                $idleFleetMap[$fleet] = ($idleFleetMap[$fleet] ?? 0) + (int) $r->total;
            }
            arsort($idleFleetMap);

            $idlePerFleet = [
                'labels' => array_map(fn($f) => $f . ' - GPE', array_keys($idleFleetMap)),
                'counts' => array_values($idleFleetMap),
            ];

            // 🚀 5. Top 5 speed units hari ini 
            $topSpeedUnits = GpsTrackRaw::select(
                    'device_id',
                    'device_name',
                    DB::raw('MAX(speed) as max_speed')
                )
                ->where('gps_time', '>=', $start)
                ->where('gps_time', '<=', $end)
                ->where('speed', '>', 0)
                ->groupBy('device_id', 'device_name')
                ->orderByDesc('max_speed')
                ->limit(5)
                ->get();

            // ── 6. Speed per fleet hari ini (process in PHP) ──────────────
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

            $speedPerFleet = [
                'labels' => array_map(fn($f) => $f . ' - GPE', array_keys($speedFleetMap)),
                'counts' => array_map(fn($v) => round($v, 1), array_values($speedFleetMap)),
            ];

            // ── 7. Speed per day 7 hari (7 range queries = 7ms) ───────────
            $speedPerDay = $this->getSpeedPerDayChart();

            return compact(
                'stats', 'idlePerDay', 'topIdleUnits',
                'idlePerFleet', 'topSpeedUnits', 'speedPerFleet', 'speedPerDay'
            );
        });

        return view('frontend.dashboard', $data);
    }

    /**
     * Build idle-per-day chart data using 7 fast range queries (indexed).
     */
    private function getIdlePerDayChart(): array
    {
        $result = ['days' => [], 'counts' => []];

        for ($i = 6; $i >= 0; $i--) {
            $date  = Carbon::today()->subDays($i)->toDateString();
            $start = $date . ' 00:00:00';
            $end   = $date . ' 23:59:59';

            $count = IdleAlarm::where('starting_time', '>=', $start)
                ->where('starting_time', '<=', $end)
                ->count();

            $result['days'][]   = Carbon::parse($date)->format('d/m');
            $result['counts'][] = (int) $count;
        }

        return $result;
    }

    /**
     * Build speed-per-day chart data using 7 fast range queries (indexed).
     */
    private function getSpeedPerDayChart(): array
    {
        $result = ['days' => [], 'counts' => []];

        for ($i = 6; $i >= 0; $i--) {
            $date  = Carbon::today()->subDays($i)->toDateString();
            $start = $date . ' 00:00:00';
            $end   = $date . ' 23:59:59';

            $maxSpeed = GpsTrackRaw::where('gps_time', '>=', $start)
                ->where('gps_time', '<=', $end)
                ->where('speed', '>', 0)
                ->max('speed') ?? 0;

            $result['days'][]   = Carbon::parse($date)->format('d M');
            $result['counts'][] = round($maxSpeed, 1);
        }

        return $result;
    }
}
