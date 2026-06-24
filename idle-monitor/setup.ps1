# Idle Monitor - Setup Script
# Run this script as Administrator for best results

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "           IDLE MONITOR - APPLICATION SETUP SCRIPT" -ForegroundColor Green
Write-Host "═══════════════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

# Step 1: Check PHP
Write-Host "STEP 1: Checking PHP Installation..." -ForegroundColor Yellow
Write-Host ""

$phpFound = $null
$phpPath = $null

# Check system PATH
$phpCmd = Get-Command php -ErrorAction SilentlyContinue
if ($phpCmd) {
    $phpFound = $true
    $phpPath = $phpCmd.Source
    Write-Host "✓ PHP found in system PATH: $phpPath" -ForegroundColor Green
    & php -v
} else {
    Write-Host "✗ PHP not found in system PATH" -ForegroundColor Red
    Write-Host ""
    Write-Host "SOLUTIONS:" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "1. DOWNLOAD PHP PORTABLE (Recommended)" -ForegroundColor Cyan
    Write-Host "   - Visit: https://windows.php.net/downloads/releases/" 
    Write-Host "   - Download: php-8.3.X-Win32-vs16-x64.zip (latest stable)"
    Write-Host "   - Extract to: C:\php"
    Write-Host "   - Add C:\php to Environment Variables PATH"
    Write-Host "   - Restart PowerShell"
    Write-Host ""
    
    Write-Host "2. INSTALL VIA CHOCOLATEY" -ForegroundColor Cyan
    Write-Host "   Run as Administrator:"
    Write-Host "   Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))"
    Write-Host "   Then: choco install php"
    Write-Host ""
    
    Write-Host "3. INSTALL DOCKER & USE SAIL" -ForegroundColor Cyan
    Write-Host "   - Install Docker Desktop: https://www.docker.com/products/docker-desktop"
    Write-Host "   - Run: ./vendor/bin/sail up"
    Write-Host ""
    
    Write-Host "Please install PHP first, then run this script again." -ForegroundColor Yellow
    exit
}

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "STEP 2: Setting up Database..." -ForegroundColor Yellow
Write-Host "═══════════════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

# Check if MySQL is running
try {
    $null = php -r "mysqli_connect('127.0.0.1', 'root', '', 'mysql');" 2>$null
    Write-Host "✓ MySQL connection successful" -ForegroundColor Green
} catch {
    Write-Host "⚠ MySQL might not be running" -ForegroundColor Yellow
    Write-Host "   Make sure MySQL/MariaDB is running before continuing"
    Write-Host ""
}

Write-Host "Running migrations..."
php artisan migrate --fresh --seed
Write-Host ""
Write-Host "✓ Database setup complete" -ForegroundColor Green
Write-Host ""

Write-Host "═══════════════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "STEP 3: Starting Development Server..." -ForegroundColor Yellow
Write-Host "═══════════════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

Write-Host "Starting Laravel development server..." -ForegroundColor Yellow
Write-Host ""
Write-Host "Application will be available at: http://localhost:8000" -ForegroundColor Green
Write-Host ""
Write-Host "Login Credentials:" -ForegroundColor Cyan
Write-Host "  Email:    manager@vss.com" -ForegroundColor White
Write-Host "  Password: manager123" -ForegroundColor White
Write-Host ""
Write-Host "To stop the server, press Ctrl+C" -ForegroundColor Yellow
Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

# Start server
php artisan serve
