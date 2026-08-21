<?php

namespace App\Services;

use App\Models\GpsTrackRaw;
use App\Models\GpsTrack;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TracksolidSyncService
{
    private TracksolidApiService $apiService;

    public function __construct(TracksolidApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Pull GPS tracks for a specific IMEI within a time range
     * 
     * @param string $imei
     * @param string $beginTime Format: Y-m-d H:i:s (Local App Time)
     * @param string $endTime Format: Y-m-d H:i:s (Local App Time)
     */
    public function syncDevice(string $imei, string $beginTime, string $endTime): array
    {
        $stats = [
            'total_fetched' => 0,
            'total_saved'   => 0,
            'errors'        => [],
        ];

        // Convert Local App Time (Makassar) to UTC since Tracksolid API expects UTC
        $appTz = config('app.timezone', 'Asia/Makassar');
        $beginTimeUtc = Carbon::parse($beginTime, $appTz)->setTimezone('UTC')->format('Y-m-d H:i:s');
        $endTimeUtc   = Carbon::parse($endTime, $appTz)->setTimezone('UTC')->format('Y-m-d H:i:s');

        Log::info("[Tracksolid Sync] Requesting IMEI {$imei} from {$beginTimeUtc} to {$endTimeUtc} (UTC)");

        $response = $this->apiService->callApi('jimi.device.track.list', [
            'imei' => $imei,
            'begin_time' => $beginTimeUtc,
            'end_time' => $endTimeUtc
        ]);

        if (!$response['success']) {
            $stats['errors'][] = $response['message'];
            return $stats;
        }

        // Tracksolid's jimi.device.track.list returns the array of items inside result.data
        $records = $response['result']['data'] ?? [];

        if (empty($records)) {
            Log::info("[Tracksolid Sync] Device {$imei}: No data found.");
            return $stats;
        }

        $stats['total_fetched'] = count($records);
        $stats['total_saved'] = $this->saveRecords($records, $imei);

        return $stats;
    }

    /**
     * Map Tracksolid data to GpsTrackRaw and GpsTrack, then insert to DB
     */
    private function saveRecords(array $records, string $imei): int
    {
        if (empty($records)) return 0;

        DB::connection()->disableQueryLog();

        $now = now()->toDateTimeString();
        $rawRows = [];
        $validRecords = [];

        foreach ($records as $item) {
            $gpsTime = $item['gpsTime'] ?? null;
            if (!$gpsTime) continue;

            // Filter speed > 0 for standard tracking
            $speed = isset($item['gpsSpeed']) ? (int) $item['gpsSpeed'] : 0;
            if ($speed <= 0) continue;

            // Generate a fake GUID to enforce uniqueness
            $guid = md5($imei . $gpsTime);
            $validRecords[$guid] = $item;

            $parsedTime = $this->parseTime($gpsTime);

            $rawRows[] = [
                'device_id'    => $imei, 
                'device_name'  => $imei, // Can be mapped to device names later
                'guid'         => $guid,
                'latitude'     => $item['lat'] ?? null,
                'longitude'    => $item['lng'] ?? null,
                'speed'        => $speed,
                'direction'    => isset($item['direction']) ? (int) $item['direction'] : null,
                'satellites'   => isset($item['satellite']) ? (int) $item['satellite'] : null,
                'gps_time'     => $parsedTime ? $parsedTime->toDateTimeString() : null,
                'report_time'  => $parsedTime ? $parsedTime->toDateTimeString() : null,
                'acc_state'    => (isset($item['accStatus']) && $item['accStatus'] === 'ON') ? 1 : 0,
                'net_type'     => isset($item['posType']) ? (int) $item['posType'] : null,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }

        if (empty($rawRows)) return 0;

        // 1. BULK insertOrIgnore to gps_tracks_raw
        foreach (array_chunk($rawRows, 300) as $chunk) {
            try {
                GpsTrackRaw::insertOrIgnore($chunk);
            } catch (\Exception $e) {
                Log::error("[Tracksolid Sync] insertOrIgnore Raw failed: " . $e->getMessage());
            }
        }

        // 2. Fetch the assigned IDs from database
        $allGuids    = array_keys($validRecords);
        $rawIdByGuid = GpsTrackRaw::whereIn('guid', $allGuids)->pluck('id', 'guid')->toArray();

        // 3. Find which ones are missing in gps_tracks
        $existingTrackRawIds = GpsTrack::whereIn('raw_id', array_values($rawIdByGuid))
            ->pluck('raw_id')
            ->flip()
            ->toArray();

        $newTracks = [];
        foreach ($rawIdByGuid as $guid => $rawId) {
            if (isset($existingTrackRawIds[$rawId])) continue; // Already mapped

            $item = $validRecords[$guid];
            $parsedTime = $this->parseTime($item['gpsTime'] ?? null);

            $newTracks[] = [
                'raw_id'       => $rawId,
                'device_id'    => $imei,
                'device_name'  => $imei,
                'latitude'     => $item['lat'] ?? null,
                'longitude'    => $item['lng'] ?? null,
                'speed'        => isset($item['gpsSpeed']) ? (int) $item['gpsSpeed'] : 0,
                'direction'    => isset($item['direction']) ? (int) $item['direction'] : null,
                'satellites'   => isset($item['satellite']) ? (int) $item['satellite'] : null,
                'gps_time'     => $parsedTime ? $parsedTime->toDateTimeString() : null,
                'report_time'  => $parsedTime ? $parsedTime->toDateTimeString() : null,
                'is_acc_on'    => (isset($item['accStatus']) && $item['accStatus'] === 'ON'),
                'is_overspeed' => false, // Can be mapped later if needed
                'is_emergency' => false,
                'is_recording' => false,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }

        // 4. BULK insertOrIgnore to gps_tracks
        if (!empty($newTracks)) {
            foreach (array_chunk($newTracks, 300) as $chunk) {
                try {
                    GpsTrack::insertOrIgnore($chunk);
                } catch (\Exception $e) {
                    Log::error("[Tracksolid Sync] insertOrIgnore Track failed: " . $e->getMessage());
                }
            }
        }

        return count($validRecords);
    }

    /**
     * Tracksolid API returns UTC time by default.
     * We need to convert it to application's local timezone.
     */
    private function parseTime(?string $value): ?Carbon
    {
        if (empty($value)) return null;
        try {
            return Carbon::parse($value, 'UTC')->setTimezone(config('app.timezone', 'Asia/Makassar'));
        } catch (\Exception $e) {
            return null;
        }
    }
}
