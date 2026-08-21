<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing SystemControlController...\n\n";

try {
    // Test getting cleanup stats
    echo "1. Testing cleanup stats...\n";
    $retentionDays = \App\Models\SystemSetting::get('cleanup_retention_days', 30);
    echo "   Retention days: $retentionDays\n";
    
    $cutoffDate = now()->subDays($retentionDays);
    echo "   Cutoff date: $cutoffDate\n";
    
    // Count alarm_raw
    echo "   Counting alarm_raw...\n";
    $alarmRawTotal = \Illuminate\Support\Facades\DB::table('alarm_raw')->count();
    echo "   Total alarm_raw: $alarmRawTotal\n";
    
    $alarmRawOld = \Illuminate\Support\Facades\DB::table('alarm_raw')
        ->where('created_at', '<', $cutoffDate)
        ->count();
    echo "   Old alarm_raw: $alarmRawOld\n";
    
    // Check if gps_tracks_raw exists
    echo "   Checking gps_tracks_raw table...\n";
    if (\Illuminate\Support\Facades\DB::getSchemaBuilder()->hasTable('gps_tracks_raw')) {
        echo "   gps_tracks_raw exists\n";
        $gpsRawTotal = \Illuminate\Support\Facades\DB::table('gps_tracks_raw')->count();
        echo "   Total gps_tracks_raw: $gpsRawTotal\n";
        
        $gpsRawOld = \Illuminate\Support\Facades\DB::table('gps_tracks_raw')
            ->where('created_at', '<', $cutoffDate)
            ->count();
        echo "   Old gps_tracks_raw: $gpsRawOld\n";
    } else {
        echo "   gps_tracks_raw does NOT exist\n";
    }
    
    echo "\n2. Testing system settings...\n";
    $cleanupEnabled = \App\Models\SystemSetting::get('cleanup_enabled', true);
    echo "   cleanup_enabled: " . ($cleanupEnabled ? 'true' : 'false') . "\n";
    
    $cleanupLastRun = \App\Models\SystemSetting::get('cleanup_last_run');
    echo "   cleanup_last_run: " . ($cleanupLastRun ?? 'NULL') . "\n";
    
    $cleanupSchedule = \App\Models\SystemSetting::get('cleanup_schedule', 'monthly');
    echo "   cleanup_schedule: $cleanupSchedule\n";
    
    echo "\n✅ All tests passed!\n";
    echo "\nIf this script works but page doesn't load, the issue is in the view rendering.\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
