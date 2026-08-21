<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\VssAuthService;
use App\Models\Device;
use App\Models\GpsTrackRaw;
use Illuminate\Support\Facades\Http;

echo "=========================================\n";
echo "🛰️ TEST LIVE RESPONSE VSS GPS TRACK API\n";
echo "=========================================\n\n";

$authService = new VssAuthService();
$token = $authService->getToken();
echo "1. VSS Token: " . substr($token, 0, 15) . "...\n";

// Find a device that actually has recent data
$recentTrack = GpsTrackRaw::orderBy('id', 'desc')->first();
$deviceId = '73207093'; // Fallback
$deviceName = 'Unknown';
if ($recentTrack) {
    $deviceId = $recentTrack->device_id;
    $device = Device::where('device_id', $deviceId)->first();
    $deviceName = $device ? $device->device_name : 'Unknown';
}

echo "2. Device ID Test: {$deviceName} ({$deviceId})\n";

$nowApp = now();
$beginApp = $nowApp->copy()->subHours(2); // Look back 2 hours to be safe

// API requires WIB (+7)
$beginWib = $beginApp->copy()->setTimezone('Asia/Jakarta');
$nowWib = $nowApp->copy()->setTimezone('Asia/Jakarta');

echo "3. Application Time (WITA) : {$beginApp->format('Y-m-d H:i:s')} -> {$nowApp->format('Y-m-d H:i:s')}\n";
echo "4. API Request Time (WIB)  : {$beginWib->format('Y-m-d H:i:s')} -> {$nowWib->format('Y-m-d H:i:s')}\n";

$baseUrl = config('vss.base_url', 'http://vss.ptdigital.co.id');
$response = Http::withOptions(['verify' => false])->timeout(30)->post("{$baseUrl}/vss/track/getApiTrackList.action", [
    'token'     => $token,
    'deviceID'  => $deviceId,
    'beginTime' => $beginWib->format('Y-m-d H:i:s'),
    'endTime'   => $nowWib->format('Y-m-d H:i:s'),
    'pageNum'   => 1,
    'pageCount' => 10,
]);

$data = $response->json();
$records = $data['data']['dataList'] ?? [];

echo "\n-----------------------------------------\n";
echo "📊 RAW RESPONSE GPS TRACK LIST FROM VSS API:\n";
echo "-----------------------------------------\n";
echo "Total Records Received: " . count($records) . "\n\n";

if (!empty($records)) {
    foreach (array_slice($records, 0, 10) as $i => $rec) {
        echo sprintf(
            " [%d] %s | Speed: %d km/h | Raw createtime: %s | Raw reportTime: %s\n",
            $i + 1,
            $rec['deviceName'] ?? $deviceId,
            $rec['speed'] ?? 0,
            $rec['createtime'] ?? 'N/A',
            $rec['reportTime'] ?? 'N/A'
        );
    }
} else {
    echo "ℹ️ No tracks found for this device in last 2 hours.\n";
    echo "Full Response: " . json_encode($data) . "\n";
}

echo "\n=========================================\n";
