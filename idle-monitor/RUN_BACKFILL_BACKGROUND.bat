@echo off
echo ========================================
echo BACKFILL START DETAIL - BACKGROUND MODE
echo ========================================
echo.
echo This will run backfill in background.
echo You can close this window and check progress later.
echo.
echo Total records: ~302,000
echo Estimated time: 5-10 minutes
echo.
pause

echo.
echo ========================================
echo STEP 1: Backfilling alarm_raw...
echo ========================================
echo.
echo Starting... (This may take 5-10 minutes)
echo Progress will be shown below:
echo.

start /B C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe artisan backfill:start-detail --no-interaction > backfill_alarm_raw.log 2>&1

echo.
echo Command started in background!
echo.
echo To check progress:
echo   type backfill_alarm_raw.log
echo.
echo Or wait for command to complete...
echo.
pause

echo.
echo ========================================
echo STEP 2: Backfilling idle_alarms...
echo ========================================
echo.

start /B C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe artisan backfill:idle-alarms-start-detail --no-interaction > backfill_idle_alarms.log 2>&1

echo.
echo Command started in background!
echo.
echo To check progress:
echo   type backfill_idle_alarms.log
echo.
pause
