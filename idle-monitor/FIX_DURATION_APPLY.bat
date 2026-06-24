@echo off
echo ========================================
echo DURATION FIX - APPLY CHANGES
echo ========================================
echo.
echo WARNING: This will UPDATE the database!
echo.
echo This will fix duration_seconds for records with dur:0 or NULL
echo by extracting dur value from alarmvalue (start_detail)
echo.
echo Press Ctrl+C to cancel, or
pause
echo.
echo Starting fix process...
echo.

cd /d "%~dp0"

:loop
echo.
echo ========================================
echo Processing batch of 1000 records...
echo ========================================
php artisan howen:fix-start-detail-duration --limit=1000

echo.
echo Batch completed. Checking if more records need fixing...
echo.

REM Run verification to see if there are still problematic records
php -r "require 'vendor/autoload.php'; $app = require 'bootstrap/app.php'; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); $count = \App\Models\AlarmRaw::where('alarm_type', 32)->where(function($q) { $q->where('duration_seconds', 0)->orWhereNull('duration_seconds'); })->count(); echo PHP_EOL . 'Remaining problematic alarm_raw: ' . $count . PHP_EOL; $idleCount = \App\Models\IdleAlarm::where(function($q) { $q->where('duration_seconds', 0)->orWhereNull('duration_seconds')->orWhere('duration_minutes', 0)->orWhereNull('duration_minutes'); })->count(); echo 'Remaining problematic idle_alarms: ' . $idleCount . PHP_EOL . PHP_EOL; if ($count > 0 || $idleCount > 0) { echo 'Still have ' . ($count + $idleCount) . ' records to fix.' . PHP_EOL; echo 'Continue? Press any key...' . PHP_EOL; } else { echo 'All records fixed!' . PHP_EOL; exit(0); }"

choice /M "Continue with next batch?"
if errorlevel 2 goto end
if errorlevel 1 goto loop

:end
echo.
echo ========================================
echo FIX PROCESS COMPLETED
echo ========================================
echo.
echo Run verify_duration_fix.php to confirm results
echo.
pause
