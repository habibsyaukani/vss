<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\HowenDeviceService;

echo "=========================================\n";
echo "🛰️ TEST FIND ALL DEVICES API\n";
echo "=========================================\n\n";

$service = new HowenDeviceService();
$devices = collect($service->fetchAllDevices());

echo "Total Devices Fetched: " . $devices->count() . "\n";

if ($devices->count() > 0) {
    echo "Sample Device 1:\n";
    print_r((array)$devices->first());
    
    // check if it has speed
    $first = (array)$devices->first();
    if (isset($first['speed'])) {
        echo "✅ Speed field found!\n";
    } else {
        echo "❌ No speed field in findAll.action\n";
    }
}
