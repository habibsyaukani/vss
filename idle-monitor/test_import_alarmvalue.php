<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\AlarmRaw;

echo "═══════════════════════════════════════════════════════════════\n";
echo "  TEST: Import alarmvalue to start_detail\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Get first alarm_raw with raw_json
$alarmRaw = AlarmRaw::whereNotNull('raw_json')
    ->orderBy('start_time', 'desc')
    ->first();

if (!$alarmRaw) {
    die("❌ No alarm_raw with raw_json found\n");
}

echo "📋 Testing with:\n";
echo "   GUID: {$alarmRaw->guid}\n";
echo "   Device: {$alarmRaw->device_name}\n";
echo "   Current start_detail: " . ($alarmRaw->start_detail ?: '(EMPTY)') . "\n\n";

// Decode raw_json
$json = json_decode($alarmRaw->raw_json, true);

echo "🔍 JSON data:\n";
echo "   alarmvalue (lowercase): " . ($json['alarmvalue'] ?? '(NOT FOUND)') . "\n";
echo "   alarmValue (camelCase): " . ($json['alarmValue'] ?? '(NOT FOUND)') . "\n\n";

// Test the mapping logic
$startDetail = $json['alarmvalue'] ?? $json['alarmValue'] ?? $json['start_detail'] ?? null;

echo "🧪 Mapped start_detail value:\n";
echo "   " . ($startDetail ?: '(NULL)') . "\n\n";

// Try to update
if ($startDetail) {
    echo "💾 Attempting to update database...\n";
    
    $alarmRaw->start_detail = $startDetail;
    $alarmRaw->save();
    
    // Verify
    $alarmRaw->refresh();
    
    echo "✅ Updated!\n";
    echo "   New start_detail: " . ($alarmRaw->start_detail ?: '(STILL EMPTY)') . "\n";
} else {
    echo "❌ Cannot update - startDetail is NULL\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";

