<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$alarm = App\Models\IdleAlarm::with('device')->first();
$out = fopen('php://stdout', 'w');
fputcsv($out, [
    $alarm->device_id,
    $alarm->device_name,
    $alarm->alarm_type,
    $alarm->alarm_status,
    $alarm->starting_time,
    $alarm->starting_location,
    $alarm->ending_time,
    $alarm->ending_location,
    $alarm->start_detail,
    $alarm->end_detail,
    $alarm->start_speed,
    $alarm->end_speed,
    $alarm->report_time,
    $alarm->duration_seconds,
    $alarm->duration_minutes
], ';');
fclose($out);
echo "\n";
