@echo off
echo ========================================
echo Clearing All Laravel Caches
echo ========================================
echo.

set PHP_PATH=C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe

echo Clearing config cache...
%PHP_PATH% artisan config:clear

echo Clearing route cache...
%PHP_PATH% artisan route:clear

echo Clearing view cache...
%PHP_PATH% artisan view:clear

echo Clearing application cache...
%PHP_PATH% artisan cache:clear

echo Clearing compiled classes...
%PHP_PATH% artisan clear-compiled

echo.
echo ========================================
echo ✅ All caches cleared!
echo ========================================
pause
