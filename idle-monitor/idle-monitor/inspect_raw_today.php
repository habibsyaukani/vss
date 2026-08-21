<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$today = date('Y-m-d');
$todayStart = "$today 00:00:00";
$todayEnd   = "$today 23:59:59";

echo "=========================================\n";
echo "🔍 INSPEKSI RAW ALARM HARI INI ($today)\n";
echo "=========================================\n\n";

$rawToday = \App\Models\AlarmRaw::where('start_time', '>=', $todayStart)
    ->where('start_time', '<=', $todayEnd)
    ->get();

echo "Total raw alarm hari ini: " . $rawToday->count() . "\n\n";

// Breakdown by alarm_type
$byType = $rawToday->groupBy('alarm_type');
echo "Breakdown berdasarkan alarm_type:\n";
foreach ($byType as $type => $group) {
    echo "  - alarm_type [{$type}]: " . $group->count() . " records\n";
}

echo "\nBreakdown alarm_type 32 (Idle Alarm) berdasarkan alarm_state:\n";
$type32 = $rawToday->where('alarm_type', 32);
$byState = $type32->groupBy('alarm_state');
foreach ($byState as $state => $group) {
    $processedCount = $group->where('is_processed', 1)->count();
    $unprocessedCount = $group->where('is_processed', 0)->count();
    echo "  - alarm_state [{$state}]: " . $group->count() . " records (Processed: {$processedCount}, Unprocessed: {$unprocessedCount})\n";
}

echo "\nSample 3 Raw Alarms (Type 32):\n";
foreach ($type32->take(3) as $raw) {
    echo "  • ID: {$raw->id} | Device: {$raw->device_name} | Type: {$raw->alarm_type} | State: {$raw->alarm_state} | Processed: {$raw->is_processed} | Value: {$raw->alarm_value} | Start: {$raw->start_time}\n";
}

echo "\n=========================================\n";
