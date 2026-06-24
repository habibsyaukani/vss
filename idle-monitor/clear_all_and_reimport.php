<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "\n=== CLEAR ALL DATA AND RE-IMPORT ===\n\n";

echo "[1/4] Clearing all tables...\n";
DB::table('idle_alarms')->truncate();
DB::table('alarm_raw')->truncate();
DB::table('devices')->truncate();
DB::table('import_logs')->truncate();
echo "✅ All tables cleared\n\n";

echo "[2/4] Syncing devices from Howen API...\n";
try {
    $deviceService = new \App\Services\HowenDeviceService();
    $synced = $deviceService->syncDevices();
    echo "✅ Synced {$synced} devices\n";
    if ($synced == 0) {
        echo "⚠️  WARNING: 0 devices synced - device endpoint not working?\n";
    }
} catch (\Exception $e) {
    echo "❌ Device sync error: {$e->getMessage()}\n";
}

echo "\n[3/4] Importing alarms from Howen API...\n";

// Check logs BEFORE import
$logsBefore = DB::table('import_logs')->count();

// Import alarms
try {
    $command = $app->make('Illuminate\Contracts\Console\Kernel');
    $command->call('howen:import-alarms');
    echo "✅ Import command executed\n";
} catch (\Exception $e) {
    echo "❌ Import error: {$e->getMessage()}\n";
}

// Process queue
try {
    $command = $app->make('Illuminate\Contracts\Console\Kernel');
    $command->call('queue:work', ['--once' => true]);
    echo "✅ Queue processed\n";
} catch (\Exception $e) {
    echo "⚠️  Queue process note: {$e->getMessage()}\n";
}

// Check logs AFTER import
$logsAfter = DB::table('import_logs')->count();
$newLogs = $logsAfter - $logsBefore;

echo "\n[4/4] Checking imported data source...\n\n";

// Get latest import log
$latestLog = DB::table('import_logs')
    ->orderBy('id', 'desc')
    ->first();

if ($latestLog) {
    echo "  Latest Import Log:\n";
    echo "  - Job: {$latestLog->job_name}\n";
    echo "  - Status: {$latestLog->status}\n";
    echo "  - Records: {$latestLog->total_record}\n";
    echo "  - Message: {$latestLog->message}\n";
}

// Count data
$deviceCount = DB::table('devices')->count();
$alarmCount = DB::table('alarm_raw')->count();

echo "\n  Data Counts:\n";
echo "  - Devices: {$deviceCount}\n";
echo "  - Alarm Raw: {$alarmCount}\n";

// Check if using mock
$isMock = false;
if ($alarmCount > 0) {
    $firstAlarm = DB::table('alarm_raw')->first();
    $rawJson = json_decode($firstAlarm->raw_json, true);
    
    // Check for mock indicators
    if (isset($rawJson['deviceName']) && in_array($rawJson['deviceName'], ['GPE-B-8322', 'GPE-FT-873', 'GPE-DTI-807'])) {
        if ($alarmCount == 2) {  // Mock returns exactly 2 records per page
            $isMock = true;
        }
    }
}

echo "\n  Data Source:\n";
if ($isMock && $alarmCount < 10) {
    echo "  ⚠️  MOCK DATA DETECTED\n";
    echo "     - Device names: GPE-B-8322, GPE-FT-873\n";
    echo "     - Count: exactly 2 (mock pattern)\n";
    echo "     - Reason: Real Howen API endpoint not responding\n";
} else if ($alarmCount > 10) {
    echo "  ✅ REAL DATA FROM API\n";
    echo "     - Large volume > 10 records\n";
    echo "     - Likely from real Howen API\n";
} else {
    echo "  ❓ UNCLEAR\n";
    echo "     - Data volume too small to determine\n";
    echo "     - Check import logs for details\n";
}

echo "\n";
