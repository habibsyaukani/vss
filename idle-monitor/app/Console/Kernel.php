<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Refresh Howen API token every 25 minutes
        $schedule->command('app:test-howen-auth')
            ->cron('*/25 * * * *')
            ->withoutOverlapping();

        // Import alarms every 2 minutes (incremental)
        $schedule->job(new \App\Jobs\ImportAlarmJob())
            ->cron('*/2 * * * *')
            ->withoutOverlapping();

        // Process idle alarms every 5 minutes
        $schedule->job(new \App\Jobs\ProcessIdleAlarmJob())
            ->cron('*/5 * * * *')
            ->withoutOverlapping();

        // Sync devices every 1 hour
        $schedule->job(new \App\Jobs\SyncDeviceJob())
            ->hourly()
            ->withoutOverlapping();

        // ========================================
        // GPS TRACK AUTO-PULL SYSTEM (SEMENTARA DIMATIKAN)
        // ========================================
        
        // Import GPS tracks every 5 minutes (2 hours back, real-time monitoring)
        // $schedule->job(new \App\Jobs\ImportGpsTrackJob(2, 500))
        //     ->everyFiveMinutes()
        //     ->withoutOverlapping();

        // Process GPS tracks every 3 minutes (map raw → display)
        // $schedule->job(new \App\Jobs\ProcessGpsTrackJob())
        //     ->everyThreeMinutes()
        //     ->withoutOverlapping();

        // ========================================
        // ALARM AUTO-PULL SYSTEM
        // ========================================

        // ✅ STRATEGI UTAMA: Real-time pull (2 jam terakhir) setiap 3 menit
        // Dibuat concurrency=1 agar SANGAT AMAN dari rate limit (blocking) saat berjalan otomatis
        $schedule->command('howen:pull-alarms-realtime', [
            '--hours' => 2,
            '--concurrency' => 1,
        ])
            ->everyThreeMinutes()
            ->withoutOverlapping();

        // ℹ️ BACKFILL HISTORIS: Jalankan manual jika dibutuhkan
        // php artisan howen:pull-alarms-date-range --from=2026-05-01 --to=2026-06-05 --parallel --concurrency=5 --wait
        //
        // Atau uncomment untuk jalankan otomatis sekali sehari jam 01:00 dini hari:
        // $schedule->command('howen:pull-alarms-date-range', [
        //     '--from' => now()->subDays(7)->format('Y-m-d'),  // 7 hari ke belakang saja
        //     '--to' => now()->format('Y-m-d'),
        //     '--pages' => 20,
        //     '--parallel',
        //     '--concurrency' => 3,
        // ])
        //     ->dailyAt('01:00')
        //     ->withoutOverlapping();

        // ℹ️ DISABLED: ImportAlarmJob (duplikat dengan realtime, pakai mock fallback)
        // Jika diaktifkan kembali, pastikan sudah tidak pakai fetchAlarmsPageWithMock
        // $schedule->job(new \App\Jobs\ImportAlarmJob())
        //     ->cron('*/2 * * * *')
        //     ->withoutOverlapping();
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
