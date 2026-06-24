<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ImportAlarmPageJob;
use App\Jobs\ProcessIdleAlarmJob;
use App\Models\SystemSetting;
use App\Models\ImportLog;
use App\Models\AlarmRaw;
use App\Models\IdleAlarm;
use Carbon\Carbon;
use GuzzleHttp\Pool;
use GuzzleHttp\Client;

class PullIdleAlarmsPerDayCommand extends Command
{
    protected $signature = 'howen:pull-alarms-per-day 
                            {--from= : Start date (YYYY-MM-DD). Default: from system_settings}
                            {--to= : End date (YYYY-MM-DD). Default: 2026-05-31}
                            {--max-days=10 : Maximum days to process per run}
                            {--parallel : Use parallel fetching}
                            {--concurrency=5 : Concurrency level for parallel}';
    
    protected $description = 'Pull idle alarm data per-day with per-page batching (optimized for backfill)';

    private $startDate;
    private $endDate;
    private $maxDaysPerRun;
    private $lastBackfillDate;

    public function handle()
    {
        $this->info('🔄 Starting PER-DAY backfill for Mei...');
        $this->newLine();

        try {
            // Get progress from system_settings
            $this->initializeProgress();
            
            // Calculate days to process
            $daysToProcess = $this->startDate->diffInDays($this->endDate) + 1;
            $daysThisRun = min($this->maxDaysPerRun, $daysToProcess);

            $this->info("📅 Backfill Progress:");
            $this->info("   Last completed: {$this->lastBackfillDate->format('Y-m-d')}");
            $this->info("   Start (this run): {$this->startDate->format('Y-m-d')}");
            $this->info("   End: {$this->endDate->format('Y-m-d')}");
            $this->info("   Days to process this run: {$daysThisRun}");
            $this->newLine();

            // Process per-day
            $currentDate = clone $this->startDate;
            $daysProcessed = 0;

            while ($currentDate->lte($this->endDate) && $daysProcessed < $daysThisRun) {
                $this->processDay($currentDate);
                $this->lastBackfillDate = clone $currentDate;
                $currentDate->addDay();
                $daysProcessed++;
            }

            // Update system_settings
            SystemSetting::set('last_backfill_date', $this->lastBackfillDate->format('Y-m-d'));
            
            // Check if Mei is complete
            if ($this->lastBackfillDate->format('Y-m-d') === '2026-05-31') {
                SystemSetting::set('backfill_completed_mei', 'true');
                $this->info("\n✅ MEI BACKFILL COMPLETED!");
                $this->showMeiSummary();
            }

            $this->newLine();
            $this->info('✅ Per-day backfill completed!');
            
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }
    }

    private function initializeProgress()
    {
        // Get from system_settings
        $lastBackfillDate = SystemSetting::get('last_backfill_date');
        
        if ($lastBackfillDate && $lastBackfillDate !== '2026-05-01') {
            // Resume from next day
            $this->lastBackfillDate = Carbon::createFromFormat('Y-m-d', $lastBackfillDate);
            $this->startDate = (clone $this->lastBackfillDate)->addDay()->startOfDay();
        } else {
            // Start from 01/05
            $this->lastBackfillDate = Carbon::createFromFormat('Y-m-d', '2026-05-01')->subDay();
            $this->startDate = Carbon::createFromFormat('Y-m-d', '2026-05-01')->startOfDay();
        }

        // Override with --from if provided
        if ($this->option('from')) {
            $this->startDate = Carbon::createFromFormat('Y-m-d', $this->option('from'))->startOfDay();
        }

        // Set end date
        if ($this->option('to')) {
            $this->endDate = Carbon::createFromFormat('Y-m-d', $this->option('to'))->endOfDay();
        } else {
            $this->endDate = Carbon::createFromFormat('Y-m-d', '2026-05-31')->endOfDay();
        }

        $this->maxDaysPerRun = (int)$this->option('max-days') ?: 10;
    }

