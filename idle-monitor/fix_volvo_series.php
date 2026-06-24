<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Device;
use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════════════\n";
echo "  FIX: Update Series to VOLVO\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Based on the image, these unit codes should be VOLVO:
// GPE932, GPE937, GPE951, GPE952, GPE953, GPE955, GPE998, GPE999, 
// GPE1000, GPE1001, GPE1002, GPE1003, GPE1005, GPE1006, GPE1007, GPE1008

// Device names from CSV data that correspond to these unit codes:
$volvoDevices = [
    'GPE-HD-855',  // GPE932
    'GPE-HD-857',  // GPE937
    'GPE-LV-890',  // GPE951
    'GPE-LV-891',  // GPE952
    'GPE-LV-892',  // GPE953
    'GPE-LV-910',  // GPE955
    'GPE-WT-836',  // GPE998
    'GPE-WT-855',  // GPE999
];

echo "📋 Devices to update to VOLVO series:\n";
echo str_repeat('-', 63) . "\n";

// Check current data
$devices = Device::whereIn('device_name', $volvoDevices)
    ->get(['device_name', 'series', 'location']);

echo "BEFORE UPDATE:\n";
foreach ($devices as $device) {
    printf("   %-15s | %-20s | %s\n", 
        $device->device_name, 
        $device->series, 
        $device->location
    );
}

echo "\n";
echo "🔄 Updating to VOLVO...\n";

// Start transaction
DB::beginTransaction();

try {
    $updated = Device::whereIn('device_name', $volvoDevices)
        ->update(['series' => 'VOLVO']);
    
    // Verify count
    $totalDevices = Device::count();
    
    if ($totalDevices !== 397) {
        throw new Exception("Device count changed! Expected 397, got {$totalDevices}");
    }
    
    DB::commit();
    
    echo "✅ Updated {$updated} devices to VOLVO series\n";
    echo "✅ Total devices: {$totalDevices} (maintained)\n\n";
    
    // Show after update
    $devicesAfter = Device::whereIn('device_name', $volvoDevices)
        ->get(['device_name', 'series', 'location']);
    
    echo "AFTER UPDATE:\n";
    echo str_repeat('-', 63) . "\n";
    foreach ($devicesAfter as $device) {
        printf("   %-15s | %-20s | %s\n", 
            $device->device_name, 
            $device->series, 
            $device->location
        );
    }
    
    echo str_repeat('-', 63) . "\n\n";
    echo "✅ SUCCESS! All VOLVO devices updated.\n";
    
} catch (Exception $e) {
    DB::rollBack();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "❌ Transaction rolled back\n";
    exit(1);
}

echo "\n═══════════════════════════════════════════════════════════════\n";
