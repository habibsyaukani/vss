<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "==========================================\n";
echo "  DEVICES DENGAN GPS DATA - 11 JUNI 2026\n";
echo "==========================================\n\n";

$devices = DB::table('gps_tracks_raw')
    ->select('device_name', DB::raw('COUNT(*) as total_records'), 
             DB::raw('MIN(gps_time) as first_gps'), 
             DB::raw('MAX(gps_time) as last_gps'))
    ->whereDate('gps_time', '2026-06-11')
    ->groupBy('device_name')
    ->orderBy('total_records', 'desc')
    ->get();

echo "Total devices dengan data: " . count($devices) . "\n";
echo "Total GPS records: " . number_format($devices->sum('total_records')) . "\n\n";

echo "List semua devices:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
printf("%-4s | %-15s | %-10s | %-8s | %-8s\n", 'No', 'Device', 'Records', 'First', 'Last');
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$no = 1;
foreach ($devices as $device) {
    $first = date('H:i', strtotime($device->first_gps));
    $last = date('H:i', strtotime($device->last_gps));
    printf("%-4d | %-15s | %10s | %8s | %8s\n", 
        $no++, 
        $device->device_name, 
        number_format($device->total_records),
        $first,
        $last
    );
}

echo "\n";

// Group by series
echo "📊 Group by Series:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$series = [
    'B' => 0,
    'DT' => 0,
    'FT' => 0,
    'HD' => 0,
    'LV' => 0,
    'WT' => 0,
    'GFTH' => 0,
];

foreach ($devices as $device) {
    if (strpos($device->device_name, 'GPE-B-') !== false) $series['B']++;
    elseif (strpos($device->device_name, 'GPE-DT-') !== false) $series['DT']++;
    elseif (strpos($device->device_name, 'GPE-FT-') !== false) $series['FT']++;
    elseif (strpos($device->device_name, 'GPE-HD-') !== false) $series['HD']++;
    elseif (strpos($device->device_name, 'GPE-LV-') !== false) $series['LV']++;
    elseif (strpos($device->device_name, 'GPE-WT-') !== false) $series['WT']++;
    elseif (strpos($device->device_name, 'GPE-GFTH-') !== false) $series['GFTH']++;
}

foreach ($series as $name => $count) {
    if ($count > 0) {
        printf("%-10s: %3d devices\n", "GPE-$name", $count);
    }
}

echo "\n";
