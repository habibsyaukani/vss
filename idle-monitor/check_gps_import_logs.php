<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n=== GPS TRACK IMPORT LOGS (Last 10) ===\n\n";

$logs = \App\Models\ImportLog::where('job_name', 'ImportGpsTrackJob')
    ->orderBy('id', 'desc')
    ->take(10)
    ->get();

if ($logs->isEmpty()) {
    echo "❌ No GPS track import logs found.\n";
    echo "ℹ️  GPS track auto-pull is currently DISABLED in scheduler.\n\n";
    exit;
}

foreach ($logs as $log) {
    $duration = 0;
    if ($log->started_at && $log->finished_at) {
        $duration = $log->started_at->diffInSeconds($log->finished_at);
    }
    
    $recordsPerSecond = $duration > 0 ? round($log->total_record / $duration, 2) : 0;
    
    echo "ID: {$log->id}\n";
    echo "Started:  " . ($log->started_at ? $log->started_at->format('Y-m-d H:i:s') : 'N/A') . "\n";
    echo "Finished: " . ($log->finished_at ? $log->finished_at->format('Y-m-d H:i:s') : 'N/A') . "\n";
    echo "Duration: {$duration} seconds\n";
    echo "Records:  {$log->total_record}\n";
    echo "Speed:    {$recordsPerSecond} records/second\n";
    echo "Status:   {$log->status}\n";
    echo "Message:  " . ($log->message ?? 'N/A') . "\n";
    echo str_repeat('-', 80) . "\n\n";
}

echo "\n=== SUMMARY ===\n";
$avgDuration = $logs->avg(function ($log) {
    if ($log->started_at && $log->finished_at) {
        return $log->started_at->diffInSeconds($log->finished_at);
    }
    return 0;
});

$avgRecords = $logs->avg('total_record');
$totalRecords = $logs->sum('total_record');

echo "Average Duration: " . round($avgDuration, 2) . " seconds\n";
echo "Average Records:  " . round($avgRecords, 2) . " records\n";
echo "Total Records:    {$totalRecords}\n";
echo "\n";
