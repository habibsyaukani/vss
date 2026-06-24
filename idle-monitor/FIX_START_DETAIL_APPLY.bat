@echo off
echo ========================================
echo  FIX START DETAIL - APPLY MODE
echo ========================================
echo.
echo ⚠️  WARNING: This will MODIFY your database!
echo.
echo This will fix start_detail and duration fields
echo by using data from alarmState=0 (end records)
echo.
echo Press Ctrl+C to cancel, or
pause

php artisan howen:fix-start-detail-duration --limit=5000

echo.
echo ========================================
echo  Fix applied!
echo ========================================
echo.
echo Check the summary above to see if there are more records to fix.
echo If needed, run this batch file again.
echo.
pause
