<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AlarmRaw;

echo "╔═══════════════════════════════════════════════════╗\n";
echo "║  CHECK: START & END Record Pairs                 ║\n";
echo "╚═══════════════════════════════════════════════════╝\n";
echo "\n";

// Find pairs of START and END records
$endRecords = AlarmRaw::where('alarm_state', 0)
    ->where('alarm_type', 32)
    ->whereNotNull('raw_json')
    ->orderByDesc('created_at')
    ->limit(5)
    ->get();

foreach ($endRecords as $i => $endRecord) {
    $startRecord = AlarmRaw::where('guid', $endRecord->guid)
        ->where('alarm_state', 1)
        ->first();
    
    echo "Pair " . ($i+1) . ": GUID {$endRecord->guid}\n";
    echo str_repeat("=", 80) . "\n";
    
    if ($startRecord) {
        echo "✅ START record found (alarmState:1)\n\n";
        
        $startJson = json_decode($startRecord->raw_json, true);
        $endJson = json_decode($endRecord->raw_json, true);
        
        // START record
        echo "START Record:\n";
        echo "  createtime: " . ($startJson['createtime'] ?? 'N/A') . "\n";
        echo "  alarmvalue: " . substr($startJson['alarmvalue'] ?? 'N/A', 0, 80) . "\n";
        
        if (preg_match('/dur[:\s]*(\d+)/', $startJson['alarmvalue'] ?? '', $m)) {
            echo "  dur: {$m[1]} seconds\n";
        }
        
        echo "\n";
        
        // END record
        echo "END Record:\n";
        echo "  createtime: " . ($endJson['createtime'] ?? 'N/A') . "\n";
        echo "  alarmvalue: " . substr($endJson['alarmvalue'] ?? 'N/A', 0, 80) . "\n";
        echo "  endDetail:  " . substr($endJson['endDetail'] ?? 'N/A', 0, 80) . "\n";
        
        if (preg_match('/dur[:\s]*(\d+)/', $endJson['endDetail'] ?? '', $m)) {
            echo "  dur (from endDetail): {$m[1]} seconds\n";
        }
        
        echo "\n";
        
        // Compare
        echo "COMPARISON:\n";
        $startDur = 0;
        $endDur = 0;
        
        if (preg_match('/dur[:\s]*(\d+)/', $startJson['alarmvalue'] ?? '', $m)) {
            $startDur = (int)$m[1];
        }
        
        if (preg_match('/dur[:\s]*(\d+)/', $endJson['endDetail'] ?? '', $m)) {
            $endDur = (int)$m[1];
        }
        
        echo "  START dur: {$startDur} seconds\n";
        echo "  END dur:   {$endDur} seconds\n";
        echo "  Difference: " . ($endDur - $startDur) . " seconds\n";
        
        if ($startDur == 0 && $endDur > 0) {
            echo "  ✅ CORRECT: START has dur:0, END has actual duration\n";
        } elseif ($startDur > 0 && $endDur > $startDur) {
            echo "  ⚠️  PARTIAL: START has small dur, END has larger dur\n";
        } else {
            echo "  ❌ UNEXPECTED: START dur:{$startDur}, END dur:{$endDur}\n";
        }
        
    } else {
        echo "❌ No START record found for this END record\n";
    }
    
    echo "\n" . str_repeat("-", 80) . "\n\n";
}

// Statistics on pairs
$totalEndRecords = AlarmRaw::where('alarm_state', 0)
    ->where('alarm_type', 32)
    ->count();

$endRecordsWithStartPair = AlarmRaw::where('alarm_state', 0)
    ->where('alarm_type', 32)
    ->whereExists(function($query) {
        $query->select(\DB::raw(1))
              ->from('alarm_raw as ar2')
              ->whereRaw('ar2.guid = alarm_raw.guid')
              ->where('ar2.alarm_state', 1);
    })
    ->count();

echo "╔═══════════════════════════════════════╗\n";
echo "║         PAIR STATISTICS               ║\n";
echo "╚═══════════════════════════════════════╝\n";
echo "\n";
echo "Total END records (alarmState:0): {$totalEndRecords}\n";
echo "END records with START pair:      {$endRecordsWithStartPair}\n";
echo "END records without START pair:   " . ($totalEndRecords - $endRecordsWithStartPair) . "\n";
echo "\n";

if ($endRecordsWithStartPair > 0) {
    echo "✅ We have START-END pairs! We can use START record for start_detail\n";
} else {
    echo "❌ No START-END pairs found. Cannot differentiate start_detail from end_detail.\n";
}
