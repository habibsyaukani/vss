<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiService = app(\App\Services\TracksolidApiService::class);

// Cek local tracksolid devices
$devices = \App\Models\Device::whereColumn('device_id', 'imei')->get(['device_name','device_id','imei','group_name']);
echo "Local Tracksolid devices: " . $devices->count() . "\n";
foreach ($devices->take(5) as $d) {
    echo "  name={$d->device_name} device_id={$d->device_id} group={$d->group_name}\n";
}

echo "\n--- Testing track pull to discover device IDs ---\n";
// Coba gunakan endpoint yang dipakai untuk pull tracks
// Karena tracks berhasil, berarti device_id yang ada memang valid di Tracksolid
// Coba ambil location dari semua IMEI yang ada
$imeis = $devices->pluck('device_id')->toArray();
echo "Known IMEIs: " . implode(', ', array_slice($imeis, 0, 5)) . "...\n";

// Test endpoint yg biasa digunakan untuk track pulling
echo "\n--- Testing jimi.device.status.list ---\n";
$res = $apiService->callApi('jimi.device.status.list', [
    'target' => 'wiwie@gpe.co.id',
    'imeis' => implode(',', $imeis)
]);
print_r($res);
