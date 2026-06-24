<?php
/**
 * Update device_id column to be NULLABLE and set all values to NULL
 * Run this ONCE before importing devices
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "===========================================\n";
echo "  UPDATE device_id COLUMN TO NULLABLE\n";
echo "===========================================\n\n";

try {
    // Step 1: Modify column to allow NULL
    echo "[1] Modifying device_id column to allow NULL...\n";
    DB::statement('ALTER TABLE devices MODIFY COLUMN device_id VARCHAR(255) NULL');
    echo "    ✓ Column modified successfully\n\n";
    
    // Step 2: Set all existing device_id to NULL
    echo "[2] Setting all device_id values to NULL...\n";
    $affected = DB::table('devices')->update(['device_id' => null]);
    echo "    ✓ Updated $affected rows\n\n";
    
    // Step 3: Verify
    echo "[3] Verification...\n";
    $total = DB::table('devices')->count();
    $nullCount = DB::table('devices')->whereNull('device_id')->count();
    
    echo "    Total devices: $total\n";
    echo "    NULL device_id: $nullCount\n\n";
    
    if ($total == $nullCount) {
        echo "✅ SUCCESS! All device_id values are now NULL\n";
        echo "✅ Ready to import devices with NULL device_id\n\n";
    } else {
        echo "⚠️  WARNING: Some device_id values are not NULL\n";
        echo "   Expected: $total, Got: $nullCount\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
    exit(1);
}

echo "===========================================\n";
echo "  ✅ COMPLETED!\n";
echo "===========================================\n";
echo "\nNext step: Run import_devices_final_397.php\n";
?>
