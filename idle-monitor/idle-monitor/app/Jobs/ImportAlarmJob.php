<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\SystemLogger;

class ImportAlarmJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job - Main orchestrator for alarm import
     * Flow: Get last_sync → fetch page 1 → dispatch per-page jobs → update last_sync
     */
    public function handle(): void
    {
        SystemLogger::jobStart('ImportAlarmJob');
        
        $mainLog = \App\Models\ImportLog::create([
            'job_name' => 'ImportAlarmJob',
            'started_at' => now(),
            'total_record' => 0,
            'status' => 'running',
        ]);

        try {
            // Get last sync time (watermark)
            $lastSync = \App\Models\SystemSetting::get(
                'last_alarm_sync',
                now()->subDays(1)->toDateTimeString()
            );

            $beginTime = $lastSync;
            $endTime = now()->toDateTimeString();

            SystemLogger::success('DATA_PULL', 'Starting alarm import', [
                'begin_time' => $beginTime,
                'end_time' => $endTime,
                'range_hours' => round((strtotime($endTime) - strtotime($beginTime)) / 3600, 1),
            ]);

            $alarmService = new \App\Services\HowenAlarmService();
            $pageNum = 1;
            $pageCount = 200;
            $totalRecords = 0;

            // Loop through pages and dispatch jobs
            do {
                try {
                    $alarms = $alarmService->fetchAlarmsPage(
                        $pageNum,
                        $pageCount,
                        $beginTime,
                        $endTime
                    );

                    if (!empty($alarms)) {
                        // Dispatch job for this page
                        ImportAlarmPageJob::dispatch($pageNum, $pageCount, $beginTime, $endTime);
                        $totalRecords += count($alarms);
                        
                        SystemLogger::success('DATA_PULL', "Page {$pageNum} dispatched for processing", [
                            'alarms_count' => count($alarms),
                            'total_so_far' => $totalRecords,
                        ]);
                    } else {
                        SystemLogger::warning('DATA_PULL', "Page {$pageNum} returned empty", [
                            'begin_time' => $beginTime,
                            'end_time' => $endTime,
                        ]);
                    }

                    $pageNum++;

                    // Stop if we got fewer records than page size
                    if (count($alarms) < $pageCount) {
                        break;
                    }

                } catch (\Exception $pageError) {
                    SystemLogger::error(
                        'DATA_PULL',
                        "Failed to fetch alarm page {$pageNum}",
                        SystemLogger::hints()['api_timeout'],
                        ['page' => $pageNum],
                        $pageError
                    );
                    break; // Stop processing further pages
                }

            } while (true);

            // Check if we got any data
            if ($totalRecords === 0) {
                SystemLogger::warning('DATA_PULL', 'No alarm data pulled', [
                    'reason' => 'API returned empty or no new alarms in time range',
                    'troubleshooting' => SystemLogger::hints()['no_data_pulled'],
                ]);
            }

            // Update last sync time
            \App\Models\SystemSetting::set('last_alarm_sync', $endTime);

            $mainLog->update([
                'finished_at' => now(),
                'status' => 'completed',
                'total_record' => $totalRecords,
                'message' => "Dispatched " . ($pageNum - 1) . " page jobs, total " . $totalRecords . " records",
            ]);

            SystemLogger::jobComplete('ImportAlarmJob', [
                'pages_processed' => $pageNum - 1,
                'total_records' => $totalRecords,
                'time_range' => "{$beginTime} to {$endTime}",
            ]);

        } catch (\Exception $e) {
            $mainLog->update([
                'finished_at' => now(),
                'status' => 'failed',
                'message' => $e->getMessage(),
            ]);

            // Determine error type and provide specific troubleshooting
            $troubleshooting = 'Check storage/logs/system-monitor.log for details';
            
            if (str_contains($e->getMessage(), 'authentication') || str_contains($e->getMessage(), 'token')) {
                $troubleshooting = SystemLogger::hints()['auth_failed'];
            } elseif (str_contains($e->getMessage(), 'timeout') || str_contains($e->getMessage(), 'connection')) {
                $troubleshooting = SystemLogger::hints()['api_timeout'];
            } elseif (str_contains($e->getMessage(), 'database') || str_contains($e->getMessage(), 'SQLSTATE')) {
                $troubleshooting = SystemLogger::hints()['database_connection'];
            }

            SystemLogger::error(
                'DATA_PULL',
                'ImportAlarmJob failed completely',
                [],
                $troubleshooting,
                $e
            );
            
            SystemLogger::jobFailed('ImportAlarmJob', $e->getMessage(), $e);
            throw $e;
        }
    }
}
