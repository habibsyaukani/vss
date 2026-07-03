<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\SystemLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CleanupByMonthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $year;
    private int $month;

    /**
     * Create a new job instance.
     *
     * @param int $year Year (e.g., 2026)
     * @param int $month Month (1-12)
     */
    public function __construct(int $year, int $month)
    {
        $this->year = $year;
        $this->month = $month;
    }

    /**
     * Execute the job - Cleanup data untuk bulan tertentu
     */
    public function handle(): void
    {
        $startTime = now();
        
        // Calculate month range
        $monthStart = Carbon::createFromDate($this->year, $this->month, 1)->startOfMonth();
        $monthEnd = Carbon::createFromDate($this->year, $this->month, 1)->endOfMonth();
        
        SystemLogger::info('CLEANUP_BY_MONTH_START', 'Starting cleanup for specific month', [
            'year' => $this->year,
            'month' => $this->month,
            'month_name' => $monthStart->format('F Y'),
            'date_range' => $monthStart->format('Y-m-d') . ' to ' . $monthEnd->format('Y-m-d'),
        ]);

        try {
            // 1. Cleanup alarm_raw for this month
            $alarmDeleted = $this->cleanupAlarmRawByMonth($monthStart, $monthEnd);

            // 2. Cleanup gps_tracks_raw for this month
            $gpsDeleted = $this->cleanupGpsRawByMonth($monthStart, $monthEnd);

            $duration = now()->diffInSeconds($startTime);
            
            SystemLogger::success('CLEANUP_BY_MONTH_COMPLETED', 'Month cleanup completed successfully', [
                'year' => $this->year,
                'month' => $this->month,
                'month_name' => $monthStart->format('F Y'),
                'alarm_raw_deleted' => $alarmDeleted,
                'gps_raw_deleted' => $gpsDeleted,
                'total_deleted' => $alarmDeleted + $gpsDeleted,
                'duration_seconds' => $duration,
            ]);

        } catch (\Exception $e) {
            SystemLogger::error('CLEANUP_BY_MONTH_FAILED', 'Month cleanup failed', [
                'year' => $this->year,
                'month' => $this->month,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Cleanup alarm_raw for specific month
     */
    private function cleanupAlarmRawByMonth(Carbon $monthStart, Carbon $monthEnd): int
    {
        try {
            // Get GUIDs from this month
            $rawGuids = DB::table('alarm_raw')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->where('alarm_type', 32) // Idle alarms
                ->pluck('guid')
                ->toArray();

            if (empty($rawGuids)) {
                SystemLogger::info('CLEANUP_ALARM_BY_MONTH', 'No alarm_raw data for this month', [
                    'month' => $monthStart->format('F Y'),
                ]);
                return 0;
            }

            // Check which ones are already processed
            $processedGuids = DB::table('idle_alarms')
                ->whereIn('guid', $rawGuids)
                ->pluck('guid')
                ->toArray();

            SystemLogger::info('CLEANUP_ALARM_BY_MONTH_VALIDATION', 'Validating month data', [
                'month' => $monthStart->format('F Y'),
                'total_records' => count($rawGuids),
                'already_processed' => count($processedGuids),
                'not_processed_yet' => count($rawGuids) - count($processedGuids),
            ]);

            // Delete only processed data
            $deletedCount = 0;
            if (!empty($processedGuids)) {
                $deletedCount = DB::table('alarm_raw')
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->whereIn('guid', $processedGuids)
                    ->delete();
            }

            // Delete non-idle alarms (type != 32) from this month
            $nonIdleDeleted = DB::table('alarm_raw')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->where('alarm_type', '!=', 32)
                ->delete();

            $totalDeleted = $deletedCount + $nonIdleDeleted;

            SystemLogger::success('CLEANUP_ALARM_BY_MONTH', 'Cleaned up alarm_raw for month', [
                'month' => $monthStart->format('F Y'),
                'deleted_idle' => $deletedCount,
                'deleted_non_idle' => $nonIdleDeleted,
                'total_deleted' => $totalDeleted,
            ]);

            return $totalDeleted;

        } catch (\Exception $e) {
            SystemLogger::error('CLEANUP_ALARM_BY_MONTH_FAILED', 'Failed to cleanup alarm_raw for month', [
                'month' => $monthStart->format('F Y'),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Cleanup gps_tracks_raw for specific month
     */
    private function cleanupGpsRawByMonth(Carbon $monthStart, Carbon $monthEnd): int
    {
        try {
            // Check if table exists
            if (!DB::getSchemaBuilder()->hasTable('gps_tracks_raw')) {
                SystemLogger::info('CLEANUP_GPS_BY_MONTH', 'Table gps_tracks_raw does not exist', []);
                return 0;
            }

            // Count records in this month
            $countInMonth = DB::table('gps_tracks_raw')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();

            if ($countInMonth === 0) {
                SystemLogger::info('CLEANUP_GPS_BY_MONTH', 'No gps_tracks_raw data for this month', [
                    'month' => $monthStart->format('F Y'),
                ]);
                return 0;
            }

            // Sample validation (check if data is processed)
            $sample = DB::table('gps_tracks_raw')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->select('device_id', 'gps_time')
                ->limit(1000)
                ->get();

            $validatedCount = 0;
            foreach ($sample as $raw) {
                $exists = DB::table('gps_tracks')
                    ->where('device_id', $raw->device_id)
                    ->where('gps_time', $raw->gps_time)
                    ->exists();
                
                if ($exists) {
                    $validatedCount++;
                }
            }

            $sampleSize = $sample->count();
            $processedPercentage = $sampleSize > 0 ? ($validatedCount / $sampleSize) * 100 : 0;

            SystemLogger::info('CLEANUP_GPS_BY_MONTH_VALIDATION', 'Validating GPS data for month', [
                'month' => $monthStart->format('F Y'),
                'total_records' => $countInMonth,
                'sample_size' => $sampleSize,
                'validated' => $validatedCount,
                'processed_percentage' => round($processedPercentage, 2) . '%',
            ]);

            // Delete if >95% processed
            if ($processedPercentage >= 95) {
                $deletedCount = DB::table('gps_tracks_raw')
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->delete();

                SystemLogger::success('CLEANUP_GPS_BY_MONTH', 'Cleaned up gps_tracks_raw for month', [
                    'month' => $monthStart->format('F Y'),
                    'deleted_count' => $deletedCount,
                    'validation_passed' => true,
                ]);

                return $deletedCount;
            } else {
                SystemLogger::warning('CLEANUP_GPS_BY_MONTH_SKIPPED', 'Skipped - data not fully processed', [
                    'month' => $monthStart->format('F Y'),
                    'processed_percentage' => round($processedPercentage, 2) . '%',
                    'required' => 95,
                ]);

                return 0;
            }

        } catch (\Exception $e) {
            SystemLogger::error('CLEANUP_GPS_BY_MONTH_FAILED', 'Failed to cleanup gps_tracks_raw for month', [
                'month' => $monthStart->format('F Y'),
                'error' => $e->getMessage(),
            ]);
            // Don't throw - continue
            return 0;
        }
    }
}
