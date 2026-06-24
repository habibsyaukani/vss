<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$alarms = \App\Models\IdleAlarm::whereDate('starting_time', '2026-06-22')
    ->orderBy('starting_time', 'desc')
    ->limit(15)
    ->get(['id', 'device_name', 'starting_time']);
foreach($alarms as $a) {
    echo $a->starting_time . ' - ' . $a->device_name . "\n";
}
