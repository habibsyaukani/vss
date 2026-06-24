<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║           CEK DATA IDLE ALARMS MEI 2026 (LENGKAP)             ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Semua tanggal Mei yang mungkin ada data
$dates = [
    '2026-05-25', '2026-05-26', '2026-05-27', '2026-05-28', 
    '2026-05-29', '2026-05-30', '2026-05-31'
];

echo "📊 Jumlah Idle Alarms per Tanggal (Akhir Mei):\n";
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

// Total Mei 2026
$meiTotal = DB::table('idle_alarms')
    ->whereRaw('MONTH(starting_time) = 5 AND YEAR(starting_time) = 2026')
    ->count();

echo "📈 Total Mei 2026: {$meiTotal} records\n";
echo "\n";

// Cek data mentah 31 Mei
$raw31 = DB::table('alarm_raw')
    ->whereDate('start_time', '2026-05-31')
    ->count();

$idle31 = DB::table('alarm_raw')
    ->whereDate('start_time', '2026-05-31')
    ->where('alarm_type', 32)
    ->count();

echo "📦 Data Mentah 31 Mei:\n";
echo "   Total: {$raw31} records\n";
echo "   Type 32 (Idle): {$idle31} records\n";

echo "\n";
