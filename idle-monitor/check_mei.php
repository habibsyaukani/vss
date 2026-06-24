<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$mayCount = \Illuminate\Support\Facades\DB::table('idle_alarms')
    ->whereRaw('MONTH(starting_time) = 5 AND YEAR(starting_time) = 2026')
    ->count();

$juneCount = \Illuminate\Support\Facades\DB::table('idle_alarms')
    ->whereRaw('MONTH(starting_time) = 6 AND YEAR(starting_time) = 2026')
    ->count();

$totalCount = \Illuminate\Support\Facades\DB::table('idle_alarms')->count();

$mayData = \Illuminate\Support\Facades\DB::table('idle_alarms')
    ->selectRaw('DATE(starting_time) as tanggal, COUNT(*) as jumlah, ROUND(SUM(duration_minutes)/60,1) as jam')
    ->whereRaw('MONTH(starting_time) = 5 AND YEAR(starting_time) = 2026')
    ->groupBy('tanggal')
    ->orderBy('tanggal')
    ->get();

echo "\n";
echo "╔════════════════════════════════════════╗\n";
echo "║     DATA IDLE BULAN MEI (2026)        ║\n";
echo "╚════════════════════════════════════════╝\n";
echo "\n";
echo "📊 TOTAL MEI: " . $mayCount . " records\n";
echo "\n";
echo "📅 DETAIL PER HARI:\n";
echo "──────────────────────────────────────────\n";

foreach($mayData as $row) {
    echo "  " . $row->tanggal . "  →  " . str_pad($row->jumlah, 3, " ", STR_PAD_LEFT) . " records  (" . str_pad($row->jam, 5, " ", STR_PAD_LEFT) . " jam)\n";
}

echo "\n";
echo "╔════════════════════════════════════════╗\n";
echo "║          RINGKASAN KESELURUHAN        ║\n";
echo "╚════════════════════════════════════════╝\n";
echo "Mei 2026:    " . $mayCount . " records\n";
echo "Juni 2026:   " . $juneCount . " records\n";
echo "Total semua: " . $totalCount . " records\n";
echo "\n";
