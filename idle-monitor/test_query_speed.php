<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing query speeds...\n\n";

$retentionDays = 30;
$cutoffDate = now()->subDays($retentionDays);

// Test 1: Count alarm_raw (all)
echo "1. Counting ALL alarm_raw records...\n";
$start = microtime(true);
$count = \Illuminate\Support\Facades\DB::table('alarm_raw')->count();
$time = round((microtime(true) - $start) * 1000, 2);
echo "   Result: $count records in {$time}ms\n\n";

// Test 2: Count alarm_raw (old)
echo "2. Counting OLD alarm_raw records (before $cutoffDate)...\n";
$start = microtime(true);
$count = \Illuminate\Support\Facades\DB::table('alarm_raw')
    ->where('created_at', '<', $cutoffDate)
    ->count();
$time = round((microtime(true) - $start) * 1000, 2);
echo "   Result: $count records in {$time}ms\n\n";

// Test 3: Count gps_tracks_raw (all)
echo "3. Counting ALL gps_tracks_raw records...\n";
$start = microtime(true);
$count = \Illuminate\Support\Facades\DB::table('gps_tracks_raw')->count();
$time = round((microtime(true) - $start) * 1000, 2);
echo "   Result: $count records in {$time}ms\n\n";

// Test 4: Count gps_tracks_raw (old)
echo "4. Counting OLD gps_tracks_raw records (before $cutoffDate)...\n";
$start = microtime(true);
$count = \Illuminate\Support\Facades\DB::table('gps_tracks_raw')
    ->where('created_at', '<', $cutoffDate)
    ->count();
$time = round((microtime(true) - $start) * 1000, 2);
echo "   Result: $count records in {$time}ms\n\n";

echo "========================================\n";
echo "If any query takes > 5000ms (5 seconds), that's the problem!\n";
