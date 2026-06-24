<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$today = '2026-06-23';

$rawCount = \DB::table('alarm_raw')
    ->where('start_time', 'like', $today . '%')
    ->count();

$idleCount = \DB::table('idle_alarms')
    ->where('starting_time', 'like', $today . '%')
    ->count();

echo "Raw alarms for today: " . $rawCount . "\n";
echo "Idle alarms for today: " . $idleCount . "\n";

$rawTypes = \DB::table('alarm_raw')
    ->select('alarm_type', \DB::raw('count(*) as count'))
    ->where('start_time', 'like', $today . '%')
    ->groupBy('alarm_type')
    ->get();

echo "\nRaw alarms breakdown by type today:\n";
foreach($rawTypes as $t) {
    echo "Type " . $t->alarm_type . ": " . $t->count . "\n";
}

$latestRaw = \DB::table('alarm_raw')->orderBy('start_time', 'desc')->first();
echo "\nLatest raw alarm in DB:\n";
if ($latestRaw) {
    echo "Time: " . $latestRaw->start_time . "\n";
}

