<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$query = \App\Models\GpsTrack::select('gps_tracks.*', 'devices.device_name as master_device_name', 'devices.location')
    ->leftJoin('devices', \Illuminate\Support\Facades\DB::raw('CAST(gps_tracks.device_id AS UNSIGNED)'), '=', \Illuminate\Support\Facades\DB::raw('CAST(devices.device_id AS UNSIGNED)'))
    ->where('devices.location', 'SELATAN')
    ->where('gps_tracks.speed', '>', 0)
    ->where('gps_tracks.speed', '<', 15)
    ->where('gps_tracks.gps_time', '>=', date('Y-m-d 00:00:00'))
    ->where('gps_tracks.gps_time', '<=', date('Y-m-d 23:59:59'));

echo "Count for SELATAN (low speed today): " . $query->count() . "\n";
$first = $query->first();
if ($first) {
    echo "First Data: Device: {$first->master_device_name} (ID: {$first->device_id}), Location: {$first->location}\n";
} else {
    echo "No matching data found.\n";
}
