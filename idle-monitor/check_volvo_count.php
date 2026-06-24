<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Device;

echo "═══════════════════════════════════════════════════════════════\n";
echo "  CHECK: VOLVO Devices Count\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Count VOLVO devices
$volvoCount = Device::where('series', 'VOLVO')->count();
echo "🔢 Total VOLVO devices in database: {$volvoCount}\n\n";

// List all VOLVO devices
$volvoDevices = Device::where('series', 'VOLVO')
    ->get(['device_name', 'series', 'location'])
    ->sortBy('device_name');

echo "📋 List of ALL VOLVO devices:\n";
echo str_repeat('-', 70) . "\n";
printf("%-20s | %-25s | %s\n", "Device Name", "Series", "Location");
echo str_repeat('-', 70) . "\n";

foreach ($volvoDevices as $device) {
    printf("%-20s | %-25s | %s\n", 
        $device->device_name, 
        $device->series, 
        $device->location
    );
}
echo str_repeat('-', 70) . "\n\n";

// Expected VOLVO devices (should be only 8)
$expectedVolvo = [
    'GPE-HD-855', 'GPE-HD-857', 'GPE-LV-890', 'GPE-LV-891',
    'GPE-LV-892', 'GPE-LV-910', 'GPE-WT-836', 'GPE-WT-855'
];

echo "✅ Expected VOLVO devices (8):\n";
foreach ($expectedVolvo as $name) {
    echo "   - {$name}\n";
}

echo "\n";
echo "📊 Summary:\n";
echo "   - Expected: 8 VOLVO devices\n";
echo "   - Actual: {$volvoCount} VOLVO devices\n";

if ($volvoCount > 8) {
    $extra = $volvoCount - 8;
    echo "   ⚠️  PROBLEM: {$extra} extra devices have VOLVO series!\n\n";
    
    echo "❌ Unexpected VOLVO devices:\n";
    foreach ($volvoDevices as $device) {
        if (!in_array($device->device_name, $expectedVolvo)) {
            echo "   - {$device->device_name} (should NOT be VOLVO)\n";
        }
    }
} elseif ($volvoCount < 8) {
    echo "   ⚠️  PROBLEM: Missing " . (8 - $volvoCount) . " VOLVO devices!\n";
} else {
    echo "   ✅ CORRECT: Exactly 8 VOLVO devices as expected!\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
