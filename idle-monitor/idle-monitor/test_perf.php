<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$start = microtime(true);
$c = \App\Models\GpsTrack::leftJoin('devices', \Illuminate\Support\Facades\DB::raw('CAST(gps_tracks.device_id AS UNSIGNED)'), '=', \Illuminate\Support\Facades\DB::raw('CAST(devices.device_id AS UNSIGNED)'))
    ->where('gps_tracks.gps_time', '>=', '2026-08-01 00:00:00')
    ->count();
echo "CAST Count: " . $c . " Time: " . (microtime(true)-$start) . "s\n";

$start = microtime(true);
$c = \App\Models\GpsTrack::leftJoin('devices', 'gps_tracks.device_id', '=', \Illuminate\Support\Facades\DB::raw("TRIM(LEADING '0' FROM devices.device_id)"))
    ->where('gps_tracks.gps_time', '>=', '2026-08-01 00:00:00')
    ->count();
echo "TRIM Count: " . $c . " Time: " . (microtime(true)-$start) . "s\n";

// Using whereIn for location instead of JOIN
$start = microtime(true);
$deviceIds = \App\Models\Device::where('location', 'SELATAN')->pluck('device_id')->toArray();
$cleanIds = array_map(function($id) { return ltrim((string)$id, '0'); }, $deviceIds);
$c = \App\Models\GpsTrack::where('gps_tracks.gps_time', '>=', '2026-08-01 00:00:00')
    ->whereIn('gps_tracks.device_id', $cleanIds)
    ->count();
echo "WhereIn Count: " . $c . " Time: " . (microtime(true)-$start) . "s\n";
