<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$mayAlarms = App\Models\IdleAlarm::whereMonth('starting_time', 5)
    ->whereYear('starting_time', 2026)
    ->orderBy('starting_time', 'asc')
    ->get();

$count = $mayAlarms->count();
if ($count == 0) {
    echo "Belum ada data bulan Mei 2026.\n";
    exit;
}

$earliest = $mayAlarms->first()->starting_time;
$latest = $mayAlarms->last()->starting_time;

$byDay = App\Models\IdleAlarm::selectRaw('DATE(starting_time) as date, count(*) as count')
    ->whereMonth('starting_time', 5)
    ->whereYear('starting_time', 2026)
    ->groupBy('date')
    ->orderBy('date', 'asc')
    ->get();

echo "Total data Mei: $count\n";
echo "Paling awal: $earliest\n";
echo "Paling akhir: $latest\n";
echo "\nDetail per hari:\n";
foreach($byDay as $day) {
    echo "{$day->date} : {$day->count} data\n";
}
