<?php

namespace App\Services;

use App\Models\IdleAlarm;
use App\Models\AlarmRaw;

class HowenAlarmService
{
    /**
     * Fetch alarms from Howen API
     */
    public function fetchAlarms($deviceId = null)
    {
        // TODO: Implement fetch alarms from Howen API
        // Use GuzzleHttp to call Howen endpoint
    }

    /**
     * Process idle alarms from raw alarm data
     */
    public function processIdleAlarm($alarmData)
    {
        // TODO: Extract idle alarm data from raw alarm
        // Filter only idle alarms (alarm_type = 100)
        // Create IdleAlarm record from AlarmRaw
    }

    /**
     * Get alarm statistics
     */
    public function getAlarmStats($startDate = null, $endDate = null)
    {
        $query = IdleAlarm::query();
        
        if ($startDate) {
            $query->whereDate('starting_time', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('starting_time', '<=', $endDate);
        }
        
        return [
            'total_alarms' => $query->count(),
            'by_device' => $query->groupBy('device_id')->selectRaw('device_id, COUNT(*) as count')->get(),
            'avg_duration' => $query->avg('duration_minutes'),
        ];
    }

    /**
     * Acknowledge alarm
     */
    public function acknowledgeAlarm($alarmId)
    {
        $alarm = IdleAlarm::findOrFail($alarmId);
        $alarm->update(['alarm_status' => 'acknowledged']);
        return $alarm;
    }
}
