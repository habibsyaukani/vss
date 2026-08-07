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
     *
     * OPTIMIZATIONS:
     * - gps_tracks queries over 7 days are extremely slow (100+ sec per query)
     *   → Query data hari ini saja (fast, hanya 1 hari data)
     * - "Speed per day 7 hari" disimpan dalam daily cache terpisah (di-build incremental)
     * - Cache TTL: 10 menit untuk data hari ini, 1 jam untuk data hari sebelumnya
     */
    public function index()
    {
        $today        = Carbon::today()->toDateString();
        $cacheKey     = "frontend_dashboard_{$today}";

        $data = Cache::remember($cacheKey, 600, function () use ($today) {

            $start        = $today . ' 00:00:00';
            $end          = $today . ' 23:59:59';

            // ── 1. Stats hari ini: idle + speed (hanya hari ini = fast) ────
            $speedStats = GpsTrack::whereBetween('gps_time', [$start, $end])
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
            $topSpeedUnits = GpsTrack::select(
                    'gps_tracks.device_id',
                    DB::raw('COALESCE(devices.device_name, gps_tracks.device_name) as device_name'),
                    DB::raw('MAX(gps_tracks.speed) as max_speed')
                )
                ->leftJoin('devices', 'gps_tracks.device_id', '=', 'devices.device_id')
                ->whereBetween('gps_tracks.gps_time', [$start, $end])
                ->where('gps_tracks.speed', '>', 0)
                ->groupBy('gps_tracks.device_id', 'devices.device_name', 'gps_tracks.device_name')
                ->orderByDesc('max_speed')
                ->limit(5)
                ->get();

            // ── 6. Speed per fleet hari ini ────────────────────────────────
            // COALESCE agar data GPS tanpa device_name tetap masuk grafik
            $speedFleetRaw = GpsTrack::selectRaw(
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
     * Days sebelum hari ini: cache 1 jam (data tidak berubah).
     * Hari ini: cache 10 menit.
     */
    private function getIdlePerDayChart(string $today): array
    {
        $result = ['days' => [], 'counts' => []];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $ttl  = ($i === 0) ? 600 : 3600; // hari ini: 10 menit, lainnya: 1 jam

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
     * Query gps_tracks per hari sangat jauh lebih cepat daripada 1 query 7 hari
     * karena index gps_time dapat digunakan optimal dengan range 1 hari.
     */
    private function getSpeedPerDayChart(string $today): array
    {
        $result = ['days' => [], 'counts' => []];

        for ($i = 6; $i >= 0; $i--) {
            $date  = Carbon::today()->subDays($i)->toDateString();
            $ttl   = ($i === 0) ? 600 : 3600; // hari ini: 10 menit, lainnya: 1 jam
            $start = $date . ' 00:00:00';
            $end   = $date . ' 23:59:59';

            $maxSpeed = Cache::remember("speed_max_day_{$date}", $ttl, function () use ($start, $end) {
                return GpsTrack::whereBetween('gps_time', [$start, $end])
                    ->where('speed', '>', 0)
                    ->max('speed') ?? 0;
            });

            $result['days'][]   = Carbon::parse($date)->format('d M');
            $result['counts'][] = round($maxSpeed, 1);
        }

        return $result;
    }
}
