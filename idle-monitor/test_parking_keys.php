<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\TracksolidApiService;

$api = new TracksolidApiService();
$response = $api->callApi('jimi.open.platform.report.parking', [
    'imeis' => '865478070069424', 
    'start_time' => '2026-08-17 00:00:00', 
    'end_time' => '2026-08-19 23:59:59'
]);

if (!empty($response['data']['rows'])) {
    print_r($response['data']['rows'][0]);
} else {
    echo "No rows returned";
}
