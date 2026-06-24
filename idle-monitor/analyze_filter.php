<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$raw = \App\Models\AlarmRaw::where('alarm_type', 32)->get();
echo "=== IDLE ALARM (Type 32) FILTER ANALYSIS ===\n\n";
echo "Total Type 32: " . $raw->count() . "\n";

$validCount = 0;
$noEndTime = 0;
$endSpeedZero = 0;
$startSpeedNotZero = 0;
$durationZero = 0;
$alarmStateNotZero = 0;

foreach ($raw as $r) {
    $startSpeed = (float)($r->start_speed ?? 0);
    $endSpeed = (float)($r->end_speed ?? 0);
    $alarmState = (int)($r->alarm_state ?? 0);
    $startTime = \Carbon\Carbon::parse($r->start_time);
    $endTime = \Carbon\Carbon::parse($r->end_time ?? now());
    $durationSeconds = $endTime->diffInSeconds($startTime);
    
    if ($alarmState != 0) $alarmStateNotZero++;
    elseif (empty($r->end_time)) $noEndTime++;
    elseif ($endSpeed == 0 || $endSpeed < 0) $endSpeedZero++;
    elseif ($startSpeed != 0) $startSpeedNotZero++;
    elseif ($durationSeconds == 0) $durationZero++;
    else $validCount++;
}

echo "\n📊 BREAKDOWN:\n";
echo "✅ Valid (masuk idle_alarms): " . $validCount . "\n";
echo "❌ Alarm state != 0: " . $alarmStateNotZero . "\n";
echo "❌ No end_time: " . $noEndTime . "\n";
echo "❌ End speed = 0: " . $endSpeedZero . "\n";
echo "❌ Start speed != 0: " . $startSpeedNotZero . "\n";
echo "❌ Duration = 0: " . $durationZero . "\n";
echo "\nTotal Rejected: " . ($noEndTime + $endSpeedZero + $startSpeedNotZero + $durationZero + $alarmStateNotZero) . "\n";
echo "\n";
