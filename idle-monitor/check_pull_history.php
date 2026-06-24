<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ImportLog;
use App\Models\AlarmRaw;
use App\Models\IdleAlarm;
use Illuminate\Support\Carbon;

echo "\n=== PULL DATA HISTORY & STATUS ===\n";
echo "Current Date: " . Carbon::now()->format('Y-m-d H:i:s') . "\n\n";

// Check import logs
echo "=== IMPORT LOGS (Last 20) ===\n";
$logs = ImportLog::orderBy('created_at', 'desc')->limit(20)->get();

if ($logs->isEmpty()) {
    echo "❌ NO IMPORT LOGS FOUND!\n";
} else {
    foreach ($logs as $log) {
        $status = $log->status === 'completed' ? '✅' : ($log->status === 'failed' ? '❌' : '⏳');
        $duration = $log->finished_at && $log->started_at 
            ? round($log->finished_at->diffInSeconds($log->started_at), 2) . 's'
            : 'N/A';
        echo $status . " " . $log->job_name . " | " . $log->started_at->format('Y-m-d H:i:s') . " | Status: " . $log->status . " | Records: " . $log->total_record . " | Duration: " . $duration . "\n";
        if ($log->message) {
            echo "   Message: " . substr($log->message, 0, 80) . "...\n";
        }
    }
}

// Check total records
echo "\n=== DATABASE STATISTICS ===\n";
$totalAlarmRaw = AlarmRaw::count();
$totalIdleAlarms = IdleAlarm::count();
$todayAlarmRaw = AlarmRaw::whereDate('created_at', Carbon::today())->count();
$todayIdleAlarms = IdleAlarm::whereDate('created_at', Carbon::today())->count();

echo "Total alarm_raw: " . $totalAlarmRaw . " (Today: " . $todayAlarmRaw . ")\n";
echo "Total idle_alarms: " . $totalIdleAlarms . " (Today: " . $todayIdleAlarms . ")\n";

// Check date range in alarm_raw
echo "\n=== DATA DATE RANGE ===\n";
$oldestAlarm = AlarmRaw::orderBy('start_time')->first();
$newestAlarm = AlarmRaw::orderBy('start_time', 'desc')->first();

if ($oldestAlarm) {
    echo "Oldest alarm_raw: " . $oldestAlarm->start_time . "\n";
}
if ($newestAlarm) {
    echo "Newest alarm_raw: " . $newestAlarm->start_time . "\n";
}

// Check system settings watermarks
echo "\n=== SYSTEM SETTINGS (WATERMARKS) ===\n";
$lastAlarmSync = \App\Models\SystemSetting::where('key', 'last_alarm_sync')->first();
$lastDeviceSync = \App\Models\SystemSetting::where('key', 'last_device_sync')->first();

echo "Last Alarm Sync: " . ($lastAlarmSync ? $lastAlarmSync->value : "NOT SET") . "\n";
echo "Last Device Sync: " . ($lastDeviceSync ? $lastDeviceSync->value : "NOT SET") . "\n";

// Check if there are any jobs in queue
echo "\n=== QUEUE STATUS ===\n";
$queuedJobs = \DB::table('jobs')->count();
$failedJobs = \DB::table('failed_jobs')->count();

echo "Queued Jobs: " . $queuedJobs . "\n";
echo "Failed Jobs: " . $failedJobs . "\n";

// Check type 32 alarms (idle type)
echo "\n=== IDLE TYPE ALARMS (Type 32) ===\n";
$type32Count = AlarmRaw::where('alarm_type', 32)->count();
$type32Today = AlarmRaw::where('alarm_type', 32)->whereDate('created_at', Carbon::today())->count();

echo "Total Type 32: " . $type32Count . " (Today: " . $type32Today . ")\n";

echo "\n";
?>
