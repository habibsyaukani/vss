<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TracksolidTrackService
{
    private TracksolidApiService $apiService;

    public function __construct(TracksolidApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Pull GPS tracks from Tracksolid API and sync to gps_tracks_raw
     */
    public function syncTracks(string $imei, string $beginTime, string $endTime): array
    {
        $stats = [
            'total_fetched' => 0,
            'total_inserted' => 0,
            'errors' => []
        ];

        Log::info("[Tracksolid Track Sync] Fetching tracks for IMEI: {$imei} from {$beginTime} to {$endTime}");

        // Call the API
        $response = $this->apiService->callApi('jimi.device.track.list', [
            'imei' => $imei,
            'begin_time' => $beginTime,
            'end_time' => $endTime,
        ]);

        if (!$response['success']) {
            $stats['errors'][] = $response['message'];
            return $stats;
        }

        $tracks = $response['result'] ?? [];

        if (empty($tracks)) {
            return $stats;
        }

        $stats['total_fetched'] = count($tracks);
        $device = Device::where('imei', $imei)->orWhere('device_id', $imei)->first();
        $deviceName = $device ? $device->device_name : $imei;

        $insertData = [];
        
        foreach ($tracks as $track) {
            try {
                $gpsTimeRaw = $track['gpsTime'] ?? null;
                if (!$gpsTimeRaw) continue;

                // Tracksolid returns track list time in UTC. We need to convert it to WITA.
                $gpsTime = Carbon::createFromFormat('Y-m-d H:i:s', $gpsTimeRaw, 'UTC')->setTimezone('Asia/Makassar')->format('Y-m-d H:i:s');

                // Prepare data for batch insert
                $insertData[] = [
                    'device_id'        => $imei,
                    'device_name'      => $deviceName,
                    'guid'             => Str::uuid()->toString(),
                    'latitude'         => $track['lat'] ?? null,
                    'longitude'        => $track['lng'] ?? null,
                    'speed'            => isset($track['gpsSpeed']) ? (int) $track['gpsSpeed'] : 0,
                    'direction'        => isset($track['direction']) ? (int) $track['direction'] : 0,
                    'satellites'       => isset($track['satellite']) ? (int) $track['satellite'] : 0,
                    'acc_state'        => (isset($track['accStatus']) && $track['accStatus'] === 'ON') ? 1 : 0,
                    'gps_time'         => $gpsTime,
                    'report_time'      => $gpsTime, // Fallback to gps_time since report_time might not be in track list
                    'created_at'       => Carbon::now(),
                    'updated_at'       => Carbon::now(),
                ];
                
                // Chunk insert to avoid memory issues if track list is huge
                if (count($insertData) >= 500) {
                    $inserted = DB::table('gps_tracks_raw')->insertOrIgnore($insertData);
                    $stats['total_inserted'] += $inserted;
                    $insertData = [];
                }

            } catch (\Exception $e) {
                Log::error("[Tracksolid Track Sync] Error formatting track for IMEI {$imei}: " . $e->getMessage());
                $stats['errors'][] = "IMEI {$imei}: " . $e->getMessage();
            }
        }

        if (count($insertData) > 0) {
            try {
                $inserted = DB::table('gps_tracks_raw')->insertOrIgnore($insertData);
                $stats['total_inserted'] += $inserted;
            } catch (\Exception $e) {
                Log::error("[Tracksolid Track Sync] Error saving batch tracks for IMEI {$imei}: " . $e->getMessage());
                $stats['errors'][] = "IMEI {$imei}: " . $e->getMessage();
            }
        }

        return $stats;
    }
}
