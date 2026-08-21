<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiService = app(\App\Services\TracksolidApiService::class);

// Get first active device IMEI
$device = \App\Models\Device::whereNotNull('imei')->first();

if (!$device) {
    die("No device found.\n");
}

$params = [
    'imei' => $device->imei,
    'begin_time' => now()->subDays(1)->format('Y-m-d H:i:s'),
    'end_time' => now()->format('Y-m-d H:i:s'),
];
$response = $apiService->callApi('jimi.device.track.list', $params);
print_r($response);

// Test raw request to see body
$token = $apiService->getAccessToken();
$params['access_token'] = $token;
$commonParams = [
    'method'      => 'jimi.device.track.list',
    'timestamp'   => \Carbon\Carbon::now('UTC')->format('Y-m-d H:i:s'),
    'app_key'     => env('TRACKSOLID_APP_KEY', ''),
    'sign_method' => 'md5',
    'v'           => '1.0',
    'format'      => 'json',
];
$requestParams = array_merge($commonParams, $params);
// sign
ksort($requestParams);
$str = '';
foreach ($requestParams as $key => $value) $str .= $key . $value;
$secret = env('TRACKSOLID_APP_SECRET', '');
$requestParams['sign'] = strtoupper(md5($secret . $str . $secret));

$resp = \Illuminate\Support\Facades\Http::asForm()->post(env('TRACKSOLID_API_URL'), $requestParams);
echo "\nRAW STATUS: " . $resp->status() . "\n";
echo "RAW BODY: " . $resp->body() . "\n";
