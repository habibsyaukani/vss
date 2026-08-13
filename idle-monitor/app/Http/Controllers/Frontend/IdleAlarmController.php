<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AlarmRaw;
use App\Http\Controllers\Frontend\Traits\HasDeviceGroups;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

use App\Services\ExcelExportService;

class IdleAlarmController extends Controller
{
    use HasDeviceGroups;

    /**
     * Show idle alarms list (read-only for fleet manager)
     */
    public function index()
    {
        // ✅ All device sidebar data is cached for 5 minutes (trait)
        $sidebar = $this->getDeviceSidebarData();

        return view('frontend.idle-alarm.index', $sidebar);
    }

    /**
     * Get idle alarms data for DataTable (AJAX) - Read-only
     */
    public function data(Request $request)
    {
        // ✅ RELEASE SESSION LOCK EARLY!
        session()->save();

        // ✅ OPTIMIZED: Use JOIN instead of whereHas for better performance
        $query = AlarmRaw::select(
                'alarm_raw.*',
                'alarm_raw.start_time as starting_time',
                'alarm_raw.end_time as ending_time',
                'alarm_raw.start_gps as starting_location',
                'alarm_raw.end_gps as ending_location'
            )
            ->leftJoin('devices', 'alarm_raw.device_id', '=', 'devices.device_id')
            ->where('alarm_raw.alarm_type', 32)
            ->where('alarm_raw.alarm_state', 0)
            ->whereNotNull('alarm_raw.end_time');

        // Filter by duration range — pakai kalkulasi real dari start_time → end_time
        if ($request->duration_range) {
            $durExpr = 'TIMESTAMPDIFF(SECOND, alarm_raw.start_time, IFNULL(alarm_raw.end_time, NOW()))';

            switch ($request->duration_range) {
                case 'lt5':
                    $query->whereRaw("{$durExpr} > 0")->whereRaw("{$durExpr} < 300");
                    break;
                case '5to15':
                    $query->whereRaw("{$durExpr} >= 300")->whereRaw("{$durExpr} < 900");
                    break;
                case '15to30':
                    $query->whereRaw("{$durExpr} >= 900")->whereRaw("{$durExpr} < 1800");
                    break;
                case 'gt30':
                    $query->whereRaw("{$durExpr} >= 1800");
                    break;
            }
        }


        // Filter by status (we don't have alarm_status in raw, we assume CLOSED/ALARM_END since state is 0)
        // Kept for signature compatibility but essentially ignored since alarm_state = 0 implies ALARM_END
        if ($request->status) {
            // $query->where('idle_alarms.alarm_status', $request->status);
        }

        // Filter by location (direct JOIN filter - much faster)
        if ($request->location) {
            $query->where('devices.lokasi', $request->location);
        }

        // Filter by series (direct JOIN filter - much faster)
        if ($request->series) {
            if (strtoupper($request->series) === 'VOLVO') {
                $query->where('devices.series', 'LIKE', '%FMX%');
            } else {
                $query->where('devices.series', $request->series);
            }
        }

        // Filter by specific devices (from tree view) - Optimized to skip when all devices are selected
        if ($request->has('device_ids')) {
            if (empty($request->device_ids)) {
                // If device_ids is passed but empty (no devices checked), return 0 records
                $query->whereRaw('1 = 0');
            } else {
                $totalDevices = cache()->remember('total_devices_count_db', 300, function() {
                    return \App\Models\Device::count();
                });
                if (count($request->device_ids) < $totalDevices) {
                    $cleanIds = array_map(function($id) { return ltrim((string)$id, '0'); }, $request->device_ids);
                    $query->whereIn('alarm_raw.device_id', $cleanIds);
                }
            }
        }

        // Filter by group (if entire group selected but not individual devices, fallback logic) - optimized
        if ($request->groups && is_array($request->groups)) {
            $query->whereIn('devices.group_name', $request->groups);
        }

        // Filter by date range
        if ($request->start_date) {
            $start = $request->start_date . ' 00:00:00';
            $query->where('alarm_raw.start_time', '>=', $start);
        }
        if ($request->end_date) {
            $end = $request->end_date . ' 23:59:59';
            $query->where('alarm_raw.start_time', '<=', $end);
        }

        return DataTables::of($query)
            ->editColumn('starting_time', function ($alarm) {
                return $alarm->start_time ? date('Y-m-d H:i:s', strtotime($alarm->start_time)) : '-';
            })
            ->editColumn('ending_time', function ($alarm) {
                return $alarm->end_time ? date('Y-m-d H:i:s', strtotime($alarm->end_time)) : '-';
            })
            ->editColumn('report_time', function ($alarm) {
                return $alarm->report_time ? date('Y-m-d H:i:s', strtotime($alarm->report_time)) : '-';
            })
            ->addColumn('alarm_type', function ($alarm) {
                return 'Idle';
            })
            ->addColumn('duration_formatted', function ($alarm) {
                if (!$alarm->start_time) return '-';

                $start = \Carbon\Carbon::parse($alarm->start_time);
                $end   = $alarm->end_time
                            ? \Carbon\Carbon::parse($alarm->end_time)
                            : now();

                $totalSeconds = max(0, $end->diffInSeconds($start));

                return "{$totalSeconds} detik";
            })
            ->addColumn('duration_seconds_calc', function ($alarm) {
                if (!$alarm->start_time) return 0;
                $start = \Carbon\Carbon::parse($alarm->start_time);
                $end   = $alarm->end_time
                            ? \Carbon\Carbon::parse($alarm->end_time)
                            : now();
                return max(0, $end->diffInSeconds($start));
            })
            ->addColumn('actions', function ($alarm) {
                return '<a href="' . route('frontend.idle-alarm.show', $alarm->id) . '" class="btn btn-sm btn-info" title="View Details"><i class="fas fa-eye"></i></a>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Show alarm detail (read-only)
     */
    public function show($id)
    {
        $idleAlarm = AlarmRaw::findOrFail($id);
        
        // Map raw object properties to simulate IdleAlarm structure for the view
        $idleAlarm->starting_time = $idleAlarm->start_time;
        $idleAlarm->ending_time = $idleAlarm->end_time;
        $idleAlarm->starting_location = $idleAlarm->start_gps;
        $idleAlarm->ending_location = $idleAlarm->end_gps;
        $idleAlarm->alarm_status = 'ALARM_END';
        
        if ($idleAlarm->start_time && $idleAlarm->end_time) {
            $idleAlarm->duration_minutes = ceil($idleAlarm->end_time->diffInSeconds($idleAlarm->start_time) / 60);
        } else {
            $idleAlarm->duration_minutes = 0;
        }

        return view('frontend.idle-alarm.show', compact('idleAlarm'));
    }

    /**
     * Export idle alarms to Excel (.xls)
     */
    public function export(Request $request)
    {
        $query = AlarmRaw::with('device')
            ->where('alarm_type', 32)
            ->where('alarm_state', 0)
            ->whereNotNull('end_time');

        // Export Selected Rows
        if ($request->selected_ids && is_array($request->selected_ids)) {
            $query->whereIn('id', $request->selected_ids);
        } else {
            // Apply sidebar and top filters
            if ($request->location) {
                $query->whereHas('device', function($q) use ($request) {
                    $q->where('lokasi', $request->location);
                });
            }
            if ($request->series) {
                $query->whereHas('device', function($q) use ($request) {
                    if (strtoupper($request->series) === 'VOLVO') {
                        $q->where('series', 'LIKE', '%FMX%');
                    } else {
                        $q->where('series', $request->series);
                    }
                });
            }
            if ($request->has('device_ids')) {
                if (empty($request->device_ids)) {
                    $query->whereRaw('1 = 0');
                } else {
                    $totalDevices = cache()->remember('total_devices_count_db', 300, function() {
                        return \App\Models\Device::count();
                    });
                    if (count($request->device_ids) < $totalDevices) {
                        $cleanIds = array_map(function($id) { return ltrim((string)$id, '0'); }, $request->device_ids);
                        $query->whereIn('device_id', $cleanIds);
                    }
                }
            }
            if ($request->start_date) {
                $start = $request->start_date . ' 00:00:00';
                $query->where('start_time', '>=', $start);
            }
            if ($request->end_date) {
                $end = $request->end_date . ' 23:59:59';
                $query->where('start_time', '<=', $end);
            }
            if ($request->duration_range) {
                $durExpr = 'TIMESTAMPDIFF(SECOND, alarm_raw.start_time, IFNULL(alarm_raw.end_time, NOW()))';
                switch ($request->duration_range) {
                    case 'lt5':
                        $query->whereRaw("{$durExpr} > 0")->whereRaw("{$durExpr} < 300");
                        break;
                    case '5to15':
                        $query->whereRaw("{$durExpr} >= 300")->whereRaw("{$durExpr} < 900");
                        break;
                    case '15to30':
                        $query->whereRaw("{$durExpr} >= 900")->whereRaw("{$durExpr} < 1800");
                        break;
                    case 'gt30':
                        $query->whereRaw("{$durExpr} >= 1800");
                        break;
                }
            }
        }

        // Jika request via AJAX, arahkan langsung untuk download tanpa queue
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['use_queue' => false]);
        }

        $metadata = [
            'Mode Export' => ($request->selected_ids && is_array($request->selected_ids)) ? 'Selected Rows (' . count($request->selected_ids) . ' items)' : 'All Filtered Rows',
            'Start Date' => $request->start_date ?? '-',
            'End Date' => $request->end_date ?? '-',
            'Location' => $request->location ?? 'Semua',
            'Series' => $request->series ?? 'Semua',
            'Durasi Filter' => $request->duration_range ?? 'Semua',
        ];

        $headers = [
            ['label' => 'NO', 'align' => 'center'],
            ['label' => 'DEVICE ID', 'align' => 'center'],
            ['label' => 'DEVICE NAME', 'align' => 'left'],
            ['label' => 'ALARM TYPE', 'align' => 'center'],
            ['label' => 'STATUS', 'align' => 'center'],
            ['label' => 'START TIME', 'align' => 'center'],
            ['label' => 'START LOCATION', 'align' => 'center'],
            ['label' => 'END TIME', 'align' => 'center'],
            ['label' => 'END LOCATION', 'align' => 'center'],
            ['label' => 'START DETAIL', 'align' => 'left'],
            ['label' => 'END DETAIL', 'align' => 'left'],
            ['label' => 'START SPEED', 'align' => 'right'],
            ['label' => 'END SPEED', 'align' => 'right'],
            ['label' => 'REPORT TIME', 'align' => 'center'],
            ['label' => 'DURATION', 'align' => 'center'],
        ];

        return ExcelExportService::streamXls(
            'export-idle-alarms-' . date('Y-m-d_H-i-s') . '.xls',
            'IDLE ALARM MONITORING REPORT',
            $headers,
            function ($out) use ($query) {
                $serial = 1;
                foreach ($query->cursor() as $alarm) {
                    $rowClass = ($serial % 2 === 0) ? 'row-even' : 'row-odd';
                    
                    $start = \Carbon\Carbon::parse($alarm->start_time);
                    $end   = $alarm->end_time ? \Carbon\Carbon::parse($alarm->end_time) : now();
                    $durationSecs = max(0, $end->diffInSeconds($start));

                    $durBadgeClass = 'text-center';
                    if ($durationSecs > 0 && $durationSecs < 300) {
                        $durBadgeClass = 'badge-success';
                    } elseif ($durationSecs < 900) {
                        $durBadgeClass = 'badge-warning';
                    } elseif ($durationSecs < 1800) {
                        $durBadgeClass = 'badge-orange';
                    } elseif ($durationSecs >= 1800) {
                        $durBadgeClass = 'badge-danger';
                    }

                    $statusClass = 'badge-success'; // ALARM_END
                    $alarmStatus = 'ALARM_END';

                    $alarmTypeDisplay = 'Idle';

                    fwrite($out, '    <tr class="' . $rowClass . '">' . "\n");
                    fwrite($out, '      <td class="text-center">' . $serial++ . '</td>' . "\n");
                    fwrite($out, '      <td class="text-center">' . htmlspecialchars($alarm->device_id ?? '-') . '</td>' . "\n");
                    fwrite($out, '      <td class="text-left">' . htmlspecialchars($alarm->device_name ?? '-') . '</td>' . "\n");
                    fwrite($out, '      <td class="text-center">' . htmlspecialchars($alarmTypeDisplay) . '</td>' . "\n");
                    fwrite($out, '      <td class="' . $statusClass . '">' . htmlspecialchars($alarmStatus) . '</td>' . "\n");
                    fwrite($out, '      <td class="text-center">' . ($alarm->start_time ? date('Y-m-d H:i:s', strtotime($alarm->start_time)) : '-') . '</td>' . "\n");
                    fwrite($out, '      <td class="text-center">' . htmlspecialchars($alarm->start_gps ?? '-') . '</td>' . "\n");
                    fwrite($out, '      <td class="text-center">' . ($alarm->end_time ? date('Y-m-d H:i:s', strtotime($alarm->end_time)) : '-') . '</td>' . "\n");
                    fwrite($out, '      <td class="text-center">' . htmlspecialchars($alarm->end_gps ?? '-') . '</td>' . "\n");
                    fwrite($out, '      <td class="text-left">' . htmlspecialchars($alarm->start_detail ?? '-') . '</td>' . "\n");
                    fwrite($out, '      <td class="text-left">' . htmlspecialchars($alarm->end_detail ?? '-') . '</td>' . "\n");
                    fwrite($out, '      <td class="text-right">' . htmlspecialchars(($alarm->start_speed ?? 0) . ' km/h') . '</td>' . "\n");
                    fwrite($out, '      <td class="text-right">' . htmlspecialchars(($alarm->end_speed ?? 0) . ' km/h') . '</td>' . "\n");
                    fwrite($out, '      <td class="text-center">' . ($alarm->report_time ? date('Y-m-d H:i:s', strtotime($alarm->report_time)) : '-') . '</td>' . "\n");
                    fwrite($out, '      <td class="' . $durBadgeClass . '">' . htmlspecialchars("{$durationSecs} detik") . '</td>' . "\n");
                    fwrite($out, '    </tr>' . "\n");
                }
            },
            $metadata
        );
    }

    /**
     * Check export job status
     */
    public function checkExportStatus($jobId)
    {
        $job = \App\Models\ExportJob::find($jobId);
        if (!$job) return response()->json(['status' => 'failed']);
        
        return response()->json([
            'status' => $job->status,
            'download_url' => $job->status === 'completed' ? route('frontend.idle-alarm.download-export', $job->id) : null
        ]);
    }

    /**
     * Download completed export file
     */
    public function downloadExport($jobId)
    {
        $job = \App\Models\ExportJob::findOrFail($jobId);
        if ($job->status !== 'completed' || !$job->file_path) {
            abort(404);
        }
        
        $fullPath = storage_path('app/' . $job->file_path);
        if (!file_exists($fullPath)) {
            abort(404);
        }

        return response()->download($fullPath)->deleteFileAfterSend(true);
    }
}
