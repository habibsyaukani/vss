@echo off
echo ========================================
echo FIX SystemLogger and Retry Cleanup
echo ========================================
echo.

set PHP_PATH=C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe

echo Step 1: Clear failed jobs from database...
%PHP_PATH% artisan tinker --execute="$deleted = DB::table('failed_jobs')->delete(); echo 'Deleted ' . $deleted . ' failed jobs' . PHP_EOL;"

echo.
echo Step 2: Clear cache...
%PHP_PATH% artisan cache:clear
%PHP_PATH% artisan config:clear

echo.
echo Step 3: Dispatch cleanup job for June 2026...
%PHP_PATH% artisan tinker --execute="App\Jobs\CleanupByMonthJob::dispatch(2026, 6); echo 'Cleanup job dispatched for June 2026!' . PHP_EOL;"

echo.
echo Step 4: Process the job...
echo This will execute the cleanup job immediately.
echo.
%PHP_PATH% artisan queue:work --once --timeout=3600

echo.
echo ========================================
echo DONE!
echo ========================================
echo.
echo Check results:
echo 1. Run: DEBUG_CLEANUP_STATUS.bat
echo 2. Check June data count (should be reduced)
echo.
pause
