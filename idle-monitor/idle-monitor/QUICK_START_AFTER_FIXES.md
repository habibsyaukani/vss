# 🚀 Quick Start Guide - After Context Transfer Fixes

**Date**: 2026-07-15  
**Status**: Ready to test

---

## ⚡ STEP-BY-STEP TESTING

### Step 1: Clear All Caches (REQUIRED)
```bash
cd /home/khabib/vss/idle-monitor-new/idle-monitor

# Clear Laravel caches
docker exec idle-monitor-app php artisan config:clear
docker exec idle-monitor-app php artisan view:clear
docker exec idle-monitor-app php artisan route:clear
```

**Expected Output**: "Configuration cache cleared!", "Compiled views cleared!", "Route cache cleared!"

---

### Step 2: Verify Database Connection
```bash
# Test database connection
docker exec idle-monitor-app php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB Connected Successfully!\n';"
```

**Expected Output**: "DB Connected Successfully!"

**If Failed**: Check `.env` file has:
- `DB_HOST=mysql` (not 127.0.0.1)
- `DB_PASSWORD=root` (not empty)

---

### Step 3: Start Queue Worker (Background Processing)
```bash
# Start queue worker in background
docker exec -d idle-monitor-app php artisan queue:work --tries=3 --timeout=600

# Check if queue worker is running
docker exec idle-monitor-app php artisan queue:work --once
```

**Expected Output**: Queue worker processes jobs

**Note**: Queue worker should already be running via supervisor/systemd. Check with:
```bash
docker exec idle-monitor-app ps aux | grep queue
```

---

### Step 4: Open Web Interface
1. **URL**: http://vams.gpe.co.id:9097/admin/data-pull
2. **Hard Refresh**: Press `Ctrl+F5` to clear browser cache
3. **Login**: Use your admin credentials

---

### Step 5: Verify UI Changes

**Check these items**:
- [x] ✅ Only ONE concurrency option visible: "1 - Sequential (Aman dari Rate Limit, ~8-10 menit per hari)"
- [x] ✅ Default pages = 200
- [x] ✅ Red warning about parallel mode rate limit
- [x] ✅ Info box mentions "background queue"
- [x] ✅ Info says "Anda bisa menutup halaman ini"

**If old UI still shows**:
- Hard refresh again: `Ctrl+Shift+R`
- Clear browser cache completely
- Try incognito/private window
- Run: `docker exec idle-monitor-app php artisan view:clear`

---

### Step 6: Test Data Pull

**Test Parameters**:
- **Dari Tanggal**: 2026-07-14
- **Sampai Tanggal**: 2026-07-14 (single day test)
- **Jumlah Pages**: 200 (default)
- **Concurrency**: 1 - Sequential (only option)

**Click**: "Tarik Data Sekarang"

**Expected Response** (within 2-3 seconds):
```json
{
  "success": true,
  "message": "Penarikan data berhasil dimasukkan ke antrean! Data sedang ditarik di latar belakang (Background). Silakan refresh halaman ini dalam 1-2 menit."
}
```

**What This Means**:
- ✅ No gateway timeout (504)
- ✅ Quick response (background queue)
- ✅ Process continues even if you close browser

---

### Step 7: Monitor Progress

**Option A: Check Queue Jobs**
```bash
# Check pending jobs
docker exec idle-monitor-app php artisan queue:work --once

# Check failed jobs
docker exec idle-monitor-app php artisan queue:failed

# Check queue table
docker exec idle-monitor-mysql mysql -u root -proot vss -e "SELECT * FROM jobs;"
```

**Option B: Check Laravel Logs**
```bash
# Monitor logs in real-time
docker exec idle-monitor-app tail -f storage/logs/laravel.log

# Check last 50 lines
docker exec idle-monitor-app tail -n 50 storage/logs/laravel.log
```

**Look for**:
- ✅ "Processing alarm data for date range..."
- ✅ "Total records fetched: XXXX"
- ✅ "Idle alarms processed: XXX"
- ❌ NO "Rate limited (10129)" errors

---

### Step 8: Verify Data After 8-10 Minutes

**Check Database**:
```bash
# Check alarm_raw table
docker exec idle-monitor-mysql mysql -u root -proot vss -e "SELECT COUNT(*) as total, MIN(start_time) as earliest, MAX(start_time) as latest FROM alarm_raw WHERE DATE(start_time) = '2026-07-14';"

# Check idle_alarms table
docker exec idle-monitor-mysql mysql -u root -proot vss -e "SELECT COUNT(*) as total, MIN(starting_time) as earliest, MAX(starting_time) as latest FROM idle_alarms WHERE DATE(starting_time) = '2026-07-14';"
```

