<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Device;

echo "═══════════════════════════════════════════════════════════════\n";
echo "  CHECK: M.SERVICE Devices (GPE2801-2812)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Unit codes from image
$unitCodes = [
    'GPE2801', 'GPE2802', 'GPE2803', 'GPE2805', 'GPE2806', 
    'GPE2807', 'GPE2808', 'GPE2809', 'GPE2810', 'GPE2811', 'GPE2812'
];

echo "🔍 Looking for devices with unit codes: GPE2801-2812\n\n";

// Search in CSV file
$csvFile = __DIR__ . '/devices_update_data.csv';
$handle = fopen($csvFile, 'r');
fgets($handle); // Skip header

$foundInCSV = [];

while (($line = fgets($handle)) !== false) {
    $parts = str_getcsv(trim($line));
    if (count($parts) >= 4) {
        $deviceCode = trim($parts[0]);
        $unitCode = trim($parts[1]);
        $series = trim($parts[2]);
        $location = trim($parts[3]);
        
        if (in_array($unitCode, $unitCodes)) {
            $foundInCSV[$unitCode] = [
                'device_code' => $deviceCode,
                'series' => $series,
                'location' => $location
            ];
        }
    }
}
fclose($handle);

echo "📋 Found in CSV:\n";
echo str_repeat('-', 75) . "\n";
printf("%-12s | %-15s | %-25s | %s\n", "Unit Code", "Device Name", "Series (CSV)", "Location");
echo str_repeat('-', 75) . "\n";

$deviceNames = [];
foreach ($unitCodes as $unitCode) {
    if (isset($foundInCSV[$unitCode])) {
        $data = $foundInCSV[$unitCode];
        printf("%-12s | %-15s | %-25s | %s\n", 
            $unitCode, 
            $data['device_code'], 
            $data['series'], 
            $data['location']
        );
        $deviceNames[] = $data['device_code'];
    } else {
        printf("%-12s | %-15s | %-25s | %s\n", 
            $unitCode, 
            "NOT FOUND", 
            "-", 
            "-"
        );
    }
}
echo str_repeat('-', 75) . "\n\n";

// Check database
if (count($deviceNames) > 0) {
    echo "📊 Current database values:\n";
    echo str_repeat('-', 75) . "\n";
    printf("%-15s | %-25s | %-15s | %s\n", "Device Name", "Series (DB)", "Location", "Status");
    echo str_repeat('-', 75) . "\n";
    
    $dbDevices = Device::whereIn('device_name', $deviceNames)
        ->get(['device_name', 'series', 'location']);
    
    foreach ($dbDevices as $device) {
        // Find expected series from CSV
        $unitCode = null;
        $expectedSeries = null;
        foreach ($foundInCSV as $code => $data) {
            if ($data['device_code'] === $device->device_name) {
                $unitCode = $code;
                $expectedSeries = $data['series'];
                break;
            }
        }
        
        $status = ($device->series === $expectedSeries) ? '✅ OK' : '❌ MISMATCH';
        
        printf("%-15s | %-25s | %-15s | %s\n", 
            $device->device_name, 
            $device->series, 
            $device->location,
            $status
        );
    }
    
    echo str_repeat('-', 75) . "\n\n";
    
    // Summary
    $total = $dbDevices->count();
    $correct = 0;
    foreach ($dbDevices as $device) {
        foreach ($foundInCSV as $code => $data) {
            if ($data['device_code'] === $device->device_name && 
                $device->series === $data['series']) {
                $correct++;
                break;
            }
        }
    }
    
    echo "📊 Summary:\n";
    echo "   - Found in CSV: " . count($foundInCSV) . " devices\n";
    echo "   - Found in DB: {$total} devices\n";
    echo "   - Correctly updated: {$correct} ✅\n";
    echo "   - Need update: " . ($total - $correct) . " ❌\n\n";
    
    if ($correct < $total) {
        echo "⚠️  Some devices need to be updated!\n";
    } else {
        echo "✅ All devices are correctly updated!\n";
    }
} else {
    echo "❌ No devices found in CSV for unit codes GPE2801-2812\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
