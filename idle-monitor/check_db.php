<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
echo json_encode(App\Models\Device::whereNotNull('device_name')->select('lokasi', 'location', 'series')->distinct()->get()->toArray(), JSON_PRETTY_PRINT);
