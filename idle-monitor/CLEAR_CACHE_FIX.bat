@echo off
echo ========================================
echo CLEAR CACHE - FIX TAMPILAN RUSAK
echo ========================================
echo.

cd /d "%~dp0"

echo [1/5] Clearing Laravel cache...
php artisan cache:clear

echo [2/5] Clearing config cache...
php artisan config:clear

echo [3/5] Clearing route cache...
php artisan route:clear

echo [4/5] Clearing view cache...
php artisan view:clear

echo [5/5] Optimizing application...
php artisan optimize

echo.
echo ========================================
echo CACHE CLEARED SUCCESSFULLY!
echo ========================================
echo.
echo NEXT STEPS:
echo 1. Restart your browser (close all tabs)
echo 2. Clear browser cache (Ctrl+Shift+Delete)
echo 3. Open http://127.0.0.1:8000 again
echo.
pause
