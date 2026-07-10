<?php
/**
 * DRY RUN - Update 397 Devices
 */

// Force output
@ini_set('output_buffering', 'off');
ob_implicit_flush(true);
error_reporting(E_ALL);

echo "============================================\n";
echo "  DRY RUN: Update 397 Devices\n";
echo "============================================\n\n";

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Read CSV
$csvFile = __DIR__ . '/devices_397_data.csv';
if (!file_exists($csvFile)) {
    die("ERROR: CSV not found!\n");
}

echo "[1] Reading CSV...\n";
$handle = fopen($csvFile, 'r');
fgetcsv($handle); // skip header
$devicesData = [];

while (($row = fgetcsv($handle)) !== false) {
    $devicesData[] = [
        'device_name' => $row[0],
        'unit_code' => empty($row[1]) ? null : $row[1],
        'location' => empty($row[2]) ? null : $row[2],
        'series' => empty($row[3]) ? null : $row[3],
    ];
}
fclose($handle);

echo "    Loaded: " . count($devicesData) . " devices\n\n";

// Check database
echo "[2] Checking database...\n";
$totalDB = DB::table('devices')->count();
echo "    Devices in DB: $totalDB\n\n";

// Process
echo "[3] Analyzing updates...\n";
$stats = [
    'found' => 0,
    'not_found' => 0,
    'will_update' => 0,
    'skip' => 0
];

foreach ($devicesData as $i => $data) {
    $device = DB::table('devices')->where('device_name', $data['device_name'])->first();
    
    if (!$device) {
        $stats['not_found']++;
        if ($i < 3) echo "    NOT FOUND: {$data['device_name']}\n";
        continue;
    }
    
    $stats['found']++;
    
    $needsUpdate = (
        $device->unit_code !== $data['unit_code'] ||
        $device->location !== $data['location'] ||
        $device->series !== $data['series']
    );
    
    if ($needsUpdate) {
        $stats['will_update']++;
        if ($stats['will_update'] <= 5) {
            echo "    WILL UPDATE: {$data['device_name']} (unit:{$data['unit_code']}, loc:{$data['location']}, ser:{$data['series']})\n";
        }
    } else {
        $stats['skip']++;
    }
}

echo "\n============================================\n";
echo "  DRY RUN SUMMARY\n";
echo "============================================\n";
echo "Total in CSV:     " . count($devicesData) . "\n";
echo "Found in DB:      {$stats['found']}\n";
echo "NOT found:        {$stats['not_found']}\n";
echo "Will UPDATE:      {$stats['will_update']}\n";
echo "Will SKIP:        {$stats['skip']}\n";
echo "============================================\n\n";

if ($stats['will_update'] > 0) {
    echo "💡 To apply these updates, run:\n";
    echo "   RUN_UPDATE_APPLY.php\n";
} else {
    echo "✅ All devices are already up to date!\n";
}

echo "\n";
?>
