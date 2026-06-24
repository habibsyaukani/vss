<?php
/**
 * Fix Duration - Howen Logic
 * 
 * This script fixes duration_seconds using correct Howen logic:
 * 1. If start_detail has dur > 0: USE start_detail
 * 2. If start_detail has dur:0 or empty: USE end_detail
 * 3. If both empty: keep existing value
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\IdleAlarm;

$dryRun = in_array('--dry-run', $argv);
$limit = 10000;

// Check for --limit parameter
foreach ($argv as $i => $arg) {
    if ($arg === '--limit' && isset($argv[$i + 1])) {
        $limit = (int)$argv[$i + 1];
    }
}

echo "🔧 FIX DURATION - Howen Logic\n";
echo "==============================\n\n";
echo "Logic: IF start_detail dur > 0 THEN use start_detail\n";
echo "       ELSE use end_detail\n\n";

if ($dryRun) {
    echo "⚠️  DRY RUN MODE - No changes will be saved\n\n";
}

echo "Processing up to {$limit} records...\n\n";

$fixed = 0;
$skipped = 0;
$alreadyCorrect = 0;

// Get all idle_alarms
$records = IdleAlarm::limit($limit)->get();

echo "Found {$records->count()} idle_alarms to check\n\n";

foreach ($records as $record) {
    try {
        // Extract dur from start_detail
        $durFromStart = 0;
        if (!empty($record->start_detail) && preg_match('/dur:(\d+)/', $record->start_detail, $m)) {
            $durFromStart = (int)$m[1];
        }
        
        // Extract dur from end_detail (fallback if start_detail is dur:0)
        $durFromEnd = 0;
        if (!empty($record->end_detail) && preg_match('/dur:(\d+)/', $record->end_detail, $m)) {
            $durFromEnd = (int)$m[1];
        }
        
        // Howen logic: Use start_detail if > 0, else use end_detail
        $correctDuration = $durFromStart > 0 ? $durFromStart : $durFromEnd;
        
        // If still 0, check alarm_raw
        if ($correctDuration == 0) {
            $alarmRaw = \App\Models\AlarmRaw::where('guid', $record->guid)->first();
            if ($alarmRaw) {
                if (!empty($alarmRaw->alarm_value) && preg_match('/dur:(\d+)/', $alarmRaw->alarm_value, $m2)) {
                    $durFromStartRaw = (int)$m2[1];
                    if ($durFromStartRaw > 0) {
                        $correctDuration = $durFromStartRaw;
                    } else {
                        // Fallback to end_detail from alarm_raw
                        if (!empty($alarmRaw->end_detail) && preg_match('/dur:(\d+)/', $alarmRaw->end_detail, $m3)) {
                            $correctDuration = (int)$m3[1];
                        }
                    }
                }
            }
        }
        
        // Check if current duration_seconds matches
        if ($record->duration_seconds == $correctDuration) {
            $alreadyCorrect++;
            continue; // Already correct, skip
        }
        
        // Skip if we don't have valid dur
        if ($correctDuration == 0) {
            $skipped++;
            continue;
        }
        
        $durMinutes = ceil($correctDuration / 60);
        
        if (!$dryRun) {
            $record->update([
                'duration_seconds' => $correctDuration,
                'duration_minutes' => $durMinutes,
            ]);
        }
        
        $fixed++;
        
        if ($fixed % 100 === 0) {
            echo "  Processed: {$fixed} records fixed, {$alreadyCorrect} already correct...\n";
        }
        
    } catch (\Exception $e) {
        $skipped++;
    }
}

echo "\n";
echo "═══════════════════════════════════\n";
echo "SUMMARY\n";
echo "═══════════════════════════════════\n\n";

if ($dryRun) {
    echo "⚠️  DRY RUN - No actual changes were made\n\n";
}

echo "Total checked: {$records->count()}\n";
echo "✅ Already correct: {$alreadyCorrect}\n";
echo "🔧 Fixed: {$fixed}\n";
echo "⚠️  Skipped: {$skipped}\n\n";

// Show sample of fixed records
if ($fixed > 0 && !$dryRun) {
    echo "Sample fixed records:\n";
    $samples = IdleAlarm::orderBy('updated_at', 'desc')->limit(5)->get();
    foreach ($samples as $s) {
        preg_match('/dur:(\d+)/', $s->start_detail, $m);
        $durStart = $m[1] ?? 'N/A';
        echo "  {$s->device_name}: dur:{$durStart} → duration:{$s->duration_seconds}s\n";
    }
}

echo "\n";
echo "✅ Fix completed!\n";
echo "\n";
