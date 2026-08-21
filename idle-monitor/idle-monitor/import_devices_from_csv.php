<?php
/**
 * Import devices from devices_update_data.csv to database
 * This will use the UPDATED CSV file with VOLVO series and M.SERVICE location
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
    die("ERROR: CSV file not found: $csvFile\n");
}

// Read CSV file
$handle = fopen($csvFile, 'r');
$header = fgetcsv($handle); // Skip header row

echo "[1] Reading CSV file...\n";
$devices = [];
$lineNumber = 1;

while (($data = fgetcsv($handle)) !== false) {
    $lineNumber++;
    $devices[] = [
        'device_code' => $data[0],
        'unit_code' => $data[1],
        'series' => $data[2],
        'location' => $data[3],
    ];
}
fclose($handle);

echo "    Total devices to import: " . count($devices) . "\n\n";

// Clear existing devices (BACKUP FIRST!)
echo "[2] Clearing existing devices table...\n";
$existingCount = DB::table('devices')->count();
echo "    Existing devices: $existingCount\n";

if ($existingCount > 0) {
    echo "    ⚠️  WARNING: This will DELETE all existing devices!\n";
    echo "    Press ENTER to continue or CTRL+C to cancel...\n";
    fgets(STDIN);
}

DB::table('devices')->truncate();
echo "    ✓ Table cleared\n\n";

// Import devices
echo "[3] Importing devices...\n";
$imported = 0;
$volvo = 0;
$mservice = 0;

foreach ($devices as $device) {
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
        echo "    Imported: $imported devices...\n";
    }
}

echo "    ✓ Import completed!\n\n";

// Verification
echo "===========================================\n";
echo "  IMPORT SUMMARY\n";
echo "===========================================\n";
echo "✅ Total devices imported: $imported\n";
echo "✅ VOLVO series: $volvo devices\n";
echo "✅ M.SERVICE location: $mservice devices\n\n";

// Verify in database
$dbTotal = DB::table('devices')->count();
$dbVolvo = DB::table('devices')->where('series', 'VOLVO')->count();
$dbMservice = DB::table('devices')->where('location', 'M.SERVICE')->count();

echo "DATABASE VERIFICATION:\n";
echo "-------------------------------------------\n";
echo "Total in database: $dbTotal\n";
echo "VOLVO in database: $dbVolvo\n";
echo "M.SERVICE in database: $dbMservice\n\n";

if ($dbTotal == $imported) {
    echo "✅ Import verification PASSED!\n";
} else {
    echo "❌ Import verification FAILED!\n";
    echo "   Expected: $imported, Got: $dbTotal\n";
}

echo "===========================================\n";
echo "  IMPORT COMPLETED\n";
echo "===========================================\n";
?>
