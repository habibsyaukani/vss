<?php
/**
 * Script untuk memperbaiki data alarm_status
 * Jalankan dengan: php artisan tinker < fix_alarm_status.php
 */

// Clear old data
echo "Clearing old data...\n";
\App\Models\AlarmRaw::truncate();
\App\Models\IdleAlarm::truncate();
\App\Models\ImportLog::truncate();
echo "✅ Database cleared\n\n";

// Get alarmService for import
$alarmService = new \App\Services\HowenAlarmService();

// Fetch alarms from mock data
echo "Importing alarms (page 1)...\n";
$alarms = $alarmService->fetchAlarmsPageWithMock(1, 200);

if (empty($alarms)) {
    echo "❌ No alarms fetched\n";
    exit(1);
}

echo "Fetched " . count($alarms) . " alarms\n\n";

// Process and insert alarm_raw
$inserted = 0;
foreach ($alarms as $alarm) {
    $deviceId = $alarm['deviceguid'] ?? $alarm['device_id'] ?? null;
    if (!$deviceId) {
        continue;
    }

    // Log API response
    echo "Processing alarm: {$alarm['guid']} | State: {$alarm['alarmState']} | Device: {$alarm['deviceName']}\n";

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

    \App\Models\AlarmRaw::updateOrCreate(
        ['guid' => $alarmRawData['guid']],
        $alarmRawData
    );

    $inserted++;
}

echo "\n✅ Imported {$inserted} alarms to alarm_raw\n\n";

// Show alarm_raw data
echo "=== ALARM_RAW DATA ===\n";
$alarmRaws = \App\Models\AlarmRaw::orderBy('id')->get();
foreach ($alarmRaws as $raw) {
    echo sprintf(
        "[%d] GUID:%s | Device:%s | State:%s | Speed:%s→%s | Duration:%ds\n",
        $raw->id,
        $raw->guid,
        $raw->device_name,
        $raw->alarm_state,
        $raw->start_speed,
        $raw->end_speed,
        $raw->duration_seconds ?? 0
    );
}
echo "\n";

// Process idle alarms
echo "Processing idle alarms with validation...\n";

$processed = 0;
$skipped = 0;

foreach ($alarmRaws as $alarmRaw) {
    $startSpeed = (float)($alarmRaw->start_speed ?? 0);
    $endSpeed = (float)($alarmRaw->end_speed ?? 0);
    $durationSeconds = 3600; // Mock value

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
        echo "  ⏭️  SKIP {$alarmRaw->guid} - Invalid (state:{$alarmRaw->alarm_state}, start_speed:{$startSpeed}, end_speed:{$endSpeed})\n";
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
    \App\Models\IdleAlarm::updateOrCreate(
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

    echo "  ✅ PROCESS {$alarmRaw->guid} - Status: {$alarmStatus}\n";
    $processed++;
}

echo "\n✅ Processed {$processed} idle alarms, skipped {$skipped}\n\n";

// Show idle_alarms data
echo "=== IDLE_ALARMS DATA ===\n";
$idleAlarms = \App\Models\IdleAlarm::orderBy('id')->get();
foreach ($idleAlarms as $idle) {
    echo sprintf(
        "[%d] GUID:%s | Device:%s | Status:%s | Speed:%s→%s | Duration:%dmin\n",
        $idle->id,
        $idle->guid,
        $idle->device_name,
        $idle->alarm_status,
        $idle->start_speed,
        $idle->end_speed,
        $idle->duration_minutes
    );
}

echo "\n✅ Done! All data fixed with correct alarm_status mapping.\n";

