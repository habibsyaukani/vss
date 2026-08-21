<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\HowenDeviceService;

$service = app(HowenDeviceService::class);
$devices = $service->fetchDevices();

$deviceList = isset($devices['list']) ? $devices['list'] : (isset($devices[0]) ? $devices : []);

$targetDevice = null;
foreach ($deviceList as $d) {
    $name = $d['devicename'] ?? ($d['device_name'] ?? '');
    
    // Cari GPE-B-806 (HD 785 yang nyasar)
    if ($name === 'GPE-B-806' || strpos($name, 'GPE7801') !== false) {
        $targetDevice = $d;
        break;
    }
}

if ($targetDevice) {
    file_put_contents('C:\Users\Administrator\.gemini\antigravity-ide\brain\f6bd6fc9-3041-4b05-af04-4e51cbe71bae\scratch\api_device_sample.json', json_encode($targetDevice, JSON_PRETTY_PRINT));
    echo "Saved to api_device_sample.json\n";
} else {
    echo "Device not found\n";
}

// Juga save 1 random device sebagai perbandingan
if (count($deviceList) > 0) {
    file_put_contents('C:\Users\Administrator\.gemini\antigravity-ide\brain\f6bd6fc9-3041-4b05-af04-4e51cbe71bae\scratch\api_device_random.json', json_encode($deviceList[0], JSON_PRETTY_PRINT));
}
