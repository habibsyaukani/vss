<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ImportAlarmPageJob;
use App\Jobs\ProcessIdleAlarmJob;
use App\Models\ImportLog;
use App\Models\AlarmRaw;
use App\Models\IdleAlarm;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HistoricalDataController extends Controller
{
    /**
     * POST /api/admin/pull-idle-alarms-range
     * Pull idle alarm data for a specific date range
     * 
     * Body:
     * {
     *   "start_date": "2026-05-01",
     *   "end_date": "2026-06-04",
     *   "pages": 7,
     *   "wait": false
     * }
     */
    public function pullIdleAlarmsDateRange(Request $request)
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date_format:Y-m-d',
                'end_date' => 'required|date_format:Y-m-d',
                'pages' => 'nullable|integer|min:1|max:50',
                'wait' => 'nullable|boolean',
            ]);

            $startDate = Carbon::createFromFormat('Y-m-d', $validated['start_date'])->startOfDay();
            $endDate = Carbon::createFromFormat('Y-m-d', $validated['end_date'])->endOfDay();
            $pages = $validated['pages'] ?? 7;
            $wait = $validated['wait'] ?? false;

            if ($startDate->isAfter($endDate)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Start date cannot be after end date',
                ], 422);
            }

            $days = $startDate->diffInDays($endDate) + 1;

            \Illuminate\Support\Facades\Log::info('Historical data pull initiated', [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'days' => $days,
                'pages' => $pages,
                'wait' => $wait,
            ]);

            // Dispatch import jobs
            $jobIds = [];
            for ($pageNum = 1; $pageNum <= $pages; $pageNum++) {
                $job = new ImportAlarmPageJob(
                    $pageNum,
                    200, // pageCount
                    $startDate->toDateTimeString(),
                    $endDate->toDateTimeString()
                );

                if ($wait) {
                    // Execute immediately (blocking)
                    $job->handle();
                } else {
                    // Dispatch to queue (non-blocking)
                    dispatch($job);
                }

                $jobIds[] = $pageNum;
            }

            // Process idle alarms
            if ($wait) {
                $processJob = new ProcessIdleAlarmJob();
                $processJob->handle();
            } else {
                dispatch(new ProcessIdleAlarmJob());
            }

            // Get statistics
            $alarmRawCount = AlarmRaw::where('created_at', '>=', $startDate)
                ->where('created_at', '<=', $endDate)
                ->count();

            $idleAlarmsCount = IdleAlarm::where('created_at', '>=', $startDate)
                ->where('created_at', '<=', $endDate)
                ->count();

            $type32Count = AlarmRaw::where('alarm_type', 32)
                ->where('created_at', '>=', $startDate)
                ->where('created_at', '<=', $endDate)
                ->count();

            return response()->json([
                'success' => true,
                'message' => $wait ? 'Data pull completed' : 'Data pull queued',
                'data' => [
                    'date_range' => [
                        'start' => $startDate->format('Y-m-d'),
                        'end' => $endDate->format('Y-m-d'),
                        'days' => $days,
                    ],
                    'jobs_dispatched' => [
                        'import_pages' => count($jobIds),
                        'process_idle' => 1,
                    ],
                    'statistics' => [
                        'alarm_raw_total' => $alarmRawCount,
                        'type_32_alarms' => $type32Count,
                        'idle_alarms_processed' => $idleAlarmsCount,
                    ],
                    'mode' => $wait ? 'synchronous' : 'asynchronous',
                ],
                'timestamp' => now()->toIso8601String(),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Historical data pull error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Pull failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/admin/historical-data-status
     * Get status of historical data pulls
     * 
     * Query params:
     * - start_date (optional)
     * - end_date (optional)
     */
    public function status(Request $request)
    {
        try {
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');

            $query = ImportLog::where('job_name', 'ImportAlarmPageJob');

            if ($startDate) {
                $query->where('created_at', '>=', Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay());
            }

            if ($endDate) {
                $query->where('created_at', '<=', Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay());
            }

            $logs = $query->latest()->limit(20)->get();

            $totalRecords = $logs->sum('total_record');
            $completedJobs = $logs->where('status', 'completed')->count();
            $failedJobs = $logs->where('status', 'failed')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'recent_jobs' => $logs->map(fn($log) => [
                        'id' => $log->id,
                        'job_name' => $log->job_name,
                        'status' => $log->status,
                        'total_record' => $log->total_record,
                        'message' => $log->message,
                        'started_at' => $log->started_at,
                        'finished_at' => $log->finished_at,
                    ]),
                    'summary' => [
                        'total_records_imported' => $totalRecords,
                        'completed_jobs' => $completedJobs,
                        'failed_jobs' => $failedJobs,
                    ],
                ],
                'timestamp' => now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Status check failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
