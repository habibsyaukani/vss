@echo off
REM =====================================================================
REM              IDLE MONITOR - START SERVER ONLY
REM =====================================================================
REM This script starts the development server
REM (Assumes database is already setup)
REM
REM FIRST TIME USERS: Run RUN_WITH_LARAGON.bat instead!
REM   - It does setup + start automatically
REM =====================================================================

echo.
echo =====================================================================
echo               IDLE MONITOR - START SERVER ONLY
echo =====================================================================
echo.
echo NOTE: This script only starts the server
echo If this is your first time, use: RUN_WITH_LARAGON.bat
echo (It does setup + start automatically)
echo.

REM Find PHP path in Laragon
set PHP_PATH=C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64

REM Check if PHP exists
if not exist "%PHP_PATH%\php.exe" (
    echo ERROR: PHP not found at %PHP_PATH%
    echo Please ensure Laragon is installed properly
    pause
    exit /b 1
)

REM Navigate to project
cd /d g:\project\vss\idle-monitor

REM Start development server
echo Starting development server...
echo.
echo Access: http://localhost:8000/login
echo Email:  manager@vss.com
echo Pass:   manager123
echo.
echo Press Ctrl+C to stop server
echo.

"%PHP_PATH%\php.exe" artisan serve

pause
