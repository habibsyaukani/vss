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
            $hours = (int)$this->option('hours') ?: 24;
            
            // Define time range: last N hours
            $dayStart = now()->subHours($hours)->startOfDay();
            $dayEnd = now()->endOfDay();

            $this->info("🔄 Real-time pull (last {$hours} hours)");
            $this->info("   Range: {$dayStart->format('Y-m-d H:i')} → {$dayEnd->format('Y-m-d H:i')}");

            // Fetch data
            $service = new \App\Services\HowenAlarmService();
            $allAlarms = [];
            
            // Fetch pages in parallel (5 concurrent)
            try {
                $allAlarms = $service->fetchAlarmsParallel(
                    1,
                    20, // Fewer pages needed for real-time (only last 24 hours)
                    200,
                    $dayStart->toDateTimeString(),
                    $dayEnd->toDateTimeString(),
                    5
                );
            } catch (\Exception $e) {
                $this->warn("⚠️ Parallel fetch failed, trying sequential: {$e->getMessage()}");
                
                // Fallback to sequential
                for ($page = 1; $page <= 20; $page++) {
                    $alarms = $service->fetchAlarms(
                        $page,
                        200,
                        $dayStart->toDateTimeString(),
                        $dayEnd->toDateTimeString()
                    );
                    
                    if (empty($alarms)) break;
                    $allAlarms = array_merge($allAlarms, $alarms);
                    usleep(150000); // 150ms delay
                }
            }

            if (empty($allAlarms)) {
                $this->info("   ℹ️ No new alarms in last {$hours} hours");
                SystemSetting::set('last_realtime_pull', now()->toDateTimeString());
                return 0;
            }

            $this->info("   ✅ Fetched " . count($allAlarms) . " records");

            // Store to alarm_raw
            $inserted = 0;
            foreach ($allAlarms as $alarm) {
                $deviceId = $alarm['deviceguid'] ?? $alarm['device_id'] ?? null;
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
                ProcessIdleAlarmJob::dispatch();
                $this->info("   ⚡ Processing idle alarms...");
            }

            // Update last pull time
            SystemSetting::set('last_realtime_pull', now()->toDateTimeString());

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
        
        return [
            'guid' => $alarm['guid'] ?? uniqid(),
            'device_id' => $alarm['deviceguid'] ?? $alarm['device_id'] ?? null,
            'device_name' => $alarm['deviceName'] ?? $alarm['device_name'] ?? null,
            'alarm_type' => $alarm['alarmtype'] ?? $alarm['alarm_type'] ?? null,
            'alarm_value' => $alarmValue,
            'alarm_state' => $alarmState,
            'start_time' => $alarm['createtime'] ?? $alarm['start_time'] ?? null,
            'end_time' => $alarm['endTime'] ?? $alarm['end_time'] ?? null,
            'start_gps' => $alarm['alarmGps'] ?? $alarm['start_gps'] ?? null,
            'end_gps' => $alarm['endGps'] ?? $alarm['end_gps'] ?? null,
            'start_speed' => (float)($alarm['speed'] ?? $alarm['start_speed'] ?? 0),
            'end_speed' => (float)($alarm['endSpeed'] ?? $alarm['end_speed'] ?? 0),
            'report_time' => $alarm['reportTime'] ?? $alarm['report_time'] ?? null,
            // ✅ Duration dari dur di alarmvalue (start_detail)
            'duration_seconds' => $duration,
            'start_detail' => $alarmValue,
            'end_detail' => $endDetail ?: null,
            'raw_json' => json_encode($alarm),
        ];
    }
}
