<?php
/**
 * DIAGNOSA: Bandingkan raw field speed dari API Howen
 * Tujuan: Cari tahu kenapa speed kita berbeda dari dashboard Howen
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\VssAuthService;
use Illuminate\Support\Facades\Http;

echo "=============================================\n";
echo "🔍 DIAGNOSA SPEED DISCREPANCY - RAW API DUMP\n";
echo "=============================================\n\n";

$authService = new VssAuthService();
$token = $authService->getToken();
echo "✅ Token: " . substr($token, 0, 15) . "...\n\n";

// Device yang digunakan di screenshot: GPE-DT-1169(73206996)
$deviceId = '73206996';
$deviceName = 'GPE-DT-1169';

// Rentang waktu sesuai screenshot: sekitar 2026-09-22 15:46:xx
$beginTime = '2026-09-22 15:40:00';
$endTime   = '2026-09-22 15:50:00';

echo "📡 Device: {$deviceName} ({$deviceId})\n";
echo "📅 Range : {$beginTime} -> {$endTime}\n\n";

$baseUrl = config('vss.base_url', 'https://vss.ptdigital.co.id');

$response = Http::withOptions(['verify' => false])->timeout(30)->post("{$baseUrl}/vss/track/getApiTrackList.action", [
    'token'     => $token,
    'deviceID'  => $deviceId,
    'beginTime' => $beginTime,
    'endTime'   => $endTime,
    'pageNum'   => 1,
    'pageCount' => 50,
]);

$data = $response->json();
$records = $data['data']['dataList'] ?? [];

echo "📊 Total records: " . count($records) . "\n";
echo "📊 API status: " . ($data['status'] ?? 'N/A') . "\n\n";

if (!empty($records)) {
    // Dump field pertama secara lengkap untuk lihat semua key yang tersedia
    echo "=== FULL KEYS dari record pertama ===\n";
    $firstRec = $records[0];
    foreach ($firstRec as $key => $value) {
        $displayVal = is_array($value) ? json_encode($value) : (string) $value;
        if (strlen($displayVal) > 120) $displayVal = substr($displayVal, 0, 120) . '...';
        echo "  {$key} => {$displayVal}\n";
    }

    echo "\n=== PERBANDINGAN FIELD SPEED per record ===\n";
    echo str_pad("No", 4) . str_pad("createtime", 22) . str_pad("speed", 10) . str_pad("altitude", 10);
    
    // Cek apakah ada field lain terkait speed
    $speedFields = [];
    foreach (array_keys($firstRec) as $key) {
        $keyLower = strtolower($key);
        if (strpos($keyLower, 'speed') !== false || strpos($keyLower, 'spd') !== false || strpos($keyLower, 'velocity') !== false) {
            if ($key !== 'speed' && $key !== 'overSpeed' && $key !== 'lowSpeed') {
                $speedFields[] = $key;
            }
        }
    }
    foreach ($speedFields as $f) {
        echo str_pad($f, 15);
    }
    echo "\n" . str_repeat("-", 80) . "\n";

    foreach (array_slice($records, 0, 20) as $i => $rec) {
        $line = str_pad($i + 1, 4);
        $line .= str_pad($rec['createtime'] ?? 'N/A', 22);
        $line .= str_pad($rec['speed'] ?? 'N/A', 10);
        $line .= str_pad($rec['altitude'] ?? 'N/A', 10);
        foreach ($speedFields as $f) {
            $line .= str_pad($rec[$f] ?? 'N/A', 15);
        }
        echo $line . "\n";
    }

    // Juga cek stateJson karena mungkin ada speed di dalamnya
    echo "\n=== CEK stateJson dari record pertama ===\n";
    $stateJson = $firstRec['stateJson'] ?? null;
    if ($stateJson) {
        if (is_string($stateJson)) {
            $stateData = json_decode($stateJson, true);
        } else {
            $stateData = $stateJson;
        }
        echo json_encode($stateData, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "(tidak ada stateJson)\n";
    }

    // Dump 1 raw record penuh untuk analisa mendalam
    echo "\n=== RAW DUMP record pertama (JSON) ===\n";
    echo json_encode($firstRec, JSON_PRETTY_PRINT) . "\n";

} else {
    echo "⚠️ Tidak ada data GPS ditemukan.\n";
    echo "Full API response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
}

echo "\n=============================================\n";
echo "SELESAI\n";
echo "=============================================\n";
