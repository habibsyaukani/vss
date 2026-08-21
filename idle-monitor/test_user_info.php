<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiService = app(\App\Services\TracksolidApiService::class);

echo "Testing jimi.user.info.get...\n";
$res = $apiService->callApi('jimi.user.info.get', []);
print_r($res);
