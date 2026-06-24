<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Check alarm_raw table for date range
$raw = \App\Models\AlarmRaw::whereBetween('start_time', ['2026-05-26 00:00:00', '2026-06-03 23:59:59'])
    ->selectRaw('DATE(start_time) as date, alarm_type, count(*) as count')
    ->groupBy('date', 'alarm_type')
    ->orderBy('date')
    ->get();

echo "Data alarm_raw (26 Mei - 3 Juni):\n";
if ($raw->count() == 0) {
    echo "KOSONG - tidak ada data di alarm_raw untuk range ini\n";
} else {
    foreach($raw as $r) {
        echo "{$r->date} | alarm_type={$r->alarm_type} | {$r->count} data\n";
    }
}

echo "\n--- Cek 5 data terbaru di alarm_raw ---\n";
$latest = \App\Models\AlarmRaw::orderByDesc('created_at')->take(5)->get(['guid','device_name','alarm_type','start_time','created_at']);
foreach($latest as $l) {
    echo "device={$l->device_name} | type={$l->alarm_type} | start={$l->start_time} | imported={$l->created_at}\n";
}

echo "\n--- Total alarm_raw per alarm_type (semua) ---\n";
$byType = \App\Models\AlarmRaw::selectRaw('alarm_type, count(*) as count')
    ->groupBy('alarm_type')
    ->orderBy('alarm_type')
    ->get();
foreach($byType as $t) {
    echo "alarm_type={$t->alarm_type} : {$t->count}\n";
}
