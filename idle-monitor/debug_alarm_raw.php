<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AlarmRaw;

echo "DEBUG: alarm_raw END records with dur:0\n";
echo str_repeat("=", 50) . "\n\n";

$sample = AlarmRaw::where('alarm_state', 0)
    ->where('start_detail', 'LIKE', '%dur:0%')
    ->first();

if ($sample) {
    echo "Sample Record Found:\n";
    echo "  GUID: {$sample->guid}\n";
    echo "  alarm_value: " . substr($sample->alarm_value, 0, 100) . "...\n";
    echo "  start_detail: " . substr($sample->start_detail, 0, 100) . "...\n\n";
    
    // Try to extract dur from alarm_value
    if (preg_match('/dur:\s*(\d+)/', $sample->alarm_value, $matches)) {
        echo "  ✅ Found dur in alarm_value: {$matches[1]} seconds\n";
    } else {
        echo "  ❌ NO dur found in alarm_value\n";
        echo "  Full alarm_value: {$sample->alarm_value}\n";
    }
} else {
    echo "No sample found!\n";
}
