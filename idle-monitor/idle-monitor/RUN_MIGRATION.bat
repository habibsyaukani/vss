@echo off
color 0A
echo ========================================
echo  BATCH DATA PULL - RUN MIGRATION
echo ========================================
echo.
echo Migration File: 2026_07_16_100000_create_data_pull_batches_table.php
echo.
echo This will create table: data_pull_batches
echo.
pause
echo.
echo [STEP 1] Running Migration...
echo.

cd /d "%~dp0"

php artisan migrate --force

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo  [SUCCESS] MIGRATION COMPLETED!
    echo ========================================
    echo.
    echo Table 'data_pull_batches' has been created.
    echo.
    echo NEXT STEPS:
    echo 1. Double-click: start_queue_worker.bat
    echo 2. Open browser: http://127.0.0.1:8000/admin/data-pull
    echo 3. Test the feature
    echo.
) else (
    echo.
    echo ========================================
    echo  [ERROR] MIGRATION FAILED!
    echo ========================================
    echo.
    echo Please check the error message above.
    echo.
)

pause
