# Fix Laragon Apache for Radmin VPN Access
# This script:
# 1. Verifies httpd.conf has correct Listen directive
# 2. Fixes it if needed
# 3. Adds Windows Firewall rule for Apache

Write-Host "======================================"
Write-Host "Laragon Apache Radmin VPN Fix Script"
Write-Host "======================================"
Write-Host ""

# Define Apache config path
$apacheConfPath = "C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\conf\httpd.conf"
$httpdExePath = "C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\bin\httpd.exe"

# Step 1: Check if file exists
Write-Host "[STEP 1] Checking Apache config file..."
if (Test-Path $apacheConfPath) {
    Write-Host "✓ File found: $apacheConfPath"
} else {
    Write-Host "✗ File NOT found: $apacheConfPath"
    Write-Host "Please check your Laragon installation path"
    exit
}

Write-Host ""

# Step 2: Read and check current Listen directive
Write-Host "[STEP 2] Checking current Listen directive..."
$content = Get-Content $apacheConfPath -Raw

# Check what's currently there
if ($content -match "Listen\s+0\.0\.0\.0:80") {
    Write-Host "✓ Already correct: Listen 0.0.0.0:80"
    $needsFix = $false
} elseif ($content -match "Listen\s+127\.0\.0\.1:80") {
    Write-Host "✗ Found incorrect: Listen 127.0.0.1:80"
    $needsFix = $true
} elseif ($content -match "Listen\s+localhost:80") {
    Write-Host "✗ Found incorrect: Listen localhost:80"
    $needsFix = $true
} else {
    Write-Host "? Could not find Listen 80 directive"
    $needsFix = $false
}

Write-Host ""

# Step 3: Fix if needed
if ($needsFix) {
    Write-Host "[STEP 3] Fixing httpd.conf..."
    
    # Create backup
    $backupPath = "$apacheConfPath.backup.$(Get-Date -Format 'yyyyMMdd-HHmmss')"
    Copy-Item $apacheConfPath $backupPath
    Write-Host "✓ Backup created: $backupPath"
    
    # Replace the Listen directive
    $content = $content -replace "Listen\s+127\.0\.0\.1:80", "Listen 0.0.0.0:80"
    $content = $content -replace "Listen\s+localhost:80", "Listen 0.0.0.0:80"
    
    # Write back
    Set-Content $apacheConfPath $content -Encoding UTF8
    Write-Host "✓ httpd.conf fixed: Changed to Listen 0.0.0.0:80"
} else {
    Write-Host "[STEP 3] No fixes needed for httpd.conf"
}

Write-Host ""

# Step 4: Add Windows Firewall rule for Apache
Write-Host "[STEP 4] Adding Windows Firewall rule..."

# Check if rule already exists
$existingRule = Get-NetFirewallRule -DisplayName "Apache Laragon" -ErrorAction SilentlyContinue

if ($existingRule) {
    Write-Host "✓ Firewall rule already exists: 'Apache Laragon'"
} else {
    Write-Host "Creating new firewall rule..."
    
    try {
        New-NetFirewallRule -DisplayName "Apache Laragon" `
                           -Direction Inbound `
                           -Program $httpdExePath `
                           -Action Allow `
                           -Protocol TCP `
                           -LocalPort 80 `
                           -ErrorAction Stop
        Write-Host "✓ Firewall rule created successfully"
    } catch {
        Write-Host "✗ Error creating firewall rule: $_"
        Write-Host "Try running as Administrator"
    }
}

Write-Host ""
Write-Host "======================================"
Write-Host "✓ Fix Complete!"
Write-Host "======================================"
Write-Host ""
Write-Host "Next steps:"
Write-Host "1. Restart Laragon (Stop → Start)"
Write-Host "2. Test: http://localhost:8000"
Write-Host "3. Test: http://26.29.218.176:8000"
Write-Host ""
