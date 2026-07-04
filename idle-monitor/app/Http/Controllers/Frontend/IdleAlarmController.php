<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\IdleAlarm;
use App\Http\Controllers\Frontend\Traits\HasDeviceGroups;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

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
        // ✅ OPTIMIZED: Use JOIN instead of whereHas for better performance
        $query = IdleAlarm::select('idle_alarms.*')
            ->leftJoin('devices', 'idle_alarms.device_id', '=', 'devices.device_id');

        // Filter by status
        if ($request->status) {
            $query->where('idle_alarms.alarm_status', $request->status);
        }

        // Filter by location (direct JOIN filter - much faster)
        if ($request->location) {
            $query->where('devices.location', $request->location);
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
        if ($request->device_ids && is_array($request->device_ids)) {
            $totalDevices = cache()->remember('total_devices_count_db', 300, function() {
                return \App\Models\Device::count();
            });
            if (count($request->device_ids) < $totalDevices) {
                $query->whereIn('idle_alarms.device_id', $request->device_ids);
            }
        }

        // Filter by group (if entire group selected but not individual devices, fallback logic) - optimized
        if ($request->groups && is_array($request->groups)) {
            $query->whereIn('devices.group_name', $request->groups);
        }

        // Filter by date range
        if ($request->start_date) {
            $query->whereDate('idle_alarms.starting_time', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->whereDate('idle_alarms.starting_time', '<=', $request->end_date);
        }

        // Filter by duration range — pakai kalkulasi real dari starting_time → ending_time
        // agar konsisten dengan tampilan di tabel (bukan dari kolom duration_seconds yg mungkin stale)
        if ($request->duration_range) {
            // Hitung durasi real: TIMESTAMPDIFF(SECOND, starting_time, IFNULL(ending_time, NOW()))
            $durExpr = 'TIMESTAMPDIFF(SECOND, idle_alarms.starting_time, IFNULL(idle_alarms.ending_time, NOW()))';

            switch ($request->duration_range) {
                case 'lt5':
                    // Hijau: 0–299 detik (< 5 menit)
                    $query->whereRaw("{$durExpr} < 300");
                    break;
                case '5to15':
                    // Kuning: 300–899 detik (5:00–14:59)
                    $query->whereRaw("{$durExpr} >= 300")->whereRaw("{$durExpr} < 900");
                    break;
                case '15to30':
                    // Oranye: 900–1799 detik (15:00–29:59)
                    $query->whereRaw("{$durExpr} >= 900")->whereRaw("{$durExpr} < 1800");
                    break;
                case 'gt30':
                    // Merah: 1800+ detik (≥ 30 menit)
                    $query->whereRaw("{$durExpr} >= 1800");
                    break;
            }
        }

        // ✅ OPTIMIZATION: Get matching device IDs for sidebar sync (simplified)
        $matchingDeviceIds = (clone $query)->distinct()->pluck('idle_alarms.device_id')->toArray();

        return DataTables::of($query)
            ->editColumn('starting_time', function ($alarm) {
                return $alarm->starting_time ? date('Y-m-d H:i:s', strtotime($alarm->starting_time)) : '-';
            })
            ->editColumn('ending_time', function ($alarm) {
                return $alarm->ending_time ? date('Y-m-d H:i:s', strtotime($alarm->ending_time)) : '-';
            })
            ->editColumn('report_time', function ($alarm) {
                return $alarm->report_time ? date('Y-m-d H:i:s', strtotime($alarm->report_time)) : '-';
            })
            ->addColumn('alarm_type', function ($alarm) {
                return 'Idle';
            })
            ->addColumn('duration_formatted', function ($alarm) {
                if (!$alarm->starting_time) return '-';

                $start = \Carbon\Carbon::parse($alarm->starting_time);
                $end   = $alarm->ending_time
                            ? \Carbon\Carbon::parse($alarm->ending_time)
                            : now();

                $totalSeconds = max(0, $end->diffInSeconds($start));

                return "{$totalSeconds} detik";
            })
            ->addColumn('duration_seconds_calc', function ($alarm) {
                if (!$alarm->starting_time) return 0;
                $start = \Carbon\Carbon::parse($alarm->starting_time);
                $end   = $alarm->ending_time
                            ? \Carbon\Carbon::parse($alarm->ending_time)
                            : now();
                return max(0, $end->diffInSeconds($start));
            })
            ->addColumn('actions', function ($alarm) {
                return '<a href="' . route('frontend.idle-alarm.show', $alarm->id) . '" class="btn btn-sm btn-info" title="View Details"><i class="fas fa-eye"></i></a>';
            })
            ->rawColumns(['actions'])
            ->with('matching_device_ids', $matchingDeviceIds)
            ->make(true);
    }

    /**
     * Show alarm detail (read-only)
     */
    public function show(IdleAlarm $idleAlarm)
    {
        return view('frontend.idle-alarm.show', compact('idleAlarm'));
    }

    /**
     * Export idle alarms to CSV (read-only)
     */
    public function export(Request $request)
    {
        $query = IdleAlarm::with('device');

        // Export Selected Rows
        if ($request->selected_ids && is_array($request->selected_ids)) {
            $query->whereIn('id', $request->selected_ids);
        } else {
            // Apply sidebar and top filters
            if ($request->location) {
                $query->whereHas('device', function($q) use ($request) {
                    $q->where('location', $request->location);
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
            if ($request->device_ids && is_array($request->device_ids)) {
                $totalDevices = cache()->remember('total_devices_count_db', 300, function() {
                    return \App\Models\Device::count();
                });
                if (count($request->device_ids) < $totalDevices) {
                    $query->whereIn('device_id', $request->device_ids);
                }
            }
            if ($request->start_date) {
                $query->whereDate('starting_time', '>=', $request->start_date);
            }
            if ($request->end_date) {
                $query->whereDate('starting_time', '<=', $request->end_date);
            }
            if ($request->duration_range) {
                switch ($request->duration_range) {
                    case 'lt5':
                        // Green: 0-299 seconds (< 5 min)
                        $query->where('duration_seconds', '<', 300);
                        break;
                    case '5to15':
                        // Yellow: 300-899 seconds (5:00-14:59)
                        $query->where('duration_seconds', '>=', 300)->where('duration_seconds', '<', 900);
                        break;
                    case '15to30':
                        // Orange: 900-1799 seconds (15:00-29:59)
                        $query->where('duration_seconds', '>=', 900)->where('duration_seconds', '<', 1800);
                        break;
                    case 'gt30':
                        // Red: 1800+ seconds (30:00+)
                        $query->where('duration_seconds', '>=', 1800);
                        break;
                }
            }
        }

        // Jika request via AJAX, arahkan langsung untuk download tanpa queue
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['use_queue' => false]);
        }

        // Synchronous Export dengan Stream (Sangat Cepat & Hemat Memory)
        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            
            // Header CSV
            fputcsv($out, [
                'device_id', 'device_name', 'alarm_type', 'alarm_status',
                'starting_time', 'starting_location', 'ending_time', 'ending_location',
                'start_detail', 'end_detail', 'start_speed', 'end_speed',
                'report_time', 'duration_seconds'
            ], ';');

            // Gunakan cursor() agar tidak meload ribuan data ke memory sekaligus
            foreach ($query->cursor() as $alarm) {
                fputcsv($out, [
                    $alarm->device_id,
                    $alarm->device_name,
                    'Idle',
                    $alarm->alarm_status,
                    $alarm->starting_time ? date('Y-m-d H:i:s', strtotime($alarm->starting_time)) : '-',
                    $alarm->starting_location,
                    $alarm->ending_time ? date('Y-m-d H:i:s', strtotime($alarm->ending_time)) : '-',
                    $alarm->ending_location,
                    $alarm->start_detail,
                    $alarm->end_detail,
                    $alarm->start_speed,
                    $alarm->end_speed,
                    $alarm->report_time ? date('Y-m-d H:i:s', strtotime($alarm->report_time)) : '-',
                    $alarm->duration_formatted
                ], ';');
            }
            fclose($out);
        }, 'export-idle-alarms-' . date('Y-m-d_H-i-s') . '.csv');
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
