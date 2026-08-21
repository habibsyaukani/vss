#!/bin/bash
# ========================================
# DEPLOYMENT SCRIPT - BATCH DATA PULL
# ========================================
# Date: July 16, 2026
# Deployment Method: Git Pull
# ========================================

echo "🚀 Starting Batch Data Pull Deployment..."
echo ""

# STEP 1: SSH ke server dan navigate
echo "📍 STEP 1: Navigate to application directory"
cd /home/khabib/vss/idle-monitor-new/idle-monitor || { echo "❌ Failed to navigate"; exit 1; }
pwd
echo "✅ Current directory confirmed"
echo ""

# STEP 2: Backup existing files (safety)
echo "💾 STEP 2: Backup existing files"
mkdir -p backups/batch-pull-$(date +%Y%m%d-%H%M%S)
cp app/Http/Controllers/DataPullController.php backups/batch-pull-$(date +%Y%m%d-%H%M%S)/ 2>/dev/null
cp routes/admin.php backups/batch-pull-$(date +%Y%m%d-%H%M%S)/ 2>/dev/null
cp resources/views/admin/data-pull.blade.php backups/batch-pull-$(date +%Y%m%d-%H%M%S)/ 2>/dev/null
cp public/js/data-pull.js backups/batch-pull-$(date +%Y%m%d-%H%M%S)/ 2>/dev/null
echo "✅ Backup completed"
echo ""

# STEP 3: Git pull latest code
echo "📥 STEP 3: Git pull from repository"
git status
git fetch origin
git pull origin main
echo "✅ Git pull completed"
echo ""

# STEP 4: Verify new files exist
echo "🔍 STEP 4: Verify new files uploaded"
echo "Checking new files..."
ls -lh app/Models/DataPullBatch.php 2>/dev/null && echo "  ✅ DataPullBatch.php" || echo "  ❌ DataPullBatch.php NOT FOUND"
ls -lh app/Jobs/DataPullOrchestratorJob.php 2>/dev/null && echo "  ✅ DataPullOrchestratorJob.php" || echo "  ❌ DataPullOrchestratorJob.php NOT FOUND"
ls -lh app/Jobs/DataPullBatchJob.php 2>/dev/null && echo "  ✅ DataPullBatchJob.php" || echo "  ❌ DataPullBatchJob.php NOT FOUND"
echo ""

# STEP 5: Clear caches (inside Docker container)
echo "🧹 STEP 5: Clear Laravel caches"
docker exec idle-monitor-app php artisan route:clear
docker exec idle-monitor-app php artisan config:clear
docker exec idle-monitor-app php artisan view:clear
docker exec idle-monitor-app php artisan cache:clear
echo "✅ Caches cleared"
echo ""

# STEP 6: Restart Docker containers
echo "🔄 STEP 6: Restart Docker containers"
docker restart idle-monitor-app idle-monitor-worker idle-monitor-scheduler
echo "⏳ Waiting 10 seconds for containers to restart..."
sleep 10
echo "✅ Containers restarted"
echo ""

# STEP 7: Verify containers running
echo "🔍 STEP 7: Verify containers status"
docker ps | grep idle-monitor
echo ""

# STEP 8: Verify migration & table
echo "🗄️ STEP 8: Verify database setup"
docker exec idle-monitor-app php artisan tinker --execute="echo Schema::hasTable('data_pull_batches') ? '✅ Table exists' : '❌ Table NOT found'; echo PHP_EOL;"
echo ""

# STEP 9: Test queue can access new classes
echo "🧪 STEP 9: Test class autoloading"
docker exec idle-monitor-app php artisan tinker --execute="echo class_exists('App\Models\DataPullBatch') ? '✅ DataPullBatch loaded' : '❌ Model NOT found'; echo PHP_EOL;"
docker exec idle-monitor-app php artisan tinker --execute="echo class_exists('App\Jobs\DataPullOrchestratorJob') ? '✅ OrchestratorJob loaded' : '❌ Job NOT found'; echo PHP_EOL;"
echo ""

echo "🎉 Deployment completed!"
echo ""
echo "📋 NEXT STEPS:"
echo "1. Open browser: http://vams.gpe.co.id:9097/admin/data-pull"
echo "2. Login as admin"
echo "3. Pilih tanggal & test pull data"
echo "4. Monitor logs: docker logs -f idle-monitor-app"
echo ""
echo "🔧 TROUBLESHOOTING:"
echo "- If class not found: docker exec idle-monitor-app composer dump-autoload"
echo "- If queue stuck: docker restart idle-monitor-worker"
echo "- Check logs: docker exec idle-monitor-app tail -f storage/logs/laravel.log"
echo ""
