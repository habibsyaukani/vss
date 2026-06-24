@echo off
echo ================================================
echo   IDLE MONITOR - Starting Realtime Data Pull
echo ================================================
echo.

cd /d "g:\project\vss\idle-monitor"

echo Starting Realtime Pull Loop...
echo This pulls data from Howen API every 3 minutes
echo.
echo Press Ctrl+C to stop
echo.

C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe artisan pull:realtime-loop

pause
