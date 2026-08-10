<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\AlarmRaw;
use App\Models\IdleAlarm;
use Illuminate\Support\Facades\DB;

echo "=========================================\n";
echo "📊 INSPEKSI ALARM RAW & IDLE ALARM PER JAM\n";
echo "=========================================\n\n";

$today = date('Y-m-d');

echo "1. Distribusi AlarmRaw (Type 32 - Idle) Hari Ini ($today):\n";
$alarmRawByHour = AlarmRaw::where('alarm_type', 32)
    ->whereDate('start_time', $today)
    ->selectRaw("HOUR(start_time) as hr, alarm_state, count(*) as total, sum(is_processed) as processed_count")
    ->groupBy('hr', 'alarm_state')
    ->orderBy('hr', 'asc')
    ->get();

foreach ($alarmRawByHour as $row) {
    $stateLabel = $row->alarm_state == 0 ? 'State 0 (ALARM_END)' : 'State 1 (ALARMING)';
    echo sprintf("   • Jam %02d:00 | %s | Total: %d | Processed: %d\n", $row->hr, $stateLabel, $row->total, $row->processed_count);
}

echo "\n-----------------------------------------\n";
echo "2. Distribusi IdleAlarm Tersimpan per Jam Start ($today):\n";
$idleByHour = IdleAlarm::whereDate('starting_time', $today)
    ->selectRaw("HOUR(starting_time) as hr, count(*) as total, avg(duration_minutes) as avg_dur")
    ->groupBy('hr')
    ->orderBy('hr', 'asc')
    ->get();

foreach ($idleByHour as $row) {
    echo sprintf("   • Jam %02d:00 | Total Idle: %d | Rata2 Durasi: %.1f min\n", $row->hr, $row->total, $row->avg_dur);
}

echo "\n-----------------------------------------\n";
echo "3. 10 Data AlarmRaw State 0 (ALARM_END) TERBARU Hari Ini:\n";
$latestRawState0 = AlarmRaw::where('alarm_type', 32)
    ->where('alarm_state', 0)
    ->whereDate('start_time', $today)
    ->orderBy('id', 'desc')
    ->take(10)
    ->get();

foreach ($latestRawState0 as $raw) {
    echo sprintf(
        "   • [ID: %d] %s | Start: %s | End: %s | Processed: %s\n",
        $raw->id,
        $raw->device_name,
        $raw->start_time,
        $raw->end_time ?? 'N/A',
        $raw->is_processed ? 'YES' : 'NO'
    );
}

echo "\n=========================================\n";
