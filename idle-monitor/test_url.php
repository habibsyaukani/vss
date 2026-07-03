<?php

// Test URL response
$url = 'http://localhost:8000/admin/system-control';

echo "Testing URL: $url\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ CURL Error: $error\n";
} else {
    echo "HTTP Code: $httpCode\n";
    
    // Separate headers and body
    list($headers, $body) = explode("\r\n\r\n", $response, 2);
    
    echo "\n--- HEADERS ---\n";
    echo substr($headers, 0, 500) . "\n";
    
    echo "\n--- BODY (first 1000 chars) ---\n";
    echo substr($body, 0, 1000) . "\n";
    
    if ($httpCode === 200) {
        echo "\n✅ Page loaded successfully!\n";
    } elseif ($httpCode === 302) {
        echo "\n⚠️ Redirect detected (probably needs login)\n";
    } else {
        echo "\n❌ HTTP Error $httpCode\n";
    }
}
