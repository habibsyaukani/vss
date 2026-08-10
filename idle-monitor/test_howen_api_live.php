<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\HowenAlarmService;
use App\Services\VssAuthService;
use Carbon\Carbon;

echo "=========================================\n";
echo "🧪 UJI LANGSUNG HOWEN API (LIVE RESPONSE)\n";
echo "=========================================\n\n";

$authService = new VssAuthService();
$token = $authService->getToken();
echo "1. Token API obtenido : " . substr($token, 0, 15) . "...\n";

$appTz = config('app.timezone', 'Asia/Makassar');
$nowWita = now($appTz);
$startWita = $nowWita->copy()->subHours(2);

$beginTimeWib = $startWita->copy()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
$endTimeWib   = $nowWita->copy()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

echo "2. Jam Lokal WITA     : " . $startWita->format('Y-m-d H:i') . " → " . $nowWita->format('Y-m-d H:i') . "\n";
echo "3. Parameter ke API (WIB): $beginTimeWib → $endTimeWib\n\n";

$service = new HowenAlarmService();
$alarms = $service->fetchAlarmsPage(1, 100, $startWita->format('Y-m-d H:i:s'), $nowWita->format('Y-m-d H:i:s'));

echo "-----------------------------------------\n";
echo "📊 HASIL DARI HOWEN API SEKARANG:\n";
echo "-----------------------------------------\n";
echo "Total Alarm Diterima Halaman 1 : " . count($alarms) . " items\n\n";

if (!empty($alarms)) {
    echo "5 Alarm Terbaru dari API:\n";
    $slice = array_slice($alarms, 0, 5);
    foreach ($slice as $i => $item) {
        $devName = $item['deviceName'] ?? $item['devicename'] ?? 'Unknown';
        $type = $item['alarmTypeValue'] ?? $item['alarmtype'] ?? 'N/A';
        $startStr = $item['createtime'] ?? $item['start_time'] ?? 'N/A';
        $endStr = $item['endTime'] ?? $item['end_time'] ?? 'N/A';
        $state = $item['alarmState'] ?? 0;
        
        // Convert to WITA for display
        $startWitaStr = Carbon::parse($startStr, 'Asia/Jakarta')->setTimezone('Asia/Makassar')->format('Y-m-d H:i:s');
        
        echo sprintf(
            " [%d] %s | Type: %s | State: %s | Start (WIB): %s -> Start (WITA): %s\n",
            $i + 1,
            $devName,
            $type,
            $state == 0 ? 'ALARM_END' : 'ALARMING',
            $startStr,
            $startWitaStr
        );
    }
} else {
    echo "❌ API mengembalikan array KOSONG.\n";
}

echo "\n=========================================\n";
