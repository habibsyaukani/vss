<?php
/**
 * Import devices from devices_update_data.csv to database
 * FINAL VERSION - device_id is NULL, will be filled when idle data is imported
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "===========================================\n";
echo "  IMPORT ALL 397 DEVICES - FINAL VERSION\n";
echo "===========================================\n\n";

$csvFile = __DIR__ . '/devices_update_data.csv';

if (!file_exists($csvFile)) {
    die("❌ ERROR: CSV file not found: $csvFile\n");
}

// Read CSV file
echo "[1] Reading CSV file...\n";
$handle = fopen($csvFile, 'r');
$header = fgetcsv($handle); // Skip header row

$devices = [];
$lineNumber = 1;

while (($data = fgetcsv($handle)) !== false) {
    $lineNumber++;
    $devices[] = [
        'device_code' => trim($data[0]),  // GPE-DT-1005, GPE-HD-840, etc (UNIQUE)
        'unit_code' => trim($data[1]),    // GPE829, etc (MAY NOT BE UNIQUE!)
        'series' => trim($data[2]),
        'location' => trim($data[3]),
    ];
}
fclose($handle);

echo "    ✓ Total devices to import: " . count($devices) . "\n\n";

// Clear existing devices
echo "[2] Clearing existing devices table...\n";
$existingCount = DB::table('devices')->count();
echo "    Existing devices: $existingCount\n";

DB::table('devices')->truncate();
echo "    ✓ Table cleared\n\n";

// Import devices - Use device_code as unique identifier instead of unit_code
echo "[3] Importing devices (using device_code as unique key)...\n";
$imported = 0;
$volvo = 0;
$mservice = 0;
$errors = 0;
$deviceCounter = 1; // For generating unique device_id

try {
    DB::beginTransaction();
    
    foreach ($devices as $device) {
        try {
            // device_id will be NULL for now, will be filled when idle data is imported
            
            DB::table('devices')->insert([
                'device_id' => null,  // NULL - akan diisi saat import idle data
                'device_name' => $device['device_code'],  // GPE-DT-1005, GPE-HD-840
                'unit_code' => $device['unit_code'],  // GPE829 (may duplicate)
                'series' => $device['series'],
                'location' => $device['location'],
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $imported++;
            
            if ($device['series'] === 'VOLVO') {
                $volvo++;
            }
            
            if ($device['location'] === 'M.SERVICE') {
                $mservice++;
            }
            
            if ($imported % 50 == 0) {
                echo "    Progress: $imported devices...\n";
            }
        } catch (Exception $e) {
            $errors++;
            echo "    ❌ Error importing {$device['device_code']}: {$e->getMessage()}\n";
        }
    }
    
    DB::commit();
    echo "    ✓ Import completed!\n\n";
    
} catch (Exception $e) {
    DB::rollBack();
    die("❌ FATAL ERROR: Import failed - {$e->getMessage()}\n");
}

// Verification
echo "===========================================\n";
echo "  IMPORT SUMMARY\n";
echo "===========================================\n";
echo "✅ Total devices imported: $imported\n";
echo "✅ VOLVO series: $volvo devices\n";
echo "✅ M.SERVICE location: $mservice devices\n";
if ($errors > 0) {
    echo "⚠️  Errors encountered: $errors\n";
}
echo "\n";

// Verify in database
$dbTotal = DB::table('devices')->count();
$dbVolvo = DB::table('devices')->where('series', 'VOLVO')->count();
$dbMservice = DB::table('devices')->where('location', 'M.SERVICE')->count();

echo "DATABASE VERIFICATION:\n";
echo "-------------------------------------------\n";
echo "Total in database: $dbTotal\n";
echo "VOLVO in database: $dbVolvo\n";
echo "M.SERVICE in database: $dbMservice\n\n";

if ($dbTotal == 397) {
    echo "✅ Import verification PASSED!\n";
    echo "✅ All 397 devices imported successfully!\n\n";
    
    // Check for devices with GPE829 unit_code
    echo "DEVICES WITH UNIT_CODE GPE829:\n";
    echo "-------------------------------------------\n";
    $gpe829 = DB::table('devices')
        ->where('unit_code', 'GPE829')
        ->get(['device_name', 'unit_code', 'series', 'location']);
    
    foreach ($gpe829 as $dev) {
        echo "  • {$dev->device_name} - unit_code:{$dev->unit_code} - {$dev->series} @ {$dev->location}\n";
    }
    
    echo "\nSAMPLE VOLVO DEVICES:\n";
    echo "-------------------------------------------\n";
    $sampleVolvo = DB::table('devices')
        ->where('series', 'VOLVO')
        ->limit(5)
        ->get(['device_name', 'unit_code', 'series', 'location']);
    
    foreach ($sampleVolvo as $dev) {
        echo "  • {$dev->device_name} ({$dev->unit_code}) - {$dev->series} @ {$dev->location}\n";
    }
    
    echo "\nSAMPLE M.SERVICE DEVICES:\n";
    echo "-------------------------------------------\n";
    $sampleMservice = DB::table('devices')
        ->where('location', 'M.SERVICE')
        ->where('series', '!=', 'VOLVO')
        ->limit(5)
        ->get(['device_name', 'unit_code', 'series', 'location']);
    
    foreach ($sampleMservice as $dev) {
        echo "  • {$dev->device_name} ({$dev->unit_code}) - {$dev->series} @ {$dev->location}\n";
    }
    
} else {
    echo "❌ Import verification FAILED!\n";
    echo "   Expected: 397, Got: $dbTotal\n";
}

echo "\n===========================================\n";
echo "  ✅ IMPORT COMPLETED!\n";
echo "===========================================\n";
echo "\nNOTE: device_id is NULL for now\n";
echo "      Will be filled when idle data is imported\n";
?>
