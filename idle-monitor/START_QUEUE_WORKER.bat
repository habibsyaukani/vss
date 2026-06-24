@echo off
REM =====================================================================
REM          IDLE MONITOR - START QUEUE WORKER (Terminal 3)
REM =====================================================================
REM This starts the queue worker that processes jobs
REM Keep this window open!

echo.
echo =====================================================================
echo                 IDLE MONITOR - QUEUE WORKER
echo =====================================================================
echo.
echo This terminal processes background jobs
echo It will handle queued tasks from the scheduler
echo.
echo Keep this window OPEN!
echo Press Ctrl+C to stop
echo.
echo =====================================================================
echo.

cd /d g:\project\vss\idle-monitor

REM Start queue worker
C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe artisan queue:work

pause
