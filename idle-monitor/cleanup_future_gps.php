<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\GpsTrack;
use App\Models\GpsTrackRaw;
use Carbon\Carbon;

echo "=========================================\n";
echo "🔧 BERSIHKAN DATA GPS JAM MASA DEPAN\n";
echo "=========================================\n\n";

$now = now();
echo "Jam Server Sekarang (WITA): " . $now->format('Y-m-d H:i:s') . "\n\n";

// 1. Cek berapa banyak data masa depan di gps_tracks
$futureTracksCount = GpsTrack::where('gps_time', '>', $now)->count();
$futureRawCount    = GpsTrackRaw::where('gps_time', '>', $now)->count();

echo "Data GPS Track masa depan: $futureTracksCount records\n";
echo "Data GPS Raw masa depan  : $futureRawCount records\n\n";

// 2. Tampilkan 5 teratas dulu untuk konfirmasi
$futureSamples = GpsTrack::where('gps_time', '>', $now)->orderBy('gps_time', 'desc')->take(5)->get();
echo "Sample Data Masa Depan:\n";
foreach ($futureSamples as $s) {
    echo " - [{$s->id}] {$s->device_name} | GPS Time: {$s->gps_time} | Saved At: {$s->created_at}\n";
}

echo "\n";

// 3. Hapus data masa depan dari gps_tracks (display table)
if ($futureTracksCount > 0) {
    // Get raw_ids first before deleting
    $rawIds = GpsTrack::where('gps_time', '>', $now)->pluck('raw_id')->toArray();
    
    $deleted = GpsTrack::where('gps_time', '>', $now)->delete();
    echo "✅ Deleted $deleted records dari gps_tracks (display)\n";
    
    // Also delete corresponding raw records so they can be re-pulled fresh
    if (!empty($rawIds)) {
        $deletedRaw = GpsTrackRaw::whereIn('id', $rawIds)->delete();
        echo "✅ Deleted $deletedRaw records dari gps_tracks_raw (raw)\n";
    }
} else {
    echo "ℹ️ Tidak ada data masa depan di gps_tracks.\n";
}

echo "\n=========================================\n";
echo "✅ SELESAI! Jalankan vss:pull-gps-tracks untuk menarik ulang data terbaru.\n";
echo "=========================================\n";
