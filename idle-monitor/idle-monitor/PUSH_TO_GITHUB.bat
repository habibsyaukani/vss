@echo off
echo ========================================
echo PUSH TO GITHUB - IDLE MONITOR
echo ========================================
echo.

cd /d "%~dp0"

echo [1/4] Checking Git status...
git status

echo.
echo [2/4] Adding all files...
git add .

echo.
echo [3/4] Committing changes...
set /p commit_message="Enter commit message: "
git commit -m "%commit_message%"

echo.
echo [4/4] Pushing to GitHub...
git push origin main

echo.
echo ========================================
echo PUSHED TO GITHUB SUCCESSFULLY!
echo ========================================
echo.
echo Next: Run deploy-to-server.sh to deploy
echo.
pause
