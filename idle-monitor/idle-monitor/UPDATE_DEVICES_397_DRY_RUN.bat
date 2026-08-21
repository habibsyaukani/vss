@echo off
REM =====================================================
REM DRY RUN - Test update without making changes
REM =====================================================

echo.
echo ========================================
echo  DRY RUN: Update 397 Devices
echo ========================================
echo.
echo This will SIMULATE the update without making any changes
echo.

php UPDATE_DEVICES_397_SAFE.php --dry-run

echo.
pause
