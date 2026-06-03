<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Clear idle_alarms
\DB::table('idle_alarms')->truncate();
echo "Truncated idle_alarms\n\n";

// Process idle alarms from alarm_raw
$service = new \App\Services\HowenAlarmService();
$alarms = \App\Models\AlarmRaw::where('alarm_type', 100)->get();

foreach ($alarms as $raw) {
    $processed = $service->processIdleAlarm(json_decode($raw->raw_json, true));
    
    if ($processed) {
        \App\Models\IdleAlarm::updateOrCreate(
            ['guid' => $processed['guid']],
            $processed
        );
    }
}

echo "\n=== RESULT ===\n";
$results = \App\Models\IdleAlarm::all();
foreach ($results as $alarm) {
    echo "GUID: {$alarm->guid}\n";
    echo "  Device: {$alarm->device_name}\n";
    echo "  Duration Seconds: {$alarm->duration_seconds}\n";
    echo "  Duration Minutes: {$alarm->duration_minutes}\n";
    echo "  Start Detail: {$alarm->start_detail}\n";
    echo "  End Detail: {$alarm->end_detail}\n";
    echo "\n";
}
