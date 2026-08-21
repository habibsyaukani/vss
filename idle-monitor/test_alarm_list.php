<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\TracksolidApiService;

$api = new TracksolidApiService();
$response = $api->callApi('jimi.device.alarm.list', [
    'imei' => '865478070069424', // Use the IMEI from earlier
    'begin_time' => '2026-08-19 00:00:00', // UTC time string? Let's check
    'end_time' => '2026-08-19 23:59:59'
]);

print_r($response);
