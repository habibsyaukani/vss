@echo off
cd /d G:\project\vss\idle-monitor
echo ========================================
echo DELETE EXTRA DEVICES FROM DATABASE
echo ========================================
echo.
php delete_extra_devices.php
echo.
pause
