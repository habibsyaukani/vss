<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$start = microtime(true);
$c = \App\Models\GpsTrack::where('gps_time', '>=', '2026-08-01 00:00:00')
    ->where('gps_time', '<=', '2026-08-01 23:59:59')
    ->count();
echo "Count for Aug 1: " . $c . " (Time: " . (microtime(true)-$start) . "s)\n";

$c2 = \App\Models\GpsTrack::count();
echo "Total rows in gps_tracks: " . $c2 . "\n";

// check data for SELATAN
$deviceIds = \App\Models\Device::where('location', 'SELATAN')->pluck('device_id')->toArray();
$c3 = \App\Models\GpsTrack::where('gps_time', '>=', '2026-08-01 00:00:00')
    ->where('gps_time', '<=', '2026-08-01 23:59:59')
    ->whereIn('device_id', $deviceIds)
    ->count();
echo "Count for SELATAN on Aug 1: " . $c3 . "\n";
