<?php

namespace App\Services;

use App\Models\GpsTrackRaw;
use App\Models\GpsTrack;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GpsTrackSyncService
{
    private string $baseUrl;
    private int $perPage;
    private int $delayMs;

    public function __construct()
    {
        $this->baseUrl = config('vss.base_url', 'http://vss.ptdigital.co.id');
        $this->perPage = config('vss.per_page', 200);
        $this->delayMs = config('vss.delay_between_pages_ms', 500);
    }

    // ----------------------------------------------------------------
    // PUBLIC ENTRY POINT
    // ----------------------------------------------------------------

    /**
     * Tarik semua data GPS satu device dalam rentang waktu tertentu.
     * Otomatis loop semua page sampai habis.
     *
     * @param  string  $token      Token dari VSS login
     * @param  string  $deviceId   Device ID
     * @param  string  $beginTime  Format: Y-m-d H:i:s
     * @param  string  $endTime    Format: Y-m-d H:i:s
     * @return array   ['total_fetched' => int, 'total_saved' => int, 'pages' => int]
     */
    public function syncDevice(
        string $token,
        string $deviceId,
        string $beginTime,
        string $endTime
    ): array {
        $stats = [
            'total_fetched' => 0,
            'total_saved'   => 0,
            'pages'         => 0,
            'errors'        => [],
        ];

        // --- Ambil page 1 dulu untuk tahu totalCount ---
        $firstPage = $this->fetchPage($token, $deviceId, $beginTime, $endTime, 1);

        if (! $firstPage['success']) {
            $stats['errors'][] = $firstPage['message'];
            return $stats;
        }

        $totalCount = $firstPage['data']['totalCount'] ?? 0;
        $totalPages = $firstPage['data']['totalNum']   ?? 1;

        Log::info("[GPS Sync] Device {$deviceId} | Total records: {$totalCount} | Pages: {$totalPages}");

        // --- Simpan page 1 ---
        $saved = $this->saveRecords($firstPage['data']['dataList'] ?? [], $deviceId);
        $stats['total_fetched'] += count($firstPage['data']['dataList'] ?? []);
        $stats['total_saved']   += $saved;
        $stats['pages']++;

        // --- Kalau cuma 1 halaman, selesai ---
        if ($totalPages <= 1) {
            return $stats;
        }

        // --- Sisa halaman ditarik PARALEL, per batch ---
        $remainingPages = range(2, $totalPages);
        $concurrency    = 3; // jumlah request paralel per batch dikurangi agar lebih aman dari rate limit

        foreach (array_chunk($remainingPages, $concurrency) as $pageBatch) {
            
            $responses = \Illuminate\Support\Facades\Http::pool(fn ($pool) =>
                collect($pageBatch)->map(fn ($page) =>
                    $pool->as("page-{$page}")
                        ->withOptions(['verify' => false]) // Disable SSL untuk dev
                        ->timeout(30)
                        ->post("{$this->baseUrl}/vss/track/getApiTrackList.action", [
                            'token'     => $token,
                            'deviceID'  => $deviceId,
                            'beginTime' => $beginTime,
                            'endTime'   => $endTime,
                            'pageNum'   => $page,
                            'pageCount' => $this->perPage,
                        ])
                )->toArray()
            );

            $allRecordsInBatch = [];

            foreach ($responses as $pageKey => $response) {
                $pageNumber = str_replace('page-', '', $pageKey);

                if ($response instanceof \Illuminate\Http\Client\Response && $response->failed()) {
                    Log::warning("[GPS Sync] Page {$pageNumber} gagal: HTTP {$response->status()}");
                    $stats['errors'][] = "Page {$pageNumber}: HTTP {$response->status()}";
                    continue;
                }
                
                if ($response instanceof \Exception) {
                    Log::warning("[GPS Sync] Page {$pageNumber} gagal exception: {$response->getMessage()}");
                    $stats['errors'][] = "Page {$pageNumber}: {$response->getMessage()}";
                    continue;
                }

                $body = $response->json();

                if (($body['status'] ?? null) !== 10000) {
                    $msg = $body['msg'] ?? 'Unknown error';
                    Log::warning("[GPS Sync] Page {$pageNumber} gagal: {$msg}");
                    $stats['errors'][] = "Page {$pageNumber}: {$msg}";
                    continue;
                }

                $records = $body['data']['dataList'] ?? [];
                $allRecordsInBatch = array_merge($allRecordsInBatch, $records);
                
                $stats['total_fetched'] += count($records);
                $stats['pages']++;
            }

            // Gabungkan records dalam 1 batch besar, lalu simpan sekali
            if (!empty($allRecordsInBatch)) {
                $saved = $this->saveRecords($allRecordsInBatch, $deviceId);
                $stats['total_saved'] += $saved;
                Log::info("[GPS Sync] Batch pages " . implode(',', $pageBatch) . " selesai | Saved: {$saved}");
            }

            // Jeda antar BATCH (bukan antar page lagi)
            if ($this->delayMs > 0) {
                usleep($this->delayMs * 1000);
            }
        }

        return $stats;
    }

    /**
     * Pull GPS tracks for multiple devices concurrently (for fast real-time pulling)
     */
    public function syncMultipleDevicesFast(array $deviceIds, string $beginTime, string $endTime): array
    {
        $token = $this->authService->getToken();
        if (!$token) {
            Log::error("[GPS Sync Bulk] Gagal mendapatkan token VSS");
            return ['status' => 'error', 'message' => 'Token failed'];
        }

        $stats = [
            'total_devices' => count($deviceIds),
            'success_devices' => 0,
            'total_fetched' => 0,
            'total_saved'   => 0,
            'errors'        => [],
        ];

        $concurrency = 20; // 20 concurrent requests per batch
        $allRecords = [];

        $appTz = config('app.timezone', 'Asia/Makassar');
        $beginTimeWib = \Carbon\Carbon::parse($beginTime, $appTz)->setTimezone('Asia/Jakarta')->toDateTimeString();
        $endTimeWib   = \Carbon\Carbon::parse($endTime, $appTz)->setTimezone('Asia/Jakarta')->toDateTimeString();

        foreach (array_chunk($deviceIds, $concurrency) as $batchIndex => $deviceBatch) {
            
            $responses = \Illuminate\Support\Facades\Http::pool(function ($pool) use ($deviceBatch, $token, $beginTimeWib, $endTimeWib) {
                return collect($deviceBatch)->map(function ($deviceId) use ($pool, $token, $beginTimeWib, $endTimeWib) {
                    return $pool->as("device-{$deviceId}")
                        ->withOptions(['verify' => false])
                        ->timeout(15)
                        ->post("{$this->baseUrl}/vss/track/getApiTrackList.action", [
                            'token'     => $token,
                            'deviceID'  => $deviceId,
                            'beginTime' => $beginTimeWib,
                            'endTime'   => $endTimeWib,
                            'pageNum'   => 1,
                            'pageCount' => 20, // 20 records is enough for short polling
                        ]);
                });
            });

            foreach ($responses as $key => $response) {
                $deviceId = str_replace('device-', '', $key);

                if ($response instanceof \Illuminate\Http\Client\Response && $response->failed()) {
                    $stats['errors'][] = "Device {$deviceId}: HTTP {$response->status()}";
                    continue;
                }
                
                if ($response instanceof \Exception) {
                    $stats['errors'][] = "Device {$deviceId}: {$response->getMessage()}";
                    continue;
                }

                $body = $response->json();

                if (($body['status'] ?? null) !== 10000) {
                    $msg = $body['msg'] ?? 'Unknown error';
                    $stats['errors'][] = "Device {$deviceId}: {$msg}";
                    continue;
                }

                $stats['success_devices']++;
                $records = $body['data']['dataList'] ?? [];
                
                if (!empty($records)) {
                    $stats['total_fetched'] += count($records);
                    
                    // Attach deviceId just in case it's missing in raw data
                    foreach ($records as &$rec) {
                        $rec['_injected_device_id'] = $deviceId;
                    }
                    
                    $allRecords = array_merge($allRecords, $records);
                }
            }

            // Optional delay between large batches to avoid rate limit
            if ($this->delayMs > 0) {
                usleep($this->delayMs * 1000);
            }
        }

        // Save all collected records across this batch of devices
        if (!empty($allRecords)) {
            // saveRecords takes care of formatting, filtering speed > 0, and inserting Or Ignore
            $saved = $this->saveRecords($allRecords, null); // passing null as deviceId because records have it or we rely on GUID
            $stats['total_saved'] += $saved;
            Log::info("[GPS Sync Bulk] Completed bulk save | Fetched: {$stats['total_fetched']} | Saved: {$saved}");
        }

        return $stats;
    }

    // ----------------------------------------------------------------
    // FETCH SATU PAGE
    // ----------------------------------------------------------------

    /**
     * Ambil satu page data GPS dari VSS API.
     *
     * @return array ['success' => bool, 'data' => array|null, 'message' => string]
     */
    public function fetchPage(
        string $token,
        string $deviceId,
        string $beginTime,
        string $endTime,
        int    $page = 1
    ): array {
        try {
            $appTz = config('app.timezone', 'Asia/Makassar');
            $beginTimeWib = Carbon::parse($beginTime, $appTz)->setTimezone('Asia/Jakarta')->toDateTimeString();
            $endTimeWib   = Carbon::parse($endTime, $appTz)->setTimezone('Asia/Jakarta')->toDateTimeString();

            $response = Http::withOptions([
                'verify' => false, // Disable SSL verification for development
            ])->timeout(30)->post("{$this->baseUrl}/vss/track/getApiTrackList.action", [
                'token'     => $token,
                'deviceID'  => $deviceId,
                'beginTime' => $beginTimeWib,
                'endTime'   => $endTimeWib,
                'pageNum'   => $page,
                'pageCount' => $this->perPage,
            ]);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'data'    => null,
                    'message' => "HTTP {$response->status()}",
                ];
            }

            $body = $response->json();

            if (($body['status'] ?? 0) !== 10000) {
                return [
                    'success' => false,
                    'data'    => null,
                    'message' => $body['msg'] ?? 'VSS error status: ' . ($body['status'] ?? 'unknown'),
                ];
            }

            return [
                'success' => true,
                'data'    => $body['data'],
                'message' => 'ok',
            ];

        } catch (\Throwable $e) {
            Log::error("[GPS Sync] Exception fetchPage: {$e->getMessage()}");
            return [
                'success' => false,
                'data'    => null,
                'message' => $e->getMessage(),
            ];
        }
    }

    // ----------------------------------------------------------------
    // PREVIEW (tanpa simpan ke DB — untuk testing di controller)
    // ----------------------------------------------------------------

    /**
     * Preview data satu page tanpa menyimpan ke database.
     * Cocok untuk ditampilkan langsung ke frontend via API endpoint.
     */
    public function previewPage(
        string $token,
        string $deviceId,
        string $beginTime,
        string $endTime,
        int    $page = 1
    ): array {
        $result = $this->fetchPage($token, $deviceId, $beginTime, $endTime, $page);

        if (! $result['success']) {
            return $result;
        }

        $rawList  = $result['data']['dataList'] ?? [];
        $mapped   = array_map([$this, 'mapForDisplay'], $rawList);

        return [
            'success'     => true,
            'page'        => $page,
            'per_page'    => $this->perPage,
            'total'       => $result['data']['totalCount'] ?? 0,
            'total_pages' => $result['data']['totalNum']   ?? 1,
            'from'        => $result['data']['fromCount']  ?? 0,
            'to'          => $result['data']['toCount']    ?? 0,
            'data'        => $mapped,
        ];
    }

    // ----------------------------------------------------------------
    // SIMPAN RECORDS KE DB
    // ----------------------------------------------------------------

    private function saveRecords(array $records, ?string $deviceId): int
    {
        if (empty($records)) return 0;

        // ⚡ Matikan query log agar tidak menumpuk di memory saat proses besar
        \Illuminate\Support\Facades\DB::connection()->disableQueryLog();

        // ✅ FILTER: Skip data dengan speed = 0 km/h
        $records = array_filter($records, function ($item) {
            return isset($item['speed']) && (int)$item['speed'] > 0;
        });

        if (empty($records)) return 0;

        $validRecords = [];
        foreach ($records as $item) {
            $guid = $item['guid'] ?? null;
            if ($guid) {
                $validRecords[$guid] = $item;
            }
        }

        if (empty($validRecords)) return 0;

        $now = now()->toDateTimeString();

        // 1. Siapkan semua raw rows
        $rawRows = [];
        foreach ($validRecords as $guid => $item) {
            $rawMap = $this->mapToRaw($item, $deviceId);
            if (isset($rawMap['gps_time']) && $rawMap['gps_time'] instanceof Carbon) {
                $rawMap['gps_time'] = $rawMap['gps_time']->toDateTimeString();
            }
            if (isset($rawMap['report_time']) && $rawMap['report_time'] instanceof Carbon) {
                $rawMap['report_time'] = $rawMap['report_time']->toDateTimeString();
            }
            $rawMap['created_at'] = $now;
            $rawMap['updated_at'] = $now;
            $rawRows[] = $rawMap;
        }

        // 2. BULK insertOrIgnore Raw — MySQL otomatis skip jika guid sudah ada (unique index)
        //    Ini menghilangkan kebutuhan SELECT dedup sebelumnya
        foreach (array_chunk($rawRows, 300) as $chunk) {
            try {
                GpsTrackRaw::insertOrIgnore($chunk);
            } catch (\Throwable $e) {
                Log::error("[GPS Sync] insertOrIgnore Raw failed: " . $e->getMessage());
            }
        }

        // 3. Ambil ID untuk semua guid (termasuk yang baru + yang sudah ada tapi belum punya track)
        $allGuids    = array_keys($validRecords);
        $rawIdByGuid = GpsTrackRaw::whereIn('guid', $allGuids)->pluck('id', 'guid')->toArray();

        // 4. Cek track mana yang belum ada
        $existingTrackRawIds = GpsTrack::whereIn('raw_id', array_values($rawIdByGuid))
            ->pluck('raw_id')
            ->flip()
            ->toArray();

        $newTracks = [];
        foreach ($rawIdByGuid as $guid => $rawId) {
            if (isset($existingTrackRawIds[$rawId])) continue; // sudah ada, skip

            $item     = $validRecords[$guid];
            $trackMap = $this->mapToDisplay($item, $deviceId, $rawId);
            if (isset($trackMap['gps_time']) && $trackMap['gps_time'] instanceof Carbon) {
                $trackMap['gps_time'] = $trackMap['gps_time']->toDateTimeString();
            }
            if (isset($trackMap['report_time']) && $trackMap['report_time'] instanceof Carbon) {
                $trackMap['report_time'] = $trackMap['report_time']->toDateTimeString();
            }
            $trackMap['created_at'] = $now;
            $trackMap['updated_at'] = $now;
            $newTracks[] = $trackMap;
        }

        // 5. BULK insertOrIgnore Track
        if (!empty($newTracks)) {
            foreach (array_chunk($newTracks, 300) as $chunk) {
                try {
                    GpsTrack::insertOrIgnore($chunk);
                } catch (\Throwable $e) {
                    Log::error("[GPS Sync] insertOrIgnore Track failed: " . $e->getMessage());
                }
            }
        }

        return count($validRecords);
    }

    // ----------------------------------------------------------------
    // MAPPING: VSS response → gps_tracks_raw
    // ----------------------------------------------------------------

    private function mapToRaw(array $item, ?string $deviceId): array
    {
        return [
            'device_id'        => $deviceId ?? ($item['_injected_device_id'] ?? null),
            'device_name'      => $item['deviceName'] ?? null,
            'guid'             => $item['guid'] ?? null,
            'longitude'        => $item['longitude'] ?? null,
            'latitude'         => $item['latitude']  ?? null,
            'altitude'         => isset($item['altitude'])  ? (int) $item['altitude']  : null,
            'speed'            => isset($item['speed'])     ? (int) $item['speed']     : null,
            'direction'        => isset($item['direct'])    ? (int) $item['direct']    : null,
            'satellites'       => isset($item['satellites'])? (int) $item['satellites']: null,
            'precision'        => isset($item['precision']) ? (int) $item['precision'] : null,
            'mode'             => isset($item['mode'])      ? (int) $item['mode']      : null,
            'acc_state'        => isset($item['accState'])  ? (int) $item['accState']  : null,
            'record_state'     => isset($item['recordState'])     ? (int) $item['recordState']     : null,
            'video_mask_state' => isset($item['videoMaskState'])  ? (int) $item['videoMaskState']  : null,
            'video_lost_state' => isset($item['videoLostState'])  ? (int) $item['videoLostState']  : null,
            'io_state'         => isset($item['ioState'])   ? (int) $item['ioState']   : null,
            'urgency'          => isset($item['urgency'])   ? (int) $item['urgency']   : null,
            'over_speed'       => isset($item['overSpeed']) ? (int) $item['overSpeed'] : null,
            'low_speed'        => isset($item['lowSpeed'])  ? (int) $item['lowSpeed']  : null,
            'oil_volume'       => $item['oilVolume']  ?? null,
            'net_type'         => isset($item['netType'])     ? (int) $item['netType']     : null,
            'signal_value'     => isset($item['signalValue']) ? (int) $item['signalValue'] : null,
            'dev_voltage'      => $item['devVoltage'] ?? null,
            'bat_voltage'      => $item['batVoltage'] ?? null,
            'driver_card_id'   => $item['driverCardId'] ?? null,
            'driver_name'      => $item['driverName']  ?? null,
            'gps_time'         => $this->parseTime($item['createtime']  ?? null),
            'report_time'      => $this->parseTime($item['reportTime']  ?? null),
            'state_json'       => isset($item['stateJson'])
                                    ? (is_string($item['stateJson'])
                                        ? $item['stateJson']
                                        : json_encode($item['stateJson']))
                                    : null,
            'tempe_humidity'   => isset($item['tempeAndHumidity'])
                                    ? json_encode($item['tempeAndHumidity'])
                                    : null,
            'is_later'         => (int) ($item['isLater'] ?? 0),
        ];
    }

    // ----------------------------------------------------------------
    // MAPPING: VSS response → gps_tracks (display)
    // ----------------------------------------------------------------

    private function mapToDisplay(array $item, string $deviceId, int $rawId): array
    {
        return [
            'raw_id'             => $rawId,
            'device_id'          => $deviceId,
            'device_name'        => $item['deviceName'] ?? null,
            'longitude'          => $item['longitude']  ?? null,
            'latitude'           => $item['latitude']   ?? null,
            'altitude'           => isset($item['altitude'])   ? (int) $item['altitude']   : null,
            'speed'              => isset($item['speed'])      ? (int) $item['speed']      : null,
            'direction'          => isset($item['direct'])     ? (int) $item['direct']     : null,
            'satellites'         => isset($item['satellites']) ? (int) $item['satellites'] : null,
            'gps_time'           => $this->parseTime($item['createtime'] ?? null),
            'report_time'        => $this->parseTime($item['reportTime'] ?? null),
            'is_acc_on'          => ($item['accState']  ?? 0) == 1,
            'is_overspeed'       => ($item['overSpeed'] ?? 0) == 1,
            'is_emergency'       => ($item['urgency']   ?? 0) == 1,
            'is_recording'       => $this->hasRecording($item['recordState'] ?? 0),
            'net_type_label'     => $this->netTypeLabel($item['netType'] ?? null),
            'dev_voltage'        => $item['devVoltage'] ?? null,
            'io_state_label'     => $item['iostateFormatter']        ?? null,
            'input_output_status'=> $item['iostateFormatter']        ?? null,
            'driver_name'        => $item['driverName'] ?? null,
            'today_mileage'      => $this->extractMileage($item, 'today'),
            'total_mileage'      => $this->extractMileage($item, 'total'),
        ];
    }

    // ----------------------------------------------------------------
    // MAPPING: untuk preview (tidak masuk DB)
    // ----------------------------------------------------------------

    public function mapForDisplay(array $item): array
    {
        return [
            'device_id'     => $item['deviceguid']  ?? $item['deviceName'] ?? null,
            'device_name'   => $item['deviceName']  ?? null,
            'longitude'     => $item['longitude']   ?? null,
            'latitude'      => $item['latitude']    ?? null,
            'speed'         => isset($item['speed'])     ? (int) $item['speed']     : null,
            'altitude'      => isset($item['altitude'])  ? (int) $item['altitude']  : null,
            'direction'     => isset($item['direct'])    ? (int) $item['direct']    : null,
            'satellites'    => isset($item['satellites'])? (int) $item['satellites']: null,
            'gps_time'      => $item['createtime']  ?? null,
            'report_time'   => $item['reportTime']  ?? null,
            'acc_on'        => ($item['accState']   ?? 0) == 1,
            'overspeed'     => ($item['overSpeed']  ?? 0) == 1,
            'emergency'     => ($item['urgency']    ?? 0) == 1,
            'recording'     => $this->hasRecording($item['recordState'] ?? 0),
            'net_type'      => $this->netTypeLabel($item['netType'] ?? null),
            'voltage'       => $item['devVoltage']  ?? null,
            'driver_name'   => $item['driverName']  ?? null,
        ];
    }

    // ----------------------------------------------------------------
    // HELPER METHODS
    // ----------------------------------------------------------------

    private function parseTime(?string $value): ?Carbon
    {
        if (empty($value)) return null;
        
        try {
            // VSS / Howen API returns timestamps in Asia/Jakarta (WIB / UTC+7)
            // Convert to application timezone (Asia/Makassar / WITA / UTC+8)
            $parsed = Carbon::parse($value, 'Asia/Jakarta')->setTimezone(config('app.timezone', 'Asia/Makassar'));
            $now = now();
            
            // Fix invalid device clocks that send timestamps from the future
            if ($parsed->greaterThan($now)) {
                return $now;
            }
            
            return $parsed;
        } catch (\Throwable) {
            return null;
        }
    }

    private function hasRecording(int|string $recordState): bool
    {
        // recordState adalah bitmask; jika ada bit yang aktif = ada yang recording
        return ((int) $recordState) > 0;
    }

    private function netTypeLabel(int|string|null $type): ?string
    {
        return match ((int) $type) {
            1 => 'Ethernet',
            2 => 'WiFi',
            3 => '2G',
            4 => '3G',
            5 => '4G',
            6 => '5G',
            7 => 'WiFi+Mobile',
            8 => 'Cable+Mobile',
            default => null,
        };
    }

    private function extractMileage(array $item, string $type): ?float
    {
        // Coba dari stateJson dulu
        $stateJson = $item['stateJson'] ?? null;

        if ($stateJson && is_string($stateJson)) {
            $state = json_decode($stateJson, true);
            
            if ($type === 'today' && isset($state['mileage']['todayDay'])) {
                // VSS simpan dalam satuan 10 meter → convert ke km
                return round($state['mileage']['todayDay'] * 10 / 1000, 2);
            }
            
            if ($type === 'total' && isset($state['mileage']['total'])) {
                return round($state['mileage']['total'] * 10 / 1000, 2);
            }
        }

        return null;
    }
}
