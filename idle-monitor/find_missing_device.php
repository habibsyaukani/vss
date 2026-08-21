<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Ambil semua IMEI Tracksolid yang ada di DB kita
$knownImeis = \App\Models\Device::whereColumn('device_id', 'imei')
    ->pluck('device_id')
    ->toArray();

echo "Known Tracksolid IMEIs di DB: " . count($knownImeis) . "\n";

// Cari di tabel GPS tracks - ada IMEI yang kirim data tapi belum ada di devices?
$tables = \Illuminate\Support\Facades\DB::select("SHOW TABLES");
echo "\nTabel yang ada di database:\n";
foreach ($tables as $t) {
    $arr = (array)$t;
    echo "  - " . array_values($arr)[0] . "\n";
}

echo "\n--- Cek di tabel gps_tracks_raw ---\n";
try {
    // Cari device_id dari GPS tracks yang bukan milik Howen (IMEI panjang)
    $imeiFromTracks = \Illuminate\Support\Facades\DB::table('gps_tracks_raw')
        ->selectRaw('device_id, COUNT(*) as cnt')
        ->where('device_id', 'REGEXP', '^[0-9]{14,15}$') // IMEI format panjang
        ->groupBy('device_id')
        ->orderByDesc('cnt')
        ->get();

    echo "Device IMEI yang kirim data GPS track: " . $imeiFromTracks->count() . "\n";
    
    $missing = [];
    foreach ($imeiFromTracks as $t) {
        if (!in_array($t->device_id, $knownImeis)) {
            $missing[] = ['imei' => $t->device_id, 'track_count' => $t->cnt];
        }
    }
    
    if (count($missing) > 0) {
        echo "\n=== IMEI ADA DI GPS TRACK TAPI BELUM DI DEVICES TABLE ===\n";
        foreach ($missing as $m) {
            echo "  IMEI: {$m['imei']} | Total track: {$m['track_count']}\n";
        }
    } else {
        echo "\nTidak ada IMEI baru yang ditemukan di GPS tracks.\n";
        echo "Semua " . $imeiFromTracks->count() . " IMEI dari GPS track sudah ada di devices table.\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Cek juga di idle_alarms jika ada
echo "\n--- Cek di tabel idle_alarms ---\n";
try {
    $imeiFromAlarms = \Illuminate\Support\Facades\DB::table('idle_alarms')
        ->selectRaw('device_id, COUNT(*) as cnt')
        ->where('device_id', 'REGEXP', '^[0-9]{14,15}$')
        ->groupBy('device_id')
        ->orderByDesc('cnt')
        ->get();

    echo "Device IMEI yang ada di idle_alarms: " . $imeiFromAlarms->count() . "\n";
    
    $missing2 = [];
    foreach ($imeiFromAlarms as $a) {
        if (!in_array($a->device_id, $knownImeis)) {
            $missing2[] = ['imei' => $a->device_id, 'alarm_count' => $a->cnt];
        }
    }
    
    if (count($missing2) > 0) {
        echo "\n=== IMEI ADA DI IDLE ALARMS TAPI BELUM DI DEVICES TABLE ===\n";
        foreach ($missing2 as $m) {
            echo "  IMEI: {$m['imei']} | Total alarm: {$m['alarm_count']}\n";
        }
    } else {
        echo "Semua IMEI dari idle_alarms sudah ada di devices table.\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
