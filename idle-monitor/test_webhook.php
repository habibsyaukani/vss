<?php

$url = 'http://localhost/api/tracksolid/webhook';
// If using artisan serve, port is 8000, but using Laragon it's probably standard 80.
// Let's use the exact artisan serve url if needed, or we can just send POST request directly in PHP curl.

// The payload format from Tracksolid API docs
$payload = [
    'msgType' => 'jimi.push.device.alarm',
    'data' => json_encode([
        'imei' => '865478070069424',
        'deviceName' => 'TEST-WEBHOOK-123',
        'alarmType' => 'stayAlertOn',
        'alarmName' => 'Idling Alert',
        'lat' => '1.050411',
        'lng' => '117.682666',
        'alarmTime' => gmdate('Y-m-d H:i:s') // Simulate UTC time
    ])
];

// If curl is available
$ch = curl_init('http://localhost/api/tracksolid/webhook'); // Change to correct URL if needed
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));

$response = curl_exec($ch);
if(curl_errno($ch)){
    echo 'Curl error: ' . curl_error($ch);
}
curl_close($ch);

echo "Response from webhook:\n";
echo $response . "\n";
