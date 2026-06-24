<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$today = '2026-06-23';

echo "=== DATABASE STATUS ===\n";
echo "Raw Alarms today   : " . \App\Models\AlarmRaw::where('start_time', '>=', $today . ' 00:00:00')->count() . "\n";
echo "Idle Alarms today  : " . \App\Models\IdleAlarm::whereDate('starting_time', $today)->count() . "\n";
echo "\n=== LAST 10 ALARM_RAW RECORDS (by id) ===\n";
$rows = \App\Models\AlarmRaw::orderBy('id','desc')->limit(10)->get(['id','device_name','alarm_type','start_time']);
foreach ($rows as $r) {
    echo "id={$r->id} | {$r->device_name} | type={$r->alarm_type} | {$r->start_time}\n";
}

echo "\n=== LAST 5 IDLE ALARMS ===\n";
$idle = \App\Models\IdleAlarm::orderBy('id','desc')->limit(5)->get(['id','device_name','starting_time','duration_minutes']);
foreach ($idle as $i) {
    echo "id={$i->id} | {$i->device_name} | {$i->starting_time} | dur={$i->duration_minutes}min\n";
}

echo "\n=== FAILED JOBS ===\n";
echo "Failed jobs count  : " . \Illuminate\Support\Facades\DB::table('failed_jobs')->count() . "\n";
$failed = \Illuminate\Support\Facades\DB::table('failed_jobs')->latest('failed_at')->first();
if ($failed) {
    echo "Last failed at: " . $failed->failed_at . "\n";
    echo "Exception: " . substr($failed->exception, 0, 300) . "\n";
}
