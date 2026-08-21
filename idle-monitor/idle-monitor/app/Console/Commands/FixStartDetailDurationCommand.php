<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AlarmRaw;
use App\Models\IdleAlarm;
use Illuminate\Support\Facades\DB;

class FixStartDetailDurationCommand extends Command
{
    protected $signature = 'howen:fix-start-detail-duration
                            {--dry-run : Dry run mode - show what would be changed without actually changing}
                            {--limit=1000 : Limit number of records to process per run}';
    
    protected $description = 'Fix duration_seconds for existing records by extracting dur from alarmvalue (start_detail) with correct priority';

    private $dryRun = false;
    private $limit = 1000;
    private $fixed = 0;
    private $skipped = 0;

    public function handle()
    {
        $this->dryRun = $this->option('dry-run') ?? false;
        $this->limit = (int)($this->option('limit') ?? 1000);

        $this->info('🔧 Fix Start Detail & Duration Command');
        $this->info('═══════════════════════════════════════');
        $this->newLine();
        
        if ($this->dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be saved');
            $this->newLine();
        }

        try {
            $this->info('📊 Analyzing alarm_raw data...');
            $this->fixAlarmRawRecords();
            
            $this->newLine();
            $this->info('📊 Analyzing idle_alarms data...');
            $this->fixIdleAlarmRecords();
            
            $this->newLine();
            $this->showSummary();
            
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }
    }

    private function fixAlarmRawRecords()
    {
        $this->info('🔍 Finding alarm_raw records with incorrect duration (dur:0 or NULL)...');
        
        // Find records where duration_seconds is 0 or doesn't match dur value in alarm_value
        // CORRECT UNDERSTANDING: Each record is independent snapshot with pre-calculated dur value
        // Priority: alarmvalue (alarm_value) > endDetail > alarmTimeLength
        $problematicRecords = AlarmRaw::where('alarm_type', 32)  // Only idle alarms
            ->where(function($query) {
                $query->where('duration_seconds', '=', 0)
                      ->orWhereNull('duration_seconds');
            })
            ->whereNotNull('raw_json')
            ->limit($this->limit)
            ->get();

        $this->info("   Found {$problematicRecords->count()} records with incorrect duration");
        $this->newLine();

        if ($problematicRecords->isEmpty()) {
            $this->info('✅ No problematic records found in alarm_raw!');
            return;
        }

        $bar = $this->output->createProgressBar($problematicRecords->count());
        $bar->start();

        foreach ($problematicRecords as $record) {
            try {
                // Parse raw_json to get source data
                $rawJson = json_decode($record->raw_json, true);
                
                if (!$rawJson) {
                    $this->skipped++;
                    $bar->advance();
                    continue;
                }
                
                // Extract alarmvalue (this is the start_detail field)
                $alarmValue = $rawJson['alarmvalue'] ?? $rawJson['alarmValue'] ?? null;
                
                // Extract dur using correct priority: alarmvalue > endDetail > alarmTimeLength
                $durationFromStart = 0;
                if (!empty($alarmValue) && preg_match('/dur:(\d+)/', $alarmValue, $m)) {
                    $durationFromStart = (int)$m[1];
                }

                $endDetail = $rawJson['endDetail'] ?? null;
                $durationFromEnd = 0;
                if (!empty($endDetail) && preg_match('/dur:(\d+)/', $endDetail, $m)) {
                    $durationFromEnd = (int)$m[1];
                }

                $alarmTimeLength = (int)($rawJson['alarmTimeLength'] ?? 0);
                
                // Priority: alarmvalue (start_detail) > endDetail > alarmTimeLength
                $durValue = $durationFromStart > 0 ? $durationFromStart : 
                           ($durationFromEnd > 0 ? $durationFromEnd : $alarmTimeLength);
                
                if ($durValue <= 0) {
                    $this->skipped++;
                    $bar->advance();
                    continue;
                }

                if (!$this->dryRun) {
                    // Update duration_seconds with correct value extracted from alarmvalue
                    $record->update([
                        'duration_seconds' => $durValue,
                    ]);
                }

                $this->fixed++;
                $bar->advance();

            } catch (\Exception $e) {
                $this->skipped++;
                $bar->advance();
                continue;
            }
        }

        $bar->finish();
        $this->newLine();
        $this->newLine();
        
        $this->info("✅ Fixed {$this->fixed} alarm_raw records");
        $this->info("⚠️  Skipped {$this->skipped} records (no valid duration data)");
    }

