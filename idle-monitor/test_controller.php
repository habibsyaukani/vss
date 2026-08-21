<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = \Illuminate\Http\Request::create('/idle-alarm/data', 'POST', [
    'start_date' => '2026-08-18',
    'end_date' => '2026-08-18',
    'device_ids' => \App\Models\Device::pluck('device_id')->toArray()
]);

$controller = new \App\Http\Controllers\Frontend\IdleAlarmController();
$response = $controller->data($request);
echo $response->getContent();
