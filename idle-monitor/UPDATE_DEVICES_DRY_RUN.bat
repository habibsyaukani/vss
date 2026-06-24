@echo off
echo ================================================================
echo   UPDATE DEVICES - DRY RUN (Preview Only)
echo ================================================================
echo.
echo This will PREVIEW updates without changing database
echo.
pause

cd /d "g:\project\vss\idle-monitor"

C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe artisan update:devices-series-location --dry-run

echo.
echo ================================================================
echo   DRY RUN COMPLETE
echo ================================================================
echo.
echo To apply changes, run: UPDATE_DEVICES_APPLY.bat
echo.
pause
