@echo off
echo ========================================
echo FIX START DETAIL - Quick Fix
echo ========================================
echo.
echo This will copy alarm_value to start_detail
echo for all records where start_detail is NULL
echo.
echo Press Ctrl+C to cancel, or
pause
echo.

cd /d "%~dp0"

echo Step 1: Dry Run (Preview)...
echo.
C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe fix_start_detail_from_alarm_value.php --dry-run --limit=100

echo.
echo ========================================
echo.
echo Review the output above.
echo If it looks good, we'll apply the fix.
echo.
pause

echo.
echo Step 2: Apply Fix...
echo.
C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe fix_start_detail_from_alarm_value.php --limit=10000

echo.
echo ========================================
echo FIX COMPLETED
echo ========================================
echo.
pause
