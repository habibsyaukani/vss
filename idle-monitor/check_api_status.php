<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== API STATUS CHECK ===\n\n";

$lastSync = \App\Models\SystemSetting::get('last_alarm_sync');
$currentTime = now();

echo "last_alarm_sync: $lastSync\n";
echo "Current time: " . $currentTime->toDateTimeString() . "\n";

if ($lastSync) {
    $lastSyncTime = \Carbon\Carbon::parse($lastSync);
    $diffMinutes = $currentTime->diffInMinutes($lastSyncTime);
    
    echo "Difference: $diffMinutes minutes ago\n\n";
    
    if ($diffMinutes < 5) {
        echo "✅ Status: CONNECTED (last check < 5 min)\n";
        echo "Badge: success (green)\n";
    } elseif ($diffMinutes < 30) {
        echo "⚠️ Status: WARNING (last check < 30 min)\n";
        echo "Badge: warning (yellow)\n";
    } else {
        echo "❌ Status: DISCONNECTED (last check > 30 min)\n";
        echo "Badge: danger (red)\n";
    }
} else {
    echo "❌ Status: UNKNOWN (no sync data)\n";
}

echo "\n=== RESULT ===\n";
echo "Refresh your System Settings page to see updated status!\n";

