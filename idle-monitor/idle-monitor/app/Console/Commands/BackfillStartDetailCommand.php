<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AlarmRaw;
use App\Models\IdleAlarm;
use Illuminate\Support\Facades\DB;

class BackfillStartDetailCommand extends Command
{
    protected $signature = 'backfill:start-detail 
                            {--limit=1000 : Number of records to process per batch}
                            {--dry-run : Preview changes without applying them}';

    protected $description = 'Backfill start_detail column from raw_json.alarmvalue';

    public function handle()
    {
        $limit = (int)$this->option('limit');
        $dryRun = $this->option('dry-run');

        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  BACKFILL: start_detail from raw_json');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No data will be changed');
            $this->newLine();
        }

        // Count records needing backfill
        $totalEmpty = AlarmRaw::where(function($q) {
            $q->whereNull('start_detail')->orWhere('start_detail', '');
        })->count();

        $this->info("📊 Records with empty start_detail: {$totalEmpty}");
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
        AlarmRaw::where(function($q) {
            $q->whereNull('start_detail')->orWhere('start_detail', '');
        })
        ->whereNotNull('raw_json')
        ->chunk($limit, function($alarms) use (&$updated, &$skipped, &$errors, $dryRun, $bar) {
            
            DB::beginTransaction();
            
            try {
                foreach ($alarms as $alarm) {
                    $json = is_string($alarm->raw_json) 
                        ? json_decode($alarm->raw_json, true) 
                        : $alarm->raw_json;

                    if (!$json) {
                        $skipped++;
                        $bar->advance();
                        continue;
                    }

                    // Extract alarmvalue from JSON
                    $startDetail = $json['alarmvalue'] ?? $json['alarmValue'] ?? null;

                    if ($startDetail) {
                        if (!$dryRun) {
                            $alarm->start_detail = $startDetail;
                            $alarm->save();
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
        $this->info("   ⏭️  Skipped (no alarmvalue): {$skipped}");
        $this->info("   ❌ Errors: {$errors}");
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        if ($dryRun) {
            $this->warn('🔍 DRY RUN COMPLETE - No data was changed');
            $this->newLine();
            $this->info('💡 Run without --dry-run to apply changes:');
            $this->info('   php artisan backfill:start-detail');
        } else {
            $this->info('✅ BACKFILL COMPLETE!');
            $this->newLine();
            
            // Suggest next step
            $this->info('💡 NEXT STEP: Update idle_alarms table');
            $this->info('   php artisan backfill:idle-alarms-start-detail');
        }

        return 0;
    }
}

