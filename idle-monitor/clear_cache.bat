@echo off
echo ========================================
echo  CLEARING LARAVEL CACHE
echo ========================================
echo.

REM Change to project directory
cd /d "g:\project\vss\idle-monitor"

echo [1/4] Clearing view cache...
php artisan view:clear
if %errorlevel% neq 0 (
    echo ERROR: Failed to clear view cache
    goto :error
)
echo      SUCCESS

echo.
echo [2/4] Clearing config cache...
php artisan config:clear
if %errorlevel% neq 0 (
    echo ERROR: Failed to clear config cache
    goto :error
)
echo      SUCCESS

echo.
echo [3/4] Clearing route cache...
php artisan route:clear
if %errorlevel% neq 0 (
    echo ERROR: Failed to clear route cache
    goto :error
)
echo      SUCCESS

echo.
echo [4/4] Clearing application cache...
php artisan cache:clear
if %errorlevel% neq 0 (
    echo ERROR: Failed to clear application cache
    goto :error
)
echo      SUCCESS

echo.
echo ========================================
echo  ALL CACHE CLEARED SUCCESSFULLY!
echo ========================================
echo.
echo NEXT STEPS:
echo 1. Close ALL browser tabs for localhost:8000
echo 2. Open new browser tab
echo 3. Press Ctrl + Shift + R (hard refresh)
echo 4. Test device search with "806" or "1098"
echo.
pause
exit /b 0

:error
echo.
echo ========================================
echo  CACHE CLEAR FAILED!
echo ========================================
echo.
echo TROUBLESHOOTING:
echo 1. Make sure PHP is installed and in PATH
echo 2. Run this batch file as Administrator
echo 3. Or manually run: php artisan view:clear
echo.
pause
exit /b 1
