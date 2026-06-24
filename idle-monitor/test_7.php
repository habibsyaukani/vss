<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AlarmRaw;
use App\Models\IdleAlarm;

$idles = IdleAlarm::whereNull('start_detail')->get();
foreach($idles as $idle) {
    $raw = AlarmRaw::where('guid', $idle->guid)->first();
    if($raw) {
        $json = json_decode($raw->raw_json, true);
        $sd = $json['alarmvalue'] ?? $json['alarmValue'] ?? $raw->start_detail;
        if($sd) {
            $idle->start_detail = $sd;
            $idle->save();
            echo "Fixed $idle->id\n";
        }
    }
}
echo "Done.\n";