    private function fixIdleAlarmRecords()
    {
        $this->fixed = 0;
        $this->skipped = 0;

        $this->info('🔍 Finding idle_alarms records with incorrect duration...');
        
        // Find idle_alarms where duration is 0 or NULL
        // CORRECT UNDERSTANDING: Duration should come from dur value in start_detail (alarmvalue)
        $problematicAlarms = IdleAlarm::where(function($query) {
                $query->where('duration_seconds', '=', 0)
                      ->orWhereNull('duration_seconds')
                      ->orWhere('duration_minutes', '=', 0)
                      ->orWhereNull('duration_minutes');
            })
            ->limit($this->limit)
            ->get();

        $this->info("   Found {$problematicAlarms->count()} problematic idle_alarms");
        $this->newLine();

        if ($problematicAlarms->isEmpty()) {
            $this->info('✅ No problematic records found in idle_alarms!');
            return;
        }

        $bar = $this->output->createProgressBar($problematicAlarms->count());
        $bar->start();

        foreach ($problematicAlarms as $alarm) {
            try {
                // Find corresponding record from alarm_raw
                $alarmRaw = AlarmRaw::where('guid', $alarm->guid)
                    ->whereNotNull('raw_json')
                    ->first();

                if (!$alarmRaw) {
                    $this->skipped++;
                    $bar->advance();
                    continue;
                }

                // Parse raw_json to get source data
                $rawJson = json_decode($alarmRaw->raw_json, true);
                
                if (!$rawJson) {
                    $this->skipped++;
                    $bar->advance();
                    continue;
                }
                
                // Extract alarmvalue (this is the start_detail field)
                $alarmValue = $rawJson['alarmvalue'] ?? $rawJson['alarmValue'] ?? null;
                
                // Extract dur using correct priority: alarmvalue > endDetail > alarmTimeLength
                $durationFromStart = 0;
                if (!empty($alarmValue) && preg_match('/dur:(\d+)/', $alarmValue, $m)) {
                    $durationFromStart = (int)$m[1];
                }

                $endDetail = $rawJson['endDetail'] ?? null;
                $durationFromEnd = 0;
                if (!empty($endDetail) && preg_match('/dur:(\d+)/', $endDetail, $m)) {
                    $durationFromEnd = (int)$m[1];
                }

                $alarmTimeLength = (int)($rawJson['alarmTimeLength'] ?? 0);
                
                // Priority: alarmvalue (start_detail) > endDetail > alarmTimeLength
                $durValue = $durationFromStart > 0 ? $durationFromStart : 
                           ($durationFromEnd > 0 ? $durationFromEnd : $alarmTimeLength);
                
                if ($durValue <= 0) {
                    $this->skipped++;
                    $bar->advance();
                    continue;
                }

                $durMinutes = ceil($durValue / 60);

                if (!$this->dryRun) {
                    // Update idle_alarm with correct duration extracted from alarmvalue
                    $alarm->update([
                        'duration_seconds' => $durValue,
                        'duration_minutes' => $durMinutes,
                    ]);
                }

                $this->fixed++;
                $bar->advance();

            } catch (\Exception $e) {
                $this->skipped++;
                $bar->advance();
                continue;
            }
        }

        $bar->finish();
        $this->newLine();
        $this->newLine();
        
        $this->info("✅ Fixed {$this->fixed} idle_alarms records");
        $this->info("⚠️  Skipped {$this->skipped} records (no valid duration data)");
    }

    private function extractDur(?string $alarmValue): ?int
    {
        if (empty($alarmValue)) {
            return null;
        }

        // Extract dur:XXX from alarmvalue string
        if (preg_match('/dur:\s*(\d+)/', $alarmValue, $matches)) {
            return (int)$matches[1];
        }

        return null;
    }

    private function showSummary()
    {
        $this->info('╔═══════════════════════════════════════╗');
        $this->info('║         FIX SUMMARY REPORT           ║');
        $this->info('╚═══════════════════════════════════════╝');
        $this->newLine();

        if ($this->dryRun) {
            $this->warn('⚠️  DRY RUN - No actual changes were made');
            $this->newLine();
        }

        // Count remaining problematic records
        $remainingAlarmRaw = AlarmRaw::where('alarm_type', 32)
            ->where(function($query) {
                $query->where('duration_seconds', '=', 0)
                      ->orWhereNull('duration_seconds');
            })
            ->count();

        $remainingIdleAlarms = IdleAlarm::where(function($query) {
                $query->where('duration_seconds', '=', 0)
                      ->orWhereNull('duration_seconds')
                      ->orWhere('duration_minutes', '=', 0)
                      ->orWhereNull('duration_minutes');
            })
            ->count();

        $totalAlarmRaw = AlarmRaw::count();
        $totalIdleAlarms = IdleAlarm::count();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total alarm_raw records', $totalAlarmRaw],
                ['Remaining problematic alarm_raw', $remainingAlarmRaw],
                ['', ''],
                ['Total idle_alarms records', $totalIdleAlarms],
                ['Remaining problematic idle_alarms', $remainingIdleAlarms],
            ]
        );

        $this->newLine();
        
        if ($remainingAlarmRaw > 0 || $remainingIdleAlarms > 0) {
            $this->warn("⚠️  There are still {$remainingAlarmRaw} problematic alarm_raw records");
            $this->warn("⚠️  There are still {$remainingIdleAlarms} problematic idle_alarms records");
            $this->newLine();
            $this->info("💡 Run this command again to fix more records:");
            $this->info("   php artisan howen:fix-start-detail-duration --limit=5000");
        } else {
            $this->info("✅ All records have been fixed!");
        }
    }
}
