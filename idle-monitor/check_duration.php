<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== IDLE ALARMS DURATION CHECK ===\n\n";

$alarms = \App\Models\IdleAlarm::all();

foreach ($alarms as $alarm) {
    echo "GUID: {$alarm->guid}\n";
    echo "  Device: {$alarm->device_name}\n";
    echo "  Duration Seconds: {$alarm->duration_seconds}\n";
    echo "  Duration Minutes: {$alarm->duration_minutes}\n";
    echo "  Start Detail: {$alarm->start_detail}\n";
    echo "  End Detail: {$alarm->end_detail}\n";
    echo "\n";
}
