<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportAlarmPageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $pageNum;
    public $pageCount = 200;
    public $beginTime;
    public $endTime;

    /**
     * Create a new job instance.
     */
    public function __construct($pageNum, $pageCount = 200, $beginTime = null, $endTime = null)
    {
        $this->pageNum = $pageNum;
        $this->pageCount = $pageCount;
        $this->beginTime = $beginTime;
        $this->endTime = $endTime;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $alarmService = new \App\Services\HowenAlarmService();
        $importLog = \App\Models\ImportLog::create([
            'job_name' => 'ImportAlarmPageJob',
            'started_at' => now(),
            'total_record' => 0,
            'status' => 'running',
        ]);

        try {
            // Delay 500ms before request
            usleep(500000);

            // Fetch alarms
            $alarms = $alarmService->fetchAlarmsPageWithMock(
                $this->pageNum,
                $this->pageCount,
                $this->beginTime,
                $this->endTime
            );

            if (empty($alarms)) {
                $importLog->update([
                    'finished_at' => now(),
                    'status' => 'completed',
                    'total_record' => 0,
                    'message' => 'No alarms returned',
                ]);
                return;
            }

            $inserted = 0;

            foreach ($alarms as $alarm) {
                // Skip alarms without device_id (invalid data)
                $deviceId = $alarm['deviceguid'] ?? $alarm['device_id'] ?? null;
                if (!$deviceId) {
                    \Illuminate\Support\Facades\Log::warning("Skipping alarm without device_id", ['alarm' => $alarm]);
                    continue;
                }

                // LOG RESPONSE API - PENTING untuk debugging
                \Illuminate\Support\Facades\Log::info("Howen API Alarm Response", [
                    'guid' => $alarm['guid'] ?? null,
                    'deviceName' => $alarm['deviceName'] ?? null,
                    'alarmState' => $alarm['alarmState'] ?? null,
                    'alarmtype' => $alarm['alarmtype'] ?? null,
                    'createtime' => $alarm['createtime'] ?? null,
                    'endTime' => $alarm['endTime'] ?? null,
                    'endSpeed' => $alarm['endSpeed'] ?? null,
                    'alarmValue' => $alarm['alarmValue'] ?? null,
                    'endDetail' => $alarm['endDetail'] ?? null,
                ]);

                // Map API fields to alarm_raw table
                $alarmRawData = [
                    'guid' => $alarm['guid'] ?? uniqid(),
                    'device_id' => $deviceId,
                    'device_name' => $alarm['deviceName'] ?? $alarm['device_name'] ?? null,
                    'alarm_type' => $alarm['alarmtype'] ?? $alarm['alarm_type'] ?? null,
                    'alarm_value' => null,
                    'alarm_state' => $alarm['alarmState'] ?? $alarm['alarm_state'] ?? 1,
                    'start_time' => $alarm['createtime'] ?? $alarm['start_time'] ?? null,
                    'end_time' => $alarm['endTime'] ?? $alarm['end_time'] ?? null,
                    'start_gps' => $alarm['alarmGps'] ?? $alarm['start_gps'] ?? null,
                    'end_gps' => $alarm['endGps'] ?? $alarm['end_gps'] ?? null,
                    'start_speed' => (float)($alarm['speed'] ?? $alarm['start_speed'] ?? 0),
                    'end_speed' => (float)($alarm['endSpeed'] ?? $alarm['end_speed'] ?? 0),
                    'report_time' => $alarm['reportTime'] ?? $alarm['report_time'] ?? null,
                    'duration_seconds' => (int)($alarm['alarmTimeLength'] ?? $alarm['duration_seconds'] ?? 0),
                    'start_detail' => $alarm['alarmValue'] ?? $alarm['start_detail'] ?? null,
                    'end_detail' => $alarm['endDetail'] ?? $alarm['end_detail'] ?? null,
                    'raw_json' => json_encode($alarm),
                ];

                // Insert or update (by guid)
                \App\Models\AlarmRaw::updateOrCreate(
                    ['guid' => $alarmRawData['guid']],
                    $alarmRawData
                );

                $inserted++;
            }

            $importLog->update([
                'finished_at' => now(),
                'status' => 'completed',
                'total_record' => $inserted,
                'message' => "Imported {$inserted} alarms",
            ]);

            \Illuminate\Support\Facades\Log::info("ImportAlarmPageJob Page {$this->pageNum} completed", ['inserted' => $inserted]);

        } catch (\Exception $e) {
            $importLog->update([
                'finished_at' => now(),
                'status' => 'failed',
                'message' => $e->getMessage(),
            ]);

            \Illuminate\Support\Facades\Log::error("ImportAlarmPageJob failed", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
