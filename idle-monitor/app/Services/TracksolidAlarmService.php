<?php

namespace App\Services;

use App\Models\Device;
use App\Models\IdleAlarm;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TracksolidAlarmService
{
    private TracksolidApiService $apiService;

    public function __construct(TracksolidApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Pull alarms from Tracksolid API and sync to local DB
     */
    public function syncAlarms(string $imei, string $beginTime, string $endTime): array
    {
        $stats = [
            'total_fetched' => 0,
            'total_inserted' => 0,
            'errors' => []
        ];

        Log::info("[Tracksolid Alarm Sync] Fetching alarms for IMEI: {$imei} from {$beginTime} to {$endTime}");

        // Call the API
        $response = $this->apiService->callApi('jimi.device.alarm.list', [
            'imeis' => $imei,
            'begin_time' => $beginTime,
            'end_time' => $endTime,
            'page_no' => 1,
            'page_size' => 200,
        ]);

        if (!$response['success']) {
            $stats['errors'][] = $response['message'];
            return $stats;
        }

        $alarms = $response['result'] ?? [];

        if (empty($alarms)) {
            return $stats;
        }

        $stats['total_fetched'] = count($alarms);
        $device = Device::where('imei', $imei)->orWhere('device_id', $imei)->first();

        foreach ($alarms as $alarm) {
            try {
                $alarmImei = $alarm['imei'] ?? null;
                $alertTime = $alarm['alertTime'] ?? null;
                if (!$alertTime || !$alarmImei) continue;

                // Cek duplikasi berdasarkan waktu dan imei
                $existing = IdleAlarm::where('device_id', $alarmImei)
                                     ->where('starting_time', $alertTime)
                                     ->where('alarm_type', $alarm['alertTypeId'] ?? 'Unknown')
                                     ->first();

                if (!$existing) {
                    IdleAlarm::create([
                        'guid'              => Str::uuid(),
                        'device_id'         => $alarmImei,
                        'device_name'       => $alarm['deviceName'] ?? $alarmImei,
                        'alarm_type'        => $alarm['alertTypeId'] ?? 'Unknown',
                        'start_detail'      => $alarm['alarmTypeName'] ?? 'Tracksolid Alarm',
                        'starting_time'     => $alertTime,
                        'ending_time'       => $alertTime, // Tracksolid alarm list generally returns point-in-time events
                        'duration_seconds'  => 0,
                        'duration_minutes'  => 0,
                        'latitude_start'    => $alarm['lat'] ?? 0,
                        'longitude_start'   => $alarm['lng'] ?? 0,
                        'start_speed'       => $alarm['speed'] ?? 0,
                        'alarm_status'      => $alarm['status'] ?? 0,
                        'report_time'       => $alarm['pushTime'] ?? $alertTime,
                    ]);
                    $stats['total_inserted']++;
                }

            } catch (\Exception $e) {
                Log::error("[Tracksolid Alarm Sync] Error saving alarm for IMEI {$imei}: " . $e->getMessage());
                $stats['errors'][] = "IMEI {$imei}: " . $e->getMessage();
            }
        }

        return $stats;
    }
}
