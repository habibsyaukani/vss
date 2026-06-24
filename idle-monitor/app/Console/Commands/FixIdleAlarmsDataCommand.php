<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AlarmRaw;
use App\Models\IdleAlarm;
use Illuminate\Support\Facades\DB;

class FixIdleAlarmsDataCommand extends Command
{
    protected $signature = 'fix:idle-alarms-data 
                            {--limit=1000 : Batch size}
                            {--dry-run : Preview only}';

    protected $description = 'Fix idle_alarms: fill start_detail and recalculate duration from alarm_raw';

    public function handle()
    {
        $limit = (int)$this->option('limit');
        $dryRun = $this->option('dry-run');

        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  FIX: idle_alarms Data (start_detail + duration)');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No data will be changed');
            $this->newLine();
        }

        // Step 1: Backfill alarm_raw first
        $this->info('STEP 1: Backfill alarm_raw.start_detail from raw_json');
        $this->info('─────────────────────────────────────────────────────────────');
        $this->backfillAlarmRaw($limit, $dryRun);
        $this->newLine();

        // Step 2: Fix idle_alarms
        $this->info('STEP 2: Fix idle_alarms from alarm_raw');
        $this->info('─────────────────────────────────────────────────────────────');
        $this->fixIdleAlarms($limit, $dryRun);

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');
        
        if ($dryRun) {
            $this->warn('🔍 DRY RUN COMPLETE - No data was changed');
            $this->newLine();
            $this->info('💡 Run without --dry-run to apply changes:');
            $this->info('   php artisan fix:idle-alarms-data');
        } else {
            $this->info('✅ FIX COMPLETE!');
        }

        return 0;
    }

    private function backfillAlarmRaw($limit, $dryRun)
    {
        $totalEmpty = AlarmRaw::where(function($q) {
            $q->whereNull('start_detail')->orWhere('start_detail', '');
        })->whereNotNull('raw_json')->count();

        $this->info("📊 alarm_raw with empty start_detail: {$totalEmpty}");

        if ($totalEmpty === 0) {
            $this->info('   ✅ All alarm_raw records have start_detail');
            return;
        }

        $updated = 0;
        $bar = $this->output->createProgressBar($totalEmpty);
        $bar->start();

        AlarmRaw::where(function($q) {
            $q->whereNull('start_detail')->orWhere('start_detail', '');
        })
        ->whereNotNull('raw_json')
        ->chunk($limit, function($alarms) use (&$updated, $dryRun, $bar) {
            DB::beginTransaction();
            
            try {
                foreach ($alarms as $alarm) {
                    $json = is_string($alarm->raw_json) 
                        ? json_decode($alarm->raw_json, true) 
                        : $alarm->raw_json;

                    if (!$json) {
                        $bar->advance();
                        continue;
                    }

                    $startDetail = $json['alarmvalue'] ?? $json['alarmValue'] ?? null;

                    if ($startDetail && !$dryRun) {
                        $alarm->start_detail = $startDetail;
                        $alarm->save();
                        $updated++;
                    }

                    $bar->advance();
                }

                if (!$dryRun) {
                    DB::commit();
                }

            } catch (\Exception $e) {
                DB::rollBack();
                $this->newLine();
                $this->error("❌ Error: " . $e->getMessage());
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("   ✅ Updated: {$updated} alarm_raw records");
    }

    private function fixIdleAlarms($limit, $dryRun)
    {
        // Count idle_alarms yang perlu di-fix
        $totalEmpty = IdleAlarm::where(function($q) {
            $q->whereNull('start_detail')->orWhere('start_detail', '');
        })->count();

        $this->info("📊 idle_alarms with empty start_detail: {$totalEmpty}");

        if ($totalEmpty === 0) {
            $this->info('   ✅ All idle_alarms records have start_detail');
            return;
        }

        $updated = 0;
        $durationFixed = 0;
        
        $bar = $this->output->createProgressBar($totalEmpty);
        $bar->start();

        IdleAlarm::where(function($q) {
            $q->whereNull('start_detail')->orWhere('start_detail', '');
        })
        ->chunk($limit, function($idleAlarms) use (&$updated, &$durationFixed, $dryRun, $bar) {
            DB::beginTransaction();
            
            try {
                foreach ($idleAlarms as $idleAlarm) {
                    // Find corresponding alarm_raw by guid
                    $alarmRaw = AlarmRaw::where('guid', $idleAlarm->guid)->first();

                    if (!$alarmRaw) {
                        $bar->advance();
                        continue;
                    }

                    $changed = false;

                    // 1. Copy start_detail
                    if ($alarmRaw->start_detail && !$dryRun) {
                        $idleAlarm->start_detail = $alarmRaw->start_detail;
                        $changed = true;
                    }

                    // 2. Recalculate duration from start_detail if exists
                    if ($alarmRaw->start_detail && !$dryRun) {
                        $duration = $this->extractDurationFromDetail($alarmRaw->start_detail);
                        if ($duration !== null && $duration > 0) {
                            $idleAlarm->duration_seconds = $duration;
                            $idleAlarm->duration_minutes = round($duration / 60, 2);
                            $durationFixed++;
                        }
                    }

                    if ($changed && !$dryRun) {
                        $idleAlarm->save();
                        $updated++;
                    }

                    $bar->advance();
                }

                if (!$dryRun) {
                    DB::commit();
                }

            } catch (\Exception $e) {
                DB::rollBack();
                $this->newLine();
                $this->error("❌ Error: " . $e->getMessage());
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("   ✅ Updated: {$updated} idle_alarms records");
        $this->info("   ✅ Duration recalculated: {$durationFixed} records");
    }

    private function extractDurationFromDetail($detail)
    {
        if (empty($detail)) {
            return null;
        }

        // Parse "dur:123" from detail string
        // Format: "avg:0.00;cur:0.00;dur:123;max:0.00;..."
        if (preg_match('/dur:\s*(\d+)/', $detail, $matches)) {
            return (int)$matches[1];
        }

        return null;
    }
}

