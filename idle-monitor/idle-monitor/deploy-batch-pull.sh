#!/bin/bash
# ========================================
# BATCH DATA PULL - ONE-COMMAND DEPLOYMENT
# ========================================
# Usage: bash deploy-batch-pull.sh
# ========================================

set -e  # Exit on error

echo "🚀 BATCH DATA PULL DEPLOYMENT"
echo "=============================="
echo ""

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Navigate
echo -e "${BLUE}📍 Navigating to application directory...${NC}"
cd /home/khabib/vss/idle-monitor-new/idle-monitor || { echo -e "${RED}❌ Failed to navigate${NC}"; exit 1; }
echo -e "${GREEN}✅ Current directory: $(pwd)${NC}"
echo ""

# Backup
echo -e "${BLUE}💾 Creating backup...${NC}"
BACKUP_DIR="backups/batch-pull-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP_DIR"
cp app/Http/Controllers/DataPullController.php "$BACKUP_DIR/" 2>/dev/null || true
cp routes/admin.php "$BACKUP_DIR/" 2>/dev/null || true
cp resources/views/admin/data-pull.blade.php "$BACKUP_DIR/" 2>/dev/null || true
cp public/js/data-pull.js "$BACKUP_DIR/" 2>/dev/null || true
echo -e "${GREEN}✅ Backup saved to: $BACKUP_DIR${NC}"
echo ""

# Git pull
echo -e "${BLUE}📥 Pulling latest code from Git...${NC}"
git fetch origin
git pull origin main
echo -e "${GREEN}✅ Git pull completed${NC}"
echo ""

# Verify files
echo -e "${BLUE}🔍 Verifying new files...${NC}"
[ -f "app/Models/DataPullBatch.php" ] && echo -e "${GREEN}  ✅ DataPullBatch.php${NC}" || echo -e "${RED}  ❌ DataPullBatch.php NOT FOUND${NC}"
[ -f "app/Jobs/DataPullOrchestratorJob.php" ] && echo -e "${GREEN}  ✅ DataPullOrchestratorJob.php${NC}" || echo -e "${RED}  ❌ DataPullOrchestratorJob.php NOT FOUND${NC}"
[ -f "app/Jobs/DataPullBatchJob.php" ] && echo -e "${GREEN}  ✅ DataPullBatchJob.php${NC}" || echo -e "${RED}  ❌ DataPullBatchJob.php NOT FOUND${NC}"
echo ""

# Clear caches
echo -e "${BLUE}🧹 Clearing Laravel caches...${NC}"
docker exec idle-monitor-app php artisan route:clear
docker exec idle-monitor-app php artisan config:clear
docker exec idle-monitor-app php artisan view:clear
docker exec idle-monitor-app php artisan cache:clear
docker exec idle-monitor-app composer dump-autoload -q
echo -e "${GREEN}✅ Caches cleared${NC}"
echo ""

# Restart containers
echo -e "${BLUE}🔄 Restarting Docker containers...${NC}"
docker restart idle-monitor-app idle-monitor-worker idle-monitor-scheduler
echo "⏳ Waiting 10 seconds for containers to restart..."
sleep 10
echo -e "${GREEN}✅ Containers restarted${NC}"
echo ""

# Verify containers
echo -e "${BLUE}🔍 Verifying container status...${NC}"
docker ps --filter "name=idle-monitor" --format "table {{.Names}}\t{{.Status}}"
echo ""

# Verify database
echo -e "${BLUE}🗄️ Verifying database setup...${NC}"
docker exec idle-monitor-app php artisan tinker --execute="echo Schema::hasTable('data_pull_batches') ? '✅ Table exists' : '❌ Table NOT found'; echo PHP_EOL;" 2>/dev/null || true
echo ""

# Test autoloading
echo -e "${BLUE}🧪 Testing class autoloading...${NC}"
docker exec idle-monitor-app php artisan tinker --execute="echo class_exists('App\Models\DataPullBatch') ? '✅ DataPullBatch loaded' : '❌ Model NOT found'; echo PHP_EOL;" 2>/dev/null || true
docker exec idle-monitor-app php artisan tinker --execute="echo class_exists('App\Jobs\DataPullOrchestratorJob') ? '✅ OrchestratorJob loaded' : '❌ Job NOT found'; echo PHP_EOL;" 2>/dev/null || true
echo ""

echo -e "${GREEN}🎉 DEPLOYMENT COMPLETED SUCCESSFULLY!${NC}"
echo ""
echo "=============================="
echo "📋 NEXT STEPS:"
echo "=============================="
echo ""
echo "1. Open browser: http://vams.gpe.co.id:9097/admin/data-pull"
echo "2. Login as admin"
echo "3. Pilih tanggal & test pull data"
echo "4. Monitor logs: docker logs -f idle-monitor-app"
echo ""
echo "=============================="
echo "🔧 TROUBLESHOOTING:"
echo "=============================="
echo ""
echo "Check logs:"
echo "  docker logs -f idle-monitor-app"
echo "  docker logs -f idle-monitor-worker"
echo ""
echo "Check database:"
echo "  docker exec idle-monitor-app php artisan tinker"
echo "  >>> \App\Models\DataPullBatch::count()"
echo ""
echo "Rollback if needed:"
echo "  cp $BACKUP_DIR/* <original-locations>"
echo ""
