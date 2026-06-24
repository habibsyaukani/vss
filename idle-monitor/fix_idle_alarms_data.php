<?php
/**
 * Script untuk memperbaiki data idle_alarms yang sudah ada
 * Akan update:
 * 1. serial_no dari tabel devices
 * 2. alarm_status berdasarkan alarm_state dari alarm_raw
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "====================================\n";
echo "FIX IDLE ALARMS DATA\n";
echo "====================================\n\n";

// Mapping function
function mapAlarmStateToStatus($alarmState) {
    switch ($alarmState) {
        case 0:
            return 'ALARMING';  // Idle masih berlangsung
        case 1:
            return 'ALARM_END'; // Idle sudah selesai
        default:
            return 'CLOSED';
    }
}

// Get all idle_alarms
$idleAlarms = DB::table('idle_alarms')->get();
echo "Found " . $idleAlarms->count() . " idle alarms to fix\n\n";

$fixed = 0;
$errors = 0;

foreach ($idleAlarms as $idleAlarm) {
    try {
        // Get alarm_raw data untuk mendapatkan alarm_state
        $alarmRaw = DB::table('alarm_raw')
            ->where('guid', $idleAlarm->guid)
            ->first();
        
        if (!$alarmRaw) {
            echo "⚠️  alarm_raw not found for guid: {$idleAlarm->guid}\n";
            $errors++;
            continue;
        }
        
        // Get device untuk mendapatkan serial_no
        $device = DB::table('devices')
            ->where('device_id', $idleAlarm->device_id)
            ->first();
        
        $serialNo = $device ? $device->serial_no : null;
        
        // Map alarm_state to alarm_status
        $alarmStatus = mapAlarmStateToStatus($alarmRaw->alarm_state);
        
        // Update idle_alarm
        $updated = DB::table('idle_alarms')
            ->where('id', $idleAlarm->id)
            ->update([
                'serial_no' => $serialNo,
                'alarm_status' => $alarmStatus,
                'updated_at' => now(),
            ]);
        
        if ($updated) {
            $fixed++;
            echo "✅ Fixed: {$idleAlarm->guid} | serial_no: {$serialNo} | status: {$alarmStatus}\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error fixing {$idleAlarm->guid}: {$e->getMessage()}\n";
        $errors++;
    }
}

echo "\n====================================\n";
echo "SUMMARY\n";
echo "====================================\n";
echo "Fixed: {$fixed}\n";
echo "Errors: {$errors}\n";
echo "\n";

// Show sample updated data
echo "Sample updated data:\n";
$samples = DB::table('idle_alarms')
    ->limit(5)
    ->get(['guid', 'serial_no', 'device_name', 'alarm_status', 'starting_time', 'ending_time', 'duration_minutes']);

foreach ($samples as $sample) {
    echo "\n{$sample->guid}";
    echo "\n  serial_no: {$sample->serial_no}";
    echo "\n  device: {$sample->device_name}";
    echo "\n  status: {$sample->alarm_status}";
    echo "\n  start: {$sample->starting_time}";
    echo "\n  end: {$sample->ending_time}";
    echo "\n  duration: {$sample->duration_minutes}min";
    echo "\n";
}

echo "\n====================================\n";
echo "DONE!\n";
echo "====================================\n";
