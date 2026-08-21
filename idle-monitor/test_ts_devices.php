<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiService = app(\App\Services\TracksolidApiService::class);
$token = $apiService->getAccessToken();
echo "Token: $token\n";

$response = $apiService->callApi('jimi.user.device.list', [
    'target' => 'wiwie@gpe.co.id'
]);

echo "jimi.user.device.list:\n";
print_r($response);

$response2 = $apiService->callApi('jimi.device.list', []);
echo "jimi.device.list:\n";
print_r($response2);
