<?php

namespace App\Http\Controllers;

use App\Models\IdleAlarm;
use App\Models\Device;
use App\Models\DeviceGroup;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Services\ExcelExportService;

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
     * Export idle alarms to Excel (.xls)
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

        $metadata = [
            'Status' => $request->status ?? 'Semua',
            'Device ID' => $request->device_id ?? 'Semua',
            'Start Date' => $request->start_date ?? '-',
            'End Date' => $request->end_date ?? '-',
            'Min Duration' => $request->min_duration ? $request->min_duration . ' min' : 'Semua',
        ];

        $headers = [
            ['label' => 'SERIAL NO', 'align' => 'center'],
            ['label' => 'DEVICE ID', 'align' => 'center'],
            ['label' => 'DEVICE NAME', 'align' => 'left'],
            ['label' => 'GROUP', 'align' => 'left'],
            ['label' => 'STATUS', 'align' => 'center'],
            ['label' => 'START TIME', 'align' => 'center'],
            ['label' => 'END TIME', 'align' => 'center'],
            ['label' => 'DURATION (MIN)', 'align' => 'center'],
            ['label' => 'SPEED (KM/H)', 'align' => 'center'],
            ['label' => 'START LOCATION', 'align' => 'center'],
            ['label' => 'END LOCATION', 'align' => 'center'],
            ['label' => 'REPORT TIME', 'align' => 'center'],
        ];

        return ExcelExportService::streamXls(
            'idle-alarms-' . date('Y-m-d-H-i-s') . '.xls',
            'ADMIN IDLE ALARMS REPORT',
            $headers,
            function ($out) use ($query) {
                $serial = 1;
                foreach ($query->cursor() as $alarm) {
                    $rowClass = ($serial % 2 === 0) ? 'row-even' : 'row-odd';
                    $statusClass = $alarm->alarm_status === 'ALARM_END' ? 'badge-success' : 'badge-warning';

                    fwrite($out, '    <tr class="' . $rowClass . '">' . "\n");
                    fwrite($out, '      <td class="text-center">' . htmlspecialchars($alarm->serial_no ?? $serial++) . '</td>' . "\n");
                    fwrite($out, '      <td class="text-center">' . htmlspecialchars($alarm->device_id ?? '-') . '</td>' . "\n");
                    fwrite($out, '      <td class="text-left">' . htmlspecialchars($alarm->device_name ?? '-') . '</td>' . "\n");
                    fwrite($out, '      <td class="text-left">' . htmlspecialchars($alarm->device->group_name ?? '-') . '</td>' . "\n");
                    fwrite($out, '      <td class="' . $statusClass . '">' . htmlspecialchars($alarm->alarm_status ?? '-') . '</td>' . "\n");
                    fwrite($out, '      <td class="text-center">' . ($alarm->starting_time ? date('Y-m-d H:i', strtotime($alarm->starting_time)) : '-') . '</td>' . "\n");
                    fwrite($out, '      <td class="text-center">' . ($alarm->ending_time ? date('Y-m-d H:i', strtotime($alarm->ending_time)) : '-') . '</td>' . "\n");
                    fwrite($out, '      <td class="text-center">' . htmlspecialchars($alarm->duration_minutes ?? '0') . '</td>' . "\n");
                    fwrite($out, '      <td class="text-center">' . htmlspecialchars(($alarm->start_speed ?? 0) . ' → ' . ($alarm->end_speed ?? 0)) . '</td>' . "\n");
                    fwrite($out, '      <td class="text-center">' . htmlspecialchars($alarm->starting_location ?? '-') . '</td>' . "\n");
                    fwrite($out, '      <td class="text-center">' . htmlspecialchars($alarm->ending_location ?? '-') . '</td>' . "\n");
                    fwrite($out, '      <td class="text-center">' . ($alarm->report_time ? date('Y-m-d H:i', strtotime($alarm->report_time)) : '-') . '</td>' . "\n");
                    fwrite($out, '    </tr>' . "\n");
                }
            },
            $metadata
        );
    }
}
