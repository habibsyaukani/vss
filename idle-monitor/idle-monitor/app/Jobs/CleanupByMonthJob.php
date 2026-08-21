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
use Illuminate\Support\Facades\Cache;

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
        
        $cacheKey = "cleanup_progress_{$this->year}_{$this->month}";
        Cache::put($cacheKey, [
            'status' => 'Starting...',
            'percentage' => 0,
            'details' => 'Initializing cleanup job.'
        ], now()->addHours(2));

        SystemLogger::info('CLEANUP_BY_MONTH_START', 'Starting cleanup for specific month', [
            'year' => $this->year,
            'month' => $this->month,
            'month_name' => $monthStart->format('F Y'),
            'date_range' => $monthStart->format('Y-m-d') . ' to ' . $monthEnd->format('Y-m-d'),
        ]);

        try {
            Cache::put($cacheKey, [
                'status' => 'Processing...',
                'percentage' => 5,
                'details' => 'Cleaning up alarm_raw...'
            ], now()->addHours(2));
            
            // 1. Cleanup alarm_raw for this month
            $alarmDeleted = $this->cleanupAlarmRawByMonth($monthStart, $monthEnd, $cacheKey);

            Cache::put($cacheKey, [
                'status' => 'Processing...',
                'percentage' => 50,
                'details' => 'Cleaning up gps_tracks_raw...'
            ], now()->addHours(2));
            
            // 2. Cleanup gps_tracks_raw for this month
            $gpsDeleted = $this->cleanupGpsRawByMonth($monthStart, $monthEnd, $cacheKey);

            $duration = now()->diffInSeconds($startTime);
            
            Cache::put($cacheKey, [
                'status' => 'Completed',
                'percentage' => 100,
                'details' => "Cleanup completed. Deleted $alarmDeleted alarms and $gpsDeleted GPS tracks."
            ], now()->addHours(2));
            
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
            Cache::put($cacheKey, [
                'status' => 'Error',
                'percentage' => 0,
                'details' => "Error: " . $e->getMessage()
            ], now()->addHours(2));
            
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
    private function cleanupAlarmRawByMonth(Carbon $monthStart, Carbon $monthEnd, string $cacheKey): int
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

            // Chunk to avoid SQL placeholder limits
            $guidChunks = array_chunk($rawGuids, 1000);
            $deletedCount = 0;
            $totalProcessedGuids = 0;
            
            $totalChunks = count($guidChunks);
            $currentChunk = 0;

            foreach ($guidChunks as $chunk) {
                $currentChunk++;
                $progressPct = 5 + round(($currentChunk / $totalChunks) * 40); // 5% to 45%
                
                Cache::put($cacheKey, [
                    'status' => 'Processing...',
                    'percentage' => $progressPct,
                    'details' => "Cleaning up alarm_raw... (Chunk $currentChunk of $totalChunks)"
                ], now()->addHours(2));
                
                // Check which ones are already processed in this chunk
                $processedGuids = DB::table('idle_alarms')
                    ->whereIn('guid', $chunk)
                    ->pluck('guid')
                    ->toArray();
                
                $totalProcessedGuids += count($processedGuids);

                // Delete only processed data for this chunk
                if (!empty($processedGuids)) {
                    $deleted = DB::table('alarm_raw')
                        ->whereBetween('created_at', [$monthStart, $monthEnd])
                        ->whereIn('guid', $processedGuids)
                        ->delete();
                    $deletedCount += $deleted;
                }
            }

            SystemLogger::info('CLEANUP_ALARM_BY_MONTH_VALIDATION', 'Validating month data', [
                'month' => $monthStart->format('F Y'),
                'total_records' => count($rawGuids),
                'already_processed' => $totalProcessedGuids,
                'not_processed_yet' => count($rawGuids) - $totalProcessedGuids,
            ]);

            // Delete non-idle alarms (type != 32) from this month
            $nonIdleDeleted = DB::table('alarm_raw')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->where('alarm_type', '!=', 32)
                ->delete();

            // Delete skipped/invalid idle alarms that will never be processed (e.g., end_speed = 0, alarm_state != 0)
            $skippedDeleted = DB::table('alarm_raw')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->where('alarm_type', 32)
                ->where(function ($query) {
                    $query->where('alarm_state', '!=', 0)
                          ->orWhere('end_speed', '<=', 0)
                          ->orWhereNull('end_time')
                          ->orWhere('end_time', '')
                          ->orWhereNull('duration_seconds')
                          ->orWhere('duration_seconds', '<=', 0);
                })
                ->delete();

            $totalDeleted = $deletedCount + $nonIdleDeleted + $skippedDeleted;

            SystemLogger::success('CLEANUP_ALARM_BY_MONTH', 'Cleaned up alarm_raw for month', [
                'month' => $monthStart->format('F Y'),
                'deleted_idle_processed' => $deletedCount,
                'deleted_idle_skipped' => $skippedDeleted,
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
    private function cleanupGpsRawByMonth(Carbon $monthStart, Carbon $monthEnd, string $cacheKey): int
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
            // 1. Pluck 1,000 IDs first (fast covering index scan on created_at)
            $sampleIds = DB::table('gps_tracks_raw')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->limit(1000)
                ->pluck('id')
                ->toArray();

            $sampleSize = count($sampleIds);
            $validatedCount = 0;

            if ($sampleSize > 0) {
                Cache::put($cacheKey, [
                    'status' => 'Validating...',
                    'percentage' => 50,
                    'details' => "Validating GPS tracks... (Checking $sampleSize samples)"
                ], now()->addHours(2));

                // 2. Fetch device_id and gps_time for these 1,000 IDs (fast primary key lookup)
                $sample = DB::table('gps_tracks_raw')
                    ->whereIn('id', $sampleIds)
                    ->select('device_id', 'gps_time')
                    ->get();

                // 3. Batch check their existence in gps_tracks in a single query with OR clauses
                $grouped = $sample->groupBy('device_id');
                $query = DB::table('gps_tracks');
                $first = true;
                
                foreach ($grouped as $deviceId => $items) {
                    $gpsTimes = $items->pluck('gps_time')->toArray();
                    if ($first) {
                        $query->where(function($q) use ($deviceId, $gpsTimes) {
                            $q->where('device_id', $deviceId)->whereIn('gps_time', $gpsTimes);
                        });
                        $first = false;
                    } else {
                        $query->orWhere(function($q) use ($deviceId, $gpsTimes) {
                            $q->where('device_id', $deviceId)->whereIn('gps_time', $gpsTimes);
                        });
                    }
                }
                
                $validatedCount = $query->count();
            }

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
                Cache::put($cacheKey, [
                    'status' => 'Deleting...',
                    'percentage' => 80,
                    'details' => 'Deleting gps_tracks_raw... this may take a while.'
                ], now()->addHours(2));
                
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
