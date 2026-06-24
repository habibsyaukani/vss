<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessIdleAlarmJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;  // Increased from 300 to 600 seconds (10 minutes)

    /**
     * Execute the job - Process idle alarms from alarm_raw → idle_alarms
     * 
     * Idle Alarm Filter (berdasarkan data nyata dari Howen API):
     * 1. alarmState = 0 (Alarm End - Idle sudah selesai, kendaraan bergerak lagi)
     * 2. alarmType = 32 (Idle Alarm Type - dikonfirmasi dari data alarm_raw)
     * 3. speed = 0 (Mulai dari diam)
     * 4. endSpeed > 0 (Berakhir saat bergerak)
     * 5. duration > 0 detik (Ada durasi yang jelas)
     * 
     * CATATAN: alarmState dari Howen API:
     *   0 = ALARM_END (idle SELESAI - kendaraan bergerak kembali) ✅ PROSES INI
     *   1 = ALARMING  (idle BERLANGSUNG - kendaraan masih diam) ❌ SKIP
     */
    public function handle(): void
    {
        $processLog = \App\Models\ImportLog::create([
            'job_name' => 'ProcessIdleAlarmJob',
            'started_at' => now(),
            'total_record' => 0,
            'status' => 'running',
        ]);

        try {
            \Illuminate\Support\Facades\Log::info('ProcessIdleAlarmJob started');

            $processed = 0;
            $skipped = 0;

            // ✅ OPTIMASI: Filter di level database agar tidak meload seluruh data ke RAM
            // Hanya proses tipe 32 (Idle) dan state 0 (Alarm End) yang belum ada di idle_alarms
            \App\Models\AlarmRaw::where('alarm_type', 32)
                ->where('alarm_state', 0)
                ->whereNotExists(function ($query) {
                    $query->select(\Illuminate\Support\Facades\DB::raw(1))
                          ->from('idle_alarms')
                          ->whereRaw('idle_alarms.guid = alarm_raw.guid');
                })
                ->orderBy('id', 'asc')
                ->chunk(1000, function ($alarms) use (&$processed, &$skipped, $processLog) {
                    \Illuminate\Support\Facades\Log::info("Processing chunk of NEW alarms", ['count' => $alarms->count()]);

                    foreach ($alarms as $alarmRaw) {
                        try {
                    // Extract fields
                    $startSpeed = (float)($alarmRaw->start_speed ?? 0);
                    $endSpeed = (float)($alarmRaw->end_speed ?? 0);
                    $alarmType = (int)($alarmRaw->alarm_type ?? 0);
                    $alarmState = (int)($alarmRaw->alarm_state ?? 0);
                    
                    // Calculate duration with correct priority based on Howen logic:
                    // 1. If start_detail has dur > 0: USE start_detail
                    // 2. If start_detail has dur:0 or empty: USE end_detail
                    // 3. If both empty: USE alarmTimeLength
                    $durationFromStart = 0;
                    if (!empty($alarmRaw->alarm_value) && preg_match('/dur:(\d+)/', $alarmRaw->alarm_value, $m)) {
                        $durationFromStart = (int)$m[1];
                    }

                    $durationFromEnd = 0;
                    if (!empty($alarmRaw->end_detail) && preg_match('/dur:(\d+)/', $alarmRaw->end_detail, $m)) {
                        $durationFromEnd = (int)$m[1];
                    }

                    $alarmTimeLength = (int)($alarmRaw->duration_seconds ?? 0);
                    
                    // Priority: start_detail (if > 0) > endDetail > alarmTimeLength
                    $durationSeconds = $durationFromStart > 0 ? $durationFromStart : 
                                      ($durationFromEnd > 0 ? $durationFromEnd : $alarmTimeLength);
                    
                    // Fallback to time diff if all extraction methods fail
                    if ($durationSeconds <= 0 && !empty($alarmRaw->start_time) && !empty($alarmRaw->end_time)) {
                        $startTime = \Carbon\Carbon::parse($alarmRaw->start_time);
                        $endTime = \Carbon\Carbon::parse($alarmRaw->end_time);
                        $durationSeconds = $endTime->diffInSeconds($startTime);
                    }
                    
                    // FILTER IDLE ALARM:
                    // 1. alarmType = 32 (Idle Alarm Code)
                    // 2. alarmState = 0 (Alarm End)
                    // 3. duration > 0 (ada durasi valid)
                    // 4. end_time exists
                    
                    $isIdleAlarm = (
                        $alarmType == 32 &&                     // Idle Alarm Type
                        $alarmState == 0 &&                     // Alarm End
                        $durationSeconds > 0 &&                 // Ada durasi valid
                        !empty($alarmRaw->end_time) &&          // End time exists
                        $endSpeed > 0                           // Tidak boleh 0 km/h (sesuai request)
                    );
                    
                    if (!$isIdleAlarm) {
                        $skipped++;
                        \Illuminate\Support\Facades\Log::debug("Skipped: {$alarmRaw->guid}", [
                            'type' => $alarmType,
                            'state' => $alarmState,
                            'start_speed' => $startSpeed,
                            'end_speed' => $endSpeed,
                            'duration_sec' => $durationSeconds,
                        ]);
                        continue;
                    }
                    
                    $durationMinutes = ceil($durationSeconds / 60);
                    
                    // MAP ALARM_STATE: 0 = ALARM_END (idle selesai)
                    $alarmStatus = 'ALARM_END';
                    
                    \Illuminate\Support\Facades\Log::debug("Processing idle alarm: {$alarmRaw->guid}", [
                        'type' => $alarmType,
                        'state' => $alarmState,
                        'speed' => "{$startSpeed} → {$endSpeed}",
                        'duration' => "{$durationMinutes}min",
                    ]);
                    
                    // Parse GPS coordinates (format dari Howen: longitude,latitude)
                    $startLat = null;
                    $startLong = null;
                    $endLat = null;
                    $endLong = null;

                    if ($alarmRaw->start_gps && strpos($alarmRaw->start_gps, ',') !== false) {
                        [$startLong, $startLat] = array_map('trim', explode(',', $alarmRaw->start_gps));
                        $startLat = (float)$startLat;
                        $startLong = (float)$startLong;
                    }

                    if ($alarmRaw->end_gps && strpos($alarmRaw->end_gps, ',') !== false) {
                        [$endLong, $endLat] = array_map('trim', explode(',', $alarmRaw->end_gps));
                        $endLat = (float)$endLat;
                        $endLong = (float)$endLong;
                    }

                    // Get serial_no from devices table
                    $device = \App\Models\Device::where('device_id', $alarmRaw->device_id)->first();
                    $serialNo = $device ? $device->serial_no : null;

                    // ✅ Use start_detail from alarm_raw directly (already mapped from alarmvalue)
                    // No need to create synthetic dur:0 - use actual technical data
                    $startDetail = $alarmRaw->start_detail ?: $alarmRaw->alarm_value;
                    $endDetail = $alarmRaw->end_detail;
                    
                    // Data untuk disimpan ke idle_alarms
                    $idleData = [
                        'serial_no'          => $serialNo,
                        'device_id'          => $alarmRaw->device_id,
                        'device_name'        => $alarmRaw->device_name,
                        'alarm_type'         => 'Idle',
                        'alarm_status'       => $alarmStatus,
                        'starting_time'      => $alarmRaw->start_time,
                        'starting_location'  => $alarmRaw->start_gps,
                        'ending_time'        => $alarmRaw->end_time,
                        'ending_location'    => $alarmRaw->end_gps,
                        'start_detail'       => $startDetail,  // ✅ Use actual technical data from alarm_raw
                        'end_detail'         => $endDetail,     // ✅ End technical data
                        'start_speed'        => $startSpeed,
                        'end_speed'          => $endSpeed,
                        'report_time'        => $alarmRaw->report_time,
                        'duration_seconds'   => $durationSeconds,
                        'duration_minutes'   => $durationMinutes,
                        'latitude_start'     => $startLat,
                        'longitude_start'    => $startLong,
                        'latitude_end'       => $endLat,
                        'longitude_end'      => $endLong,
                    ];

                    // Tambah alarm_state jika kolom sudah ada di tabel
                    if (\Illuminate\Support\Facades\Schema::hasColumn('idle_alarms', 'alarm_state')) {
                        $idleData['alarm_state'] = $alarmState;
                    }

                    // Create or update idle_alarm (only valid ones)
                    \App\Models\IdleAlarm::updateOrCreate(
                        ['guid' => $alarmRaw->guid],
                        $idleData
                    );

                    $processed++;
                    
                    // Update processLog every 100 records to show progress
                    if ($processed % 100 === 0) {
                        $processLog->update([
                            'total_record' => $processed,
                            'updated_at' => now(),
                        ]);
                        \Illuminate\Support\Facades\Log::info("ProcessIdleAlarmJob progress", [
                            'processed' => $processed,
                            'skipped' => $skipped,
                        ]);
                    }

                } catch (\Exception $e) {
                    $skipped++;
                    \Illuminate\Support\Facades\Log::error("Failed to process: {$alarmRaw->guid}", [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        });

            // ✅ AUTO-UPDATE: Update device_id yang masih NULL di devices table
            $this->autoUpdateDeviceIds();

        $processLog->update([
                'finished_at' => now(),
                'status' => 'completed',
                'total_record' => $processed,
                'message' => "Processed {$processed} idle alarms (Type 32, State 0), skipped {$skipped}",
            ]);

            \Illuminate\Support\Facades\Log::info("ProcessIdleAlarmJob completed", [
                'processed' => $processed,
                'skipped' => $skipped,
            ]);

        } catch (\Exception $e) {
            $processLog->update([
                'finished_at' => now(),
                'status' => 'failed',
                'message' => $e->getMessage(),
            ]);

            \Illuminate\Support\Facades\Log::error('ProcessIdleAlarmJob failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Auto-update device_id yang masih NULL di devices table
     * Ambil dari data idle_alarms yang baru saja diproses
     */
    protected function autoUpdateDeviceIds(): void
    {
        try {
            \Illuminate\Support\Facades\Log::info('Auto-updating NULL device_ids from idle_alarms...');

            // Get devices yang masih NULL device_id
            $devicesWithNullId = \App\Models\Device::whereNull('device_id')->pluck('device_name')->toArray();
            
            if (empty($devicesWithNullId)) {
                \Illuminate\Support\Facades\Log::info('No devices with NULL device_id, skipping auto-update');
                return;
            }

            \Illuminate\Support\Facades\Log::info('Found devices with NULL device_id', ['count' => count($devicesWithNullId)]);

            // Get mapping dari idle_alarms untuk devices yang NULL
            $mappings = \Illuminate\Support\Facades\DB::table('idle_alarms')
                ->select('device_id', 'device_name')
                ->whereIn('device_name', $devicesWithNullId)
                ->whereNotNull('device_id')
                ->distinct()
                ->get();

            $updated = 0;
            foreach ($mappings as $mapping) {
                $result = \App\Models\Device::where('device_name', $mapping->device_name)
                    ->whereNull('device_id')
                    ->update([
                        'device_id' => $mapping->device_id,
                        'updated_at' => now()
                    ]);
                
                if ($result > 0) {
                    $updated++;
                    \Illuminate\Support\Facades\Log::info("Auto-updated device_id", [
                        'device_name' => $mapping->device_name,
                        'device_id' => $mapping->device_id
                    ]);
                }
            }

            \Illuminate\Support\Facades\Log::info('Auto-update device_ids completed', ['updated' => $updated]);

        } catch (\Exception $e) {
            // Don't throw, just log - tidak boleh mengganggu proses utama
            \Illuminate\Support\Facades\Log::warning('Auto-update device_ids failed (non-critical)', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
