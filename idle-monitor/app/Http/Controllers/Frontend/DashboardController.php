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
     * - Uses GpsTrackRaw directly for real-time speed metrics without heavy joins
     * - Cache TTL: 10 minutes for today's data, 1 hour for previous days
     */
    public function index()
    {
        $today    = Carbon::today()->toDateString();
        $cacheKey = "frontend_dashboard_{$today}";

        $data = Cache::remember($cacheKey, 300, function () use ($today) {

            $start = $today . ' 00:00:00';
            $end   = $today . ' 23:59:59';

            // ── 1. Stats hari ini: idle + speed (hanya hari ini = fast) ────
            $speedStats = GpsTrackRaw::whereBetween('gps_time', [$start, $end])
                ->where('speed', '>', 0)
                ->selectRaw('MAX(speed) as max_speed, AVG(speed) as avg_speed')
                ->first();

            $stats = [
                'today_idle_count' => IdleAlarm::whereDate('starting_time', $today)->count(),
                'max_speed'        => number_format($speedStats->max_speed ?? 0, 1),
                'avg_speed'        => number_format($speedStats->avg_speed ?? 0, 1),
            ];

            // ── 2. Idle per day 7 hari (gunakan cache per-hari) ────────────
            $idlePerDay = $this->getIdlePerDayChart($today);

            // ── 3. Top 5 idle units hari ini ──────────────────────────────
            $topIdleUnits = IdleAlarm::select('device_name', 'device_id', DB::raw('COUNT(*) as event_count'))
                ->whereDate('starting_time', $today)
                ->groupBy('device_name', 'device_id')
                ->orderByDesc('event_count')
                ->limit(5)
                ->get();

            // ── 4. Idle per fleet — GROUP di DB ───────────────────────────
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

            // 🚀 5. Top 5 speed units hari ini 
            $topSpeedUnits = GpsTrackRaw::select(
                    'device_id',
                    'device_name',
                    DB::raw('MAX(speed) as max_speed')
                )
                ->whereBetween('gps_time', [$start, $end])
                ->where('speed', '>', 0)
                ->groupBy('device_id', 'device_name')
                ->orderByDesc('max_speed')
                ->limit(5)
                ->get();

            // ── 6. Speed per fleet hari ini ────────────────────────────────
            $speedFleetRaw = GpsTrackRaw::selectRaw(
                    "COALESCE(
                        NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(device_name, '-', 2), '-', -1), ''),
                        'Unknown'
                     ) as fleet,
                     MAX(speed) as max_speed"
                )
                ->whereBetween('gps_time', [$start, $end])
                ->where('speed', '>', 0)
                ->whereNotNull('device_name')
                ->where('device_name', '!=', '')
                ->groupBy('fleet')
                ->orderByDesc('max_speed')
                ->get();

            // Jika masih kosong, coba ambil dari alarm_raw sebagai fallback
            if ($speedFleetRaw->isEmpty()) {
                $speedFleetRaw = \App\Models\AlarmRaw::selectRaw(
                        "COALESCE(
                            NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(device_name, '-', 2), '-', -1), ''),
                            'Unknown'
                         ) as fleet,
                         MAX(end_speed) as max_speed"
                    )
                    ->whereBetween('start_time', [$start, $end])
                    ->where('end_speed', '>', 0)
                    ->whereNotNull('device_name')
                    ->groupBy('fleet')
                    ->orderByDesc('max_speed')
                    ->get();
            }

            $speedPerFleet = [
                'labels' => $speedFleetRaw->map(fn($r) => $r->fleet . ' - GPE')->toArray(),
                'counts' => $speedFleetRaw->map(fn($r) => round($r->max_speed, 1))->toArray(),
            ];

            // ── 7. Speed per day 7 hari (cache per-hari) ──────────────────
            $speedPerDay = $this->getSpeedPerDayChart($today);

            return compact(
                'stats', 'idlePerDay', 'topIdleUnits',
                'idlePerFleet', 'topSpeedUnits', 'speedPerFleet', 'speedPerDay'
            );
        });

        return view('frontend.dashboard', $data);
    }

    /**
     * Build idle-per-day chart data using per-day caches.
     */
    private function getIdlePerDayChart(string $today): array
    {
        $result = ['days' => [], 'counts' => []];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $ttl  = ($i === 0) ? 600 : 3600;

            $count = Cache::remember("idle_day_{$date}", $ttl, function () use ($date) {
                return IdleAlarm::whereDate('starting_time', $date)->count();
            });

            $result['days'][]   = Carbon::parse($date)->format('d/m');
            $result['counts'][] = (int) $count;
        }

        return $result;
    }

    /**
     * Build speed-per-day chart data using per-day caches.
     */
    private function getSpeedPerDayChart(string $today): array
    {
        $result = ['days' => [], 'counts' => []];

        for ($i = 6; $i >= 0; $i--) {
            $date  = Carbon::today()->subDays($i)->toDateString();
            $ttl   = ($i === 0) ? 600 : 3600;
            $start = $date . ' 00:00:00';
            $end   = $date . ' 23:59:59';

            $maxSpeed = Cache::remember("speed_max_day_{$date}", $ttl, function () use ($start, $end) {
                return GpsTrackRaw::whereBetween('gps_time', [$start, $end])
                    ->where('speed', '>', 0)
                    ->max('speed') ?? 0;
            });

            $result['days'][]   = Carbon::parse($date)->format('d M');
            $result['counts'][] = round($maxSpeed, 1);
        }

        return $result;
    }
}
