<?php
/**
 * Clear idle_alarms dan re-process dengan mapping yang benar
 * Run: php clear_and_regenerate.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\IdleAlarm;
use App\Models\AlarmRaw;
use App\Models\Device;

echo "\n====================================\n";
echo "CLEAR & REGENERATE IDLE ALARMS DATA\n";
echo "====================================\n\n";

// Step 1: Clear idle_alarms
echo "[1/3] Clearing idle_alarms table...\n";
$clearedCount = DB::table('idle_alarms')->delete();
echo "✅ Cleared {$clearedCount} records\n\n";

// Step 2: Re-process alarm_raw
echo "[2/3] Re-processing from alarm_raw...\n";

$alarmRaws = AlarmRaw::where('alarm_type', 100)  // alarm_type 100 = Idle
    ->get();

echo "Found {$alarmRaws->count()} alarm_raw records to process\n\n";

$processed = 0;
$skipped = 0;
$errors = 0;

foreach ($alarmRaws as $alarmRaw) {
    try {
        // Get device untuk serial_no
        $device = Device::where('device_id', $alarmRaw->device_id)->first();
        $serialNo = $device ? ($device->serial_no ?? $device->id) : $alarmRaw->device_id;
        
        // Parse GPS
        $startGps = $alarmRaw->start_gps ? explode(',', $alarmRaw->start_gps) : [0, 0];
        $endGps = $alarmRaw->end_gps ? explode(',', $alarmRaw->end_gps) : [0, 0];
        
        $latStart = $startGps[1] ?? 0;
        $longStart = $startGps[0] ?? 0;
        $latEnd = $endGps[1] ?? 0;
        $longEnd = $endGps[0] ?? 0;
        
        // Parse alarm_state untuk mapping status
        $alarmState = $alarmRaw->alarm_state ?? 0;
        $alarmStatus = ($alarmState == 1) ? 'ALARM_END' : 'ALARMING';
        
        // Calculate duration
        $startTime = \Carbon\Carbon::parse($alarmRaw->start_time);
        $endTime = \Carbon\Carbon::parse($alarmRaw->end_time);
        $durationMinutes = $endTime->diffInMinutes($startTime);
        $durationSeconds = $endTime->diffInSeconds($startTime);
        
        // Validasi
        if ($alarmRaw->start_speed != 0) {
            echo "⏭️  SKIP: {$alarmRaw->guid} - start_speed not 0 (was {$alarmRaw->start_speed})\n";
            $skipped++;
            continue;
        }
        
        if (empty($alarmRaw->end_speed) || $alarmRaw->end_speed <= 0) {
            echo "⏭️  SKIP: {$alarmRaw->guid} - end_speed not valid (was {$alarmRaw->end_speed})\n";
            $skipped++;
            continue;
        }
        
        if ($durationSeconds < 300) {  // 5 minutes
            echo "⏭️  SKIP: {$alarmRaw->guid} - duration too short ({$durationMinutes}min < 5min)\n";
            $skipped++;
            continue;
        }
        
        if (!$alarmRaw->end_time) {
            echo "⏭️  SKIP: {$alarmRaw->guid} - end_time is NULL\n";
            $skipped++;
            continue;
        }
        
        // Create idle_alarm
        IdleAlarm::create([
            'guid' => $alarmRaw->guid,
            'serial_no' => $serialNo,
            'device_id' => $alarmRaw->device_id,
            'device_name' => $alarmRaw->device_name,
            'alarm_type' => $alarmRaw->alarm_type,
            'alarm_state' => $alarmState,
            'alarm_status' => $alarmStatus,
            'starting_time' => $alarmRaw->start_time,
            'starting_location' => $alarmRaw->start_gps,
            'ending_time' => $alarmRaw->end_time,
            'ending_location' => $alarmRaw->end_gps,
            'duration_minutes' => $durationMinutes,
            'duration_seconds' => $durationSeconds,
            'start_speed' => $alarmRaw->start_speed,
            'end_speed' => $alarmRaw->end_speed,
            'latitude_start' => $latStart,
            'longitude_start' => $longStart,
            'latitude_end' => $latEnd,
            'longitude_end' => $longEnd,
            'report_time' => $alarmRaw->report_time,
            'raw_json' => $alarmRaw->raw_json,
        ]);
        
        $processed++;
        echo "✅ Created: {$alarmRaw->guid} | {$alarmRaw->device_name} | {$alarmStatus} | {$durationMinutes}min\n";
        
    } catch (Exception $e) {
        $errors++;
        echo "❌ Error: {$alarmRaw->guid} - {$e->getMessage()}\n";
    }
}

echo "\n====================================\n";
echo "SUMMARY\n";
echo "====================================\n";
echo "Processed: {$processed}\n";
echo "Skipped: {$skipped}\n";
echo "Errors: {$errors}\n";

// Step 3: Show results
echo "\n[3/3] Showing updated data...\n\n";

$totalCount = IdleAlarm::count();
echo "Total idle_alarms now: {$totalCount}\n\n";

$samples = IdleAlarm::limit(10)
    ->get(['guid', 'serial_no', 'device_name', 'alarm_status', 'starting_time', 'ending_time', 'duration_minutes', 'start_speed', 'end_speed']);

echo "Sample data:\n";
echo "=".str_repeat("=", 150)."\n";

foreach ($samples as $sample) {
    echo sprintf(
        "%-40s | %-12s | %-20s | %-12s | %2.0f min | %.0f→%.0f km/h\n",
        substr($sample->guid, 0, 40),
        $sample->serial_no ?? 'N/A',
        substr($sample->device_name, 0, 20),
        $sample->alarm_status,
        $sample->duration_minutes,
        $sample->start_speed,
        $sample->end_speed
    );
}

echo "=".str_repeat("=", 150)."\n";

echo "\n====================================\n";
echo "DONE!\n";
echo "====================================\n\n";
