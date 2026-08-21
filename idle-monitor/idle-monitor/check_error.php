<?php

// Script untuk debug 500 error di System Control Center

echo "=== DEBUGGING SYSTEM CONTROL CENTER ERROR ===\n\n";

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    // Test 1: Check if system_settings table exists
    echo "1. Checking system_settings table...\n";
    $exists = DB::getSchemaBuilder()->hasTable('system_settings');
    echo "   Table exists: " . ($exists ? "YES" : "NO") . "\n\n";
    
    if (!$exists) {
        echo "   ERROR: Table tidak ada! Run migration dulu.\n";
        exit(1);
    }
    
    // Test 2: Count records
    echo "2. Counting records in system_settings...\n";
    $count = DB::table('system_settings')->count();
    echo "   Total records: {$count}\n\n";
    
    // Test 3: Get all settings
    echo "3. Getting all settings...\n";
    $settings = DB::table('system_settings')->get();
    foreach ($settings as $setting) {
        echo "   - {$setting->key} = {$setting->value} (type: {$setting->type})\n";
    }
    echo "\n";
    
    // Test 4: Test SystemSetting model
    echo "4. Testing SystemSetting model...\n";
    $enabled = \App\Models\SystemSetting::get('cleanup_enabled');
    echo "   cleanup_enabled = " . var_export($enabled, true) . "\n";
    
    $days = \App\Models\SystemSetting::get('cleanup_retention_days');
    echo "   cleanup_retention_days = " . var_export($days, true) . "\n\n";
    
    // Test 5: Test controller instantiation
    echo "5. Testing SystemControlController...\n";
    $controller = new \App\Http\Controllers\Admin\SystemControlController();
    echo "   Controller instantiated: OK\n\n";
    
    // Test 6: Get cleanup stats
    echo "6. Getting cleanup stats...\n";
    $retentionDays = \App\Models\SystemSetting::getCleanupRetentionDays();
    $cutoffDate = now()->subDays($retentionDays);
    
    $alarmRawOld = DB::table('alarm_raw')
        ->where('created_at', '<', $cutoffDate)
        ->count();
    echo "   alarm_raw old records: {$alarmRawOld}\n";
    
    $hasGpsRaw = DB::getSchemaBuilder()->hasTable('gps_tracks_raw');
    echo "   gps_tracks_raw exists: " . ($hasGpsRaw ? "YES" : "NO") . "\n";
    
    if ($hasGpsRaw) {
        $gpsRawOld = DB::table('gps_tracks_raw')
            ->where('created_at', '<', $cutoffDate)
            ->count();
        echo "   gps_tracks_raw old records: {$gpsRawOld}\n";
    }
    
    echo "\n=== ALL TESTS PASSED ===\n";
    echo "Error mungkin di view blade. Check laravel.log untuk detail.\n";
    
} catch (\Exception $e) {
    echo "\n=== ERROR FOUND ===\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
