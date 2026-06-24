<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\IdleAlarm;
use App\Models\AlarmRaw;
use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════════════\n";
echo "  CHECK: Start Detail Column\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Check idle_alarms table
echo "📋 Sample data from idle_alarms (first 10 records):\n";
echo str_repeat('-', 120) . "\n";
printf("%-15s | %-20s | %-35s | %s\n", "Device Name", "Starting Time", "Start Detail", "End Detail");
echo str_repeat('-', 120) . "\n";

$idleAlarms = IdleAlarm::orderBy('starting_time', 'desc')
    ->limit(10)
    ->get(['device_name', 'starting_time', 'start_detail', 'end_detail']);

foreach ($idleAlarms as $alarm) {
    $startDetail = $alarm->start_detail ?: '(NULL/EMPTY)';
    $endDetail = $alarm->end_detail ?: '(NULL/EMPTY)';
    
    printf("%-15s | %-20s | %-35s | %s\n", 
        substr($alarm->device_name, 0, 15),
        $alarm->starting_time,
        substr($startDetail, 0, 35),
        substr($endDetail, 0, 30)
    );
}

echo str_repeat('-', 120) . "\n\n";

// Count empty start_detail
$emptyCount = IdleAlarm::whereNull('start_detail')
    ->orWhere('start_detail', '')
    ->count();

$totalCount = IdleAlarm::count();

echo "📊 Statistics:\n";
echo "   Total idle_alarms: {$totalCount}\n";
echo "   Empty start_detail: {$emptyCount}\n";
echo "   Percentage empty: " . round(($emptyCount / $totalCount) * 100, 2) . "%\n\n";

// Check alarm_raw table for comparison
echo "📋 Sample data from alarm_raw (first 5 records):\n";
echo str_repeat('-', 120) . "\n";
printf("%-15s | %-20s | %-35s | %s\n", "Device Name", "Start Time", "Start Detail", "End Detail");
echo str_repeat('-', 120) . "\n";

$alarmRaws = AlarmRaw::orderBy('start_time', 'desc')
    ->limit(5)
    ->get(['device_name', 'start_time', 'start_detail', 'end_detail']);

foreach ($alarmRaws as $raw) {
    $startDetail = $raw->start_detail ?: '(NULL/EMPTY)';
    $endDetail = $raw->end_detail ?: '(NULL/EMPTY)';
    
    printf("%-15s | %-20s | %-35s | %s\n", 
        substr($raw->device_name, 0, 15),
        $raw->start_time,
        substr($startDetail, 0, 35),
        substr($endDetail, 0, 30)
    );
}

echo str_repeat('-', 120) . "\n\n";

// Check raw JSON data from alarm_raw
echo "🔍 Checking raw JSON data from alarm_raw (first record):\n";
echo str_repeat('-', 120) . "\n";

$firstRaw = AlarmRaw::orderBy('start_time', 'desc')->first();

if ($firstRaw) {
    echo "GUID: {$firstRaw->guid}\n";
    echo "Device: {$firstRaw->device_name}\n\n";
    
    if ($firstRaw->raw_json) {
        $json = json_decode($firstRaw->raw_json, true);
        
        echo "JSON Fields Available:\n";
        foreach ($json as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                echo "   - {$key}: " . substr($value, 0, 50) . "\n";
            } else {
                echo "   - {$key}: " . gettype($value) . "\n";
            }
        }
        
        echo "\n";
        echo "🔍 Looking for 'detail' or 'startDetail' fields:\n";
        
        // Check for potential detail fields
        $detailFields = ['detail', 'startDetail', 'start_detail', 'alarmDetail', 'description', 'remark'];
        foreach ($detailFields as $field) {
            if (isset($json[$field])) {
                echo "   ✅ Found '{$field}': {$json[$field]}\n";
            } else {
                echo "   ❌ Not found: '{$field}'\n";
            }
        }
    } else {
        echo "❌ No raw_json data available\n";
    }
} else {
    echo "❌ No alarm_raw records found\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "INVESTIGATION COMPLETE\n";
echo "═══════════════════════════════════════════════════════════════\n";

