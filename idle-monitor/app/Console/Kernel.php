<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

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
        $schedule->command('app:test-howen-auth')
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
        // This ensures real-time monitoring without overwhelming the API
        $schedule->command('howen:pull-alarms-realtime', [
            '--hours' => 2,
            '--concurrency' => 1,
        ])
            ->everyThreeMinutes()
            ->withoutOverlapping()
            ->description('Pull alarm data (real-time, last 2 hours)');

        // ✅ SECONDARY: Process idle alarms every 5 minutes
        // Analyze alarm data and calculate idle duration
        $schedule->job(new \App\Jobs\ProcessIdleAlarmJob())
            ->cron('*/5 * * * *')
            ->withoutOverlapping()
            ->description('Process idle alarms (analyze duration)');

        // ========================================
        // 📍 GPS TRACK AUTO-PULL (ENABLED - REAL-TIME)
        // ========================================
        
        // Import GPS tracks every 5 minutes (last 30 minutes)
        // Configuration: 30 min lookback for optimal real-time with safety buffer
        // - Execution time: ~1.5 min
        // - Buffer time: ~3.5 min
        // - Coverage: 6x overlap (excellent safety margin)
        // - API load: 75% lower than 2-hour lookback
        $schedule->job(new \App\Jobs\ImportGpsTrackJob(0.5, 500))  // 0.5 hours = 30 minutes
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->description('Pull GPS track data (last 30 min, every 5 min)');

        // Process GPS tracks every 3 minutes (raw → display table)
        // This processes any remaining raw data that wasn't auto-processed during sync
        $schedule->job(new \App\Jobs\ProcessGpsTrackJob())
            ->everyThreeMinutes()
            ->withoutOverlapping()
            ->description('Process GPS tracks (raw to display)');

        // ========================================
        // 🗑️ DATABASE CLEANUP (AUTO-MAINTENANCE)
        // ========================================
        
        // Cleanup old raw data (> 1 month) every day at 02:00 AM
        // Menghapus data raw yang sudah lebih dari 1 bulan
        // Data sebenarnya sudah ada di tabel inti (idle_alarms, gps_track)
        $schedule->job(new \App\Jobs\CleanupOldRawDataJob())
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->description('Cleanup old raw data (> 1 month retention)');

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
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
