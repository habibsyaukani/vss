<?php

namespace App\Http\Controllers;

use App\Models\IdleAlarm;
use App\Models\Device;
use App\Models\DeviceGroup;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;

class IdleAlarmController extends Controller
{
    /**
     * Show idle alarms list page
     */
    public function index()
    {
        $groups = DeviceGroup::all();
        return view('admin.idle-alarm.index', compact('groups'));
    }

    /**
     * Get idle alarms data for DataTable (AJAX)
     */
    public function data(Request $request)
    {
        $query = IdleAlarm::query();

        // Filter by status
        if ($request->status) {
            $query->where('alarm_status', $request->status);
        }

        // Filter by device
        if ($request->device_id) {
            $query->where('device_id', $request->device_id);
        }

        // Filter by group
        if ($request->group) {
            // Join with devices to filter by group name
            $query->join('devices', 'idle_alarms.device_id', '=', 'devices.device_id')
                ->where('devices.group_name', $request->group)
                ->select('idle_alarms.*');
        }

        // Filter by date range (both filters based on starting_time)
        if ($request->start_date) {
            $query->whereDate('starting_time', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->whereDate('starting_time', '<=', $request->end_date);
        }

        // Filter by minimum duration
        if ($request->min_duration) {
            $query->where('duration_minutes', '>=', $request->min_duration);
        }

        return DataTables::of($query)
            ->addColumn('status_badge', function ($alarm) {
                $class = $alarm->alarm_status === 'ALARM_END' ? 'badge-success' : 'badge-warning';
                return '<span class="badge ' . $class . '">' . $alarm->alarm_status . '</span>';
            })
            ->addColumn('starting_time_formatted', function ($alarm) {
                return $alarm->starting_time ? date('Y-m-d H:i', strtotime($alarm->starting_time)) : '-';
            })
            ->addColumn('ending_time_formatted', function ($alarm) {
                return $alarm->ending_time ? date('Y-m-d H:i', strtotime($alarm->ending_time)) : '-';
            })
            ->addColumn('speed_info', function ($alarm) {
                return $alarm->start_speed . ' → ' . $alarm->end_speed . ' km/h';
            })
            ->addColumn('actions', function ($alarm) {
                return '
                    <a href="' . route('idle-alarm.show', $alarm->id) . '" class="btn btn-sm btn-info">
                        <i class="fas fa-eye"></i> Detail
                    </a>
                ';
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Show alarm detail
     */
    public function show(IdleAlarm $idleAlarm)
    {
        return view('admin.idle-alarm.show', compact('idleAlarm'));
    }

    /**
     * Export idle alarms to Excel
     */
    public function export(Request $request)
    {
        $query = IdleAlarm::query();

        // Apply same filters as data() method
        if ($request->status) {
            $query->where('alarm_status', $request->status);
        }
        if ($request->device_id) {
            $query->where('device_id', $request->device_id);
        }
        if ($request->start_date) {
            $query->whereDate('starting_time', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->whereDate('starting_time', '<=', $request->end_date);
        }
        if ($request->min_duration) {
            $query->where('duration_minutes', '>=', $request->min_duration);
        }

        $alarms = $query->get();

        // Create array for export
        $data = [];
        $data[] = [
            'Serial No',
            'Device Name',
            'Group',
            'Status',
            'Start Time',
            'End Time',
            'Duration (min)',
            'Speed (km/h)',
            'Start Location',
            'End Location',
            'Report Time',
        ];

        foreach ($alarms as $alarm) {
            $data[] = [
                $alarm->serial_no ?? '-',
                $alarm->device_name,
                $alarm->device->group_name ?? '-',
                $alarm->alarm_status,
                $alarm->starting_time ? date('Y-m-d H:i', strtotime($alarm->starting_time)) : '-',
                $alarm->ending_time ? date('Y-m-d H:i', strtotime($alarm->ending_time)) : '-',
                $alarm->duration_minutes,
                $alarm->start_speed . ' → ' . $alarm->end_speed,
                $alarm->starting_location ?? '-',
                $alarm->ending_location ?? '-',
                $alarm->report_time ? date('Y-m-d H:i', strtotime($alarm->report_time)) : '-',
            ];
        }

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            foreach ($data as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'idle-alarms-' . date('Y-m-d-H-i-s') . '.csv');
    }
}