    private function processDay(Carbon $date)
    {
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        $this->info("📅 Processing {$date->format('Y-m-d')}...");

        try {
            if ($this->option('parallel')) {
                $this->processDayParallel($dayStart, $dayEnd);
            } else {
                $this->processDaySequential($dayStart, $dayEnd);
            }

        } catch (\Exception $e) {
            $this->warn("⚠️ Error processing {$date->format('Y-m-d')}: {$e->getMessage()}");
        }

        $this->newLine();
    }

    private function processDaySequential(Carbon $dayStart, Carbon $dayEnd)
    {
        $pages = 50; // Max pages per day
        $bar = $this->output->createProgressBar($pages);
        $bar->start();

        for ($pageNum = 1; $pageNum <= $pages; $pageNum++) {
            try {
                $service = new \App\Services\HowenAlarmService();
                $alarms = $service->fetchAlarms(
                    $pageNum,
                    200,
                    $dayStart->toDateTimeString(),
                    $dayEnd->toDateTimeString()
                );

                if (empty($alarms)) {
                    break; // No more pages for this day
                }

                // Store to alarm_raw
                $inserted = 0;
                foreach ($alarms as $alarm) {
                    $deviceId = $alarm['deviceguid'] ?? $alarm['device_id'] ?? null;
                    if (!$deviceId) continue;

                    AlarmRaw::updateOrCreate(
                        ['guid' => $alarm['guid'] ?? uniqid()],
                        $this->mapAlarmData($alarm)
                    );
                    $inserted++;
                }

                if ($inserted > 0) {
                    // Dispatch real-time processing
                    ProcessIdleAlarmJob::dispatch();
                }

                $bar->advance();
                usleep(200000); // 200ms delay between pages

            } catch (\Exception $e) {
                $this->warn("⚠️ Error on page {$pageNum}: {$e->getMessage()}");
                $bar->advance();
                break;
            }
        }

        $bar->finish();
        $this->line('');
    }

    private function processDayParallel(Carbon $dayStart, Carbon $dayEnd)
    {
        $concurrency = min((int)$this->option('concurrency') ?: 5, 10);
        $maxPages = 50;

        $this->line("   ⚡ Using parallel fetching ({$concurrency} concurrent)");

        try {
            $service = new \App\Services\HowenAlarmService();
            $allAlarms = $service->fetchAlarmsParallel(
                1,
                $maxPages,
                200,
                $dayStart->toDateTimeString(),
                $dayEnd->toDateTimeString(),
                $concurrency
            );

            if (empty($allAlarms)) {
                $this->line("   ℹ️ No alarms found for this day");
                return;
            }

            $this->line("   ✅ Fetched " . count($allAlarms) . " records");

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

            $this->line("   💾 Stored {$inserted} records to alarm_raw");

            // Dispatch real-time processing
            if ($inserted > 0) {
                ProcessIdleAlarmJob::dispatch();
                $this->line("   ⚡ Triggered idle alarm processing");
            }

        } catch (\Exception $e) {
            $this->warn("   ❌ Parallel error: {$e->getMessage()}");
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

    private function showMeiSummary()
    {
        $this->line('');
        $this->info('╔═══════════════════════════════════════╗');
        $this->info('║   MEI BACKFILL COMPLETION SUMMARY    ║');
        $this->info('╚═══════════════════════════════════════╝');
        $this->line('');

        $meiStart = Carbon::createFromFormat('Y-m-d', '2026-05-01')->startOfDay();
        $meiEnd = Carbon::createFromFormat('Y-m-d', '2026-05-31')->endOfDay();

        $totalAlarmRaw = AlarmRaw::whereRaw('DATE(created_at) BETWEEN ? AND ?', [
            $meiStart->toDateString(),
            $meiEnd->toDateString()
        ])->count();

        $totalIdleAlarms = IdleAlarm::whereRaw('DATE(created_at) BETWEEN ? AND ?', [
            $meiStart->toDateString(),
            $meiEnd->toDateString()
        ])->count();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total alarm_raw (Mei)', $totalAlarmRaw],
                ['Total idle_alarms (Mei)', $totalIdleAlarms],
                ['Type 32 alarms', AlarmRaw::where('alarm_type', 32)->whereBetween('created_at', [$meiStart, $meiEnd])->count()],
            ]
        );

        $this->line('');
        $this->info('✅ Mei backfill completed! Ready for June real-time.');
    }
}
