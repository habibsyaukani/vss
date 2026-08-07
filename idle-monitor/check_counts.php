<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "idle_alarms: " . \App\Models\IdleAlarm::count() . "\n";
echo "gps_tracks: " . \App\Models\GpsTrack::count() . "\n";
