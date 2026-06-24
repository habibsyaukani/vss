<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Device;
use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════════════\n";
echo "  UPDATE: GPE2801-2812 Location to M.SERVICE\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Target unit codes from image
$targetUnitCodes = [
    'GPE2801', 'GPE2802', 'GPE2803', 'GPE2805', 'GPE2806',
    'GPE2807', 'GPE2808', 'GPE2809', 'GPE2810', 'GPE2811', 'GPE2812'
];

echo "🔍 Step 1: Searching for devices with unit codes GPE2801-2812...\n\n";

// We found earlier that these devices exist as GPE-DT-2801, GPE-DT-2802, etc.
$deviceNames = [
    'GPE-DT-2801', 'GPE-DT-2802', 'GPE-DT-2803', 'GPE-DT-2805', 'GPE-DT-2806',
    'GPE-DT-2807', 'GPE-DT-2808', 'GPE-DT-2809', 'GPE-DT-2810', 'GPE-DT-2811', 'GPE-DT-2812'
];

// Check current status
$devices = Device::whereIn('device_name', $deviceNames)
    ->get(['device_name', 'series', 'location']);

if ($devices->count() === 0) {
    echo "❌ No devices found with names: GPE-DT-2801 to GPE-DT-2812\n";
    echo "   These devices do NOT exist in database (397 devices)\n";
    echo "   ✅ NO UPDATE NEEDED\n\n";
    exit(0);
}

echo "✅ Found {$devices->count()} devices in database:\n\n";
echo "BEFORE UPDATE:\n";
echo str_repeat('-', 70) . "\n";
printf("%-15s | %-25s | %s\n", "Device Name", "Series", "Location");
echo str_repeat('-', 70) . "\n";

foreach ($devices as $device) {
    printf("%-15s | %-25s | %s\n", 
        $device->device_name, 
        $device->series, 
        $device->location
    );
}
echo str_repeat('-', 70) . "\n\n";

// Ask for confirmation
echo "🔄 Will update location to: M.SERVICE\n";
echo "   Devices to update: {$devices->count()}\n\n";

// Start transaction
DB::beginTransaction();

try {
    $updated = Device::whereIn('device_name', $deviceNames)
        ->update(['location' => 'M.SERVICE']);
    
    // Verify total count
    $totalDevices = Device::count();
    
    if ($totalDevices !== 397) {
        throw new Exception("Device count changed! Expected 397, got {$totalDevices}");
    }
    
    DB::commit();
    
    echo "✅ Updated {$updated} devices to M.SERVICE location\n";
    echo "✅ Total devices: {$totalDevices} (maintained)\n\n";
    
    // Show after update
    $devicesAfter = Device::whereIn('device_name', $deviceNames)
        ->get(['device_name', 'series', 'location']);
    
    echo "AFTER UPDATE:\n";
    echo str_repeat('-', 70) . "\n";
    printf("%-15s | %-25s | %s\n", "Device Name", "Series", "Location");
    echo str_repeat('-', 70) . "\n";
    
    foreach ($devicesAfter as $device) {
        printf("%-15s | %-25s | %s\n", 
            $device->device_name, 
            $device->series, 
            $device->location
        );
    }
    echo str_repeat('-', 70) . "\n\n";
    
    echo "✅ SUCCESS! All GPE2801-2812 devices updated to M.SERVICE\n";
    
} catch (Exception $e) {
    DB::rollBack();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "❌ Transaction rolled back\n";
    exit(1);
}

echo "\n═══════════════════════════════════════════════════════════════\n";
