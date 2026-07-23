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

            // Prepare Excel (.xls) file
            $directory = 'public/exports';
            if (!Storage::exists($directory)) {
                Storage::makeDirectory($directory);
            }
            
            $fileName = 'idle-alarms-' . date('Y-m-d-H-i-s') . '-' . uniqid() . '.xls';
            $filePath = storage_path('app/' . $directory . '/' . $fileName);
            
            $out = fopen($filePath, 'w');
            
            $metadata = [
                'Mode Export' => 'Background Job',
                'Start Date' => $this->filters['start_date'] ?? '-',
                'End Date' => $this->filters['end_date'] ?? '-',
                'Location' => $this->filters['location'] ?? 'Semua',
                'Series' => $this->filters['series'] ?? 'Semua',
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
                ['label' => 'S.SPD', 'align' => 'right'],
                ['label' => 'E.SPD', 'align' => 'right'],
                ['label' => 'REPORT TIME', 'align' => 'center'],
                ['label' => 'DURATION', 'align' => 'center'],
            ];

            \App\Services\ExcelExportService::writeExcelDocument(
                $out,
                'IDLE ALARM MONITORING REPORT',
                $headers,
                function ($outStream) use ($query) {
                    $serial = 1;
                    $query->chunk(500, function ($alarms) use ($outStream, &$serial) {
                        foreach ($alarms as $alarm) {
                            $rowClass = ($serial % 2 === 0) ? 'row-even' : 'row-odd';

                            $durationSecs = $alarm->duration_seconds_calculated ?? 0;
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

                            $statusClass = $alarm->alarm_status === 'ALARM_END' ? 'badge-success' : 'badge-warning';

                            fwrite($outStream, '    <tr class="' . $rowClass . '">' . "\n");
                            fwrite($outStream, '      <td class="text-center">' . $serial++ . '</td>' . "\n");
                            fwrite($outStream, '      <td class="text-center">' . htmlspecialchars($alarm->device_id ?? '-') . '</td>' . "\n");
                            fwrite($outStream, '      <td class="text-left">' . htmlspecialchars($alarm->device_name ?? '-') . '</td>' . "\n");
                            fwrite($outStream, '      <td class="text-center">Idle</td>' . "\n");
                            fwrite($outStream, '      <td class="' . $statusClass . '">' . htmlspecialchars($alarm->alarm_status ?? '-') . '</td>' . "\n");
                            fwrite($outStream, '      <td class="text-center">' . ($alarm->starting_time ? date('Y-m-d H:i:s', strtotime($alarm->starting_time)) : '-') . '</td>' . "\n");
                            fwrite($outStream, '      <td class="text-center">' . htmlspecialchars($alarm->starting_location ?? '-') . '</td>' . "\n");
                            fwrite($outStream, '      <td class="text-center">' . ($alarm->ending_time ? date('Y-m-d H:i:s', strtotime($alarm->ending_time)) : '-') . '</td>' . "\n");
                            fwrite($outStream, '      <td class="text-center">' . htmlspecialchars($alarm->ending_location ?? '-') . '</td>' . "\n");
                            fwrite($outStream, '      <td class="text-left">' . htmlspecialchars($alarm->start_detail ?? '-') . '</td>' . "\n");
                            fwrite($outStream, '      <td class="text-left">' . htmlspecialchars($alarm->end_detail ?? '-') . '</td>' . "\n");
                            fwrite($outStream, '      <td class="text-right">' . htmlspecialchars(($alarm->start_speed ?? 0) . ' km/h') . '</td>' . "\n");
                            fwrite($outStream, '      <td class="text-right">' . htmlspecialchars(($alarm->end_speed ?? 0) . ' km/h') . '</td>' . "\n");
                            fwrite($outStream, '      <td class="text-center">' . ($alarm->report_time ? date('Y-m-d H:i:s', strtotime($alarm->report_time)) : '-') . '</td>' . "\n");
                            fwrite($outStream, '      <td class="' . $durBadgeClass . '">' . htmlspecialchars($alarm->duration_formatted ?? '-') . '</td>' . "\n");
                            fwrite($outStream, '    </tr>' . "\n");
                        }
                    });
                },
                $metadata
            );

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
