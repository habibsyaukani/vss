<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== STATUS PENARIKAN DATA MEI 2026 ===\n\n";

// Get all idle alarms in May
$totalMei = \App\Models\IdleAlarm::whereMonth('starting_time', 5)
    ->whereYear('starting_time', 2026)
    ->count();

$totalJuni = \App\Models\IdleAlarm::whereMonth('starting_time', 6)
    ->whereYear('starting_time', 2026)
    ->count();

echo "📊 SUMMARY:\n";
echo "Data Mei (1-31): " . $totalMei . " idle events\n";
echo "Data Juni (1-4): " . $totalJuni . " idle events\n";
echo "Total: " . ($totalMei + $totalJuni) . "\n\n";

// Breakdown by date for May
echo "📅 BREAKDOWN DATA MEI PER TANGGAL:\n";
echo "────────────────────────────────────\n";

$meiData = [];
for ($day = 1; $day <= 31; $day++) {
    $count = \App\Models\IdleAlarm::whereDay('starting_time', $day)
        ->whereMonth('starting_time', 5)
        ->whereYear('starting_time', 2026)
        ->count();
    
    if ($count > 0) {
        $meiData[$day] = $count;
    }
}

if (empty($meiData)) {
    echo "❌ Belum ada data Mei\n";
} else {
    foreach ($meiData as $day => $count) {
        $pct = round(($count / max($meiData)) * 100);
        $bar = str_repeat("█", round($pct / 5));
        printf("Mei %2d: %3d events %s %3d%%\n", $day, $count, str_pad($bar, 20), $pct);
    }
}

echo "\n";

// Check coverage
$firstDate = \App\Models\IdleAlarm::whereMonth('starting_time', 5)
    ->whereYear('starting_time', 2026)
    ->orderBy('starting_time')
    ->first();

$lastDate = \App\Models\IdleAlarm::whereMonth('starting_time', 5)
    ->whereYear('starting_time', 2026)
    ->orderByDesc('starting_time')
    ->first();

if ($firstDate) {
    echo "📆 COVERAGE MEI:\n";
    echo "First date: " . $firstDate->starting_time->format('Y-m-d H:i') . "\n";
    echo "Last date: " . $lastDate->starting_time->format('Y-m-d H:i') . "\n";
    
    $meiDays = count($meiData);
    $coverage = round(($meiDays / 31) * 100);
    echo "Coverage: $meiDays dari 31 hari ($coverage%)\n";
} else {
    echo "❌ Tidak ada data Mei sama sekali\n";
}

echo "\n";

// Estimate completion
if ($totalMei > 0) {
    $estimatedTotalMei = 65000; // Rough estimate
    $pctComplete = round(($totalMei / $estimatedTotalMei) * 100);
    echo "📈 ESTIMASI KELENGKAPAN:\n";
    echo "Data yang sudah: " . $totalMei . "\n";
    echo "Estimasi total: " . $estimatedTotalMei . "\n";
    echo "Progress: " . $pctComplete . "%\n";
}
