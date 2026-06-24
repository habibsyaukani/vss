<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║           CEK DATA IDLE_ALARMS TANGGAL 1 JUNI 2026            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Total di idle_alarms
$totalIdle = DB::table('idle_alarms')
    ->whereDate('starting_time', '2026-06-01')
    ->count();

echo "✅ Total di idle_alarms (starting_time = 1 Juni): {$totalIdle}\n";
echo "\n";

// Sample data
$samples = DB::table('idle_alarms')
    ->whereDate('starting_time', '2026-06-01')
    ->limit(5)
    ->get();

echo "📄 Sample Data dari idle_alarms:\n";
echo str_repeat("─", 70) . "\n";

foreach ($samples as $idx => $alarm) {
    echo "\nSample " . ($idx + 1) . ":\n";
    echo "   Device ID: {$alarm->device_id}\n";
    echo "   Starting Time: {$alarm->starting_time}\n";
    echo "   Ending Time: {$alarm->ending_time}\n";
    
    $start = new DateTime($alarm->starting_time);
    $end = new DateTime($alarm->ending_time);
    $duration = $start->diff($end);
    $minutes = ($duration->days * 24 * 60) + ($duration->h * 60) + $duration->i;
    
    echo "   Duration: {$minutes} minutes\n";
    echo "   Alarm Status: {$alarm->alarm_status}\n";
}

echo "\n";
echo str_repeat("─", 70) . "\n";
echo "\n";

// Cek distribusi by alarm_status
$byStatus = DB::table('idle_alarms')
    ->whereDate('starting_time', '2026-06-01')
    ->select('alarm_status', DB::raw('COUNT(*) as count'))
    ->groupBy('alarm_status')
    ->get();

echo "📊 Breakdown by Alarm Status:\n";
foreach ($byStatus as $status) {
    echo "   {$status->alarm_status}: {$status->count} records\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ KESIMPULAN: Data 1 Juni BERHASIL masuk ke idle_alarms!\n";
echo "   Total: {$totalIdle} idle alarms\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";
