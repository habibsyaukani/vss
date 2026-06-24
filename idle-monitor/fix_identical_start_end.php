<?php

/**
 * Fix Identical Start/End Details
 * 
 * This script fixes idle_alarms where start_detail = end_detail
 * by creating synthetic start_detail with dur:0
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\IdleAlarm;
use App\Models\AlarmRaw;

echo "╔═══════════════════════════════════════════════════╗\n";
echo "║  FIX: Identical Start/End Details                ║\n";
echo "╚═══════════════════════════════════════════════════╝\n";
echo "\n";

$dryRun = in_array('--dry-run', $argv);
$limit = 50000; // Process all

if ($dryRun) {
    echo "⚠️  DRY RUN MODE - No changes will be saved\n\n";
}

// Find records where start_detail = end_detail (identical)
$identicalRecords = IdleAlarm::whereNotNull('start_detail')
    ->whereNotNull('end_detail')
    ->whereRaw('start_detail = end_detail')
    ->limit($limit)
    ->get();

echo "Found {$identicalRecords->count()} records with identical start_detail and end_detail\n\n";

if ($identicalRecords->isEmpty()) {
    echo "✅ No records to fix!\n";
    exit(0);
}

$fixed = 0;
$skipped = 0;

foreach ($identicalRecords as $alarm) {
    try {
        $endDetail = $alarm->end_detail;
        
        // Create synthetic start_detail by replacing dur value with 0
        $syntheticStartDetail = preg_replace('/dur[:\s]*\d+/', 'dur:0', $endDetail);
        
        if ($syntheticStartDetail === $endDetail) {
            // No dur found, skip
            $skipped++;
            continue;
        }
        
        if (!$dryRun) {
            $alarm->update([
                'start_detail' => $syntheticStartDetail,
            ]);
        }
        
        $fixed++;
        
        if ($fixed % 1000 === 0) {
            echo "  Progress: {$fixed} records processed...\n";
        }
        
    } catch (\Exception $e) {
        $skipped++;
    }
}

echo "\n";
echo "╔═══════════════════════════════════════╗\n";
echo "║            SUMMARY                    ║\n";
echo "╚═══════════════════════════════════════╝\n";
echo "\n";
echo "Fixed:   {$fixed}\n";
echo "Skipped: {$skipped}\n";
echo "\n";

if ($dryRun) {
    echo "⚠️  DRY RUN - No actual changes were made\n";
    echo "Run without --dry-run to apply changes\n";
} else {
    echo "✅ Changes applied to database\n";
}
