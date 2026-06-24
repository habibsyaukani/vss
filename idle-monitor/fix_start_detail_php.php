<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AlarmRaw;
use App\Models\IdleAlarm;
use Illuminate\Support\Facades\DB;

$alarms = AlarmRaw::where('alarm_type', 32)
    ->whereNull('start_detail')
    ->get();

$count = 0;
foreach($alarms as $alarm) {
    $json = json_decode($alarm->raw_json, true);
    $sd = $json['alarmvalue'] ?? $json['alarmValue'] ?? null;
    
    if ($sd) {
        // Update raw
        DB::table('alarm_raw')
            ->where('id', $alarm->id)
            ->update(['start_detail' => $sd]);
            
        // Update idle
        DB::table('idle_alarms')
            ->where('guid', $alarm->guid)
            ->update(['start_detail' => $sd]);
            
        $count++;
    }
}

echo "Successfully updated $count records using direct DB queries.\n";
