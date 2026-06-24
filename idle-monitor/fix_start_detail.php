<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AlarmRaw;
use App\Models\IdleAlarm;

// 1. Fix AlarmRaw
$alarms = AlarmRaw::where('alarm_type', 32)->get();
$countRaw = 0;
foreach($alarms as $alarm) {
    $json = json_decode($alarm->raw_json, true);
    // Cari di lowercase alarmvalue
    $sd = $json['alarmvalue'] ?? $json['alarmValue'] ?? null;
    if ($sd && empty($alarm->start_detail)) {
        $alarm->start_detail = $sd;
        $alarm->save();
        $countRaw++;
    }
}
echo "Updated alarm_raw start_detail: $countRaw\n";

// 2. Fix IdleAlarm
$idles = IdleAlarm::all();
$countIdle = 0;
foreach($idles as $idle) {
    $raw = AlarmRaw::where('guid', $idle->guid)->first();
    if ($raw && !empty($raw->start_detail) && empty($idle->start_detail)) {
        $idle->start_detail = $raw->start_detail;
        $idle->save();
        $countIdle++;
    }
}
echo "Updated idle_alarms start_detail: $countIdle\n";
