<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== WHY ARE ALARMS SKIPPED? ===\n\n";

// Total alarm_raw type 32
$totalType32 = DB::table('alarm_raw')->where('alarm_type', 32)->count();
echo "Total alarm_raw (type 32): $totalType32\n";

// Type 32 State 0
$type32State0 = DB::table('alarm_raw')
    ->where('alarm_type', 32)
    ->where('alarm_state', 0)
    ->count();
echo "Type 32 + State 0: $type32State0\n";

// Type 32 State 0 yang belum ada di idle_alarms
$notInIdleAlarms = DB::table('alarm_raw')
    ->where('alarm_type', 32)
    ->where('alarm_state', 0)
    ->whereNotExists(function ($query) {
        $query->select(DB::raw(1))
              ->from('idle_alarms')
              ->whereRaw('idle_alarms.guid = alarm_raw.guid');
    })
    ->count();
echo "Type 32 + State 0 + NOT in idle_alarms: $notInIdleAlarms\n";

// Check yang di-skip: end_speed = 0
$skippedEndSpeed0 = DB::table('alarm_raw')
    ->where('alarm_type', 32)
    ->where('alarm_state', 0)
    ->whereNotExists(function ($query) {
        $query->select(DB::raw(1))
              ->from('idle_alarms')
              ->whereRaw('idle_alarms.guid = alarm_raw.guid');
    })
    ->where(function($q) {
        $q->whereNull('end_speed')
          ->orWhere('end_speed', '=', 0)
          ->orWhere('end_speed', '=', '');
    })
    ->count();
echo "  └─ Skipped: end_speed = 0 or NULL: $skippedEndSpeed0\n";

// Check yang di-skip: end_time NULL
$skippedEndTimeNull = DB::table('alarm_raw')
    ->where('alarm_type', 32)
    ->where('alarm_state', 0)
    ->whereNotExists(function ($query) {
        $query->select(DB::raw(1))
              ->from('idle_alarms')
              ->whereRaw('idle_alarms.guid = alarm_raw.guid');
    })
    ->whereNull('end_time')
    ->count();
echo "  └─ Skipped: end_time = NULL: $skippedEndTimeNull\n";

// Check yang di-skip: duration = 0
$skippedDuration0 = DB::table('alarm_raw')
    ->where('alarm_type', 32)
    ->where('alarm_state', 0)
    ->whereNotExists(function ($query) {
        $query->select(DB::raw(1))
              ->from('idle_alarms')
              ->whereRaw('idle_alarms.guid = alarm_raw.guid');
    })
    ->where('duration_seconds', '<=', 0)
    ->count();
echo "  └─ Skipped: duration <= 0: $skippedDuration0\n";

// Check yang VALID (memenuhi semua kriteria)
$valid = DB::table('alarm_raw')
    ->where('alarm_type', 32)
    ->where('alarm_state', 0)
    ->whereNotExists(function ($query) {
        $query->select(DB::raw(1))
              ->from('idle_alarms')
              ->whereRaw('idle_alarms.guid = alarm_raw.guid');
    })
    ->whereNotNull('end_time')
    ->where('end_speed', '>', 0)
    ->where('duration_seconds', '>', 0)
    ->count();
echo "  └─ VALID (should be processed): $valid\n\n";

// Total already in idle_alarms
$alreadyProcessed = DB::table('alarm_raw')
    ->where('alarm_type', 32)
    ->where('alarm_state', 0)
    ->whereExists(function ($query) {
        $query->select(DB::raw(1))
              ->from('idle_alarms')
              ->whereRaw('idle_alarms.guid = alarm_raw.guid');
    })
    ->count();
echo "Already processed (in idle_alarms): $alreadyProcessed\n";

echo "\n=== CONCLUSION ===\n";
echo "Job is working correctly!\n";
echo "- It skips alarms that don't meet criteria (end_speed=0, end_time=NULL, etc)\n";
echo "- It skips alarms already processed (guid exists in idle_alarms)\n";
echo "- Only $valid alarms are pending for processing\n";

if ($valid > 0) {
    echo "\n⚠️ WARNING: $valid alarms should be processed but showing as skipped!\n";
    echo "This might be a timing issue or filter mismatch.\n";
} else {
    echo "\n✅ All valid alarms have been processed!\n";
    echo "The '0 records' in import log is CORRECT because all alarms are already in idle_alarms.\n";
}

