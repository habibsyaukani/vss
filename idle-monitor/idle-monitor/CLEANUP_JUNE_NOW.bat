@echo off
echo ========================================
echo CLEANUP DATA BULAN JUNI - IMMEDIATE
echo ========================================
echo.
echo WARNING: This will DELETE all data from June 2026!
echo.
echo Current date: %date% %time%
echo Cutoff will be set to: May 29, 2026
echo Data BEFORE May 29 will be DELETED (including ALL June data)
echo.
pause
echo.

set PHP_PATH=C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe

echo Step 1: Setting retention to 35 days...
%PHP_PATH% artisan tinker --execute="App\Models\SystemSetting::set('cleanup_retention_days', 35); echo 'Retention set to: ' . App\Models\SystemSetting::get('cleanup_retention_days') . ' days' . PHP_EOL;"

echo.
echo Step 2: Enabling cleanup...
%PHP_PATH% artisan tinker --execute="App\Models\SystemSetting::set('cleanup_enabled', true); echo 'Cleanup enabled: ' . (App\Models\SystemSetting::get('cleanup_enabled') ? 'YES' : 'NO') . PHP_EOL;"

echo.
echo Step 3: Dispatching cleanup job...
%PHP_PATH% artisan tinker --execute="App\Jobs\CleanupOldRawDataJob::dispatch(); echo 'Cleanup job dispatched to queue!' . PHP_EOL;"

echo.
echo Step 4: Running queue worker (will process the job)...
echo This will take a while depending on data size...
echo.
%PHP_PATH% artisan queue:work --once --timeout=3600

echo.
echo ========================================
echo CLEANUP COMPLETED!
echo ========================================
echo.
echo Check results:
echo 1. Open System Control page
echo 2. Check "Last Run" timestamp
echo 3. Check table statistics
echo.
echo To reset retention back to 30 days:
echo Run: SET_RETENTION_30.bat
echo.
pause
