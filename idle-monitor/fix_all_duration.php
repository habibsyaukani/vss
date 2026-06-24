<?php
/**
 * Fix ALL Duration - Batch Processing
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\IdleAlarm;

$dryRun = in_array('--dry-run', $argv);

echo "🔧 FIX ALL DURATION - Batch Processing\n";
echo "======================================\n\n";
echo "Howen Logic:\n";
echo "  IF start_detail dur > 0 → USE start_detail\n";
echo "  ELSE → USE end_detail\n\n";

if ($dryRun) {
    echo "⚠️  DRY RUN MODE\n\n";
}

$fixed = 0;
$alreadyCorrect = 0;
$batchSize = 1000;
$offset = 0;

$total = IdleAlarm::count();
echo "Total records to check: {$total}\n\n";

while (true) {
    $records = IdleAlarm::skip($offset)->take($batchSize)->get();
    
    if ($records->isEmpty()) {
        break;
    }
    
    foreach ($records as $record) {
        // Extract dur from start_detail
        $durFromStart = 0;
        if (!empty($record->start_detail) && preg_match('/dur:(\d+)/', $record->start_detail, $m)) {
            $durFromStart = (int)$m[1];
        }
        
        // Extract dur from end_detail
        $durFromEnd = 0;
        if (!empty($record->end_detail) && preg_match('/dur:(\d+)/', $record->end_detail, $m)) {
            $durFromEnd = (int)$m[1];
        }
        
        // Howen logic
        $correctDuration = $durFromStart > 0 ? $durFromStart : $durFromEnd;
        
        if ($correctDuration == 0) {
            continue;
        }
        
        // Check if already correct
        if ($record->duration_seconds == $correctDuration) {
            $alreadyCorrect++;
            continue;
        }
        
        // Fix it
        if (!$dryRun) {
            $record->update([
                'duration_seconds' => $correctDuration,
                'duration_minutes' => ceil($correctDuration / 60),
            ]);
        }
        
        $fixed++;
    }
    
    $offset += $batchSize;
    $progress = min(100, round(($offset / $total) * 100));
    echo "  Progress: {$progress}% ({$offset}/{$total}) - Fixed: {$fixed}, Correct: {$alreadyCorrect}\r";
}

echo "\n\n";
echo "═══════════════════════════════════\n";
echo "SUMMARY\n";
echo "═══════════════════════════════════\n\n";

if ($dryRun) {
    echo "⚠️  DRY RUN - No changes made\n\n";
}

echo "Total checked: {$total}\n";
echo "✅ Already correct: {$alreadyCorrect}\n";
echo "🔧 Fixed: {$fixed}\n\n";

echo "✅ Done!\n";
