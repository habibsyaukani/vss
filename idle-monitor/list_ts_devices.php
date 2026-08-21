<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$devices = \App\Models\Device::whereColumn('device_id', 'imei')
    ->orderBy('group_name')
    ->orderBy('device_name')
    ->get(['device_name','device_id','group_name']);

echo "=== Daftar " . $devices->count() . " Device Tracksolid di Database ===\n\n";
$currentGroup = null;
foreach ($devices as $d) {
    if ($d->group_name !== $currentGroup) {
        $currentGroup = $d->group_name;
        echo "\n[" . strtoupper($currentGroup) . "]\n";
    }
    echo "  - {$d->device_name}  (IMEI: {$d->device_id})\n";
}
