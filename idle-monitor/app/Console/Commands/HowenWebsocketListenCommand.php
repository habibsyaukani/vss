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
    
    private function connect($connector, $loop)
    {
        // 1. Get fresh token and pid
        try {
            // Kita panggil refreshToken agar token 100% fresh saat reconnect
            // Karena jika token expired di tengah jalan, websocket akan putus
            $this->authService->refreshToken(); 
            $this->authData = $this->authService->getAuthData();
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
            $loginPayload = [
                'action' => '80000',
                'payload' => [
                    'username' => $this->username,
                    'pid' => $this->authData['pid'],
                    'token' => $this->authData['token']
                ]
            ];
            
            $conn->send(json_encode($loginPayload));
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
                $data = json_decode($msg->getPayload(), true);
                if (!$data) return;

                $action = $data['action'] ?? null;
                
                // Handle Login Response
                if ($action === '80000') {
                    if (($data['payload']['result'] ?? '') === 'success' || ($data['msg'] ?? '') === 'success') {
                        $this->info("✅ Login successful. Sending Subscribe (80001)...");
                        $conn->send(json_encode([
                            'action' => '80001',
                            'payload' => ''
                        ]));
                        $this->info("📡 Subscribed to realtime events!");
                    } else {
                        $this->error("❌ Login failed: " . json_encode($data));
                        $conn->close(); // Will trigger reconnect
                    }
                    return;
                }

                // Handle Subscribe Response (Optional verification)
                if ($action === '80001') {
                    $this->line("✅ Subscribe response received.");
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
            $this->connect($connector, $loop);
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

        // dtu dari WebSocket dalam format WIB (UTC+7), konversi ke WITA (UTC+8)
        $dtuRaw = $loc['dtu'] ?? null;
        if ($dtuRaw) {
            try {
                $gpsTime = \Carbon\Carbon::parse($dtuRaw, 'Asia/Jakarta')->setTimezone('Asia/Makassar');
                // Tolak timestamp masa depan dari hardware clock rusak
                if ($gpsTime->greaterThan(now()->addMinutes(1))) {
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

        $device = Device::where('device_id', $deviceId)->select('id', 'device_name', 'serial_no')->first();
        $deviceName = $device ? $device->device_name : null;

        $acc = (isset($payload['basic']['key']) && $payload['basic']['key'] == 1);

        try {
            // Simpan ke gps_tracks langsung (WebSocket adalah sumber real-time utama)
            \App\Models\GpsTrack::updateOrCreate(
                [
                    'device_id' => $deviceId,
                    'gps_time'  => $gpsTimeStr,
                ],
                [
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

            // Update lokasi terkini di tabel devices
            if ($device) {
                $device->update([
                    'location'   => "{$lat},{$lon}",
                    'updated_at' => now(),
                ]);
            }

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

        $device     = Device::where('device_id', $deviceId)->select('id', 'device_name', 'serial_no')->first();
        $deviceName = $device ? $device->device_name : null;
        $serialNo   = $device ? $device->serial_no   : null;

        $lat = (float)($loc['latitude']  ?? 0);
        $lon = (float)($loc['longitude'] ?? 0);
        $gpsString = ($lat && $lon) ? "{$lon},{$lat}" : null;

        $alarmTypeName = $payload['alarmTypeName'] ?? ('AlarmCode-' . $ec);

        try {
            // 1. Simpan ke alarm_raws (semua alarm types)
            \App\Models\AlarmRaw::updateOrCreate(
                ['guid' => $alarmId],
                [
                    'device_id'      => $deviceId,
                    'device_name'    => $deviceName,
                    'serial_no'      => $serialNo,
                    'alarm_type'     => $alarmTypeName,
                    'alarm_type_code'=> $ec,
                    'alarm_state'    => 'ALARM_END',
                    'start_time'     => $startTimeWita,
                    'end_time'       => $endTimeWita,
                    'latitude'       => $lat ?: null,
                    'longitude'      => $lon ?: null,
                    'location'       => $gpsString,
                    'speed'          => (float)($loc['speed'] ?? 0),
                    'source'         => 'websocket',
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
