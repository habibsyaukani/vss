@echo off
REM Process pending queue jobs
cd /d g:\project\vss\idle-monitor

set PHP_PATH=C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe

echo.
echo =====================================================================
echo                   PROCESSING QUEUE JOBS
echo =====================================================================
echo.
echo This will process all pending jobs:
echo   - 7x ImportAlarmPageJob (import alarm pages)
echo   - 1x ProcessIdleAlarmJob (process idle alarms)
echo.
echo Processing...
echo.

%PHP_PATH% artisan queue:work --once --max-jobs=100

echo.
echo =====================================================================
echo                        DONE!
echo =====================================================================
echo.

pause
