@echo off
echo ========================================
echo   CHECK SYSTEM CONTROL ERROR
echo ========================================
echo.

echo 1. Checking if migration ran...
C:\laragon\bin\php\php-8.2.9-Win32-vs16-x64\php.exe artisan migrate:status | findstr "system_settings"

echo.
echo 2. Checking laravel.log for errors...
echo Last 50 lines:
type storage\logs\laravel.log | more

echo.
echo 3. Trying to run migration...
C:\laragon\bin\php\php-8.2.9-Win32-vs16-x64\php.exe artisan migrate --force

echo.
echo ========================================
pause
