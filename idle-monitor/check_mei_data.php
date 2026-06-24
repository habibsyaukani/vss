<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$mayCount = DB::table('idle_alarms')
    ->whereRaw('MONTH(starting_time) = 5')
    ->whereRaw('YEAR(starting_time) = 2026')
    ->count();

$mayDetailByDate = DB::table('idle_alarms')
    ->selectRaw('DATE(starting_time) as date, COUNT(*) as count, SUM(duration_minutes) as total_duration')
    ->whereRaw('MONTH(starting_time) = 5')
    ->whereRaw('YEAR(starting_time) = 2026')
    ->groupBy('date')
    ->orderBy('date')
    ->get();

$juneCount = DB::table('idle_alarms')
    ->whereRaw('MONTH(starting_time) = 6')
    ->whereRaw('YEAR(starting_time) = 2026')
    ->count();

$totalAll = DB::table('idle_alarms')->count();

echo "╔════════════════════════════════════════╗\n";
echo "║     DATA IDLE BULAN MEI (2026)        ║\n";
echo "╚════════════════════════════════════════╝\n\n";
echo "📊 TOTAL MEI: " . $mayCount . " records\n\n";

echo "📅 DETAIL PER HARI:\n";
echo "─────────────────────────────────────────\n";
foreach($mayDetailByDate as $row) {
    echo $row->date . "  →  " . $row->count . " records (" . round($row->total_duration/60, 1) . " jam)\n";
}

echo "\n╔════════════════════════════════════════╗\n";
echo "║          RINGKASAN KESELURUHAN        ║\n";
echo "╚════════════════════════════════════════╝\n";
echo "Mei 2026:    " . $mayCount . " records\n";
echo "Juni 2026:   " . $juneCount . " records\n";
echo "Total semua: " . $totalAll . " records\n";
