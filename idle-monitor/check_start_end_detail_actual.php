<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\IdleAlarm;

echo "╔═══════════════════════════════════════════════════╗\n";
echo "║  CHECK: Start Detail vs End Detail Comparison    ║\n";
echo "╚═══════════════════════════════════════════════════╝\n";
echo "\n";

// Ambil sample idle_alarms
$samples = IdleAlarm::whereNotNull('start_detail')
    ->whereNotNull('end_detail')
    ->where('duration_minutes', '>', 5)  // Idle lebih dari 5 menit
    ->orderByDesc('created_at')
    ->limit(10)
    ->get(['device_name', 'starting_time', 'ending_time', 'start_detail', 'end_detail', 'duration_minutes']);

echo "Sample idle_alarms (duration > 5 min):\n";
echo str_repeat("=", 100) . "\n\n";

foreach ($samples as $i => $alarm) {
    echo "Sample " . ($i+1) . ": {$alarm->device_name}\n";
    echo "  Starting Time: {$alarm->starting_time}\n";
    echo "  Ending Time:   {$alarm->ending_time}\n";
    echo "  Duration:      {$alarm->duration_minutes} minutes\n";
    echo "\n";
    
    // Extract dur from start_detail
    $startDur = 'N/A';
    if (preg_match('/dur[:\s]*(\d+)/', $alarm->start_detail, $matches)) {
        $startDur = $matches[1];
    }
    
    // Extract dur from end_detail
    $endDur = 'N/A';
    if (preg_match('/dur[:\s]*(\d+)/', $alarm->end_detail, $matches)) {
        $endDur = $matches[1];
    }
    
    echo "  Start Detail dur: {$startDur} seconds\n";
    echo "  End Detail dur:   {$endDur} seconds\n";
    
    // Check if they are the same
    if ($startDur === $endDur) {
        echo "  ⚠️  WARNING: Start and End Detail are IDENTICAL!\n";
        echo "  Start Detail: " . substr($alarm->start_detail, 0, 80) . "...\n";
        echo "  End Detail:   " . substr($alarm->end_detail, 0, 80) . "...\n";
    } else {
        echo "  ✅ Start and End Detail are DIFFERENT (correct!)\n";
        echo "  Start Detail: " . substr($alarm->start_detail, 0, 80) . "...\n";
        echo "  End Detail:   " . substr($alarm->end_detail, 0, 80) . "...\n";
    }
    
    echo "\n" . str_repeat("-", 100) . "\n\n";
}

// Statistics
$total = IdleAlarm::whereNotNull('start_detail')
    ->whereNotNull('end_detail')
    ->count();

$identical = IdleAlarm::whereNotNull('start_detail')
    ->whereNotNull('end_detail')
    ->whereRaw('start_detail = end_detail')
    ->count();

$different = $total - $identical;

echo "╔═══════════════════════════════════════╗\n";
echo "║         STATISTICS                    ║\n";
echo "╚═══════════════════════════════════════╝\n";
echo "\n";
echo "Total records with both details: {$total}\n";
echo "  - Identical (start = end):      {$identical} (" . round($identical/$total*100, 2) . "%)\n";
echo "  - Different (start ≠ end):      {$different} (" . round($different/$total*100, 2) . "%)\n";
echo "\n";

if ($identical > $total * 0.5) {
    echo "⚠️  WARNING: More than 50% have identical start_detail and end_detail!\n";
    echo "This matches the issue you described.\n";
} else {
    echo "✅ Most records have different start_detail and end_detail (expected behavior).\n";
}
