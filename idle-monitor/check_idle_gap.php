<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AlarmRaw;
use App\Models\IdleAlarm;

$today = '2026-06-05';

// Cek alarm_raw hari ini
$rawToday = AlarmRaw::whereDate('created_at', $today)->count();
$rawIdleToday = AlarmRaw::whereDate('created_at', $today)->where('alarm_type', 100)->count();
$rawSample = AlarmRaw::whereDate('created_at', $today)->first();

echo "=== alarm_raw hari ini ({$today}) ===\n";
echo "Total alarm_raw: {$rawToday}\n";
echo "Total alarm_type=100 (idle): {$rawIdleToday}\n\n";

if ($rawSample) {
    echo "Sample record:\n";
    echo "  guid: " . $rawSample->guid . "\n";
    echo "  alarm_type: " . $rawSample->alarm_type . "\n";
    echo "  alarm_state: " . $rawSample->alarm_state . "\n";
    echo "  start_time: " . $rawSample->start_time . "\n";
    echo "  end_time: " . $rawSample->end_time . "\n";
    echo "  duration_seconds: " . $rawSample->duration_seconds . "\n";
    echo "  end_detail: " . $rawSample->end_detail . "\n";
}

echo "\n=== idle_alarms hari ini ({$today}) ===\n";
$idleToday = IdleAlarm::whereDate('created_at', $today)->count();
$idleSample = IdleAlarm::whereDate('created_at', $today)->first();
echo "Total idle_alarms: {$idleToday}\n";

// Cek by starting_time
$idleByStarting = IdleAlarm::whereDate('starting_time', $today)->count();
echo "Total idle_alarms by starting_time: {$idleByStarting}\n\n";

// Cek ProcessIdleAlarmJob - cek logic whereNotExists
echo "=== CHECK SYNC GAP ===\n";
$unprocessed = AlarmRaw::where('alarm_type', 100)
    ->whereNotExists(function($q) {
        $q->select(\DB::raw(1))
          ->from('idle_alarms')
          ->whereColumn('idle_alarms.guid', 'alarm_raw.guid');
    })
    ->count();
echo "alarm_raw type=100 yang BELUM masuk idle_alarms: {$unprocessed}\n";

$unprocessedSample = AlarmRaw::where('alarm_type', 100)
    ->whereNotExists(function($q) {
        $q->select(\DB::raw(1))
          ->from('idle_alarms')
          ->whereColumn('idle_alarms.guid', 'alarm_raw.guid');
    })
    ->first();

if ($unprocessedSample) {
    echo "\nSample unprocessed:\n";
    echo "  guid: " . $unprocessedSample->guid . "\n";
    echo "  alarm_type: " . $unprocessedSample->alarm_type . "\n";
    echo "  alarm_state: " . $unprocessedSample->alarm_state . "\n";
    echo "  start_time: " . $unprocessedSample->start_time . "\n";
    echo "  end_time: " . $unprocessedSample->end_time . "\n";
    echo "  end_detail: " . $unprocessedSample->end_detail . "\n";
}
