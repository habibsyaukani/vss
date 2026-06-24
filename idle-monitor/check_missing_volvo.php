<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Device;

echo "═══════════════════════════════════════════════════════════════\n";
echo "  CHECK: Missing VOLVO Devices\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// From image, these 16 unit codes should be VOLVO:
$volvoUnitCodes = [
    'GPE932', 'GPE937', 'GPE951', 'GPE952', 'GPE953', 'GPE955', 
    'GPE998', 'GPE999', 'GPE1000', 'GPE1001', 'GPE1002', 'GPE1003', 
    'GPE1005', 'GPE1006', 'GPE1007', 'GPE1008'
];

echo "📋 Looking for devices with these unit codes:\n";
foreach ($volvoUnitCodes as $code) {
    echo "   - {$code}\n";
}
echo "\n";

// Check CSV data to find device_names
echo "🔍 Checking devices_update_data.csv...\n\n";

$csvFile = __DIR__ . '/devices_update_data.csv';
$handle = fopen($csvFile, 'r');

// Skip header
fgets($handle);

$foundDevices = [];
$devicesByUnitCode = [];

while (($line = fgets($handle)) !== false) {
    $parts = str_getcsv(trim($line));
    if (count($parts) >= 4) {
        $deviceCode = trim($parts[0]);
        $unitCode = trim($parts[1]);
        $series = trim($parts[2]);
        $location = trim($parts[3]);
        
        if (in_array($unitCode, $volvoUnitCodes)) {
            $foundDevices[] = $deviceCode;
            $devicesByUnitCode[$unitCode] = [
                'device_code' => $deviceCode,
                'series' => $series,
                'location' => $location
            ];
        }
    }
}

fclose($handle);

echo "✅ Found " . count($foundDevices) . " devices in CSV:\n";
echo str_repeat('-', 70) . "\n";
printf("%-15s | %-15s | %-20s | %s\n", "Unit Code", "Device Name", "Current Series", "Location");
echo str_repeat('-', 70) . "\n";

foreach ($volvoUnitCodes as $unitCode) {
    if (isset($devicesByUnitCode[$unitCode])) {
        $data = $devicesByUnitCode[$unitCode];
        printf("%-15s | %-15s | %-20s | %s\n", 
            $unitCode, 
            $data['device_code'], 
            $data['series'], 
            $data['location']
        );
    } else {
        printf("%-15s | %-15s | %-20s | %s\n", 
            $unitCode, 
            "NOT FOUND", 
            "-", 
            "-"
        );
    }
}

echo str_repeat('-', 70) . "\n\n";

// Check current database values
echo "📊 Checking database for these devices...\n\n";

$dbDevices = Device::whereIn('device_name', $foundDevices)
    ->get(['device_name', 'series', 'location']);

echo "Current database values:\n";
echo str_repeat('-', 70) . "\n";
printf("%-15s | %-20s | %s\n", "Device Name", "Series", "Location");
echo str_repeat('-', 70) . "\n";

foreach ($dbDevices as $device) {
    $isVolvo = $device->series === 'VOLVO' ? '✅' : '❌';
    printf("%-15s | %-20s | %s %s\n", 
        $device->device_name, 
        $device->series, 
        $device->location,
        $isVolvo
    );
}

echo str_repeat('-', 70) . "\n\n";

// Count how many are already VOLVO
$volvoCount = $dbDevices->where('series', 'VOLVO')->count();
$notVolvoCount = $dbDevices->where('series', '!=', 'VOLVO')->count();

echo "Summary:\n";
echo "   - Total devices found: " . $dbDevices->count() . "\n";
echo "   - Already VOLVO: {$volvoCount} ✅\n";
echo "   - Need to update: {$notVolvoCount} ❌\n\n";

if ($notVolvoCount > 0) {
    echo "⚠️  Missing " . $notVolvoCount . " devices to update!\n";
} else {
    echo "✅ All devices already have VOLVO series!\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
