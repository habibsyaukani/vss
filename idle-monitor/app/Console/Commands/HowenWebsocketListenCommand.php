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
        $connector = new Connector($loop, [
            'timeout' => 15,
            'tls' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);

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
        $dtu = $loc['dtu'] ?? now()->toDateTimeString();
        $lat = (float)($loc['latitude'] ?? 0);
        $lon = (float)($loc['longitude'] ?? 0);
        
        $acc = (isset($payload['basic']['key']) && $payload['basic']['key'] == 1) ? true : false;
        
        if ($lat == 0 || $lon == 0) return; // Invalid GPS

        // Update to gps_tracks
        // Menggunakan updateOrCreate untuk mencegah duplikasi jika waktu sama persis
        GpsTrack::updateOrCreate(
            [
                'device_id' => $deviceId,
                'gps_time' => $dtu
            ],
            [
                'latitude' => $lat,
                'longitude' => $lon,
                'speed' => $speed,
                'direction' => $loc['direct'] ?? 0,
                'satellites' => $loc['satellites'] ?? 0,
                'altitude' => $loc['altitude'] ?? 0,
                'is_acc_on' => $acc,
                // Kolom lain dapat disesuaikan dengan skema tabel GpsTrack Anda
            ]
        );

        // Opsional: Anda bisa memperbarui 'location' (format lat,lon) di tabel devices
        Device::where('device_id', $deviceId)->update([
            'location' => "{$lat},{$lon}",
            'updated_at' => now()
        ]);
        
        // $this->line("📍 GPS Updated: Device {$deviceId} at {$dtu} (Speed: {$speed})");
    }

    private function handleAlarmPush($payload)
    {
        $deviceId = $payload['deviceID'] ?? null;
        $alarmId = $payload['alarmID'] ?? null; // Digunakan sebagai GUID
        $ec = $payload['ec'] ?? null;           // Event Code / Alarm Type
        
        if (!$deviceId || !$alarmId) return;

        // ec = 32 adalah Idle Alarm
        if ($ec != '32' && $ec != 32) {
            return; // Ignore other alarms
        }

        $st = $payload['st'] ?? null; // Start Time
        $et = $payload['et'] ?? null; // End Time
        $loc = $payload['location'] ?? [];

        $durationSeconds = 0;
        $durationMinutes = 0;
        
        if ($st && $et) {
            $startTime = \Carbon\Carbon::parse($st);
            $endTime = \Carbon\Carbon::parse($et);
            $durationSeconds = $endTime->diffInSeconds($startTime);
            $durationMinutes = ceil($durationSeconds / 60);
        }

        // Jika durasi 0, abaikan (mungkin alarm invalid atau alarm mulai tapi belum selesai)
        if ($durationSeconds <= 0) return;

        $lat = (float)($loc['latitude'] ?? 0);
        $lon = (float)($loc['longitude'] ?? 0);
        $gpsString = ($lat && $lon) ? "{$lon},{$lat}" : null; // Howen format lon,lat

        // Cari data device untuk mendapatkan serial_no dan device_name
        $device = Device::where('device_id', $deviceId)->first();
        
        IdleAlarm::updateOrCreate(
            ['guid' => $alarmId],
            [
                'serial_no' => $device ? $device->serial_no : null,
                'device_id' => $deviceId,
                'device_name' => $device ? $device->device_name : null,
                'alarm_type' => 'Idle',
                'alarm_status' => 'ALARM_END', // Realtime push mengirimkan saat alarm selesai
                'starting_time' => $st,
                'ending_time' => $et,
                'duration_seconds' => $durationSeconds,
                'duration_minutes' => $durationMinutes,
                'latitude_start' => $lat, // Asumsikan start/end di posisi yang sama untuk idle
                'longitude_start' => $lon,
                'latitude_end' => $lat,
                'longitude_end' => $lon,
                'starting_location' => $gpsString,
                'ending_location' => $gpsString,
                'start_speed' => 0,
                'end_speed' => (float)($loc['speed'] ?? 0),
                'report_time' => $loc['dtu'] ?? now()->toDateTimeString(),
            ]
        );

        $this->info("🚨 Idle Alarm Received: Device {$deviceId} duration {$durationMinutes} min");
    }
}
