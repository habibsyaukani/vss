<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiService = app(\App\Services\TracksolidApiService::class);

$methods = [
    ['method' => 'jimi.user.device.list', 'params' => []], // No target!
    ['method' => 'jimi.device.user.list', 'params' => ['target' => 'wiwie@gpe.co.id']], // alternative method name
];

foreach ($methods as $m) {
    echo "Testing {$m['method']}...\n";
    $res = $apiService->callApi($m['method'], $m['params']);
    if (!empty($res['result']) && is_array($res['result'])) {
        echo "SUCCESS! Got " . count($res['result']) . " items.\n";
    } else {
        echo "Empty or failed: " . json_encode($res) . "\n";
    }
}
