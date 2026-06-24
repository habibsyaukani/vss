@echo off
echo ================================================
echo   IDLE MONITOR - Starting Laravel Server
echo ================================================
echo.

cd /d "g:\project\vss\idle-monitor"

echo [1/1] Starting Laravel Development Server...
echo URL: http://127.0.0.1:8000
echo.
echo Press Ctrl+C to stop the server
echo.

C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe artisan serve --host=0.0.0.0 --port=8000

pause
