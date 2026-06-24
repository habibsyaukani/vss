<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ExportJob;
use App\Models\IdleAlarm;
use Illuminate\Support\Facades\Storage;
use Exception;

class ExportIdleAlarmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes timeout
    protected $jobId;
    protected $filters;

    /**
     * Create a new job instance.
     */
    public function __construct($jobId, $filters)
    {
        $this->jobId = $jobId;
        $this->filters = $filters;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $exportJob = ExportJob::find($this->jobId);
        if (!$exportJob) return;

        try {
            $exportJob->update(['status' => 'processing']);

            $query = IdleAlarm::with('device');

            // Apply filters
            if (!empty($this->filters['location'])) {
                $query->whereHas('device', function($q) {
                    $q->where('location', $this->filters['location']);
                });
            }
            if (!empty($this->filters['series'])) {
                $query->whereHas('device', function($q) {
                    if (strtoupper($this->filters['series']) === 'VOLVO') {
                        $q->where('series', 'LIKE', '%FMX%');
                    } else {
                        $q->where('series', $this->filters['series']);
                    }
                });
            }
            if (!empty($this->filters['device_ids']) && is_array($this->filters['device_ids'])) {
                $query->whereIn('device_id', $this->filters['device_ids']);
            }
            if (!empty($this->filters['start_date'])) {
                $query->whereDate('starting_time', '>=', $this->filters['start_date']);
            }
            if (!empty($this->filters['end_date'])) {
                $query->whereDate('starting_time', '<=', $this->filters['end_date']);
            }
            if (!empty($this->filters['duration_range'])) {
                switch ($this->filters['duration_range']) {
                    case 'lt5':
                        $query->where('duration_minutes', '<', 5);
                        break;
                    case '5to15':
                        $query->where('duration_minutes', '>=', 5)->where('duration_minutes', '<', 15);
                        break;
                    case '15to30':
                        $query->where('duration_minutes', '>=', 15)->where('duration_minutes', '<', 30);
                        break;
                    case 'gt30':
                        $query->where('duration_minutes', '>=', 30);
                        break;
                }
            }

            // Prepare CSV file
            $directory = 'public/exports';
            if (!Storage::exists($directory)) {
                Storage::makeDirectory($directory);
            }
            
            $fileName = 'idle-alarms-' . date('Y-m-d-H-i-s') . '-' . uniqid() . '.csv';
            $filePath = storage_path('app/' . $directory . '/' . $fileName);
            
            $out = fopen($filePath, 'w');
            
            // CSV Headers
            fputcsv($out, [
                'device_id', 'device_name', 'alarm_type', 'alarm_status',
                'starting_time', 'starting_location', 'ending_time', 'ending_location',
                'start_detail', 'end_detail', 'start_speed', 'end_speed',
                'report_time', 'duration_seconds'
            ], ';');

            // Chunk records to prevent memory exhaustion
            $query->chunk(500, function ($alarms) use ($out) {
                foreach ($alarms as $alarm) {
                    fputcsv($out, [
                        $alarm->device_id,
                        $alarm->device_name,
                        $alarm->alarm_type,
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
            });

            fclose($out);

            // Mark job complete
            $exportJob->update([
                'status' => 'completed',
                'file_path' => $directory . '/' . $fileName
            ]);

        } catch (Exception $e) {
            $exportJob->update(['status' => 'failed']);
            throw $e;
        }
    }
}
