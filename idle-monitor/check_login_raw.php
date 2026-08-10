<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Models\ApiToken;

$baseUrl  = config('vss.base_url', 'http://vss.ptdigital.co.id');
$username = config('vss.username');
$password = config('vss.password', '');

echo "=== FRESH LOGIN RAW (BYPASS CACHE) ===\n\n";
echo "Server  : $baseUrl\n";
echo "Username: $username\n\n";

// Hash password jika belum MD5
$hashedPassword = preg_match('/^[a-f0-9]{32}$/i', $password)
    ? $password
    : md5($password);

echo "🔐 Logging in...\n";

$response = Http::withOptions(['verify' => false])
    ->timeout(15)
    ->post("{$baseUrl}/vss/user/apiLogin.action", [
        'username' => $username,
        'password' => $hashedPassword,
    ]);

$body = $response->json();

echo "\n📡 RAW RESPONSE FROM HOWEN LOGIN API:\n";
echo json_encode($body, JSON_PRETTY_PRINT) . "\n\n";

if (($body['status'] ?? 0) !== 10000) {
    echo "❌ Login GAGAL: " . ($body['msg'] ?? 'unknown') . "\n";
    exit(1);
}

$token = $body['data']['token'] ?? null;
$pid   = $body['data']['pid']   ?? null;

echo "✅ Login sukses!\n";
echo "Token : " . substr($token, 0, 25) . "...\n";
echo "PID   : " . ($pid ?: '❌ TIDAK ADA - Server tidak kirim pid!') . "\n\n";

// Clear cache lama
Cache::forget('vss_auth_data');

// Simpan ke DB
ApiToken::updateOrCreate(
    ['token' => $token],
    [
        'pid'        => $pid,
        'username'   => $username,
        'expires_at' => now()->addMinutes(25),
    ]
);

echo "✅ Token & PID disimpan ke database!\n\n";

if (empty($pid)) {
    echo "⚠️  Server Howen tidak mengembalikan field 'pid'.\n";
    echo "   WebSocket login mungkin menggunakan format berbeda.\n";
    echo "   Cek kunci 'data' di response di atas untuk field yang tepat.\n";
} else {
    echo "🎯 Sekarang restart WebSocket:\n";
    echo "   docker restart idle-monitor-websocket\n";
    echo "   docker logs idle-monitor-websocket --tail 20\n";
}
