<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$alarm = \App\Models\AlarmRaw::find(598871);

if ($alarm) {
    echo "ID: " . $alarm->id . "\n";
    echo "Device: " . $alarm->device_name . "\n";
    echo "Start Time: " . $alarm->start_time . "\n";
    echo "End Time: " . $alarm->end_time . "\n";
    echo "Duration Seconds: " . $alarm->duration_seconds . "\n";
    echo "Alarm Value (start_detail): " . $alarm->alarm_value . "\n";
    echo "End Detail: " . $alarm->end_detail . "\n";
    echo "Alarm State: " . $alarm->alarm_state . "\n";
    echo "Alarm Type: " . $alarm->alarm_type . "\n";
    echo "Is Processed: " . $alarm->is_processed . "\n";
} else {
    echo "Record not found.\n";
}
