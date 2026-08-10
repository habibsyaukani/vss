<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessIdleAlarmJob;
use App\Models\SystemSetting;
use App\Models\AlarmRaw;
use App\Models\IdleAlarm;
use Carbon\Carbon;

class PullIdleAlarmsRealtimeCommand extends Command
{
    protected $signature = 'howen:pull-alarms-realtime 
                            {--hours=24 : Look back hours (default: last 24 hours = 2 days)}';
    
    protected $description = 'Pull real-time idle alarm data (last 24-48 hours) for always-fresh data';

    public function handle()
    {
        try {
            $hours = (int)$this->option('hours') ?: 2;
            
            // ✅ FIX: Use exact N hours lookback, NOT startOfDay()
            // startOfDay() was causing re-import of all day's data every 3 minutes
            // Now we use precise timestamp: last N hours from now
            $beginTime = now()->subHours($hours);
            $endTime = now();

            $this->info("🔄 Real-time pull (last {$hours} hours)");
            $this->info("   Range: {$beginTime->format('Y-m-d H:i')} → {$endTime->format('Y-m-d H:i')}");

            // Fetch data secara sequential dengan delay 1 detik per halaman (anti rate-limit)
            $service = new \App\Services\HowenAlarmService();

            $this->info("   📡 Fetching pages 1–10 (sequential, 1s delay/page)...");
            $allAlarms = $service->fetchAlarmsParallel(
                1,
                10, // Max 10 halaman untuk last 2 jam (sudah lebih dari cukup)
                200,
                $beginTime->toDateTimeString(),
                $endTime->toDateTimeString()
            );

            if (empty($allAlarms)) {
                $this->info("   ℹ️ No new alarms in last {$hours} hours");
                SystemSetting::set('last_realtime_pull', now()->toDateTimeString());
                // ✅ Also update last_alarm_sync watermark even if empty
                SystemSetting::set('last_alarm_sync', $endTime->toDateTimeString());
                return 0;
            }

            $this->info("   ✅ Fetched " . count($allAlarms) . " records");

            // Store to alarm_raw
            $inserted = 0;
            foreach ($allAlarms as $alarm) {
                $deviceId = $alarm['deviceguid'] ?? $alarm['deviceID'] ?? $alarm['deviceId'] ?? $alarm['device_id'] ?? null;
                if (!$deviceId) continue;

                AlarmRaw::updateOrCreate(
                    ['guid' => $alarm['guid'] ?? uniqid()],
                    $this->mapAlarmData($alarm)
                );
                $inserted++;
            }

            $this->info("   💾 Stored {$inserted} records to alarm_raw");

            // Process idle alarms in real-time
            if ($inserted > 0) {
                // Gunakan antrean Queue Worker agar berjalan di latar belakang bergantian
                ProcessIdleAlarmJob::dispatch();
                $this->info("   ⚡ Processing idle alarms (dispatched to Queue)...");
            }

            // ✅ Update both timestamps so next pull doesn't re-import the same data
            SystemSetting::set('last_realtime_pull', now()->toDateTimeString());
            SystemSetting::set('last_alarm_sync', $endTime->toDateTimeString());

            $this->info("   ✅ Real-time pull completed\n");
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }
    }

    private function mapAlarmData($alarm)
    {
        $alarmState = $alarm['alarmState'] ?? $alarm['alarm_state'] ?? null;
        $alarmValue = $alarm['alarmvalue'] ?? $alarm['alarmValue'] ?? '';
        $endDetail = $alarm['endDetail'] ?? $alarm['end_detail'] ?? '';
        
        // ✅ CORRECT LOGIC: Parse dur dari alarmvalue (start_detail)
        // Howen menggunakan dur dari alarmvalue sebagai Duration yang ditampilkan
        // Setiap record adalah snapshot independent dengan dur yang sudah dihitung
        $durationFromStart = 0;
        if (preg_match('/dur[:\s]*(\d+)/', $alarmValue, $m)) {
            $durationFromStart = (int)$m[1];
        }
        
        // ✅ Fallback: Parse dur dari endDetail
        $durationFromEnd = 0;
        if (preg_match('/dur[:\s]*(\d+)/', $endDetail, $m)) {
            $durationFromEnd = (int)$m[1];
        }
        
        // ✅ Duration = dari alarmvalue (start_detail) seperti logic Howen
        // Prioritas: alarmvalue > endDetail > alarmTimeLength
        $duration = $durationFromStart > 0 
                    ? $durationFromStart 
                    : ($durationFromEnd > 0 
                        ? $durationFromEnd 
                        : (int)($alarm['alarmTimeLength'] ?? 0));
        
        $guid = $alarm['guid'] ?? $alarm['id'] ?? uniqid();
        $deviceId = $alarm['deviceguid'] ?? $alarm['deviceno'] ?? $alarm['deviceID'] ?? $alarm['device_id'] ?? null;
        $deviceName = $alarm['deviceName'] ?? $alarm['devicename'] ?? $alarm['device_name'] ?? null;
        $alarmType = $alarm['alarmtype'] ?? $alarm['alarmType'] ?? $alarm['alarm_type'] ?? null;
        $alarmState = $alarm['alarmState'] ?? $alarm['alarmstate'] ?? $alarm['alarm_state'] ?? 0;

        // ✅ Exact Howen API field names confirmed from live API test:
        // 'createtime' = alarm START time
        // 'endTime'    = alarm END time
        $startTime = $alarm['createtime'] ?? $alarm['startAlarmTimeStr'] ?? $alarm['start_time'] ?? null;
        $endTime   = $alarm['endTime']    ?? $alarm['endAlarmTimeStr']   ?? $alarm['end_time']   ?? null;
        $reportTime = $alarm['reportTime'] ?? $alarm['report_time'] ?? $startTime ?? now()->toDateTimeString();

        // If end_time is empty, calculate end_time from start_time + duration
        if (empty($endTime) && !empty($startTime) && $duration > 0) {
            $endTime = \Carbon\Carbon::parse($startTime)->addSeconds($duration)->toDateTimeString();
        }

        return [
            'guid'             => $guid,
            'device_id'        => $deviceId,
            'device_name'      => $deviceName,
            'alarm_type'       => $alarmType,
            'alarm_value'      => $alarmValue,
            'alarm_state'      => $alarmState,
            'start_time'       => $startTime,
            'end_time'         => $endTime,
            'start_gps'        => $alarm['alarmGps']  ?? $alarm['start_gps'] ?? null,
            'end_gps'          => $alarm['endGps']    ?? $alarm['end_gps']   ?? null,
            'start_speed'      => (float)($alarm['speed']    ?? $alarm['start_speed'] ?? 0),
            'end_speed'        => (float)($alarm['endSpeed'] ?? $alarm['end_speed']   ?? 0),
            'report_time'      => $reportTime,
            'duration_seconds' => $duration,
            'start_detail'     => $alarmValue,
            'end_detail'       => $endDetail ?: null,
            'raw_json'         => json_encode($alarm),
        ];
    }
}
