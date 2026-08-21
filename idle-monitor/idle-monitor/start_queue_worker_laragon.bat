@echo off
color 0A
echo ========================================
echo  STARTING QUEUE WORKER (LARAGON)
echo ========================================
echo.
echo Queue Connection: database
echo Tries: 2
echo Timeout: 600 seconds (10 minutes)
echo.
echo Press CTRL+C to stop
echo.

cd /d "%~dp0"

set PHP_PATH=C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe

if not exist "%PHP_PATH%" (
    echo [ERROR] PHP not found at: %PHP_PATH%
    pause
    exit /b 1
)

"%PHP_PATH%" artisan queue:work --tries=2 --timeout=600
