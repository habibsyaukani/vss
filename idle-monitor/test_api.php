<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new \App\Services\HowenAlarmService();
$res = $service->fetchAlarmsPage(1, 10, '2026-05-01 00:00:00', '2026-05-01 23:59:59');
echo "Records for May 1: " . count($res) . "\n";

$res2 = $service->fetchAlarmsPage(1, 10, '2026-05-15 00:00:00', '2026-05-15 23:59:59');
echo "Records for May 15: " . count($res2) . "\n";
