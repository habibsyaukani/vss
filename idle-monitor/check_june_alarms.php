<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$alarms = DB::table('alarm_raw')
    ->whereRaw('start_time LIKE "2026-06-%"')
    ->select('id', 'guid', 'start_time', 'created_at')
    ->limit(10)
    ->get();

foreach ($alarms as $alarm) {
    echo "ID: {$alarm->id}, Start Time: {$alarm->start_time}, Created At: {$alarm->created_at}\n";
}

$total_june_by_start = DB::table('alarm_raw')->whereRaw('start_time LIKE "2026-06-%"')->count();
echo "Total alarms with start_time in June: $total_june_by_start\n";

$total_june_by_created = DB::table('alarm_raw')->whereBetween('created_at', ['2026-06-01 00:00:00', '2026-06-30 23:59:59'])->count();
echo "Total alarms with created_at in June: $total_june_by_created\n";
