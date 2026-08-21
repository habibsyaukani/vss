<?php
/**
 * Update devices.device_id from idle_alarms data
 * Match by device_name
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "===========================================\n";
echo "  UPDATE DEVICES WITH REAL DEVICE_IDs\n";
echo "===========================================\n\n";

echo "🔍 Analyzing idle_alarms data...\n\n";

try {
    // Get unique device_id and device_name from idle_alarms
    echo "[1] Getting device mapping from idle_alarms...\n";
    $deviceMappings = DB::table('idle_alarms')
        ->select('device_id', 'device_name')
        ->distinct()
        ->whereNotNull('device_id')
        ->whereNotNull('device_name')
        ->get();
    
    echo "    Found " . $deviceMappings->count() . " unique devices in idle_alarms\n\n";
    
    // Group by device_name to handle duplicates (take most recent device_id)
    $mappingByName = [];
    foreach ($deviceMappings as $mapping) {
        $mappingByName[$mapping->device_name] = $mapping->device_id;
    }
    
    echo "[2] Updating devices table...\n";
    $updated = 0;
    $notFound = 0;
    
    DB::beginTransaction();
    
    foreach ($mappingByName as $deviceName => $deviceId) {
        $result = DB::table('devices')
            ->where('device_name', $deviceName)
            ->update(['device_id' => $deviceId, 'updated_at' => now()]);
        
        if ($result > 0) {
            $updated++;
            if ($updated % 50 == 0) {
                echo "    Progress: $updated devices updated...\n";
            }
        } else {
            $notFound++;
            echo "    ⚠️  Device not found in devices table: $deviceName\n";
        }
    }
    
    DB::commit();
    
    echo "    ✓ Update completed!\n\n";
    
    // Verification
    echo "===========================================\n";
    echo "  SUMMARY\n";
    echo "===========================================\n";
    echo "✅ Devices updated: $updated\n";
    if ($notFound > 0) {
        echo "⚠️  Not found in devices table: $notFound\n";
    }
    echo "\n";
    
    // Sample check
    echo "[3] Verification - Sample devices:\n";
    $sample = DB::table('devices')
        ->whereNotNull('device_id')
        ->limit(5)
        ->get(['device_id', 'device_name', 'unit_code']);
    
    foreach ($sample as $device) {
        echo "    ✓ {$device->device_name} - device_id: {$device->device_id} - unit: {$device->unit_code}\n";
    }
    echo "\n";
    
    // Count devices with NULL device_id
    $nullCount = DB::table('devices')->whereNull('device_id')->count();
    echo "Devices still with NULL device_id: $nullCount\n\n";
    
    if ($nullCount > 0) {
        echo "⚠️  These devices don't have matching data in idle_alarms yet.\n";
        echo "   They will be updated when idle data is imported.\n\n";
    }
    
    echo "===========================================\n";
    echo "  ✅ COMPLETED!\n";
    echo "===========================================\n";
    echo "\ndevice_id successfully populated from idle_alarms data!\n";
    echo "Idle Monitor should work now.\n\n";
    
} catch (Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: {$e->getMessage()}\n";
    exit(1);
}
?>
