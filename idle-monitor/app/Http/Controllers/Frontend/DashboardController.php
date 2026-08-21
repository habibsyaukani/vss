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
            try {
                // One combined query for today's data using the optimized hourly stats table
                // Since this runs during the day, we still need to grab the current hour's raw data
                // Or we can just rely on the rollup table, which might be up to 1 hour behind.
                // For a dashboard, 1 hour behind for speed max is usually acceptable.
                // Alternatively, we query the rollup for past hours + raw for current hour.
                // Let's keep it simple and query the raw data only for the current hour, 
                // and the rollup for the rest. Actually, wait. It's much simpler to just
                // query GpsHourlyStat for the whole day. Since rollup runs hourly, it's fast.
                // To get real-time max speed, we can query raw data ONLY for >= start of current hour.
                $currentHourStart = Carbon::now()->startOfHour()->format('Y-m-d H:i:s');

                // 1. Get from Hourly Stats
                $deviceStats = \App\Models\GpsHourlyStat::select(
                        'device_id',
                        'device_name',
                        DB::raw('MAX(max_speed) as max_speed'),
                        DB::raw('SUM(sum_speed) as sum_speed'),
                        DB::raw('SUM(total_records) as row_count')
                    )
                    ->where('record_date', $today)
                    ->groupBy('device_id', 'device_name')
                    ->get();
                    
                // 2. Merge with real-time current hour data from raw
                $realtimeStats = GpsTrackRaw::select(
                        'device_id',
                        'device_name',
                        DB::raw('MAX(speed) as max_speed'),
                        DB::raw('SUM(speed) as sum_speed'),
                        DB::raw('COUNT(*) as row_count')
                    )
                    ->where('gps_time', '>=', $currentHourStart)
                    ->where('speed', '>', 0)
                    ->groupBy('device_id', 'device_name')
                    ->get();
                
                // Combine them
                $combined = [];
                foreach ($deviceStats as $d) {
                    $combined[$d->device_id] = [
                        'device_name' => $d->device_name,
                        'max_speed' => (float)$d->max_speed,
                        'sum_speed' => (float)$d->sum_speed,
                        'row_count' => (int)$d->row_count,
                    ];
                }
                foreach ($realtimeStats as $r) {
                    if (isset($combined[$r->device_id])) {
                        $combined[$r->device_id]['max_speed'] = max($combined[$r->device_id]['max_speed'], (float)$r->max_speed);
                        $combined[$r->device_id]['sum_speed'] += (float)$r->sum_speed;
                        $combined[$r->device_id]['row_count'] += (int)$r->row_count;
                    } else {
                        $combined[$r->device_id] = [
                            'device_name' => $r->device_name,
                            'max_speed' => (float)$r->max_speed,
                            'sum_speed' => (float)$r->sum_speed,
                            'row_count' => (int)$r->row_count,
                        ];
                    }
                }
                
                // Convert back to collection
                $deviceStats = collect($combined)->map(function ($item, $key) {
                    return (object)[
                        'device_id' => $key,
                        'device_name' => $item['device_name'],
                        'max_speed' => $item['max_speed'],
                        'sum_speed' => $item['sum_speed'],
                        'row_count' => $item['row_count'],
                    ];
                });

                // Stats
                $overallMaxSpeed = $deviceStats->max('max_speed') ?? 0;
                $totalSpeed      = $deviceStats->sum('sum_speed');
                $totalCount      = $deviceStats->sum('row_count');
                $overallAvgSpeed = $totalCount > 0 ? ($totalSpeed / $totalCount) : 0;

                // Top 5 speed units
                $topSpeedUnits = $deviceStats->sortByDesc('max_speed')->take(5)->values();

                // Speed per fleet
                $speedFleetMap = [];
                foreach ($deviceStats as $r) {
                    if (empty($r->device_name)) continue;
                    $parts = explode('-', $r->device_name);
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
                    'max_speed'     => number_format($overallMaxSpeed ?? 0, 1),
                    'avg_speed'     => number_format($overallAvgSpeed ?? 0, 1),
                    'topSpeedUnits' => $topSpeedUnits,
                    'speedPerFleet' => [
                        'labels' => array_map(fn($f) => $f . ' - GPE', array_keys($speedFleetMap)),
                        'counts' => array_map(fn($v) => round($v, 1), array_values($speedFleetMap)),
                    ],
                    'speedPerDay'   => $speedPerDay,
                ];
            } catch (\Exception $e) {
                \Log::warning('[Dashboard speedStats] Query timeout or error: ' . $e->getMessage());
                // Return empty data agar tidak loading selamanya
                return [
                    'max_speed'     => '—',
                    'avg_speed'     => '—',
                    'topSpeedUnits' => [],
                    'speedPerFleet' => ['labels' => [], 'counts' => []],
                    'speedPerDay'   => ['days' => [], 'counts' => []],
                ];
            }
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
     * Build speed-per-day chart data.
     */
    private function getSpeedPerDayChart(): array
    {
        $result  = ['days' => [], 'counts' => []];
        $minDate = '2026-08-07';
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $maxSpeed = 0;
            
            if ($date >= $minDate) {
                // Sangat cepat karena hanya baca 1 data max dari puluhan baris per hari
                $maxSpeed = \App\Models\GpsHourlyStat::where('record_date', $date)
                                ->max('max_speed') ?? 0;
                
                // Jika hari ini, tambah max speed dari real-time jam terakhir
                if ($i === 0) {
                    $currentHourStart = Carbon::now()->startOfHour()->format('Y-m-d H:i:s');
                    $rawMax = GpsTrackRaw::where('gps_time', '>=', $currentHourStart)
                                         ->where('speed', '>', 0)
                                         ->max('speed') ?? 0;
                    $maxSpeed = max($maxSpeed, $rawMax);
                }
            }
            
            $result['days'][]   = Carbon::parse($date)->format('d M');
            $result['counts'][] = round($maxSpeed, 1);
        }
        return $result;
    }
}
