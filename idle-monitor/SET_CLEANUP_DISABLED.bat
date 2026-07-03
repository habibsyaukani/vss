@echo off
echo ========================================
echo Set Cleanup to DISABLED by Default
echo ========================================
echo.

set PHP_PATH=C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe

echo Setting cleanup_enabled to FALSE (DISABLED)...
%PHP_PATH% artisan tinker --execute="App\Models\SystemSetting::set('cleanup_enabled', false); echo 'cleanup_enabled set to: ' . (App\Models\SystemSetting::get('cleanup_enabled') ? 'true' : 'false') . PHP_EOL;"

echo.
echo ========================================
echo Done! Cleanup is now DISABLED by default.
echo User must manually ENABLE it from UI.
echo ========================================
pause
