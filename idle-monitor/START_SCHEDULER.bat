@echo off
REM ============================================================================
REM              LARAVEL SCHEDULER - START & MONITOR
REM ============================================================================
REM
REM This batch file starts the Laravel scheduler for Windows development
REM The scheduler will run all scheduled tasks (including daily data pull)
REM
REM ============================================================================

setlocal enabledelayedexpansion

cd /d "%~dp0"

echo.
echo ============================================================================
echo                    LARAVEL SCHEDULER - STARTING
echo ============================================================================
echo.
echo Purpose: Run scheduled tasks (daily historical data pull at 3 AM)
echo Location: %cd%
echo.

REM Check if PHP is available
php -v >nul 2>&1
if errorlevel 1 (
    echo.
    echo ERROR: PHP not found in PATH
    echo.
    echo Solutions:
    echo 1. Install PHP from: https://windows.php.net/downloads/releases/
    echo 2. Add PHP folder to Environment Variables PATH
    echo 3. Restart command prompt
    echo.
    pause
    exit /b 1
)

echo.
echo ============================================================================
echo                    SCHEDULER CONFIGURATION
echo ============================================================================
echo.
echo Scheduled Tasks:
echo   - Refresh Howen API token (every 25 minutes)
echo   - Import alarms (every 2 minutes)
echo   - Process idle alarms (every 5 minutes)
echo   - Sync devices (every hour)
echo   - Daily historical data pull (3 AM daily) ✅ NEW
echo.

echo ============================================================================
echo                    STARTING SCHEDULER...
echo ============================================================================
echo.

php artisan schedule:work

echo.
echo ============================================================================
echo                    SCHEDULER STOPPED
echo ============================================================================
echo.

pause
