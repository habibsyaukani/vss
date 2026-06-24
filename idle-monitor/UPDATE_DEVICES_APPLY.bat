@echo off
echo ================================================================
echo   UPDATE DEVICES - APPLY CHANGES
echo ================================================================
echo.
echo ⚠️  WARNING: This will UPDATE the database!
echo.
echo Make sure you have:
echo   1. Created devices_update_data.csv with all 397 records
echo   2. Run UPDATE_DEVICES_DRY_RUN.bat first
echo   3. Backed up database (optional)
echo.
pause

cd /d "g:\project\vss\idle-monitor"

echo.
echo 🔄 Applying updates...
echo.

C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe artisan update:devices-series-location

echo.
echo ================================================================
echo   UPDATE COMPLETE
echo ================================================================
echo.
pause
