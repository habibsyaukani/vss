<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Oldest raw alarm June 22: " . \App\Models\AlarmRaw::whereDate('start_time', '2026-06-22')->min('start_time') . "\n";
echo "Max raw alarm June 22: " . \App\Models\AlarmRaw::whereDate('start_time', '2026-06-22')->max('start_time') . "\n";
