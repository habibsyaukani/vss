<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\IdleAlarm;
use App\Models\AlarmRaw;
use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════════════\n";
echo "  CHECK: Idle Alarm Start Detail (alarm_type = Idle)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Check idle_alarms WHERE alarm_type = Idle
echo "📋 Idle Alarms (first 10):\n";
echo str_repeat('-', 140) . "\n";
printf("%-15s | %-20s | %-60s | %s\n", "Device", "Starting Time", "Start Detail", "End Detail");
echo str_repeat('-', 140) . "\n";

$idleAlarms = IdleAlarm::where('alarm_type', 'Idle')
    ->orderBy('starting_time', 'desc')
    ->limit(10)
    ->get();

$withStartDetail = 0;
$withoutStartDetail = 0;

foreach ($idleAlarms as $alarm) {
    $startDetail = $alarm->start_detail ?: '(EMPTY)';
    $endDetail = $alarm->end_detail ?: '(EMPTY)';
    
    if ($alarm->start_detail) {
        $withStartDetail++;
    } else {
        $withoutStartDetail++;
    }
    
    printf("%-15s | %-20s | %-60s | %s\n",
        substr($alarm->device_name, 0, 15),
        $alarm->starting_time,
        substr($startDetail, 0, 60),
        substr($endDetail, 0, 40)
    );
}

echo str_repeat('-', 140) . "\n\n";

// Statistics
$totalIdle = IdleAlarm::where('alarm_type', 'Idle')->count();
$emptyStartDetail = IdleAlarm::where('alarm_type', 'Idle')
    ->where(function($q) {
        $q->whereNull('start_detail')->orWhere('start_detail', '');
    })
    ->count();

echo "📊 Statistics for alarm_type='Idle':\n";
echo "   Total Idle alarms: {$totalIdle}\n";
echo "   Empty start_detail: {$emptyStartDetail}\n";
echo "   With start_detail: " . ($totalIdle - $emptyStartDetail) . "\n";
echo "   Percentage empty: " . round(($emptyStartDetail / $totalIdle) * 100, 2) . "%\n\n";

// Check corresponding alarm_raw data
echo "🔍 Checking corresponding alarm_raw data:\n";
echo str_repeat('-', 140) . "\n";

$sampleIdleAlarm = IdleAlarm::where('alarm_type', 'Idle')
    ->whereNull('start_detail')
    ->orderBy('starting_time', 'desc')
    ->first();

if ($sampleIdleAlarm) {
    echo "Sample Idle Alarm with NULL start_detail:\n";
    echo "   GUID: {$sampleIdleAlarm->guid}\n";
    echo "   Device: {$sampleIdleAlarm->device_name}\n";
    echo "   Starting Time: {$sampleIdleAlarm->starting_time}\n\n";
    
    $alarmRaw = AlarmRaw::where('guid', $sampleIdleAlarm->guid)->first();
    
    if ($alarmRaw) {
        echo "✅ Found in alarm_raw:\n";
        echo "   start_detail (DB): " . ($alarmRaw->start_detail ?: '(EMPTY)') . "\n";
        echo "   end_detail (DB): " . ($alarmRaw->end_detail ?: '(EMPTY)') . "\n\n";
        
        if ($alarmRaw->raw_json) {
            $json = json_decode($alarmRaw->raw_json, true);
            echo "   Raw JSON 'alarmvalue': " . ($json['alarmvalue'] ?? '(NOT FOUND)') . "\n";
            echo "   Raw JSON 'endDetail': " . ($json['endDetail'] ?? '(NOT FOUND)') . "\n";
        }
    } else {
        echo "❌ NOT found in alarm_raw\n";
    }
}

echo "\n═══════════════════════════════════════════════════════════════\n";

