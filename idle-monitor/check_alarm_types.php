<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ALARM TYPE ANALYSIS ===\n\n";

// Check alarm types in alarm_raw
echo "--- Alarm Types in alarm_raw ---\n";
$types = DB::table('alarm_raw')
    ->select('alarm_type', DB::raw('COUNT(*) as count'), DB::raw('MAX(start_time) as latest'))
    ->groupBy('alarm_type')
    ->orderBy('count', 'desc')
    ->get();

foreach ($types as $type) {
    echo sprintf("  Type %s: %d records | Latest: %s\n", $type->alarm_type, $type->count, $type->latest);
}

// Check alarm_state distribution for type 32
echo "\n--- Alarm State for Type 32 ---\n";
$states = DB::table('alarm_raw')
    ->where('alarm_type', 32)
    ->select('alarm_state', DB::raw('COUNT(*) as count'))
    ->groupBy('alarm_state')
    ->get();

foreach ($states as $state) {
    echo sprintf("  State %s: %d records\n", $state->alarm_state ?? 'NULL', $state->count);
}

// Sample alarm_raw data for type 32
echo "\n--- Sample alarm_raw (Type 32) ---\n";
$samples = DB::table('alarm_raw')
    ->where('alarm_type', 32)
    ->orderBy('id', 'desc')
    ->limit(3)
    ->get(['guid', 'device_name', 'alarm_type', 'alarm_state', 'start_time', 'end_time', 'duration_seconds']);

foreach ($samples as $alarm) {
    echo sprintf(
        "  GUID: %s\n  Device: %s | Type: %s | State: %s\n  Start: %s | End: %s | Duration: %ds\n\n",
        $alarm->guid,
        $alarm->device_name,
        $alarm->alarm_type,
        $alarm->alarm_state ?? 'NULL',
        $alarm->start_time,
        $alarm->end_time,
        $alarm->duration_seconds
    );
}

// Check what ProcessIdleAlarmJob is looking for
echo "=== ProcessIdleAlarmJob Filter ===\n";
echo "Current filter: alarm_type = 100 (Idle)\n";
echo "Issue: All data has alarm_type = 32\n";
echo "Solution: Update ProcessIdleAlarmJob to use alarm_type = 32\n";

echo "\n=== END ANALYSIS ===\n";
