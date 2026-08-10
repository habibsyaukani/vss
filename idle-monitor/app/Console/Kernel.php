<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     * 
     * ⚠️ IMPORTANT: Keep this clean and simple!
     * - No duplicate jobs
     * - Clear comments
     * - Easy to understand
     */
    protected function schedule(Schedule $schedule): void
    {
        // ========================================
        // 🔐 AUTHENTICATION
        // ========================================
        
        // Refresh Howen API token every 25 minutes (to prevent expiration)
        $schedule->command('howen:test-auth')
            ->cron('*/25 * * * *')
            ->withoutOverlapping()
            ->description('Refresh Howen API authentication token');

        // ========================================
        // 📊 DATA SYNCHRONIZATION
        // ========================================
        
        // Sync device list from Howen API every 1 hour
        $schedule->job(new \App\Jobs\SyncDeviceJob())
            ->hourly()
            ->withoutOverlapping()
            ->description('Sync device list from Howen API');

        // ========================================
        // 🚨 ALARM DATA PULL (REAL-TIME)
        // ========================================
        
        // ✅ PRIMARY: Pull alarms every 3 minutes (last 2 hours)
        $schedule->command('howen:pull-alarms-realtime', [
            '--hours' => 2,
        ])
            ->everyThreeMinutes()
            ->withoutOverlapping(5)
            ->runInBackground()
            ->description('Pull alarm data (real-time, last 2 hours)');

        // ✅ SECONDARY: Process idle alarms every 3 minutes (direct command, no queue delay)
        $schedule->command('howen:process-idle-alarms')
            ->everyThreeMinutes()
            ->withoutOverlapping(5)
            ->runInBackground()
            ->description('Process idle alarms (analyze duration)');

        // ✅ PRIMARY: Pull GPS tracks every 3 minutes (last 1 hour, fast concurrent)
        $schedule->command('vss:pull-gps-tracks', [
            '--hours' => 1,
        ])
            ->everyThreeMinutes()
            ->withoutOverlapping(10)
            ->runInBackground()
            ->description('Pull GPS track data (fast concurrent, last 1 hour)');

        // Process GPS tracks every 3 minutes (dispatch to queue)
        $schedule->call(function () {
            \App\Jobs\ProcessGpsTrackJob::dispatch();
        })
            ->name('process-gps-track-job')
            ->everyThreeMinutes()
            ->withoutOverlapping(5)
            ->description('Process GPS tracks (raw to display)');

        // ========================================
        // 🗑️ DATABASE CLEANUP (AUTO-MAINTENANCE)
        // ========================================
        
        // Cleanup old raw data - Schedule berdasarkan setting di database
        // Setting bisa diubah di System Control Center
        // Default: Monthly pada tanggal 1 jam 02:00 AM
        $schedule->call(function () {
            try {
                // Check jika tabel system_settings ada
                if (!DB::getSchemaBuilder()->hasTable('system_settings')) {
                    return; // Skip jika tabel belum ada
                }

                // Dispatch job hanya jika cleanup enabled
                if (\App\Models\SystemSetting::isCleanupEnabled()) {
                    \App\Jobs\CleanupOldRawDataJob::dispatch();
                }
            } catch (\Exception $e) {
                // Silent fail - jangan break scheduler
                \Log::error('Cleanup scheduler error: ' . $e->getMessage());
            }
        })->cron($this->getCleanupCron())->description('Cleanup old raw data (based on system settings)');

        // ========================================
        // ⚙️ AUTO QUEUE WORKER (FAILSAFE)
        // ========================================
        
        // This ensures the queue worker is always running and clears jobs automatically
        // even if the user forgets to start the queue worker manually.
        $schedule->command('queue:work --stop-when-empty')
            ->everyMinute()
            ->withoutOverlapping()
            ->description('Auto-run queue worker to prevent stuck background jobs');

        // ========================================
        // 🗄️ HISTORICAL BACKFILL (MANUAL/OPTIONAL)
        // ========================================
        
        // ℹ️ For historical data backfill, run manually:
        // php artisan howen:pull-alarms-date-range --from=2026-05-01 --to=2026-06-05 --parallel --concurrency=5 --wait
        //
        // Or uncomment below to run daily at 1:00 AM (pulls last 7 days):
        // $schedule->command('howen:pull-alarms-date-range', [
        //     '--from' => now()->subDays(7)->format('Y-m-d'),
        //     '--to' => now()->format('Y-m-d'),
        //     '--pages' => 20,
        //     '--parallel',
        //     '--concurrency' => 3,
        // ])
        //     ->dailyAt('01:00')
        //     ->withoutOverlapping()
        //     ->description('Backfill alarm data (last 7 days)');

        // ========================================
        // 🔥 CACHE WARM-UP (PERFORMANCE)
        // ========================================

        // Pre-warm dashboard caches every hour so users never hit cold cache.
        // This prevents the 100-second first-load delay on Speed/Idle/Dashboard.
        $schedule->command('cache:warm-dashboard')
            ->hourly()
            ->withoutOverlapping()
            ->description('Pre-warm dashboard & speed per-day caches');

        // ========================================
        // 🔄 AUTO DATA PULL (TEMPORARILY DISABLED)
        // ========================================

        // ⚠️ DISABLED: Conflicting with manual data pull
        // Uncomment to re-enable alternating Idle & GPS auto-pull
        
        // Run auto pull every minute (command will check if 30 min passed)
        // $schedule->command('auto-pull:run')
        //     ->everyMinute()
        //     ->withoutOverlapping()
        //     ->description('Auto pull data alternately (Idle & GPS every 30 min)');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Get cleanup cron expression based on schedule setting
     */
    private function getCleanupCron(): string
    {
        try {
            // Check jika tabel system_settings ada
            if (!DB::getSchemaBuilder()->hasTable('system_settings')) {
                return '0 2 1 * *'; // Default: monthly
            }

            $schedule = \App\Models\SystemSetting::get('cleanup_schedule', 'monthly');
            
            return match($schedule) {
                'daily' => '0 2 * * *',           // Every day at 02:00
                'weekly' => '0 2 * * 0',          // Every Sunday at 02:00
                'monthly' => '0 2 1 * *',         // 1st of month at 02:00
                default => '0 2 1 * *',           // Default: monthly
            };
        } catch (\Exception $e) {
            return '0 2 1 * *'; // Default: monthly jika error
        }
    }
}
