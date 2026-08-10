<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=========================================\n";
echo "🧪 TESTING HOWEN API PARAMETER PARAM CHECK\n";
echo "=========================================\n\n";

$authService = app(\App\Services\HowenAuthService::class);
$token = $authService->getToken();
$beginTime = now()->subHours(6)->format('Y-m-d H:i:s');
$endTime   = now()->format('Y-m-d H:i:s');
$client = new \GuzzleHttp\Client(['timeout' => 30, 'verify' => false]);
$apiUrl = config('vss.howen_api_url');

// Test 1: WITH empty deviceID parameter
echo "--- TEST 1: Sending 'deviceID' => '' ---\n";
try {
    $res1 = $client->post("{$apiUrl}/alarm/apiFindAllByTime.action", [
        'form_params' => [
            'token' => $token,
            'pageNum' => 1,
            'pageCount' => 50,
            'beginTime' => $beginTime,
            'endTime' => $endTime,
            'alarmType' => '',
            'deviceID' => '',
        ],
    ]);
    $data1 = json_decode($res1->getBody()->getContents(), true);
    $items1 = $data1['data']['dataList'] ?? [];
    echo "Count with empty deviceID: " . count($items1) . " items\n\n";
} catch (\Exception $e) {
    echo "Error 1: " . $e->getMessage() . "\n\n";
}

// Test 2: WITHOUT deviceID parameter
echo "--- TEST 2: OMITTING deviceID parameter ---\n";
try {
    $res2 = $client->post("{$apiUrl}/alarm/apiFindAllByTime.action", [
        'form_params' => [
            'token' => $token,
            'pageNum' => 1,
            'pageCount' => 50,
            'beginTime' => $beginTime,
            'endTime' => $endTime,
        ],
    ]);
    $data2 = json_decode($res2->getBody()->getContents(), true);
    $items2 = $data2['data']['dataList'] ?? [];
    echo "Count without deviceID: " . count($items2) . " items\n\n";
} catch (\Exception $e) {
    echo "Error 2: " . $e->getMessage() . "\n\n";
}

echo "=========================================\n";
