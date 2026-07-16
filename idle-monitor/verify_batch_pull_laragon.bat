@echo off
color 0A
echo ========================================
echo  BATCH DATA PULL - VERIFICATION
echo ========================================
echo.

cd /d "%~dp0"

set PHP_PATH=C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe

echo [1] Checking if table exists...
echo.
"%PHP_PATH%" artisan tinker --execute="echo 'Table exists: ' . (Schema::hasTable('data_pull_batches') ? 'YES' : 'NO') . PHP_EOL; echo 'Records: ' . \App\Models\DataPullBatch::count() . PHP_EOL;"
echo.

echo [2] Checking queue worker status...
tasklist | findstr php
echo.

echo ========================================
echo  VERIFICATION COMPLETED
echo ========================================
pause
