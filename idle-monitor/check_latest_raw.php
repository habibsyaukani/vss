<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== LATEST ALARM_RAW CHECK ===\n\n";

$latest = DB::table('alarm_raw')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get(['id', 'guid', 'device_name', 'alarm_type', 'alarm_state', 'start_time', 'end_time', 'created_at', 'updated_at']);

echo "Latest 10 alarm_raw records:\n\n";
foreach ($latest as $alarm) {
    echo sprintf(
        "[%d] %s\n  Device: %s | Type: %s | State: %s\n  Start: %s | End: %s\n  Created: %s | Updated: %s\n\n",
        $alarm->id,
        substr($alarm->guid, 0, 30) . '...',
        $alarm->device_name,
        $alarm->alarm_type,
        $alarm->alarm_state,
        $alarm->start_time,
        $alarm->end_time ?? 'NULL',
        $alarm->created_at,
        $alarm->updated_at
    );
}

// Check when last data came in
$lastCreated = DB::table('alarm_raw')
    ->orderBy('created_at', 'desc')
    ->first(['created_at']);

if ($lastCreated) {
    $createdAt = new DateTime($lastCreated->created_at);
    $now = new DateTime();
    $diff = $now->diff($createdAt);
    $minutesAgo = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
    
    echo "Last alarm_raw created: $minutesAgo minutes ago ({$lastCreated->created_at})\n";
    
    if ($minutesAgo > 30) {
        echo "⚠️ WARNING: No new data from Howen API for $minutesAgo minutes\n";
        echo "This might indicate:\n";
        echo "  - No new idle alarms occurred\n";
        echo "  - Howen API is not returning new data\n";
        echo "  - API authentication issue\n";
    } else {
        echo "✅ Data is relatively fresh (< 30 min ago)\n";
    }
}

echo "\n=== CONCLUSION ===\n";
echo "If last created_at is many hours ago:\n";
echo "  → Howen API is NOT returning new alarm data\n";
echo "  → This is likely NORMAL if no new idle events occurred\n";
echo "  → System is working, just waiting for new idle alarms from field\n";

