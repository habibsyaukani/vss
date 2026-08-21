@echo off
echo ========================================
echo  FIX START DETAIL - DRY RUN MODE
echo ========================================
echo.
echo This will show what would be fixed WITHOUT actually changing data
echo.
pause

php artisan howen:fix-start-detail-duration --dry-run --limit=1000

echo.
echo ========================================
echo  Dry run completed!
echo ========================================
echo.
echo If you want to apply the fix, run: FIX_START_DETAIL_APPLY.bat
echo.
pause
