@echo off
echo =========================================== 
echo   VERIFICATION - VOLVO ^& M.SERVICE UPDATE
echo ===========================================
echo.

echo [1] Total devices in CSV:
powershell -Command "$total = (Get-Content devices_update_data.csv | Measure-Object -Line).Lines; Write-Host '   Total lines (with header):' $total; Write-Host '   Total devices:' ($total - 1)"
echo.

echo [2] VOLVO Series entries:
powershell -Command "$volvo = (Get-Content devices_update_data.csv | Select-String 'VOLVO' | Measure-Object).Count; Write-Host '   Count:' $volvo"
echo.

echo [3] M.SERVICE Location entries:
powershell -Command "$mservice = (Get-Content devices_update_data.csv | Select-String 'M.SERVICE' | Measure-Object).Count; Write-Host '   Count:' $mservice"
echo.

echo [4] VOLVO entries detail:
powershell -Command "Get-Content devices_update_data.csv | Select-String 'VOLVO'"
echo.

echo [5] M.SERVICE entries detail:
powershell -Command "Get-Content devices_update_data.csv | Select-String 'M.SERVICE'"
echo.

echo ===========================================
echo   VERIFICATION COMPLETED
echo ===========================================
pause
