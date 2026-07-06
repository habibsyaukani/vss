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
        $this->apiUrl = rtrim(config('vss.howen_api_url'), '/');
        // Use VssAuthService because HowenAuthService is failing due to JSON vs Form differences
        $this->authService = new VssAuthService();
    }

    /**
     * Fetch alarms from Howen API with pagination
     * Endpoint: POST /alarm/apiFindAllByTime.action
     * 
     * @param int $pageNum Page number for pagination
     * @param int $pageCount Records per page
     * @param string|null $beginTime Start time filter (YYYY-MM-DD HH:mm:ss)
     * @param string|null $endTime End time filter (YYYY-MM-DD HH:mm:ss)
     * @param string|null $deviceId Filter by device ID
     * @param int|null $alarmType Filter by alarm type
     * @return array Array of alarm records
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

            Log::info("Fetching alarms from Howen API", [
                'page' => $pageNum,
                'page_count' => $pageCount,
                'begin' => $beginTime,
                'end' => $endTime,
                'device_id' => $deviceId,
                'alarm_type' => $alarmType,
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
                // Handle two possible response structures:
                // Structure 1: data is array of alarm objects directly
                // Structure 2: data is object with 'dataList' key containing alarms
                
                $dataItem = $data['data'];
                
                if (isset($dataItem['dataList'])) {
                    // New structure: data contains metadata + dataList
                    $alarms = is_array($dataItem['dataList']) ? $dataItem['dataList'] : [$dataItem['dataList']];
                    Log::info("Fetched alarms page (new structure)", [
                        'count' => count($alarms),
                        'totalCount' => $dataItem['totalCount'] ?? null,
                        'pageNum' => $pageNum,
                    ]);
                } else {
                    // Old structure: data is direct array of alarms
                    $alarms = is_array($dataItem) ? $dataItem : [$dataItem];
                    Log::info("Fetched alarms page (old structure)", ['count' => count($alarms)]);
                }
                
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
     * Get alarms with EXPLICIT source logging
     * IMPORTANT: Log clearly if using mock or real API
     */
    public function fetchAlarmsPageWithMock($pageNum = 1, $pageCount = 200, $beginTime = null, $endTime = null)
    {
        $alarms = $this->fetchAlarmsPage($pageNum, $pageCount, $beginTime, $endTime);
        
        if (empty($alarms)) {
            // Log warning bahwa kita fallback ke mock
            Log::warning('⚠️ USING MOCK DATA - API returned empty', [
                'page' => $pageNum,
                'reason' => 'Real Howen API not returning data - check endpoint configuration'
            ]);
            return $this->getMockAlarms($pageNum);
        }
        
        // Log kalau berasal dari API yang real
        Log::info('✅ REAL DATA FROM API', ['page' => $pageNum, 'count' => count($alarms)]);
        return $alarms;
    }

    /**
     * OPSI 1: Fetch multiple pages IN PARALLEL (faster)
     * 
     * Uses GuzzleHttp concurrent requests for 3-4x speed improvement
     * 
     * @param int $startPage Starting page number
     * @param int $endPage Ending page number
     * @param int $pageCount Records per page
     * @param string|null $beginTime Start time
     * @param string|null $endTime End time
     * @param int $concurrency How many pages to fetch simultaneously (default: 3)
     * @return array Combined array of all alarms from all pages
     */
    public function fetchAlarmsParallel($startPage = 1, $endPage = 7, $pageCount = 200, $beginTime = null, $endTime = null, $concurrency = 3)
    {
        try {
            $token = $this->authService->getToken();

            if (!$beginTime) {
                $beginTime = SystemSetting::get('last_alarm_sync', now()->subDays(1)->toDateTimeString());
            }
            if (!$endTime) {
                $endTime = now()->toDateTimeString();
            }

            Log::info("⚡ Starting PARALLEL alarm fetch (Option 1)", [
                'pages' => "{$startPage}-{$endPage}",
                'concurrency' => $concurrency,
                'total_pages' => ($endPage - $startPage + 1),
                'date_range' => "{$beginTime} to {$endTime}",
            ]);

            // Build requests for all pages as CALLABLES that return Promises
            // Guzzle Pool requires an array of closures (or RequestInterface)
            $requests = [];
            $pageNumbers = [];
            
            for ($pageNum = $startPage; $pageNum <= $endPage; $pageNum++) {
                $pageNumbers[] = $pageNum;
                
                $requests[] = function() use ($pageNum, $token, $pageCount, $beginTime, $endTime) {
                    return $this->client->postAsync("{$this->apiUrl}/alarm/apiFindAllByTime.action", [
                        'form_params' => [
                            'token' => $token,
                            'pageNum' => $pageNum,
                            'pageCount' => $pageCount,
                            'beginTime' => $beginTime,
                            'endTime' => $endTime,
                            'alarmType' => '',
                            'deviceID' => '',
                        ],
                        'timeout' => 10,
                        'verify' => false,
                        'connect_timeout' => 10,
                    ]);
                };
            }

            // Inisialisasi array kosong SEBELUM pool
            $allAlarms = [];

            // Execute all requests concurrently
            $pool = new \GuzzleHttp\Pool($this->client, $requests, [
                'concurrency' => $concurrency,
                'fulfilled' => function($response, $index) use ($pageNumbers, &$allAlarms) {
                    $pageNum = $pageNumbers[$index];
                    $data = json_decode($response->getBody()->getContents(), true);
                    
                    if ($data['status'] == 10000 && isset($data['data'])) {
                        $dataItem = $data['data'];
                        $alarms = isset($dataItem['dataList']) 
                            ? (is_array($dataItem['dataList']) ? $dataItem['dataList'] : [$dataItem['dataList']])
                            : (is_array($dataItem) ? $dataItem : [$dataItem]);
                        
                        Log::info("✅ Page {$pageNum} fetched", ['count' => count($alarms)]);
                        
                        // PUSH ke reference array
                        if (is_array($alarms) && !empty($alarms)) {
                            $allAlarms = array_merge($allAlarms, $alarms);
                        }
                    }
                },
                'rejected' => function($reason, $index) use ($pageNumbers) {
                    $pageNum = $pageNumbers[$index];
                    Log::error("❌ Page {$pageNum} failed", ['error' => $reason->getMessage()]);
                }
            ]);

            // Wait for all requests to complete
            $promise = $pool->promise();
            $promise->wait();

            $totalCount = count($allAlarms);
            Log::info("⚡ PARALLEL fetch completed", [
                'pages_fetched' => ($endPage - $startPage + 1),
                'total_records' => $totalCount,
                'concurrency' => $concurrency,
            ]);

            return $allAlarms;

        } catch (\Exception $e) {
            Log::error('⚠️ Parallel fetch error', ['error' => $e->getMessage()]);
            return [];
        }
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

        // IMPORTANT: Extract 'dur' value from endDetail or alarmValue
        // endDetail format: "dur:59 ; tt:300 ; cur:13.72 ; ..."
        $startDetail = $alarmData['alarmvalue'] ?? $alarmData['alarmValue'] ?? $alarmData['start_detail'] ?? '';
        $endDetail = $alarmData['endDetail'] ?? $alarmData['end_detail'] ?? '';
        $durationSeconds = 0;
        
        if (preg_match('/dur:\s*(\d+)/', $endDetail, $matches)) {
            // Extract duration from endDetail
            $durationSeconds = (int)$matches[1];  // 59 seconds
        } elseif (preg_match('/dur:\s*(\d+)/', $startDetail, $matches)) {
            // Extract duration from startDetail (alarmvalue)
            $durationSeconds = (int)$matches[1];
        } else {
            // Fallback: use alarmTimeLength if dur not found in endDetail or startDetail
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
            'start_detail' => $alarmData['alarmvalue'] ?? $alarmData['alarmValue'] ?? $alarmData['start_detail'] ?? null,
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
