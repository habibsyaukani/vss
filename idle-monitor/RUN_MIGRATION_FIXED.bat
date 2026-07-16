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

REM Try multiple common PHP paths
set PHP_PATH=

if exist "C:\xampp\php\php.exe" set PHP_PATH=C:\xampp\php\php.exe
if exist "C:\laragon\bin\php\php8.1\php.exe" set PHP_PATH=C:\laragon\bin\php\php8.1\php.exe
if exist "C:\laragon\bin\php\php8.2\php.exe" set PHP_PATH=C:\laragon\bin\php\php8.2\php.exe
if exist "C:\laragon\bin\php\php8.3\php.exe" set PHP_PATH=C:\laragon\bin\php\php8.3\php.exe
if exist "C:\wamp64\bin\php\php8.1\php.exe" set PHP_PATH=C:\wamp64\bin\php\php8.1\php.exe
if exist "C:\wamp64\bin\php\php8.2\php.exe" set PHP_PATH=C:\wamp64\bin\php\php8.2\php.exe

if "%PHP_PATH%"=="" (
    echo [ERROR] PHP not found!
    echo.
    echo Please update this batch file with your PHP path.
    echo Common locations:
    echo   - C:\xampp\php\php.exe
    echo   - C:\laragon\bin\php\php8.x\php.exe
    echo   - C:\wamp64\bin\php\php8.x\php.exe
    echo.
    echo Or run manually:
    echo   cd g:\project\vss\idle-monitor
    echo   "C:\full\path\to\php.exe" artisan migrate
    echo.
    pause
    exit /b 1
)

echo Found PHP at: %PHP_PATH%
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
    echo 1. Start queue worker
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
