<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\App\Models\IdleAlarm::where('device_id', 'like', '864%')->orWhere('device_id', 'like', '865%')->update([
    'ending_location' => null,
    'latitude_end' => null,
    'longitude_end' => null
]);

echo "End location patched back to null for Tracksolid records.\n";
