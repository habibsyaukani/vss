@echo off
echo ========================================
echo FIX IDLE ALARMS DATA
echo ========================================
echo.
echo This will:
echo 1. Backfill alarm_raw.start_detail from raw_json
echo 2. Copy start_detail to idle_alarms
echo 3. Recalculate duration from start_detail
echo.
echo Total records to fix: ~17,000
echo Estimated time: 5-10 minutes
echo.
pause

echo.
echo Running fix...
echo.

C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe artisan fix:idle-alarms-data

echo.
echo ========================================
echo FIX COMPLETE!
echo ========================================
echo.
echo Verify with: check_start_detail.php
echo.
pause
