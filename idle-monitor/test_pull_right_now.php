<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=========================================\n";
echo "📡 TESTING HOWEN API REALTIME PULL RIGHT NOW\n";
echo "=========================================\n\n";

$authService = app(\App\Services\HowenAuthService::class);
try {
    $token = $authService->getToken();
    echo "✅ Howen Auth Token: " . substr($token, 0, 15) . "...\n";
} catch (\Exception $e) {
    echo "❌ Howen Auth Token Error: " . $e->getMessage() . "\n";
    exit(1);
}

$beginTime = now()->subHours(6)->format('Y-m-d H:i:s');
$endTime   = now()->format('Y-m-d H:i:s');

echo "Range: $beginTime → $endTime\n\n";

$client = new \GuzzleHttp\Client(['timeout' => 30, 'verify' => false]);
$apiUrl = config('vss.howen_api_url');

try {
    $response = $client->post("{$apiUrl}/alarm/apiFindAllByTime.action", [
        'form_params' => [
            'token' => $token,
            'pageNum' => 1,
            'pageCount' => 50,
            'beginTime' => $beginTime,
            'endTime' => $endTime,
        ],
    ]);

    $body = $response->getBody()->getContents();
    $data = json_decode($body, true);

    if (isset($data['status']) && $data['status'] == 10000 && isset($data['data'])) {
        $alarms = is_array($data['data']) ? $data['data'] : [$data['data']];
        echo "✅ SUCCESS: Fetched " . count($alarms) . " alarms from Howen API!\n\n";
        
        foreach (array_slice($alarms, 0, 5) as $i => $alarm) {
            echo "--- Alarm #$i ---\n";
            print_r($alarm);
            echo "\n";
        }
    } else {
        echo "⚠️ Response status: " . ($data['status'] ?? 'UNKNOWN') . " | Msg: " . ($data['message'] ?? $body) . "\n";
    }

} catch (\Exception $e) {
    echo "❌ API Request Failed: " . $e->getMessage() . "\n";
}

echo "\n=========================================\n";
