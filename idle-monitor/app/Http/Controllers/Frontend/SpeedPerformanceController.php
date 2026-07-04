<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\GpsTrack;
use App\Http\Controllers\Frontend\Traits\HasDeviceGroups;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class SpeedPerformanceController extends Controller
{
    use HasDeviceGroups;

    /**
     * Show speed performance page
     */
    public function index()
    {
        // ✅ All device sidebar data is cached for 5 minutes (trait)
        $sidebar = $this->getDeviceSidebarData();

        return view('frontend.speed-performance.index', $sidebar);
    }

    /**
     * Get Aggregated data for DataTables
     */
    public function getData(Request $request)
    {
        $query = GpsTrack::select(
                'gps_tracks.device_id',
                'devices.device_name',
                DB::raw('AVG(gps_tracks.speed) as avg_speed'),
                DB::raw('MAX(gps_tracks.speed) as max_speed')
            )
            ->leftJoin('devices', 'gps_tracks.device_id', '=', 'devices.device_id')
            ->where('gps_tracks.speed', '>', 0)
            ->groupBy('gps_tracks.device_id', 'devices.device_name');

        // Filter by specific device IDs (from tree view) - Optimized to skip when all devices are selected
        if ($request->device_ids && is_array($request->device_ids)) {
            $totalDevices = cache()->remember('total_devices_count_db', 300, function() {
                return Device::count();
            });
            if (count($request->device_ids) < $totalDevices) {
                $query->whereIn('gps_tracks.device_id', $request->device_ids);
            }
        }

        // Filter by location (via JOIN)
        if ($request->filled('location')) {
            $query->where('devices.location', $request->location);
        }

        // Filter by series (via JOIN)
        if ($request->filled('series')) {
            $seriesParam = strtoupper($request->series);
            if ($seriesParam === 'VOLVO') {
                $query->where('devices.series', 'like', '%FMX%');
            } else {
                $query->where('devices.series', $request->series);
            }
        }

        // Date and Shift Filter
        $date = $request->input('date', date('Y-m-d'));
        $shift = $request->input('shift', 'shift1');

        $startDateTime = $date . ' 00:00:00';
        $endDateTime = $date . ' 23:59:59';
        $timeLabel = $date;

        if ($shift === 'shift1') {
            $startDateTime = $date . ' 07:00:00';
            $endDateTime = $date . ' 19:00:00';
            $timeLabel = $date . "\n07:00 - 19:00\nSHIFT 1";
        } elseif ($shift === 'shift2') {
            $startDateTime = $date . ' 19:00:00';
            $endDateTime = date('Y-m-d', strtotime($date . ' +1 day')) . ' 07:00:00';
            $timeLabel = $date . "\n19:00 - 07:00\nSHIFT 2";
        } elseif ($shift === 'op_malam') {
            $startDateTime = $date . ' 18:00:00';
            $endDateTime = $date . ' 23:59:59';
            $timeLabel = $date . "\n18:00 - 23:59\nOP. MALAM";
        } elseif ($shift === 'op_dini_hari') {
            $startDateTime = $date . ' 00:00:00';
            $endDateTime = $date . ' 07:00:00';
            $timeLabel = $date . "\n00:00 - 07:00\nOP. DINI HARI";
        } elseif ($shift === 'op_pagi') {
            $startDateTime = $date . ' 07:00:00';
            $endDateTime = $date . ' 12:00:00';
            $timeLabel = $date . "\n07:00 - 12:00\nOP. PAGI";
        } elseif ($shift === 'op_siang') {
            $startDateTime = $date . ' 12:00:00';
            $endDateTime = $date . ' 18:00:00';
            $timeLabel = $date . "\n12:00 - 18:00\nOP. SIANG";
        } elseif ($shift === 'full') {
            $startDateTime = $date . ' 00:00:00';
            $endDateTime = $date . ' 23:59:59';
            $timeLabel = $date . "\n00:00 - 23:59\nFULL DAY";
        }

        $query->where('gps_tracks.gps_time', '>=', $startDateTime)
              ->where('gps_tracks.gps_time', '<=', $endDateTime);

        // Subquery for general totals (ignoring pagination, doing simple sum)
        // We do this by getting the summary from the DB directly
        $summaryQuery = clone $query;
        $summary = $summaryQuery->get();
        $overallAvg = $summary->avg('avg_speed') ?? 0;
        $overallMax = $summary->max('max_speed') ?? 0;

        return DataTables::of($query)
            ->addColumn('checkbox', function($row){
                return '<input type="checkbox" class="row-checkbox" value="' . $row->device_id . '">';
            })
            ->addColumn('time_label', function() use ($timeLabel) {
                return $timeLabel;
            })
            ->editColumn('avg_speed', function($row) {
                return round($row->avg_speed, 1);
            })
            ->editColumn('max_speed', function($row) {
                return round($row->max_speed, 1);
            })
            ->rawColumns(['checkbox'])
            ->with([
                'summaryAvg' => round($overallAvg, 1),
                'summaryMax' => round($overallMax, 1),
                'totalRecords' => $summary->count()
            ])
            ->make(true);
    }

    /**
     * Export to CSV
     */
    public function export(Request $request)
    {
        $query = GpsTrack::select(
                'gps_tracks.device_id',
                'devices.device_name',
                DB::raw('AVG(gps_tracks.speed) as avg_speed'),
                DB::raw('MAX(gps_tracks.speed) as max_speed')
            )
            ->leftJoin('devices', 'gps_tracks.device_id', '=', 'devices.device_id')
            ->where('gps_tracks.speed', '>', 0)
            ->groupBy('gps_tracks.device_id', 'devices.device_name');

        if ($request->filled('export_type') && $request->export_type === 'selected' && $request->filled('row_ids')) {
            $rowIds = explode(',', $request->row_ids);
            if (!empty($rowIds)) {
                $query->whereIn('gps_tracks.device_id', $rowIds);
            }
        } else {
            if ($request->filled('device_ids')) {
                $deviceIds = explode(',', $request->device_ids);
                if (!empty($deviceIds)) {
                    $totalDevices = cache()->remember('total_devices_count_db', 300, function() {
                        return Device::count();
                    });
                    if (count($deviceIds) < $totalDevices) {
                        $query->whereIn('gps_tracks.device_id', $deviceIds);
                    }
                }
            }
        }

        if ($request->filled('location')) {
            $query->where('devices.location', $request->location);
        }

        if ($request->filled('series')) {
            $seriesParam = strtoupper($request->series);
            if ($seriesParam === 'VOLVO') {
                $query->where('devices.series', 'like', '%FMX%');
            } else {
                $query->where('devices.series', $request->series);
            }
        }

        $date = $request->input('date', date('Y-m-d'));
        $shift = $request->input('shift', 'shift1');

        $startDateTime = $date . ' 00:00:00';
        $endDateTime = $date . ' 23:59:59';
        $timeLabel = $date;

        if ($shift === 'shift1') {
            $startDateTime = $date . ' 07:00:00';
            $endDateTime = $date . ' 19:00:00';
            $timeLabel = "Shift 1 (07:00 - 19:00)";
        } elseif ($shift === 'shift2') {
            $startDateTime = $date . ' 19:00:00';
            $endDateTime = date('Y-m-d', strtotime($date . ' +1 day')) . ' 07:00:00';
            $timeLabel = "Shift 2 (19:00 - 07:00)";
        } elseif ($shift === 'op_malam') {
            $startDateTime = $date . ' 18:00:00';
            $endDateTime = $date . ' 23:59:59';
            $timeLabel = "Operasional Malam (18:00 - 23:59)";
        } elseif ($shift === 'op_dini_hari') {
            $startDateTime = $date . ' 00:00:00';
            $endDateTime = $date . ' 07:00:00';
            $timeLabel = "Operasional Dini Hari (00:00 - 07:00)";
        } elseif ($shift === 'op_pagi') {
            $startDateTime = $date . ' 07:00:00';
            $endDateTime = $date . ' 12:00:00';
            $timeLabel = "Operasional Pagi (07:00 - 12:00)";
        } elseif ($shift === 'op_siang') {
            $startDateTime = $date . ' 12:00:00';
            $endDateTime = $date . ' 18:00:00';
            $timeLabel = "Operasional Siang (12:00 - 18:00)";
        } elseif ($shift === 'full') {
            $timeLabel = "Full Day (00:00 - 23:59)";
        }

        $query->where('gps_tracks.gps_time', '>=', $startDateTime)
              ->where('gps_tracks.gps_time', '<=', $endDateTime);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['use_queue' => false]);
        }

        return response()->streamDownload(function () use ($query, $date, $timeLabel) {
            $out = fopen('php://output', 'w');
            
            fputcsv($out, [
                '#', 'DEVICE NAME', 'WAKTU', 'AVG SPEED (Km/h)', 'MAX SPEED (Km/h)'
            ], ';');

            $serial = 1;
            foreach ($query->cursor() as $row) {
                $deviceName = ($row->device_name ?? '-') . "\n" . $row->device_id;
                
                fputcsv($out, [
                    $serial++,
                    $deviceName,
                    $date . " " . $timeLabel,
                    round($row->avg_speed, 1),
                    round($row->max_speed, 1)
                ], ';');
            }
            fclose($out);
        }, 'export-speed-performance-' . date('Y-m-d_H-i-s') . '.csv');
    }
}
