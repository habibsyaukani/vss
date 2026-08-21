@echo off
echo ========================================
echo    IDLE MONITOR - WEB SERVER STARTER
echo ========================================
echo.

cd /d "%~dp0"

echo [1/3] Checking if port 8000 is available...
netstat -ano | findstr ":8000" >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo ⚠️  Port 8000 is already in use!
    echo.
    echo Do you want to stop the existing server? (Y/N)
    choice /C YN /N /M "Press Y to stop, N to exit: "
    if !ERRORLEVEL! EQU 1 (
        echo Stopping existing PHP servers...
        taskkill /F /IM php.exe >nul 2>&1
        timeout /t 2 >nul
    ) else (
        echo Exiting...
        pause
        exit /b
    )
)

echo [2/3] Starting Laravel web server...
echo.

C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe artisan serve --host=0.0.0.0 --port=8000

echo.
echo [3/3] Server stopped.
pause
