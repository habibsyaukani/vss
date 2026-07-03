@echo off
cd /d "%~dp0"
SET PHP_PATH=C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe

echo ========================================
echo   RUNNING MIGRATION FOR SYSTEM CONTROL
echo ========================================
echo.

echo [1/4] Running migration...
"%PHP_PATH%" artisan migrate --force

echo.
echo [2/4] Clearing config cache...
"%PHP_PATH%" artisan config:clear

echo.
echo [3/4] Clearing route cache...
"%PHP_PATH%" artisan route:clear

echo.
echo [4/4] Clearing view cache...
"%PHP_PATH%" artisan view:clear

echo.
echo ========================================
echo   DONE! Migration completed.
echo   You can now access System Control Center
echo ========================================
echo.
pause
