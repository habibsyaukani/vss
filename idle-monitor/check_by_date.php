<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║           CEK DATA IDLE ALARMS PER TANGGAL (MEI-JUNI)         ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$dates = [
    '2026-05-26', '2026-05-27', '2026-05-28', '2026-05-29', '2026-05-30', '2026-05-31',
    '2026-06-01', '2026-06-02', '2026-06-03', '2026-06-04', '2026-06-05', '2026-06-06'
];

echo "📊 Jumlah Idle Alarms per Tanggal:\n";
echo str_repeat("─", 70) . "\n";

$total = 0;
foreach ($dates as $date) {
    $count = DB::table('idle_alarms')
        ->whereDate('starting_time', $date)
        ->count();
    
    $total += $count;
    $bar = str_repeat("█", min(50, $count / 10));
    
    echo sprintf("%-12s : %5d  %s\n", $date, $count, $bar);
}

echo str_repeat("─", 70) . "\n";
echo sprintf("%-12s : %5d\n", "TOTAL", $total);
echo "\n";

// Cek range Mei lengkap
$meiTotal = DB::table('idle_alarms')
    ->whereRaw('MONTH(starting_time) = 5 AND YEAR(starting_time) = 2026')
    ->count();
    
$juneTotal = DB::table('idle_alarms')
    ->whereRaw('MONTH(starting_time) = 6 AND YEAR(starting_time) = 2026')
    ->count();

echo "📈 Summary:\n";
echo "   Mei 2026:  {$meiTotal} records\n";
echo "   Juni 2026: {$juneTotal} records\n";
echo "   Total:     " . ($meiTotal + $juneTotal) . " records\n";
echo "\n";
