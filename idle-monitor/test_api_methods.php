<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiService = app(\App\Services\TracksolidApiService::class);

$methods = [
    ['method' => 'jimi.user.device.location.list', 'params' => ['target' => 'wiwie@gpe.co.id']],
    ['method' => 'jimi.device.ptg.list', 'params' => ['target' => 'wiwie@gpe.co.id']],
    ['method' => 'jimi.device.bound.list', 'params' => ['target' => 'wiwie@gpe.co.id']],
    ['method' => 'jimi.user.device.list', 'params' => []],
];

foreach ($methods as $m) {
    echo "Testing {$m['method']}...\n";
    $res = $apiService->callApi($m['method'], $m['params']);
    if (!empty($res['result']) && is_array($res['result'])) {
        echo "SUCCESS! Got " . count($res['result']) . " items.\n";
        print_r($res['result'][0] ?? null);
    } else {
        echo "Empty or failed.\n";
    }
}
