<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║     CEK KAPAN DATA 1 JUNI MASUK KE DATABASE (created_at)      ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Cek data yang created_at hari ini tapi start_time 1 Juni
$todayCreated = DB::table('alarm_raw')
    ->whereDate('created_at', '2026-06-08')  // Created hari ini
    ->whereDate('start_time', '2026-06-01')  // Start time 1 Juni
    ->count();

echo "📅 Data dengan start_time 1 Juni yang masuk hari ini:\n";
echo "   Count: {$todayCreated}\n";
echo "\n";

// Cek semua data yang start_time 1 Juni (kapan pun created_at)
$allJune1 = DB::table('alarm_raw')
    ->whereDate('start_time', '2026-06-01')
    ->count();

echo "📦 Total data dengan start_time 1 Juni (semua created_at):\n";
echo "   Count: {$allJune1}\n";
echo "\n";

// Cek data yang masuk hari ini (created_at = today)
$todayAll = DB::table('alarm_raw')
    ->whereDate('created_at', '2026-06-08')
    ->count();

echo "📊 Total data masuk hari ini (created_at = 2026-06-08):\n";
echo "   Count: {$todayAll}\n";
echo "\n";

// Cek breakdown by start_time date untuk data yang created today
echo "📈 Breakdown by start_time (data created hari ini):\n";
$breakdown = DB::table('alarm_raw')
    ->whereDate('created_at', '2026-06-08')
    ->selectRaw('DATE(start_time) as date, COUNT(*) as count')
    ->groupBy('date')
    ->orderBy('date')
    ->get();

foreach ($breakdown as $row) {
    echo "   {$row->date}: {$row->count} records\n";
}

echo "\n";

// Cek apakah command pull untuk 1 Juni pernah dijalankan dengan melihat start_time range
$minDate = DB::table('alarm_raw')->min('start_time');
$maxDate = DB::table('alarm_raw')->max('start_time');

echo "📌 Range start_time di alarm_raw:\n";
echo "   Earliest: {$minDate}\n";
echo "   Latest: {$maxDate}\n";

echo "\n";
