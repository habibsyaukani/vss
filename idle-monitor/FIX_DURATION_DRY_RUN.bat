@echo off
echo ========================================
echo DURATION FIX - DRY RUN MODE
echo ========================================
echo.
echo This will show what will be fixed WITHOUT making any changes.
echo.
echo Processing up to 100 records to preview...
echo.

cd /d "%~dp0"
php artisan howen:fix-start-detail-duration --dry-run --limit=100

echo.
echo ========================================
echo DRY RUN COMPLETED
echo ========================================
echo.
echo Review the output above.
echo If everything looks good, run: FIX_DURATION_APPLY.bat
echo.
pause
