<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║      TAHAP 12 VERIFICATION - System Ready Check               ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$checks = [];

// 1. Check commands exist
echo "📋 Checking Commands:\n";
$commands = [
    'howen:pull-alarms-per-day' => 'PullIdleAlarmsPerDayCommand',
    'howen:pull-alarms-realtime' => 'PullIdleAlarmsRealtimeCommand',
    'howen:pull-alarms-date-range' => 'PullIdleAlarmsDateRangeCommand',
];

foreach ($commands as $cmd => $class) {
    try {
        $artisan = app(\Illuminate\Contracts\Console\Application::class);
        $result = ($artisan->has($cmd) ? "✅ OK" : "❌ MISSING");
        echo "   $result  $cmd\n";
        $checks[] = $artisan->has($cmd);
    } catch (\Exception $e) {
        echo "   ❌ ERROR  $cmd\n";
        $checks[] = false;
    }
}

echo "\n";

// 2. Check system_settings
echo "📊 Checking System Settings:\n";
$settings = [
    'last_backfill_date',
    'last_realtime_pull',
    'backfill_completed_mei'
];

foreach ($settings as $setting) {
    $exists = \Illuminate\Support\Facades\DB::table('system_settings')
        ->where('key', $setting)
        ->exists();
    $result = ($exists ? "✅ OK" : "❌ MISSING");
    echo "   $result  $setting\n";
    $checks[] = $exists;
}

echo "\n";

// 3. Check data status
echo "📈 Current Data Status:\n";
$meiCount = \Illuminate\Support\Facades\DB::table('idle_alarms')
    ->whereRaw('MONTH(starting_time) = 5 AND YEAR(starting_time) = 2026')
    ->count();
$juneCount = \Illuminate\Support\Facades\DB::table('idle_alarms')
    ->whereRaw('MONTH(starting_time) = 6 AND YEAR(starting_time) = 2026')
    ->count();
$totalCount = \Illuminate\Support\Facades\DB::table('idle_alarms')->count();

echo "   Mei 2026:    $meiCount records\n";
echo "   Juni 2026:   $juneCount records\n";
echo "   Total:       $totalCount idle alarms\n";

echo "\n";

// 4. Final verdict
$allOk = !in_array(false, $checks);
if ($allOk) {
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  ✅ TAHAP 12 IS READY! System fully configured & operational   ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    echo "🚀 Next Step:\n";
    echo "   Run: START_SCHEDULER_TAHAP12.bat\n";
    echo "   Or:  php artisan schedule:work\n";
} else {
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  ⚠️ TAHAP 12 has issues. Check above for details.             ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "\n";
