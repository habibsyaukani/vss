<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test ambil langsung dari API dengan token VSS yang terbukti bisa
$vssAuth = new \App\Services\VssAuthService();
\Illuminate\Support\Facades\Cache::forget('vss_token'); // Force fresh token
$token = $vssAuth->getToken();
echo "VSS Token: " . substr($token, 0, 10) . "...\n";

$client = new \GuzzleHttp\Client();
$apiUrl = 'https://vss.ptdigital.co.id/vss';

// Test ambil data tanggal hari ini
$today = date('Y-m-d');
echo "Testing date: $today\n\n";

// Coba dengan alarmType = 32 (Idle) dulu
$response = $client->post("$apiUrl/alarm/apiFindAllByTime.action", [
    'form_params' => [
        'token'      => $token,
        'pageNum'    => 1,
        'pageCount'  => 50,
        'beginTime'  => $today . ' 00:00:00',
        'endTime'    => $today . ' 23:59:59',
        'alarmType'  => '32',  // Type 32 = Idle Alarm
        'deviceID'   => '',
    ],
    'verify' => false,
]);

$data = json_decode($response->getBody()->getContents(), true);
echo "Status: " . ($data['status'] ?? 'null') . "\n";
if (isset($data['data']['totalCount'])) {
    echo "Total records (type 32 / idle): " . $data['data']['totalCount'] . "\n";
}
if (isset($data['data']['dataList']) && is_array($data['data']['dataList'])) {
    echo "Page 1 returned: " . count($data['data']['dataList']) . " records\n";
    if (!empty($data['data']['dataList'])) {
        echo "Sample: " . json_encode($data['data']['dataList'][0]) . "\n";
    }
} else {
    echo "No dataList found. Full response: " . json_encode($data) . "\n";
}

echo "\n--- Also trying without alarmType filter ---\n";
$response2 = $client->post("$apiUrl/alarm/apiFindAllByTime.action", [
    'form_params' => [
        'token'      => $token,
        'pageNum'    => 1,
        'pageCount'  => 10,
        'beginTime'  => $today . ' 00:00:00',
        'endTime'    => $today . ' 23:59:59',
        'alarmType'  => '',
        'deviceID'   => '',
    ],
    'verify' => false,
]);

$data2 = json_decode($response2->getBody()->getContents(), true);
echo "Status: " . ($data2['status'] ?? 'null') . "\n";
if (isset($data2['data']['totalCount'])) {
    echo "Total records (all types today): " . $data2['data']['totalCount'] . "\n";
}
if (isset($data2['data']['dataList']) && is_array($data2['data']['dataList'])) {
    echo "Page 1: " . count($data2['data']['dataList']) . " records\n";
    if (!empty($data2['data']['dataList'])) {
        // Show distinct alarm types
        $types = array_unique(array_column($data2['data']['dataList'], 'alarmtype'));
        echo "Alarm types found: " . implode(', ', $types) . "\n";
    }
}
