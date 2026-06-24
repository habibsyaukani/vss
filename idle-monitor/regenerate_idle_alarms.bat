@echo off
echo ====================================
echo REGENERATE IDLE ALARMS DATA
echo ====================================
echo.
echo This will:
echo 1. Clear all idle_alarms data
echo 2. Re-process from alarm_raw with correct mapping
echo.
pause

cd /d "g:\project\vss\idle-monitor"

echo.
echo [1/3] Clearing idle_alarms table...
php artisan tinker --execute="DB::table('idle_alarms')->truncate(); echo 'Cleared idle_alarms table';"

echo.
echo [2/3] Re-processing idle alarms with correct status mapping...
php artisan howen:process-idle-alarms

echo.
echo [3/3] Showing updated data...
php artisan tinker --execute="echo '\nTotal idle_alarms: ' . App\Models\IdleAlarm::count(); echo '\n\nSample data:\n'; App\Models\IdleAlarm::limit(5)->get(['guid','serial_no','device_name','alarm_status','starting_time','ending_time','duration_minutes'])->each(function($a) { echo \"\n{$a->guid} | {$a->serial_no} | {$a->device_name} | {$a->alarm_status} | {$a->starting_time} | {$a->ending_time} | {$a->duration_minutes}min\"; });"

echo.
echo ====================================
echo DONE!
echo ====================================
pause
