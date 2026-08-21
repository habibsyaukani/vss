<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\HowenDeviceService;
use Illuminate\Support\Facades\Log;

$service = app(HowenDeviceService::class);
echo "Mengambil data device dari API VSS...\n";
$devices = $service->fetchDevices();

echo "\nData Tipe: " . gettype($devices) . "\n";
echo "Jumlah Element: " . count($devices) . "\n";
echo "Keys: " . implode(', ', array_keys($devices)) . "\n\n";

if (isset($devices['list'])) {
    echo "Ditemukan key 'list', ini berarti object pagination.\n";
    echo "Total Data Asli: " . count($devices['list']) . " devices.\n\n";
    $deviceList = $devices['list'];
} else if (isset($devices[0]) && is_array($devices[0])) {
    echo "Ditemukan array berindeks, ini berarti array of devices langsung.\n";
    $deviceList = $devices;
} else {
    echo "Format tidak dikenal. Dumping semua data:\n";
    echo json_encode($devices, JSON_PRETTY_PRINT);
    exit;
}

if (count($deviceList) > 0) {
    echo "Contoh Raw Data dari API (1 Device Pertama):\n";
    echo json_encode($deviceList[0], JSON_PRETTY_PRINT) . "\n\n";

    echo "Contoh Raw Data (Device ke-2):\n";
    echo json_encode($deviceList[1], JSON_PRETTY_PRINT) . "\n\n";
    
    // Cari spesifik device GPE7801
    foreach ($deviceList as $d) {
        $name = $d['device_name'] ?? ($d['vehicle_name'] ?? ($d['plateNo'] ?? ''));
        $unit_code = $d['custom_code'] ?? ($d['unit_code'] ?? ($d['vehicleNo'] ?? ''));
        
        if ($unit_code === 'GPE7801' || $name === 'GPE-B-806' || strpos($name, '806') !== false) {
            echo "Contoh Raw Data untuk Device GPE-B-806 / GPE7801:\n";
            echo json_encode($d, JSON_PRETTY_PRINT) . "\n";
            break;
        }
    }
}
