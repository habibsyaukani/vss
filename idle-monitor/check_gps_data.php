<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "==========================================\n";
echo "  CHECK GPS DATA - 11 JUNI 2026\n";
echo "==========================================\n\n";

// Check total records
$total = DB::table('gps_tracks_raw')
    ->whereDate('gps_time', '2026-06-11')
    ->count();

echo "✅ Total GPS records: {$total}\n\n";

// Check top devices
$devices = DB::table('gps_tracks_raw')
    ->select('device_name', DB::raw('COUNT(*) as total'))
    ->whereDate('gps_time', '2026-06-11')
    ->groupBy('device_name')
    ->orderBy('total', 'desc')
    ->limit(20)
    ->get();

echo "📊 Top 20 devices with data:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
foreach ($devices as $device) {
    echo sprintf("%-20s: %5d records\n", $device->device_name, $device->total);
}

echo "\n";

// Check devices with 0 records
$totalDevices = DB::table('devices')
    ->where('status', 'active')
    ->whereNotNull('device_id')
    ->count();

$devicesWithData = DB::table('gps_tracks_raw')
    ->whereDate('gps_time', '2026-06-11')
    ->distinct('device_name')
    ->count('device_name');

$devicesWithoutData = $totalDevices - $devicesWithData;

echo "📈 Summary:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Total Active Devices: {$totalDevices}\n";
echo "Devices WITH data:    {$devicesWithData}\n";
echo "Devices WITHOUT data: {$devicesWithoutData}\n\n";

if ($devicesWithoutData > 0) {
    echo "ℹ️  Kemungkinan alasan devices tanpa data:\n";
    echo "  1. Device tidak aktif di tanggal 11 Juni\n";
    echo "  2. GPS device mati/offline\n";
    echo "  3. Device baru registered setelah 11 Juni\n";
    echo "  4. Data belum sempat ter-sync ke VSS\n\n";
}
