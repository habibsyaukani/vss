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

        \Illuminate\Support\Facades\Log::info("[ProcessSpeedExportJob] START: Job ID {$this->exportJobId}");

        try {
            $exportJob->update(['status' => 'processing']);

            // 1. Build Query
            // ⚡ Fast query purely on gps_tracks_raw (NO SQL JOINs)
            $query = GpsTrackRaw::query()
                ->select(
                'id', 'device_id', 'device_name', 'longitude', 'latitude',
                'altitude', 'speed', 'direction', 'satellites', 'gps_time',
                'acc_state as is_acc_on', 'over_speed as is_overspeed', 'urgency as is_emergency',
                'io_state as input_output_status'
            )->orderBy('gps_time', 'desc');

            $deviceMap = cache()->remember('devices_map_by_id_dict', 300, function() {
                return Device::all()->keyBy(function($item) {
                    return (string) $item->device_id;
                });
            });

            if (!empty($this->filters['selected_ids']) && is_array($this->filters['selected_ids'])) {
                $query->whereIn('id', $this->filters['selected_ids']);
            } else {
                if (!empty($this->filters['device_ids']) && is_array($this->filters['device_ids'])) {
                    $totalDevices = count($deviceMap);
                    if (count($this->filters['device_ids']) < $totalDevices) {
                        $cleanIds = array_map(function($id) { return ltrim((string)$id, '0'); }, $this->filters['device_ids']);
                        $query->whereIn('device_id', $cleanIds);
                    }
                }

                if (!empty($this->filters['location']) || !empty($this->filters['series'])) {
                    $filteredDevices = collect($deviceMap->values());
                    if (!empty($this->filters['location'])) {
                        $filteredDevices = $filteredDevices->filter(function($d) {
                            return $d->location === $this->filters['location'] || $d->lokasi === $this->filters['location'];
                        });
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
                    $query->whereIn('device_id', $filteredDevices->pluck('device_id')->toArray());
                }

                if (!empty($this->filters['start_date'])) {
                    $query->where('gps_time', '>=', $this->filters['start_date'] . ' 00:00:00');
                } else {
                    $query->where('gps_time', '>=', now()->startOfDay());
                }
                
                if (!empty($this->filters['end_date'])) {
                    $query->where('gps_time', '<=', $this->filters['end_date'] . ' 23:59:59');
                }

                if (!empty($this->filters['speed_filter'])) {
                    switch ($this->filters['speed_filter']) {
                        case 'low':
                            $query->where('speed', '>', 0)->where('speed', '<', 15);
                            break;
                        case 'high':
                            $query->where('speed', '>=', 41);
                            break;
                    }
                } else {
                    $query->where('speed', '>', 0);
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
            ], ';');

            // We do NOT use count() because it is extremely slow on 9 million rows
            // We just update progress with the number of rows exported so far
            \Illuminate\Support\Facades\Cache::put('export_job_total_' . $this->exportJobId, -1, 3600);
            \Illuminate\Support\Facades\Cache::put('export_job_progress_' . $this->exportJobId, 1, 3600); // Set to 1 so frontend immediately shows "Sedang mengekspor: 1 baris..."

            // 3. Stream Data
            $serial = 1;
            // Limit to 200,000 rows to prevent infinite exports crashing the server
            $query->limit(200000);
            
            foreach ($query->toBase()->cursor() as $track) {
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
                ], ';');

                // Update progress every 500 rows
                if ($serial % 500 === 0) {
                    \Illuminate\Support\Facades\Cache::put('export_job_progress_' . $this->exportJobId, $serial, 3600);
                }
                
                $serial++;
            }
            
            fclose($out);

            // 4. Update Job Status
            $exportJob->update([
                'status' => 'completed',
                'file_path' => $filePath
            ]);
            
            \Illuminate\Support\Facades\Log::info("[ProcessSpeedExportJob] FINISHED: Job ID {$this->exportJobId}. Total processed: " . ($serial - 1));

        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[ProcessSpeedExportJob] Failed: ' . $e->getMessage());
            $exportJob->update([
                'status' => 'failed',
                'file_path' => $e->getMessage() // Store error message for debugging
            ]);
            throw $e;
        }
    }
}
