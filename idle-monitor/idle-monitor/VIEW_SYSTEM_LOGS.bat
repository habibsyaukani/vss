@echo off
REM ============================================================================
REM                   VIEW SYSTEM MONITORING LOGS
REM ============================================================================
REM
REM This script helps you view system logs for debugging and troubleshooting
REM
REM Log Files:
REM 1. system-monitor.log - Enhanced logs with troubleshooting hints
REM 2. laravel.log - Standard Laravel logs
REM
REM ============================================================================

setlocal enabledelayedexpansion

cd /d "%~dp0"

echo.
echo ============================================================================
echo                   SYSTEM MONITORING LOGS VIEWER
echo ============================================================================
echo.
echo Choose log file to view:
echo.
echo [1] System Monitor Log (Enhanced - Recommended)
echo [2] Laravel Log (Standard)
echo [3] View Last 50 Lines (System Monitor)
echo [4] View Last 50 Lines (Laravel)
echo [5] Clear System Monitor Log
echo [6] Exit
echo.
set /p choice="Enter your choice (1-6): "

if "%choice%"=="1" goto view_system
if "%choice%"=="2" goto view_laravel
if "%choice%"=="3" goto tail_system
if "%choice%"=="4" goto tail_laravel
if "%choice%"=="5" goto clear_system
if "%choice%"=="6" goto end

:view_system
echo.
echo Opening storage\logs\system-monitor.log...
echo.
if exist "storage\logs\system-monitor.log" (
    type "storage\logs\system-monitor.log"
) else (
    echo [INFO] Log file not found yet. It will be created when jobs run.
)
echo.
pause
goto end

:view_laravel
echo.
echo Opening storage\logs\laravel.log...
echo.
if exist "storage\logs\laravel.log" (
    type "storage\logs\laravel.log"
) else (
    echo [INFO] Log file not found yet.
)
echo.
pause
goto end

:tail_system
echo.
echo Last 50 lines of system-monitor.log:
echo ============================================================================
if exist "storage\logs\system-monitor.log" (
    powershell -Command "Get-Content 'storage\logs\system-monitor.log' -Tail 50"
) else (
    echo [INFO] Log file not found yet. It will be created when jobs run.
)
echo ============================================================================
echo.
pause
goto end

:tail_laravel
echo.
echo Last 50 lines of laravel.log:
echo ============================================================================
if exist "storage\logs\laravel.log" (
    powershell -Command "Get-Content 'storage\logs\laravel.log' -Tail 50"
) else (
    echo [INFO] Log file not found yet.
)
echo ============================================================================
echo.
pause
goto end

:clear_system
echo.
echo [WARNING] This will delete all logs in system-monitor.log
set /p confirm="Are you sure? (Y/N): "
if /i "%confirm%"=="Y" (
    if exist "storage\logs\system-monitor.log" (
        del "storage\logs\system-monitor.log"
        echo.
        echo [SUCCESS] System monitor log cleared.
    ) else (
        echo [INFO] Log file doesn't exist.
    )
) else (
    echo.
    echo [INFO] Cancelled.
)
echo.
pause
goto end

:end
echo.
echo ============================================================================
echo                   LOG VIEWER CLOSED
echo ============================================================================
echo.
echo TIP: Run this anytime to check for errors or troubleshoot issues
echo.
