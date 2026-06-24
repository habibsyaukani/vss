<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\IdleAlarm;

echo "═══════════════════════════════════════════════════════════════\n";
echo "  CHECK: Idle Alarms Only (alarm_type = 'Idle')\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Count idle_alarms WHERE alarm_type = Idle
$totalIdle = IdleAlarm::where('alarm_type', 'Idle')->count();
$emptyStartDetail = IdleAlarm::where('alarm_type', 'Idle')
    ->where(function($q) {
        $q->whereNull('start_detail')->orWhere('start_detail', '');
    })
    ->count();

$withStartDetail = $totalIdle - $emptyStartDetail;
$percentEmpty = $totalIdle > 0 ? round(($emptyStartDetail / $totalIdle) * 100, 2) : 0;
$percentFilled = 100 - $percentEmpty;

echo "📊 Statistics for alarm_type = 'Idle':\n";
echo "   Total Idle alarms: " . number_format($totalIdle) . "\n";
echo "   ✅ With start_detail: " . number_format($withStartDetail) . " ({$percentFilled}%)\n";
echo "   ❌ Empty start_detail: " . number_format($emptyStartDetail) . " ({$percentEmpty}%)\n\n";

// Sample with start_detail
echo "📋 Sample Idle alarms WITH start_detail (5 records):\n";
echo str_repeat('-', 120) . "\n";
printf("%-15s | %-20s | %-50s | %s\n", "Device", "Time", "Start Detail (50 chars)", "Dur (sec)");
echo str_repeat('-', 120) . "\n";

$samplesWithDetail = IdleAlarm::where('alarm_type', 'Idle')
    ->whereNotNull('start_detail')
    ->where('start_detail', '!=', '')
    ->orderBy('starting_time', 'desc')
    ->limit(5)
    ->get();

foreach ($samplesWithDetail as $alarm) {
    printf("%-15s | %-20s | %-50s | %s\n",
        substr($alarm->device_name, 0, 15),
        $alarm->starting_time,
        substr($alarm->start_detail, 0, 50),
        $alarm->duration_seconds
    );
}

echo str_repeat('-', 120) . "\n\n";

// Check if duration was recalculated from start_detail
echo "🔍 Checking if duration extracted from start_detail `dur`:\n";

$sampleWithDur = IdleAlarm::where('alarm_type', 'Idle')
    ->whereNotNull('start_detail')
    ->where('start_detail', 'like', '%dur:%')
    ->orderBy('starting_time', 'desc')
    ->first();

if ($sampleWithDur) {
    echo "   Sample Record:\n";
    echo "   - Device: {$sampleWithDur->device_name}\n";
    echo "   - Start Detail: {$sampleWithDur->start_detail}\n";
    
    // Extract dur from start_detail
    if (preg_match('/dur:\s*(\d+)/', $sampleWithDur->start_detail, $matches)) {
        $durFromDetail = (int)$matches[1];
        echo "   - Dur (from start_detail): {$durFromDetail} seconds\n";
        echo "   - Duration (in database): {$sampleWithDur->duration_seconds} seconds\n";
        
        if ($durFromDetail == $sampleWithDur->duration_seconds) {
            echo "   ✅ MATCH! Duration diambil dari start_detail\n";
        } else {
            echo "   ⚠️  Different! Duration belum diambil dari start_detail\n";
        }
    }
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "SUMMARY:\n";

if ($percentFilled >= 95) {
    echo "✅ EXCELLENT! {$percentFilled}% Idle alarms have start_detail\n";
} elseif ($percentFilled >= 80) {
    echo "✅ GOOD! {$percentFilled}% Idle alarms have start_detail\n";
} elseif ($percentFilled >= 50) {
    echo "⚠️  OK! {$percentFilled}% Idle alarms have start_detail (masih ada {$percentEmpty}% kosong)\n";
} else {
    echo "❌ Need more fix! Only {$percentFilled}% have start_detail\n";
}

echo "═══════════════════════════════════════════════════════════════\n";

