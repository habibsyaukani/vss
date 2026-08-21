<?php

namespace App\Console\Commands;

use App\Models\GpsTrackRaw;
use App\Models\IdleAlarm;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Warms up all dashboard caches proactively.
 *
 * Run this:
 *   php artisan cache:warm-dashboard
 *
 * Or schedule it hourly in app/Console/Kernel.php:
 *   $schedule->command('cache:warm-dashboard')->hourly();
 */
class WarmDashboardCache extends Command
{
    protected $signature   = 'cache:warm-dashboard';
    protected $description = 'Pre-warm frontend & admin dashboard caches to prevent slow first load';

    public function handle(): int
    {
        $this->info('Warming dashboard caches...');

        $today = Carbon::today()->toDateString();

        // ── Speed per day 7 hari (paling lambat — cache per hari) ─────────
        $this->info('  → Warming speed_max_day cache...');
        for ($i = 6; $i >= 0; $i--) {
            $date  = Carbon::today()->subDays($i)->toDateString();
            $ttl   = ($i === 0) ? 600 : 3600;
            $start = $date . ' 00:00:00';
            $end   = $date . ' 23:59:59';

            Cache::remember("speed_max_day_{$date}", $ttl, function () use ($start, $end) {
                return GpsTrackRaw::whereBetween('gps_time', [$start, $end])
                    ->where('speed', '>', 0)
                    ->max('speed') ?? 0;
            });

            $this->line("    speed_max_day_{$date} cached");
        }

        // ── Idle per day 7 hari ───────────────────────────────────────────
        $this->info('  → Warming idle_day cache...');
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $ttl  = ($i === 0) ? 600 : 3600;

            Cache::remember("idle_day_{$date}", $ttl, function () use ($date) {
                return IdleAlarm::whereDate('starting_time', $date)->count();
            });

            $this->line("    idle_day_{$date} cached");
        }

        // ── Frontend device sidebar ───────────────────────────────────────
        $this->info('  → Warming frontend_device_sidebar cache...');
        Cache::forget('frontend_device_sidebar'); // force refresh
        $controller = new \App\Http\Controllers\Frontend\DashboardController();
        // Re-trigger via Cache::remember by calling the protected helper via reflection is complex.
        // Instead directly rebuild the device sidebar cache here.
        $devices = \App\Models\Device::whereNotNull('device_name')
            ->orderBy('device_name')
            ->get(['device_id', 'device_name', 'status', 'location', 'series']);

        $locations    = collect(['JO SELATAN', 'M.SERVICE', 'MUD', 'SELATAN', 'UTARA']);
        $seriesList   = collect(['DT HINO', 'DT VOLVO', 'HD 465', 'HD 785', 'OHT 773']);
        $deviceGroups = [];

        foreach ($devices as $device) {
            $parts = explode('-', $device->device_name);
            $group = 'OTHER - GPE';
            if (count($parts) >= 2) {
                $type = $parts[1];
                if ($type === 'B'  || $type === 'BUS')    $group = 'BUS - GPE';
                elseif ($type === 'DT')                    $group = 'DT - GPE';
                elseif ($type === 'FT' || $type === 'GFTH') $group = 'FT - GPE';
                elseif ($type === 'HD')                    $group = 'HD - GPE';
                elseif ($type === 'LV')                    $group = 'PATROL - GPE';
                elseif ($type === 'WT')                    $group = 'WT - GPE';
            }
            if (!isset($deviceGroups[$group])) $deviceGroups[$group] = ['total' => 0, 'active' => 0, 'devices' => []];
            $deviceGroups[$group]['total']++;
            if ($device->status === 'active') $deviceGroups[$group]['active']++;
            $deviceGroups[$group]['devices'][] = $device;
        }
        ksort($deviceGroups);

        Cache::put('frontend_device_sidebar', [
            'locations'    => $locations,
            'seriesList'   => $seriesList,
            'deviceGroups' => $deviceGroups,
            'totalDevices' => $devices->count(),
            'totalActive'  => $devices->where('status', 'active')->count(),
        ], 300);

        $this->info('✅ All caches warmed successfully!');
        return self::SUCCESS;
    }
}
