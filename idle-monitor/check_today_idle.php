<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING IDLE DATA FOR TODAY ===\n\n";

$today = date('Y-m-d');
echo "Today's date: $today\n\n";

// Count idle alarms today
$count = \App\Models\IdleAlarm::whereDate('starting_time', $today)->count();
echo "Idle alarm count today: $count\n\n";

if ($count > 0) {
    // Sample record
    $sample = \App\Models\IdleAlarm::whereDate('starting_time', $today)->first();
    echo "Sample record:\n";
    echo "  Device: " . $sample->device_name . "\n";
    echo "  Start: " . $sample->starting_time . "\n";
    echo "  Duration: " . $sample->duration_minutes . " minutes\n\n";
} else {
    echo "❌ NO IDLE ALARM DATA FOR TODAY\n\n";
}

// Check most recent idle alarm
$recent = \App\Models\IdleAlarm::orderBy('starting_time', 'desc')->first();
if ($recent) {
    echo "Most recent idle alarm:\n";
    echo "  Device: " . $recent->device_name . "\n";
    echo "  Date: " . $recent->starting_time . "\n";
    echo "  Duration: " . $recent->duration_minutes . " minutes\n\n";
}

// Check GPS track data for today
$gpsCount = \App\Models\GpsTrack::whereDate('gps_time', $today)->count();
echo "GPS track count today: $gpsCount\n";

if ($gpsCount > 0) {
    $speedStats = \App\Models\GpsTrack::whereDate('gps_time', $today)
        ->where('speed', '>', 0)
        ->selectRaw('MAX(speed) as max_speed, AVG(speed) as avg_speed')
        ->first();
    
    echo "  Max speed today: " . round($speedStats->max_speed ?? 0, 1) . " km/h\n";
    echo "  Avg speed today: " . round($speedStats->avg_speed ?? 0, 1) . " km/h\n\n";
}

// Check if there's data from other dates
$totalIdle = \App\Models\IdleAlarm::count();
$totalGps = \App\Models\GpsTrack::count();

echo "\n=== TOTAL DATA IN DATABASE ===\n";
echo "Total idle alarms: $totalIdle\n";
echo "Total GPS tracks: $totalGps\n";

