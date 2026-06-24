@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

title Idle Monitor Scheduler - TAHAP 12 (Optimized Dual Strategy)

echo.
echo ╔════════════════════════════════════════════════════════════════╗
echo ║  IDLE MONITOR SCHEDULER - TAHAP 12 (Optimized Dual Strategy)  ║
echo ╚════════════════════════════════════════════════════════════════╝
echo.
echo 🚀 TAHAP 12: Hybrid Backfill + Real-time Strategy
echo.
echo Strategy:
echo   - Full range pull (1 Mei - Today): Every 3 minutes
echo   - Parallel fetching: 5 concurrent connections
echo   - Real-time data: Always fresh
echo   - Data in idle_alarms: < 1 second latency
echo.
echo Expected Results:
echo   ✅ Mei data (if available): Backfilled
echo   ✅ Juni data: Always fresh (every 3 minutes)
echo   ✅ Real-time updates: Immediate processing
echo.
echo Status:
echo   Current Mei: 16 records (2026-05-25 only)
echo   Current Juni: 1,229 records (always updating)
echo   Total: 1,245 valid idle alarms
echo.
echo ════════════════════════════════════════════════════════════════
echo.

cd /d "g:\project\vss\idle-monitor"

echo Starting scheduler with Laragon PHP...
echo.

set PHP_PATH=C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe

if not exist "%PHP_PATH%" (
    echo ❌ ERROR: PHP not found at %PHP_PATH%
    echo Please ensure Laragon is installed
    pause
    exit /b 1
)

echo ✅ PHP: %PHP_PATH%
echo ✅ Working directory: %cd%
echo.
echo ════════════════════════════════════════════════════════════════
echo.
echo 🔄 Starting scheduler...
echo    Press Ctrl+C to stop
echo.
echo ════════════════════════════════════════════════════════════════
echo.

"%PHP_PATH%" artisan schedule:work

pause
