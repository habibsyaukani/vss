<?php
/**
 * APPLY UPDATE - Update 397 Devices
 */

// Force output
@ini_set('output_buffering', 'off');
ob_implicit_flush(true);
error_reporting(E_ALL);

echo "============================================\n";
echo "  APPLY UPDATE: 397 Devices\n";
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

// Start transaction
echo "[3] Starting UPDATE with transaction...\n";
DB::beginTransaction();

$stats = [
    'found' => 0,
    'not_found' => 0,
    'updated' => 0,
    'skipped' => 0,
    'errors' => 0
];

try {
    foreach ($devicesData as $i => $data) {
        $device = DB::table('devices')->where('device_name', $data['device_name'])->first();
        
        if (!$device) {
            $stats['not_found']++;
            continue;
        }
        
        $stats['found']++;
        
        $needsUpdate = (
            $device->unit_code !== $data['unit_code'] ||
            $device->location !== $data['location'] ||
            $device->series !== $data['series']
        );
        
        if (!$needsUpdate) {
            $stats['skipped']++;
            continue;
        }
        
        // UPDATE
        DB::table('devices')
            ->where('device_name', $data['device_name'])
            ->update([
                'unit_code' => $data['unit_code'],
                'location' => $data['location'],
                'series' => $data['series'],
                'updated_at' => now()
            ]);
        
        $stats['updated']++;
        
        // Progress
        if ($stats['updated'] % 50 == 0) {
            echo "    Updated: {$stats['updated']} devices...\n";
        }
    }
    
    // Commit
    DB::commit();
    echo "    ✓ Transaction COMMITTED\n\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "    ❌ Transaction ROLLED BACK\n\n";
    echo "ERROR: " . $e->getMessage() . "\n";
    $stats['errors']++;
}

echo "============================================\n";
echo "  UPDATE SUMMARY\n";
echo "============================================\n";
echo "Total in CSV:     " . count($devicesData) . "\n";
echo "Found in DB:      {$stats['found']}\n";
echo "NOT found:        {$stats['not_found']}\n";
echo "UPDATED:          {$stats['updated']}\n";
echo "SKIPPED:          {$stats['skipped']}\n";
echo "ERRORS:           {$stats['errors']}\n";
echo "============================================\n\n";

if ($stats['errors'] == 0) {
    echo "✅ UPDATE COMPLETED SUCCESSFULLY!\n";
} else {
    echo "❌ UPDATE FAILED - Changes were rolled back\n";
}

echo "\n";
?>
