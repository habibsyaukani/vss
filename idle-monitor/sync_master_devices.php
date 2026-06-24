<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Device;
use Illuminate\Support\Facades\DB;

echo "\n=== SYNC MASTER DEVICES (397 Units) ===\n\n";

// Load master data from external file
$masterDevices = include __DIR__.'/master_devices_data.php';

echo "[1] Current database state:\n";
$currentCount = Device::count();
echo "    Total devices in DB: {$currentCount}\n\n";

echo "[2] Master data loaded:\n";
echo "    Total master devices: " . count($masterDevices) . "\n\n";

// Get all device names from master
$masterNames = array_column($masterDevices, 'device_name');

echo "[3] Analyzing differences...\n";

// Find devices in DB but NOT in master (will be deleted)
$devicesInDb = Device::pluck('device_name')->toArray();
$toDelete = array_diff($devicesInDb, $masterNames);

echo "    Devices to DELETE: " . count($toDelete) . "\n";
if (count($toDelete) > 0 && count($toDelete) <= 20) {
    foreach ($toDelete as $name) {
        echo "      - {$name}\n";
    }
}

// Find devices in master but NOT in DB (will be added)
$toAdd = array_diff($masterNames, $devicesInDb);
echo "    Devices to ADD: " . count($toAdd) . "\n";

// Devices that exist in both (will be updated)
$toUpdate = array_intersect($masterNames, $devicesInDb);
echo "    Devices to UPDATE: " . count($toUpdate) . "\n\n";

echo "[4] Checking for alarm dependencies...\n";
$devicesWithAlarms = DB::table('idle_alarms')
    ->select('device_name', DB::raw('COUNT(*) as alarm_count'))
    ->whereIn('device_name', $toDelete)
    ->groupBy('device_name')
    ->get();

if ($devicesWithAlarms->count() > 0) {
    echo "    ⚠️  WARNING: Some devices to be deleted have alarm history:\n";
    foreach ($devicesWithAlarms as $d) {
        echo "      - {$d->device_name} ({$d->alarm_count} alarms)\n";
    }
    echo "\n";
} else {
    echo "    ✅ No alarm dependencies found\n\n";
}

echo "═══════════════════════════════════════════════════════\n";
echo "PROCEED WITH SYNC? This will:\n";
echo "  - DELETE " . count($toDelete) . " devices\n";
echo "  - ADD " . count($toAdd) . " devices\n";
echo "  - UPDATE " . count($toUpdate) . " devices\n";
echo "  - FINAL TOTAL: 397 devices\n";
echo "═══════════════════════════════════════════════════════\n\n";

echo "Type 'YES' to proceed, anything else to cancel: ";
$handle = fopen ("php://stdin","r");
$line = trim(fgets($handle));

if ($line !== 'YES') {
    echo "\n❌ Operation cancelled.\n\n";
    exit(0);
}

echo "\n[5] Executing sync...\n\n";

// Step 1: Delete extra devices
$deleted = 0;
if (count($toDelete) > 0) {
    echo "  [a] Deleting " . count($toDelete) . " devices...\n";
    $deleted = Device::whereIn('device_name', $toDelete)->delete();
    echo "      Deleted: {$deleted}\n";
}

// Step 2 & 3: Update existing + Insert new
$updated = 0;
$inserted = 0;

echo "  [b] Syncing " . count($masterDevices) . " devices...\n";
foreach ($masterDevices as $data) {
    $device = Device::where('device_name', $data['device_name'])->first();
    
    if ($device) {
        // Update existing
        $device->update([
            'unit_code' => $data['unit_code'],
            'location' => $data['location'],
            'series' => $data['series'],
        ]);
        $updated++;
    } else {
        // Insert new
        Device::create([
            'device_name' => $data['device_name'],
            'unit_code' => $data['unit_code'],
            'location' => $data['location'],
            'series' => $data['series'],
            'status' => 'active',
        ]);
        $inserted++;
    }
}

echo "      Updated: {$updated}\n";
echo "      Inserted: {$inserted}\n\n";

// Final verification
$finalCount = Device::count();
echo "[6] Final verification:\n";
echo "    Total devices in DB: {$finalCount}\n";
echo "    Expected: 397\n";

if ($finalCount == 397) {
    echo "    ✅ SUCCESS! Device count matches master data.\n\n";
} else {
    echo "    ⚠️  WARNING: Count mismatch! Please verify.\n\n";
}

echo "=== SYNC COMPLETED ===\n\n";
