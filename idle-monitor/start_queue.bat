@echo off
echo ================================================
echo   IDLE MONITOR - Starting Queue Worker
echo ================================================
echo.

cd /d "g:\project\vss\idle-monitor"

echo Starting Queue Worker...
echo This processes background jobs
echo.
echo Press Ctrl+C to stop the worker
echo.

C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe artisan queue:work --tries=3 --timeout=3600

pause
