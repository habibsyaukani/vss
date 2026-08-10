<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use React\EventLoop\Loop;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use App\Services\VssAuthService;
use App\Models\GpsTrack;
use App\Models\IdleAlarm;
use App\Models\Device;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class HowenWebsocketListenCommand extends Command
{
    protected $signature = 'howen:websocket-listen';
    protected $description = 'Listen to Howen WebSocket for real-time GPS and Alarms (VSS Port 36300)';

    private $authService;
    private $authData;
    private $reconnectDelay = 5;
    private $username;
    
    // Track last heartbeat time for logging to avoid spam
    private $lastHeartbeatLog = 0;

    public function handle()
    {
        $this->authService = new VssAuthService();
        $this->username = config('vss.username');
        
        $this->info('Starting Howen WebSocket Listener Daemon...');
        Log::info('[HowenWS] Starting WebSocket Listener');

        $loop = Loop::get();
        $reactConnector = new \React\Socket\Connector([
            'timeout' => 15,
            'tls' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        $connector = new Connector($loop, $reactConnector);

        $this->connect($connector, $loop);
        
        // Block and listen forever
        $loop->run();
    }
    
    private function connect($connector, $loop, bool $isReconnect = false)
    {
        // 1. Get token and pid
        // HANYA refresh token saat pertama kali connect, BUKAN saat reconnect
        // agar tidak spam login ke server (bisa rate-limited)
        try {
            if ($isReconnect && $this->authData) {
                // Reuse token yang sudah ada saat reconnect
                Log::info('[HowenWS] Reconnect: reusing cached auth data');
            } else {
                // First connect: ambil data auth fresh
                $this->authData = $this->authService->getAuthData();
                Log::info('[HowenWS] Fresh auth data obtained', [
                    'has_pid' => !empty($this->authData['pid']),
                ]);
            }
        } catch (\Exception $e) {
            $this->error('Failed to get Auth Data: ' . $e->getMessage());
            Log::error("[HowenWS] Failed to get Auth Data: " . $e->getMessage());
            $this->reconnect($connector, $loop);
            return;
        }

        $host = parse_url(config('vss.base_url', 'http://vss.ptdigital.co.id'), PHP_URL_HOST);
        $wsUrl = "ws://{$host}:36300";
        
        $this->info("Connecting to {$wsUrl} as {$this->username}...");

        $connector($wsUrl)->then(function (WebSocket $conn) use ($connector, $loop) {
            $this->info("✅ Connected successfully to WebSocket server!");
            Log::info("[HowenWS] Connected to WebSocket server");
            
            // 2. Send Login (Action 80000)
        // Hanya kirim pid jika ada nilainya (pid opsional di beberapa versi Howen)
        $loginPayloadData = [
            'username' => $this->username,
            'token'    => $this->authData['token'],
        ];
        if (!empty($this->authData['pid'])) {
            $loginPayloadData['pid'] = $this->authData['pid'];
        }

        $loginPayload = [
            'action'  => '80000',
            'payload' => $loginPayloadData,
        ];
        
        $conn->send(json_encode($loginPayload));
        Log::info('[HowenWS] Login sent', ['username' => $this->username, 'has_pid' => !empty($this->authData['pid'])]);
        $this->info("📤 Sent Login Request (80000)");

            // 3. Start Heartbeat Timer (Action 80009) - every 60 seconds
            $heartbeatTimer = $loop->addPeriodicTimer(60, function() use ($conn) {
                $heartbeatPayload = [
                    'action' => '80009',
                    'payload' => [
                        'username' => $this->username,
                        'token' => $this->authData['token']
                    ]
                ];
                $conn->send(json_encode($heartbeatPayload));
                
                // Only log heartbeat every 5 minutes to avoid log spam
                $now = time();
                if ($now - $this->lastHeartbeatLog > 300) {
                    $this->line("💓 Sent Heartbeat (80009)");
                    $this->lastHeartbeatLog = $now;
                }
            });

            // 4. Handle Incoming Messages
            $conn->on('message', function ($msg) use ($conn) {
                $rawPayload = $msg->getPayload();
                $data = json_decode($rawPayload, true);
                if (!$data) return;

                $action = $data['action'] ?? null;

                // Log ALL incoming messages for debugging
                Log::debug("[HowenWS] RAW IN action={$action}: " . substr($rawPayload, 0, 300));
                
                // Handle Login Response
                if ($action === '80000') {
                    Log::info('[HowenWS] Login response: ' . $rawPayload);
                    // Howen dapat return msg='success' ATAU payload.result='success' tergantung versi
                    $isSuccess = ($data['msg'] ?? '') === 'success'
                        || ($data['payload']['result'] ?? '') === 'success'
                        || ($data['payload']['msg']    ?? '') === 'success'
                        || ($data['status'] ?? 0) === 10000;

                    if ($isSuccess) {
                        $this->info('✅ Login successful. Sending Subscribe (80001)...');
                        Log::info('[HowenWS] Login successful, sending Subscribe');
                        $conn->send(json_encode([
                            'action'  => '80001',
                            'payload' => ['username' => $this->username]
                        ]));
                        Log::info('[HowenWS] Subscribe sent');
                        $this->info('📡 Subscribed to realtime events!');
                    } else {
                        $this->error('❌ Login failed: ' . $rawPayload);
                        Log::error('[HowenWS] Login FAILED: ' . $rawPayload);
                        $conn->close();
                    }
                    return;
                }

                // Handle Subscribe Response
                if ($action === '80001') {
                    Log::info('[HowenWS] Subscribe response: ' . substr($rawPayload, 0, 300));
                    $this->line('✅ Subscribe response received.');
                    return;
                }

                // Route to main data handler
                $this->handleMessage($action, $data);
            });

            // 5. Handle Close / Disconnect
            $conn->on('close', function ($code = null, $reason = null) use ($connector, $loop, $heartbeatTimer) {
                $this->warn("⚠️ Connection closed (Code: {$code}, Reason: {$reason})");
                Log::warning("[HowenWS] Connection closed ({$code})");
                $loop->cancelTimer($heartbeatTimer);
                $this->reconnect($connector, $loop);
            });
            
        }, function (\Exception $e) use ($connector, $loop) {
            $this->error("❌ Could not connect: {$e->getMessage()}");
            Log::error("[HowenWS] Connect error: {$e->getMessage()}");
            $this->reconnect($connector, $loop);
        });
    }

    private function reconnect($connector, $loop)
    {
        $this->info("🔄 Reconnecting in {$this->reconnectDelay} seconds...");
        $loop->addTimer($this->reconnectDelay, function() use ($connector, $loop) {
            $this->connect($connector, $loop, isReconnect: true);
        });
    }

    private function handleMessage($action, $data)
    {
        $payload = $data['payload'] ?? [];
        if (empty($payload)) return;

        try {
            switch ($action) {
                case '80003': // GPS Position Push
                    $this->handleGpsPush($payload);
                    break;
                case '80004': // Alarm Push
                    $this->handleAlarmPush($payload);
                    break;
                case '80005': // Online/Offline Status (Opsional)
                    // You can update device online status here if needed
                    break;
                case '80009': // Heartbeat Response
                    // Ignore response payload, just keeping connection alive
                    break;
            }
        } catch (\Exception $e) {
            Log::error("[HowenWS] Error handling message action {$action}: " . $e->getMessage());
        }
    }

    private function handleGpsPush($payload)
    {
        $deviceId = $payload['deviceID'] ?? null;
        if (!$deviceId) return;

        $loc = $payload['location'] ?? [];
        if (empty($loc)) return;

        $speed = (float)($loc['speed'] ?? 0);
        if ($speed <= 0) return; // Skip jika speed = 0 (tidak bergerak)

        // dtu dari WebSocket push sudah dalam WITA (UTC+8) — BERBEDA dengan HTTP API yang pakai WIB
        // Jangan dikonversi! Parse langsung sebagai WITA.
        $dtuRaw = $loc['dtu'] ?? null;
        if ($dtuRaw) {
            try {
                $gpsTime = \Carbon\Carbon::parse($dtuRaw, 'Asia/Makassar'); // Already WITA
                // Tolak timestamp masa depan (toleransi 2 menit untuk clock drift kecil)
                if ($gpsTime->greaterThan(now()->addMinutes(2))) {
                    Log::warning("[HowenWS] Future timestamp ignored from device {$deviceId}: {$dtuRaw}");
                    return;
                }
                $gpsTimeStr = $gpsTime->toDateTimeString();
            } catch (\Exception $e) {
                $gpsTimeStr = now()->toDateTimeString();
            }
        } else {
            $gpsTimeStr = now()->toDateTimeString();
        }

        $lat = (float)($loc['latitude'] ?? 0);
        $lon = (float)($loc['longitude'] ?? 0);

        if ($lat == 0 || $lon == 0) return; // Invalid GPS

        $device = Device::where('device_id', $deviceId)->select('id', 'device_name')->first();
        $deviceName = $device ? $device->device_name : null;

        $acc = (isset($payload['basic']['key']) && $payload['basic']['key'] == 1);

        try {
            // 1. Simpan ke gps_tracks_raw agar struktur sama dengan metode HTTP Polling
            // Web VAMS kemungkinan melakukan JOIN atau membutuhkan raw_id
            $guid = 'ws_' . $deviceId . '_' . time() . '_' . rand(100, 999);
            $raw = \App\Models\GpsTrackRaw::create([
                'device_id'   => $deviceId,
                'device_name' => $deviceName,
                'guid'        => $guid,
                'latitude'    => $lat,
                'longitude'   => $lon,
                'speed'       => $speed,
                'direction'   => (int)($loc['direct'] ?? 0),
                'satellites'  => (int)($loc['satellites'] ?? 0),
                'altitude'    => (int)($loc['altitude'] ?? 0),
                'acc_state'   => $acc ? 1 : 0,
                'gps_time'    => $gpsTimeStr,
                'report_time' => $gpsTimeStr,
                'is_later'    => 0,
            ]);

            // 2. Simpan ke gps_tracks dengan raw_id
            \App\Models\GpsTrack::updateOrCreate(
                [
                    'device_id' => $deviceId,
                    'gps_time'  => $gpsTimeStr,
                ],
                [
                    'raw_id'      => $raw->id,
                    'device_name' => $deviceName,
                    'latitude'    => $lat,
                    'longitude'   => $lon,
                    'speed'       => $speed,
                    'direction'   => (int)($loc['direct'] ?? 0),
                    'satellites'  => (int)($loc['satellites'] ?? 0),
                    'altitude'    => (int)($loc['altitude'] ?? 0),
                    'is_acc_on'   => $acc,
                    'report_time' => $gpsTimeStr,
                ]
            );

            // Kita hapus update 'devices' location karena HTTP polling juga tidak melakukannya
            // Biarkan backend VAMS membaca dari gps_tracks

            Log::info("[HowenWS] GPS: {$deviceName} ({$deviceId}) | Speed: {$speed} | Time: {$gpsTimeStr}");
            $this->line("📍 GPS: {$deviceName} | Speed: {$speed} km/h | {$gpsTimeStr}");

        } catch (\Exception $e) {
            Log::error("[HowenWS] GPS save error for {$deviceId}: " . $e->getMessage());
        }
    }

    private function handleAlarmPush($payload)
    {
        $deviceId = $payload['deviceID'] ?? null;
        $alarmId  = $payload['alarmID']  ?? null;
        $ec       = $payload['ec']       ?? null; // Event Code / Alarm Type

        if (!$deviceId || !$alarmId) return;

        $st  = $payload['st']  ?? null; // Start Time (WIB)
        $et  = $payload['et']  ?? null; // End Time (WIB)
        $loc = $payload['location'] ?? [];

        // Konversi WIB → WITA
        $toWita = function (?string $t): ?string {
            if (!$t) return null;
            try {
                return \Carbon\Carbon::parse($t, 'Asia/Jakarta')->setTimezone('Asia/Makassar')->toDateTimeString();
            } catch (\Exception $e) {
                return $t;
            }
        };

        $startTimeWita = $toWita($st);
        $endTimeWita   = $toWita($et);

        $durationSeconds = 0;
        $durationMinutes = 0;
        if ($startTimeWita && $endTimeWita) {
            try {
                $durationSeconds = \Carbon\Carbon::parse($endTimeWita)->diffInSeconds(\Carbon\Carbon::parse($startTimeWita));
                $durationMinutes = (int) ceil($durationSeconds / 60);
            } catch (\Exception $e) {
                // ignore
            }
        }

        $device     = Device::where('device_id', $deviceId)->select('id', 'device_name', 'imei')->first();
        $deviceName = $device ? $device->device_name : null;
        $serialNo   = $device ? $device->imei        : null; // Fallback ke imei karena serial_no tidak ada di tabel devices

        $lat = (float)($loc['latitude']  ?? 0);
        $lon = (float)($loc['longitude'] ?? 0);
        $gpsString = ($lat && $lon) ? "{$lon},{$lat}" : null;

        $alarmTypeName = $payload['alarmTypeName'] ?? ('AlarmCode-' . $ec);

        try {
            // 1. Simpan ke alarm_raw (sesuai schema yang ada)
            // alarm_type = integer (ec code), alarm_state = tinyint (1=alarming, 0=end)
            \App\Models\AlarmRaw::updateOrCreate(
                ['guid' => $alarmId],
                [
                    'device_id'        => $deviceId,
                    'device_name'      => $deviceName ?? '',
                    'alarm_type'       => (int)$ec,    // integer di DB
                    'alarm_value'      => $alarmTypeName,
                    'alarm_state'      => 0,           // 0 = ALARM_END (selesai)
                    'start_time'       => $startTimeWita ?? now()->toDateTimeString(),
                    'end_time'         => $endTimeWita,
                    'start_gps'        => $gpsString,
                    'end_gps'          => $gpsString,
                    'start_speed'      => 0,
                    'end_speed'        => (float)($loc['speed'] ?? 0),
                    'report_time'      => $toWita($loc['dtu'] ?? null) ?? now()->toDateTimeString(),
                    'duration_seconds' => $durationSeconds,
                    'raw_json'         => json_encode($payload), // simpan payload asli untuk debug
                ]
            );

            // 2. Jika ec = 32 (Idle), simpan juga ke idle_alarms
            if (($ec == '32' || $ec == 32) && $durationSeconds > 0) {
                \App\Models\IdleAlarm::updateOrCreate(
                    ['guid' => $alarmId],
                    [
                        'serial_no'        => $serialNo,
                        'device_id'        => $deviceId,
                        'device_name'      => $deviceName,
                        'alarm_type'       => 'Idle',
                        'alarm_status'     => 'ALARM_END',
                        'starting_time'    => $startTimeWita,
                        'ending_time'      => $endTimeWita,
                        'duration_seconds' => $durationSeconds,
                        'duration_minutes' => $durationMinutes,
                        'latitude_start'   => $lat ?: null,
                        'longitude_start'  => $lon ?: null,
                        'latitude_end'     => $lat ?: null,
                        'longitude_end'    => $lon ?: null,
                        'starting_location'=> $gpsString,
                        'ending_location'  => $gpsString,
                        'start_speed'      => 0,
                        'end_speed'        => (float)($loc['speed'] ?? 0),
                        'report_time'      => $toWita($loc['dtu'] ?? null) ?? now()->toDateTimeString(),
                    ]
                );
                $this->info("🚨 Idle Alarm: {$deviceName} durasi {$durationMinutes} menit");
            }

            Log::info("[HowenWS] Alarm ec={$ec} ({$alarmTypeName}) device {$deviceId} disimpan.");

        } catch (\Exception $e) {
            Log::error("[HowenWS] Alarm save error for {$deviceId}: " . $e->getMessage());
        }
    }
}
