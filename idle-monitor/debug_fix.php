<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$idle = DB::table('idle_alarms')->orderBy('id', 'desc')->first();
echo "=== IDLE ALARM ===\n";
print_r($idle);

$raw = DB::table('alarm_raw')->where('guid', $idle->guid)->first();
echo "\n=== MATCHING ALARM RAW ===\n";
if ($raw) {
    print_r($raw);
    $json = json_decode($raw->raw_json, true);
    echo "\nExtracted alarmvalue: " . ($json['alarmvalue'] ?? 'NULL') . "\n";
    echo "Extracted alarmValue: " . ($json['alarmValue'] ?? 'NULL') . "\n";
} else {
    echo "NO MATCH BY GUID\n";
    
    // Try matching by device_id and starting_time
    $raw2 = DB::table('alarm_raw')
        ->where('device_id', $idle->device_id)
        ->where('starting_time', $idle->starting_time)
        ->first();
    if ($raw2) {
        echo "Found by device_id & starting_time instead:\n";
        print_r($raw2);
        $json = json_decode($raw2->raw_json, true);
        echo "\nExtracted alarmvalue: " . ($json['alarmvalue'] ?? 'NULL') . "\n";
    }
}
