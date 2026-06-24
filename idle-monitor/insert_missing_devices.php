<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Device;
use App\Models\AlarmRaw;

// Find all devices in alarm_raw that are NOT in devices table
$missingDevices = AlarmRaw::select('device_id', 'device_name')
    ->distinct()
    ->whereNotIn('device_id', function($q) {
        $q->select('device_id')->from('devices');
    })
    ->whereNotNull('device_id')
    ->whereNotNull('device_name')
    ->get();

echo "Missing devices found: " . $missingDevices->count() . "\n";

$inserted = 0;
foreach($missingDevices as $d) {
    // Try to determine group from device name
    $name = $d->device_name;
    $group = null;
    if (str_contains($name, '-BUS-') || str_contains($name, 'GPE-B-')) $group = 'BUS - GPE';
    elseif (str_contains($name, '-DT-') || str_contains($name, 'GPE-DT')) $group = 'DT - GPE';
    elseif (str_contains($name, '-FT-') || str_contains($name, 'GPE-FT')) $group = 'FT - GPE';
    elseif (str_contains($name, '-HD-') || str_contains($name, 'GPE-HD')) $group = 'HD - GPE';
    elseif (str_contains($name, '-WT-') || str_contains($name, 'GPE-WT')) $group = 'WT - GPE';
    elseif (str_contains($name, '-PATROL') || str_contains($name, 'GPE-PTRL')) $group = 'PATROL - GPE';
    elseif (str_contains($name, '-GFTH-') || str_contains($name, 'GPE-GFTH')) $group = 'DT - GPE'; // Forklift -> DT

    Device::updateOrCreate(
        ['device_id' => $d->device_id],
        [
            'device_name' => $d->device_name,
            'group_name'  => $group,
            'last_sync_at' => now(),
        ]
    );
    echo "Inserted: " . $d->device_id . " - " . $d->device_name . " [" . ($group ?? 'unknown') . "]\n";
    $inserted++;
}

echo "\nDone! Inserted: $inserted, Total devices: " . Device::count() . "\n";
