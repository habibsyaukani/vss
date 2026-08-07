<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\GpsTrack;
use App\Http\Controllers\Frontend\Traits\HasDeviceGroups;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use App\Services\ExcelExportService;

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
                DB::raw('AVG(gps_tracks.speed) as avg_speed'),
                DB::raw('MAX(gps_tracks.speed) as max_speed')
            )
            ->from(\Illuminate\Support\Facades\DB::raw('gps_tracks FORCE INDEX (idx_time_speed)'))
            ->where('gps_tracks.speed', '>', 0)
            ->groupBy('gps_tracks.device_id');

        // Filter by specific device IDs
        if ($request->device_ids && is_array($request->device_ids)) {
            $totalDevices = cache()->remember('total_devices_count_db', 300, function() {
                return Device::count();
            });
            if (count($request->device_ids) < $totalDevices) {
                $searchIds = [];
                foreach ($request->device_ids as $id) {
                    $searchIds[] = (string)$id;
                    $searchIds[] = ltrim((string)$id, '0');
                    $searchIds[] = '0' . ltrim((string)$id, '0');
                }
                $query->whereIn('gps_tracks.device_id', array_unique($searchIds));
            }
        }

        // Filter by location or series (Without heavy JOIN)
        if ($request->filled('location') || $request->filled('series')) {
            $deviceQuery = \App\Models\Device::query();
            
            if ($request->filled('location')) {
                $deviceQuery->where('location', $request->location);
            }
            if ($request->filled('series')) {
                $seriesParam = strtoupper($request->series);
                if ($seriesParam === 'VOLVO') {
                    $deviceQuery->where('series', 'like', '%FMX%');
                } else {
                    $deviceQuery->where('series', $request->series);
                }
            }
            
            $matchedIds = $deviceQuery->pluck('device_id')->toArray();
            
            $searchIds = [];
            foreach ($matchedIds as $id) {
                $searchIds[] = (string)$id;
                $searchIds[] = ltrim((string)$id, '0');
                $searchIds[] = '0' . ltrim((string)$id, '0');
            }
            $searchIds = array_unique($searchIds);
            
            // If no devices match the filter, force an empty result safely
            if (empty($searchIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('gps_tracks.device_id', $searchIds);
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

        $devicesMap = cache()->remember('devices_map_fast', 300, function() {
            $all = \App\Models\Device::all();
            $map = [];
            foreach ($all as $d) {
                $map[ltrim($d->device_id, '0')] = $d;
            }
            return $map;
        });

        return DataTables::of($query)
            ->addColumn('checkbox', function($row){
                return '<input type="checkbox" class="row-checkbox" value="' . $row->device_id . '">';
            })
            ->addColumn('time_label', function() use ($timeLabel) {
                return $timeLabel;
            })
            ->addColumn('device_name', function ($track) use ($devicesMap) {
                $deviceInfo = $devicesMap[$track->device_id] ?? null;
                return htmlspecialchars($deviceInfo->device_name ?? '-');
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
     * Export to Excel (.xls)
     */
    public function export(Request $request)
    {
        $query = GpsTrack::select(
                'gps_tracks.device_id',
                DB::raw('AVG(gps_tracks.speed) as avg_speed'),
                DB::raw('MAX(gps_tracks.speed) as max_speed')
            )
            ->where('gps_tracks.speed', '>', 0)
            ->groupBy('gps_tracks.device_id');

        $isExportSelected = false;
        if ($request->filled('export_type') && $request->export_type === 'selected' && $request->filled('row_ids')) {
            $rowIds = explode(',', $request->row_ids);
            if (!empty($rowIds)) {
                $query->whereIn('gps_tracks.device_id', $rowIds);
                $isExportSelected = true;
            }
        } else {
            if ($request->filled('device_ids')) {
                $deviceIds = explode(',', $request->device_ids);
                $totalDevices = cache()->remember('total_devices_count_db', 300, function() {
                    return Device::count();
                });
                if (count($deviceIds) < $totalDevices) {
                    $searchIds = [];
                    foreach ($deviceIds as $id) {
                        $searchIds[] = (string)$id;
                        $searchIds[] = ltrim((string)$id, '0');
                        $searchIds[] = '0' . ltrim((string)$id, '0');
                    }
                    $query->whereIn('gps_tracks.device_id', array_unique($searchIds));
                }
            }

            // Filter by location or series (Without heavy JOIN)
            if ($request->filled('location') || $request->filled('series')) {
                $deviceQuery = \App\Models\Device::query();
                
                if ($request->filled('location')) {
                    $deviceQuery->where('location', $request->location);
                }
                if ($request->filled('series')) {
                    $seriesParam = strtoupper($request->series);
                    if ($seriesParam === 'VOLVO') {
                        $deviceQuery->where('series', 'like', '%FMX%');
                    } else {
                        $deviceQuery->where('series', $request->series);
                    }
                }
                
                $matchedIds = $deviceQuery->pluck('device_id')->toArray();
                
                $searchIds = [];
                foreach ($matchedIds as $id) {
                    $searchIds[] = (string)$id;
                    $searchIds[] = ltrim((string)$id, '0');
                    $searchIds[] = '0' . ltrim((string)$id, '0');
                }
                $searchIds = array_unique($searchIds);
                
                if (empty($searchIds)) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereIn('gps_tracks.device_id', $searchIds);
                }
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

        $metadata = [
            'Mode Export' => $isExportSelected ? 'Selected Rows' : 'All Filtered Rows',
            'Tanggal' => $date,
            'Shift / Periode' => $timeLabel,
            'Location' => $request->location ?? 'Semua',
            'Series' => $request->series ?? 'Semua',
        ];

        $headers = [
            ['label' => 'NO', 'align' => 'center'],
            ['label' => 'DEVICE ID', 'align' => 'center'],
            ['label' => 'DEVICE NAME', 'align' => 'left'],
            ['label' => 'PERIODE / WAKTU', 'align' => 'center'],
            ['label' => 'AVG SPEED (KM/H)', 'align' => 'right'],
            ['label' => 'MAX SPEED (KM/H)', 'align' => 'right'],
        ];

        $devicesMap = cache()->remember('devices_map_fast', 300, function() {
            $all = \App\Models\Device::all();
            $map = [];
            foreach ($all as $d) {
                $map[ltrim($d->device_id, '0')] = $d;
            }
            return $map;
        });

        return ExcelExportService::streamXls(
            'export-speed-performance-' . date('Y-m-d_H-i-s') . '.xls',
            'SPEED PERFORMANCE REPORT',
            $headers,
            function ($out) use ($query, $date, $timeLabel, $devicesMap) {
                $serial = 1;
                foreach ($query->cursor() as $row) {
                    $rowClass = ($serial % 2 === 0) ? 'row-even' : 'row-odd';
                    $avgSpd = round($row->avg_speed, 1);
                    $maxSpd = round($row->max_speed, 1);
                    
                    $deviceInfo = $devicesMap[$row->device_id] ?? null;
                    $deviceName = $deviceInfo->device_name ?? '-';

                    fwrite($out, '    <tr class="' . $rowClass . '">' . "\n");
                    fwrite($out, '      <td class="text-center">' . $serial++ . '</td>' . "\n");
                    fwrite($out, '      <td class="text-center">' . htmlspecialchars($row->device_id ?? '-') . '</td>' . "\n");
                    fwrite($out, '      <td class="text-left">' . htmlspecialchars($deviceName) . '</td>' . "\n");
                    fwrite($out, '      <td class="text-center">' . htmlspecialchars($date . ' ' . $timeLabel) . '</td>' . "\n");
                    fwrite($out, '      <td class="text-right">' . number_format($avgSpd, 1) . ' Km/h</td>' . "\n");
                    fwrite($out, '      <td class="text-right">' . number_format($maxSpd, 1) . ' Km/h</td>' . "\n");
                    fwrite($out, '    </tr>' . "\n");
                }
            },
            $metadata
        );
    }
}
