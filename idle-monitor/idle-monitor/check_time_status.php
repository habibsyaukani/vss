<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=========================================\n";
echo "⏰ DIAGNOSA WAKTU SISTEM & TIMEZONE\n";
echo "=========================================\n\n";

echo "1. PHP Container Timezone  : " . date_default_timezone_get() . "\n";
echo "2. PHP Container Current Time : " . now()->format('Y-m-d H:i:s P') . "\n";

$dbTime = DB::select("SELECT NOW() as now_time, @@global.time_zone as global_tz, @@session.time_zone as session_tz")[0];
echo "3. MySQL Database Time     : " . $dbTime->now_time . "\n";
echo "   - Global TZ             : " . $dbTime->global_tz . "\n";
echo "   - Session TZ            : " . $dbTime->session_tz . "\n\n";

echo "-----------------------------------------\n";
echo "📡 DATA RAW TERBARU DI DATABASE:\n";
echo "-----------------------------------------\n";

$latestRawAlarm = \App\Models\AlarmRaw::latest('id')->first();
if ($latestRawAlarm) {
    echo "• AlarmRaw Terbaru (ID: {$latestRawAlarm->id})\n";
    echo "  - Start Time (API) : {$latestRawAlarm->start_time}\n";
    echo "  - Created At (DB)   : {$latestRawAlarm->created_at}\n";
    
    $diffMinutes = Carbon::parse($latestRawAlarm->start_time)->diffInMinutes(now(), false);
    echo "  - Selisih vs Jam Server Sekarang : {$diffMinutes} menit\n";
} else {
    echo "• AlarmRaw: Belum ada data\n";
}

echo "\n";

$latestGpsRaw = \App\Models\GpsTrackRaw::latest('id')->first();
if ($latestGpsRaw) {
    echo "• GpsTrackRaw Terbaru (ID: {$latestGpsRaw->id})\n";
    echo "  - Device Name       : {$latestGpsRaw->device_name}\n";
    echo "  - GPS Time (API)    : {$latestGpsRaw->gps_time}\n";
    echo "  - Created At (DB)   : {$latestGpsRaw->created_at}\n";
    
    $diffMinutesGps = Carbon::parse($latestGpsRaw->gps_time)->diffInMinutes(now(), false);
    echo "  - Selisih vs Jam Server Sekarang : {$diffMinutesGps} menit\n";
} else {
    echo "• GpsTrackRaw: Belum ada data\n";
}

echo "\n=========================================\n";
