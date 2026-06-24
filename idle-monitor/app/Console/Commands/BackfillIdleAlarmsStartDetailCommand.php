<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AlarmRaw;
use App\Models\IdleAlarm;
use Illuminate\Support\Facades\DB;

class BackfillIdleAlarmsStartDetailCommand extends Command
{
    protected $signature = 'backfill:idle-alarms-start-detail 
                            {--limit=1000 : Number of records to process per batch}
                            {--dry-run : Preview changes without applying them}';

    protected $description = 'Backfill idle_alarms.start_detail from alarm_raw.start_detail';

    public function handle()
    {
        $limit = (int)$this->option('limit');
        $dryRun = $this->option('dry-run');

        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  BACKFILL: idle_alarms.start_detail from alarm_raw');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No data will be changed');
            $this->newLine();
        }

        // Count records needing backfill
        $totalEmpty = IdleAlarm::where(function($q) {
            $q->whereNull('start_detail')->orWhere('start_detail', '');
        })->count();

        $this->info("📊 idle_alarms with empty start_detail: {$totalEmpty}");
        $this->newLine();

        if ($totalEmpty === 0) {
            $this->info('✅ No records need backfilling!');
            return 0;
        }

        if (!$dryRun) {
            if (!$this->confirm('Proceed with backfill?', true)) {
                $this->warn('❌ Cancelled by user');
                return 1;
            }
            $this->newLine();
        }

        $updated = 0;
        $skipped = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($totalEmpty);
        $bar->start();

        // Process in batches
        IdleAlarm::where(function($q) {
            $q->whereNull('start_detail')->orWhere('start_detail', '');
        })
        ->chunk($limit, function($idleAlarms) use (&$updated, &$skipped, &$errors, $dryRun, $bar) {
            
            DB::beginTransaction();
            
            try {
                foreach ($idleAlarms as $idleAlarm) {
                    // Find corresponding alarm_raw by guid
                    $alarmRaw = AlarmRaw::where('guid', $idleAlarm->guid)->first();

                    if (!$alarmRaw) {
                        $skipped++;
                        $bar->advance();
                        continue;
                    }

                    // Copy start_detail from alarm_raw
                    if ($alarmRaw->start_detail) {
                        if (!$dryRun) {
                            $idleAlarm->start_detail = $alarmRaw->start_detail;
                            $idleAlarm->save();
                        }
                        $updated++;
                    } else {
                        $skipped++;
                    }

                    $bar->advance();
                }

                if (!$dryRun) {
                    DB::commit();
                }

            } catch (\Exception $e) {
                DB::rollBack();
                $errors++;
                $this->newLine();
                $this->error("❌ Error in batch: " . $e->getMessage());
            }
        });

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('📊 BACKFILL SUMMARY:');
        $this->info("   Total processed: {$totalEmpty}");
        $this->info("   ✅ Updated: {$updated}");
        $this->info("   ⏭️  Skipped (no alarm_raw): {$skipped}");
        $this->info("   ❌ Errors: {$errors}");
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        if ($dryRun) {
            $this->warn('🔍 DRY RUN COMPLETE - No data was changed');
            $this->newLine();
            $this->info('💡 Run without --dry-run to apply changes:');
            $this->info('   php artisan backfill:idle-alarms-start-detail');
        } else {
            $this->info('✅ BACKFILL COMPLETE!');
            $this->newLine();
            $this->info('🎉 All start_detail fields have been backfilled!');
        }

        return 0;
    }
}

