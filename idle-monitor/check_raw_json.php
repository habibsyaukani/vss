<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$sample = DB::table('alarm_raw')
    ->whereNotNull('raw_json')
    ->where('alarm_type', '32')
    ->orderBy('id', 'desc')
    ->first();

if ($sample) {
    echo "=== RAW JSON from API (Idle Alarm) ===\n";
    $json = json_decode($sample->raw_json, true);
    print_r($json);
} else {
    echo "No raw_json found for alarm_type 32.\n";
}
