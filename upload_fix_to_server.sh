#!/bin/bash
# ====================================================
# Upload Fixed Files to Server
# ====================================================

SERVER="103.130.6.115"
USER="khabib"
PASSWORD="gpe13579"
REMOTE_PATH="/home/khabib/vss/idle-monitor-new/idle-monitor"
LOCAL_PATH="/g/project/vss/idle-monitor"

echo ""
echo "=========================================="
echo "UPLOAD FIXES TO SERVER"
echo "=========================================="
echo ""

# Install sshpass if not available
if ! command -v sshpass &> /dev/null; then
    echo "Installing sshpass..."
    # For Git Bash on Windows, use winget or manual install
    echo "ERROR: sshpass not available on Windows Git Bash"
    echo "Use alternative method below..."
    exit 1
fi

echo "Uploading data-pull.blade.php..."
sshpass -p "$PASSWORD" scp "$LOCAL_PATH/resources/views/admin/data-pull.blade.php" $USER@$SERVER:$REMOTE_PATH/resources/views/admin/data-pull.blade.php

echo "Uploading .env..."
sshpass -p "$PASSWORD" scp "$LOCAL_PATH/.env" $USER@$SERVER:$REMOTE_PATH/.env

echo ""
echo "Clearing cache..."
sshpass -p "$PASSWORD" ssh $USER@$SERVER << 'ENDSSH'
docker exec idle-monitor-app php artisan config:clear
docker exec idle-monitor-app php artisan view:clear
docker exec idle-monitor-app php artisan route:clear
ENDSSH

echo ""
echo "=========================================="
echo "UPLOAD COMPLETE!"
echo "=========================================="
echo ""
