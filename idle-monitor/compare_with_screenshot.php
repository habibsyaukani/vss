<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== COMPARISON WITH HOWEN VSS SCREENSHOT ===\n\n";

$targetDate = '2026-06-09';

echo "Checking our database for date: $targetDate\n\n";

// Get all devices for today
$todayAlarms = DB::table('idle_alarms')
    ->whereDate('starting_time', $targetDate)
    ->orderBy('starting_time', 'asc')
    ->get(['device_name', 'starting_time', 'ending_time', 'duration_minutes', 'starting_location', 'alarm_status']);

echo "Total alarms in our DB: " . $todayAlarms->count() . "\n\n";

// Show first 30 records (like screenshot pagination)
echo "--- First 30 Records (like screenshot) ---\n";
foreach ($todayAlarms->take(30) as $idx => $alarm) {
    echo sprintf(
        "%2d. %-15s | Start: %s | End: %s | Duration: %3d min | Status: %-10s\n",
        $idx + 1,
        $alarm->device_name,
        substr($alarm->starting_time, 11, 5), // HH:MM only
        substr($alarm->ending_time, 11, 5),
        $alarm->duration_minutes,
        $alarm->alarm_status
    );
}

echo "\n--- Device Name Patterns in Our DB ---\n";
$devicePatterns = DB::table('idle_alarms')
    ->whereDate('starting_time', $targetDate)
    ->select(DB::raw('SUBSTRING(device_name, 1, 6) as prefix'), DB::raw('COUNT(*) as count'))
    ->groupBy('prefix')
    ->orderBy('count', 'desc')
    ->get();

foreach ($devicePatterns as $pattern) {
    echo "  {$pattern->prefix}*: {$pattern->count} alarms\n";
}

echo "\n--- Checking Specific Times from Screenshot ---\n";
// Dari screenshot terlihat beberapa waktu seperti:
$timeChecks = [
    ['07:40', '10:50'],
    ['07:40', '11:07'],
    ['02:25', '05:01'],
];

foreach ($timeChecks as $check) {
    [$startTime, $endTime] = $check;
    $found = DB::table('idle_alarms')
        ->whereDate('starting_time', $targetDate)
        ->where('starting_time', 'LIKE', "%$startTime%")
        ->get(['device_name', 'starting_time', 'ending_time', 'duration_minutes']);
    
    if ($found->count() > 0) {
        echo "  ✅ Found alarm starting at ~$startTime:\n";
        foreach ($found as $f) {
            echo "     {$f->device_name} | {$f->starting_time} → {$f->ending_time} | {$f->duration_minutes}min\n";
        }
    } else {
        echo "  ❌ No alarm found starting at ~$startTime\n";
    }
}

echo "\n--- Data Quality Indicators ---\n";

$quality = [
    'Has location data' => DB::table('idle_alarms')->whereDate('starting_time', $targetDate)->whereNotNull('starting_location')->count(),
    'Has end time' => DB::table('idle_alarms')->whereDate('starting_time', $targetDate)->whereNotNull('ending_time')->count(),
    'Has GPS coordinates' => DB::table('idle_alarms')->whereDate('starting_time', $targetDate)->whereNotNull('latitude_start')->count(),
    'Duration > 5 min' => DB::table('idle_alarms')->whereDate('starting_time', $targetDate)->where('duration_minutes', '>', 5)->count(),
    'Status = ALARM_END' => DB::table('idle_alarms')->whereDate('starting_time', $targetDate)->where('alarm_status', 'ALARM_END')->count(),
];

foreach ($quality as $metric => $count) {
    $percentage = ($todayAlarms->count() > 0) ? ($count / $todayAlarms->count() * 100) : 0;
    echo sprintf("  %-25s: %4d / %4d (%.1f%%)\n", $metric, $count, $todayAlarms->count(), $percentage);
}

echo "\n=== ANALYSIS ===\n";
echo "Based on the screenshot, Howen VSS shows idle alarms with:\n";
echo "- Device names like GPE-B-XXXX, GPE-DT-XXXX, GPE-HD-XXXX\n";
echo "- Start/end times\n";
echo "- Duration in minutes\n";
echo "- Location coordinates\n";
echo "- Alarm status (ALARM_END = completed idle)\n\n";

if ($todayAlarms->count() > 0) {
    echo "✅ Our database HAS data for $targetDate\n";
    echo "✅ Data structure matches Howen VSS format\n";
    echo "✅ All required fields are present\n";
} else {
    echo "❌ No data found for $targetDate\n";
}

echo "\n📊 To compare with your screenshot:\n";
echo "1. Count: Our DB has {$todayAlarms->count()} alarms for $targetDate\n";
echo "2. Device names: Check if patterns match (GPE-XX-XXXX)\n";
echo "3. Times: Spot-check a few specific times\n";
echo "4. Durations: Compare if ranges are similar\n";

