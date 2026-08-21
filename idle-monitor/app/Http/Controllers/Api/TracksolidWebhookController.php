<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\IdleAlarm;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TracksolidWebhookController extends Controller
{
    /**
     * Handle incoming push notifications from Tracksolid
     */
    public function handlePush(Request $request)
    {
        // Tracksolid sends msgType and data
        $msgType = $request->input('msgType');
        $dataStr = $request->input('data');
        
        Log::info('[Tracksolid Webhook] Received payload', [
            'msgType' => $msgType,
            'data' => $dataStr
        ]);

        if ($msgType === 'jimi.push.device.alarm') {
            $data = is_string($dataStr) ? json_decode($dataStr, true) : $dataStr;

            if ($data && isset($data['alarmType'])) {
                // stayAlertOn = Idling, stayAlert = Parking
                if ($data['alarmType'] === 'stayAlertOn' || $data['alarmType'] === 'stayAlert') {
                    $this->processIdleAlarm($data);
                }
            }
        }

        // Always return success so Tracksolid knows we received it
        return response()->json([
            'code' => 0,
            'message' => 'success'
        ]);
    }

    private function processIdleAlarm(array $data)
    {
        try {
            // Alarm time is usually provided in local or UTC. Assume UTC if it matches Tracksolid standard
            $alarmTimeUtc = $data['alarmTime'] ?? now();
            // Since alarmTime from push might be string like "2026-08-19 12:00:00", we assume UTC based on Tracksolid standard
            // and convert it to Asia/Makassar
            $startingTime = Carbon::parse($alarmTimeUtc, 'UTC')->setTimezone('Asia/Makassar')->format('Y-m-d H:i:s');
            
            $lat = $data['lat'] ?? null;
            $lng = $data['lng'] ?? null;
            $location = ($lng && $lat) ? "{$lng},{$lat}" : null;
            
            $imei = $data['imei'] ?? 'Unknown';
            $deviceName = $data['deviceName'] ?? 'Unknown';

            // Check if this exact alarm already exists (to prevent duplicates)
            $exists = IdleAlarm::where('device_id', $imei)
                ->where('starting_time', $startingTime)
                ->exists();

            if (!$exists) {
                IdleAlarm::create([
                    'guid'              => Str::uuid(),
                    'device_id'         => $imei,
                    'device_name'       => $deviceName,
                    'alarm_type'        => 'Idle',
                    'alarm_status'      => 'on',
                    'starting_time'     => $startingTime,
                    'ending_time'       => null,
                    'starting_location' => $location,
                    'ending_location'   => null,
                    'start_detail'      => $data['alarmName'] ?? 'Push Alarm',
                    'latitude_start'    => $lat,
                    'longitude_start'   => $lng,
                    'report_time'       => now('Asia/Makassar')->format('Y-m-d H:i:s'),
                    'start_speed'       => null,
                    'end_speed'         => null,
                    'duration_seconds'  => 0, // Real-time means just started, 0 seconds so far
                ]);
                Log::info("[Tracksolid Webhook] Inserted new Real-time Idling Alarm for IMEI {$imei}");
            } else {
                Log::info("[Tracksolid Webhook] Duplicate alarm ignored for IMEI {$imei}");
            }
        } catch (\Exception $e) {
            Log::error("[Tracksolid Webhook] Error processing alarm: " . $e->getMessage());
        }
    }
}
