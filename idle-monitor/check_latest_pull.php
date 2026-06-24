<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\AlarmRaw;
use App\Models\IdleAlarm;

echo "═══════════════════════════════════════════════════════════════\n";
echo "  CHECK: Latest Pull Data - Start Detail\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Check latest 10 records from alarm_raw (just pulled)
echo "📋 Latest 10 records from alarm_raw (just pulled):\n";
echo str_repeat('-', 120) . "\n";
printf("%-15s | %-20s | %-50s | %s\n", "Device", "Time", "Start Detail (first 50 chars)", "End Detail");
echo str_repeat('-', 120) . "\n";

$latestRaw = AlarmRaw::orderBy('id', 'desc')
    ->limit(10)
    ->get(['device_name', 'start_time', 'start_detail', 'end_detail']);

$withStartDetail = 0;
$withoutStartDetail = 0;

foreach ($latestRaw as $raw) {
    $startDetail = $raw->start_detail ?: '(EMPTY)';
    $endDetail = $raw->end_detail ?: '(EMPTY)';
    
    if ($raw->start_detail) {
        $withStartDetail++;
    } else {
        $withoutStartDetail++;
    }
    
    printf("%-15s | %-20s | %-50s | %s\n",
        substr($raw->device_name, 0, 15),
        $raw->start_time,
        substr($startDetail, 0, 50),
        substr($endDetail, 0, 30)
    );
}

echo str_repeat('-', 120) . "\n\n";

echo "📊 Latest Pull Results (10 records):\n";
echo "   ✅ With start_detail: {$withStartDetail}\n";
echo "   ❌ Empty start_detail: {$withoutStartDetail}\n";

if ($withStartDetail > 0) {
    echo "\n✅ SUKSES! Backend pull data sudah menarik start_detail dengan benar!\n";
} else {
    echo "\n⚠️  WARNING: Latest pull belum ada start_detail\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";

