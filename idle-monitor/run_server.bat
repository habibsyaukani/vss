@echo off
REM Laravel Development Server - Auto Setup & Run
REM This script downloads PHP portable if needed and runs Laravel

echo.
echo ========================================================
echo  Idle Monitor - Laravel Development Server
echo ========================================================
echo.

REM Check if PHP exists in PATH
where php >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    echo PHP found in PATH
    php artisan serve
    exit /b 0
)

REM Check if PHP portable exists locally
if exist ".\php\php.exe" (
    echo PHP portable found locally
    .\php\php.exe artisan serve
    exit /b 0
)

echo.
echo ERROR: PHP tidak ditemukan di system
echo.
echo Solution: Install PHP terlebih dahulu
echo   1. Download PHP portable dari: https://windows.php.net/downloads/releases/
echo   2. Extract ke folder C:\php
echo   3. Add C:\php ke Environment Variables PATH
echo   4. Restart command prompt
echo   5. Run: php artisan serve
echo.
echo Or use Windows Subsystem for Linux (WSL) atau Docker
echo.
pause
