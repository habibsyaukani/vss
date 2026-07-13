@echo off
echo ============================================
echo  STARTING BACKGROUND SERVICES
echo ============================================
echo.

echo [OPTION 1] Start Queue Worker
echo Command: start php artisan queue:work
echo.

echo [OPTION 2] Start Scheduler (Runs every minute)
echo Command: php artisan schedule:run
echo Note: In production, this should be in cron/Task Scheduler
echo.

echo [OPTION 3] Start Realtime Pull
echo Command: php artisan realtime:pull
echo Note: This runs continuously
echo.

echo ============================================
echo  MANUAL START REQUIRED
echo ============================================
echo.
echo Please choose which service to start:
echo.
echo 1. Queue Worker only
echo 2. All services (Queue + Realtime)
echo 3. Cancel
echo.

set /p choice="Enter your choice (1-3): "

if "%choice%"=="1" (
    echo Starting Queue Worker...
    start "Queue Worker" php artisan queue:work
    echo Queue Worker started in new window!
)

if "%choice%"=="2" (
    echo Starting Queue Worker...
    start "Queue Worker" php artisan queue:work
    echo.
    timeout /t 2 /nobreak >nul
    echo Starting Realtime Pull...
    start "Realtime Pull" php artisan realtime:pull
    echo All services started in new windows!
)

if "%choice%"=="3" (
    echo Cancelled.
    exit /b
)

echo.
echo Services are now running in separate windows.
echo Close those windows to stop the services.
echo.
pause
