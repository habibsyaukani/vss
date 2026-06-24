<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AlarmRaw;

$startCount = AlarmRaw::where('alarm_state', 1)->where('alarm_type', 32)->count();
$endCount = AlarmRaw::where('alarm_state', 0)->where('alarm_type', 32)->count();

echo "START records (alarmState=1, type=32): {$startCount}\n";
echo "END records (alarmState=0, type=32):   {$endCount}\n";
