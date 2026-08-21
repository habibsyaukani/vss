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

        Log::info("[Tracksolid Alarm Sync] Fetching idling report for IMEI: {$imei} from {$beginTime} to {$endTime}");

        $response = $this->apiService->callApi('jimi.open.platform.report.parking', [
            'account' => env('TRACKSOLID_USERNAME'),
            'imeis' => $imei,
            'start_time' => $beginTime,
            'end_time' => $endTime,
            'start_row' => 0,
            'page_size' => 200,
            'acc_type' => 'on'
        ]);

        if (!$response['success']) {
            $stats['errors'][] = $response['message'];
            return $stats;
        }

        $alarms = $response['data']['rows'] ?? [];

        if (empty($alarms)) {
            return $stats;
        }

        $stats['total_fetched'] = count($alarms);

        foreach ($alarms as $alarm) {
            try {
                $alarmImei = $alarm['imei'] ?? null;
                $alertTime = $alarm['startTime'] ?? null;
                if (!$alertTime || !$alarmImei) continue;

                $parsedStartingTime = Carbon::parse($alertTime)->setTimezone('Asia/Makassar')->format('Y-m-d H:i:s');

                $existing = IdleAlarm::where('device_id', $alarmImei)
                                     ->where('starting_time', $parsedStartingTime)
                                     ->where('alarm_type', 'Idle')
                                     ->first();

                if (!$existing) {
                    $durationSeconds = (int)($alarm['durSecond'] ?? 0);
                    $durationMinutes = floor($durationSeconds / 60);

                    IdleAlarm::create([
                        'guid'              => Str::uuid(),
                        'device_id'         => $alarmImei,
                        'device_name'       => $alarm['deviceName'] ?? $alarmImei,
                        'alarm_type'        => 'Idle',
                        'alarm_status'      => 'on',
                        'alarm_state'       => 0,
                        'starting_time'     => Carbon::parse($alarm['startTime'] ?? now())->setTimezone('Asia/Makassar')->format('Y-m-d H:i:s'),
                        'ending_time'       => Carbon::parse($alarm['endTime'] ?? now())->setTimezone('Asia/Makassar')->format('Y-m-d H:i:s'),
                        'starting_location' => (isset($alarm['lng']) && isset($alarm['lat'])) ? $alarm['lng'] . ',' . $alarm['lat'] : null,
                        'ending_location'   => null,
                        'start_detail'      => $alarm['addr'] ?? null,
                        'latitude_start'    => $alarm['lat'] ?? null,
                        'longitude_start'   => $alarm['lng'] ?? null,
                        'latitude_end'      => null,
                        'longitude_end'     => null,
                        'duration_seconds'  => $durationSeconds,
                        'duration_minutes'  => $durationMinutes,
                        'report_time'       => Carbon::parse($alarm['startTime'] ?? now())->setTimezone('Asia/Makassar')->format('Y-m-d H:i:s'),
                        'start_speed'       => null, // Null to display as '-'
                    ]);
                    $stats['total_inserted']++;
                }

            } catch (\Exception $e) {
                Log::error("[Tracksolid Alarm Sync] Error saving idling for IMEI {$imei}: " . $e->getMessage());
                $stats['errors'][] = "IMEI {$imei}: " . $e->getMessage();
            }
        }

        return $stats;
    }
}
