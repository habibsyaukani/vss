<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DATA ACCURACY VERIFICATION ===\n\n";

// Dari screenshot terlihat tanggal 09 Jun 2026
$targetDate = '2026-06-09';

echo "--- Checking data for: $targetDate ---\n\n";

// 1. Total idle alarms for today
$todayTotal = DB::table('idle_alarms')
    ->whereDate('starting_time', $targetDate)
    ->count();
echo "Total idle alarms on $targetDate: $todayTotal\n";

// 2. Sample data yang terlihat di screenshot
echo "\n--- Sample Devices from Screenshot (for verification) ---\n";
$screenshotDevices = [
    'GPE-B-8322',
    'GPE-DT-801', 
    'GPE-B-8312',
    'GPE-DT-803',
    'GPE-HD-822',
    'GPE-HD-800'
];

foreach ($screenshotDevices as $deviceName) {
    $count = DB::table('idle_alarms')
        ->where('device_name', 'LIKE', "%{$deviceName}%")
        ->whereDate('starting_time', $targetDate)
        ->count();
    
    if ($count > 0) {
        $latest = DB::table('idle_alarms')
            ->where('device_name', 'LIKE', "%{$deviceName}%")
            ->whereDate('starting_time', $targetDate)
            ->orderBy('starting_time', 'desc')
            ->first(['starting_time', 'ending_time', 'duration_minutes', 'alarm_status']);
        
        echo sprintf(
            "  ✅ %s: %d records | Latest: %s | Duration: %d min | Status: %s\n",
            $deviceName,
            $count,
            $latest->starting_time ?? 'N/A',
            $latest->duration_minutes ?? 0,
            $latest->alarm_status ?? 'N/A'
        );
    } else {
        echo "  ❌ $deviceName: NOT FOUND in our data\n";
    }
}

// 3. Check alarm characteristics
echo "\n--- Alarm Characteristics Analysis ---\n";

$stats = DB::table('idle_alarms')
    ->whereDate('starting_time', $targetDate)
    ->selectRaw('
        COUNT(*) as total,
        MIN(duration_minutes) as min_duration,
        MAX(duration_minutes) as max_duration,
        AVG(duration_minutes) as avg_duration,
        COUNT(DISTINCT device_name) as unique_devices
    ')
    ->first();

echo sprintf("  Total alarms: %d\n", $stats->total);
echo sprintf("  Unique devices: %d\n", $stats->unique_devices);
echo sprintf("  Duration range: %d - %d minutes\n", $stats->min_duration, $stats->max_duration);
echo sprintf("  Average duration: %.2f minutes\n", $stats->avg_duration);

// 4. Alarm status distribution
echo "\n--- Alarm Status Distribution ---\n";
$statusDist = DB::table('idle_alarms')
    ->whereDate('starting_time', $targetDate)
    ->select('alarm_status', DB::raw('COUNT(*) as count'))
    ->groupBy('alarm_status')
    ->get();

foreach ($statusDist as $status) {
    echo sprintf("  %s: %d alarms\n", $status->alarm_status ?? 'NULL', $status->count);
}

// 5. Recent 10 alarms
echo "\n--- Recent 10 Alarms from $targetDate ---\n";
$recent = DB::table('idle_alarms')
    ->whereDate('starting_time', $targetDate)
    ->orderBy('starting_time', 'desc')
    ->limit(10)
    ->get(['device_name', 'starting_time', 'ending_time', 'duration_minutes', 'starting_location', 'alarm_status']);

foreach ($recent as $alarm) {
    echo sprintf(
        "  %s | %s → %s | %d min | Status: %s\n  Location: %s\n\n",
        $alarm->device_name,
        $alarm->starting_time,
        $alarm->ending_time,
        $alarm->duration_minutes,
        $alarm->alarm_status,
        substr($alarm->starting_location ?? 'N/A', 0, 30)
    );
}

// 6. Data completeness check
echo "--- Data Completeness Check ---\n";
$incomplete = DB::table('idle_alarms')
    ->whereDate('starting_time', $targetDate)
    ->where(function($q) {
        $q->whereNull('ending_time')
          ->orWhereNull('ending_location')
          ->orWhere('duration_minutes', '<=', 0)
          ->orWhere('end_speed', '<=', 0);
    })
    ->count();

if ($incomplete > 0) {
    echo "  ⚠️ WARNING: $incomplete incomplete records found\n";
    echo "  (These might be ongoing idle alarms)\n";
} else {
    echo "  ✅ All records are complete\n";
}

// 7. Compare with alarm_raw
echo "\n--- Data Pipeline Check ---\n";
$rawCount = DB::table('alarm_raw')
    ->where('alarm_type', 32)
    ->where('alarm_state', 0)
    ->whereDate('start_time', $targetDate)
    ->count();

$processedCount = DB::table('idle_alarms')
    ->whereDate('starting_time', $targetDate)
    ->count();

echo "  alarm_raw (type 32, state 0): $rawCount records\n";
echo "  idle_alarms (processed): $processedCount records\n";

if ($processedCount < $rawCount) {
    $notProcessed = $rawCount - $processedCount;
    echo "  ⚠️ Note: $notProcessed alarms from alarm_raw not in idle_alarms\n";
    echo "  (Likely due to validation filters: end_speed=0, duration too short, etc)\n";
} else {
    echo "  ✅ Processing rate looks good\n";
}

echo "\n=== VERIFICATION COMPLETE ===\n";
echo "\nTo verify with Howen VSS:\n";
echo "1. Compare total count with screenshot\n";
echo "2. Spot-check device names\n";
echo "3. Compare duration values\n";
echo "4. Check if alarm statuses match\n";

