<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiService = app(\App\Services\TracksolidApiService::class);

$methods = [
    ['method' => 'jimi.user.device.location.list', 'params' => ['target' => 'wiwie@gpe.co.id']],
    ['method' => 'jimi.user.device.list', 'params' => ['target' => 'wiwie@gpe.co.id']],
];

foreach ($methods as $m) {
    echo "Testing {$m['method']}...\n";
    $res = $apiService->callApi($m['method'], $m['params']);
    print_r($res);
}
