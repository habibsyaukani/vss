<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiService = app(\App\Services\TracksolidApiService::class);

// Coba tanpa target sama sekali - pakai akun yang login
$tests = [
    ['method' => 'jimi.user.device.location.list', 'params' => []],
    ['method' => 'jimi.user.device.list', 'params' => []],
    ['method' => 'jimi.user.device.location.list', 'params' => ['target' => 'manager@gpe.co.id']],
    ['method' => 'jimi.user.device.list', 'params' => ['target' => 'manager@gpe.co.id']],
];

foreach ($tests as $t) {
    $label = $t['method'] . ' target=' . ($t['params']['target'] ?? '(none)');
    echo "Testing: $label ...\n";
    $res = $apiService->callApi($t['method'], $t['params']);
    if (!empty($res['result']) && is_array($res['result'])) {
        echo "  SUCCESS! Got " . count($res['result']) . " items.\n";
        echo "  First item: " . json_encode($res['result'][0]) . "\n";
    } else {
        echo "  Empty/Failed: " . json_encode($res) . "\n";
    }
    echo "\n";
}
