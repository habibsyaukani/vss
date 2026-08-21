@echo off
echo ===========================================
echo   IMPORT DEVICES FROM CSV TO DATABASE
echo ===========================================
echo.
echo This will import 397 devices from devices_update_data.csv
echo to the database with UPDATED VOLVO and M.SERVICE data.
echo.
echo ⚠️  WARNING: This will CLEAR existing devices table!
echo.
echo Press ENTER to continue or CTRL+C to cancel...
pause > nul

C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe import_devices_from_csv.php

echo.
echo Done! Press any key to exit...
pause > nul
