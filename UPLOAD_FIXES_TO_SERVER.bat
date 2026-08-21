@echo off
REM ====================================================
REM Upload Fixed Files to Production Server
REM ====================================================
REM
REM Prerequisites:
REM 1. Install PuTTY (includes pscp.exe)
REM 2. Add PuTTY to PATH or specify full path
REM 3. Know SSH password for khabib@103.130.6.115
REM
REM ====================================================

echo.
echo ====================================================
echo UPLOAD FIXES TO PRODUCTION SERVER
echo ====================================================
echo.

REM Set variables
set SERVER=103.130.6.115
set USER=khabib
set REMOTE_PATH=/home/khabib/vss/idle-monitor-new/idle-monitor
set LOCAL_PATH=g:\project\vss\idle-monitor

echo Checking if pscp.exe is available...
where pscp.exe >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo ERROR: pscp.exe not found!
    echo.
    echo Please install PuTTY from: https://www.putty.org/
    echo Or specify full path to pscp.exe in this script
    echo.
    pause
    exit /b 1
)

echo.
echo ====================================================
echo STEP 1: Upload data-pull.blade.php
echo ====================================================
echo.
echo Source: %LOCAL_PATH%\resources\views\admin\data-pull.blade.php
echo Destination: %SERVER%:%REMOTE_PATH%/resources/views/admin/data-pull.blade.php
echo.

pscp -pw YOUR_PASSWORD_HERE "%LOCAL_PATH%\resources\views\admin\data-pull.blade.php" %USER%@%SERVER%:%REMOTE_PATH%/resources/views/admin/data-pull.blade.php

if %ERRORLEVEL% EQU 0 (
    echo [OK] File uploaded successfully!
) else (
    echo [ERROR] Upload failed!
    echo Please check password and network connection
    pause
    exit /b 1
)

echo.
echo ====================================================
echo STEP 2: Upload .env file
echo ====================================================
echo.
echo Source: %LOCAL_PATH%\.env
echo Destination: %SERVER%:%REMOTE_PATH%/.env
echo.

pscp -pw YOUR_PASSWORD_HERE "%LOCAL_PATH%\.env" %USER%@%SERVER%:%REMOTE_PATH%/.env

if %ERRORLEVEL% EQU 0 (
    echo [OK] File uploaded successfully!
) else (
    echo [ERROR] Upload failed!
    pause
    exit /b 1
)

echo.
echo ====================================================
echo STEP 3: Clear Laravel Cache (via SSH)
echo ====================================================
echo.
echo Running: docker exec idle-monitor-app php artisan view:clear
echo.

REM Use plink (PuTTY's SSH client) to run remote commands
plink -pw YOUR_PASSWORD_HERE %USER%@%SERVER% "docker exec idle-monitor-app php artisan view:clear && docker exec idle-monitor-app php artisan config:clear && docker exec idle-monitor-app php artisan route:clear"

if %ERRORLEVEL% EQU 0 (
    echo [OK] Cache cleared successfully!
) else (
    echo [WARNING] Cache clear failed or plink not available
    echo You may need to clear cache manually or restart Docker
)

echo.
echo ====================================================
echo UPLOAD COMPLETE!
echo ====================================================
echo.
echo Next steps:
echo 1. Open browser: http://vams.gpe.co.id:9097/admin/data-pull
echo 2. Hard refresh: Ctrl+F5
echo 3. Verify changes:
echo    - Pages default should be 200
echo    - Only Sequential mode visible
echo    - Rate limit warning visible
echo.
pause
