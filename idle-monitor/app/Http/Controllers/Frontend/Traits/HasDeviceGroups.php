<?php

namespace App\Http\Controllers\Frontend\Traits;

use App\Models\Device;
use Illuminate\Support\Facades\Cache;

/**
 * Shared device-grouping logic for all frontend controllers.
 * Results are cached for 5 minutes to avoid repeated DB queries.
 */
trait HasDeviceGroups
{
    /**
     * Return all data needed to render the sidebar device tree.
     * All queries are consolidated and cached.
     *
     * @return array{locations, seriesList, deviceGroups, totalDevices, totalActive}
     */
    protected function getDeviceSidebarData(): array
    {
        return Cache::remember('frontend_device_sidebar', 300, function () {

            // Single query — fetch all columns we need at once
            $devices = Device::whereNotNull('device_name')
                ->orderBy('device_name')
                ->get(['device_id', 'device_name', 'status', 'location', 'series']);

            // ── Locations ─────────────────────────────────────────────
            $locations = collect(['JO SELATAN', 'M.SERVICE', 'MUD', 'SELATAN', 'UTARA']);

            // ── Series ────────────────────────────────────────────────
            $seriesList = collect(['DT HINO', 'DT VOLVO', 'HD 465', 'HD 785', 'OHT 773']);

            // ── Device Groups (tree view) ──────────────────────────────
            $deviceGroups = [];

            foreach ($devices as $device) {
                $parts = explode('-', $device->device_name);
                $group = 'OTHER - GPE';

                if (count($parts) >= 2) {
                    $type = $parts[1];
                    if ($type === 'B'  || $type === 'BUS')              $group = 'BUS - GPE';
                    elseif ($type === 'DT')                              $group = 'DT - GPE';
                    elseif ($type === 'FT' || $type === 'GFTH')         $group = 'FT - GPE';
                    elseif ($type === 'HD')                              $group = 'HD - GPE';
                    elseif ($type === 'LV')                              $group = 'PATROL - GPE';
                    elseif ($type === 'WT')                              $group = 'WT - GPE';
                }

                if (!isset($deviceGroups[$group])) {
                    $deviceGroups[$group] = ['total' => 0, 'active' => 0, 'devices' => []];
                }

                $deviceGroups[$group]['total']++;
                if ($device->status === 'active') {
                    $deviceGroups[$group]['active']++;
                }
                $deviceGroups[$group]['devices'][] = $device;
            }

            ksort($deviceGroups);

            return [
                'locations'    => $locations,
                'seriesList'   => $seriesList,
                'deviceGroups' => $deviceGroups,
                'totalDevices' => $devices->count(),
                'totalActive'  => $devices->where('status', 'active')->count(),
            ];
        });
    }
}
