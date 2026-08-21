<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Facades\Log;

class TracksolidDeviceService
{
    private TracksolidApiService $apiService;

    public function __construct(TracksolidApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Pull all devices from a specific Tracksolid account and sync to local DB
     */
    public function syncDevices(string $targetAccount): array
    {
        $stats = [
            'total_fetched' => 0,
            'total_inserted' => 0,
            'total_updated' => 0,
            'errors' => []
        ];

        Log::info("[Tracksolid Device Sync] Fetching devices for account: {$targetAccount}");

        // Call the API
        $response = $this->apiService->callApi('jimi.user.device.list', [
            'target' => $targetAccount
        ]);

        if (!$response['success']) {
            $stats['errors'][] = $response['message'];
            return $stats;
        }

        $devices = $response['result'] ?? [];

        if (empty($devices)) {
            Log::info("[Tracksolid Device Sync] No devices found for account {$targetAccount}");
            return $stats;
        }

        $stats['total_fetched'] = count($devices);

        foreach ($devices as $device) {
            try {
                $imei = $device['imei'] ?? null;
                if (!$imei) continue;

                // Cek apakah device sudah ada di database
                $existingDevice = Device::where('imei', $imei)->orWhere('device_id', $imei)->first();

                $deviceData = [
                    'device_id'   => $imei, // Kita set device_id sama dengan IMEI
                    'imei'        => $imei,
                    'device_name' => $device['deviceName'] ?? $imei,
                    'plate_no'    => $device['vehicleNumber'] ?? null,
                    'sim_number'  => $device['sim'] ?? null,
                    'group_name'  => $device['deviceGroup'] ?? null,
                    'series'      => $device['mcType'] ?? null,
                    'status'      => isset($device['enabledFlag']) ? (int) $device['enabledFlag'] : 1,
                    // Tracksolid tidak memberikan lokasi di endpoint ini, jadi dibiarkan saja
                ];

                if ($existingDevice) {
                    $existingDevice->update($deviceData);
                    $stats['total_updated']++;
                } else {
                    Device::create($deviceData);
                    $stats['total_inserted']++;
                }

            } catch (\Exception $e) {
                Log::error("[Tracksolid Device Sync] Error saving device IMEI {$imei}: " . $e->getMessage());
                $stats['errors'][] = "IMEI {$imei}: " . $e->getMessage();
            }
        }

        return $stats;
    }
}
