@echo off
REM ============================================================================
REM              LARAVEL SCHEDULER - START & MONITOR
REM ============================================================================
REM
REM This batch file starts the Laravel scheduler for Windows
REM The scheduler runs background tasks automatically:
REM   - Authentication token refresh (every 25 minutes)
REM   - Alarm data pull (every 3 minutes - REAL-TIME)
REM   - Idle alarm processing (every 5 minutes)
REM   - Device sync (every 1 hour)
REM
REM ⚠️ IMPORTANT: This must be running for auto data pull to work!
REM
REM ============================================================================

setlocal enabledelayedexpansion

cd /d "%~dp0"

echo.
echo ============================================================================
echo                    LARAVEL SCHEDULER - STARTING
echo ============================================================================
echo.
echo Purpose: Run scheduled background tasks automatically
echo Location: %cd%
echo.

REM Check if PHP is available
php -v >nul 2>&1
if errorlevel 1 (
    echo.
    echo [ERROR] PHP not found in PATH
    echo.
    echo Solutions:
    echo 1. Make sure Laragon is running
    echo 2. Or add PHP to PATH manually
    echo 3. Restart command prompt after adding PATH
    echo.
    pause
    exit /b 1
)

REM Check if scheduler is already running
tasklist /FI "IMAGENAME eq php.exe" /FI "WINDOWTITLE eq *schedule:work*" 2>nul | find /I "php.exe" >nul
if %errorlevel%==0 (
    echo.
    echo [WARNING] Scheduler might already be running!
    echo.
    echo Check Task Manager for: php.exe (schedule:work)
    echo.
    echo Press any key to continue anyway, or Ctrl+C to cancel...
    pause >nul
)

echo.
echo ============================================================================
echo                    ACTIVE SCHEDULED TASKS
echo ============================================================================
echo.
echo [AUTH] Refresh Howen API token        : Every 25 minutes
echo [DATA] Pull alarm data (real-time)    : Every 3 minutes (last 2 hours)
echo [DATA] Process idle alarms            : Every 5 minutes
echo [SYNC] Sync device list               : Every 1 hour
echo.
echo Press Ctrl+C to stop the scheduler
echo.

echo ============================================================================
echo                    SCHEDULER RUNNING...
echo ============================================================================
echo.

REM Start scheduler with proper error handling
php artisan schedule:work

echo.
echo ============================================================================
echo                    SCHEDULER STOPPED
echo ============================================================================
echo.
echo If scheduler stopped unexpectedly, check:
echo 1. PHP errors in the output above
echo 2. Laravel logs: storage/logs/laravel.log
echo 3. Database connection in .env file
echo.

pause
