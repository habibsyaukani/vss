<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
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

        // ⚡ Cache devices map to avoid SQL joins on 9 million raw tracks table
        $deviceMap = cache()->remember('devices_map_by_id_dict', 300, function() {
            return Device::all()->keyBy(function($item) {
                return (string) $item->device_id;
            });
        });

        // ⚡ Fast indexed query purely on gps_tracks_raw (NO SQL JOINs)
        $query = GpsTrackRaw::from(DB::raw('gps_tracks_raw FORCE INDEX (gps_tracks_raw_gps_time_index)'))
            ->select(
                'id',
                'device_id',
                'device_name',
                'longitude',
                'latitude',
                'altitude',
                'speed',
                'direction',
                'satellites',
                'gps_time',
                'acc_state as is_acc_on',
                'over_speed as is_overspeed',
                'urgency as is_emergency'
            )
            ->latest('gps_time');

        $deviceIds = $request->device_ids;
        if (is_string($deviceIds)) {
            $deviceIds = json_decode($deviceIds, true);
        }

        // Filter by specific device IDs (from tree view)
        if ($deviceIds && is_array($deviceIds)) {
            $totalDevices = count($deviceMap);
            if (count($deviceIds) < $totalDevices) {
                $cleanIds = array_map(function($id) {
                    return ltrim((string)$id, '0');
                }, $deviceIds);
                $query->whereIn('gps_tracks_raw.device_id', $cleanIds);
            }
        }

        // Filter by location or series (in-memory lookup)
        if ($request->filled('location') || $request->filled('series')) {
            $filteredDevices = $deviceMap;
            if ($request->filled('location')) {
                $filteredDevices = $filteredDevices->where('lokasi', $request->location);
            }
            if ($request->filled('series')) {
                if (strtoupper($request->series) === 'VOLVO') {
                    $filteredDevices = $filteredDevices->filter(function($d) {
                        return stripos($d->series, 'FMX') !== false;
                    });
                } else {
                    $filteredDevices = $filteredDevices->where('series', $request->series);
                }
            }
            $query->whereIn('gps_tracks_raw.device_id', $filteredDevices->pluck('device_id')->toArray());
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

        // Filter by date
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

        // Limit data to prevent hanging on millions of rows when selecting ALL devices
        $data = $query->limit(2000)->get();

        return DataTables::of($data)
            ->addColumn('checkbox', function($row){
                return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
            })
            ->editColumn('device_name', function($row) use ($deviceMap) {
                $master = $deviceMap->get((string)$row->device_id);
                return $row->device_name ?: ($master ? $master->device_name : null);
            })
            ->addColumn('fleet_name', function($row) use ($deviceMap) {
                $master = $deviceMap->get((string)$row->device_id);
                $name = $row->device_name ?: ($master ? $master->device_name : null);
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
        $deviceMap = cache()->remember('devices_map_by_id_dict', 300, function() {
            return Device::all()->keyBy(function($item) {
                return (string) $item->device_id;
            });
        });

        $query = GpsTrackRaw::select(
                'id',
                'device_id',
                'device_name',
                'longitude',
                'latitude',
                'altitude',
                'speed',
                'direction',
                'satellites',
                'gps_time',
                'acc_state as is_acc_on',
                'over_speed as is_overspeed',
                'urgency as is_emergency'
            )
            ->latest('gps_tracks_raw.gps_time');

        // Export Selected Rows
        $selectedIds = $request->selected_ids;
        if (is_string($selectedIds)) {
            $selectedIds = json_decode($selectedIds, true);
        }
        
        if ($selectedIds && is_array($selectedIds)) {
            $query->whereIn('gps_tracks_raw.id', $selectedIds);
        } else {
            // Filter by specific device IDs
            $deviceIds = $request->device_ids;
            if (is_string($deviceIds)) {
                $deviceIds = json_decode($deviceIds, true);
            }
            if ($deviceIds && is_array($deviceIds)) {
                $totalDevices = count($deviceMap);
                if (count($deviceIds) < $totalDevices) {
                    $cleanIds = array_map(function($id) {
                        return ltrim((string)$id, '0');
                    }, $deviceIds);
                    $query->whereIn('gps_tracks_raw.device_id', $cleanIds);
                }
            }

            // Filter by location or series
            if ($request->filled('location') || $request->filled('series')) {
                $filteredDevices = $deviceMap;
                if ($request->filled('location')) {
                    $filteredDevices = $filteredDevices->where('lokasi', $request->location);
                }
                if ($request->filled('series')) {
                    if (strtoupper($request->series) === 'VOLVO') {
                        $filteredDevices = $filteredDevices->filter(function($d) {
                            return stripos($d->series, 'FMX') !== false;
                        });
                    } else {
                        $filteredDevices = $filteredDevices->where('series', $request->series);
                    }
                }
                $query->whereIn('gps_tracks_raw.device_id', $filteredDevices->pluck('device_id')->toArray());
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
            'NO',
            'DEVICE NAME (ID)',
            'FLEET',
            'SPEED',
            'ALTITUDE',
            'TIME',
            'LOCATION',
            'ACCURACY',
            'DIRECTION',
            'SATELLITES',
            'I/O STATUS',
            'EMERGENCY',
            'IGNITION (ACC)'
        ];

        return ExcelExportService::streamCsv(
            'export-speed-monitoring-' . date('Y-m-d_H-i-s') . '.csv',
            $headers,
            function ($out) use ($query, $deviceMap) {
                $serial = 1;
                foreach ($query->cursor() as $track) {
                    $master = $deviceMap->get((string)$track->device_id);
                    $realDevName = $track->device_name ?: ($master ? $master->device_name : null);
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

                    fputcsv($out, [
                        $serial++,
                        $deviceName,
                        $fleetName,
                        $speed,
                        $track->altitude ?? '0',
                        $time,
                        $location,
                        '0',
                        $track->direction ?? '0',
                        $track->satellites ?? '0',
                        $track->input_output_status ?? '',
                        $emergency,
                        $ignition
                    ]);
                }
            }
        );
    }
}
