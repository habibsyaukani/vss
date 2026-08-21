@echo off
echo ============================================
echo  STOPPING ALL BACKGROUND SERVICES
echo ============================================
echo.

echo [1/3] Killing all PHP processes...
taskkill /F /IM php.exe 2>nul
taskkill /F /IM php-cgi.exe 2>nul
echo Done!
echo.

echo [2/3] Clearing Laravel Queue...
php artisan queue:clear 2>nul
php artisan queue:flush 2>nul
echo Done!
echo.

echo [3/3] Verifying all processes stopped...
tasklist | findstr /I "php"
if errorlevel 1 (
    echo No PHP processes running - SUCCESS!
) else (
    echo Warning: Some PHP processes still running
)
echo.

echo ============================================
echo  ALL SERVICES STOPPED!
echo ============================================
echo.
echo Background services that were stopped:
echo - Queue Worker (php artisan queue:work)
echo - Realtime Pull (if running)
echo - Scheduler (if running)
echo.
echo To restart services, use start_services.bat
echo.
pause
