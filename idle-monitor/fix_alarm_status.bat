@echo off
REM Script untuk memperbaiki data alarm_status dengan mapping alarmState yang benar
cd /d G:\project\vss\idle-monitor

set PHP_PATH=C:\laragon\bin\php\php.exe
if not exist %PHP_PATH% (
    REM Try alternate path
    set PHP_PATH=C:\laragon\bin\php\php-8.1.8-Win32-vs16-x64\php.exe
)

echo Clearing old data...
%PHP_PATH% artisan tinker --execute="App\Models\AlarmRaw::truncate(); App\Models\IdleAlarm::truncate(); App\Models\ImportLog::truncate(); echo 'Database cleared';"

echo.
echo Importing alarms...
%PHP_PATH% artisan command:import-alarms

echo.
echo Showing AlarmRaw data...
%PHP_PATH% artisan tinker --execute="foreach (App\Models\AlarmRaw::all() as $r) { echo $r->guid . ' - ' . $r->device_name . ' - State:' . $r->alarm_state . ' - Speed:' . $r->start_speed . '->' . $r->end_speed . PHP_EOL; }"

echo.
echo Processing idle alarms...
%PHP_PATH% artisan command:process-idle-alarms

echo.
echo Showing IdleAlarm data...
%PHP_PATH% artisan tinker --execute="foreach (App\Models\IdleAlarm::all() as $i) { echo $i->guid . ' - ' . $i->device_name . ' - Status:' . $i->alarm_status . ' - Speed:' . $i->start_speed . '->' . $i->end_speed . ' - Duration:' . $i->duration_minutes . 'min' . PHP_EOL; }"

echo.
echo Done!
pause
