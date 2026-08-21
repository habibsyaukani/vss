<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = App\Models\IdleAlarm::where('alarm_type', '!=', 'Idle')->delete();
echo "Deleted {$count} non-idle alarms.\n";
