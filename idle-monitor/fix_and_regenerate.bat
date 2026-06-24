@echo off
echo ====================================
echo FIX AND REGENERATE IDLE ALARMS
echo ====================================
echo.
echo This will:
echo 1. Fix existing data (serial_no and alarm_status)
echo 2. Show updated data
echo.
pause

cd /d "g:\project\vss\idle-monitor"

echo.
echo [1/2] Fixing existing data...
php fix_idle_alarms_data.php

echo.
echo [2/2] Verifying data...
echo.
php artisan tinker --execute="echo '\n=== IDLE ALARMS SUMMARY ===\n'; echo 'Total records: ' . App\Models\IdleAlarm::count() . '\n\n'; echo 'Status distribution:\n'; DB::table('idle_alarms')->select('alarm_status', DB::raw('count(*) as count'))->groupBy('alarm_status')->get()->each(function($s) { echo \"  {$s->alarm_status}: {$s->count}\n\"; }); echo '\n=== SAMPLE DATA ===\n'; App\Models\IdleAlarm::limit(5)->get()->each(function($a) { echo \"\n[{$a->guid}]\"; echo \"\n  Serial No: {$a->serial_no}\"; echo \"\n  Device: {$a->device_name}\"; echo \"\n  Status: {$a->alarm_status}\"; echo \"\n  Starting: {$a->starting_time} at {$a->starting_location}\"; echo \"\n  Ending: {$a->ending_time} at {$a->ending_location}\"; echo \"\n  Duration: {$a->duration_minutes} minutes\n\"; });"

echo.
echo ====================================
echo DONE!
echo ====================================
echo.
echo You can now check your database.
echo.
pause
