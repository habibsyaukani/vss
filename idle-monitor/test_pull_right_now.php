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

    $alarms = $data['data']['dataList'] ?? [];
    echo "✅ SUCCESS: Found " . count($alarms) . " alarms in dataList!\n\n";

    foreach ($alarms as $i => $alarm) {
        $name  = $alarm['deviceName'] ?? $alarm['devicename'] ?? 'N/A';
        $type  = $alarm['alarmType'] ?? $alarm['alarmtype'] ?? 'N/A';
        $state = $alarm['alarmState'] ?? $alarm['alarmstate'] ?? 'N/A';
        $start = $alarm['startAlarmTimeStr'] ?? $alarm['startalarmtimestr'] ?? 'N/A';
        $end   = $alarm['endAlarmTimeStr'] ?? $alarm['endalarmtimestr'] ?? 'N/A';
        $val   = $alarm['alarmvalue'] ?? $alarm['alarmValue'] ?? 'N/A';

        echo sprintf(
            "• [#%d] %-12s | Type: %-3s | State: %s | Start: %s | End: %s | Val: %s\n",
            $i + 1,
            $name,
            $type,
            $state,
            $start,
            $end,
            $val
        );
    }

} catch (\Exception $e) {
    echo "❌ API Request Failed: " . $e->getMessage() . "\n";
}

echo "\n=========================================\n";
