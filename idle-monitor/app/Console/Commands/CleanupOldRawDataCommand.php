<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CleanupOldRawDataJob;

class CleanupOldRawDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:raw-data 
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--days=30 : Retention period in days (default: 30)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup old raw data (alarm_raw, gps_raw) older than specified days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('╔════════════════════════════════════════════════════╗');
        $this->info('║     🗑️  Raw Data Cleanup Tool                     ║');
        $this->info('╚════════════════════════════════════════════════════╝');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $days = (int) $this->option('days');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE: No data will be deleted');
            $this->newLine();
        }

        $this->info("📊 Retention Period: {$days} days");
        $cutoffDate = now()->subDays($days);
        $this->info("📅 Cutoff Date: {$cutoffDate->toDateTimeString()}");
        $this->info("🗑️  Data older than this date will be deleted");
        $this->newLine();

        // Preview data yang akan dihapus
        $this->showPreview($cutoffDate);

        if ($dryRun) {
            $this->newLine();
            $this->info('✅ Dry run completed. No data was deleted.');
            $this->info('💡 Run without --dry-run to actually delete data');
            return 0;
        }

        // Konfirmasi dari user
        if (!$this->confirm('⚠️  Do you want to proceed with deletion?', false)) {
            $this->warn('❌ Cleanup cancelled by user');
            return 1;
        }

        $this->newLine();
        $this->info('🔄 Starting cleanup process...');

        // Jalankan cleanup job
        try {
            CleanupOldRawDataJob::dispatch();
            
            $this->newLine();
            $this->info('✅ Cleanup job dispatched successfully!');
            $this->info('📋 Check system logs for detailed results');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Failed to dispatch cleanup job: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Tampilkan preview data yang akan dihapus
     */
    private function showPreview($cutoffDate): void
    {
        $this->info('📋 Preview of data to be deleted:');
        $this->info(str_repeat('─', 50));

        // alarm_raw
        $alarmRawCount = \App\Models\AlarmRaw::where('created_at', '<', $cutoffDate)->count();
        $alarmRawSize = $this->estimateTableSize('alarm_raw', $alarmRawCount);
        
        $this->line("  📁 alarm_raw:");
        $this->line("     • Records: " . number_format($alarmRawCount));
        $this->line("     • Estimated size: ~{$alarmRawSize}");

        // gps_raw (jika ada)
        if (\DB::getSchemaBuilder()->hasTable('gps_raw')) {
            $gpsRawCount = \DB::table('gps_raw')
                ->where('created_at', '<', $cutoffDate)
                ->count();
            $gpsRawSize = $this->estimateTableSize('gps_raw', $gpsRawCount);
            
            $this->line("  📁 gps_raw:");
            $this->line("     • Records: " . number_format($gpsRawCount));
            $this->line("     • Estimated size: ~{$gpsRawSize}");
        }

        $this->newLine();
        
        // Total records
        $totalRecords = $alarmRawCount + ($gpsRawCount ?? 0);
        
        if ($totalRecords === 0) {
            $this->info('✨ No old data found. Database is clean!');
        } else {
            $this->warn("⚠️  Total records to delete: " . number_format($totalRecords));
        }
    }

    /**
     * Estimasi ukuran tabel berdasarkan jumlah record
     */
    private function estimateTableSize(string $table, int $records): string
    {
        // Estimasi rata-rata: 1 record = ~2KB
        $estimatedBytes = $records * 2048;
        
        return $this->formatBytes($estimatedBytes);
    }

    /**
     * Format bytes ke human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
