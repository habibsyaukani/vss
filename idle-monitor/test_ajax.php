<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$request = Illuminate\Http\Request::create('/idle-alarm/data', 'POST', [
    'start_date' => '2026-08-24',
    'end_date' => '2026-08-24',
    'location' => 'SELATAN',
    'series' => '',
    'device_ids' => ['1','2','3'],
    'draw' => 1,
    'start' => 0,
    'length' => 10,
    'columns' => [
        ['data' => 'id', 'name' => 'id', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']]
    ],
    'order' => [
        ['column' => 0, 'dir' => 'asc']
    ]
]);

$controller = app()->make(App\Http\Controllers\Frontend\IdleAlarmController::class);
echo substr($controller->data($request)->getContent(), 0, 500);
