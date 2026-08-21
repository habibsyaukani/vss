<?php

namespace App\Services;

use App\Models\Device;
use App\Models\SystemSetting;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class HowenDeviceService
{
    private $client;
    private $apiUrl;
    private $authService;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiUrl = rtrim(config('vss.howen_api_url'), '/');
        $this->authService = new HowenAuthService();
    }

    /**
     * Fetch devices from Howen API
     * Endpoint: POST /vss/vehicle/findAll.action (port 9966)
     */
    public function fetchDevices($useMock = false)
    {
        try {
            $token = $this->authService->getToken();

            Log::info('Fetching devices from Howen API');

            // Extract host and scheme properly
            $parsedUrl = parse_url($this->apiUrl);
            $host = $parsedUrl['host'] ?? '';
            $scheme = $parsedUrl['scheme'] ?? 'http';
            
            // List of endpoints to try (Primary endpoint without port 9966 FIRST)
            $endpoints = [
                "{$this->apiUrl}/vehicle/findAll.action",             // Primary working endpoint
                "http://{$host}:9966/vss/vehicle/findAll.action",
                "https://{$host}:9966/vss/vehicle/findAll.action",
            ];

            $lastError = null;

            foreach ($endpoints as $endpoint) {
                try {
                    Log::info("Trying endpoint: {$endpoint}");

                    $response = $this->client->post($endpoint, [
                        'form_params' => [
                            'token' => $token,
                            'pageNum' => '1',
                            'pageCount' => '20000',
                            'isOnline' => '',  // All (online + offline)
                            'keyword' => '',
                        ],
                        'timeout' => 15,
                        'verify' => false,
                    ]);

                    $data = json_decode($response->getBody()->getContents(), true);

                    if (isset($data['status']) && $data['status'] == 10000 && isset($data['data'])) {
                        $devices = is_array($data['data']) ? $data['data'] : [$data['data']];
                        Log::info("✅ Success with endpoint: {$endpoint}", ['count' => count($devices)]);
                        return $devices;
                    } elseif (isset($data['data'])) {
                        // Some responses might not have status field, check for data directly
                        $devices = is_array($data['data']) ? $data['data'] : [$data['data']];
                        Log::info("✅ Success with endpoint (no status check): {$endpoint}", ['count' => count($devices)]);
                        return $devices;
                    }

                } catch (\Exception $e) {
                    $lastError = $e->getMessage();
                    Log::warning("Endpoint failed: {$endpoint}", ['error' => substr($lastError, 0, 100)]);
                    continue;
                }
            }

            // All endpoints failed
            Log::error('All device endpoints failed', ['last_error' => $lastError]);
            
            // Try to extract devices from alarms as fallback
            Log::info('Attempting to extract devices from existing alarms');
            try {
                $alarmResponse = $this->client->post("{$this->apiUrl}/alarm/apiFindAllByTime.action", [
                    'form_params' => [
                        'token' => $token,
                        'pageNum' => 1,
                        'pageCount' => 500,
                        'beginTime' => now()->subMonths(1)->toDateTimeString(),
                        'endTime' => now()->toDateTimeString(),
                    ],
                    'timeout' => 60,
                    'verify' => false,
                ]);

                $alarmData = json_decode($alarmResponse->getBody()->getContents(), true);

                if (isset($alarmData['data'])) {
                    $alarms = is_array($alarmData['data']) ? $alarmData['data'] : [$alarmData['data']];
                    // Extract unique devices from alarms
                    $devicesMap = [];
                    foreach ($alarms as $alarm) {
                        $deviceId = $alarm['deviceguid'] ?? $alarm['deviceID'] ?? null;
                        $deviceName = $alarm['deviceName'] ?? null;
                        if ($deviceId && !isset($devicesMap[$deviceId])) {
                            $devicesMap[$deviceId] = [
                                'deviceID' => $deviceId,
                                'deviceName' => $deviceName,
                                'groupName' => $alarm['groupName'] ?? null,
                            ];
                        }
                    }
                    $devices = array_values($devicesMap);
                    Log::info("✅ Devices extracted from alarms", ['count' => count($devices)]);
                    return $devices;
                }
            } catch (\Exception $e) {
                Log::warning("Device extraction from alarms failed", ['error' => substr($e->getMessage(), 0, 100)]);
            }

            // All endpoints failed
            Log::error('All device endpoints failed', ['last_error' => $lastError]);
            return [];
        } catch (GuzzleException $e) {
            Log::error('Howen API request failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get mock devices for development/testing
     * Using REAL Howen device naming format from fleet
     * Format: GPE-B-8322(755161145) where GPE-B-8322 is deviceName, 755161145 is deviceID
     */
    private function getMockDevices()
    {
        return [
            [
                'deviceID' => '755161145',
                'deviceName' => 'GPE-B-8322',
                'imei' => '862267036256784',
                'sim' => '08123456789',
                'groupName' => 'BUS - GPE',
            ],
            [
                'deviceID' => '732390518',
                'deviceName' => 'GPE-FT-873',
                'imei' => '862267036256785',
                'sim' => '08123456790',
                'groupName' => 'FT - GPE',
            ],
            [
                'deviceID' => '731865503',
                'deviceName' => 'GPE-DTI-807',
                'imei' => '862267036256786',
                'sim' => '08123456791',
                'groupName' => 'DT - GPE',
            ],
        ];
    }

    /**
     * Get device by ID
     */
    public function getDevice($deviceId)
    {
        return Device::where('device_id', $deviceId)->firstOrFail();
    }

    /**
     * Sync devices to database from Howen API
     */
    public function syncDevices()
    {
        try {
            $devices = $this->fetchDevices();
            
            if (empty($devices)) {
                Log::warning('No devices returned from Howen API');
                return 0;
            }

            $synced = 0;

            foreach ($devices as $device) {
                // Map Howen field to database (handle both camelCase and variations)
                $deviceId = $device['deviceID'] ?? $device['deviceId'] ?? $device['device_id'] ?? null;
                $deviceName = $device['deviceName'] ?? $device['devicename'] ?? $device['device_name'] ?? null;
                
                if (!$deviceId) {
                    Log::warning('Skipping device without ID', ['device' => json_encode($device)]);
                    continue;
                }

                Device::updateOrCreate(
                    ['device_id' => $deviceId],
                    [
                        'device_name' => $deviceName,
                        'group_name' => $device['groupName'] ?? $device['group_name'] ?? null,
                        'imei' => $device['imei'] ?? $device['IMEI'] ?? null,
                        'sim_number' => $device['sim'] ?? $device['simNumber'] ?? $device['sim_number'] ?? null,
                        'last_sync_at' => now(),
                    ]
                );

                $synced++;
            }

            // Update last sync time
            \App\Models\SystemSetting::set('last_device_sync', now()->toDateTimeString());

            Log::info("Device sync completed", ['synced' => $synced]);
            return $synced;

        } catch (\Exception $e) {
            Log::error('Device sync failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get device location from latest alarm
     */
    public function getDeviceLocation($deviceId)
    {
        // TODO: Get latest GPS location from idle_alarms
        // Return latitude and longitude
    }
}
