<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\GpsTrack;
use App\Models\GpsTrackRaw;
use App\Models\Device;
use App\Http\Controllers\Frontend\Traits\HasDeviceGroups;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Services\ExcelExportService;

class SpeedController extends Controller
{
    use HasDeviceGroups;

    /**
     * Show speed monitoring page
     */
    public function index()
    {
        // ✅ All device sidebar data is cached for 5 minutes (trait)
        $sidebar = $this->getDeviceSidebarData();

        return view('frontend.speed.index', $sidebar);
    }

    /**
     * Get GPS tracks data for DataTables
     */
    public function getData(Request $request)
    {
        // ✅ RELEASE SESSION LOCK EARLY!
        session()->save();

        // ⚡ [MODE RAW] Sementara baca langsung dari gps_tracks_raw
        // karena gps_tracks sedang terkunci oleh rollback migrasi MySQL.
        // Setelah rollback selesai (cek processlist ID 1167), kembalikan ke GpsTrack.
        $query = GpsTrackRaw::select(
                'gps_tracks_raw.*',
                'devices.device_name as master_device_name',
                DB::raw('acc_state as is_acc_on'),
                DB::raw('over_speed as is_overspeed'),
                DB::raw('urgency as is_emergency')
            )
            ->leftJoin('devices', 'gps_tracks_raw.device_id', '=', 'devices.device_id')
            ->latest('gps_tracks_raw.gps_time');

        // Filter by specific device IDs (from tree view)
        if ($request->device_ids && is_array($request->device_ids)) {
            $totalDevices = cache()->remember('total_devices_count_db', 300, function() {
                return Device::count();
            });
            if (count($request->device_ids) < $totalDevices) {
                $cleanIds = array_map(function($id) {
                    return ltrim((string)$id, '0');
                }, $request->device_ids);
                $query->whereIn('gps_tracks_raw.device_id', $cleanIds);
            }
        }

        // Filter by location or series
        if ($request->filled('location') || $request->filled('series')) {
            if ($request->filled('location')) {
                $query->where('devices.location', $request->location);
            }
            if ($request->filled('series')) {
                if (strtoupper($request->series) === 'VOLVO') {
                    $query->where('devices.series', 'LIKE', '%FMX%');
                } else {
                    $query->where('devices.series', $request->series);
                }
            }
        }

        // Filter by speed range
        if ($request->filled('min_speed')) {
            $query->where('gps_tracks_raw.speed', '>=', $request->min_speed);
        }
        if ($request->filled('max_speed')) {
            $query->where('gps_tracks_raw.speed', '<=', $request->max_speed);
        }

        // Filter by overspeed
        if ($request->filled('overspeed') && $request->overspeed == '1') {
            $query->where('gps_tracks_raw.over_speed', 1);
        }

        // Filter by ACC status
        if ($request->filled('acc_on') && $request->acc_on == '1') {
            $query->where('gps_tracks_raw.acc_state', 1);
        }

        if ($request->filled('start_date')) {
            $query->where('gps_tracks_raw.gps_time', '>=', $request->start_date . ' 00:00:00');
        } else {
            // Default: hanya hari ini agar query cepat (gps_tracks_raw punya 9 juta baris)
            $query->where('gps_tracks_raw.gps_time', '>=', now()->startOfDay());
        }
        if ($request->filled('end_date')) {
            $query->where('gps_tracks_raw.gps_time', '<=', $request->end_date . ' 23:59:59');
        }

        // Filter by speed mode
        if ($request->filled('speed_filter')) {
            switch ($request->speed_filter) {
                case 'low':
                    $query->where('gps_tracks_raw.speed', '>', 0)
                          ->where('gps_tracks_raw.speed', '<', 15);
                    break;
                case 'high':
                    $query->where('gps_tracks_raw.speed', '>=', 41);
                    break;
            }
        } else {
            $query->where('gps_tracks_raw.speed', '>', 0);
        }

        return DataTables::of($query)
            ->addColumn('checkbox', function($row){
                return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
            })
            ->editColumn('device_name', function($row) {
                return $row->device_name ?: $row->master_device_name;
            })
            ->addColumn('fleet_name', function($row) {
                $name = $row->device_name ?: $row->master_device_name;
                if (!$name) return '-';
                $parts = explode('-', $name);
                return isset($parts[1]) ? $parts[1] : 'Unknown';
            })
            ->editColumn('gps_time', function($row) {
                return $row->gps_time ? date('Y-m-d H:i:s', strtotime($row->gps_time)) : '-';
            })
            ->rawColumns(['checkbox'])
            ->make(true);
    }

