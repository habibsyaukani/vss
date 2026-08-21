<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Device;

echo "═══════════════════════════════════════════════════════════════\n";
echo "  VERIFICATION: Devices Series & Location Update\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Count total devices
$totalDevices = Device::count();
echo "✅ Total devices: {$totalDevices}\n";

// Count devices with series
$withSeries = Device::whereNotNull('series')->count();
echo "✅ Devices with series: {$withSeries}\n";

// Count devices with location
$withLocation = Device::whereNotNull('location')->count();
echo "✅ Devices with location: {$withLocation}\n\n";

// Sample data
echo "📋 Sample Data:\n";
echo str_repeat('-', 63) . "\n";

$samples = Device::whereIn('device_name', [
    'GPE-B-806', 'GPE-DT-1015', 'GPE-DT-1182', 
    'GPE-HD-701', 'GPE-FT-860', 'GPE-LV-890'
])
->get(['device_name', 'series', 'location']);

foreach ($samples as $device) {
    printf("%-15s | %-20s | %s\n", 
        $device->device_name, 
        $device->series, 
        $device->location
    );
}

echo str_repeat('-', 63) . "\n\n";

// Series distribution
echo "📊 Series Distribution:\n";
$seriesDist = Device::select('series', \DB::raw('count(*) as count'))
    ->whereNotNull('series')
    ->groupBy('series')
    ->orderBy('count', 'desc')
    ->get();

foreach ($seriesDist as $item) {
    printf("   %-25s : %d devices\n", $item->series, $item->count);
}

echo "\n";

// Location distribution
echo "📍 Location Distribution:\n";
$locationDist = Device::select('location', \DB::raw('count(*) as count'))
    ->whereNotNull('location')
    ->groupBy('location')
    ->orderBy('count', 'desc')
    ->get();

foreach ($locationDist as $item) {
    printf("   %-25s : %d devices\n", $item->location, $item->count);
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ VERIFICATION COMPLETE\n";
echo "═══════════════════════════════════════════════════════════════\n";
