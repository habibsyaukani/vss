<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Check alarm_state distribution for type 32
echo "=== alarm_state untuk alarm_type=32 ===\n";
$states = DB::table('alarm_raw')
    ->where('alarm_type', 32)
    ->selectRaw('alarm_state, count(*) as count')
    ->groupBy('alarm_state')
    ->get();
foreach($states as $s) {
    echo "alarm_state={$s->alarm_state} : {$s->count} data\n";
}

// Check how many type 32 with alarm_state=0 not yet in idle_alarms
echo "\n=== Type 32, state=0, belum di idle_alarms ===\n";
$pending = DB::table('alarm_raw')
    ->where('alarm_type', 32)
    ->where('alarm_state', 0)
    ->whereNotExists(function($q) {
        $q->select(DB::raw(1))->from('idle_alarms')->whereRaw('idle_alarms.guid = alarm_raw.guid');
    })
    ->selectRaw('count(*) as total, sum(case when end_speed > 0 then 1 else 0 end) as has_end_speed, sum(case when start_speed = 0 then 1 else 0 end) as start_zero')
    ->first();
echo "Total pending: {$pending->total}\n";
echo "Yang end_speed > 0: {$pending->has_end_speed}\n";
echo "Yang start_speed = 0: {$pending->start_zero}\n";

// Sample 5 records type 32
echo "\n=== Sample 5 alarm_type=32 ===\n";
$samples = DB::table('alarm_raw')
    ->where('alarm_type', 32)
    ->whereNotExists(function($q) {
        $q->select(DB::raw(1))->from('idle_alarms')->whereRaw('idle_alarms.guid = alarm_raw.guid');
    })
    ->select('guid','device_name','alarm_state','alarm_type','start_time','end_time','start_speed','end_speed','start_detail','end_detail')
    ->limit(5)
    ->get();
foreach($samples as $s) {
    echo "---\n";
    echo "device={$s->device_name} | state={$s->alarm_state} | start_spd={$s->start_speed} | end_spd={$s->end_speed}\n";
    echo "start_time={$s->start_time} | end_time={$s->end_time}\n";
    echo "start_detail={$s->start_detail}\n";
    echo "end_detail={$s->end_detail}\n";
}

// Check dates in alarm_raw type 32
echo "\n=== Tanggal data alarm_type=32 di alarm_raw ===\n";
$dates = DB::table('alarm_raw')
    ->where('alarm_type', 32)
    ->selectRaw("DATE(start_time) as date, count(*) as count")
    ->groupBy('date')
    ->orderBy('date', 'desc')
    ->limit(20)
    ->get();
foreach($dates as $d) {
    echo "{$d->date} : {$d->count}\n";
}
