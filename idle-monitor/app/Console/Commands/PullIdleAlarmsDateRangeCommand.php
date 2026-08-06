<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ImportAlarmPageJob;
use App\Jobs\ProcessIdleAlarmJob;
use Carbon\Carbon;

class PullIdleAlarmsDateRangeCommand extends Command
{
    protected $signature = 'howen:pull-alarms-date-range 
                            {--from= : Start date (YYYY-MM-DD). Default: 1 days ago}
                            {--to= : End date (YYYY-MM-DD). Default: today}
                            {--wait : Wait for jobs to complete (blocking)}
                            {--pages=7 : Number of pages to fetch (default: 7)}
                            {--parallel : Use parallel fetching (3-4x faster) - Option 1}
                            {--concurrency=3 : Concurrency level for parallel (default: 3, max: 10)}';
    
    protected $description = 'Pull idle alarm data from Howen API for a specific date range';

    private $totalImported = 0;
    private $totalProcessed = 0;
    private $startDate;
    private $endDate;

    public function handle()
    {
        $this->info('🔄 Starting historical idle alarm data pull...');
        $this->newLine();

        try {
            // Validate and parse dates
            $this->validateAndParseDates();
            
            $this->info("📅 Date Range: {$this->startDate->format('Y-m-d')} to {$this->endDate->format('Y-m-d')}");
            $days = $this->startDate->diffInDays($this->endDate) + 1;
            $this->info("📊 Total days: {$days} days");
            $this->newLine();

            // Pull data for each day
            $this->pullDataByDateRange();
            
            // Process idle alarms if requested
            if ($this->option('wait')) {
                $this->info('⏳ Processing idle alarms...');
                $this->processIdleAlarms();
            } else {
                $this->info('📤 Idle alarm processing dispatched to queue.');
                dispatch(new ProcessIdleAlarmJob());
            }
            
            $this->newLine();
            $this->info('✅ Historical data pull completed!');
            
            // Update last_alarm_sync for API status indicator
            \App\Models\SystemSetting::set('last_alarm_sync', now()->toDateTimeString());
            
            $this->showSummary();
            
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }
    }

    private function validateAndParseDates()
    {
        $from = $this->option('from');
        $to = $this->option('to');

        if ($from) {
            try {
                $this->startDate = Carbon::createFromFormat('Y-m-d', $from)->startOfDay();
            } catch (\Exception $e) {
                throw new \Exception("Invalid 'from' date format. Use YYYY-MM-DD");
            }
        } else {
            $this->startDate = now()->subDays(1)->startOfDay();
        }

        if ($to) {
            try {
                $this->endDate = Carbon::createFromFormat('Y-m-d', $to)->endOfDay();
            } catch (\Exception $e) {
                throw new \Exception("Invalid 'to' date format. Use YYYY-MM-DD");
            }
        } else {
            $this->endDate = now()->endOfDay();
        }

        if ($this->startDate->isAfter($this->endDate)) {
            throw new \Exception("Start date cannot be after end date");
        }
    }

    private function pullDataByDateRange()
    {
        $pages = (int)$this->option('pages') ?: 7;
        $useParallel = $this->option('parallel') ? true : false;
        $concurrency = (int)$this->option('concurrency') ?: 3;
        $concurrency = min($concurrency, 10); // Max 10 concurrent

        if ($useParallel) {
            $this->info('⚡ Using PARALLEL fetching (Option 1) - 3-4x faster!');
            $this->newLine();
            $this->pullDataParallel($pages, $concurrency);
        } else {
            $this->info('📊 Using sequential fetching (current method)');
            $this->newLine();
            $this->pullDataSequential($pages);
        }
    }

