<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║          INVESTIGASI DATA 1 JUNI 2026 (DETAIL ANALISIS)       ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// 1. Total alarm_raw untuk 1 Juni
$totalRaw = DB::table('alarm_raw')
    ->whereDate('start_time', '2026-06-01')
    ->count();

echo "📦 DATA MENTAH (alarm_raw) - 1 JUNI 2026:\n";
echo str_repeat("─", 70) . "\n";
echo "   Total records: {$totalRaw}\n";

// 2. Breakdown by alarm_type
$byType = DB::table('alarm_raw')
    ->whereDate('start_time', '2026-06-01')
    ->select('alarm_type', DB::raw('COUNT(*) as count'))
    ->groupBy('alarm_type')
    ->orderByDesc('count')
    ->get();

echo "\n   Breakdown by Alarm Type:\n";
foreach ($byType as $type) {
    echo "      Type {$type->alarm_type}: {$type->count} records\n";
}

// 3. Khusus alarm type 32 (idle)
$type32 = DB::table('alarm_raw')
    ->whereDate('start_time', '2026-06-01')
    ->where('alarm_type', 32)
    ->count();

echo "\n   🚗 Alarm Type 32 (Idle): {$type32} records\n";

// 4. Breakdown alarm_state untuk type 32
$byState = DB::table('alarm_raw')
    ->whereDate('start_time', '2026-06-01')
    ->where('alarm_type', 32)
    ->select('alarm_state', DB::raw('COUNT(*) as count'))
    ->groupBy('alarm_state')
    ->get();

echo "\n   Alarm State (Type 32 saja):\n";
foreach ($byState as $state) {
    $stateName = $state->alarm_state == 0 ? 'ONGOING/START' : 'ENDED';
    echo "      State {$state->alarm_state} ({$stateName}): {$state->count} records\n";
}

echo "\n";
echo str_repeat("─", 70) . "\n";
echo "\n";

// 5. Sample data type 32 dengan berbagai kondisi
echo "📄 SAMPLE DATA TYPE 32:\n";
echo str_repeat("─", 70) . "\n";

// Sample 1: alarm_state = 1 (ended)
$ended = DB::table('alarm_raw')
    ->whereDate('start_time', '2026-06-01')
    ->where('alarm_type', 32)
    ->where('alarm_state', 1)
    ->limit(3)
    ->get();

echo "\n✅ Sample dengan alarm_state = 1 (ENDED):\n";
if ($ended->count() > 0) {
    foreach ($ended as $idx => $alarm) {
        echo "\n   Sample " . ($idx + 1) . ":\n";
        echo "      GUID: {$alarm->guid}\n";
        echo "      Device: {$alarm->device_id} - {$alarm->device_name}\n";
        echo "      Start Time: {$alarm->start_time}\n";
        echo "      End Time: {$alarm->end_time}\n";
        echo "      Duration: " . ($alarm->duration_seconds ?? 0) . " seconds (" . round(($alarm->duration_seconds ?? 0) / 60, 1) . " min)\n";
        echo "      Start Speed: {$alarm->start_speed} km/h\n";
        echo "      End Speed: {$alarm->end_speed} km/h\n";
        echo "      Alarm State: {$alarm->alarm_state}\n";
        
        // Cek validasi
        $valid = true;
        $reasons = [];
        
        if ($alarm->start_speed != 0) {
            $valid = false;
            $reasons[] = "Start speed != 0 ({$alarm->start_speed})";
        }
        if ($alarm->end_speed == 0 || $alarm->end_speed === null) {
            $valid = false;
            $reasons[] = "End speed = 0 or NULL ({$alarm->end_speed})";
        }
        if (($alarm->duration_seconds ?? 0) < 300) {
            $valid = false;
            $reasons[] = "Duration < 5 min (" . round(($alarm->duration_seconds ?? 0) / 60, 1) . " min)";
        }
        
        if ($valid) {
            echo "      ✅ VALID untuk idle_alarms\n";
        } else {
            echo "      ❌ TIDAK VALID: " . implode(", ", $reasons) . "\n";
        }
    }
} else {
    echo "   ❌ Tidak ada data dengan alarm_state = 1\n";
}

