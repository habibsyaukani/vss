<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\IdleAlarm;
use Illuminate\Support\Carbon;

echo "\n=== IDLE ALARMS DATABASE STATUS ===\n";
echo "Date: " . Carbon::now()->format('Y-m-d H:i:s') . "\n\n";

// Total counts
$idleCount = IdleAlarm::count();
echo "✅ Total Idle Events: " . $idleCount . "\n";

// Today's data
$todayCount = IdleAlarm::whereDate('starting_time', Carbon::today())->count();
echo "📅 Today's Idle Events: " . $todayCount . "\n";

// Unique devices
$uniqueDevices = IdleAlarm::distinct('device_id')->count();
echo "🚗 Unique Devices: " . $uniqueDevices . "\n";

// Duration statistics
$totalDuration = IdleAlarm::sum('duration_minutes');
$avgDuration = IdleAlarm::avg('duration_minutes');
$maxDuration = IdleAlarm::max('duration_minutes');
$minDuration = IdleAlarm::min('duration_minutes');

echo "\n=== DURATION STATISTICS ===\n";
echo "Total Duration: " . ($totalDuration ? $totalDuration . " minutes" : "0") . "\n";
echo "Average Duration: " . ($avgDuration ? round($avgDuration, 2) . " minutes" : "0") . "\n";
echo "Max Duration: " . ($maxDuration ? $maxDuration . " minutes" : "0") . "\n";
echo "Min Duration: " . ($minDuration ? $minDuration . " minutes" : "0") . "\n";

// Latest records
$latest = IdleAlarm::latest('starting_time')->first();
if ($latest) {
    echo "\n=== LATEST RECORD ===\n";
    echo "Device: " . $latest->device_name . "\n";
    echo "Time: " . $latest->starting_time . "\n";
    echo "Duration: " . $latest->duration_minutes . " minutes\n";
    echo "Speed: " . $latest->start_speed . " → " . $latest->end_speed . " km/h\n";
}

// Records by date (last 7 days)
echo "\n=== IDLE EVENTS BY DATE (Last 7 Days) ===\n";
for ($i = 6; $i >= 0; $i--) {
    $date = Carbon::today()->subDays($i);
    $count = IdleAlarm::whereDate('starting_time', $date)->count();
    if ($count > 0) {
        echo $date->format('Y-m-d') . ": " . $count . " events\n";
    }
}

// Top 5 devices
echo "\n=== TOP 5 DEVICES BY IDLE COUNT ===\n";
$topDevices = IdleAlarm::selectRaw('device_name, COUNT(*) as count, SUM(duration_minutes) as total_duration')
    ->groupBy('device_name')
    ->orderByDesc('count')
    ->limit(5)
    ->get();

foreach ($topDevices as $device) {
    echo $device->device_name . ": " . $device->count . " events (" . $device->total_duration . " min)\n";
}

// Status breakdown
echo "\n=== STATUS BREAKDOWN ===\n";
$statusCounts = IdleAlarm::groupBy('alarm_status')
    ->selectRaw('alarm_status, COUNT(*) as count')
    ->get();

foreach ($statusCounts as $status) {
    echo $status->alarm_status . ": " . $status->count . " events\n";
}

echo "\n";
?>
