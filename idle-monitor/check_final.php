<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$total = App\Models\IdleAlarm::count();
$earliest = App\Models\IdleAlarm::min('starting_time');
$latest = App\Models\IdleAlarm::max('starting_time');

echo "=== RINGKASAN idle_alarms ===\n";
echo "Total: $total\n";
echo "Terlama: $earliest\n";
echo "Terbaru: $latest\n";

echo "\n=== Per Tanggal (desc) ===\n";
$byDay = DB::table('idle_alarms')
    ->selectRaw("DATE(starting_time) as date, count(*) as count")
    ->groupBy('date')
    ->orderBy('date', 'desc')
    ->get();
foreach($byDay as $d) {
    echo "{$d->date} : {$d->count}\n";
}
