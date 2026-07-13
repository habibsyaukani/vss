@echo off
REM =====================================================
REM APPLY UPDATE - Update 397 devices in database
REM =====================================================

echo.
echo ========================================
echo  APPLY UPDATE: 397 Devices
echo ========================================
echo.
echo WARNING: This will UPDATE the devices table
echo.
echo Please make sure you have:
echo  1. Run the DRY RUN first
echo  2. Backed up your database
echo.
echo Press CTRL+C to cancel, or
pause

php UPDATE_DEVICES_397_SAFE.php

echo.
echo ========================================
echo  UPDATE COMPLETED
echo ========================================
echo.
pause
