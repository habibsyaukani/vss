<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$today = date('Y-m-d');
$totalSpeed0 = \App\Models\GpsTrack::whereDate('gps_time', $today)->where('speed', 0)->count();
$totalSpeedLow = \App\Models\GpsTrack::whereDate('gps_time', $today)->where('speed', '>', 0)->where('speed', '<', 15)->count();
$totalSpeedHigh = \App\Models\GpsTrack::whereDate('gps_time', $today)->where('speed', '>=', 41)->count();

echo "Date: $today\n";
echo "Speed 0 km/h (Parkir) : $totalSpeed0 records\n";
echo "Speed 1 - 14 km/h     : $totalSpeedLow records\n";
echo "Speed >= 41 km/h      : $totalSpeedHigh records\n";
