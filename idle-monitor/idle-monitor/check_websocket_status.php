<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\GpsTrack;
use App\Models\AlarmRaw;
use App\Models\IdleAlarm;

echo "=========================================\n";
echo "🔌 STATUS WEBSOCKET HOWEN REAL-TIME\n";
echo "=========================================\n\n";

$now = now();
echo "Jam Server Sekarang (WITA): " . $now->format('Y-m-d H:i:s') . "\n\n";

// Check log WebSocket 
$logPath = storage_path('logs/laravel.log');
$wsConnected = false;
$wsLastMsg = 'Log tidak ditemukan';
$wsGpsCount = 0;
$wsAlarmCount = 0;

if (file_exists($logPath)) {
    // Read only last 100KB of log file to avoid memory exhaustion
    $fp = fopen($logPath, 'r');
    fseek($fp, max(0, filesize($logPath) - 100000));
    $chunk = fread($fp, 100000);
    fclose($fp);
    $recentLines = explode("\n", $chunk);

    foreach (array_reverse($recentLines) as $line) {
        if (str_contains($line, '[HowenWS] Connected')) {
            $wsConnected = true;
            preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $m);
            $wsLastMsg = 'WebSocket TERHUBUNG di ' . ($m[1] ?? 'unknown');
            break;
        }
    }
    foreach ($recentLines as $line) {
        if (str_contains($line, '[HowenWS] GPS:'))       $wsGpsCount++;
        if (str_contains($line, '[HowenWS] Alarm ec=')) $wsAlarmCount++;
    }
}

echo "-----------------------------------------\n";
echo "📡 STATUS KONEKSI WEBSOCKET:\n";
echo "-----------------------------------------\n";
echo $wsConnected ? "✅ WebSocket TERHUBUNG\n" : "❌ WebSocket TIDAK terhubung atau log tidak lengkap\n";
echo "   Info: $wsLastMsg\n";
echo "   GPS diterima via WS (log terakhir): $wsGpsCount records\n";
echo "   Alarm diterima via WS (log terakhir): $wsAlarmCount records\n\n";

// Data 10 menit terakhir
$tenMinAgo = $now->copy()->subMinutes(10);
$gpsLast10m = GpsTrack::where('created_at', '>=', $tenMinAgo)->count();
$idleLast10m = IdleAlarm::where('created_at', '>=', $tenMinAgo)->count();
$alarmRawLast10m = AlarmRaw::where('created_at', '>=', $tenMinAgo)->count();

echo "-----------------------------------------\n";
echo "⏱️ DATA MASUK 10 MENIT TERAKHIR:\n";
echo "-----------------------------------------\n";
echo "• GPS Tracks baru : $gpsLast10m records\n";
echo "• Idle Alarms baru: $idleLast10m records\n";
echo "• Alarm Raw baru  : $alarmRawLast10m records\n\n";

// Check apakah GPS time masih ada yang dari masa depan
$futureGps = GpsTrack::where('gps_time', '>', $now)->count();
echo "-----------------------------------------\n";
echo "🕒 CEK TIMESTAMP MASA DEPAN:\n";
echo "-----------------------------------------\n";
echo $futureGps > 0 
    ? "⚠️ Masih ada $futureGps GPS records dengan jam masa depan!\n"
    : "✅ Tidak ada GPS records dengan jam masa depan.\n";

echo "\n=========================================\n";
