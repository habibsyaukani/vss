<?php

/**
 * Verification Script: Start Detail & Duration Fix
 * 
 * This script checks if the fix for dur:0 issue is working correctly
 * 
 * Usage:
 *   php verify_start_detail_fix.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AlarmRaw;
use App\Models\IdleAlarm;

echo "╔═══════════════════════════════════════════════════╗\n";
echo "║  VERIFICATION: Start Detail & Duration Fix       ║\n";
echo "╚═══════════════════════════════════════════════════╝\n";
echo "\n";

// 1. Check alarm_raw table
echo "📊 Checking alarm_raw table...\n";
echo str_repeat("-", 50) . "\n";

$totalAlarmRaw = AlarmRaw::count();
$alarmRawState0 = AlarmRaw::where('alarm_state', 0)->count();
$alarmRawState1 = AlarmRaw::where('alarm_state', 1)->count();

echo "Total records: {$totalAlarmRaw}\n";
echo "  - alarmState=0 (END): {$alarmRawState0}\n";
echo "  - alarmState=1 (START): {$alarmRawState1}\n";
echo "\n";

// Check problematic records (alarmState=1 with dur:0)
$problematicStart = AlarmRaw::where('alarm_state', 1)
    ->whereNotNull('start_detail')
    ->where('start_detail', 'LIKE', '%dur:0%')
    ->count();

echo "Problematic START records (dur:0): {$problematicStart}\n";

if ($problematicStart > 0) {
    echo "  ⚠️  WARNING: Found {$problematicStart} START records with dur:0\n";
    echo "  💡 This is OK if they don't have matching END records yet\n";
} else {
    echo "  ✅ No problematic START records!\n";
}
echo "\n";

// Check END records (should have valid dur)
$endRecordsWithDur0 = AlarmRaw::where('alarm_state', 0)
    ->whereNotNull('start_detail')
    ->where('start_detail', 'LIKE', '%dur:0%')
    ->count();

echo "END records with dur:0: {$endRecordsWithDur0}\n";

if ($endRecordsWithDur0 > 0) {
    echo "  ❌ ERROR: Found {$endRecordsWithDur0} END records with dur:0\n";
    echo "  💡 Run: php artisan howen:fix-start-detail-duration\n";
} else {
    echo "  ✅ All END records have valid duration!\n";
}
echo "\n";

// Sample END records
$sampleEndRecords = AlarmRaw::where('alarm_state', 0)
    ->where('alarm_type', 32)
    ->whereNotNull('start_detail')
    ->orderByDesc('created_at')
    ->limit(5)
    ->get(['device_name', 'alarm_state', 'start_detail', 'duration_seconds', 'created_at']);

if ($sampleEndRecords->isNotEmpty()) {
    echo "Sample END records (alarmState=0):\n";
    foreach ($sampleEndRecords as $record) {
        $dur = 'N/A';
        if (preg_match('/dur:\s*(\d+)/', $record->start_detail, $matches)) {
            $dur = $matches[1] . ' sec';
        }
        
        echo "  • {$record->device_name} - dur:{$dur} - {$record->created_at}\n";
    }
    echo "\n";
}

// 2. Check idle_alarms table
echo "📊 Checking idle_alarms table...\n";
echo str_repeat("-", 50) . "\n";

$totalIdleAlarms = IdleAlarm::count();
$idleAlarmsWithDur0 = IdleAlarm::where(function($query) {
        $query->where('start_detail', 'LIKE', '%dur:0%')
              ->orWhere('duration_seconds', '=', 0)
              ->orWhere('duration_minutes', '=', 0);
    })
    ->count();

echo "Total idle_alarms: {$totalIdleAlarms}\n";
echo "Records with dur:0: {$idleAlarmsWithDur0}\n";

if ($idleAlarmsWithDur0 > 0) {
    echo "  ⚠️  WARNING: Found {$idleAlarmsWithDur0} idle_alarms with dur:0\n";
    echo "  💡 Run: php artisan howen:fix-start-detail-duration\n";
} else {
    echo "  ✅ All idle_alarms have valid duration!\n";
}
echo "\n";

// Sample idle alarms
$sampleIdleAlarms = IdleAlarm::whereNotNull('start_detail')
    ->orderByDesc('created_at')
    ->limit(5)
    ->get(['device_name', 'start_detail', 'duration_minutes', 'starting_time']);

if ($sampleIdleAlarms->isNotEmpty()) {
    echo "Sample idle_alarms:\n";
    foreach ($sampleIdleAlarms as $alarm) {
        $dur = 'N/A';
        if (preg_match('/dur:\s*(\d+)/', $alarm->start_detail, $matches)) {
            $durSec = $matches[1];
            $durMin = ceil($durSec / 60);
            $dur = "{$durSec}s ({$durMin}m)";
        }
        
        echo "  • {$alarm->device_name} - dur:{$dur} - DB:{$alarm->duration_minutes}m\n";
    }
    echo "\n";
}

// 3. Check mapping logic consistency
echo "📊 Checking mapping logic consistency...\n";
echo str_repeat("-", 50) . "\n";

// Find pairs of start/end records
$endRecords = AlarmRaw::where('alarm_state', 0)
    ->where('alarm_type', 32)
    ->whereNotNull('start_detail')
    ->limit(3)
    ->get();

$pairsChecked = 0;
$pairsCorrect = 0;
$pairsIncorrect = 0;

foreach ($endRecords as $endRecord) {
    $startRecord = AlarmRaw::where('guid', $endRecord->guid)
        ->where('alarm_state', 1)
        ->first();
    
    if ($startRecord) {
        $pairsChecked++;
        
        // Extract dur from both records
        $startDur = null;
        $endDur = null;
        
        if ($startRecord->start_detail && preg_match('/dur:\s*(\d+)/', $startRecord->start_detail, $matches)) {
            $startDur = (int)$matches[1];
        }
        
        if ($endRecord->start_detail && preg_match('/dur:\s*(\d+)/', $endRecord->start_detail, $matches)) {
            $endDur = (int)$matches[1];
        }
        
        // Check if logic is correct
        if ($startDur === 0 || $startDur === null) {
            // START should be null or 0 (correct)
            if ($endDur > 0) {
                $pairsCorrect++;
                echo "  ✅ GUID {$endRecord->guid}: START=null/0, END={$endDur}s (CORRECT)\n";
            }
        } else {
            // START has duration (incorrect mapping)
            $pairsIncorrect++;
            echo "  ❌ GUID {$endRecord->guid}: START={$startDur}s, END={$endDur}s (NEEDS FIX)\n";
        }
    }
}

echo "\nPairs checked: {$pairsChecked}\n";
echo "  ✅ Correct: {$pairsCorrect}\n";
echo "  ❌ Incorrect: {$pairsIncorrect}\n";
echo "\n";

// 4. Summary and Recommendations
echo "╔═══════════════════════════════════════════════════╗\n";
echo "║                    SUMMARY                        ║\n";
echo "╚═══════════════════════════════════════════════════╝\n";
echo "\n";

$status = "🟢 GOOD";
$needsFix = false;

if ($endRecordsWithDur0 > 0 || $idleAlarmsWithDur0 > 0 || $pairsIncorrect > 0) {
    $status = "🔴 NEEDS FIX";
    $needsFix = true;
}

echo "Overall Status: {$status}\n";
echo "\n";

if ($needsFix) {
    echo "📋 RECOMMENDED ACTIONS:\n";
    echo "\n";
    
    if ($endRecordsWithDur0 > 0 || $idleAlarmsWithDur0 > 0) {
        echo "1. Run backfill (DRY RUN first):\n";
        echo "   FIX_START_DETAIL_DRY_RUN.bat\n";
        echo "\n";
        
        echo "2. If dry run looks good, apply fix:\n";
        echo "   FIX_START_DETAIL_APPLY.bat\n";
        echo "\n";
        
        echo "3. Run this verification again:\n";
        echo "   php verify_start_detail_fix.php\n";
        echo "\n";
    }
    
    if ($pairsIncorrect > 0) {
        echo "⚠️  Found incorrect mapping in existing data\n";
        echo "   This is expected for old data before the fix\n";
        echo "   New pulls will use correct logic\n";
        echo "\n";
    }
} else {
    echo "✅ All checks passed!\n";
    echo "✅ Mapping logic is working correctly\n";
    echo "✅ No backfill needed (or already completed)\n";
    echo "\n";
}

echo "═══════════════════════════════════════════════════\n";
echo "Verification completed at: " . now()->format('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════\n";
