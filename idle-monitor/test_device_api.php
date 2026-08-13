<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiService = app(\App\Services\TracksolidApiService::class);

echo "Calling jimi.user.device.list...\n";
$response = $apiService->callApi('jimi.user.device.list', [
    'target' => 'plantjo@gpe.co.id'
]);

print_r($response);
