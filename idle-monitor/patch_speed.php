<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Reset Tracksolid speed to null
\App\Models\IdleAlarm::where('device_id', 'like', '864%')->orWhere('device_id', 'like', '865%')->update(['start_speed' => null, 'end_speed' => null]);

echo "Speed patched to null for Tracksolid records.\n";
