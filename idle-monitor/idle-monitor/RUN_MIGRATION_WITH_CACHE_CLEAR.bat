@echo off
color 0A
echo ========================================
echo  BATCH DATA PULL - RUN MIGRATION
echo  (With Cache Clear)
echo ========================================
echo.

cd /d "%~dp0"

set PHP_PATH=C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe

if not exist "%PHP_PATH%" (
    echo [ERROR] PHP not found at: %PHP_PATH%
    pause
    exit /b 1
)

echo Using PHP: %PHP_PATH%
echo.

echo [STEP 1] Clearing All Caches...
echo.
"%PHP_PATH%" artisan config:clear
"%PHP_PATH%" artisan cache:clear
"%PHP_PATH%" artisan route:clear
"%PHP_PATH%" artisan view:clear
echo.
echo Caches cleared!
echo.

echo [STEP 2] Running Migration...
echo.
"%PHP_PATH%" artisan migrate --force

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo  [SUCCESS] MIGRATION COMPLETED!
    echo ========================================
    echo.
    echo Table 'data_pull_batches' has been created.
    echo.
    echo NEXT STEPS:
    echo 1. Verify: verify_batch_pull_laragon.bat
    echo 2. Start queue: start_queue_worker_laragon.bat
    echo 3. Test: http://vams.gpe.co.id:9097/admin/data-pull
    echo.
) else (
    echo.
    echo ========================================
    echo  [ERROR] MIGRATION FAILED!
    echo ========================================
    echo.
    echo Error occurred. Check message above.
    echo.
    echo If still having database connection issues,
    echo the migration might need to be run from the
    echo same environment where the web app is running.
    echo.
)

pause
