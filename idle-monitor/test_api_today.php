<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$service = new \App\Services\HowenAlarmService();
$todayStart = '2026-06-23 00:00:00';
$todayEnd = '2026-06-23 23:59:59';

echo "Fetching page 1 from API for $todayStart to $todayEnd...\n";
$alarms = $service->fetchAlarmsPage(1, 100, $todayStart, $todayEnd);

echo "Returned count: " . count($alarms) . "\n";
if (count($alarms) > 0) {
    echo "Sample Alarm Type: " . ($alarms[0]['alarmtype'] ?? $alarms[0]['alarm_type'] ?? 'unknown') . "\n";
} else {
    echo "API returned empty array or error.\n";
}
