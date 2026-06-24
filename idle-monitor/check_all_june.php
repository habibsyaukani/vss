<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║         CEK DATA IDLE ALARMS SEMUA TANGGAL JUNI 2026          ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Semua tanggal Juni
$dates = [];
for ($i = 1; $i <= 8; $i++) {
    $dates[] = '2026-06-' . str_pad($i, 2, '0', STR_PAD_LEFT);
}

echo "📊 Jumlah Idle Alarms per Tanggal:\n";
echo str_repeat("─", 70) . "\n";

$total = 0;
foreach ($dates as $date) {
    $count = DB::table('idle_alarms')
        ->whereDate('starting_time', $date)
        ->count();
    
    $total += $count;
    $bar = str_repeat("█", min(50, $count / 40));
    
    echo sprintf("%-12s : %5d  %s\n", $date, $count, $bar);
}

echo str_repeat("─", 70) . "\n";
echo sprintf("%-12s : %5d\n", "TOTAL", $total);
echo "\n";

// Cek data mentah untuk 7-8 Juni
echo "📦 Data Mentah (alarm_raw) untuk 7-8 Juni:\n";
echo str_repeat("─", 70) . "\n";

$raw7 = DB::table('alarm_raw')->whereDate('start_time', '2026-06-07')->count();
$raw8 = DB::table('alarm_raw')->whereDate('start_time', '2026-06-08')->count();
$idle7 = DB::table('alarm_raw')->whereDate('start_time', '2026-06-07')->where('alarm_type', 32)->count();
$idle8 = DB::table('alarm_raw')->whereDate('start_time', '2026-06-08')->where('alarm_type', 32)->count();

echo "   2026-06-07: {$raw7} total, {$idle7} idle (type 32)\n";
echo "   2026-06-08: {$raw8} total, {$idle8} idle (type 32)\n";

echo "\n";

// Cek sample data 7 Juni
$sample7 = DB::table('alarm_raw')
    ->whereDate('start_time', '2026-06-07')
    ->where('alarm_type', 32)
    ->first();

if ($sample7) {
    echo "📄 Sample Data 7 Juni (alarm_raw):\n";
    echo "   GUID: {$sample7->guid}\n";
    echo "   Device ID: {$sample7->device_id}\n";
    echo "   Start Time: {$sample7->start_time}\n";
    echo "   End Time: {$sample7->end_time}\n";
    echo "   Alarm State: {$sample7->alarm_state}\n";
    echo "   Duration: {$sample7->duration_seconds} seconds\n";
    echo "   Start Speed: {$sample7->start_speed}\n";
    echo "   End Speed: {$sample7->end_speed}\n";
}

echo "\n";
