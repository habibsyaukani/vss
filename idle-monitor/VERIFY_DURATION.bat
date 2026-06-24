@echo off
echo ========================================
echo DURATION VERIFICATION
echo ========================================
echo.
echo Checking duration extraction status...
echo.

cd /d "%~dp0"
php verify_duration_fix.php

echo.
echo ========================================
echo VERIFICATION COMPLETED
echo ========================================
echo.
pause
