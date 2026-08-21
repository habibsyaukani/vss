@echo off
REM =====================================================================
REM         IDLE MONITOR - SETUP & START (ALL-IN-ONE)
REM =====================================================================
REM This script does everything:
REM   1. Setup database (migrations, users)
REM   2. Start development server
REM No manual steps needed!

echo.
echo =====================================================================
echo           IDLE MONITOR - COMPLETE SETUP & START
echo =====================================================================
echo.

REM Find PHP path in Laragon
set LARAGON_PATH=C:\laragon
set PHP_PATH=%LARAGON_PATH%\bin\php\php-8.1.10-Win32-vs16-x64

REM Check if PHP exists
if not exist "%PHP_PATH%\php.exe" (
    echo ERROR: PHP not found at %PHP_PATH%
    echo Please ensure Laragon is installed properly
    pause
    exit /b 1
)

echo [STEP 1] Found PHP: %PHP_PATH%\php.exe
echo.

REM Navigate to project
cd /d g:\project\vss\idle-monitor

REM =====================================================================
REM STEP 2: Database Setup (Migrations)
REM =====================================================================
echo [STEP 2] Setting up database (preserving data)...
echo.

echo Running migrations...
"%PHP_PATH%\php.exe" artisan migrate

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo ERROR: Migration failed
    echo Make sure:
    echo   1. Laragon MySQL is running (green icon in tray)
    echo   2. Database 'vss' exists
    echo   3. .env file is correct
    echo.
    pause
    exit /b 1
)

echo Migration complete!
echo.

REM =====================================================================
REM STEP 3: Check Test Users (Seed if needed)
REM =====================================================================
echo [STEP 3] Checking test users...
echo.

for /f "delims=" %%A in ('"%PHP_PATH%\php.exe" artisan tinker --execute="echo App\Models\User::count();"') do set USER_COUNT=%%A

if %USER_COUNT% LSS 1 (
    echo No users found, creating test users...
    "%PHP_PATH%\php.exe" artisan db:seed --class=UserSeeder
    echo Test users created!
) else (
    echo Test users already exist (%USER_COUNT% users found)
)

echo.

REM =====================================================================
REM STEP 4: Start Development Server
REM =====================================================================
echo =====================================================================
echo [STEP 4] Starting development server...
echo =====================================================================
echo.
echo ✓ Application will be available at:
echo.
echo   http://localhost:8000/login
echo.
echo ✓ Login credentials:
echo.
echo   Email:    manager@vss.com
echo   Password: manager123
echo.
echo ✓ To stop server: Press Ctrl+C
echo.
echo =====================================================================
echo.

"%PHP_PATH%\php.exe" artisan serve

pause
