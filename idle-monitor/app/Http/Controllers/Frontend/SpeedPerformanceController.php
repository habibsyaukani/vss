<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\GpsTrackRaw;
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
        session()->save();

        $deviceMap = cache()->remember('devices_map_by_id_dict', 300, function() {
            return Device::all()->keyBy(function($item) {
                return (string) $item->device_id;
            });
        });

        $query = \App\Models\GpsHourlyStat::select(
                'device_id',
                'device_name',
                DB::raw('CASE WHEN SUM(total_records) > 0 THEN SUM(sum_speed) / SUM(total_records) ELSE 0 END as avg_speed'),
                DB::raw('MAX(max_speed) as max_speed')
            )
            ->where('total_records', '>', 0)
            ->groupBy('device_id', 'device_name');

        // Filter by specific device IDs (from tree view)
        if ($request->device_ids && is_array($request->device_ids)) {
            $totalDevices = count($deviceMap);
            if (count($request->device_ids) < $totalDevices) {
                $cleanIds = array_map(function($id) { return ltrim((string)$id, '0'); }, $request->device_ids);
                $query->whereIn('gps_tracks_raw.device_id', $cleanIds);
            }
        }

        // Filter by location or series (in-memory lookup)
        if ($request->filled('location') || $request->filled('series')) {
            $filteredDevices = $deviceMap;
            if ($request->filled('location')) {
                $loc = trim(strtoupper($request->location));
                $filteredDevices = $filteredDevices->filter(function($d) use ($loc) {
                    $dLoc = strtoupper($d->location ?? '');
                    $dLok = strtoupper($d->lokasi ?? '');
                    return str_contains($dLoc, $loc) || str_contains($dLok, $loc);
                });
            }
            if ($request->filled('series')) {
                $series = trim(strtoupper($request->series));
                if ($series === 'VOLVO' || $series === 'DT VOLVO') {
                    $filteredDevices = $filteredDevices->filter(function($d) {
                        return stripos($d->series, 'FMX') !== false || stripos($d->series, 'VOLVO') !== false;
                    });
                } else {
                    $filteredDevices = $filteredDevices->filter(function($d) use ($series) {
                        return stripos($d->series, $series) !== false;
                    });
                }
            }
            $query->whereIn('gps_tracks_raw.device_id', $filteredDevices->pluck('device_id')->toArray());
        }

        // Date and Shift Filter
        $date = $request->input('date', date('Y-m-d'));
        $shift = $request->input('shift', 'shift1');

        if ($shift === 'shift1') {
            $query->where('record_date', $date)->whereBetween('record_hour', [7, 18]);
            $timeLabel = $date . "\n07:00 - 19:00\nSHIFT 1";
        } elseif ($shift === 'shift2') {
            $nextDate = date('Y-m-d', strtotime($date . ' +1 day'));
            $query->where(function($q) use ($date, $nextDate) {
                $q->where(function($q1) use ($date) {
                    $q1->where('record_date', $date)->where('record_hour', '>=', 19);
                })->orWhere(function($q2) use ($nextDate) {
                    $q2->where('record_date', $nextDate)->where('record_hour', '<=', 6);
                });
            });
            $timeLabel = $date . "\n19:00 - 07:00\nSHIFT 2";
        } elseif ($shift === 'op_malam') {
            $query->where('record_date', $date)->whereBetween('record_hour', [18, 23]);
            $timeLabel = $date . "\n18:00 - 23:59\nOP. MALAM";
        } elseif ($shift === 'op_dini_hari') {
            $query->where('record_date', $date)->whereBetween('record_hour', [0, 6]);
            $timeLabel = $date . "\n00:00 - 07:00\nOP. DINI HARI";
        } elseif ($shift === 'op_pagi') {
            $query->where('record_date', $date)->whereBetween('record_hour', [7, 11]);
            $timeLabel = $date . "\n07:00 - 12:00\nOP. PAGI";
        } elseif ($shift === 'op_siang') {
            $query->where('record_date', $date)->whereBetween('record_hour', [12, 17]);
            $timeLabel = $date . "\n12:00 - 18:00\nOP. SIANG";
        } else {
            $query->where('record_date', $date);
            $timeLabel = $date . "\n00:00 - 23:59\nFULL DAY";
        }

        $summaryQuery = clone $query;
        $summary = $summaryQuery->get();
        $overallAvg = $summary->avg('avg_speed') ?? 0;
        $overallMax = $summary->max('max_speed') ?? 0;

        return DataTables::of($query)
            ->addColumn('checkbox', function($row){
                return '<input type="checkbox" class="row-checkbox" value="' . $row->device_id . '">';
            })
            ->editColumn('device_name', function($row) use ($deviceMap) {
                $master = $deviceMap->get((string)$row->device_id);
                return $row->device_name ?: ($master ? $master->device_name : null);
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
     * Export to Excel (.xls)
     */
    public function export(Request $request)
    {
        $deviceMap = cache()->remember('devices_map_by_id_dict', 300, function() {
            return Device::all()->keyBy(function($item) {
                return (string) $item->device_id;
            });
        });

        $query = \App\Models\GpsHourlyStat::select(
                'device_id',
                'device_name',
                DB::raw('CASE WHEN SUM(total_records) > 0 THEN SUM(sum_speed) / SUM(total_records) ELSE 0 END as avg_speed'),
                DB::raw('MAX(max_speed) as max_speed')
            )
            ->where('total_records', '>', 0)
            ->groupBy('device_id', 'device_name');

        $isExportSelected = false;
        if ($request->filled('export_type') && $request->export_type === 'selected' && $request->filled('row_ids')) {
            $rowIds = explode(',', $request->row_ids);
            if (!empty($rowIds)) {
                $query->whereIn('gps_tracks_raw.device_id', $rowIds);
                $isExportSelected = true;
            }
        } else {
            if ($request->filled('device_ids')) {
                $deviceIds = explode(',', $request->device_ids);
                $totalDevices = count($deviceMap);
                if (count($deviceIds) < $totalDevices) {
                    $cleanIds = array_map(function($id) { return ltrim((string)$id, '0'); }, $deviceIds);
                    $query->whereIn('gps_tracks_raw.device_id', $cleanIds);
                }
            }
        }

        if ($request->filled('location') || $request->filled('series')) {
            $filteredDevices = $deviceMap;
            if ($request->filled('location')) {
                $loc = trim(strtoupper($request->location));
                $filteredDevices = $filteredDevices->filter(function($d) use ($loc) {
                    $dLoc = strtoupper($d->location ?? '');
                    $dLok = strtoupper($d->lokasi ?? '');
                    return str_contains($dLoc, $loc) || str_contains($dLok, $loc);
                });
            }
            if ($request->filled('series')) {
                $series = trim(strtoupper($request->series));
                if ($series === 'VOLVO' || $series === 'DT VOLVO') {
                    $filteredDevices = $filteredDevices->filter(function($d) {
                        return stripos($d->series, 'FMX') !== false || stripos($d->series, 'VOLVO') !== false;
                    });
                } else {
                    $filteredDevices = $filteredDevices->filter(function($d) use ($series) {
                        return stripos($d->series, $series) !== false;
                    });
                }
            }
            $query->whereIn('gps_tracks_raw.device_id', $filteredDevices->pluck('device_id')->toArray());
        }

        $date = $request->input('date', date('Y-m-d'));
        $shift = $request->input('shift', 'shift1');

        if ($shift === 'shift1') {
            $query->where('record_date', $date)->whereBetween('record_hour', [7, 18]);
            $timeLabel = "Shift 1 (07:00 - 19:00)";
        } elseif ($shift === 'shift2') {
            $nextDate = date('Y-m-d', strtotime($date . ' +1 day'));
            $query->where(function($q) use ($date, $nextDate) {
                $q->where(function($q1) use ($date) {
                    $q1->where('record_date', $date)->where('record_hour', '>=', 19);
                })->orWhere(function($q2) use ($nextDate) {
                    $q2->where('record_date', $nextDate)->where('record_hour', '<=', 6);
                });
            });
            $timeLabel = "Shift 2 (19:00 - 07:00)";
        } elseif ($shift === 'op_malam') {
            $query->where('record_date', $date)->whereBetween('record_hour', [18, 23]);
            $timeLabel = "Operasional Malam (18:00 - 23:59)";
        } elseif ($shift === 'op_dini_hari') {
            $query->where('record_date', $date)->whereBetween('record_hour', [0, 6]);
            $timeLabel = "Operasional Dini Hari (00:00 - 07:00)";
        } elseif ($shift === 'op_pagi') {
            $query->where('record_date', $date)->whereBetween('record_hour', [7, 11]);
            $timeLabel = "Operasional Pagi (07:00 - 12:00)";
        } elseif ($shift === 'op_siang') {
            $query->where('record_date', $date)->whereBetween('record_hour', [12, 17]);
            $timeLabel = "Operasional Siang (12:00 - 18:00)";
        } else {
            $query->where('record_date', $date);
            $timeLabel = "Full Day (00:00 - 23:59)";
        }

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

        return ExcelExportService::streamXls(
            'export-speed-performance-' . date('Y-m-d_H-i-s') . '.xls',
            'SPEED PERFORMANCE REPORT',
            $headers,
            function ($out) use ($query, $date, $timeLabel, $deviceMap) {
                $serial = 1;
                foreach ($query->cursor() as $row) {
                    $rowClass = ($serial % 2 === 0) ? 'row-even' : 'row-odd';
                    $avgSpd = round($row->avg_speed, 1);
                    $maxSpd = round($row->max_speed, 1);
                    $master = $deviceMap->get((string)$row->device_id);
                    $devName = $row->device_name ?: ($master ? $master->device_name : $row->device_id);

                    fwrite($out, '    <tr class="' . $rowClass . '">' . "\n");
                    fwrite($out, '      <td class="text-center">' . $serial++ . '</td>' . "\n");
                    fwrite($out, '      <td class="text-center">' . htmlspecialchars($row->device_id ?? '-') . '</td>' . "\n");
                    fwrite($out, '      <td class="text-left">' . htmlspecialchars($devName) . '</td>' . "\n");
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
