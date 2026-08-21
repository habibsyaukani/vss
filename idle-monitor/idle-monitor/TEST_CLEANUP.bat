@echo off
echo ========================================
echo   TEST CLEANUP RAW DATA (DRY RUN)
echo ========================================
echo.

REM Test cleanup dengan dry-run mode
REM Tidak akan menghapus data, hanya menampilkan preview
C:\laragon\bin\php\php-8.2.9-Win32-vs16-x64\php.exe artisan cleanup:raw-data --dry-run --days=30

echo.
echo ========================================
echo   Test completed! Check preview above
echo ========================================
pause
