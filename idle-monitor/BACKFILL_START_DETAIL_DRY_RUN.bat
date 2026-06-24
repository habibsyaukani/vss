@echo off
echo ========================================
echo BACKFILL START DETAIL - DRY RUN
echo ========================================
echo.
echo This will preview backfill changes WITHOUT applying them.
echo.
pause

C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe artisan backfill:start-detail --dry-run

echo.
echo ========================================
echo DRY RUN COMPLETE
echo ========================================
echo.
echo If results look good, run: BACKFILL_START_DETAIL_APPLY.bat
echo.
pause
