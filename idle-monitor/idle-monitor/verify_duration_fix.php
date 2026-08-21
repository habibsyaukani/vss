<?php
/**
 * Verification script to test duration extraction fix
 * 
 * This script verifies that:
 * 1. duration_seconds matches dur value from alarmvalue (start_detail)
 * 2. Priority order is correct: alarmvalue > endDetail > alarmTimeLength
 * 3. Both alarm_raw and idle_alarms tables have correct duration values
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 VERIFICATION: Duration Extraction Fix\n";
echo "==========================================\n\n";

// Test 1: Check alarm_raw records
echo "📊 TEST 1: Checking alarm_raw records (Type 32 - Idle Alarms)\n";
echo "--------------------------------------------------------------\n";

$alarmRawRecords = \App\Models\AlarmRaw::where('alarm_type', 32)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

$totalChecked = 0;
$correctCount = 0;
$incorrectCount = 0;
$missingDurCount = 0;

foreach ($alarmRawRecords as $record) {
    $totalChecked++;
    
    // Extract dur from alarm_value (alarmvalue/start_detail)
    $durFromAlarmValue = null;
    if (!empty($record->alarm_value) && preg_match('/dur:(\d+)/', $record->alarm_value, $m)) {
        $durFromAlarmValue = (int)$m[1];
    }
    
    // Extract dur from end_detail (fallback)
    $durFromEndDetail = null;
    if (!empty($record->end_detail) && preg_match('/dur:(\d+)/', $record->end_detail, $m)) {
        $durFromEndDetail = (int)$m[1];
    }
    
    // Expected duration (priority: alarmvalue > endDetail > duration_seconds)
    $expectedDur = $durFromAlarmValue > 0 ? $durFromAlarmValue : ($durFromEndDetail > 0 ? $durFromEndDetail : null);
    
    $actualDur = (int)$record->duration_seconds;
    
    if ($expectedDur === null) {
        $missingDurCount++;
        echo "  ⚠️  GUID: {$record->guid}\n";
        echo "      No dur value found in alarmvalue or endDetail\n";
        echo "      duration_seconds: {$actualDur}s\n";
    } elseif ($actualDur === $expectedDur) {
        $correctCount++;
        echo "  ✅ GUID: {$record->guid}\n";
        echo "      dur from alarmvalue: " . ($durFromAlarmValue ?: 'N/A') . "s\n";
        echo "      duration_seconds: {$actualDur}s (CORRECT)\n";
    } else {
        $incorrectCount++;
        echo "  ❌ GUID: {$record->guid}\n";
        echo "      Expected (from alarmvalue): {$expectedDur}s\n";
        echo "      Actual (duration_seconds): {$actualDur}s (INCORRECT)\n";
        echo "      alarmvalue: " . substr($record->alarm_value, 0, 50) . "...\n";
    }
    echo "\n";
}

echo "Summary for alarm_raw:\n";
echo "  Total checked: {$totalChecked}\n";
echo "  ✅ Correct: {$correctCount}\n";
echo "  ❌ Incorrect: {$incorrectCount}\n";
echo "  ⚠️  Missing dur: {$missingDurCount}\n";
echo "\n\n";

// Test 2: Check idle_alarms records
echo "📊 TEST 2: Checking idle_alarms records\n";
echo "----------------------------------------\n";

$idleAlarms = \App\Models\IdleAlarm::orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

$totalIdleChecked = 0;
$correctIdleCount = 0;
$incorrectIdleCount = 0;

foreach ($idleAlarms as $alarm) {
    $totalIdleChecked++;
    
    // Get corresponding alarm_raw record
    $alarmRaw = \App\Models\AlarmRaw::where('guid', $alarm->guid)->first();
    
    if (!$alarmRaw) {
        echo "  ⚠️  GUID: {$alarm->guid} - No matching alarm_raw record\n\n";
        continue;
    }
    
    // Extract dur from alarm_value
    $durFromAlarmValue = null;
    if (!empty($alarmRaw->alarm_value) && preg_match('/dur:(\d+)/', $alarmRaw->alarm_value, $m)) {
        $durFromAlarmValue = (int)$m[1];
    }
    
    // Extract dur from end_detail (fallback)
    $durFromEndDetail = null;
    if (!empty($alarmRaw->end_detail) && preg_match('/dur:(\d+)/', $alarmRaw->end_detail, $m)) {
        $durFromEndDetail = (int)$m[1];
    }
    
    // Expected duration (priority: alarmvalue > endDetail)
    $expectedDur = $durFromAlarmValue > 0 ? $durFromAlarmValue : ($durFromEndDetail > 0 ? $durFromEndDetail : null);
    
    $actualDurSeconds = (int)$alarm->duration_seconds;
    $actualDurMinutes = (int)$alarm->duration_minutes;
    $expectedMinutes = $expectedDur ? ceil($expectedDur / 60) : null;
    
    if ($expectedDur === null) {
        echo "  ⚠️  GUID: {$alarm->guid}\n";
        echo "      No dur value found\n";
    } elseif ($actualDurSeconds === $expectedDur && $actualDurMinutes === $expectedMinutes) {
        $correctIdleCount++;
        echo "  ✅ GUID: {$alarm->guid}\n";
        echo "      Device: {$alarm->device_name}\n";
        echo "      dur from alarmvalue: " . ($durFromAlarmValue ?: 'N/A') . "s\n";
        echo "      duration_seconds: {$actualDurSeconds}s (CORRECT)\n";
        echo "      duration_minutes: {$actualDurMinutes}min (CORRECT)\n";
    } else {
        $incorrectIdleCount++;
        echo "  ❌ GUID: {$alarm->guid}\n";
        echo "      Device: {$alarm->device_name}\n";
        echo "      Expected: {$expectedDur}s ({$expectedMinutes}min)\n";
        echo "      Actual: {$actualDurSeconds}s ({$actualDurMinutes}min) (INCORRECT)\n";
    }
    echo "\n";
}

echo "Summary for idle_alarms:\n";
echo "  Total checked: {$totalIdleChecked}\n";
echo "  ✅ Correct: {$correctIdleCount}\n";
echo "  ❌ Incorrect: {$incorrectIdleCount}\n";
echo "\n\n";

// Test 3: Statistics
echo "📊 TEST 3: Overall Statistics\n";
echo "-----------------------------\n";

$totalAlarmRaw = \App\Models\AlarmRaw::where('alarm_type', 32)->count();
$alarmRawWithZeroDuration = \App\Models\AlarmRaw::where('alarm_type', 32)
    ->where(function($query) {
        $query->where('duration_seconds', '=', 0)
              ->orWhereNull('duration_seconds');
    })
    ->count();

$totalIdleAlarms = \App\Models\IdleAlarm::count();
$idleAlarmsWithZeroDuration = \App\Models\IdleAlarm::where(function($query) {
        $query->where('duration_seconds', '=', 0)
              ->orWhereNull('duration_seconds')
              ->orWhere('duration_minutes', '=', 0)
              ->orWhereNull('duration_minutes');
    })
    ->count();

echo "alarm_raw (Type 32 - Idle):\n";
echo "  Total records: {$totalAlarmRaw}\n";
echo "  Records with dur=0 or NULL: {$alarmRawWithZeroDuration}\n";
echo "  Percentage correct: " . number_format((($totalAlarmRaw - $alarmRawWithZeroDuration) / max($totalAlarmRaw, 1)) * 100, 2) . "%\n";
echo "\n";

echo "idle_alarms:\n";
echo "  Total records: {$totalIdleAlarms}\n";
echo "  Records with dur=0 or NULL: {$idleAlarmsWithZeroDuration}\n";
echo "  Percentage correct: " . number_format((($totalIdleAlarms - $idleAlarmsWithZeroDuration) / max($totalIdleAlarms, 1)) * 100, 2) . "%\n";
echo "\n\n";

// Test 4: Sample query from user
echo "📊 TEST 4: Running user's verification query\n";
echo "---------------------------------------------\n";
echo "Query: SELECT guid, LEFT(start_detail, 40), duration_seconds, report_time\n";
echo "       FROM alarm_raw WHERE alarm_type = 32\n";
echo "       ORDER BY report_time DESC LIMIT 10\n\n";

$samples = \App\Models\AlarmRaw::where('alarm_type', 32)
    ->orderBy('report_time', 'desc')
    ->limit(10)
    ->get(['guid', 'start_detail', 'duration_seconds', 'report_time']);

foreach ($samples as $sample) {
    $startDetailPreview = substr($sample->start_detail, 0, 40);
    echo "GUID: {$sample->guid}\n";
    echo "  start_detail: {$startDetailPreview}...\n";
    echo "  duration_seconds: {$sample->duration_seconds}s\n";
    echo "  report_time: {$sample->report_time}\n";
    
    // Check if duration matches dur in start_detail
    if (preg_match('/dur:(\d+)/', $sample->start_detail, $m)) {
        $durInStartDetail = (int)$m[1];
        if ($durInStartDetail === (int)$sample->duration_seconds) {
            echo "  ✅ MATCH: duration_seconds = dur in start_detail\n";
        } else {
            echo "  ❌ MISMATCH: dur:{$durInStartDetail} but duration_seconds:{$sample->duration_seconds}\n";
        }
    } else {
        echo "  ⚠️  No dur found in start_detail\n";
    }
    echo "\n";
}

echo "\n";
echo "==========================================\n";
echo "✅ Verification complete!\n";
echo "\n";
echo "Next steps:\n";
echo "1. If there are incorrect records, run: php artisan howen:fix-start-detail-duration --dry-run\n";
echo "2. Review the dry-run output\n";
echo "3. Run the actual fix: php artisan howen:fix-start-detail-duration\n";
echo "4. Run this verification script again to confirm\n";
