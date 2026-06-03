<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessIdleAlarmJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes

    /**
     * Execute the job - Process idle alarms from alarm_raw → idle_alarms
     * Filter: alarm_type = 100
     * Calculate: duration, GPS coordinates
     */
    public function handle(): void
    {
        $processLog = \App\Models\ImportLog::create([
            'job_name' => 'ProcessIdleAlarmJob',
            'started_at' => now(),
            'total_record' => 0,
            'status' => 'running',
        ]);

        try {
            \Illuminate\Support\Facades\Log::info('ProcessIdleAlarmJob started');

            $alarmService = new \App\Services\HowenAlarmService();
            $processed = 0;
            $skipped = 0;

            // Get unprocessed alarms from alarm_raw (alarm_type = 100 = idle)
            $alarms = \App\Models\AlarmRaw::where('alarm_type', 100)
                ->orderBy('start_time', 'asc')
                ->get();

            \Illuminate\Support\Facades\Log::info("Processing idle alarms", ['count' => $alarms->count()]);

            foreach ($alarms as $alarmRaw) {
                try {
                    // VALIDATION: Check if idle is complete and valid for reporting
                    // Valid idle: start_speed = 0 AND end_speed > 0 AND duration >= 5 minutes
                    
                    $startSpeed = (float)($alarmRaw->start_speed ?? 0);
                    $endSpeed = (float)($alarmRaw->end_speed ?? 0);
                    
                    // Calculate duration first
                    $startTime = \Carbon\Carbon::parse($alarmRaw->start_time);
                    $endTime = \Carbon\Carbon::parse($alarmRaw->end_time ?? now());
                    $durationSeconds = $endTime->diffInSeconds($startTime);
                    
                    // Check validation conditions
                    $isValidIdle = (
                        $startSpeed == 0 &&
                        !empty($alarmRaw->end_speed) &&
                        $endSpeed > 0 &&
                        !empty($alarmRaw->end_time) &&
                        $durationSeconds >= 300  // 5 minutes minimum
                    );
                    
                    if (!$isValidIdle) {
                        $skipped++;
                        \Illuminate\Support\Facades\Log::info("Skipped invalid idle alarm {$alarmRaw->guid}", [
                            'start_speed' => $startSpeed,
                            'end_speed' => $endSpeed,
                            'end_time' => $alarmRaw->end_time,
                            'duration_seconds' => $durationSeconds,
                            'reason' => $this->getValidationFailureReason($startSpeed, $endSpeed, $alarmRaw->end_time, $durationSeconds)
                        ]);
                        continue;
                    }
                    
                    $durationMinutes = ceil($durationSeconds / 60);
                    
                    // MAP ALARM_STATE TO ALARM_STATUS
                    // alarmState from Howen API:
                    // 0 = ALARMING (idle masih berlangsung)
                    // 1 = ALARM_END (idle sudah selesai, kendaraan bergerak lagi)
                    // Untuk idle alarm yang valid, pasti sudah ALARM_END (state = 1)
                    $alarmState = $alarmRaw->alarm_state ?? 1;
                    $alarmStatus = $this->mapAlarmStateToStatus($alarmState);
                    
                    \Illuminate\Support\Facades\Log::info("Processing idle alarm with state mapping", [
                        'guid' => $alarmRaw->guid,
                        'alarmState' => $alarmState,
                        'alarm_status' => $alarmStatus,
                        'end_speed' => $endSpeed,
                    ]);
                    
                    // Parse GPS coordinates
                    $startLat = null;
                    $startLong = null;
                    $endLat = null;
                    $endLong = null;

                    if ($alarmRaw->start_gps && strpos($alarmRaw->start_gps, ',') !== false) {
                        [$startLong, $startLat] = array_map('trim', explode(',', $alarmRaw->start_gps));
                        $startLat = (float)$startLat;
                        $startLong = (float)$startLong;
                    }

                    if ($alarmRaw->end_gps && strpos($alarmRaw->end_gps, ',') !== false) {
                        [$endLong, $endLat] = array_map('trim', explode(',', $alarmRaw->end_gps));
                        $endLat = (float)$endLat;
                        $endLong = (float)$endLong;
                    }

                    // Create or update idle_alarm (only valid ones)
                    $idleAlarm = \App\Models\IdleAlarm::updateOrCreate(
                        ['guid' => $alarmRaw->guid],
                        [
                            'device_id' => $alarmRaw->device_id,
                            'device_name' => $alarmRaw->device_name,
                            'alarm_type' => 'Idle',
                            'alarm_state' => $alarmState,
                            'alarm_status' => $alarmStatus,  // Mapped from alarmState
                            'starting_time' => $alarmRaw->start_time,
                            'starting_location' => $alarmRaw->start_gps,
                            'ending_time' => $alarmRaw->end_time,
                            'ending_location' => $alarmRaw->end_gps,
                            'start_detail' => $alarmRaw->start_detail,
                            'end_detail' => $alarmRaw->end_detail,
                            'start_speed' => $startSpeed,
                            'end_speed' => $endSpeed,
                            'report_time' => $alarmRaw->report_time,
                            'duration_seconds' => $durationSeconds,
                            'duration_minutes' => $durationMinutes,
                            'latitude_start' => $startLat,
                            'longitude_start' => $startLong,
                            'latitude_end' => $endLat,
                            'longitude_end' => $endLong,
                        ]
                    );

                    $processed++;
                    \Illuminate\Support\Facades\Log::info("Valid idle alarm processed {$alarmRaw->guid}", [
                        'start_speed' => $startSpeed,
                        'end_speed' => $endSpeed,
                        'duration_minutes' => $durationMinutes,
                    ]);

                } catch (\Exception $e) {
                    $skipped++;
                    \Illuminate\Support\Facades\Log::error("Failed to process alarm {$alarmRaw->guid}", [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $processLog->update([
                'finished_at' => now(),
                'status' => 'completed',
                'total_record' => $processed,
                'message' => "Processed {$processed} idle alarms, skipped {$skipped}",
            ]);

            \Illuminate\Support\Facades\Log::info("ProcessIdleAlarmJob completed", [
                'processed' => $processed,
                'skipped' => $skipped,
            ]);

        } catch (\Exception $e) {
            $processLog->update([
                'finished_at' => now(),
                'status' => 'failed',
                'message' => $e->getMessage(),
            ]);

            \Illuminate\Support\Facades\Log::error('ProcessIdleAlarmJob failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get validation failure reason for logging
     */
    private function getValidationFailureReason($startSpeed, $endSpeed, $endTime, $durationSeconds)
    {
        if ($startSpeed != 0) {
            return "start_speed not 0 (was {$startSpeed})";
        }
        if (empty($endTime)) {
            return "end_time is NULL (idle still ongoing)";
        }
        if (empty($endSpeed) || $endSpeed == 0) {
            return "end_speed is 0 or empty (vehicle still idle)";
        }
        if ($durationSeconds < 300) {
            $minutes = floor($durationSeconds / 60);
            return "duration too short ({$minutes}min < 5min minimum)";
        }
        return "unknown validation failure";
    }

    /**
     * Map alarmState from Howen API to alarm_status
     * 
     * alarmState values from Howen API:
     * 0 = ALARMING (idle masih berlangsung, kendaraan belum bergerak)
     * 1 = ALARM_END (idle sudah selesai, kendaraan sudah bergerak lagi)
     * 
     * @param int $alarmState
     * @return string
     */
    private function mapAlarmStateToStatus($alarmState)
    {
        switch ($alarmState) {
            case 0:
                return 'ALARMING';  // Idle masih berlangsung
            case 1:
                return 'ALARM_END'; // Idle sudah selesai, kendaraan bergerak
            default:
                return 'CLOSED';    // Default untuk valid idle yang sudah selesai
        }
    }
}
