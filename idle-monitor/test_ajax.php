<?php

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/idle-alarm/data");
curl_setopt($ch, CURLOPT_POST, 1);
// Pass start_date, end_date
$data = [
    'start_date' => '2026-08-18',
    'end_date' => '2026-08-18',
    // We don't need CSRF token if we just disable VerifyCsrfToken middleware for testing, 
    // but instead let's just query the DB directly the same way the controller does.
];
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
echo $response;
