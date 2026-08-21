<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\GpsTrackRaw;
use App\Models\GpsTrack;
use App\Models\AlarmRaw;
use App\Models\IdleAlarm;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

echo "=========================================\n";
echo "🛠️ FIX TOTAL: HAPUS JAM INVALID MASA DEPAN & RE-PROCESS IDLE\n";
echo "=========================================\n\n";

$now = now()->toDateTimeString();
echo "Jam Server Sekarang (WITA): $now\n\n";

// 1. Delete all invalid future records (> now()) in all tables
$deletedGpsRaw = GpsTrackRaw::where('gps_time', '>', $now)->delete();
echo "1. Dihapus $deletedGpsRaw data GpsTrackRaw invalid (jam > $now)\n";

$deletedGps = GpsTrack::where('gps_time', '>', $now)->delete();
echo "2. Dihapus $deletedGps data GpsTrack invalid (jam > $now)\n";

$deletedAlarmRaw = AlarmRaw::where('start_time', '>', $now)->delete();
echo "3. Dihapus $deletedAlarmRaw data AlarmRaw invalid (jam > $now)\n";

$deletedIdle = IdleAlarm::where('starting_time', '>', $now)->delete();
echo "4. Dihapus $deletedIdle data IdleAlarm invalid (jam > $now)\n\n";

// 2. Clear caches
Artisan::call('cache:clear');
echo "5. Cache aplikasi dibersihkan.\n\n";

// 3. Re-process Idle Alarms
echo "6. Memproses Ulang Idle Alarms...\n";
Artisan::call('howen:process-idle-alarms');
echo Artisan::output() . "\n";

echo "=========================================\n";
echo "✅ HASIL SETELAH PERBAIKAN:\n";
echo "=========================================\n";

$latestSpeed = GpsTrack::orderBy('gps_time', 'desc')->first();
if ($latestSpeed) {
    echo "• Speed Track Terbaru di Web : {$latestSpeed->device_name} | Jam: {$latestSpeed->gps_time} | Speed: {$latestSpeed->speed} km/h\n";
} else {
    echo "• Speed Track Terbaru di Web : Belum ada\n";
}

$latestIdle = IdleAlarm::orderBy('starting_time', 'desc')->first();
if ($latestIdle) {
    echo "• Idle Alarm Terbaru di Web  : {$latestIdle->device_name} | Jam: {$latestIdle->starting_time} | Durasi: {$latestIdle->duration_minutes}m\n";
} else {
    echo "• Idle Alarm Terbaru di Web  : Belum ada\n";
}

echo "=========================================\n";
