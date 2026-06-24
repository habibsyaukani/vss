<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\AlarmRaw;
use App\Models\IdleAlarm;

echo "═══════════════════════════════════════════════════════════════\n";
echo "  BACKFILL PROGRESS CHECK\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Check alarm_raw
$totalAlarmRaw = AlarmRaw::count();
$emptyAlarmRaw = AlarmRaw::where(function($q) {
    $q->whereNull('start_detail')->orWhere('start_detail', '');
})->count();
$filledAlarmRaw = $totalAlarmRaw - $emptyAlarmRaw;
$percentAlarmRaw = $totalAlarmRaw > 0 ? round(($filledAlarmRaw / $totalAlarmRaw) * 100, 2) : 0;

echo "📊 alarm_raw table:\n";
echo "   Total records: " . number_format($totalAlarmRaw) . "\n";
echo "   With start_detail: " . number_format($filledAlarmRaw) . " ({$percentAlarmRaw}%)\n";
echo "   Empty start_detail: " . number_format($emptyAlarmRaw) . " (" . round(100 - $percentAlarmRaw, 2) . "%)\n\n";

// Check idle_alarms
$totalIdleAlarms = IdleAlarm::count();
$emptyIdleAlarms = IdleAlarm::where(function($q) {
    $q->whereNull('start_detail')->orWhere('start_detail', '');
})->count();
$filledIdleAlarms = $totalIdleAlarms - $emptyIdleAlarms;
$percentIdleAlarms = $totalIdleAlarms > 0 ? round(($filledIdleAlarms / $totalIdleAlarms) * 100, 2) : 0;

echo "📊 idle_alarms table:\n";
echo "   Total records: " . number_format($totalIdleAlarms) . "\n";
echo "   With start_detail: " . number_format($filledIdleAlarms) . " ({$percentIdleAlarms}%)\n";
echo "   Empty start_detail: " . number_format($emptyIdleAlarms) . " (" . round(100 - $percentIdleAlarms, 2) . "%)\n\n";

// Status
echo "🎯 STATUS:\n";
if ($emptyAlarmRaw == 0 && $emptyIdleAlarms == 0) {
    echo "   ✅ BACKFILL COMPLETE! All records have start_detail.\n";
} elseif ($percentAlarmRaw > 10 || $percentIdleAlarms > 10) {
    echo "   ⏳ BACKFILL IN PROGRESS... ({$percentAlarmRaw}% alarm_raw done)\n";
} else {
    echo "   ⏸️  BACKFILL NOT STARTED or JUST STARTED\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";

