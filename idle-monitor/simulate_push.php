<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Api\TracksolidWebhookController;
use Illuminate\Http\Request;

// Create a simulated request exactly like Tracksolid would send
$data = [
    'imei' => '865478070069424',
    'deviceName' => 'SIMULASI-REALTIME-1',
    'alarmType' => 'stayAlertOn',
    'alarmName' => 'Idling Alert',
    'lat' => '1.050411',
    'lng' => '117.682666',
    'alarmTime' => gmdate('Y-m-d H:i:s') // UTC time right now
];

$request = Request::create('/api/tracksolid/webhook', 'POST', [
    'msgType' => 'jimi.push.device.alarm',
    'data' => json_encode($data)
]);

$controller = new TracksolidWebhookController();
$response = $controller->handlePush($request);

echo "Tembakan Webhook Selesai!\n";
echo "Response Server: " . $response->getContent() . "\n";
