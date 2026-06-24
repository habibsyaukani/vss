@echo off
REM ============================================================================
REM TAHAP 10 PHASE 2 - INSTALLER & SETUP
REM ============================================================================

echo.
echo ============================================================================
echo TAHAP 10 - PHASE 2: Backend Admin Panel - INSTALLER
echo ============================================================================
echo.

REM Step 1: Install Yajra DataTables
echo [STEP 1/5] Installing Yajra DataTables...
echo.
call composer require yajra/laravel-datatables-oracle
if %ERRORLEVEL% neq 0 (
    echo ERROR: Failed to install Yajra DataTables
    pause
    exit /b 1
)

echo.
echo [STEP 2/5] Publishing DataTables assets...
echo.
call php artisan vendor:publish --provider="Yajra\DataTables\DataTablesServiceProvider"
if %ERRORLEVEL% neq 0 (
    echo ERROR: Failed to publish DataTables
    pause
    exit /b 1
)

echo.
echo [STEP 3/5] Running migrations...
echo.
call php artisan migrate
if %ERRORLEVEL% neq 0 (
    echo ERROR: Migration failed
    pause
    exit /b 1
)

echo.
echo [STEP 4/5] Seeding initial data...
echo.
call php artisan db:seed --class=InitialDataSeeder
if %ERRORLEVEL% neq 0 (
    echo ERROR: Seeding failed
    pause
    exit /b 1
)

echo.
echo [STEP 5/5] Clearing cache...
echo.
call php artisan cache:clear
call php artisan config:cache
call php artisan route:cache
call php artisan view:clear

echo.
echo ============================================================================
echo SUCCESS! PHASE 2 INSTALLATION COMPLETE
echo ============================================================================
echo.
echo Next steps:
echo 1. Start Laravel server: php artisan serve
echo 2. Open browser: http://localhost:8000/admin/login
echo 3. Login with: admin@vss.com / admin123
echo 4. Test all features using PHASE_2_TESTING_CHECKLIST.txt
echo.
echo ============================================================================
pause
