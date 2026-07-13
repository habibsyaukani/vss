<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Check all B-series devices (HD 785 dump trucks)
echo "=== GPE-B-xxx DEVICES (HD 785) ===\n";
$bDevices = DB::table('devices')
    ->where('device_name', 'LIKE', 'GPE-B-%')
    ->orderBy('device_name')
    ->get(['device_id', 'device_name', 'unit_code', 'location', 'series']);

echo str_pad("No", 4) . str_pad("Device Name", 20) . str_pad("Unit Code", 12) . str_pad("Location", 15) . "Series\n";
echo str_repeat("-", 70) . "\n";
foreach ($bDevices as $i => $d) {
    $ok = ($d->location === 'SELATAN' && str_contains($d->series ?? '', 'HD 785')) ? '✓' : '❌';
    echo str_pad($i+1, 4) 
        . str_pad($d->device_name ?? '-', 20)
        . str_pad($d->unit_code ?? '-', 12)
        . str_pad($d->location ?? 'NULL', 15)
        . ($d->series ?? 'NULL') . " $ok\n";
}

// Check OHT series devices
echo "\n=== OHT DEVICES ===\n";
$ohtDevices = DB::table('devices')
    ->where('device_name', 'LIKE', 'GPE-OHT-%')
    ->orderBy('device_name')
    ->get(['device_id', 'device_name', 'unit_code', 'location', 'series']);

echo str_pad("No", 4) . str_pad("Device Name", 25) . str_pad("Unit Code", 12) . str_pad("Location", 15) . "Series\n";
echo str_repeat("-", 75) . "\n";
foreach ($ohtDevices as $i => $d) {
    echo str_pad($i+1, 4) 
        . str_pad($d->device_name ?? '-', 25)
        . str_pad($d->unit_code ?? '-', 12)
        . str_pad($d->location ?? 'NULL', 15)
        . ($d->series ?? 'NULL') . "\n";
}

// Summary by series
echo "\n=== SERIES SUMMARY ===\n";
$summary = DB::table('devices')
    ->select('series', 'location', DB::raw('count(*) as total'))
    ->whereNotNull('series')
    ->groupBy('series', 'location')
    ->orderBy('series')
    ->orderBy('location')
    ->get();

echo str_pad("Series", 25) . str_pad("Location", 20) . "Count\n";
echo str_repeat("-", 55) . "\n";
foreach ($summary as $s) {
    echo str_pad($s->series, 25) . str_pad($s->location ?? 'NULL', 20) . $s->total . "\n";
}
