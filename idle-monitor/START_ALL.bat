@echo off
echo ================================================================
echo   IDLE MONITOR SYSTEM - STARTING ALL COMPONENTS
echo ================================================================
echo.
echo This will open 3 command windows:
echo   1. Laravel Server (http://127.0.0.1:8000)
echo   2. Queue Worker (background jobs)
echo   3. Realtime Data Pull (from Howen API)
echo.
echo To stop: Close each window or press Ctrl+C in each window
echo.
pause

cd /d "g:\project\vss\idle-monitor"

echo.
echo [1/3] Starting Laravel Server...
start "Idle Monitor - Laravel Server" cmd /k "start_server.bat"
timeout /t 3 /nobreak >nul

echo [2/3] Starting Queue Worker...
start "Idle Monitor - Queue Worker" cmd /k "start_queue.bat"
timeout /t 2 /nobreak >nul

echo [3/3] Starting Realtime Data Pull...
start "Idle Monitor - Realtime Pull" cmd /k "start_realtime.bat"
timeout /t 2 /nobreak >nul

echo.
echo ================================================================
echo   ALL COMPONENTS STARTED!
echo ================================================================
echo.
echo   Laravel Server: http://127.0.0.1:8000
echo   Queue Worker: Running in background
echo   Realtime Pull: Running every 3 minutes
echo.
echo You can now open your browser to: http://127.0.0.1:8000
echo.
echo To stop all components: Close the 3 command windows
echo.
pause
