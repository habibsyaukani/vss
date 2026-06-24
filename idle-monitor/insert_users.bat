@echo off
REM Direct SQL insert to create test users

set LARAGON_PATH=C:\laragon
set PHP_PATH=%LARAGON_PATH%\bin\php\php-8.1.10-Win32-vs16-x64
set MYSQL_PATH=%LARAGON_PATH%\bin\mysql\bin

echo Creating test users in database...
echo.

REM Create PHP script that inserts users directly
(
echo ^<?php
echo require 'bootstrap/app.php';
echo use App\Models\User;
echo use Illuminate\Support\Facades\Hash;
echo.
echo try {
echo     // Create admin if not exists
echo     if (!User::where('email', 'admin@vss.com'^).exists(^)^) {
echo         User::create([
echo             'name' =^> 'Administrator',
echo             'email' =^> 'admin@vss.com',
echo             'password' =^> Hash::make('admin123'^),
echo             'role' =^> 'admin',
echo             'status' =^> 'active',
echo         ]^);
echo         echo "✓ Admin created\n";
echo     } else {
echo         echo "✓ Admin already exists\n";
echo     }
echo.
echo     // Create fleet manager if not exists
echo     if (!User::where('email', 'manager@vss.com'^).exists(^)^) {
echo         User::create([
echo             'name' =^> 'Fleet Manager',
echo             'email' =^> 'manager@vss.com',
echo             'password' =^> Hash::make('manager123'^),
echo             'role' =^> 'fleet_manager',
echo             'status' =^> 'active',
echo         ]^);
echo         echo "✓ Fleet Manager created\n";
echo     } else {
echo         echo "✓ Fleet Manager already exists\n";
echo     }
echo.
echo     echo "\nUsers ready for login!\n";
echo } catch (Exception $e^) {
echo     echo "Error: " . $e-^>getMessage(^) . "\n";
echo }
) > insert_users_temp.php

echo Running insert script...
"%PHP_PATH%\php.exe" insert_users_temp.php

del insert_users_temp.php

echo.
echo ================================================================
echo Test Users Created:
echo   Email: manager@vss.com
echo   Password: manager123
echo ================================================================
echo.
pause
