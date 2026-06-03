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
     * 
     * FIELD MAPPING CORRECTION:
     * start_detail         = alarmValue (e.g., "avg:0.00 ; cur:0.00 ; dur:0 ; max:0.00 ; min:0.00 ; pre:5.00 ; tt:300 ; vt:2 ; satellites:22")
     * end_detail           = endDetail (e.g., "dur:59 ; tt:300 ; cur:13.72 ; pre:13.00 ; avg:1.20 ; min:9.71 ; max:13.72 ; vt:2 ; satellites:22")
     * 
     * IMPORTANT: Extract dur value from endDetail!
     * endDetail contains: "dur:59" which is the ACTUAL idle duration in seconds (59 seconds)
     * This is the final idle duration, NOT alarmTimeLength
     */
    public function processIdleAlarm($alarmData)
    {
        // Filter only idle alarms (type 100)
        if (($alarmData['alarmtype'] ?? $alarmData['alarm_type'] ?? null) != 100) {
            return null;
        }

        // IMPORTANT: Extract 'dur' value from endDetail
        // endDetail format: "dur:59 ; tt:300 ; cur:13.72 ; ..."
        $endDetail = $alarmData['endDetail'] ?? $alarmData['end_detail'] ?? '';
        $durationSeconds = 0;
        
        if (preg_match('/dur:\s*(\d+)/', $endDetail, $matches)) {
            // Extract duration from endDetail
            $durationSeconds = (int)$matches[1];  // 59 seconds
        } else {
            // Fallback: use alarmTimeLength if dur not found in endDetail
            $durationSeconds = (int)($alarmData['alarmTimeLength'] ?? $alarmData['duration_seconds'] ?? 0);
        }
        
        $durationMinutes = floor($durationSeconds / 60);

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
            'start_detail' => $alarmData['alarmValue'] ?? $alarmData['start_detail'] ?? null,
            'end_detail' => $endDetail,  // Full endDetail string
            'start_speed' => (float)($alarmData['speed'] ?? $alarmData['start_speed'] ?? 0),
            'end_speed' => (float)($alarmData['endSpeed'] ?? $alarmData['end_speed'] ?? 0),
            'report_time' => $alarmData['reportTime'] ?? $alarmData['report_time'] ?? null,
            'duration_seconds' => $durationSeconds,  // Extracted from endDetail dur field
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
     * Using REAL Howen device naming from fleet
     */
    private function getMockAlarms($pageNum = 1)
    {
        // Return different mock data based on page number
        // This simulates pagination
        
        if ($pageNum > 3) {
            return []; // Stop after page 3
        }

        $baseAlarms = [
            [
                'guid' => 'alarm-' . (($pageNum - 1) * 2 + 1),
                'deviceguid' => '755161145',
                'deviceName' => 'GPE-B-8322',
                'alarmtype' => 100,
                'alarmState' => 1,
                'createtime' => now()->subHours(4 - $pageNum)->toDateTimeString(),
                'alarmGps' => '-6.2197,107.0088',
                'speed' => '0',
                'reportTime' => now()->subHours(4 - $pageNum)->toDateTimeString(),
                'endTime' => now()->subHours(3 - $pageNum)->toDateTimeString(),
                'endGps' => '-6.2197,107.0088',
                // alarmTimeLength (total time before resuming movement)
                'alarmTimeLength' => '9200',  // 2.5 hours from createtime to endTime
                'endSpeed' => '15',
                // start_detail: idle START conditions
                'alarmValue' => 'avg:0.00 ; cur:0.00 ; dur:0 ; max:0.00 ; min:0.00 ; pre:5.00 ; tt:300 ; vt:2 ; satellites:22',
                // end_detail: idle END conditions - dur:59 is ACTUAL idle duration before movement
                'endDetail' => 'dur:59 ; tt:300 ; cur:13.72 ; pre:13.00 ; avg:1.20 ; min:9.71 ; max:13.72 ; vt:2 ; satellites:22',
            ],
            [
                'guid' => 'alarm-' . (($pageNum - 1) * 2 + 2),
                'deviceguid' => '732390518',
                'deviceName' => 'GPE-FT-873',
                'alarmtype' => 100,
                'alarmState' => 1,
                'createtime' => now()->subHours(5 - $pageNum)->toDateTimeString(),
                'alarmGps' => '-6.1753,107.0147',
                'speed' => '0',
                'reportTime' => now()->subHours(5 - $pageNum)->toDateTimeString(),
                'endTime' => now()->subHours(4 - $pageNum)->toDateTimeString(),
                'endGps' => '-6.1753,107.0147',
                // alarmTimeLength (total time before resuming movement)
                'alarmTimeLength' => '7340',  // 2 hours 2 min 20 sec from createtime to endTime
                'endSpeed' => '20',
                // start_detail: idle START conditions
                'alarmValue' => 'avg:0.00 ; cur:0.00 ; dur:0 ; max:0.00 ; min:0.00 ; pre:4.50 ; tt:280 ; vt:2 ; satellites:21',
                // end_detail: idle END conditions - dur:120 is ACTUAL idle duration before movement
                'endDetail' => 'dur:120 ; tt:280 ; cur:11.50 ; pre:12.00 ; avg:0.95 ; min:8.50 ; max:14.00 ; vt:2 ; satellites:21',
            ],
        ];

        return $baseAlarms;
    }
}
