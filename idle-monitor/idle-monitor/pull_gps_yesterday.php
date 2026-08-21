<?php

/**
 * PULL GPS DATA - TANGGAL 11 JUNI 2026
 * 
 * Script untuk tarik data GPS kemarin (11 Juni 2026)
 * Range: 00:00:00 - 23:59:59
 * 
 * Usage:
 *   C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe pull_gps_yesterday.php
 */

require __DIR__ . '/vendor/autoload.php';

use App\Jobs\ImportGpsTrackJob;
use App\Jobs\ProcessGpsTrackJob;
use App\Services\GpsTrackSyncService;
use App\Services\VssAuthService;
use App\Models\Device;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "========================================\n";
echo "  PULL GPS DATA - 11 JUNI 2026\n";
echo "========================================\n\n";

// Set tanggal kemarin (11 Juni 2026)
$targetDate = '2026-06-11';
$beginTime = Carbon::parse("{$targetDate} 00:00:00");
$endTime = Carbon::parse("{$targetDate} 23:59:59");

echo "📅 Target Date: {$targetDate}\n";
echo "⏰ Time Range: {$beginTime->format('Y-m-d H:i:s')} - {$endTime->format('Y-m-d H:i:s')}\n";
echo "\n";

try {
    // 1. Get VSS token
    echo "🔐 Getting VSS authentication token...\n";
    $authService = app(VssAuthService::class);
    $token = $authService->getToken();
    
    if (!$token) {
        throw new Exception('Failed to get VSS token. Please check VssAuthService.');
    }
    
    echo "✅ Token obtained successfully\n\n";

    // 2. Get active devices
    echo "🚗 Loading active devices...\n";
    $devices = Device::where('status', 'active')
        ->whereNotNull('device_id')
        ->orderBy('device_name')
        ->get();
    
    if ($devices->isEmpty()) {
        throw new Exception('No active devices found with device_id');
    }
    
    echo "✅ Found {$devices->count()} active devices\n\n";

    // 3. Sync each device
    $syncService = app(GpsTrackSyncService::class);
    
    echo "📡 Starting GPS data sync...\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $totalFetched = 0;
    $totalSaved = 0;
    $totalDevicesProcessed = 0;
    $deviceErrors = [];
    
    $startTime = microtime(true);
    
    foreach ($devices as $index => $device) {
        $deviceNum = $index + 1;
        echo "[{$deviceNum}/{$devices->count()}] {$device->device_name} (ID: {$device->device_id})\n";
        
        try {
            $result = $syncService->syncDevice(
                $token,
                $device->device_id,
                $beginTime->format('Y-m-d H:i:s'),
                $endTime->format('Y-m-d H:i:s')
            );
            
            $totalFetched += $result['total_fetched'];
            $totalSaved += $result['total_saved'];
            $totalDevicesProcessed++;
            
            echo "   ✅ Fetched: {$result['total_fetched']} | Saved: {$result['total_saved']} | Pages: {$result['pages']}\n";
            
            // Delay 500ms between devices
            usleep(500000);
            
        } catch (Exception $e) {
            echo "   ❌ Error: {$e->getMessage()}\n";
            $deviceErrors[] = [
                'device_name' => $device->device_name,
                'device_id' => $device->device_id,
                'error' => $e->getMessage(),
            ];
        }
        
        echo "\n";
    }
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📊 IMPORT SUMMARY\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    echo "✅ Devices Processed: {$totalDevicesProcessed} / {$devices->count()}\n";
    echo "✅ Total Records Fetched: {$totalFetched}\n";
    echo "✅ Total Records Saved: {$totalSaved}\n";
    echo "⏱️  Duration: {$duration} seconds\n";
    
    if (!empty($deviceErrors)) {
        echo "\n⚠️  Errors: " . count($deviceErrors) . " devices failed\n";
        foreach ($deviceErrors as $error) {
            echo "   - {$error['device_name']}: {$error['error']}\n";
        }
    }
    
    echo "\n";
    
    // 4. Now process the raw data
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "🔄 PROCESSING RAW DATA\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "Processing gps_tracks_raw → gps_tracks...\n";
    
    // Dispatch ProcessGpsTrackJob
    dispatch(new ProcessGpsTrackJob());
    
    echo "✅ ProcessGpsTrackJob dispatched to queue\n";
    echo "ℹ️  Run queue worker to process: php artisan queue:work\n";
    
    echo "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ COMPLETED SUCCESSFULLY\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "📝 NEXT STEPS:\n";
    echo "1. Run queue worker: php artisan queue:work\n";
    echo "2. Check import_logs table for job status\n";
    echo "3. Verify gps_tracks table for data\n\n";
    
    echo "🔍 VERIFICATION QUERIES:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "-- Check raw data\n";
    echo "SELECT COUNT(*) as total FROM gps_tracks_raw WHERE DATE(gps_time) = '{$targetDate}';\n\n";
    echo "-- Check processed data\n";
    echo "SELECT COUNT(*) as total FROM gps_tracks WHERE DATE(gps_time) = '{$targetDate}';\n\n";
    echo "-- Latest GPS per device\n";
    echo "SELECT device_name, MAX(gps_time) as latest FROM gps_tracks WHERE DATE(gps_time) = '{$targetDate}' GROUP BY device_name;\n\n";
    
} catch (Exception $e) {
    echo "\n❌ FATAL ERROR:\n";
    echo $e->getMessage() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}
