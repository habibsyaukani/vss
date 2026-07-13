<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\HowenDeviceService;

$service = app(HowenDeviceService::class);
echo "Mengambil data device dari API VSS...\n";
$devices = $service->fetchDevices();

echo "Total Device dari API: " . count($devices) . "\n\n";

if (count($devices) > 0) {
    $devicesList = array_values($devices);
    
    echo "Contoh Raw Data dari API (1 Device Pertama):\n";
    echo json_encode($devicesList[0], JSON_PRETTY_PRINT) . "\n\n";

    if (isset($devicesList[1])) {
        echo "Contoh Raw Data (Device ke-2):\n";
        echo json_encode($devicesList[1], JSON_PRETTY_PRINT) . "\n\n";
    }
    
    // Cari spesifik device GPE7801
    $found = false;
    foreach ($devicesList as $d) {
        // Cek if device has vehicle_name or custom properties
        $name = $d['device_name'] ?? ($d['vehicle_name'] ?? '');
        $unit_code = $d['custom_code'] ?? ($d['unit_code'] ?? '');
        
        if ($unit_code === 'GPE7801' || $name === 'GPE-B-806') {
            echo "Contoh Raw Data untuk Device GPE-B-806 / GPE7801:\n";
            echo json_encode($d, JSON_PRETTY_PRINT) . "\n";
            $found = true;
            break;
        }
    }
}