    private function pullDataSequential($pages)
    {
        $bar = $this->output->createProgressBar($pages);
        $bar->start();

        for ($pageNum = 1; $pageNum <= $pages; $pageNum++) {
            try {
                $job = new ImportAlarmPageJob(
                    $pageNum,
                    200, // pageCount
                    $this->startDate->toDateTimeString(),
                    $this->endDate->toDateTimeString()
                );

                if ($this->option('wait')) {
                    $job->handle();
                } else {
                    dispatch($job);
                }

                $bar->advance();
                usleep(200000); // 200ms delay

            } catch (\Exception $e) {
                $this->warn("⚠️ Error on page {$pageNum}: " . $e->getMessage());
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
    }

    private function pullDataParallel($pages, $concurrency)
    {
        $this->info("🔄 Fetching {$pages} pages with {$concurrency} concurrent connections...");
        
        try {
            $alarmService = new \App\Services\HowenAlarmService();
            
            // Fetch all pages in parallel
            $startTime = microtime(true);
            $allAlarms = $alarmService->fetchAlarmsParallel(
                1, 
                $pages, 
                200, 
                $this->startDate->toDateTimeString(),
                $this->endDate->toDateTimeString(),
                $concurrency
            );
            $fetchTime = microtime(true) - $startTime;

            $this->info("✅ Fetched " . count($allAlarms) . " records in " . round($fetchTime, 2) . " seconds");
            $this->newLine();

            // Store to alarm_raw
            if (!empty($allAlarms)) {
                $this->info("💾 Importing records to database...");
                $importLog = \App\Models\ImportLog::create([
                    'job_name' => 'ParallelImportAlarmJob',
                    'started_at' => now(),
                    'total_record' => 0,
                    'status' => 'running',
                ]);

                $inserted = 0;
                foreach ($allAlarms as $alarm) {
                    $deviceId = $alarm['deviceguid'] ?? $alarm['device_id'] ?? null;
                    if (!$deviceId) continue;

                    $alarmState = $alarm['alarmState'] ?? $alarm['alarm_state'] ?? null;
                    $alarmValue = $alarm['alarmvalue'] ?? $alarm['alarmValue'] ?? null;
                    
                    // ✅ ALWAYS map alarmvalue to start_detail (regardless of alarmState)
                    // start_detail = technical data from start (avg, cur, dur, max, min, pre, tt, vt, satellites)
                    $startDetail = $alarmValue;
                    $endDetail = $alarm['endDetail'] ?? $alarm['end_detail'] ?? null;

                    // Extract duration using correct priority: alarmvalue > endDetail > alarmTimeLength
                    $durationFromStart = 0;
                    if (!empty($alarmValue) && preg_match('/dur:(\d+)/', $alarmValue, $m)) {
                        $durationFromStart = (int)$m[1];
                    }

                    $endDetailValue = $alarm['endDetail'] ?? $alarm['end_detail'] ?? null;
                    $durationFromEnd = 0;
                    if (!empty($endDetailValue) && preg_match('/dur:(\d+)/', $endDetailValue, $m)) {
                        $durationFromEnd = (int)$m[1];
                    }

                    $alarmTimeLength = (int)($alarm['alarmTimeLength'] ?? $alarm['duration_seconds'] ?? 0);
                    
                    // Priority: alarmvalue (start_detail) > endDetail > alarmTimeLength
                    $durationSeconds = $durationFromStart > 0 ? $durationFromStart : 
                                      ($durationFromEnd > 0 ? $durationFromEnd : $alarmTimeLength);

                    $alarmRawData = [
                        'guid' => $alarm['guid'] ?? uniqid(),
                        'device_id' => $deviceId,
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
                        'duration_seconds' => $durationSeconds,
                        'start_detail' => $startDetail,
                        'end_detail' => $endDetail,
                        'raw_json' => json_encode($alarm),
                    ];

                    \App\Models\AlarmRaw::updateOrCreate(
                        ['guid' => $alarmRawData['guid']],
                        $alarmRawData
                    );

                    $inserted++;
                }

                $importLog->update([
                    'finished_at' => now(),
                    'status' => 'completed',
                    'total_record' => $inserted,
                    'message' => "Parallel imported {$inserted} alarms in " . round($fetchTime, 2) . "s",
                ]);

                $this->info("✅ Imported {$inserted} records to database");
            }

        } catch (\Exception $e) {
            $this->error("❌ Parallel fetch error: " . $e->getMessage());
        }
    }

    private function processIdleAlarms()
    {
        try {
            $job = new ProcessIdleAlarmJob();
            $job->handle();
            $this->info("✅ Idle alarm processing completed");
        } catch (\Exception $e) {
            $this->warn("⚠️ Processing warning: " . $e->getMessage());
        }
    }

    private function showSummary()
    {
        $this->line('');
        $this->info('📋 SUMMARY:');
        
        // Show import logs
        $logs = \App\Models\ImportLog::whereIn('job_name', ['ImportAlarmPageJob', 'ParallelImportAlarmJob'])
            ->where('created_at', '>=', now()->subMinutes(10))
            ->get();
        
        $totalRecords = $logs->sum('total_record');
        $totalType32 = \App\Models\AlarmRaw::where('alarm_type', 32)
            ->where('created_at', '>=', $this->startDate)
            ->where('created_at', '<=', $this->endDate)
            ->count();
        
        $totalIdleAlarms = \App\Models\IdleAlarm::where('created_at', '>=', $this->startDate)
            ->where('created_at', '<=', $this->endDate)
            ->count();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Records imported', $totalRecords],
                ['Type 32 (Idle) records', $totalType32],
                ['Valid idle alarms processed', $totalIdleAlarms],
            ]
        );

        // Required for JS frontend extraction
        $this->info("FrontendMatch: Fetched {$totalRecords} records");
        $this->info("FrontendMatch: {$totalIdleAlarms} idle alarms processed");

        if ($totalIdleAlarms > 0) {
            $this->newLine();
            $this->info('Sample processed alarms:');
            
            $alarms = \App\Models\IdleAlarm::where('created_at', '>=', $this->startDate)
                ->where('created_at', '<=', $this->endDate)
                ->orderByDesc('duration_minutes')
                ->limit(5)
                ->get();
            
            foreach ($alarms as $alarm) {
                $duration = $alarm->duration_minutes;
                $date = $alarm->starting_time ? Carbon::parse($alarm->starting_time)->format('Y-m-d H:i') : 'N/A';
                $this->line("  • {$alarm->device_name} - {$duration}min - {$date}");
            }
        }
    }
}
