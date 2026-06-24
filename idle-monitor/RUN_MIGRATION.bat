@echo off
echo ========================================
echo  STEP 1: RUN GPS TRACKS MIGRATION
echo ========================================
echo.

REM Change to project directory
cd /d "g:\project\vss\idle-monitor"

echo [INFO] Running migration...
echo.

REM Run migration - adjust PHP path if needed
php artisan migrate

if %errorlevel% neq 0 (
    echo.
    echo ========================================
    echo  MIGRATION FAILED!
    echo ========================================
    echo.
    echo POSSIBLE CAUSES:
    echo 1. PHP not in PATH
    echo 2. Database connection error
    echo 3. Migration files not found
    echo.
    echo SOLUTIONS:
    echo 1. Add PHP to system PATH
    echo 2. Or run manually: php artisan migrate
    echo 3. Check .env database config
    echo.
    pause
    exit /b 1
)

echo.
echo ========================================
echo  MIGRATION SUCCESS!
echo ========================================
echo.
echo Tables created:
echo - gps_tracks_raw
echo - gps_tracks
echo.
echo NEXT STEP: Test API Preview
echo URL: http://localhost:8000/api/gps-tracks/preview?device_id=73200940^&begin_time=2026-06-11 00:00:00^&end_time=2026-06-11 23:59:59
echo.
echo Or proceed to STEP 3: Sync Data
echo (See GPS_TRACKS_SETUP.md for instructions)
echo.
pause
