<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$today = date('Y-m-d');
$todayStart = "$today 00:00:00";
$todayEnd   = "$today 23:59:59";

echo "=========================================\n";
echo "🔍 INSPEKSI CANDIDATE IDLE ALARM HARI INI ($today)\n";
echo "=========================================\n\n";

$alarms = \App\Models\AlarmRaw::where('alarm_type', 32)
    ->where('alarm_state', 0)
    ->where('start_time', '>=', $todayStart)
    ->where('start_time', '<=', $todayEnd)
    ->get();

echo "Total Type 32, State 0 Hari Ini: " . $alarms->count() . " records\n\n";

foreach ($alarms as $alarmRaw) {
    $durationFromStart = 0;
    if (!empty($alarmRaw->alarm_value) && preg_match('/dur:(\d+)/', $alarmRaw->alarm_value, $m)) {
        $durationFromStart = (int)$m[1];
    }

    $durationFromEnd = 0;
    if (!empty($alarmRaw->end_detail) && preg_match('/dur:(\d+)/', $alarmRaw->end_detail, $m)) {
        $durationFromEnd = (int)$m[1];
    }

    $alarmTimeLength = (int)($alarmRaw->duration_seconds ?? 0);

    $durationSeconds = $durationFromStart > 0 ? $durationFromStart : 
                      ($durationFromEnd > 0 ? $durationFromEnd : $alarmTimeLength);
    
    if ($durationSeconds <= 0 && !empty($alarmRaw->start_time) && !empty($alarmRaw->end_time)) {
        $startTime = \Carbon\Carbon::parse($alarmRaw->start_time);
        $endTime = \Carbon\Carbon::parse($alarmRaw->end_time);
        $durationSeconds = $endTime->diffInSeconds($startTime);
    }

    $hasEndTime = !empty($alarmRaw->end_time);
    $isValid = ($durationSeconds > 0 && $hasEndTime);

    $inIdleTable = \App\Models\IdleAlarm::where('guid', $alarmRaw->guid)->exists();

    echo sprintf(
        "• ID: %d | Device: %-12s | Start: %s | End: %-19s | Dur: %4d dtk | Valid: %s | Saved: %s\n",
        $alarmRaw->id,
        $alarmRaw->device_name,
        $alarmRaw->start_time,
        $alarmRaw->end_time ?? 'NULL',
        $durationSeconds,
        $isValid ? 'YES' : 'NO',
        $inIdleTable ? 'YES' : 'NO'
    );
}

echo "\n=========================================\n";
