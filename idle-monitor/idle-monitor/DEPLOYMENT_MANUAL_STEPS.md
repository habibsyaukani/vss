# 🚀 MANUAL DEPLOYMENT STEPS - BATCH DATA PULL

**Date**: July 16, 2026  
**Method**: Git Pull (Recommended)  
**Server**: 103.130.6.115 (khabib@dash-serv)

---

## ⚡ QUICK DEPLOYMENT (Copy-Paste Commands)

### STEP 1: SSH ke Server

```bash
ssh khabib@103.130.6.115
# Enter password when prompted
```

---

### STEP 2: Navigate & Backup

```bash
# Navigate to application directory
cd /home/khabib/vss/idle-monitor-new/idle-monitor
pwd

# Backup existing files (safety measure)
mkdir -p backups/batch-pull-$(date +%Y%m%d-%H%M%S)
cp app/Http/Controllers/DataPullController.php backups/batch-pull-$(date +%Y%m%d-%H%M%S)/
cp routes/admin.php backups/batch-pull-$(date +%Y%m%d-%H%M%S)/
cp resources/views/admin/data-pull.blade.php backups/batch-pull-$(date +%Y%m%d-%H%M%S)/
cp public/js/data-pull.js backups/batch-pull-$(date +%Y%m%d-%H%M%S)/

echo "✅ Backup completed"
```

---

### STEP 3: Git Pull Latest Code

```bash
# Check git status
git status

# Fetch latest from origin
git fetch origin

# Pull main branch (contains commit 0c927da with batch pull feature)
git pull origin main

# Verify files updated
ls -lh app/Models/DataPullBatch.php
ls -lh app/Jobs/DataPullOrchestratorJob.php
ls -lh app/Jobs/DataPullBatchJob.php
```

**Expected Output**:
```
✅ app/Models/DataPullBatch.php exists
✅ app/Jobs/DataPullOrchestratorJob.php exists
✅ app/Jobs/DataPullBatchJob.php exists
```

---

### STEP 4: Clear Laravel Caches

```bash
# Clear all Laravel caches (run inside Docker container)
docker exec idle-monitor-app php artisan route:clear
docker exec idle-monitor-app php artisan config:clear
docker exec idle-monitor-app php artisan view:clear
docker exec idle-monitor-app php artisan cache:clear

# Optional: Regenerate autoload files
docker exec idle-monitor-app composer dump-autoload
```

---

### STEP 5: Restart Docker Containers

```bash
# Restart containers to load new code
docker restart idle-monitor-app idle-monitor-worker idle-monitor-scheduler

# Wait for containers to restart
sleep 10

# Verify containers running
docker ps | grep idle-monitor
```

**Expected Output**:
```
idle-monitor-app        Up X seconds
idle-monitor-worker     Up X seconds
idle-monitor-scheduler  Up X seconds
idle-monitor-mysql      Up X days
idle-monitor-web        Up X days
```

---

### STEP 6: Verify Database Setup

```bash
# Check if table exists (should already exist from previous migration)
docker exec idle-monitor-app php artisan tinker --execute="var_dump(Schema::hasTable('data_pull_batches'));"
```

**Expected Output**: `bool(true)` ✅

---

### STEP 7: Test Class Autoloading

```bash
# Verify new classes can be loaded
docker exec idle-monitor-app php artisan tinker --execute="echo class_exists('App\Models\DataPullBatch') ? 'Model OK' : 'NOT FOUND';"
docker exec idle-monitor-app php artisan tinker --execute="echo class_exists('App\Jobs\DataPullOrchestratorJob') ? 'Job OK' : 'NOT FOUND';"
```

**Expected Output**: 
```
Model OK
Job OK
```

---

### STEP 8: Monitor Logs (Optional)

```bash
# Monitor application logs
docker logs -f idle-monitor-app

# In another terminal, monitor worker logs
docker logs -f idle-monitor-worker
```

**Press Ctrl+C to stop following logs**

---

## 🧪 TEST THE FEATURE

### Browser Test:

1. Open browser: **http://vams.gpe.co.id:9097/admin/data-pull**
2. Login as admin
3. Pilih tanggal: **16/07/2026** (atau tanggal lain)
4. Klik: **"Tarik Data Sekarang"**
5. **Verify**:
   - ✅ Browser tidak timeout (response < 1 detik)
   - ✅ Progress container muncul
   - ✅ 8 batch displayed (Batch 1-8)
   - ✅ Auto-refresh setiap 3 detik
   - ✅ Status berubah: Pending → Processing → Completed

