<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "==========================================\n";
echo "  DEVICE ACTIVITY - LAST 30 DAYS\n";
echo "==========================================\n\n";

// Check device activity per day for last 30 days
$activity = DB::table('gps_tracks_raw')
    ->select(
        DB::raw('DATE(gps_time) as date'),
        DB::raw('COUNT(DISTINCT device_name) as devices'),
        DB::raw('COUNT(*) as records')
    )
    ->where('gps_time', '>=', DB::raw('DATE_SUB(NOW(), INTERVAL 30 DAY)'))
    ->groupBy('date')
    ->orderBy('date', 'desc')
    ->get();

echo "📅 Daily Activity:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo sprintf("%-12s | %-8s | %s\n", "Date", "Devices", "Records");
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

foreach ($activity as $day) {
    echo sprintf("%-12s | %8d | %s\n", $day->date, $day->devices, number_format($day->records));
}

echo "\n";

// Find best date
if ($activity->isNotEmpty()) {
    $best = $activity->sortByDesc('devices')->first();
    echo "✅ Tanggal dengan device terbanyak:\n";
    echo "   Date: {$best->date}\n";
    echo "   Devices: {$best->devices}\n";
    echo "   Records: " . number_format($best->records) . "\n\n";
}
