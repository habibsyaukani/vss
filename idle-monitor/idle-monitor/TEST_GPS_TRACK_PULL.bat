@echo off
REM ========================================
REM GPS TRACK PULL PAGE - QUICK TEST
REM ========================================
REM Created: 2026-06-12
REM Purpose: Clear cache and open GPS Track Pull page
REM ========================================

echo.
echo ================================================
echo GPS TRACK PULL PAGE - QUICK TEST
echo ================================================
echo.

cd /d "g:\project\vss\idle-monitor"

echo [1/3] Clearing Laravel caches...
echo.
php artisan route:clear
php artisan view:clear
php artisan config:clear
php artisan cache:clear
echo.

echo [2/3] Caches cleared successfully!
echo.

echo [3/3] Opening GPS Track Pull page in browser...
echo.
echo URL: http://127.0.0.1:8000/admin/gps-track-pull
echo.

timeout /t 2 /nobreak >nul
start http://127.0.0.1:8000/admin/gps-track-pull

echo.
echo ================================================
echo DONE!
echo ================================================
echo.
echo Your browser should open the GPS Track Pull page.
echo.
echo If Laravel server is not running, run this first:
echo   php artisan serve
echo.
echo RECOMMENDED FIRST TEST:
echo   1. Click "Test Pull (10 Device Only)" button
echo   2. Wait ~30-60 seconds
echo   3. Verify success message
echo.
echo For full documentation, read:
echo   - GPS_TRACK_PULL_PAGE_READY.md (quick start)
echo   - GPS_TRACK_PULL_TEST_GUIDE.md (testing guide)
echo   - GPS_TRACK_PULL_PAGE_ANALYSIS.md (technical details)
echo.
pause
