@echo off
echo ========================================
echo Reset Retention to 30 Days
echo ========================================
echo.

set PHP_PATH=C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe

echo Setting retention back to 30 days...
%PHP_PATH% artisan tinker --execute="App\Models\SystemSetting::set('cleanup_retention_days', 30); echo 'Retention set to: ' . App\Models\SystemSetting::get('cleanup_retention_days') . ' days' . PHP_EOL;"

echo.
echo ========================================
echo Done! Retention is now 30 days.
echo ========================================
pause
