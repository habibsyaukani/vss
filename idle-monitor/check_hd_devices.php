<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Get all HD devices with their current location and series
$devices = DB::table('devices')
    ->where('device_name', 'LIKE', '%-HD-%')
    ->orderBy('device_name')
    ->get(['device_id', 'device_name', 'unit_code', 'location', 'series', 'status']);

echo "=== HD DEVICES (Total: " . count($devices) . ") ===\n";
echo str_pad("No", 4) . str_pad("Device Name", 30) . str_pad("Unit Code", 12) . str_pad("Location", 15) . str_pad("Series", 15) . "Status\n";
echo str_repeat("-", 90) . "\n";

foreach ($devices as $i => $d) {
    echo str_pad($i+1, 4) 
        . str_pad($d->device_name ?? '-', 30) 
        . str_pad($d->unit_code ?? '-', 12) 
        . str_pad($d->location ?? 'NULL', 15) 
        . str_pad($d->series ?? 'NULL', 15) 
        . ($d->status ?? '-') . "\n";
}

echo "\n=== UNIT CODES TO MATCH ===\n";
$targetCodes = [
    'GPE7801','GPE7802','GPE7803','GPE7805','GPE7806','GPE7807',
    'GPE7808','GPE7809','GPE7810','GPE7811','GPE7812','GPE7813',
    'GPE7815','GPE7816','GPE7817','GPE7818','GPE7819','GPE7820'
];

echo "Looking for these unit codes:\n";
$found = DB::table('devices')
    ->whereIn('unit_code', $targetCodes)
    ->orderBy('unit_code')
    ->get(['device_id', 'device_name', 'unit_code', 'location', 'series']);

echo str_pad("Unit Code", 12) . str_pad("Device Name", 30) . str_pad("Location", 15) . "Series\n";
echo str_repeat("-", 70) . "\n";
foreach ($found as $d) {
    echo str_pad($d->unit_code ?? '-', 12)
        . str_pad($d->device_name ?? '-', 30)
        . str_pad($d->location ?? 'NULL', 15)
        . ($d->series ?? 'NULL') . "\n";
}

echo "\nNot found:\n";
$foundCodes = $found->pluck('unit_code')->toArray();
$notFound = array_diff($targetCodes, $foundCodes);
foreach ($notFound as $code) {
    echo "  - $code\n";
}
