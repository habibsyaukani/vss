<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$alarmRaw = \App\Models\AlarmRaw::find(598871);

if (!$alarmRaw) {
    echo "Record 598871 not found!\n";
    exit;
}

echo "=== TESTING PROCESS FOR ALARM 598871 ===\n";
echo "GUID: " . $alarmRaw->guid . "\n";
echo "Device ID: " . $alarmRaw->device_id . "\n";
echo "Device Name: " . $alarmRaw->device_name . "\n";
echo "Start Time: " . $alarmRaw->start_time . "\n";
echo "End Time: " . $alarmRaw->end_time . "\n";

// Check if GUID already exists in idle_alarms
$existing = \App\Models\IdleAlarm::where('guid', $alarmRaw->guid)->first();
if ($existing) {
    echo "⚠️ GUID already exists in idle_alarms (ID: {$existing->id}, Start: {$existing->starting_time})\n";
} else {
    echo "✅ GUID is NEW (not in idle_alarms)\n";
}

// Check device serial_no mapping
$device = \App\Models\Device::where('device_id', $alarmRaw->device_id)->first();
echo "Device in DB: " . ($device ? "Found (Serial: {$device->serial_no})" : "Not found") . "\n";

// Test updateOrCreate
try {
    $idleData = [
        'serial_no'          => $device ? $device->serial_no : null,
        'device_id'          => $alarmRaw->device_id,
        'device_name'        => $alarmRaw->device_name,
        'alarm_type'         => 'Idle',
        'alarm_status'       => 'ALARM_END',
        'starting_time'      => $alarmRaw->start_time,
        'starting_location'  => $alarmRaw->start_gps,
        'ending_time'        => $alarmRaw->end_time,
        'ending_location'    => $alarmRaw->end_gps,
        'start_detail'       => $alarmRaw->start_detail ?: $alarmRaw->alarm_value,
        'end_detail'         => $alarmRaw->end_detail,
        'start_speed'        => (float)($alarmRaw->start_speed ?? 0),
        'end_speed'          => (float)($alarmRaw->end_speed ?? 0),
        'report_time'        => $alarmRaw->report_time,
        'duration_seconds'   => $alarmRaw->duration_seconds,
        'duration_minutes'   => ceil($alarmRaw->duration_seconds / 60),
    ];

    $saved = \App\Models\IdleAlarm::updateOrCreate(
        ['guid' => $alarmRaw->guid],
        $idleData
    );

    echo "✅ SUCCESS: IdleAlarm created/updated! ID: {$saved->id}, Starting Time: {$saved->starting_time}\n";

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
