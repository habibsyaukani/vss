<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=========================================\n";
echo "📊 MONITOR DATA IDLE & GPS (LATEST DATA)\n";
echo "=========================================\n\n";

$today = date('Y-m-d');
$todayStart = "$today 00:00:00";
$todayEnd   = "$today 23:59:59";
echo "📅 Tanggal Hari Ini: $today\n\n";

// 1. Check Idle Alarms Today (Fast Query)
$idleTodayCount = \App\Models\IdleAlarm::where('starting_time', '>=', $todayStart)
    ->where('starting_time', '<=', $todayEnd)
    ->count();
echo "🚨 Total Idle Alarms Hari Ini ($today): $idleTodayCount record\n\n";

// 2. 5 Data Idle Alarm Terbaru (Global)
echo "-----------------------------------------\n";
echo "🔥 5 DATA IDLE ALARM TERBARU DI DATABASE:\n";
echo "-----------------------------------------\n";

$latestIdle = \App\Models\IdleAlarm::orderBy('id', 'desc')->take(5)->get();

if ($latestIdle->isEmpty()) {
    echo "Belum ada data Idle Alarm.\n";
} else {
    foreach ($latestIdle as $idle) {
        echo sprintf(
            "• [%s] %s | Durasi: %d min | Start: %s\n",
            $idle->id,
            $idle->device_name,
            $idle->duration_minutes,
            $idle->starting_time
        );
    }
}

// 3. Raw Alarms Count
$rawAlarmCount = \App\Models\AlarmRaw::count();
$rawAlarmToday = \App\Models\AlarmRaw::where('start_time', '>=', $todayStart)
    ->where('start_time', '<=', $todayEnd)
    ->count();

echo "\n-----------------------------------------\n";
echo "📦 STATISTIK DATA RAW ALARM (HOWEN API):\n";
echo "-----------------------------------------\n";
echo "Total Raw Alarm di Database : $rawAlarmCount record\n";
echo "Total Raw Alarm Hari Ini    : $rawAlarmToday record\n";

// 4. Latest GPS Track time (MAX gps_time)
$maxGpsTimeRaw = \App\Models\GpsTrackRaw::where('gps_time', '>=', $todayStart)->max('gps_time');
$maxGpsTimeDisplay = \App\Models\GpsTrack::where('gps_time', '>=', $todayStart)->max('gps_time');

echo "Waktu GPS Raw Terbaru (API) : " . ($maxGpsTimeRaw ?? 'Belum ada') . "\n";
echo "Waktu GPS Display Terbaru   : " . ($maxGpsTimeDisplay ?? 'Belum ada') . "\n";

echo "=========================================\n";
