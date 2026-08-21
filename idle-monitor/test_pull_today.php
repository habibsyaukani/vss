<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Device;
use App\Services\TracksolidAlarmService;

$alarmService = app(TracksolidAlarmService::class);

$beginTime = '2026-08-19 00:00:00';
$endTime = '2026-08-19 23:59:59';

$imeisToFetch = Device::whereRaw('LENGTH(device_id) > 10')->pluck('device_id')->toArray();
$chunks = array_chunk($imeisToFetch, 50);

$totalFetched = 0;
$totalInserted = 0;

foreach ($chunks as $chunk) {
    $imeiString = implode(',', $chunk);
    echo "Fetching for chunk of " . count($chunk) . " devices...\n";
    
    $stats = $alarmService->syncAlarms($imeiString, $beginTime, $endTime);
    
    if (!empty($stats['errors'])) {
        foreach ($stats['errors'] as $err) {
            echo "ERROR: " . $err . "\n";
        }
    }
    
    $totalFetched += $stats['total_fetched'] ?? 0;
    $totalInserted += $stats['total_inserted'] ?? 0;
}

echo "Alarm Sync Complete!\n";
echo "Total Alarms Fetched  : {$totalFetched}\n";
echo "Total New Alarms Saved: {$totalInserted}\n";
