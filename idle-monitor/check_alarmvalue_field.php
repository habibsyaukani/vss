<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\AlarmRaw;

echo "═══════════════════════════════════════════════════════════════\n";
echo "  CHECK: alarmValue Field in Raw JSON\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Get 5 recent alarm_raw records
$alarms = AlarmRaw::orderBy('start_time', 'desc')
    ->limit(5)
    ->get();

foreach ($alarms as $index => $alarm) {
    echo "📋 Record #" . ($index + 1) . ":\n";
    echo "GUID: {$alarm->guid}\n";
    echo "Device: {$alarm->device_name}\n";
    echo "Start Detail (DB): " . ($alarm->start_detail ?: '(NULL/EMPTY)') . "\n";
    echo "End Detail (DB): " . ($alarm->end_detail ?: '(NULL/EMPTY)') . "\n\n";
    
    if ($alarm->raw_json) {
        $json = json_decode($alarm->raw_json, true);
        
        echo "🔍 Checking JSON for 'alarmvalue' field:\n";
        
        // Check all possible variations
        $variations = [
            'alarmvalue',
            'alarmValue', 
            'alarm_value',
            'alarmValueValue',
            'alarmTypeValue'
        ];
        
        foreach ($variations as $field) {
            if (isset($json[$field])) {
                $value = is_string($json[$field]) ? $json[$field] : json_encode($json[$field]);
                echo "   ✅ Found '{$field}': " . substr($value, 0, 100) . "\n";
            } else {
                echo "   ❌ Not found: '{$field}'\n";
            }
        }
        
        echo "\n";
        echo "📝 Full JSON keys available:\n";
        foreach (array_keys($json) as $key) {
            echo "   - {$key}\n";
        }
        
    } else {
        echo "❌ No raw_json\n";
    }
    
    echo "\n" . str_repeat('-', 70) . "\n\n";
}

echo "═══════════════════════════════════════════════════════════════\n";