// Sample 2: alarm_state = 0 (ongoing)
$ongoing = DB::table('alarm_raw')
    ->whereDate('start_time', '2026-06-01')
    ->where('alarm_type', 32)
    ->where('alarm_state', 0)
    ->limit(3)
    ->get();

echo "\n⏳ Sample dengan alarm_state = 0 (ONGOING/START):\n";
if ($ongoing->count() > 0) {
    foreach ($ongoing as $idx => $alarm) {
        echo "\n   Sample " . ($idx + 1) . ":\n";
        echo "      GUID: {$alarm->guid}\n";
        echo "      Device: {$alarm->device_id} - {$alarm->device_name}\n";
        echo "      Start Time: {$alarm->start_time}\n";
        echo "      End Time: {$alarm->end_time}\n";
        echo "      Duration: " . ($alarm->duration_seconds ?? 0) . " seconds\n";
        echo "      Start Speed: {$alarm->start_speed} km/h\n";
        echo "      End Speed: " . ($alarm->end_speed ?? 'NULL') . " km/h\n";
        echo "      Alarm State: {$alarm->alarm_state}\n";
        echo "      ⚠️  Alarm belum selesai (state = 0), tidak masuk idle_alarms\n";
    }
} else {
    echo "   Tidak ada data dengan alarm_state = 0\n";
}

echo "\n";
echo str_repeat("─", 70) . "\n";
echo "\n";

// 6. Kesimpulan validasi
echo "🔍 ANALISIS VALIDASI:\n";
echo str_repeat("─", 70) . "\n";

$validCount = DB::table('alarm_raw')
    ->whereDate('start_time', '2026-06-01')
    ->where('alarm_type', 32)
    ->where('alarm_state', 1)  // Harus ended
    ->where('start_speed', 0)   // Start speed = 0
    ->where('end_speed', '>', 0) // End speed > 0
    ->where('duration_seconds', '>=', 300) // Duration >= 5 minutes
    ->count();

echo "   Kriteria Validasi Idle Alarm:\n";
echo "      1. alarm_type = 32 (Idle)\n";
echo "      2. alarm_state = 1 (Ended)\n";
echo "      3. start_speed = 0\n";
echo "      4. end_speed > 0\n";
echo "      5. duration >= 300 seconds (5 minutes)\n";
echo "\n";
echo "   ✅ Data yang memenuhi SEMUA kriteria: {$validCount} records\n";

// Breakdown validation failures
echo "\n   🔍 Breakdown Kegagalan Validasi (Type 32, State 1):\n";

$state1Count = DB::table('alarm_raw')
    ->whereDate('start_time', '2026-06-01')
    ->where('alarm_type', 32)
    ->where('alarm_state', 1)
    ->count();

$failStartSpeed = DB::table('alarm_raw')
    ->whereDate('start_time', '2026-06-01')
    ->where('alarm_type', 32)
    ->where('alarm_state', 1)
    ->where('start_speed', '!=', 0)
    ->count();

$failEndSpeed = DB::table('alarm_raw')
    ->whereDate('start_time', '2026-06-01')
    ->where('alarm_type', 32)
    ->where('alarm_state', 1)
    ->where(function($q) {
        $q->where('end_speed', 0)->orWhereNull('end_speed');
    })
    ->count();

$failDuration = DB::table('alarm_raw')
    ->whereDate('start_time', '2026-06-01')
    ->where('alarm_type', 32)
    ->where('alarm_state', 1)
    ->where(function($q) {
        $q->where('duration_seconds', '<', 300)->orWhereNull('duration_seconds');
    })
    ->count();

echo "      Total Type 32, State 1: {$state1Count}\n";
echo "      ❌ Start speed != 0: {$failStartSpeed}\n";
echo "      ❌ End speed = 0 or NULL: {$failEndSpeed}\n";
echo "      ❌ Duration < 5 min: {$failDuration}\n";
echo "      ✅ Valid (lolos semua): {$validCount}\n";

echo "\n";
