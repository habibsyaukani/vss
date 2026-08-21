@echo off
echo ========================================
echo BATCH DATA PULL - DEPLOYMENT SCRIPT
echo ========================================
echo.

echo [STEP 1] Running Migration...
php artisan migrate --force
echo.

if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Migration failed! Check error above.
    pause
    exit /b 1
)

echo [SUCCESS] Migration completed!
echo.

echo [STEP 2] Clearing Caches...
php artisan route:clear
php artisan config:clear
php artisan view:clear
echo.

echo [SUCCESS] Caches cleared!
echo.

echo ========================================
echo DEPLOYMENT COMPLETED SUCCESSFULLY!
echo ========================================
echo.
echo NEXT STEPS:
echo 1. Start queue worker: php artisan queue:work --tries=2 --timeout=600
echo 2. Test feature: http://127.0.0.1:8000/admin/data-pull
echo 3. Monitor logs: tail -f storage\logs\laravel.log
echo.
pause
