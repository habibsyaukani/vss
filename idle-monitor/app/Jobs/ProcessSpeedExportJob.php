<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\ExportJob;
use App\Models\GpsTrackRaw;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessSpeedExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // Allow 1 hour for huge exports

    protected $exportJobId;
    protected $filters;

    /**
     * Create a new job instance.
     */
    public function __construct(int $exportJobId, array $filters)
    {
        $this->exportJobId = $exportJobId;
        $this->filters = $filters;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $exportJob = ExportJob::find($this->exportJobId);
        if (!$exportJob) return;

        try {
            $exportJob->update(['status' => 'processing']);

            // 1. Build Query
            $query = GpsTrackRaw::select(
                'id', 'device_id', 'device_name', 'longitude', 'latitude',
                'altitude', 'speed', 'direction', 'satellites', 'gps_time',
                'acc_state as is_acc_on', 'over_speed as is_overspeed', 'urgency as is_emergency',
                'input_output_status'
            )->latest('gps_tracks_raw.gps_time');

            $deviceMap = cache()->remember('devices_map_by_id_dict', 300, function() {
                return Device::all()->keyBy(function($item) {
                    return (string) $item->device_id;
                });
            });

            if (!empty($this->filters['selected_ids']) && is_array($this->filters['selected_ids'])) {
                $query->whereIn('gps_tracks_raw.id', $this->filters['selected_ids']);
            } else {
                if (!empty($this->filters['device_ids']) && is_array($this->filters['device_ids'])) {
                    $totalDevices = count($deviceMap);
                    if (count($this->filters['device_ids']) < $totalDevices) {
                        $cleanIds = array_map(function($id) { return ltrim((string)$id, '0'); }, $this->filters['device_ids']);
                        $query->whereIn('gps_tracks_raw.device_id', $cleanIds);
                    }
                }

                if (!empty($this->filters['location']) || !empty($this->filters['series'])) {
                    $filteredDevices = collect($deviceMap->values());
                    if (!empty($this->filters['location'])) {
                        $filteredDevices = $filteredDevices->where('lokasi', $this->filters['location']);
                    }
                    if (!empty($this->filters['series'])) {
                        if (strtoupper($this->filters['series']) === 'VOLVO') {
                            $filteredDevices = $filteredDevices->filter(function($d) {
                                return stripos($d->series, 'FMX') !== false;
                            });
                        } else {
                            $filteredDevices = $filteredDevices->where('series', $this->filters['series']);
                        }
                    }
                    $query->whereIn('gps_tracks_raw.device_id', $filteredDevices->pluck('device_id')->toArray());
                }

                if (!empty($this->filters['start_date'])) {
                    $query->where('gps_tracks_raw.gps_time', '>=', $this->filters['start_date'] . ' 00:00:00');
                } else {
                    $query->where('gps_tracks_raw.gps_time', '>=', now()->startOfDay());
                }
                
                if (!empty($this->filters['end_date'])) {
                    $query->where('gps_tracks_raw.gps_time', '<=', $this->filters['end_date'] . ' 23:59:59');
                }

                if (!empty($this->filters['speed_filter'])) {
                    switch ($this->filters['speed_filter']) {
                        case 'low':
                            $query->where('gps_tracks_raw.speed', '>', 0)->where('gps_tracks_raw.speed', '<', 15);
                            break;
                        case 'high':
                            $query->where('gps_tracks_raw.speed', '>=', 41);
                            break;
                    }
                } else {
                    $query->where('gps_tracks_raw.speed', '>', 0);
                }
            }

            // 2. Prepare File
            $filename = 'export-speed-monitoring-' . date('Y-m-d_H-i-s') . '.csv';
            $filePath = 'exports/' . $filename;
            
            // Ensure directory exists
            \Illuminate\Support\Facades\Storage::disk('local')->makeDirectory('exports');
            $fullPath = storage_path('app/' . $filePath);
            
            $out = fopen($fullPath, 'w');
            
            // UTF-8 BOM
            fwrite($out, "\xEF\xBB\xBF");
            
            // Write Headers
            fputcsv($out, [
                'NO', 'DEVICE NAME (ID)', 'FLEET', 'SPEED', 'ALTITUDE', 
                'TIME', 'LOCATION', 'ACCURACY', 'DIRECTION', 'SATELLITES', 
                'I/O STATUS', 'EMERGENCY', 'IGNITION (ACC)'
            ]);

            // Count total rows for progress tracking
            $totalRows = $query->count();
            \Illuminate\Support\Facades\Cache::put('export_job_total_' . $this->exportJobId, $totalRows, 3600);
            \Illuminate\Support\Facades\Cache::put('export_job_progress_' . $this->exportJobId, 0, 3600);

            // 3. Stream Data
            $serial = 1;
            foreach ($query->cursor() as $track) {
                $master = $deviceMap->get((string)$track->device_id);
                $realDevName = $track->device_name ?: ($master ? $master->device_name : null);
                $deviceName = ($realDevName ?? '-') . ' (' . $track->device_id . ')';
                
                $fleetName = '-';
                if ($realDevName) {
                    $parts = explode('-', $realDevName);
                    $fleetName = isset($parts[1]) ? $parts[1] : 'Unknown';
                }

                $location = ($track->latitude && $track->longitude) ? $track->latitude . ',' . $track->longitude : '-';
                $time = $track->gps_time ? date('Y-m-d H:i:s', strtotime($track->gps_time)) : '-';
                
                fputcsv($out, [
                    $serial,
                    $deviceName,
                    $fleetName,
                    $track->speed . ' Km/h',
                    $track->altitude ?? '0',
                    $time,
                    $location,
                    '0',
                    $track->direction ?? '0',
                    $track->satellites ?? '0',
                    $track->input_output_status ?? '',
                    $track->is_emergency ? '1' : '0',
                    $track->is_acc_on ? 'ON' : 'OFF'
                ]);

                if ($serial % 2000 === 0 && $totalRows > 0) {
                    $progress = round(($serial / $totalRows) * 100);
                    \Illuminate\Support\Facades\Cache::put('export_job_progress_' . $this->exportJobId, $progress, 3600);
                }
                
                $serial++;
            }
            
            fclose($out);

            // 4. Update Job Status
            $exportJob->update([
                'status' => 'completed',
                'file_path' => $filePath
            ]);

        } catch (Throwable $e) {
            $exportJob->update([
                'status' => 'failed',
                'file_path' => $e->getMessage() // Store error message for debugging
            ]);
            throw $e;
        }
    }
}
