<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\GpsTrack;
use App\Models\Device;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SpeedController extends Controller
{
    /**
     * Show speed monitoring page
     */
    public function index()
    {
        // Load device groups (same logic as IdleAlarmController)
        $locations = Device::whereNotNull('location')
            ->whereNotIn('location', ['MUD UTARA STB', 'STB_001', 'STB_SITE'])
            ->distinct()->pluck('location')->sort();
        
        $rawSeries = Device::whereNotNull('series')->distinct()->pluck('series');
        $seriesList = $rawSeries->map(function ($s) {
            if (stripos($s, 'FMX') !== false) return 'VOLVO';
            return $s;
        })->unique()->sort()->values();

        $devices = Device::whereNotNull('device_name')->orderBy('device_name')->get();
        $deviceGroups = [];

        foreach ($devices as $device) {
            $parts = explode('-', $device->device_name);
            $group = 'OTHER - GPE';
            if (count($parts) >= 2) {
                $type = $parts[1];
                if ($type == 'B' || $type == 'BUS') $group = 'BUS - GPE';
                elseif ($type == 'DT') $group = 'DT - GPE';
                elseif ($type == 'FT' || $type == 'GFTH') $group = 'FT - GPE';
                elseif ($type == 'HD') $group = 'HD - GPE';
                elseif ($type == 'LV') $group = 'PATROL - GPE';
                elseif ($type == 'WT') $group = 'WT - GPE';
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

        $totalDevices = $devices->count();
        $totalActive = $devices->where('status', 'active')->count();

        return view('frontend.speed.index', compact('deviceGroups', 'totalDevices', 'totalActive', 'locations', 'seriesList'));
    }

    /**
     * Get GPS tracks data for DataTables
     */
    public function getData(Request $request)
    {
        $query = GpsTrack::select('gps_tracks.*')
            ->leftJoin('devices', 'gps_tracks.device_id', '=', 'devices.device_id')
            ->latest('gps_tracks.gps_time');

        // Filter by specific device IDs (from tree view)
        if ($request->device_ids && is_array($request->device_ids)) {
            $query->whereIn('gps_tracks.device_id', $request->device_ids);
        }

        // Filter by location (via JOIN)
        if ($request->filled('location')) {
            $query->where('devices.location', $request->location);
        }

        // Filter by series (via JOIN)
        if ($request->filled('series')) {
            if (strtoupper($request->series) === 'VOLVO') {
                $query->where('devices.series', 'LIKE', '%FMX%');
            } else {
                $query->where('devices.series', $request->series);
            }
        }

        // Filter by fleet
        if ($request->filled('fleet_id')) {
            $query->where('gps_tracks.fleet_id', $request->fleet_id);
        }

        // Filter by speed range
        if ($request->filled('min_speed')) {
            $query->where('gps_tracks.speed', '>=', $request->min_speed);
        }
        if ($request->filled('max_speed')) {
            $query->where('gps_tracks.speed', '<=', $request->max_speed);
        }

        // Filter by overspeed
        if ($request->filled('overspeed') && $request->overspeed == '1') {
            $query->where('gps_tracks.is_overspeed', true);
        }

        // Filter by ACC status
        if ($request->filled('acc_on') && $request->acc_on == '1') {
            $query->where('gps_tracks.is_acc_on', true);
        }

        // Filter by date range (Optimized to avoid DATE() function on indexed columns)
        if ($request->filled('start_date')) {
            $query->where('gps_tracks.gps_time', '>=', $request->start_date . ' 00:00:00');
        }
        if ($request->filled('end_date')) {
            $query->where('gps_tracks.gps_time', '<=', $request->end_date . ' 23:59:59');
        }

        // Filter by speed mode
        // Default: exclude speed = 0
        // 'low' : speed 1 - 14 km/h
        // 'high': speed >= 41 km/h
        if ($request->filled('speed_filter')) {
            switch ($request->speed_filter) {
                case 'low':
                    $query->where('gps_tracks.speed', '>', 0)
                          ->where('gps_tracks.speed', '<', 15);
                    break;
                case 'high':
                    $query->where('gps_tracks.speed', '>=', 41);
                    break;
            }
        } else {
            // Default: sembunyikan data dengan speed = 0
            $query->where('gps_tracks.speed', '>', 0);
        }

        return DataTables::of($query)
            ->addColumn('checkbox', function($row){
                return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
            })
            ->editColumn('gps_time', function($row) {
                return $row->gps_time ? date('Y-m-d H:i:s', strtotime($row->gps_time)) : '-';
            })
            ->rawColumns(['checkbox'])
            ->make(true);
    }

    /**
     * Export speed data to CSV
     */
    public function export(Request $request)
    {
        $query = GpsTrack::select('gps_tracks.*')
            ->leftJoin('devices', 'gps_tracks.device_id', '=', 'devices.device_id')
            ->latest('gps_tracks.gps_time');

        // Export Selected Rows
        if ($request->selected_ids && is_array($request->selected_ids)) {
            $query->whereIn('gps_tracks.id', $request->selected_ids);
        } else {
            // Filter by specific device IDs (from tree view)
            if ($request->device_ids && is_array($request->device_ids)) {
                $query->whereIn('gps_tracks.device_id', $request->device_ids);
            }

            // Filter by location
            if ($request->filled('location')) {
                $query->where('devices.location', $request->location);
            }

            // Filter by series
            if ($request->filled('series')) {
                if (strtoupper($request->series) === 'VOLVO') {
                    $query->where('devices.series', 'LIKE', '%FMX%');
                } else {
                    $query->where('devices.series', $request->series);
                }
            }

            // Filter by date range
            if ($request->filled('start_date')) {
                $query->whereDate('gps_tracks.gps_time', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('gps_tracks.gps_time', '<=', $request->end_date);
            }

            // Filter by speed mode
            if ($request->filled('speed_filter')) {
                switch ($request->speed_filter) {
                    case 'low':
                        $query->where('gps_tracks.speed', '>', 0)
                              ->where('gps_tracks.speed', '<', 15);
                        break;
                    case 'high':
                        $query->where('gps_tracks.speed', '>=', 41);
                        break;
                }
            } else {
                $query->where('gps_tracks.speed', '>', 0);
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['use_queue' => false]);
        }

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            
            // Header CSV
            fputcsv($out, [
                'Serial No.', 'Device Name (ID)', 'Fleet', 'Speed', 'Altitude', 
                'Time', 'Location', 'Accuracy', 'Direction', 'Qty of Satellites', 
                'Input and Output Status', 'Emergency Alarm', 'Ignition'
            ], ';');

            $serial = 1;
            foreach ($query->cursor() as $track) {
                $deviceName = ($track->device_name ?? '-') . '(' . $track->device_id . ')';
                $location = ($track->latitude && $track->longitude) ? $track->latitude . ',' . $track->longitude : '-';
                $time = $track->gps_time ? date('Y-m-d H:i:s', strtotime($track->gps_time)) : '-';
                $speed = $track->speed . ' Km/h';
                $emergency = $track->is_emergency ? '1' : '0';
                $ignition = $track->is_acc_on ? 'ON' : 'OFF';
                $satellites = $track->satellites ?? '0';

                fputcsv($out, [
                    $serial++,
                    $deviceName,
                    $track->fleet_name ?? '-',
                    $speed,
                    $track->altitude ?? '0',
                    $time,
                    $location,
                    '0',
                    $track->direction ?? '0',
                    $satellites,
                    $track->input_output_status ?? '',
                    $emergency,
                    $ignition
                ], ';');
            }
            fclose($out);
        }, 'export-speed-monitoring-' . date('Y-m-d_H-i-s') . '.csv');
    }
}
