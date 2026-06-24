<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║        CEK DATA MENTAH (alarm_raw) PER TANGGAL 26 MEI-3 JUNI  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$dates = [
    '2026-05-26', '2026-05-27', '2026-05-28', '2026-05-29', '2026-05-30', '2026-05-31',
    '2026-06-01', '2026-06-02', '2026-06-03'
];

echo "📦 Jumlah Data Mentah (alarm_raw) per Tanggal:\n";
echo str_repeat("─", 70) . "\n";

$totalRaw = 0;
foreach ($dates as $date) {
    // Cek berdasarkan start_time
    $count = DB::table('alarm_raw')
        ->whereDate('start_time', $date)
        ->count();
    
    $totalRaw += $count;
    $bar = str_repeat("█", min(50, $count / 50));
    
    echo sprintf("%-12s : %5d  %s\n", $date, $count, $bar);
}

echo str_repeat("─", 70) . "\n";
echo sprintf("%-12s : %5d\n", "TOTAL RAW", $totalRaw);
echo "\n";

// Cek total alarm_raw hari ini (yang baru masuk)
$todayRaw = DB::table('alarm_raw')
    ->whereDate('created_at', '2026-06-06')
    ->count();
    
echo "📊 Total alarm_raw masuk hari ini (2026-06-06): {$todayRaw}\n";

// Cek khusus alarm type 32 (idle) untuk range 26 Mei - 3 Juni
$idleRaw = DB::table('alarm_raw')
    ->where('alarm_type', 32)
    ->whereBetween('start_time', ['2026-05-26', '2026-06-03 23:59:59'])
    ->count();
    
echo "🚗 Alarm type 32 (Idle) di range 26 Mei - 3 Juni: {$idleRaw}\n";

// Sample 1 data untuk lihat struktur
$sample = DB::table('alarm_raw')
    ->whereDate('created_at', '2026-06-06')
    ->first();

if ($sample) {
    echo "\n📄 Sample Data (ID: {$sample->id}):\n";
    echo "   GUID: {$sample->guid}\n";
    echo "   Device ID: {$sample->device_id}\n";
    echo "   Start Time: {$sample->start_time}\n";
    echo "   Alarm Type: {$sample->alarm_type}\n";
    echo "   Alarm State: {$sample->alarm_state}\n";
    echo "   Duration: {$sample->duration_seconds} seconds\n";
}

echo "\n";
