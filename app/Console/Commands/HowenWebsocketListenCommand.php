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
use Illuminate\Support\Facades\Cache;

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

    // FIX: Lacak apakah login gagal agar bisa force refresh token pada reconnect
    private $loginFailed = false;

    // FIX: Counter reconnect untuk backoff bertahap
    private $reconnectAttempt = 0;

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
        try {
            if ($isReconnect && $this->authData && !$this->loginFailed) {
                // Reuse token yang sudah ada saat reconnect HANYA jika login sebelumnya sukses
                Log::info('[HowenWS] Reconnect: reusing cached auth data');
            } else {
                // FIX: Jika login gagal sebelumnya, paksa clear cache dan ambil token baru
                if ($this->loginFailed) {
                    Log::warning('[HowenWS] Previous login failed — forcing fresh token from server...');
                    Cache::forget('vss_auth_data');
                    Cache::forget('vss_auth_refresh_lock');
                    $this->loginFailed = false;
                }

                // Ambil data auth fresh
                $this->authData = $this->authService->getAuthData();
                Log::info('[HowenWS] Fresh auth data obtained', [
                    'has_pid' => !empty($this->authData['pid']),
                    'token_prefix' => substr($this->authData['token'] ?? '', 0, 10) . '...',
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

            // Reset reconnect counter saat berhasil connect
            $this->reconnectAttempt = 0;

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
            Log::info('[HowenWS] Login sent', [
                'username' => $this->username,
                'has_pid'  => !empty($this->authData['pid']),
            ]);
            $this->info("📤 Sent Login Request (80000)");

            // FIX: Terkadang server Howen nge-bug dan TIDAK membalas request 80000.
            // Kita langsung kirim Subscribe (80001) 1 detik setelah login untuk memancing data keluar.
            $loop->addTimer(1, function() use ($conn) {
                $this->info('📡 Sending Subscribe (80001) immediately...');
                $conn->send(json_encode([
                    'action'  => '80001',
                    'payload' => ['username' => $this->username]
                ]));
            });

            // 3. Start Heartbeat Timer (Action 80009) - every 60 seconds
            $heartbeatTimer = $loop->addPeriodicTimer(60, function() use ($conn) {
                $heartbeatPayload = [
                    'action' => '80009',
                    'payload' => [
                        'username' => $this->username,
                        'token'    => $this->authData['token']
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
            $conn->on('message', function ($msg) use ($conn, $connector, $loop, $heartbeatTimer) {
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

                        // FIX: Tandai loginFailed = true agar reconnect berikutnya paksa refresh token
                        $this->loginFailed = true;

                        // FIX: Batalkan heartbeat timer sebelum tutup koneksi
                        $loop->cancelTimer($heartbeatTimer);
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
        // FIX: Backoff bertahap - semakin sering gagal, semakin lama tunggu (max 60 detik)
        $this->reconnectAttempt++;
        $delay = min($this->reconnectDelay * $this->reconnectAttempt, 60);

        $this->info("🔄 Reconnecting in {$delay} seconds... (attempt #{$this->reconnectAttempt})");
        Log::info("[HowenWS] Scheduling reconnect in {$delay}s (attempt #{$this->reconnectAttempt})");

        $loop->addTimer($delay, function() use ($connector, $loop) {
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
            Log::error("[HowenWS] Error handling message action {$action}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function handleGpsPush($payload)
    {
        $deviceId = $payload['deviceID'] ?? null;
        if (!$deviceId) return;

        $loc = $payload['location'] ?? [];
        if (empty($loc)) return;

        $speed = (float)($loc['speed'] ?? 0);

        // FIX: Hapus filter speed <= 0. GPS push saat diam tetap perlu disimpan
        // agar history posisi kendaraan lengkap (tidak ada gap saat parkir/idle)
        // Sebelumnya: if ($speed <= 0) return;

        // FIX: Tangani timezone dtu dengan lebih robust
        // Server Howen bisa kirim dtu tanpa timezone info (bare datetime string)
        // Toleransi masa depan diperbesar ke 30 menit untuk menghindari false positive
        $dtuRaw = $loc['dtu'] ?? null;
        if ($dtuRaw) {
            try {
                // Coba parse — jika dtu tidak punya offset eksplisit, anggap sebagai WITA
                $gpsTime = \Carbon\Carbon::parse($dtuRaw);
                if (!str_contains($dtuRaw, '+') && !str_contains($dtuRaw, 'Z')) {
                    // Tidak ada timezone info → asumsikan WITA (UTC+8)
                    $gpsTime = \Carbon\Carbon::parse($dtuRaw, 'Asia/Makassar');
                } else {
                    // Ada timezone eksplisit → konversi ke WITA
                    $gpsTime = $gpsTime->setTimezone('Asia/Makassar');
                }

                // FIX: Toleransi 30 menit (bukan 2 menit) untuk clock drift server
                if ($gpsTime->greaterThan(now('Asia/Makassar')->addMinutes(30))) {
                    Log::warning("[HowenWS] Future timestamp ignored from device {$deviceId}: {$dtuRaw} → parsed: {$gpsTime}");
                    return;
                }

                // FIX: Abaikan data historis (backlog sebelum hari ini) agar langsung tarik realtime
                if ($gpsTime->lessThan(now('Asia/Makassar')->startOfDay())) {
                    // Cetak log sesekali (tiap 100 data) agar terminal tidak freeze/lag, tapi user bisa lihat progress
                    if (rand(1, 100) === 1) {
                        $this->line("⏩ Fast-forward backlog: skipping old data... ({$gpsTimeStr})");
                    }
                    return;
                }
                $gpsTimeStr = $gpsTime->toDateTimeString();
            } catch (\Exception $e) {
                Log::warning("[HowenWS] Failed to parse dtu '{$dtuRaw}' for device {$deviceId}, using now()");
                $gpsTimeStr = now('Asia/Makassar')->toDateTimeString();
            }
        } else {
            $gpsTimeStr = now('Asia/Makassar')->toDateTimeString();
        }

        $lat = (float)($loc['latitude'] ?? 0);
        $lon = (float)($loc['longitude'] ?? 0);

        if ($lat == 0 || $lon == 0) return; // Invalid GPS

        $device = Device::where('device_id', $deviceId)->select('id', 'device_name')->first();
        $deviceName = $device ? $device->device_name : null;

        $acc = (isset($payload['basic']['key']) && $payload['basic']['key'] == 1);

        try {
            // 1. Simpan ke gps_tracks_raw
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

            Log::info("[HowenWS] GPS: {$deviceName} ({$deviceId}) | Speed: {$speed} | ACC: " . ($acc ? 'ON' : 'OFF') . " | Time: {$gpsTimeStr}");
            $this->line("📍 GPS: {$deviceName} | Speed: {$speed} km/h | {$gpsTimeStr}");

        } catch (\Exception $e) {
            Log::error("[HowenWS] GPS save error for {$deviceId}: " . $e->getMessage(), [
                'payload' => $payload,
            ]);
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
        $serialNo   = $device ? $device->imei        : null;

        $lat = (float)($loc['latitude']  ?? 0);
        $lon = (float)($loc['longitude'] ?? 0);
        $gpsString = ($lat && $lon) ? "{$lon},{$lat}" : null;

        $alarmTypeName = $payload['alarmTypeName'] ?? ('AlarmCode-' . $ec);

        try {
            // 1. Simpan ke alarm_raw
            \App\Models\AlarmRaw::updateOrCreate(
                ['guid' => $alarmId],
                [
                    'device_id'        => $deviceId,
                    'device_name'      => $deviceName ?? '',
                    'alarm_type'       => (int)$ec,
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
                    'raw_json'         => json_encode($payload),
                ]
            );

            // 2. Jika ec = 32 (Idle), simpan juga ke idle_alarms
            if (($ec == '32' || $ec == 32) && $durationSeconds > 0) {
                \App\Models\IdleAlarm::updateOrCreate(
                    ['guid' => $alarmId],
                    [
                        'serial_no'         => $serialNo,
                        'device_id'         => $deviceId,
                        'device_name'       => $deviceName,
                        'alarm_type'        => 'Idle',
                        'alarm_status'      => 'ALARM_END',
                        'starting_time'     => $startTimeWita,
                        'ending_time'       => $endTimeWita,
                        'duration_seconds'  => $durationSeconds,
                        'duration_minutes'  => $durationMinutes,
                        'latitude_start'    => $lat ?: null,
                        'longitude_start'   => $lon ?: null,
                        'latitude_end'      => $lat ?: null,
                        'longitude_end'     => $lon ?: null,
                        'starting_location' => $gpsString,
                        'ending_location'   => $gpsString,
                        'start_speed'       => 0,
                        'end_speed'         => (float)($loc['speed'] ?? 0),
                        'report_time'       => $toWita($loc['dtu'] ?? null) ?? now()->toDateTimeString(),
                    ]
                );
                $this->info("🚨 Idle Alarm: {$deviceName} durasi {$durationMinutes} menit");
            }

            Log::info("[HowenWS] Alarm ec={$ec} ({$alarmTypeName}) device {$deviceId} disimpan.");

        } catch (\Exception $e) {
            Log::error("[HowenWS] Alarm save error for {$deviceId}: " . $e->getMessage(), [
                'payload' => $payload,
            ]);
        }
    }
}
