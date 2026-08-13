<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiService = app(\App\Services\TracksolidApiService::class);

// Find a tracksolid device
$device = \App\Models\Device::whereNotNull('imei')->where('imei', '!=', '')->first();
if (!$device) {
    die("No Tracksolid device found.\n");
}

echo "Testing with IMEI: " . $device->imei . "\n\n";

$params = [
    'account' => env('TRACKSOLID_USERNAME'),
    'imeis' => $device->imei,
    'start_time' => now()->subDays(2)->format('Y-m-d H:i:s'),
    'end_time' => now()->format('Y-m-d H:i:s'),
];

echo "--- Testing jimi.open.platform.report.parking ---\n";
// Get token
$token = $apiService->getAccessToken();
$requestParams = [
    'method'      => 'jimi.open.platform.report.parking',
    'timestamp'   => \Carbon\Carbon::now('UTC')->format('Y-m-d H:i:s'),
    'app_key'     => env('TRACKSOLID_APP_KEY', ''),
    'sign_method' => 'md5',
    'v'           => '1.0',
    'format'      => 'json',
    'access_token'=> $token,
];
$requestParams = array_merge($requestParams, $params);
$ref = new \ReflectionMethod($apiService, 'generateSignature');
$ref->setAccessible(true);
$requestParams['sign'] = $ref->invoke($apiService, $requestParams);

$response = \Illuminate\Support\Facades\Http::asForm()->post(env('TRACKSOLID_API_URL', 'http://open.10000track.com/route/rest'), $requestParams);
echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n";

echo "\n--- Testing jimi.device.report.parking ---\n";
$params2 = [
    'imeis' => $device->imei,
    'begin_time' => now()->subDays(2)->format('Y-m-d H:i:s'),
    'end_time' => now()->format('Y-m-d H:i:s'),
];
$response3 = $apiService->callApi('jimi.device.report.parking', $params2);
print_r($response3);
