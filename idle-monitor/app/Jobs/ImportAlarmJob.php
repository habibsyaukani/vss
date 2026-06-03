<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportAlarmJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job - Main orchestrator for alarm import
     * Flow: Get last_sync → fetch page 1 → dispatch per-page jobs → update last_sync
     */
    public function handle(): void
    {
        $mainLog = \App\Models\ImportLog::create([
            'job_name' => 'ImportAlarmJob',
            'started_at' => now(),
            'total_record' => 0,
            'status' => 'running',
        ]);

        try {
            \Illuminate\Support\Facades\Log::info('ImportAlarmJob started');

            // Get last sync time (watermark)
            $lastSync = \App\Models\SystemSetting::get(
                'last_alarm_sync',
                now()->subDays(1)->toDateTimeString()
            );

            $beginTime = $lastSync;
            $endTime = now()->toDateTimeString();

            \Illuminate\Support\Facades\Log::info('Alarm import range', [
                'begin' => $beginTime,
                'end' => $endTime,
            ]);

            $alarmService = new \App\Services\HowenAlarmService();
            $pageNum = 1;
            $pageCount = 200;
            $totalRecords = 0;

            // Loop through pages and dispatch jobs
            do {
                $alarms = $alarmService->fetchAlarmsPageWithMock(
                    $pageNum,
                    $pageCount,
                    $beginTime,
                    $endTime
                );

                if (!empty($alarms)) {
                    // Dispatch job for this page
                    ImportAlarmPageJob::dispatch($pageNum, $pageCount, $beginTime, $endTime);
                    $totalRecords += count($alarms);
                    \Illuminate\Support\Facades\Log::info("Page {$pageNum} dispatched", ['count' => count($alarms)]);
                }

                $pageNum++;

                // Stop if we got fewer records than page size
                if (count($alarms) < $pageCount) {
                    break;
                }

            } while (true);

            // Update last sync time
            \App\Models\SystemSetting::set('last_alarm_sync', $endTime);

            $mainLog->update([
                'finished_at' => now(),
                'status' => 'completed',
                'total_record' => $totalRecords,
                'message' => "Dispatched " . ($pageNum - 1) . " page jobs, total " . $totalRecords . " records",
            ]);

            \Illuminate\Support\Facades\Log::info("ImportAlarmJob completed", [
                'pages' => $pageNum - 1,
                'total_records' => $totalRecords,
            ]);

        } catch (\Exception $e) {
            $mainLog->update([
                'finished_at' => now(),
                'status' => 'failed',
                'message' => $e->getMessage(),
            ]);

            \Illuminate\Support\Facades\Log::error('ImportAlarmJob failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
