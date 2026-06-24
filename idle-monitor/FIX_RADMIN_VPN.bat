@echo off
REM Fix Laragon Apache for Radmin VPN Access
REM This batch file runs the PowerShell fix script

echo.
echo ================================================
echo Laragon Apache Radmin VPN Fix
echo ================================================
echo.
echo WARNING: This script needs Administrator rights
echo.
echo Running fix script...
echo.

REM Run PowerShell script with Administrator
powershell -NoProfile -ExecutionPolicy Bypass -File "fix_apache_radmin_vpn.ps1"

echo.
echo ================================================
echo Completed!
echo ================================================
echo.
echo NEXT STEPS:
echo 1. Close Laragon completely (click X)
echo 2. Wait 5 seconds
echo 3. Re-open Laragon
echo 4. Click [Start] to start services
echo 5. Wait for Apache and MySQL to start
echo 6. Test in browser:
echo    - http://localhost:8000
echo    - http://26.29.218.176:8000 (if VPN connected)
echo.
pause
