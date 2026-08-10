<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\IdleAlarm;
use App\Models\GpsTrack;
use App\Models\GpsTrackRaw;
use App\Models\AlarmRaw;
use Carbon\Carbon;

echo "=========================================\n";
echo "📊 DIAGNOSA REALTIME KEDUANYA (IDLE & SPEED)\n";
echo "=========================================\n\n";

$now = now();
echo "Jam Server Sekarang (WITA) : " . $now->format('Y-m-d H:i:s P') . "\n\n";

// 1. Check IdleAlarms inserted / starting in last 30 minutes
echo "-----------------------------------------\n";
echo "🚨 5 IDLE ALARM TERBARU (DISORTIR DARI STARTING_TIME DESC):\n";
echo "-----------------------------------------\n";
$latestIdle = IdleAlarm::orderBy('starting_time', 'desc')->take(5)->get();

foreach ($latestIdle as $idle) {
    echo sprintf(
        "• [%s] %s | Durasi: %dm | Start: %s | End: %s | Saved At: %s\n",
        $idle->id,
        $idle->device_name,
        $idle->duration_minutes,
        $idle->starting_time,
        $idle->ending_time ?? 'N/A',
        $idle->created_at
    );
}

echo "\n-----------------------------------------\n";
echo "🏎️ 5 SPEED / GPS TRACKS TERBARU (DISORTIR DARI GPS_TIME DESC):\n";
echo "-----------------------------------------\n";
$latestSpeed = GpsTrack::orderBy('gps_time', 'desc')->take(5)->get();

foreach ($latestSpeed as $spd) {
    echo sprintf(
        "• [%s] %s | Speed: %d km/h | GPS Time: %s | Saved At: %s\n",
        $spd->id,
        $spd->device_name,
        $spd->speed,
        $spd->gps_time,
        $spd->created_at
    );
}

echo "\n-----------------------------------------\n";
echo "📈 REKAP PENARIKAN REALTIME 30 MENIT TERAKHIR:\n";
echo "-----------------------------------------\n";
$fifteenMinAgo = $now->copy()->subMinutes(30);

$rawAlarm30m = AlarmRaw::where('created_at', '>=', $fifteenMinAgo)->count();
$idleSaved30m = IdleAlarm::where('created_at', '>=', $fifteenMinAgo)->count();
$gpsRawSaved30m = GpsTrackRaw::where('created_at', '>=', $fifteenMinAgo)->count();
$gpsDisplaySaved30m = GpsTrack::where('created_at', '>=', $fifteenMinAgo)->count();

echo "• Raw Alarm Ditarik (30m Terakhir)   : $rawAlarm30m records\n";
echo "• Idle Alarm Diproses (30m Terakhir) : $idleSaved30m records\n";
echo "• GPS Raw Ditarik (30m Terakhir)     : $gpsRawSaved30m records\n";
echo "• Speed Display Saved (30m Terakhir) : $gpsDisplaySaved30m records\n";

echo "=========================================\n";
