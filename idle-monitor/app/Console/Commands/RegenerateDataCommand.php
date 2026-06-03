<?php

namespace App\Console\Commands;

use App\Models\AlarmRaw;
use App\Models\IdleAlarm;
use App\Models\ImportLog;
use App\Services\HowenAlarmService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RegenerateDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:regenerate-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate alarm data dengan correct mapping (alarm_status + GPS format)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== DATA REGENERATION ===');
        $this->newLine();

        // Step 1: Clear existing data
        $this->info('Step 1: Clearing old data...');
        IdleAlarm::truncate();
        AlarmRaw::truncate();
        ImportLog::truncate();
        $this->info('✅ Database cleared');
        $this->newLine();

        // Step 2: Import fresh alarm data
        $this->info('Step 2: Importing alarms from Howen API...');
        $alarmService = new HowenAlarmService();
        $alarms = $alarmService->fetchAlarmsPageWithMock(1, 200);

        if (empty($alarms)) {
            $this->error('❌ No alarms fetched!');
            return 1;
        }

        $this->info("Fetched " . count($alarms) . " alarms");
        $this->newLine();

        // Step 3: Process and insert alarm_raw with correct data
        $this->info('Step 3: Processing alarms to alarm_raw...');
        $inserted = 0;

        foreach ($alarms as $alarm) {
            $deviceId = $alarm['deviceguid'] ?? $alarm['device_id'] ?? null;
            if (!$deviceId) {
                continue;
            }

            $this->info("  Processing: {$alarm['guid']} | State: {$alarm['alarmState']} | Device: {$alarm['deviceName']}");

            // CRITICAL: Parse GPS in correct format (longitude,latitude from Howen)
            $startGps = $alarm['alarmGps'] ?? null;
            $endGps = $alarm['endGps'] ?? null;

            $this->line("    Start GPS (from API): $startGps");
            $this->line("    End GPS (from API): $endGps");

            $alarmRawData = [
                'guid' => $alarm['guid'] ?? uniqid(),
                'device_id' => $deviceId,
                'device_name' => $alarm['deviceName'] ?? null,
                'alarm_type' => $alarm['alarmtype'] ?? 100,
                'alarm_state' => $alarm['alarmState'] ?? 1,
                'start_time' => $alarm['createtime'] ?? now()->toDateTimeString(),
                'end_time' => $alarm['endTime'] ?? now()->toDateTimeString(),
                'start_gps' => $startGps,  // longitude,latitude from Howen
                'end_gps' => $endGps,      // longitude,latitude from Howen
                'start_speed' => (float)($alarm['speed'] ?? 0),
                'end_speed' => (float)($alarm['endSpeed'] ?? 0),
                'report_time' => $alarm['reportTime'] ?? now()->toDateTimeString(),
                'start_detail' => $alarm['alarmValue'] ?? null,
                'end_detail' => $alarm['endDetail'] ?? null,
                'raw_json' => json_encode($alarm),
            ];

            AlarmRaw::create($alarmRawData);
            $inserted++;
        }

        $this->info("\n✅ Inserted $inserted alarms to alarm_raw");
        $this->newLine();

        // Step 4: Process to idle_alarms with correct mapping
        $this->info('Step 4: Processing idle alarms with correct mapping...');

        $alarmRaws = AlarmRaw::where('alarm_type', 100)->get();
        $processed = 0;
        $skipped = 0;

        foreach ($alarmRaws as $alarmRaw) {
            $startSpeed = (float)($alarmRaw->start_speed ?? 0);
            $endSpeed = (float)($alarmRaw->end_speed ?? 0);
            $startTime = Carbon::parse($alarmRaw->start_time);
            $endTime = Carbon::parse($alarmRaw->end_time ?? now());
            $durationSeconds = $endTime->diffInSeconds($startTime);

            // Validate
            $isValid = (
                $alarmRaw->alarm_state == 1 &&          // ALARM_END
                $startSpeed == 0 &&
                !empty($alarmRaw->end_speed) &&
                $endSpeed > 0 &&
                !empty($alarmRaw->end_time) &&
                $durationSeconds >= 300
            );

            if (!$isValid) {
                $this->warn("  ⏭️  SKIP {$alarmRaw->guid} - Invalid");
                $this->line("      State: {$alarmRaw->alarm_state}, Start Speed: $startSpeed, End Speed: $endSpeed, Duration: {$durationSeconds}s");
                $skipped++;
                continue;
            }

            $durationMinutes = ceil($durationSeconds / 60);

            // Map alarmState to alarm_status
            $alarmStatus = ($alarmRaw->alarm_state == 1) ? 'ALARM_END' : 'ALARMING';

            // Parse GPS - CORRECT FORMAT: longitude,latitude
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

            // Create idle_alarm with CORRECT data
            IdleAlarm::create([
                'guid' => $alarmRaw->guid,
                'device_id' => $alarmRaw->device_id,
                'device_name' => $alarmRaw->device_name,
                'alarm_type' => 'Idle',
                'alarm_state' => $alarmRaw->alarm_state,
                'alarm_status' => $alarmStatus,  // CORRECT: ALARM_END (not 'new')
                'starting_time' => $alarmRaw->start_time,
                'starting_location' => $alarmRaw->start_gps,  // longitude,latitude
                'ending_time' => $alarmRaw->end_time,
                'ending_location' => $alarmRaw->end_gps,      // longitude,latitude
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
            ]);

            $this->info("  ✅ PROCESS {$alarmRaw->guid}");
            $this->line("      Status: $alarmStatus, Duration: {$durationMinutes}min");
            $this->line("      Start GPS: {$alarmRaw->start_gps} → Lat: $startLat, Long: $startLong");
            $this->line("      End GPS: {$alarmRaw->end_gps} → Lat: $endLat, Long: $endLong");
            $processed++;
        }

        $this->info("\n✅ Processed $processed idle alarms, skipped $skipped");
        $this->newLine();

        // Step 5: Verify data
        $this->info('Step 5: Verifying data...');
        $idleAlarms = IdleAlarm::all();
        foreach ($idleAlarms as $idle) {
            $this->line("  ID: {$idle->id} | GUID: {$idle->guid}");
            $this->line("    Status: {$idle->alarm_status} | Duration: {$idle->duration_minutes}min");
            $this->line("    Start: {$idle->starting_time} | Location: {$idle->starting_location}");
            $this->line("    End: {$idle->ending_time} | Location: {$idle->ending_location}");
            $this->line("    GPS Parsed: Lat({$idle->latitude_start}, {$idle->latitude_end}), Long({$idle->longitude_start}, {$idle->longitude_end})");
        }

        $this->newLine();
        $this->info('✅ Data regeneration complete!');
        $this->info('Database is now properly configured with:');
        $this->line('  ✓ alarm_status: ALARM_END (from alarmState)');
        $this->line('  ✓ starting_location: longitude,latitude format');
        $this->line('  ✓ ending_location: longitude,latitude format');
        $this->line('  ✓ Extracted lat/long for each location');

        return 0;
    }
}
