<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\TracksolidApiService;

$api = new TracksolidApiService();

// We need an IMEI to test. Let's get one Tracksolid device from DB
$device = \App\Models\Device::whereNotNull('imei')->where('imei', '!=', '')->first();
if (!$device) {
    echo "No tracksolid device found in DB.\n";
    exit;
}

$imei = $device->imei;
echo "Testing with IMEI: {$imei}\n";

$beginTime = now()->subDays(3)->format('Y-m-d H:i:s');
$endTime = now()->format('Y-m-d H:i:s');

$response = $api->callApi('jimi.device.alarm.list', [
    'imeis' => $imei,
    'begin_time' => $beginTime,
    'end_time' => $endTime,
    'page_no' => 1,
    'page_size' => 500,
]);

if (!$response['success']) {
    echo "API Failed: " . json_encode($response) . "\n";
    exit;
}

$alarms = $response['result'] ?? [];
$types = [];

foreach ($alarms as $alarm) {
    $id = $alarm['alertTypeId'] ?? 'Unknown';
    $name = $alarm['alarmTypeName'] ?? 'Unknown';
    if (!isset($types[$id])) {
        $types[$id] = $name;
    }
}

echo "Found Alarm Types:\n";
foreach ($types as $id => $name) {
    echo "- ID: {$id} | Name: {$name}\n";
}

echo "\nDone.\n";
