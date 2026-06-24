@echo off
echo ========================================
echo VOLVO DEVICE COUNT VERIFICATION
echo ========================================
echo.
echo Running database check...
echo.

C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe check_volvo_count.php

echo.
echo ========================================
echo EXPECTED RESULT:
echo - Total VOLVO devices: 8
echo - All in M.SERVICE location
echo ========================================
echo.
pause