**Expected Results**:
- ✅ alarm_raw: ~40,000 records (200 pages × 200 records)
- ✅ idle_alarms: subset of alarm_raw (only idle events)
- ✅ earliest time: 2026-07-14 00:00:XX (start of day)
- ✅ latest time: 2026-07-14 23:59:XX (end of day)

**If coverage incomplete**:
- Increase pages to 300-400
- API might have more data than expected
- Check logs for any errors

---

## 🔍 TROUBLESHOOTING

### Problem: Gateway Timeout (504)
**Solution**: Already fixed! If still happening:
1. Verify controller uses `Artisan::queue()` not `Artisan::call()`
2. Check Nginx timeout settings
3. Verify queue worker is running

### Problem: Rate Limit Error (10129)
**Solution**: Already fixed! If still happening:
1. Verify only sequential mode available in UI
2. Check controller doesn't force parallel mode
3. User might be using old cached page - clear browser cache

### Problem: Database Connection Failed
**Solution**:
```bash
# Check .env configuration
docker exec idle-monitor-app cat .env | grep DB_

# Should show:
# DB_HOST=mysql
# DB_PASSWORD=root

# If wrong, edit .env and clear config:
docker exec idle-monitor-app php artisan config:clear
```

### Problem: No Data After Pull
**Solution**:
```bash
# Check if job failed
docker exec idle-monitor-app php artisan queue:failed

# Retry failed job
docker exec idle-monitor-app php artisan queue:retry <job-id>

# Check logs
docker exec idle-monitor-app tail -n 100 storage/logs/laravel.log | grep -i error
```

### Problem: Old UI Still Shows
**Solution**:
```bash
# Clear view cache
docker exec idle-monitor-app php artisan view:clear

# Rebuild views
docker exec idle-monitor-app php artisan view:cache

# In browser: Ctrl+Shift+R (hard refresh)
# Or use incognito mode
```

---

## 📊 SUCCESS CRITERIA

**All these should be TRUE**:
- [x] Request returns in < 5 seconds (no timeout)
- [x] Only sequential mode visible in UI
- [x] Default pages = 200
- [x] No rate limit errors in logs
- [x] Data covers full 24 hours (00:00-23:59)
- [x] Can close browser, process continues
- [x] Background queue processes data

---

## 🎯 NEXT STEPS (Optional Improvements)

### 1. Real-time Progress (Future Enhancement)
Implement WebSocket/Pusher for live updates:
- Progress bar updates automatically
- No need to refresh page
- See records processed in real-time

### 2. Notification on Completion
Send notification when pull completes:
- Email notification
- Browser notification (if page open)
- Telegram/Slack integration

### 3. Schedule Automatic Daily Pulls
Set up scheduler to auto-pull data:
```php
// app/Console/Kernel.php
$schedule->command('howen:pull-alarms-date-range', [
    '--from' => now()->subDay()->format('Y-m-d'),
    '--to' => now()->subDay()->format('Y-m-d'),
    '--pages' => 200,
])->dailyAt('02:00'); // 2 AM daily
```

### 4. Data Pull History
Track all pull operations:
- When pulled
- How many records
- Success/failure status
- Duration

---

## 📞 SUPPORT COMMANDS

### Check Application Status
```bash
docker ps
docker logs idle-monitor-app --tail 50
docker exec idle-monitor-app php artisan about
```

### Check Queue Status
```bash
docker exec idle-monitor-app php artisan queue:work --once
docker exec idle-monitor-app php artisan queue:failed
docker exec idle-monitor-mysql mysql -u root -proot vss -e "SELECT * FROM jobs LIMIT 10;"
```

### Check Database Stats
```bash
docker exec idle-monitor-mysql mysql -u root -proot vss -e "
  SELECT 
    'alarm_raw' as table_name, 
    COUNT(*) as total_records,
    MIN(start_time) as earliest,
    MAX(start_time) as latest
  FROM alarm_raw
  UNION ALL
  SELECT 
    'idle_alarms' as table_name,
    COUNT(*) as total_records,
    MIN(starting_time) as earliest,
    MAX(starting_time) as latest
  FROM idle_alarms;
"
```

---

## ✅ FINAL CHECKLIST

Before reporting success:
- [ ] Caches cleared (config, view, route)
- [ ] Database connection works
- [ ] Queue worker running
- [ ] UI shows only sequential mode
- [ ] UI shows pages default 200
- [ ] UI shows rate limit warning
- [ ] Test pull returns quickly (< 5 sec)
- [ ] No gateway timeout
- [ ] No rate limit errors
- [ ] Data appears after 8-10 minutes
- [ ] Data covers full 24 hours

---

**Ready to go!** 🚀

*Follow steps 1-8 in order for best results*
