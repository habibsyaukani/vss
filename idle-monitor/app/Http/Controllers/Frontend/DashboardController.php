<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\IdleAlarm;
use App\Models\GpsTrack;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Show frontend dashboard
     */
    public function index()
    {
        $today    = Carbon::today()->toDateString();
        $cacheKey = "dashboard_data_{$today}";

        // Cache seluruh data dashboard selama 5 menit
        // Kunjungan pertama: query ke DB. Kunjungan berikutnya: dari cache (instan)
        $data = Cache::remember($cacheKey, 300, function () use ($today) {

            $start        = $today . ' 00:00:00';
            $end          = $today . ' 23:59:59';
            $sevenDaysAgo = Carbon::today()->subDays(6)->toDateString();

            // ── 1. Stats hari ini (MAX & AVG speed dalam 1 query) ──────────
            $speedStats = GpsTrack::whereBetween('gps_time', [$start, $end])
                ->where('speed', '>', 0)
                ->selectRaw('MAX(speed) as max_speed, AVG(speed) as avg_speed')
                ->first();

            $stats = [
                'today_idle_count' => IdleAlarm::whereDate('starting_time', $today)->count(),
                'max_speed'        => number_format($speedStats->max_speed ?? 0, 1),
                'avg_speed'        => number_format($speedStats->avg_speed ?? 0, 1),
            ];

            // ── 2. Idle per day — 1 query (bukan 7 query terpisah) ─────────
            $idleByDay = IdleAlarm::selectRaw('DATE(starting_time) as day, COUNT(*) as total')
                ->whereDate('starting_time', '>=', $sevenDaysAgo)
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('total', 'day');

            $idlePerDay = ['days' => [], 'counts' => []];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i)->toDateString();
                $idlePerDay['days'][]   = Carbon::parse($date)->format('d/m');
                $idlePerDay['counts'][] = (int) ($idleByDay[$date] ?? 0);
            }

            // ── 3. Top 5 idle units hari ini ──────────────────────────────
            $topIdleUnits = IdleAlarm::select('device_name', 'device_id', DB::raw('COUNT(*) as event_count'))
                ->whereDate('starting_time', $today)
                ->groupBy('device_name', 'device_id')
                ->orderByDesc('event_count')
                ->limit(5)
                ->get();

            // ── 4. Idle per fleet — GROUP di DB, bukan di PHP ─────────────
            $idleFleetRaw = IdleAlarm::selectRaw(
                    "SUBSTRING_INDEX(SUBSTRING_INDEX(device_name, '-', 2), '-', -1) as fleet,
                     COUNT(*) as total"
                )
                ->whereDate('starting_time', $today)
                ->groupBy('fleet')
                ->orderByDesc('total')
                ->get();

            $idlePerFleet = [
                'labels' => $idleFleetRaw->map(fn($r) => $r->fleet . ' - GPE')->toArray(),
                'counts' => $idleFleetRaw->pluck('total')->map(fn($v) => (int) $v)->toArray(),
            ];

            // ── 5. Top 5 speed units hari ini ─────────────────────────────
            $topSpeedUnits = GpsTrack::select('device_name', 'device_id', DB::raw('MAX(speed) as max_speed'))
                ->whereBetween('gps_time', [$start, $end])
                ->where('speed', '>', 0)
                ->whereNotNull('device_name')
                ->groupBy('device_name', 'device_id')
                ->orderByDesc('max_speed')
                ->limit(5)
                ->get();

            // ── 6. Speed per fleet — GROUP di DB ──────────────────────────
            $speedFleetRaw = GpsTrack::selectRaw(
                    "SUBSTRING_INDEX(SUBSTRING_INDEX(device_name, '-', 2), '-', -1) as fleet,
                     MAX(speed) as max_speed"
                )
                ->whereBetween('gps_time', [$start, $end])
                ->where('speed', '>', 0)
                ->whereNotNull('device_name')
                ->groupBy('fleet')
                ->orderByDesc('max_speed')
                ->get();

            $speedPerFleet = [
                'labels' => $speedFleetRaw->map(fn($r) => $r->fleet . ' - GPE')->toArray(),
                'counts' => $speedFleetRaw->map(fn($r) => round($r->max_speed, 1))->toArray(),
            ];

            // ── 7. Max speed per day — 1 query (bukan 7 query terpisah) ───
            $speedByDay = GpsTrack::selectRaw('DATE(gps_time) as day, MAX(speed) as max_speed')
                ->whereDate('gps_time', '>=', $sevenDaysAgo)
                ->where('speed', '>', 0)
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('max_speed', 'day');

            $speedPerDay = ['days' => [], 'counts' => []];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i)->toDateString();
                $speedPerDay['days'][]   = Carbon::parse($date)->format('d M');
                $speedPerDay['counts'][] = round($speedByDay[$date] ?? 0, 1);
            }

            return compact(
                'stats', 'idlePerDay', 'topIdleUnits',
                'idlePerFleet', 'topSpeedUnits', 'speedPerFleet', 'speedPerDay'
            );
        });

        return view('frontend.dashboard', $data);
    }
}
