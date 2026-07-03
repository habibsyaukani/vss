<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\AlarmRaw;
use App\Services\SystemLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CleanupOldRawDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Retention period untuk data raw (dalam hari)
     * Default: 30 hari (1 bulan)
     */
    private int $retentionDays = 30;

    /**
     * Execute the job - Cleanup data raw yang sudah lebih dari 1 bulan
     *
     * TUJUAN:
     * - Menghapus data raw (alarm_raw, gps_raw) yang sudah lebih dari 1 bulan
     * - Data yang sebenarnya sudah ada di tabel inti (idle_alarms, gps_track)
     * - Mengurangi size database dan meningkatkan performance
     *
     * SAFETY:
     * - Retention period: 1 bulan (cukup untuk troubleshooting)
     * - Hanya hapus data yang sudah lama
     * - Logging detail untuk monitoring
     */
    public function handle(): void
    {
        $startTime = now();
        SystemLogger::info('CLEANUP_START', 'Starting raw data cleanup job', [
            'retention_days' => $this->retentionDays,
            'cutoff_date' => Carbon::now()->subDays($this->retentionDays)->toDateTimeString(),
        ]);

        try {
            // Tanggal cutoff: data sebelum tanggal ini akan dihapus
            $cutoffDate = Carbon::now()->subDays($this->retentionDays);

            // 1. Cleanup alarm_raw
            $this->cleanupAlarmRaw($cutoffDate);

            // 2. Cleanup gps_raw (jika tabel ada)
            $this->cleanupGpsRaw($cutoffDate);

            $duration = now()->diffInSeconds($startTime);
            SystemLogger::success('CLEANUP_COMPLETED', 'Raw data cleanup completed successfully', [
                'duration_seconds' => $duration,
                'retention_days' => $this->retentionDays,
            ]);

        } catch (\Exception $e) {
            SystemLogger::error('CLEANUP_FAILED', 'Raw data cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Cleanup tabel alarm_raw
     */
    private function cleanupAlarmRaw(Carbon $cutoffDate): void
    {
        try {
            // Hitung jumlah record yang akan dihapus
            $countToDelete = AlarmRaw::where('created_at', '<', $cutoffDate)->count();

            if ($countToDelete === 0) {
                SystemLogger::info('CLEANUP_ALARM_RAW', 'No alarm_raw records to cleanup', [
                    'cutoff_date' => $cutoffDate->toDateTimeString(),
                ]);
                return;
            }

            // Hapus data lama
            $deletedCount = AlarmRaw::where('created_at', '<', $cutoffDate)->delete();

            SystemLogger::success('CLEANUP_ALARM_RAW', 'Cleaned up alarm_raw table', [
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate->toDateTimeString(),
                'retention_days' => $this->retentionDays,
            ]);

        } catch (\Exception $e) {
            SystemLogger::error('CLEANUP_ALARM_RAW_FAILED', 'Failed to cleanup alarm_raw', [
                'error' => $e->getMessage(),
                'cutoff_date' => $cutoffDate->toDateTimeString(),
            ]);
            throw $e;
        }
    }

    /**
     * Cleanup tabel gps_raw (jika ada)
     */
    private function cleanupGpsRaw(Carbon $cutoffDate): void
    {
        try {
            // Cek apakah tabel gps_raw ada
            if (!DB::getSchemaBuilder()->hasTable('gps_raw')) {
                SystemLogger::info('CLEANUP_GPS_RAW', 'Table gps_raw does not exist, skipping', []);
                return;
            }

            // Hitung jumlah record yang akan dihapus
            $countToDelete = DB::table('gps_raw')
                ->where('created_at', '<', $cutoffDate)
                ->count();

            if ($countToDelete === 0) {
                SystemLogger::info('CLEANUP_GPS_RAW', 'No gps_raw records to cleanup', [
                    'cutoff_date' => $cutoffDate->toDateTimeString(),
                ]);
                return;
            }

            // Hapus data lama
            $deletedCount = DB::table('gps_raw')
                ->where('created_at', '<', $cutoffDate)
                ->delete();

            SystemLogger::success('CLEANUP_GPS_RAW', 'Cleaned up gps_raw table', [
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate->toDateTimeString(),
                'retention_days' => $this->retentionDays,
            ]);

        } catch (\Exception $e) {
            SystemLogger::error('CLEANUP_GPS_RAW_FAILED', 'Failed to cleanup gps_raw', [
                'error' => $e->getMessage(),
                'cutoff_date' => $cutoffDate->toDateTimeString(),
            ]);
            // Don't throw - continue cleanup process
        }
    }
}
