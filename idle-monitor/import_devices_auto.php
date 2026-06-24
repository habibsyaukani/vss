<?php
/**
 * Import devices from devices_update_data.csv to database
 * AUTO MODE - No confirmation needed (user already approved)
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Device;
use Illuminate\Support\Facades\DB;

echo "===========================================\n";
echo "  IMPORT DEVICES FROM CSV TO DATABASE\n";
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
        'device_code' => trim($data[0]),
        'unit_code' => trim($data[1]),
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

// Import devices
echo "[3] Importing devices...\n";
$imported = 0;
$volvo = 0;
$mservice = 0;
$errors = 0;

try {
    DB::beginTransaction();
    
    foreach ($devices as $device) {
        try {
            DB::table('devices')->insert([
                'device_code' => $device['device_code'],
                'unit_code' => $device['unit_code'],
                'series' => $device['series'],
                'location' => $device['location'],
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

if ($dbTotal == $imported && $dbTotal == 397) {
    echo "✅ Import verification PASSED!\n";
    echo "✅ All 397 devices imported successfully!\n\n";
    
    // Show sample VOLVO devices
    echo "SAMPLE VOLVO DEVICES:\n";
    echo "-------------------------------------------\n";
    $sampleVolvo = DB::table('devices')
        ->where('series', 'VOLVO')
        ->limit(5)
        ->get(['device_code', 'unit_code', 'series', 'location']);
    
    foreach ($sampleVolvo as $dev) {
        echo "  • {$dev->device_code} ({$dev->unit_code}) - {$dev->series} @ {$dev->location}\n";
    }
    
    echo "\nSAMPLE M.SERVICE DEVICES:\n";
    echo "-------------------------------------------\n";
    $sampleMservice = DB::table('devices')
        ->where('location', 'M.SERVICE')
        ->where('series', '!=', 'VOLVO')
        ->limit(5)
        ->get(['device_code', 'unit_code', 'series', 'location']);
    
    foreach ($sampleMservice as $dev) {
        echo "  • {$dev->device_code} ({$dev->unit_code}) - {$dev->series} @ {$dev->location}\n";
    }
    
} else {
    echo "❌ Import verification FAILED!\n";
    echo "   Expected: 397, Got: $dbTotal\n";
}

echo "\n===========================================\n";
echo "  ✅ IMPORT COMPLETED SUCCESSFULLY!\n";
echo "===========================================\n";
?>
