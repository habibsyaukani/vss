<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DATA FLOW INVESTIGATION ===\n\n";

// Check last 5 minutes activity
echo "--- Activity in Last 5 Minutes ---\n";

// 1. Check alarm_raw (new data from API)
$recentAlarmRaw = DB::table('alarm_raw')
    ->where('created_at', '>=', now()->subMinutes(5))
    ->count();
echo "New alarm_raw records (last 5 min): $recentAlarmRaw\n";

// 2. Check idle_alarms (processed data)
$recentIdleAlarms = DB::table('idle_alarms')
    ->where('created_at', '>=', now()->subMinutes(5))
    ->count();
echo "New idle_alarms records (last 5 min): $recentIdleAlarms\n";

// 3. Check import_logs (job activity)
$recentImportLogs = DB::table('import_logs')
    ->where('started_at', '>=', now()->subMinutes(5))
    ->get(['id', 'job_name', 'status', 'total_record', 'message', 'started_at']);

echo "\nRecent Import Logs (last 5 min): " . $recentImportLogs->count() . " jobs\n";
foreach ($recentImportLogs as $log) {
    echo sprintf(
        "  [%d] %s | Status: %s | Records: %d | %s\n  Message: %s\n",
        $log->id,
        $log->job_name,
        $log->status,
        $log->total_record,
        $log->started_at,
        $log->message ?? 'N/A'
    );
}

echo "\n--- System Status ---\n";

// Check system settings
$settings = [
    'last_alarm_sync' => DB::table('system_settings')->where('key', 'last_alarm_sync')->value('value'),
    'realtime_pull_status' => DB::table('system_settings')->where('key', 'realtime_pull_status')->value('value'),
    'realtime_pull_last_success_at' => DB::table('system_settings')->where('key', 'realtime_pull_last_success_at')->value('value'),
    'queue_worker_status' => DB::table('system_settings')->where('key', 'queue_worker_status')->value('value'),
];

foreach ($settings as $key => $value) {
    echo "  $key: $value\n";
}

echo "\n--- Recent alarm_raw (Last 10) ---\n";
$recent = DB::table('alarm_raw')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get(['id', 'guid', 'device_name', 'alarm_type', 'alarm_state', 'start_time', 'created_at']);

foreach ($recent as $alarm) {
    echo sprintf(
        "  [%d] %s | Device: %s | Type: %s | State: %s | Start: %s | Created: %s\n",
        $alarm->id,
        substr($alarm->guid, 0, 20) . '...',
        $alarm->device_name,
        $alarm->alarm_type,
        $alarm->alarm_state,
        $alarm->start_time,
        $alarm->created_at
    );
}

echo "\n--- Jobs in Queue ---\n";
$queuedJobs = DB::table('jobs')->count();
echo "Pending jobs in queue: $queuedJobs\n";

if ($queuedJobs > 0) {
    $jobs = DB::table('jobs')
        ->orderBy('id', 'desc')
        ->limit(5)
        ->get(['id', 'queue', 'payload', 'attempts', 'created_at']);
    
    foreach ($jobs as $job) {
        $payload = json_decode($job->payload, true);
        $jobName = $payload['displayName'] ?? 'Unknown';
        echo sprintf("  [%d] %s | Queue: %s | Attempts: %d | Created: %s\n",
            $job->id,
            $jobName,
            $job->queue,
            $job->attempts,
            date('Y-m-d H:i:s', $job->created_at)
        );
    }
}

echo "\n--- Failed Jobs ---\n";
$failedJobs = DB::table('failed_jobs')
    ->where('failed_at', '>=', now()->subHours(1))
    ->count();
echo "Failed jobs (last hour): $failedJobs\n";

if ($failedJobs > 0) {
    echo "⚠️ WARNING: Some jobs are failing!\n";
    $failed = DB::table('failed_jobs')
        ->orderBy('id', 'desc')
        ->limit(3)
        ->get(['id', 'queue', 'exception', 'failed_at']);
    
    foreach ($failed as $job) {
        echo sprintf("\n  [%d] Queue: %s | Failed: %s\n  Error: %s\n",
            $job->id,
            $job->queue,
            $job->failed_at,
            substr($job->exception, 0, 200) . '...'
        );
    }
}

echo "\n=== DIAGNOSIS ===\n";

if ($recentAlarmRaw == 0) {
    echo "❌ ISSUE: No new data from Howen API in last 5 minutes\n";
    echo "   → Check if Realtime Pull is actually running\n";
    echo "   → Check Howen API credentials\n";
    echo "   → Check network connectivity\n";
} else {
    echo "✅ GOOD: New data is coming from API ($recentAlarmRaw records)\n";
}

if ($recentIdleAlarms == 0 && $recentAlarmRaw > 0) {
    echo "⚠️ WARNING: New alarm_raw but no new idle_alarms\n";
    echo "   → This is NORMAL if alarms don't meet criteria (end_speed=0, etc)\n";
} elseif ($recentIdleAlarms > 0) {
    echo "✅ GOOD: New idle alarms are being processed ($recentIdleAlarms records)\n";
}

if ($queuedJobs > 100) {
    echo "⚠️ WARNING: Queue backlog is high ($queuedJobs jobs)\n";
    echo "   → Queue worker might be slow or stopped\n";
}

if ($failedJobs > 0) {
    echo "❌ ISSUE: Jobs are failing ($failedJobs in last hour)\n";
    echo "   → Check error logs above\n";
}

$lastSync = $settings['last_alarm_sync'] ?? null;
if ($lastSync) {
    $lastSyncTime = new DateTime($lastSync);
    $now = new DateTime();
    $diff = $now->diff($lastSyncTime);
    $minutesAgo = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
    
    echo "\nLast API sync: $minutesAgo minutes ago\n";
    if ($minutesAgo > 10) {
        echo "⚠️ WARNING: Last sync was more than 10 minutes ago!\n";
        echo "   → Realtime pull might not be working\n";
    }
}

echo "\n=== END DIAGNOSIS ===\n";
