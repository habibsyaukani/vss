@echo off
REM ===============================================
REM VERIFICATION COMMANDS - Context Transfer Fixes
REM ===============================================
REM
REM This file contains commands to verify all fixes
REM applied during context transfer continuation
REM
REM Date: 2026-07-15
REM ===============================================

echo.
echo ====================================================
echo STEP 1: Verify Database Configuration (.env)
echo ====================================================
echo.
echo Checking if DB_HOST=mysql and DB_PASSWORD=root...
findstr /C:"DB_HOST=mysql" .env
findstr /C:"DB_PASSWORD=root" .env
echo.
echo Expected output:
echo   DB_HOST=mysql
echo   DB_PASSWORD=root
echo.
pause

echo.
echo ====================================================
echo STEP 2: Verify VSS API Credentials (.env)
echo ====================================================
echo.
echo Checking VSS credentials...
findstr /C:"HOWEN_USERNAME" .env
findstr /C:"HOWEN_PASSWORD" .env
findstr /C:"VSS_USERNAME" .env
findstr /C:"VSS_PASSWORD" .env
echo.
echo Expected: All credentials should be present (not empty)
echo.
pause

echo.
echo ====================================================
echo STEP 3: Clear Laravel Cache (Docker)
echo ====================================================
echo.
echo Clearing config, view, and route cache...
docker exec idle-monitor-app php artisan config:clear
docker exec idle-monitor-app php artisan view:clear
docker exec idle-monitor-app php artisan route:clear
echo.
echo Cache cleared successfully!
echo.
pause

echo.
echo ====================================================
echo STEP 4: Test Database Connection (Docker)
echo ====================================================
echo.
echo Testing database connection...
docker exec idle-monitor-app php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB Connected Successfully!\n';"
echo.
pause

echo.
echo ====================================================
echo STEP 5: Verify Parallel Options Removed
echo ====================================================
echo.
echo Searching for parallel options in blade file...
echo Should NOT find "value=\"3\"" or "value=\"5\"":
findstr /C:"value=\"3\"" resources\views\admin\data-pull.blade.php
findstr /C:"value=\"5\"" resources\views\admin\data-pull.blade.php
echo.
echo If no output above = GOOD (options removed)
echo If found lines = BAD (still exist)
echo.
pause

echo.
echo ====================================================
echo STEP 6: Verify Sequential Mode Default
echo ====================================================
echo.
echo Checking if only sequential mode exists...
findstr /C:"value=\"1\" selected" resources\views\admin\data-pull.blade.php
echo.
echo Expected: Should find "value=\"1\" selected"
echo.
pause

echo.
echo ====================================================
echo STEP 7: Verify Pages Default = 200
echo ====================================================
echo.
echo Checking default pages value...
findstr /C:"value=\"200\"" resources\views\admin\data-pull.blade.php
echo.
echo Expected: Should find "value=\"200\""
echo.
pause

echo.
echo ====================================================
echo STEP 8: Check Queue Worker Status
echo ====================================================
echo.
echo Checking if queue worker is running...
docker exec idle-monitor-app php artisan queue:work --once
echo.
echo Queue worker executed one job (if any pending)
echo.
pause

echo.
echo ====================================================
echo VERIFICATION COMPLETE!
echo ====================================================
echo.
echo Summary of Changes:
echo   [DONE] Fixed .env DB_HOST (127.0.0.1 -^> mysql)
echo   [DONE] Fixed .env DB_PASSWORD (empty -^> root)
echo   [DONE] Removed parallel options (3, 5) from UI
echo   [DONE] Set sequential mode (1) as default
echo   [DONE] Updated pages default (50 -^> 200)
echo   [DONE] Updated help text (background queue info)
echo   [DONE] Added rate limit warning
echo.
echo Next Steps:
echo   1. Open browser: http://vams.gpe.co.id:9097/admin/data-pull
echo   2. Hard refresh (Ctrl+F5) to clear browser cache
echo   3. Verify only "Sequential" option visible
echo   4. Test data pull with date 2026-07-14
echo   5. Should return quickly (~2-3 sec) with background queue message
echo.
pause
