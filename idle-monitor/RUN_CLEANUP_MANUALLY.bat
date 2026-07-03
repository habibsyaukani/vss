@echo off
echo ========================================
echo   MANUAL CLEANUP RAW DATA
echo ========================================
echo.
echo WARNING: This will DELETE old raw data!
echo         Data older than 30 days will be removed
echo.
pause

REM Jalankan cleanup (akan konfirmasi lagi di artisan command)
C:\laragon\bin\php\php-8.2.9-Win32-vs16-x64\php.exe artisan cleanup:raw-data --days=30

echo.
echo ========================================
echo   Cleanup completed!
echo ========================================
pause
