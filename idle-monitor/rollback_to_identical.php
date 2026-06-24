<?php

/**
 * Rollback: Make start_detail = end_detail
 * 
 * This reverses the dur:0 fix and makes both fields identical again
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\IdleAlarm;

echo "╔═══════════════════════════════════════════════════╗\n";
echo "║  ROLLBACK: Make Start/End Details Identical      ║\n";
echo "╚═══════════════════════════════════════════════════╝\n";
echo "\n";

$dryRun = in_array('--dry-run', $argv);

if ($dryRun) {
    echo "⚠️  DRY RUN MODE - No changes will be saved\n\n";
}

// Find records where start_detail has dur:0 (these were modified)
$modifiedRecords = IdleAlarm::whereNotNull('start_detail')
    ->whereNotNull('end_detail')
    ->where('start_detail', 'LIKE', '%dur:0%')
    ->get();

echo "Found {$modifiedRecords->count()} records with dur:0 in start_detail\n\n";

if ($modifiedRecords->isEmpty()) {
    echo "✅ No records to rollback!\n";
    exit(0);
}

$rolled = 0;

foreach ($modifiedRecords as $alarm) {
    try {
        if (!$dryRun) {
            // Make start_detail = end_detail (rollback to original)
            $alarm->update([
                'start_detail' => $alarm->end_detail,
            ]);
        }
        
        $rolled++;
        
        if ($rolled % 1000 === 0) {
            echo "  Progress: {$rolled} records rolled back...\n";
        }
        
    } catch (\Exception $e) {
        // Skip
    }
}

echo "\n";
echo "Rolled back: {$rolled} records\n";

if ($dryRun) {
    echo "⚠️  DRY RUN - No actual changes\n";
} else {
    echo "✅ Changes applied - start_detail now = end_detail\n";
}
