@echo off
color 0A
echo ========================================
echo  STARTING MYSQL SERVICE (LARAGON)
echo ========================================
echo.

echo Checking if MySQL is already running...
tasklist | findstr mysqld
if %ERRORLEVEL% EQU 0 (
    echo MySQL is already running!
    echo.
    pause
    exit /b 0
)

echo.
echo Starting MySQL service...
echo.

REM Start MySQL via Laragon
net start mysql

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo  [SUCCESS] MYSQL STARTED!
    echo ========================================
    echo.
    echo You can now run the migration.
    echo.
) else (
    echo.
    echo ========================================
    echo  [ERROR] FAILED TO START MYSQL!
    echo ========================================
    echo.
    echo Please start Laragon manually:
    echo 1. Open Laragon
    echo 2. Click "Start All"
    echo 3. Wait until MySQL is green
    echo.
)

pause
