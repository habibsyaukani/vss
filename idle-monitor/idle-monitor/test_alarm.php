<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiService = app(\App\Services\TracksolidApiService::class);

$begin = \Carbon\Carbon::now()->subDays(1)->format('Y-m-d H:i:s');
$end = \Carbon\Carbon::now()->format('Y-m-d H:i:s');

echo "Calling jimi.device.alarm.list from {$begin} to {$end}...\n";

// Let's get 5 IMEIs from DB
$devices = \App\Models\Device::whereRaw('LENGTH(device_id) > 10')->limit(5)->pluck('device_id')->toArray();
$imeis = implode(',', $devices);
echo "Testing for IMEIs: {$imeis}\n";

$response = $apiService->callApi('jimi.device.alarm.list', [
    'imeis' => $imeis,
    'begin_time' => $begin,
    'end_time' => $end,
    'page_no' => 1,
    'page_size' => 200,
]);

print_r($response);
