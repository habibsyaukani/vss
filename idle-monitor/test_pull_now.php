<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Device;
use App\Services\TracksolidAlarmService;
use Carbon\Carbon;

$alarmService = app(TracksolidAlarmService::class);

$beginTime = Carbon::now('Asia/Makassar')->subHours(48)->setTimezone('UTC')->format('Y-m-d H:i:s');
$endTime = Carbon::now('Asia/Makassar')->setTimezone('UTC')->format('Y-m-d H:i:s');

echo "Waktu Mulai (UTC) : {$beginTime}\n";
echo "Waktu Akhir (UTC) : {$endTime}\n";

$imeisToFetch = Device::whereRaw('LENGTH(device_id) > 10')->pluck('device_id')->toArray();
$chunks = array_chunk($imeisToFetch, 50);

$totalFetched = 0;
$totalInserted = 0;

foreach ($chunks as $chunk) {
    $imeiString = implode(',', $chunk);
    $stats = $alarmService->syncAlarms($imeiString, $beginTime, $endTime);
    $totalFetched += $stats['total_fetched'] ?? 0;
    $totalInserted += $stats['total_inserted'] ?? 0;
}
echo "Total Alarms Fetched  : {$totalFetched}\n";
echo "Total New Alarms Saved: {$totalInserted}\n";
