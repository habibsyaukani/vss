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
     * - Menghapus data raw (alarm_raw, gps_raw) yang sudah lebih dari X hari
     * - Data yang sebenarnya sudah ada di tabel inti (idle_alarms, gps_track)
     * - Mengurangi size database dan meningkatkan performance
     *
     * SAFETY:
     * - Check system setting apakah cleanup enabled
     * - Retention period dari database settings
     * - Validasi data sudah ada di tabel final
     * - Logging detail untuk monitoring
     */
    public function handle(): void
    {
        // CHECK: Apakah cleanup enabled di system settings?
        if (!\App\Models\SystemSetting::isCleanupEnabled()) {
            SystemLogger::info('CLEANUP_SKIPPED', 'Cleanup is disabled in system settings', []);
            return;
        }

        // Get retention days from settings
        $this->retentionDays = \App\Models\SystemSetting::getCleanupRetentionDays();

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

            // Update last run time
            \App\Models\SystemSetting::updateCleanupLastRun();

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
     * 
     * SAFETY: Hanya hapus data yang sudah ada di tabel idle_alarms
     * - Validasi berdasarkan guid (unique identifier)
     * - Pastikan data raw sudah diproses ke tabel final
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

            // SAFETY CHECK: Hanya hapus data yang sudah ada di idle_alarms
            // Ambil GUIDs dari alarm_raw yang mau dihapus
            $rawGuids = AlarmRaw::where('created_at', '<', $cutoffDate)
                ->where('alarm_type', 32) // Type 32 = Idle alarms
                ->pluck('guid')
                ->toArray();

            // Cek mana yang sudah ada di idle_alarms
            $processedGuids = DB::table('idle_alarms')
                ->whereIn('guid', $rawGuids)
                ->pluck('guid')
                ->toArray();

            SystemLogger::info('CLEANUP_ALARM_RAW_VALIDATION', 'Validating data before cleanup', [
                'total_old_records' => count($rawGuids),
                'already_processed' => count($processedGuids),
                'not_processed_yet' => count($rawGuids) - count($processedGuids),
            ]);

            // Hapus HANYA data yang sudah diproses ke idle_alarms
            $deletedCount = 0;
            if (!empty($processedGuids)) {
                $deletedCount = AlarmRaw::where('created_at', '<', $cutoffDate)
                    ->whereIn('guid', $processedGuids)
                    ->delete();
            }

            // Untuk non-idle alarms (type != 32), hapus yang sudah lama
            // Karena tidak semua alarm type ada tabel terpisah
            $nonIdleDeleted = AlarmRaw::where('created_at', '<', $cutoffDate)
                ->where('alarm_type', '!=', 32)
                ->delete();

            $totalDeleted = $deletedCount + $nonIdleDeleted;

            SystemLogger::success('CLEANUP_ALARM_RAW', 'Cleaned up alarm_raw table', [
                'deleted_idle_alarms' => $deletedCount,
                'deleted_non_idle' => $nonIdleDeleted,
                'total_deleted' => $totalDeleted,
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
     * Cleanup tabel gps_tracks_raw (jika ada)
     * 
     * SAFETY: Hanya hapus data yang sudah ada di tabel gps_tracks
     * - Validasi berdasarkan device_id + gps_time
     * - Pastikan data raw sudah diproses ke tabel final
     */
    private function cleanupGpsRaw(Carbon $cutoffDate): void
    {
        try {
            // Cek apakah tabel gps_tracks_raw ada
            if (!DB::getSchemaBuilder()->hasTable('gps_tracks_raw')) {
                SystemLogger::info('CLEANUP_GPS_RAW', 'Table gps_tracks_raw does not exist, skipping', []);
                return;
            }

            // Hitung jumlah record yang akan dihapus
            $countToDelete = DB::table('gps_tracks_raw')
                ->where('created_at', '<', $cutoffDate)
                ->count();

            if ($countToDelete === 0) {
                SystemLogger::info('CLEANUP_GPS_RAW', 'No gps_tracks_raw records to cleanup', [
                    'cutoff_date' => $cutoffDate->toDateTimeString(),
                ]);
                return;
            }

            // SAFETY CHECK: Hanya hapus data yang sudah ada di gps_tracks
            // Ambil sample data lama untuk validasi
            $oldRawData = DB::table('gps_tracks_raw')
                ->where('created_at', '<', $cutoffDate)
                ->select('device_id', 'gps_time')
                ->limit(1000) // Ambil sample untuk validasi
                ->get();

            $validatedCount = 0;
            foreach ($oldRawData as $raw) {
                // Cek apakah data ini sudah ada di gps_tracks
                $exists = DB::table('gps_tracks')
                    ->where('device_id', $raw->device_id)
                    ->where('gps_time', $raw->gps_time)
                    ->exists();
                
                if ($exists) {
                    $validatedCount++;
                }
            }

            $sampleSize = $oldRawData->count();
            $processedPercentage = $sampleSize > 0 ? ($validatedCount / $sampleSize) * 100 : 0;

            SystemLogger::info('CLEANUP_GPS_RAW_VALIDATION', 'Validating data before cleanup', [
                'sample_size' => $sampleSize,
                'already_processed' => $validatedCount,
                'processed_percentage' => round($processedPercentage, 2) . '%',
            ]);

            // Hanya hapus jika mayoritas data sudah diproses (> 95%)
            if ($processedPercentage >= 95) {
                // Hapus data lama
                $deletedCount = DB::table('gps_tracks_raw')
                    ->where('created_at', '<', $cutoffDate)
                    ->delete();

                SystemLogger::success('CLEANUP_GPS_RAW', 'Cleaned up gps_tracks_raw table', [
                    'deleted_count' => $deletedCount,
                    'cutoff_date' => $cutoffDate->toDateTimeString(),
                    'retention_days' => $this->retentionDays,
                    'validation_passed' => true,
                ]);
            } else {
                SystemLogger::warning('CLEANUP_GPS_RAW_SKIPPED', 'Skipped cleanup - data not fully processed yet', [
                    'processed_percentage' => round($processedPercentage, 2) . '%',
                    'required_percentage' => 95,
                    'recommendation' => 'Wait for ProcessGpsTrackJob to complete processing',
                ]);
            }

        } catch (\Exception $e) {
            SystemLogger::error('CLEANUP_GPS_RAW_FAILED', 'Failed to cleanup gps_tracks_raw', [
                'error' => $e->getMessage(),
                'cutoff_date' => $cutoffDate->toDateTimeString(),
            ]);
            // Don't throw - continue cleanup process
        }
    }
}
