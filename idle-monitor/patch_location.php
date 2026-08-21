<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$alarms = \App\Models\IdleAlarm::where('device_id', 'like', '864%')->orWhere('device_id', 'like', '865%')->get();
$count = 0;
foreach($alarms as $alarm) {
    if (!$alarm->ending_location && $alarm->latitude_start && $alarm->longitude_start) {
        $alarm->ending_location = $alarm->longitude_start . ',' . $alarm->latitude_start;
        $alarm->latitude_end = $alarm->latitude_start;
        $alarm->longitude_end = $alarm->longitude_start;
        $alarm->save();
        $count++;
    }
}
echo "Patched $count records with ending_location.\n";
