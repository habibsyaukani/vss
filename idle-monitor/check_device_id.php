<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$gps = DB::table('gps_tracks')->first();
$device = DB::table('devices')->where('device_id', 'LIKE', '%' . ltrim($gps->device_id, '0') . '%')->first();

echo "GPS Track device_id: '" . $gps->device_id . "'\n";
echo "Device table device_id: '" . ($device ? $device->device_id : 'NOT FOUND') . "'\n";
