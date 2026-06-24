<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\GpsTrack;
use Illuminate\Support\Facades\DB;

$today = date('Y-m-d');

// Max & Avg speed
$max = GpsTrack::where('gps_time', '>=', $today . ' 00:00:00')
    ->where('gps_time', '<=', $today . ' 23:59:59')
    ->max('speed');
$avg = GpsTrack::where('gps_time', '>=', $today . ' 00:00:00')
    ->where('gps_time', '<=', $today . ' 23:59:59')
    ->where('speed', '>', 0)
    ->avg('speed');

echo "=== STATS ===\n";
echo "Max Speed : " . number_format($max, 1) . " km/h\n";
echo "Avg Speed : " . number_format($avg, 1) . " km/h\n\n";

// Top 5 speed units
$top = GpsTrack::select('device_name', DB::raw('MAX(speed) as max_speed'))
    ->where('gps_time', '>=', $today . ' 00:00:00')
    ->where('gps_time', '<=', $today . ' 23:59:59')
    ->where('speed', '>', 0)
    ->whereNotNull('device_name')
    ->groupBy('device_name')
    ->orderBy('max_speed', 'desc')
    ->limit(5)
    ->get();

echo "=== TOP 5 SPEED UNITS ===\n";
foreach ($top as $i => $u) {
    echo ($i+1) . ". " . $u->device_name . " => " . $u->max_speed . " km/h\n";
}

// Top 5 idle units
$topIdle = \App\Models\IdleAlarm::select('device_name', DB::raw('COUNT(*) as event_count'))
    ->whereDate('starting_time', $today)
    ->groupBy('device_name')
    ->orderBy('event_count', 'desc')
    ->limit(5)
    ->get();

echo "\n=== TOP 5 IDLE UNITS ===\n";
foreach ($topIdle as $i => $u) {
    echo ($i+1) . ". " . $u->device_name . " => " . $u->event_count . " alarm\n";
}
