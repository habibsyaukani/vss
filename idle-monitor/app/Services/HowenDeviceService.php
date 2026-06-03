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
        $this->apiUrl = rtrim(env('HOWEN_API_URL'), '/');
        $this->authService = new HowenAuthService();
    }

    /**
     * Fetch devices from Howen API
     * Try multiple endpoints and ports
     * Fall back to mock data if all fail
     */
    public function fetchDevices($useMock = false)
    {
        try {
            if ($useMock) {
                Log::info('Using mock device data for testing');
                return $this->getMockDevices();
            }

            $token = $this->authService->getToken();

            Log::info('Fetching devices from Howen API');

            // List of endpoints to try
            $endpoints = [
                "{$this->apiUrl}/vehicle/getDeviceList.action",
                "{$this->apiUrl}/vehicle/apiFindVehicle.action",
                "https://vss.ptdigital.co.id:9966/vss/vehicle/getDeviceList.action",
                "https://vss.ptdigital.co.id:9966/vss/vehicle/apiFindVehicle.action",
            ];

            $lastError = null;

            foreach ($endpoints as $endpoint) {
                try {
                    Log::info("Trying endpoint: {$endpoint}");

                    $response = $this->client->post($endpoint, [
                        'form_params' => [
                            'token' => $token,
                        ],
                        'timeout' => 10,
                        'verify' => false,
                    ]);

                    $data = json_decode($response->getBody()->getContents(), true);

                    if ($data['status'] == 10000 && isset($data['data'])) {
                        $devices = is_array($data['data']) ? $data['data'] : [$data['data']];
                        Log::info("✅ Success with endpoint: {$endpoint}", ['count' => count($devices)]);
                        return $devices;
                    }

                } catch (\Exception $e) {
                    $lastError = $e->getMessage();
                    Log::warning("Endpoint failed: {$endpoint}", ['error' => substr($lastError, 0, 100)]);
                    continue;
                }
            }

            // All endpoints failed, use mock for development
            Log::warning('All device endpoints failed, falling back to mock data');
            return $this->getMockDevices();

        } catch (GuzzleException $e) {
            Log::error('Howen API request failed', ['error' => $e->getMessage()]);
            return $this->getMockDevices();
        }
    }

    /**
     * Get mock devices for development/testing
     */
    private function getMockDevices()
    {
        return [
            [
                'deviceID' => '99990001',
                'deviceName' => 'TRUCK-001',
                'imei' => '869459030007543',
                'sim' => '62812345678901',
            ],
            [
                'deviceID' => '99990002',
                'deviceName' => 'TRUCK-002',
                'imei' => '869459030007544',
                'sim' => '62812345678902',
            ],
            [
                'deviceID' => '99990003',
                'deviceName' => 'TRUCK-003',
                'imei' => '869459030007545',
                'sim' => '62812345678903',
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
                    Log::warning('Skipping device without ID', $device);
                    continue;
                }

                Device::updateOrCreate(
                    ['device_id' => $deviceId],
                    [
                        'device_name' => $deviceName,
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
