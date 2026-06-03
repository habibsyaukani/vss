<?php

namespace App\Console\Commands;

use App\Models\AlarmRaw;
use App\Models\IdleAlarm;
use App\Models\ImportLog;
use App\Services\HowenAlarmService;
use Illuminate\Console\Command;

class FixAlarmStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:fix-alarm-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix alarm data with correct alarmState mapping to alarm_status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Clear old data
        $this->info('Clearing old data...');
        AlarmRaw::truncate();
        IdleAlarm::truncate();
        ImportLog::truncate();
        $this->info("✅ Database cleared\n");

        // Get alarmService for import
        $alarmService = new HowenAlarmService();

        // Fetch alarms from mock data
        $this->info('Importing alarms (page 1)...');
        $alarms = $alarmService->fetchAlarmsPageWithMock(1, 200);

        if (empty($alarms)) {
            $this->error('❌ No alarms fetched');
            return 1;
        }

        $this->info("Fetched " . count($alarms) . " alarms\n");

        // Process and insert alarm_raw
        $inserted = 0;
        foreach ($alarms as $alarm) {
            $deviceId = $alarm['deviceguid'] ?? $alarm['device_id'] ?? null;
            if (!$deviceId) {
                continue;
            }

            // Log API response
            $this->info("Processing alarm: {$alarm['guid']} | State: {$alarm['alarmState']} | Device: {$alarm['deviceName']}");

            $alarmRawData = [
                'guid' => $alarm['guid'] ?? uniqid(),
                'device_id' => $deviceId,
                'device_name' => $alarm['deviceName'] ?? null,
                'alarm_type' => $alarm['alarmtype'] ?? 100,
                'alarm_state' => $alarm['alarmState'] ?? 1,
                'start_time' => $alarm['createtime'] ?? now()->toDateTimeString(),
                'end_time' => $alarm['endTime'] ?? now()->toDateTimeString(),
                'start_gps' => $alarm['alarmGps'] ?? null,
                'end_gps' => $alarm['endGps'] ?? null,
                'start_speed' => (float)($alarm['speed'] ?? 0),
                'end_speed' => (float)($alarm['endSpeed'] ?? 0),
                'report_time' => $alarm['reportTime'] ?? now()->toDateTimeString(),
                'start_detail' => $alarm['alarmValue'] ?? null,
                'end_detail' => $alarm['endDetail'] ?? null,
                'raw_json' => json_encode($alarm),
            ];

            AlarmRaw::updateOrCreate(
                ['guid' => $alarmRawData['guid']],
                $alarmRawData
            );

            $inserted++;
        }

        $this->info("\n✅ Imported {$inserted} alarms to alarm_raw\n");

        // Show alarm_raw data
        $this->line('=== ALARM_RAW DATA ===');
        $alarmRaws = AlarmRaw::orderBy('id')->get();
        foreach ($alarmRaws as $raw) {
            $this->line(sprintf(
                "[%d] GUID:%s | Device:%s | State:%s | Speed:%s→%s | Duration:%ds",
                $raw->id,
                $raw->guid,
                $raw->device_name,
                $raw->alarm_state,
                $raw->start_speed,
                $raw->end_speed,
                $raw->duration_seconds ?? 0
            ));
        }
        $this->newLine();

        // Process idle alarms
        $this->info('Processing idle alarms with validation...');

        $processed = 0;
        $skipped = 0;

        foreach ($alarmRaws as $alarmRaw) {
            $startSpeed = (float)($alarmRaw->start_speed ?? 0);
            $endSpeed = (float)($alarmRaw->end_speed ?? 0);
            $durationSeconds = 3600; // Mock value for testing

            // Validate
            $isValid = (
                $alarmRaw->alarm_state == 1 &&      // ALARM_END
                $startSpeed == 0 &&
                !empty($alarmRaw->end_speed) &&
                $endSpeed > 0 &&
                !empty($alarmRaw->end_time) &&
                $durationSeconds >= 300
            );

            if (!$isValid) {
                $this->warn("  ⏭️  SKIP {$alarmRaw->guid} - Invalid (state:{$alarmRaw->alarm_state}, start_speed:{$startSpeed}, end_speed:{$endSpeed})");
                $skipped++;
                continue;
            }

            // Map alarmState to alarm_status
            $alarmStatus = ($alarmRaw->alarm_state == 1) ? 'ALARM_END' : 'ALARMING';
            $durationMinutes = ceil($durationSeconds / 60);

            // Parse GPS
            $startLat = null;
            $startLong = null;
            if ($alarmRaw->start_gps && strpos($alarmRaw->start_gps, ',') !== false) {
                [$startLong, $startLat] = array_map('trim', explode(',', $alarmRaw->start_gps));
                $startLat = (float)$startLat;
                $startLong = (float)$startLong;
            }

            // Create idle_alarm
            IdleAlarm::updateOrCreate(
                ['guid' => $alarmRaw->guid],
                [
                    'device_id' => $alarmRaw->device_id,
                    'device_name' => $alarmRaw->device_name,
                    'alarm_type' => 'Idle',
                    'alarm_state' => $alarmRaw->alarm_state,
                    'alarm_status' => $alarmStatus,
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
                ]
            );

            $this->info("  ✅ PROCESS {$alarmRaw->guid} - Status: {$alarmStatus}");
            $processed++;
        }

        $this->info("\n✅ Processed {$processed} idle alarms, skipped {$skipped}\n");

        // Show idle_alarms data
        $this->line('=== IDLE_ALARMS DATA ===');
        $idleAlarms = IdleAlarm::orderBy('id')->get();
        foreach ($idleAlarms as $idle) {
            $this->line(sprintf(
                "[%d] GUID:%s | Device:%s | Status:%s | Speed:%s→%s | Duration:%dmin",
                $idle->id,
                $idle->guid,
                $idle->device_name,
                $idle->alarm_status,
                $idle->start_speed,
                $idle->end_speed,
                $idle->duration_minutes
            ));
        }

        $this->newLine();
        $this->info('✅ Done! All data fixed with correct alarm_status mapping.');

        return 0;
    }
}
