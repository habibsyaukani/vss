<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ALARM STATE MAPPING VERIFICATION ===\n\n";

// Check state 0 samples
echo "--- State 0 Samples (Job thinks this is ALARM_END) ---\n";
$state0 = DB::table('alarm_raw')
    ->where('alarm_type', 32)
    ->where('alarm_state', 0)
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get(['guid', 'device_name', 'alarm_state', 'start_time', 'end_time', 'start_speed', 'end_speed', 'duration_seconds', 'end_detail']);

foreach ($state0 as $alarm) {
    echo sprintf(
        "  Device: %s | State: %s\n  Start: %s | End: %s\n  Speed: %s → %s km/h | Duration: %ds\n  End Detail: %s\n\n",
        $alarm->device_name,
        $alarm->alarm_state,
        $alarm->start_time,
        $alarm->end_time ?? 'NULL',
        $alarm->start_speed ?? '?',
        $alarm->end_speed ?? 'NULL',
        $alarm->duration_seconds,
        substr($alarm->end_detail ?? 'N/A', 0, 100)
    );
}

// Check state 1 samples
echo "\n--- State 1 Samples (Job thinks this is ALARMING) ---\n";
$state1 = DB::table('alarm_raw')
    ->where('alarm_type', 32)
    ->where('alarm_state', 1)
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get(['guid', 'device_name', 'alarm_state', 'start_time', 'end_time', 'start_speed', 'end_speed', 'duration_seconds', 'end_detail']);

foreach ($state1 as $alarm) {
    echo sprintf(
        "  Device: %s | State: %s\n  Start: %s | End: %s\n  Speed: %s → %s km/h | Duration: %ds\n  End Detail: %s\n\n",
        $alarm->device_name,
        $alarm->alarm_state,
        $alarm->start_time,
        $alarm->end_time ?? 'NULL',
        $alarm->start_speed ?? '?',
        $alarm->end_speed ?? 'NULL',
        $alarm->duration_seconds,
        substr($alarm->end_detail ?? 'N/A', 0, 100)
    );
}

// Check recently created idle_alarms
echo "\n--- Recent Idle Alarms (Successfully Processed) ---\n";
$recentIdle = DB::table('idle_alarms')
    ->orderBy('id', 'desc')
    ->limit(3)
    ->get(['guid', 'device_name', 'alarm_status', 'alarm_state', 'starting_time', 'ending_time', 'start_speed', 'end_speed', 'duration_minutes']);

foreach ($recentIdle as $idle) {
    echo sprintf(
        "  Device: %s | Status: %s | State: %s\n  Start: %s | End: %s\n  Speed: %s → %s km/h | Duration: %d min\n\n",
        $idle->device_name,
        $idle->alarm_status ?? 'N/A',
        $idle->alarm_state ?? 'N/A',
        $idle->starting_time,
        $idle->ending_time,
        $idle->start_speed,
        $idle->end_speed,
        $idle->duration_minutes
    );
}

echo "=== ANALYSIS ===\n";
echo "If State 0 has end_time = NULL and end_speed = NULL → State 0 = ALARMING (still idle)\n";
echo "If State 1 has end_time set and end_speed > 0 → State 1 = ALARM_END (idle finished)\n";
echo "\n";

