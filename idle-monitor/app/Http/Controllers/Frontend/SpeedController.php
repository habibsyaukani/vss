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
                $filteredDevices = $filteredDevices->filter(function($d) use ($request) {
                    return $d->location === $request->location || $d->lokasi === $request->location;
                });
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

        ProcessSpeedExportJob::dispatch($exportJob->id, $filters);

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

        $percentage = 0;
        if ($total > 0) {
            $percentage = round(($progress / $total) * 100);
            if ($percentage > 100) $percentage = 100;
        }

        return response()->json([
            'status' => $job->status,
            'progress' => $percentage,
            'total' => $total,
            'download_url' => $job->status === 'completed' ? route('speed.export.download', $job->id) : null
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
