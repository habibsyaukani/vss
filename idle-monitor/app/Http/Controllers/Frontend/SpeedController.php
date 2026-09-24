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
use App\Models\ExportJob;
use App\Jobs\ProcessSpeedExportJob;

class SpeedController extends Controller
{
    use HasDeviceGroups;

    /**
     * Show speed monitoring page
     */
    public function index()
    {
        // Force clear cache to fix the "0 devices" filter issue
        \Illuminate\Support\Facades\Cache::forget('frontend_device_sidebar');

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

        // ⚡ True server-side query — let MySQL handle pagination via composite index
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
            );

        $deviceIds = $request->device_ids;
        if (is_string($deviceIds)) {
            $deviceIds = json_decode($deviceIds, true);
        }

        // Filter by specific device IDs (from tree view)
        // Only apply whereIn if selected devices are a subset of total master devices
        if ($deviceIds && is_array($deviceIds) && !empty($deviceIds)) {
            $cleanIds = [];
            foreach ($deviceIds as $id) {
                $strId = (string) $id;
                $cleanIds[] = $strId;
                $unpadded = ltrim($strId, '0');
                if ($unpadded !== '' && $unpadded !== $strId) {
                    $cleanIds[] = $unpadded;
                }
            }
            $uniqueCleanIds = array_values(array_unique($cleanIds));
            if (count($uniqueCleanIds) < $deviceMap->count()) {
                $query->whereIn('device_id', $uniqueCleanIds);
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
            $query->whereIn('device_id', $filteredDevices->pluck('device_id')->toArray());
        }

        // Filter by speed range
        if ($request->filled('min_speed')) {
            $query->where('speed', '>=', $request->min_speed);
        }
        if ($request->filled('max_speed')) {
            $query->where('speed', '<=', $request->max_speed);
        }

        // Filter by overspeed
        if ($request->filled('overspeed') && $request->overspeed == '1') {
            $query->where('over_speed', 1);
        }

        // Filter by ACC status
        if ($request->filled('acc_on') && $request->acc_on == '1') {
            $query->where('acc_state', 1);
        }

        // Filter by date
        if ($request->filled('start_date')) {
            $query->where('gps_time', '>=', $request->start_date . ' 00:00:00');
        } else {
            $query->where('gps_time', '>=', now()->startOfDay());
        }
        if ($request->filled('end_date')) {
            $query->where('gps_time', '<=', $request->end_date . ' 23:59:59');
        }

        // Filter by speed mode
        if ($request->filled('speed_filter')) {
            switch ($request->speed_filter) {
                case 'low':
                    $query->where('speed', '>', 0)
                          ->where('speed', '<', 15);
                    break;
                case 'high':
                    $query->where('speed', '>=', 15);
                    break;
                default:
                    $query->where('speed', '>', 0);
                    break;
            }
        } else {
            $query->where('speed', '>', 0);
        }

        // ⚡ Validate DataTables pagination parameters
        $start = max(0, (int) $request->input('start', 0));
        $allowedLengths = [50, 100, 200, 300, 500];
        $reqLength = (int) $request->input('length', 50);
        $length = in_array($reqLength, $allowedLengths, true) ? $reqLength : 50;

        $fetchLimit = $length + 1;

        // ⚡ Order by gps_time DESC
        $query->orderBy('gps_time', 'desc');

        // ⚡ Over-fetch data: fetch $length + 1 rows
        $rawItems = $query->offset($start)->limit($fetchLimit)->get();

        $hasMore = $rawItems->count() > $length;
        if ($hasMore) {
            $rawItems->pop(); // remove 51st row
        }

        $dynamicRecords = $start + $rawItems->count() + ($hasMore ? 1 : 0);

        // ⚡ True server-side without COUNT(*) — pass paginated collection to DataTables
        return DataTables::of($rawItems)
            ->skipPaging()
            ->setTotalRecords($dynamicRecords)
            ->setFilteredRecords($dynamicRecords)
            ->with('has_more', $hasMore)
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
    /**
     * Dispatch background export job for speed data
     */
    public function export(Request $request)
    {
        $exportJob = ExportJob::create([
            'status' => 'pending'
        ]);

        $filters = $request->only([
            'start_date', 'end_date', 'speed_filter', 'location', 'series', 'device_ids', 'selected_ids'
        ]);

        if (is_string($filters['selected_ids'] ?? null)) {
            $filters['selected_ids'] = json_decode($filters['selected_ids'], true);
        }
        if (is_string($filters['device_ids'] ?? null)) {
            $filters['device_ids'] = json_decode($filters['device_ids'], true);
        }

        ProcessSpeedExportJob::dispatch($exportJob->id, $filters)->onQueue('exports');

        return response()->json([
            'use_queue' => true,
            'job_id' => $exportJob->id
        ]);
    }

    public function exportStatus($jobId)
    {
        $job = ExportJob::find($jobId);
        if (!$job) return response()->json(['status' => 'failed']);
        
        $progress = \Illuminate\Support\Facades\Cache::get('export_job_progress_' . $jobId, 0);
        $total = \Illuminate\Support\Facades\Cache::get('export_job_total_' . $jobId, 0);

        return response()->json([
            'status' => $job->status,
            'progress' => $progress,
            'total' => $total,
            'download_url' => $job->status === 'completed' ? route('frontend.speed.export.download', $job->id) : null
        ]);
    }

    /**
     * Download completed export file
     */
    public function exportDownload($jobId)
    {
        $job = ExportJob::findOrFail($jobId);
        if ($job->status !== 'completed' || !$job->file_path) {
            abort(404, 'Export is not ready or failed.');
        }
        
        $fullPath = storage_path('app/' . $job->file_path);
        if (!file_exists($fullPath)) {
            abort(404, 'Export file not found on disk.');
        }

        return response()->download($fullPath)->deleteFileAfterSend(true);
    }
}
