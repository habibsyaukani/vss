<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\TracksolidApiService;

$api = new TracksolidApiService();

$device = \App\Models\Device::whereNotNull('imei')->where('imei', '!=', '')->first();
if (!$device) {
    echo "No tracksolid device found in DB.\n";
    exit;
}

$imei = $device->imei;
echo "Testing Idling report with IMEI: {$imei}\n";

$beginTime = now()->subDays(3)->format('Y-m-d H:i:s');
$endTime = now()->format('Y-m-d H:i:s');

$response = $api->callApi('jimi.open.platform.report.parking', [
    'account' => env('TRACKSOLID_USERNAME'),
    'imeis' => $imei,
    'start_time' => $beginTime,
    'end_time' => $endTime,
    'page_size' => 50,
    'start_row' => 0,
    'acc_type' => 'on'
]);

if (!$response['success']) {
    echo "API Failed: " . json_encode($response) . "\n";
    exit;
}

echo "Response:\n";
print_r($response['data'] ?? $response['result']);

echo "\nDone.\n";
