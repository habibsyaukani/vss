@echo off
echo ========================================
echo   FIX SYSTEM CONTROL 500 ERROR
echo ========================================
echo.

echo Step 1: Run migration...
C:\laragon\bin\php\php-8.2.9-Win32-vs16-x64\php.exe artisan migrate --force

echo.
echo Step 2: Clear cache...
C:\laragon\bin\php\php-8.2.9-Win32-vs16-x64\php.exe artisan config:clear
C:\laragon\bin\php\php-8.2.9-Win32-vs16-x64\php.exe artisan route:clear
C:\laragon\bin\php\php-8.2.9-Win32-vs16-x64\php.exe artisan view:clear

echo.
echo Step 3: Check if system_settings table exists...
C:\laragon\bin\php\php-8.2.9-Win32-vs16-x64\php.exe artisan tinker --execute="echo DB::table('system_settings')->count();"

echo.
echo ========================================
echo   Fix completed! Try accessing the page again.
echo ========================================
pause
