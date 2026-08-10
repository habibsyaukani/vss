<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\GpsTrackRaw;
use App\Models\GpsTrack;
use App\Models\AlarmRaw;
use App\Models\IdleAlarm;
use Carbon\Carbon;

echo "=========================================\n";
echo "🧹 INSPEKSI & PEMBERSIHAN DATA JAM MASA DEPAN (FUTURE TIMESTAMPS)\n";
echo "=========================================\n\n";

$now = now()->toDateTimeString();
echo "Jam Server Sekarang: $now\n\n";

// 1. Check GpsTrackRaw > now
$futureGpsRaw = GpsTrackRaw::where('gps_time', '>', $now)->count();
echo "• GpsTrackRaw dengan gps_time > sekarang : $futureGpsRaw records\n";

// 2. Check GpsTrack > now
$futureGpsDisplay = GpsTrack::where('gps_time', '>', $now)->count();
echo "• GpsTrack dengan gps_time > sekarang    : $futureGpsDisplay records\n";

// 3. Check AlarmRaw > now
$futureAlarmRaw = AlarmRaw::where('start_time', '>', $now)->count();
echo "• AlarmRaw dengan start_time > sekarang  : $futureAlarmRaw records\n";

// Delete invalid future records inserted during timezone mismatch
if ($futureGpsRaw > 0) {
    GpsTrackRaw::where('gps_time', '>', $now)->delete();
    echo "  ✅ Dihapus $futureGpsRaw data GpsTrackRaw invalid!\n";
}

if ($futureGpsDisplay > 0) {
    GpsTrack::where('gps_time', '>', $now)->delete();
    echo "  ✅ Dihapus $futureGpsDisplay data GpsTrack invalid!\n";
}

if ($futureAlarmRaw > 0) {
    AlarmRaw::where('start_time', '>', $now)->delete();
    echo "  ✅ Dihapus $futureAlarmRaw data AlarmRaw invalid!\n";
}

echo "\n=========================================\n";
echo "📊 MONITORING ULANG STAMP GPS REALTIME:\n";
echo "=========================================\n";

$todayStart = date('Y-m-d 00:00:00');
$maxGpsRaw = GpsTrackRaw::where('gps_time', '>=', $todayStart)->where('gps_time', '<=', $now)->max('gps_time');
$maxGpsDisplay = GpsTrack::where('gps_time', '>=', $todayStart)->where('gps_time', '<=', $now)->max('gps_time');

echo "Waktu GPS Raw Terbaru (Terkini) : " . ($maxGpsRaw ?? 'Belum ada') . "\n";
echo "Waktu GPS Display Terbaru       : " . ($maxGpsDisplay ?? 'Belum ada') . "\n";
echo "=========================================\n";
