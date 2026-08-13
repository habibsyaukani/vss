<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiService = app(\App\Services\TracksolidApiService::class);

// Force to use HK node
$reflection = new \ReflectionClass($apiService);
$property = $reflection->getProperty('apiUrl');
$property->setAccessible(true);
$property->setValue($apiService, 'https://hk-open.tracksolidpro.com/route/rest');

echo "Testing with HK Node...\n";
$token = $apiService->getAccessToken();
if ($token) {
    echo "SUCCESS! Token: " . $token . "\n";
} else {
    echo "FAILED.\n";
}
