<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Device;

echo "Searching for devices with names containing '1000', '1001', etc...\n\n";

// Search for devices that might match
$patterns = ['1000', '1001', '1002', '1003', '1005', '1006', '1007', '1008'];

foreach ($patterns as $pattern) {
    $devices = Device::where('device_name', 'LIKE', "%{$pattern}%")->get(['device_name', 'series', 'location']);
    
    if ($devices->count() > 0) {
        echo "Pattern '{$pattern}':\n";
        foreach ($devices as $device) {
            echo "   - {$device->device_name} | {$device->series} | {$device->location}\n";
        }
    }
}

echo "\n";
echo "Total devices in database: " . Device::count() . "\n";
echo "VOLVO devices: " . Device::where('series', 'VOLVO')->count() . "\n";
