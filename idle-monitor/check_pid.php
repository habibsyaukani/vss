<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ApiToken;

echo "=== CEK API TOKEN & PID DI DATABASE ===\n\n";

$token = ApiToken::orderBy('created_at', 'desc')->first();

if (!$token) {
    echo "❌ Tidak ada token di database!\n";
    echo "   Jalankan: docker exec -it idle-monitor-app php artisan howen:test-auth\n";
    exit(1);
}

echo "Token   : " . substr($token->token, 0, 20) . "...\n";
echo "PID     : " . ($token->pid    ?: '❌ KOSONG - WebSocket akan gagal login!') . "\n";
echo "Username: " . ($token->username ?: '(tidak tersimpan)') . "\n";
echo "Expires : " . $token->expires_at . "\n";
echo "Valid   : " . ($token->expires_at && $token->expires_at > now() ? '✅ Masih valid' : '⚠️ Sudah expired') . "\n\n";

if (empty($token->pid)) {
    echo "⚠️ PID MASIH KOSONG!\n";
    echo "   Kemungkinan penyebab:\n";
    echo "   1. Token ini dibuat sebelum fix - perlu login ulang\n";
    echo "   2. Howen API tidak mengembalikan field 'pid'\n\n";
    echo "Coba hapus cache dan login ulang:\n";
    echo "  docker exec -it idle-monitor-app php artisan cache:clear\n";
    echo "  docker exec -it idle-monitor-app php check_login_raw.php\n";
} else {
    echo "✅ PID tersimpan dengan benar. WebSocket seharusnya bisa login!\n";
    echo "\nCek log WebSocket:\n";
    echo "  docker logs idle-monitor-websocket --tail 20\n";
}
