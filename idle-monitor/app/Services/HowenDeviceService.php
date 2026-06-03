<?php

namespace App\Services;

use App\Models\Device;

class HowenDeviceService
{
    /**
     * Fetch devices from Howen API
     */
    public function fetchDevices()
    {
        // TODO: Implement fetch devices from Howen API
        // Use GuzzleHttp to call Howen endpoint
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
        // TODO: Fetch devices from Howen API
        // Upsert into devices table
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
