<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemHealthChecker;
use Illuminate\Http\Request;

class SystemHealthController extends Controller
{
    /**
     * Show system health check page
     */
    public function index()
    {
        try {
            $healthChecker = new SystemHealthChecker();
            $healthData = $healthChecker->runAllChecks();

            // Check if healing_logs table exists
            $tableExists = \Illuminate\Support\Facades\Schema::hasTable('healing_logs');
            
            if ($tableExists) {
                // Get healing statistics and recent logs
                $healer = new \App\Services\SystemHealer();
                $healingStats = $healer->getHealingStats();
                
                // Get recent healing logs (last 50)
                $healingLogs = \App\Models\HealingLog::orderBy('created_at', 'desc')
                    ->limit(50)
                    ->get();
            } else {
                // Table doesn't exist yet
                $healingStats = [
                    'total_attempts' => 0,
                    'successful' => 0,
                    'failed' => 0,
                    'success_rate' => 0,
                    'average_healing_time_ms' => 0,
                ];
                $healingLogs = collect(); // Empty collection
            }

            return view('admin.system-health', array_merge($healthData, [
                'healing_stats' => $healingStats,
                'healing_logs' => $healingLogs,
                'table_exists' => $tableExists,
            ]));
            
        } catch (\Exception $e) {
            // If error, show basic health check without healing features
            return view('admin.system-health', [
                'health_score' => 0,
                'status' => 'critical',
                'issues' => [[
                    'title' => '❌ System Health Check Error',
                    'message' => $e->getMessage(),
                    'troubleshooting' => [
                        '1. Check if database is running',
                        '2. Run migration: php artisan migrate',
                        '3. Check storage/logs/laravel.log for details',
                    ],
                    'severity' => 'critical',
                ]],
                'warnings' => [],
                'checks' => [],
                'healing_stats' => [
                    'total_attempts' => 0,
                    'successful' => 0,
                    'failed' => 0,
                    'success_rate' => 0,
                    'average_healing_time_ms' => 0,
                ],
                'healing_logs' => collect(),
                'table_exists' => false,
            ]);
        }
    }

    /**
     * Get health data via AJAX (for auto-refresh)
     */
    public function checkHealth()
    {
        $healthChecker = new SystemHealthChecker();
        $healthData = $healthChecker->runAllChecks();

        return response()->json($healthData);
    }

    /**
     * Manual heal specific issue type
     */
    public function manualHeal(Request $request)
    {
        $issueType = $request->input('issue_type');
        
        try {
            $healer = new \App\Services\SystemHealer();
            
            $result = match($issueType) {
                'api_token' => $healer->healAPIToken(),
                'queue_worker_inactive' => $healer->healQueueWorker(),
                'failed_jobs' => $healer->healFailedJobs(),
                default => [
                    'success' => false,
                    'message' => 'Unknown issue type or not healable automatically'
                ]
            };
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Healing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get healing logs (for history display)
     */
    public function getHealingLogs(Request $request)
    {
        try {
            $limit = $request->input('limit', 50);
            
            $logs = \App\Models\HealingLog::orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function($log) {
                    return [
                        'id' => $log->id,
                        'issue_type' => $log->issue_type,
                        'severity' => $log->severity,
                        'problem' => $log->problem_description,
                        'action' => $log->healing_action,
                        'status' => $log->status,
                        'result' => $log->result_message,
                        'detected_at' => $log->detected_at->format('Y-m-d H:i:s'),
                        'healed_at' => $log->healed_at ? $log->healed_at->format('Y-m-d H:i:s') : null,
                        'execution_time_ms' => $log->execution_time_ms,
                        'time_ago' => $log->created_at->diffForHumans(),
                    ];
                });
            
            return response()->json([
                'success' => true,
                'logs' => $logs
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch logs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Run database migration for healing_logs table
     */
    public function runMigration()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', [
                '--path' => 'database/migrations/2026_06_27_100000_create_healing_logs_table.php',
                '--force' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Healing logs table created successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Migration failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
