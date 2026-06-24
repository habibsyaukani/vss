<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Device;

echo "═══════════════════════════════════════════════════════════════\n";
echo "  SEARCH: Devices with '2801-2812' in name\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Search patterns
$patterns = ['2801', '2802', '2803', '2805', '2806', '2807', '2808', '2809', '2810', '2811', '2812'];

echo "🔍 Searching in database for devices with '2801-2812' in device_name...\n\n";

$found = [];
foreach ($patterns as $pattern) {
    $devices = Device::where('device_name', 'LIKE', "%{$pattern}%")
        ->get(['device_name', 'series', 'location']);
    
    foreach ($devices as $device) {
        $found[] = $device;
    }
}

if (count($found) > 0) {
    echo "✅ Found " . count($found) . " devices:\n";
    echo str_repeat('-', 70) . "\n";
    printf("%-20s | %-25s | %s\n", "Device Name", "Series", "Location");
    echo str_repeat('-', 70) . "\n";
    
    foreach ($found as $device) {
        printf("%-20s | %-25s | %s\n", 
            $device->device_name, 
            $device->series, 
            $device->location
        );
    }
    echo str_repeat('-', 70) . "\n";
} else {
    echo "❌ NO devices found with '2801-2812' in device_name\n\n";
    
    echo "Let me check what M.SERVICE devices exist...\n\n";
    
    $mserviceDevices = Device::where('location', 'M.SERVICE')
        ->get(['device_name', 'series', 'location']);
    
    if ($mserviceDevices->count() > 0) {
        echo "✅ Found " . $mserviceDevices->count() . " M.SERVICE devices:\n";
        echo str_repeat('-', 70) . "\n";
        printf("%-20s | %-25s | %s\n", "Device Name", "Series", "Location");
        echo str_repeat('-', 70) . "\n";
        
        foreach ($mserviceDevices as $device) {
            printf("%-20s | %-25s | %s\n", 
                $device->device_name, 
                $device->series, 
                $device->location
            );
        }
        echo str_repeat('-', 70) . "\n";
    } else {
        echo "❌ NO M.SERVICE devices found in database\n";
    }
}

echo "\n═══════════════════════════════════════════════════════════════\n";
