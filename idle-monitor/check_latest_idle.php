<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=========================================\n";
echo "📊 MONITOR DATA IDLE & GPS (LATEST DATA)\n";
echo "=========================================\n\n";

$today = date('Y-m-d');
echo "📅 Tanggal Hari Ini: $today\n\n";

// 1. Check Idle Alarms Today
$idleTodayCount = \App\Models\IdleAlarm::whereDate('starting_time', $today)->count();
echo "🚨 Total Idle Alarms Hari Ini ($today): $idleTodayCount record\n";

// 2. Check GPS Tracks Today
$gpsTodayCount = \App\Models\GpsTrack::whereDate('gps_time', $today)->count();
echo "📍 Total GPS Track Hari Ini ($today): $gpsTodayCount record\n\n";

// 3. 5 Data Idle Alarm Terbaru (Global)
echo "-----------------------------------------\n";
echo "🔥 5 DATA IDLE ALARM TERBARU:\n";
echo "-----------------------------------------\n";

$latestIdle = \App\Models\IdleAlarm::orderBy('id', 'desc')->take(5)->get();

if ($latestIdle->isEmpty()) {
    echo "Belum ada data Idle Alarm.\n";
} else {
    foreach ($latestIdle as $idle) {
        echo sprintf(
            "• [%s] %s | Durasi: %d menit (%d dtk) | Start: %s\n",
            $idle->id,
            $idle->device_name,
            $idle->duration_minutes,
            $idle->duration_seconds,
            $idle->starting_time
        );
    }
}

// 4. Raw Alarms Count
$rawAlarmCount = \App\Models\AlarmRaw::count();
$rawAlarmToday = \App\Models\AlarmRaw::whereDate('start_time', $today)->count();

echo "\n-----------------------------------------\n";
echo "📦 STATISTIK DATA RAW ALARM (HOWEN API):\n";
echo "-----------------------------------------\n";
echo "Total Raw Alarm di Database : $rawAlarmCount record\n";
echo "Total Raw Alarm Hari Ini    : $rawAlarmToday record\n";

echo "\n=========================================\n";
