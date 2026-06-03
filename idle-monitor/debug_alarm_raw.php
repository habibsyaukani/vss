<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get raw alarm data
$alarms = \App\Models\AlarmRaw::where('alarm_type', 100)->get();

echo "=== DEBUGGING ALARM RAW DATA ===\n\n";

foreach ($alarms as $alarm) {
    echo "GUID: {$alarm->guid}\n";
    echo "Device: {$alarm->device_name}\n";
    echo "Duration Seconds: {$alarm->duration_seconds}\n";
    echo "End Detail: {$alarm->end_detail}\n";
    echo "\nRAW JSON:\n";
    
    $raw = json_decode($alarm->raw_json, true);
    echo json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    
    echo "\n" . str_repeat("=", 80) . "\n\n";
}

echo "\nKEY FIELDS TO CHECK:\n";
echo "- alarmValue (untuk start_detail)\n";
echo "- endDetail atau alarmDetail (untuk end_detail)\n";
echo "- alarmTimeLength (untuk duration_seconds - USE THIS, DON'T CALCULATE)\n";
echo "- speed vs endSpeed\n";
