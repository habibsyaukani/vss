<?php
require __DIR__ . '/vendor/autoload.php';

use Ratchet\Client\Connector;
use React\EventLoop\Loop;

$loop = Loop::get();
$reactConnector = new \React\Socket\Connector(['timeout' => 10, 'tls' => ['verify_peer' => false, 'verify_peer_name' => false]]);
$connector = new Connector($loop, $reactConnector);

$host = 'vss.ptdigital.co.id';
$port = 36300;
$username = 'dash_gpe_gam';

// Get token from cache directly
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$token = Cache::get('vss_auth_data')['token'] ?? null;
if (!$token) {
    echo "No token in cache\n";
    exit;
}

echo "Connecting to ws://{$host}:{$port} with token: " . substr($token, 0, 10) . "...\n";

$connector("ws://{$host}:{$port}")->then(function($conn) use ($username, $token, $loop) {
    echo "Connected!\n";
    $conn->on('message', function($msg) use ($conn, $loop) {
        $raw = $msg->getPayload();
        echo "RECV: " . substr($raw, 0, 200) . "...\n";
        $data = json_decode($raw, true);
        if (($data['action'] ?? '') === '80000') {
            echo "Login response received. Sending subscribe.\n";
            $conn->send(json_encode(['action' => '80001', 'payload' => ['username' => $username]]));
        }
    });

    $login = json_encode(['action' => '80000', 'payload' => ['username' => $username, 'token' => $token]]);
    echo "SEND: {$login}\n";
    $conn->send($login);

    $loop->addPeriodicTimer(10, function() use ($conn, $username, $token) {
        echo "Sending heartbeat...\n";
        $conn->send(json_encode(['action' => '80009', 'payload' => ['username' => $username, 'token' => $token]]));
    });

}, function($e) {
    echo "Could not connect: {$e->getMessage()}\n";
});

$loop->run();