### Database Verification:

```bash
# Check batch records
docker exec idle-monitor-app php artisan tinker

# In tinker:
\App\Models\DataPullBatch::latest()->take(5)->get(['session_id', 'batch_number', 'status', 'total_records']);

# Check idle alarms data
DB::table('idle_alarms')->whereDate('starting_time', '2026-07-16')->count();

# Exit tinker
exit
```

---

## 🔧 TROUBLESHOOTING

### Issue 1: Git pull shows "Already up to date" but files missing

**Solution**:
```bash
# Force reset to remote
git fetch origin
git reset --hard origin/main
```

### Issue 2: Class not found error

**Solution**:
```bash
# Regenerate autoload files
docker exec idle-monitor-app composer dump-autoload
docker restart idle-monitor-app idle-monitor-worker
```

### Issue 3: Progress tidak update (batch stuck on Pending)

**Solution**:
```bash
# Restart queue worker
docker restart idle-monitor-worker

# Check queue worker logs
docker logs -f idle-monitor-worker
```

### Issue 4: 404 on progress endpoint

**Solution**:
```bash
# Clear route cache
docker exec idle-monitor-app php artisan route:clear

# Verify route exists
docker exec idle-monitor-app php artisan route:list | grep progress
```

---

## 🛡️ ROLLBACK (If Issues Found)

```bash
# Navigate to backup
cd /home/khabib/vss/idle-monitor-new/idle-monitor
ls -lh backups/

# Restore from backup
cp backups/batch-pull-YYYYMMDD-HHMMSS/DataPullController.php app/Http/Controllers/
cp backups/batch-pull-YYYYMMDD-HHMMSS/admin.php routes/
cp backups/batch-pull-YYYYMMDD-HHMMSS/data-pull.blade.php resources/views/admin/
cp backups/batch-pull-YYYYMMDD-HHMMSS/data-pull.js public/js/

# Delete new files
rm app/Models/DataPullBatch.php
rm app/Jobs/DataPullOrchestratorJob.php
rm app/Jobs/DataPullBatchJob.php

# Clear caches & restart
docker exec idle-monitor-app php artisan route:clear
docker exec idle-monitor-app php artisan config:clear
docker restart idle-monitor-app idle-monitor-worker
```

---

## ✅ DEPLOYMENT CHECKLIST

```markdown
PRE-DEPLOYMENT:
[✅] Git push successful (commit 0c927da)
[✅] Migration executed (table exists)
[✅] Queue configured (QUEUE_CONNECTION=database)
[✅] Documentation ready

DEPLOYMENT STEPS:
[ ] SSH to server (103.130.6.115)
[ ] Navigate to app directory
[ ] Backup existing files
[ ] Git pull origin main
[ ] Verify new files exist
[ ] Clear Laravel caches
[ ] Restart Docker containers
[ ] Verify containers running

VERIFICATION:
[ ] Table exists (Schema::hasTable check)
[ ] Classes autoload (class_exists check)
[ ] Browser test (no timeout)
[ ] Progress displays (8 batches)
[ ] Auto-refresh works (3 seconds)
[ ] Data saved to database

SUCCESS CRITERIA:
[ ] User can pull data without 504 timeout
[ ] Progress real-time displayed
[ ] All 8 batches completed
[ ] Data in idle_alarms table
[ ] No errors in logs
[ ] Existing features still working
```

---

## 📞 SUPPORT COMMANDS

### Check Application Status:
```bash
docker ps | grep idle-monitor
docker exec idle-monitor-app php artisan --version
```

### Check Queue Status:
```bash
docker exec idle-monitor-app php artisan queue:work --once --verbose
```

### Check Database:
```bash
docker exec idle-monitor-mysql mysql -uvss -p vss -e "SHOW TABLES LIKE 'data_pull_batches';"
```

### Check Logs:
```bash
docker exec idle-monitor-app tail -100 storage/logs/laravel.log
docker logs --tail 100 idle-monitor-app
docker logs --tail 100 idle-monitor-worker
```

---

**🎉 Ready to Deploy!**

Copy-paste commands di atas satu per satu ke terminal SSH Anda.

