@echo off
REM Setup PHP Configuration for Idle Monitor

echo.
echo ================================================================
echo                 PHP CONFIGURATION SETUP
echo ================================================================
echo.

REM Check if php.ini exists
if not exist "C:\php\php.ini" (
    echo ERROR: php.ini not found at C:\php\php.ini
    echo Please make sure PHP is extracted to C:\php
    pause
    exit /b 1
)

echo Found php.ini at: C:\php\php.ini
echo.
echo Enable MySQL PDO extension...
echo.

REM Create php.ini backup
if not exist "C:\php\php.ini.backup" (
    copy "C:\php\php.ini" "C:\php\php.ini.backup"
    echo Backup created: C:\php\php.ini.backup
)

REM Search for pdo_mysql extension line and uncomment it
REM Using PowerShell to do the replacement
powershell -Command ^
    "$content = Get-Content 'C:\php\php.ini' -Raw; " ^
    "$content = $content -replace ';\s*extension=pdo_mysql', 'extension=pdo_mysql'; " ^
    "$content = $content -replace ';\s*extension=mysql', 'extension=mysql'; " ^
    "$content = $content -replace ';\s*extension=mysqli', 'extension=mysqli'; " ^
    "Set-Content -Path 'C:\php\php.ini' -Value $content"

echo.
echo ✓ PHP Configuration Updated
echo.
echo Extensions enabled:
echo   - pdo_mysql
echo   - mysql
echo   - mysqli
echo.
echo Next steps:
echo   1. Navigate to project folder: cd g:\project\vss\idle-monitor
echo   2. Setup database: C:\php\php.exe artisan migrate --fresh --seed
echo   3. Start server: C:\php\php.exe artisan serve --port=8000
echo   4. Open browser: http://localhost:8000
echo.
pause
