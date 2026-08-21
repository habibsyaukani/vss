@echo off
echo ========================================
echo BACKFILL START DETAIL - APPLY CHANGES
echo ========================================
echo.
echo WARNING: This will UPDATE ~17,000 records in alarm_raw table
echo.
echo Step 1: Backfill alarm_raw.start_detail
echo Step 2: Backfill idle_alarms.start_detail
echo.
pause

echo.
echo ========================================
echo STEP 1: Backfilling alarm_raw table...
echo ========================================
echo.

C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe artisan backfill:start-detail

echo.
echo ========================================
echo STEP 2: Backfilling idle_alarms table...
echo ========================================
echo.

C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe artisan backfill:idle-alarms-start-detail

echo.
echo ========================================
echo BACKFILL COMPLETE!
echo ========================================
echo.
echo Verify changes with: check_start_detail.php
echo.
pause
