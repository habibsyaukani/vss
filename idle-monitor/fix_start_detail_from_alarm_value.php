<?php
/**
 * Fix Start Detail - Backfill from alarm_value
 * 
 * This script fixes start_detail by copying from alarm_value field
 * for records where start_detail is NULL or empty
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AlarmRaw;
use App\Models\IdleAlarm;

$dryRun = in_array('--dry-run', $argv);
$limit = 1000;

// Check for --limit parameter
foreach ($argv as $i => $arg) {
    if ($arg === '--limit' && isset($argv[$i + 1])) {
        $limit = (int)$argv[$i + 1];
    }
}

echo "🔧 FIX START DETAIL FROM ALARM_VALUE\n";
echo "=====================================\n\n";

if ($dryRun) {
    echo "⚠️  DRY RUN MODE - No changes will be saved\n\n";
}

echo "Processing up to {$limit} records...\n\n";

// FIX ALARM_RAW
echo "📊 Step 1: Fixing alarm_raw table\n";
echo "----------------------------------\n";

$fixed = 0;
$skipped = 0;

$records = AlarmRaw::where('alarm_type', 32)
    ->where(function($query) {
        $query->whereNull('start_detail')
              ->orWhere('start_detail', '');
    })
    ->whereNotNull('alarm_value')
    ->where('alarm_value', '!=', '')
    ->limit($limit)
    ->get();

echo "Found {$records->count()} records with empty start_detail\n\n";

foreach ($records as $record) {
    try {
        if (!$dryRun) {
            $record->update([
                'start_detail' => $record->alarm_value
            ]);
        }
        
        $fixed++;
        
        if ($fixed % 100 === 0) {
            echo "  Processed: {$fixed} records...\n";
        }
        
    } catch (\Exception $e) {
        $skipped++;
    }
}

echo "\n✅ alarm_raw: Fixed {$fixed} records";
if ($skipped > 0) {
    echo ", skipped {$skipped}";
}
echo "\n\n";

// FIX IDLE_ALARMS
echo "📊 Step 2: Fixing idle_alarms table\n";
echo "------------------------------------\n";

$fixedIdle = 0;
$skippedIdle = 0;

$idleRecords = IdleAlarm::where(function($query) {
        $query->whereNull('start_detail')
              ->orWhere('start_detail', '');
    })
    ->limit($limit)
    ->get();

echo "Found {$idleRecords->count()} idle_alarms with empty start_detail\n\n";

foreach ($idleRecords as $idle) {
    try {
        // Get start_detail from alarm_raw
        $alarmRaw = AlarmRaw::where('guid', $idle->guid)->first();
        
        if (!$alarmRaw) {
            $skippedIdle++;
            continue;
        }
        
        // Get start_detail from alarm_raw (which should now be fixed)
        $startDetail = $alarmRaw->start_detail ?: $alarmRaw->alarm_value;
        
        if (empty($startDetail)) {
            $skippedIdle++;
            continue;
        }
        
        if (!$dryRun) {
            $idle->update([
                'start_detail' => $startDetail
            ]);
        }
        
        $fixedIdle++;
        
        if ($fixedIdle % 100 === 0) {
            echo "  Processed: {$fixedIdle} records...\n";
        }
        
    } catch (\Exception $e) {
        $skippedIdle++;
    }
}

echo "\n✅ idle_alarms: Fixed {$fixedIdle} records";
if ($skippedIdle > 0) {
    echo ", skipped {$skippedIdle}";
}
echo "\n\n";

// SUMMARY
echo "═══════════════════════════════════\n";
echo "SUMMARY\n";
echo "═══════════════════════════════════\n\n";

if ($dryRun) {
    echo "⚠️  DRY RUN - No actual changes were made\n\n";
}

echo "alarm_raw:\n";
echo "  Fixed: {$fixed}\n";
echo "  Skipped: {$skipped}\n\n";

echo "idle_alarms:\n";
echo "  Fixed: {$fixedIdle}\n";
echo "  Skipped: {$skippedIdle}\n\n";

// Check remaining
$remainingRaw = AlarmRaw::where('alarm_type', 32)
    ->where(function($query) {
        $query->whereNull('start_detail')
              ->orWhere('start_detail', '');
    })
    ->count();

$remainingIdle = IdleAlarm::where(function($query) {
        $query->whereNull('start_detail')
              ->orWhere('start_detail', '');
    })
    ->count();

echo "Remaining:\n";
echo "  alarm_raw: {$remainingRaw}\n";
echo "  idle_alarms: {$remainingIdle}\n\n";

if ($remainingRaw > 0 || $remainingIdle > 0) {
    echo "💡 Run again to fix more records:\n";
    echo "   php fix_start_detail_from_alarm_value.php --limit=5000\n";
} else {
    echo "✅ All records fixed!\n";
}

echo "\n";
