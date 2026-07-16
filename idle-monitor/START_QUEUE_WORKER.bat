@echo off
echo ========================================
echo STARTING QUEUE WORKER
echo ========================================
echo.
echo Queue Connection: database
echo Tries: 2
echo Timeout: 600 seconds (10 minutes)
echo.
echo Press CTRL+C to stop
echo.

php artisan queue:work --tries=2 --timeout=600
