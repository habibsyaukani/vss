<?php
// Simple API test script

$endpoints = [
    'http://localhost:8000/api/dashboard',
    'http://localhost:8000/api/idle-alarms',
    'http://localhost:8000/api/idle-alarms/device/732390518',
];

foreach ($endpoints as $url) {
    echo "Testing: $url\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($response === false) {
        echo "  ERROR: " . curl_error($ch) . "\n";
    } else {
        $data = json_decode($response, true);
        echo "  Status: $httpCode\n";
        echo "  Response: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
    
    curl_close($ch);
    echo "\n";
}
