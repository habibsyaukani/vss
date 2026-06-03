<?php

namespace App\Services;

use App\Models\AlarmRaw;
use App\Models\IdleAlarm;
use App\Models\SystemSetting;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class HowenAlarmService
{
    private $client;
    private $apiUrl;
    private $authService;
    private const REQUEST_DELAY_MS = 500; // 500ms delay between requests

    public function __construct()
    {
        $this->client = new Client();
        $this->apiUrl = rtrim(env('HOWEN_API_URL'), '/');
        $this->authService = new HowenAuthService();
    }

    /**
     * Fetch alarms from Howen API with pagination
     * Endpoint: POST /alarm/apiFindAllByTime.action
     */
    public function fetchAlarmsPage($pageNum = 1, $pageCount = 200, $beginTime = null, $endTime = null, $deviceId = null, $alarmType = null)
    {
        try {
            $token = $this->authService->getToken();

            if (!$beginTime) {
                $beginTime = SystemSetting::get('last_alarm_sync', now()->subDays(1)->toDateTimeString());
            }
            if (!$endTime) {
                $endTime = now()->toDateTimeString();
            }

            Log::info("Fetching alarms", [
                'page' => $pageNum,
                'begin' => $beginTime,
                'end' => $endTime,
            ]);

            $response = $this->client->post("{$this->apiUrl}/alarm/apiFindAllByTime.action", [
                'form_params' => [
                    'token' => $token,
                    'pageNum' => $pageNum,
                    'pageCount' => $pageCount,
                    'beginTime' => $beginTime,
                    'endTime' => $endTime,
                    'alarmType' => $alarmType ?? '',
                    'deviceID' => $deviceId ?? '',
                ],
                'timeout' => 10,
                'verify' => false,
                'connect_timeout' => 10,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if ($data['status'] == 10000 && isset($data['data'])) {
                $alarms = is_array($data['data']) ? $data['data'] : [$data['data']];
                Log::info("Fetched alarms page", ['count' => count($alarms)]);
                return $alarms;
            } else {
                Log::error('Failed to fetch alarms', ['status' => $data['status'] ?? null, 'message' => $data['msg'] ?? null]);
                return [];
            }

        } catch (GuzzleException $e) {
            Log::error('Howen API request failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get alarms with mock fallback for development
     */
    public function fetchAlarmsPageWithMock($pageNum = 1, $pageCount = 200, $beginTime = null, $endTime = null)
    {
        $alarms = $this->fetchAlarmsPage($pageNum, $pageCount, $beginTime, $endTime);
        
        if (empty($alarms)) {
            Log::warning('No alarms from API, using mock data');
            return $this->getMockAlarms($pageNum);
        }
        
        return $alarms;
    }

    /**
     * Process idle alarms from alarm_raw
     */
    public function processIdleAlarm($alarmData)
    {
        // Filter only idle alarms (type 100)
        if (($alarmData['alarmtype'] ?? $alarmData['alarm_type'] ?? null) != 100) {
            return null;
        }

        // Calculate duration in minutes
        $startTime = strtotime($alarmData['createtime'] ?? $alarmData['start_time'] ?? 'now');
        $endTime = strtotime($alarmData['endTime'] ?? $alarmData['end_time'] ?? 'now');
        $durationSeconds = $endTime - $startTime;
        $durationMinutes = ceil($durationSeconds / 60);

        // Parse GPS coordinates
        $startGps = $alarmData['alarmGps'] ?? $alarmData['start_gps'] ?? null;
        $endGps = $alarmData['endGps'] ?? $alarmData['end_gps'] ?? null;

        $startLat = null;
        $startLong = null;
        $endLat = null;
        $endLong = null;

        if ($startGps && strpos($startGps, ',') !== false) {
            [$startLong, $startLat] = explode(',', $startGps);
        }
        if ($endGps && strpos($endGps, ',') !== false) {
            [$endLong, $endLat] = explode(',', $endGps);
        }

        return [
            'guid' => $alarmData['guid'] ?? null,
            'serial_no' => null,
            'device_id' => $alarmData['deviceguid'] ?? $alarmData['device_id'] ?? null,
            'device_name' => $alarmData['deviceName'] ?? $alarmData['device_name'] ?? null,
            'alarm_type' => 'Idle',
            'alarm_status' => 'new',
            'starting_time' => $alarmData['createtime'] ?? $alarmData['start_time'] ?? null,
            'starting_location' => $startGps,
            'ending_time' => $alarmData['endTime'] ?? $alarmData['end_time'] ?? null,
            'ending_location' => $endGps,
            'start_detail' => $alarmData['endDetail'] ?? null,
            'end_detail' => $alarmData['endDetail'] ?? null,
            'start_speed' => (float)($alarmData['speed'] ?? $alarmData['start_speed'] ?? 0),
            'end_speed' => (float)($alarmData['endSpeed'] ?? $alarmData['end_speed'] ?? 0),
            'report_time' => $alarmData['reportTime'] ?? $alarmData['report_time'] ?? null,
            'duration_seconds' => $durationSeconds,
            'duration_minutes' => $durationMinutes,
            'latitude_start' => $startLat ? (float)$startLat : null,
            'longitude_start' => $startLong ? (float)$startLong : null,
            'latitude_end' => $endLat ? (float)$endLat : null,
            'longitude_end' => $endLong ? (float)$endLong : null,
        ];
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

    /**
     * Get mock alarms for development
     */
    private function getMockAlarms($pageNum = 1)
    {
        if ($pageNum > 1) {
            return [];
        }

        return [
            [
                'guid' => 'alarm-001',
                'deviceguid' => '99990001',
                'deviceName' => 'TRUCK-001',
                'alarmtype' => 100,
                'alarmState' => 1,
                'createtime' => now()->subHours(2)->toDateTimeString(),
                'alarmGps' => '117.153,-0.502',
                'speed' => '0',
                'reportTime' => now()->subHours(2)->toDateTimeString(),
                'endTime' => now()->subHours(1)->toDateTimeString(),
                'endGps' => '117.153,-0.502',
                'alarmTimeLength' => '3600',
                'endSpeed' => '0',
                'endDetail' => 'Engine ON',
            ],
            [
                'guid' => 'alarm-002',
                'deviceguid' => '99990002',
                'deviceName' => 'TRUCK-002',
                'alarmtype' => 100,
                'alarmState' => 1,
                'createtime' => now()->subHours(3)->toDateTimeString(),
                'alarmGps' => '117.154,-0.503',
                'speed' => '0',
                'reportTime' => now()->subHours(3)->toDateTimeString(),
                'endTime' => now()->subHours(2)->toDateTimeString(),
                'endGps' => '117.154,-0.503',
                'alarmTimeLength' => '3600',
                'endSpeed' => '0',
                'endDetail' => 'Idle Detected',
            ],
        ];
    }
}
