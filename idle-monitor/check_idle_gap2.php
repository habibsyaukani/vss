<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AlarmRaw;
use App\Models\IdleAlarm;

$today = '2026-06-05';

// Cek alarm_type yang ada di alarm_raw hari ini
echo "=== ALARM TYPES di alarm_raw hari ini ===\n";
$types = AlarmRaw::whereDate('created_at', $today)
    ->selectRaw('alarm_type, COUNT(*) as cnt')
    ->groupBy('alarm_type')
    ->orderBy('cnt', 'desc')
    ->get();

foreach ($types as $t) {
    echo "  alarm_type={$t->alarm_type}: {$t->cnt} records\n";
}

echo "\n=== ALARM_RAW sample - alarm_type terbanyak ===\n";
$sample = AlarmRaw::whereDate('created_at', $today)->where('alarm_type', 236)->first();
if ($sample) {
    echo "raw_json sample: \n";
    $raw = json_decode($sample->raw_json, true);
    print_r($raw);
}

echo "\n=== IDLE_ALARMS - cek tanggal starting_time ===\n";
$idleDates = IdleAlarm::selectRaw('DATE(starting_time) as dt, COUNT(*) as cnt')
    ->groupBy('dt')
    ->orderBy('dt', 'desc')
    ->limit(10)
    ->get();

foreach ($idleDates as $d) {
    echo "  starting_time={$d->dt}: {$d->cnt} records\n";
}

echo "\n=== IDLE_ALARMS - cek tanggal created_at ===\n";
$idleCreatedDates = IdleAlarm::selectRaw('DATE(created_at) as dt, COUNT(*) as cnt')
    ->groupBy('dt')
    ->orderBy('dt', 'desc')
    ->limit(10)
    ->get();

foreach ($idleCreatedDates as $d) {
    echo "  created_at={$d->dt}: {$d->cnt} records\n";
}