    /**
     * Export speed data to Excel (.xls)
     */
    public function export(Request $request)
    {
        $query = GpsTrackRaw::select(
                'gps_tracks_raw.*',
                'devices.device_name as master_device_name',
                DB::raw('acc_state as is_acc_on'),
                DB::raw('over_speed as is_overspeed'),
                DB::raw('urgency as is_emergency')
            )
            ->leftJoin('devices', 'gps_tracks_raw.device_id', '=', 'devices.device_id')
            ->latest('gps_tracks_raw.gps_time');

        // Export Selected Rows
        if ($request->selected_ids && is_array($request->selected_ids)) {
            $query->whereIn('gps_tracks_raw.id', $request->selected_ids);
        } else {
            // Filter by specific device IDs
            if ($request->device_ids && is_array($request->device_ids)) {
                $totalDevices = cache()->remember('total_devices_count_db', 300, function() {
                    return Device::count();
                });
                if (count($request->device_ids) < $totalDevices) {
                    $cleanIds = array_map(function($id) {
                        return ltrim((string)$id, '0');
                    }, $request->device_ids);
                    $query->whereIn('gps_tracks_raw.device_id', $cleanIds);
                }
            }

            // Filter by location or series
            if ($request->filled('location') || $request->filled('series')) {
                if ($request->filled('location')) {
                    $query->where('devices.location', $request->location);
                }
                if ($request->filled('series')) {
                    if (strtoupper($request->series) === 'VOLVO') {
                        $query->where('devices.series', 'LIKE', '%FMX%');
                    } else {
                        $query->where('devices.series', $request->series);
                    }
                }
            }

            // Filter by date range
            if ($request->filled('start_date')) {
                $query->where('gps_tracks_raw.gps_time', '>=', $request->start_date . ' 00:00:00');
            } else {
                $query->where('gps_tracks_raw.gps_time', '>=', now()->startOfDay());
            }
            if ($request->filled('end_date')) {
                $query->where('gps_tracks_raw.gps_time', '<=', $request->end_date . ' 23:59:59');
            }

            // Filter by speed mode
            if ($request->filled('speed_filter')) {
                switch ($request->speed_filter) {
                    case 'low':
                        $query->where('gps_tracks_raw.speed', '>', 0)
                              ->where('gps_tracks_raw.speed', '<', 15);
                        break;
                    case 'high':
                        $query->where('gps_tracks_raw.speed', '>=', 41);
                        break;
                }
            } else {
                $query->where('gps_tracks_raw.speed', '>', 0);
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['use_queue' => false]);
        }

        $metadata = [
            'Mode Export' => ($request->selected_ids && is_array($request->selected_ids)) ? 'Selected Rows (' . count($request->selected_ids) . ' items)' : 'All Filtered Rows',
            'Start Date' => $request->start_date ?? '-',
            'End Date' => $request->end_date ?? '-',
            'Speed Filter' => $request->speed_filter ? strtoupper($request->speed_filter) . ' SPEED' : 'Semua',
            'Location' => $request->location ?? 'Semua',
            'Series' => $request->series ?? 'Semua',
        ];

        $headers = [
            ['label' => 'NO', 'align' => 'center'],
            ['label' => 'DEVICE NAME (ID)', 'align' => 'left'],
            ['label' => 'FLEET', 'align' => 'left'],
            ['label' => 'SPEED', 'align' => 'right'],
            ['label' => 'ALTITUDE', 'align' => 'right'],
            ['label' => 'TIME', 'align' => 'center'],
            ['label' => 'LOCATION', 'align' => 'center'],
            ['label' => 'ACCURACY', 'align' => 'center'],
            ['label' => 'DIRECTION', 'align' => 'center'],
            ['label' => 'SATELLITES', 'align' => 'center'],
            ['label' => 'I/O STATUS', 'align' => 'center'],
            ['label' => 'EMERGENCY', 'align' => 'center'],
            ['label' => 'IGNITION (ACC)', 'align' => 'center'],
        ];

        return ExcelExportService::streamXls(
            'export-speed-monitoring-' . date('Y-m-d_H-i-s') . '.xls',
            'GPS SPEED MONITORING REPORT',
            $headers,
            function ($out) use ($query) {
                $serial = 1;
                foreach ($query->cursor() as $track) {
                    $rowClass = ($serial % 2 === 0) ? 'row-even' : 'row-odd';
                    
                    $realDevName = $track->device_name ?: $track->master_device_name;
                    $deviceName = ($realDevName ?? '-') . ' (' . $track->device_id . ')';
                    
                    $fleetName = '-';
                    if ($realDevName) {
                        $parts = explode('-', $realDevName);
                        $fleetName = isset($parts[1]) ? $parts[1] : 'Unknown';
                    }

                    $location = ($track->latitude && $track->longitude) ? $track->latitude . ',' . $track->longitude : '-';
                    $time = $track->gps_time ? date('Y-m-d H:i:s', strtotime($track->gps_time)) : '-';
                    $speed = $track->speed . ' Km/h';
                    $emergency = $track->is_emergency ? '1' : '0';
                    $ignition = $track->is_acc_on ? 'ON' : 'OFF';

                    $spd = (int) $track->speed;
                    $spdBadgeClass = 'text-right';
                    if ($spd >= 41) {
                        $spdBadgeClass = 'badge-danger';
                    } elseif ($spd >= 15) {
                        $spdBadgeClass = 'badge-warning';
                    } else {
                        $spdBadgeClass = 'text-right';
                    }

                    $emgClass = $track->is_emergency ? 'badge-danger' : 'text-center';
                    $accClass = $track->is_acc_on ? 'badge-success' : 'text-center';

                    fwrite($out, '    <tr class="' . $rowClass . '">' . "\n");
                    fwrite($out, '      <td class="text-center">' . $serial++ . '</td>' . "\n");
                    fwrite($out, '      <td class="text-left">' . htmlspecialchars($deviceName) . '</td>' . "\n");
                    fwrite($out, '      <td class="text-left">' . htmlspecialchars($fleetName) . '</td>' . "\n");
                    fwrite($out, '      <td class="' . $spdBadgeClass . '">' . htmlspecialchars($speed) . '</td>' . "\n");
                    fwrite($out, '      <td class="text-right">' . htmlspecialchars($track->altitude ?? '0') . '</td>' . "\n");
                    fwrite($out, '      <td class="text-center">' . htmlspecialchars($time) . '</td>' . "\n");
                    fwrite($out, '      <td class="text-center">' . htmlspecialchars($location) . '</td>' . "\n");
                    fwrite($out, '      <td class="text-center">0</td>' . "\n");
                    fwrite($out, '      <td class="text-center">' . htmlspecialchars($track->direction ?? '0') . '</td>' . "\n");
                    fwrite($out, '      <td class="text-center">' . htmlspecialchars($track->satellites ?? '0') . '</td>' . "\n");
                    fwrite($out, '      <td class="text-center">' . htmlspecialchars($track->input_output_status ?? '') . '</td>' . "\n");
                    fwrite($out, '      <td class="' . $emgClass . '">' . htmlspecialchars($emergency) . '</td>' . "\n");
                    fwrite($out, '      <td class="' . $accClass . '">' . htmlspecialchars($ignition) . '</td>' . "\n");
                    fwrite($out, '    </tr>' . "\n");
                }
            },
            $metadata
        );
    }
}
