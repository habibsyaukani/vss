# ====================================================
# PowerShell Script - Upload Fixed Files to Server
# ====================================================

$SERVER = "103.130.6.115"
$USER = "khabib"
$REMOTE_PATH = "/home/khabib/vss/idle-monitor-new/idle-monitor"
$LOCAL_PATH = "g:\project\vss\idle-monitor"

Write-Host ""
Write-Host "=========================================="
Write-Host "UPLOAD FIXES TO SERVER"
Write-Host "=========================================="
Write-Host ""

# Check if pscp is available
if (!(Get-Command pscp -ErrorAction SilentlyContinue)) {
    Write-Host "ERROR: pscp not found!"
    Write-Host ""
    Write-Host "Please install PuTTY from: https://www.putty.org/"
    Write-Host "Or use WinSCP/FileZilla for GUI upload"
    Write-Host ""
    pause
    exit
}

Write-Host "Uploading data-pull.blade.php..."
$file1 = "$LOCAL_PATH\resources\views\admin\data-pull.blade.php"
pscp -pw gpe13579 $file1 ${USER}@${SERVER}:${REMOTE_PATH}/resources/views/admin/data-pull.blade.php

Write-Host "Uploading .env..."
$file2 = "$LOCAL_PATH\.env"
pscp -pw gpe13579 $file2 ${USER}@${SERVER}:${REMOTE_PATH}/.env

Write-Host ""
Write-Host "Clearing cache..."
plink -pw gpe13579 ${USER}@${SERVER} @"
docker exec idle-monitor-app php artisan config:clear
docker exec idle-monitor-app php artisan view:clear
docker exec idle-monitor-app php artisan route:clear
"@

Write-Host ""
Write-Host "=========================================="
Write-Host "UPLOAD COMPLETE!"
Write-Host "=========================================="
Write-Host ""
Write-Host "Next: Open browser http://vams.gpe.co.id:9097/admin/data-pull"
Write-Host "Press Ctrl+F5 to refresh"
Write-Host ""
pause
