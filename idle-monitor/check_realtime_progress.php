<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== REALTIME DATA PROGRESS CHECK ===\n\n";

// 1. Check current total
$currentTotal = DB::table('idle_alarms')->count();
echo "Current total idle_alarms: $currentTotal\n";

// 2. Check data added in last 10 minutes
$last10min = DB::table('idle_alarms')
    ->where('created_at', '>=', now()->subMinutes(10))
    ->count();
echo "Added in last 10 minutes: $last10min\n";

// 3. Check data for today
$today = date('Y-m-d');
$todayCount = DB::table('idle_alarms')
    ->whereDate('starting_time', $today)
    ->count();
echo "Alarms for today ($today): $todayCount\n";

// 4. Check latest created_at
$latest = DB::table('idle_alarms')
    ->orderBy('created_at', 'desc')
    ->first(['id', 'device_name', 'starting_time', 'created_at']);

if ($latest) {
    echo "\nLatest alarm added:\n";
    echo "  ID: {$latest->id}\n";
    echo "  Device: {$latest->device_name}\n";
    echo "  Start time: {$latest->starting_time}\n";
    echo "  Created at: {$latest->created_at}\n";
    
    $createdAt = new DateTime($latest->created_at);
    $now = new DateTime();
    $diff = $now->diff($createdAt);
    $minutesAgo = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
    echo "  Time ago: $minutesAgo minutes ago\n";
}

// 5. Check realtime pull status
echo "\n--- Realtime Pull Status ---\n";
$realtimeStatus = DB::table('system_settings')
    ->whereIn('key', ['realtime_pull_status', 'realtime_pull_last_success_at', 'realtime_pull_last_duration'])
    ->pluck('value', 'key');

foreach ($realtimeStatus as $key => $value) {
    echo "  $key: $value\n";
}

// 6. Check recent import logs
echo "\n--- Recent Import Activity (Last 5 minutes) ---\n";
$recentLogs = DB::table('import_logs')
    ->where('started_at', '>=', now()->subMinutes(5))
    ->orderBy('id', 'desc')
    ->get(['job_name', 'status', 'total_record', 'message', 'started_at']);

if ($recentLogs->count() > 0) {
    foreach ($recentLogs as $log) {
        echo sprintf(
            "  %s | %s | Records: %d | %s\n",
            $log->job_name,
            $log->status,
            $log->total_record,
            $log->started_at
        );
    }
} else {
    echo "  No import activity in last 5 minutes\n";
}

// 7. Check alarm_raw for new data
echo "\n--- New Data in alarm_raw ---\n";
$newAlarmRaw = DB::table('alarm_raw')
    ->where('created_at', '>=', now()->subMinutes(10))
    ->count();
echo "New alarm_raw (last 10 min): $newAlarmRaw\n";

// 8. Check if ProcessIdleAlarmJob is running
echo "\n--- Queue Status ---\n";
$queuedJobs = DB::table('jobs')->count();
echo "Jobs in queue: $queuedJobs\n";

$runningProcessJob = DB::table('import_logs')
    ->where('job_name', 'ProcessIdleAlarmJob')
    ->where('status', 'running')
    ->count();
echo "ProcessIdleAlarmJob running: $runningProcessJob\n";

// 9. Check for stuck jobs
$stuckJobs = DB::table('import_logs')
    ->where('status', 'running')
    ->where('started_at', '<', now()->subMinutes(30))
    ->get(['id', 'job_name', 'started_at']);

if ($stuckJobs->count() > 0) {
    echo "\n⚠️ WARNING: Found stuck jobs (running > 30 min):\n";
    foreach ($stuckJobs as $job) {
        echo "  [#{$job->id}] {$job->job_name} - Started: {$job->started_at}\n";
    }
}

// 10. Diagnosis
echo "\n=== DIAGNOSIS ===\n";

if ($last10min > 0) {
    echo "✅ Data IS being added ($last10min new records in last 10 min)\n";
} else {
    echo "❌ NO new data in last 10 minutes\n";
    
    if ($newAlarmRaw > 0) {
        echo "  → alarm_raw has new data ($newAlarmRaw records)\n";
        echo "  → But ProcessIdleAlarmJob is not processing them\n";
        echo "  → Check if Queue Worker is running\n";
    } else {
        echo "  → No new alarm_raw data either\n";
        echo "  → Check if Realtime Pull is actually fetching from API\n";
    }
}

if ($minutesAgo > 10) {
    echo "⚠️ Last alarm was added $minutesAgo minutes ago\n";
    echo "  → Data flow might be stopped\n";
}

echo "\n=== RECOMMENDATION ===\n";
if ($last10min == 0 && $newAlarmRaw == 0) {
    echo "1. Check if Realtime Pull process is running\n";
    echo "2. Check Howen API connectivity\n";
    echo "3. Check Laravel logs for errors\n";
} elseif ($last10min == 0 && $newAlarmRaw > 0) {
    echo "1. Check if Queue Worker is running\n";
    echo "2. Restart Queue Worker if needed\n";
    echo "3. Check for stuck ProcessIdleAlarmJob\n";
}

