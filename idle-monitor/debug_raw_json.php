<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AlarmRaw;

echo "DEBUG: Check raw_json for END records\n";
echo str_repeat("=", 70) . "\n\n";

$samples = AlarmRaw::where('alarm_state', 0)
    ->where('alarm_type', 32)
    ->whereNotNull('raw_json')
    ->orderByDesc('created_at')
    ->limit(3)
    ->get(['guid', 'alarm_value', 'start_detail', 'duration_seconds', 'raw_json']);

foreach ($samples as $i => $sample) {
    echo "Sample " . ($i+1) . ":\n";
    echo "  GUID: {$sample->guid}\n";
    echo "  alarm_value: " . substr($sample->alarm_value ?? 'NULL', 0, 80) . "\n";
    echo "  start_detail: " . substr($sample->start_detail ?? 'NULL', 0, 80) . "\n";
    echo "  duration_seconds: {$sample->duration_seconds}\n\n";
    
    if ($sample->raw_json) {
        $json = json_decode($sample->raw_json, true);
        
        if (isset($json['alarmvalue'])) {
            echo "  raw_json['alarmvalue']: " . substr($json['alarmvalue'], 0, 100) . "\n";
            
            // Extract dur from raw JSON
            if (preg_match('/dur[:\s]*(\d+)/', $json['alarmvalue'], $matches)) {
                echo "  ✅ Found dur in raw JSON: {$matches[1]} seconds\n";
            } else {
                echo "  ❌ NO dur in raw JSON\n";
            }
        }
        
        if (isset($json['endDetail'])) {
            echo "  raw_json['endDetail']: " . substr($json['endDetail'], 0, 100) . "\n";
        }
        
        if (isset($json['alarmTimeLength'])) {
            echo "  raw_json['alarmTimeLength']: {$json['alarmTimeLength']} seconds\n";
        }
    }
    
    echo "\n" . str_repeat("-", 70) . "\n\n";
}
