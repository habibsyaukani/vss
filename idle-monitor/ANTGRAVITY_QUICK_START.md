# 🚀 ANTIGRAVITY QUICK START GUIDE
**Idle Monitor Project - June 5, 2026**

---

## ⚡ 5 MENIT - GET STARTED

### Step 1: Read Project Status (2 min)
```
📄 Open: DEVELOPMENT_PROGRESS.md
📄 Open: HANDOFF_TO_ANTIGRAVITY.md (full documentation)
📄 Read: .kiro/SYSTEM_RULES.md (MUST READ - protection rules)
```

### Step 2: Verify System Ready (1 min)
```bash
cd g:\project\vss\idle-monitor
php VERIFY_TAHAP12.php
# Should show ✅ All checks passed
```

### Step 3: Start Scheduler (1 min)
```bash
double-click: START_SCHEDULER_TAHAP12.bat
# Should show: 🔄 Starting scheduler...
```

### Step 4: Check Data Flow (1 min)
```bash
# Wait 3 minutes for scheduler to run
# Then check database:
php artisan tinker
>>> DB::table('idle_alarms')->latest()->first()
# Should show fresh data from June 5, 2026
```

---

## 📊 CURRENT PROJECT STATE

### Status: ✅ PRODUCTION READY

| Aspect | Status | Details |
|--------|--------|---------|
| Database | ✅ Live | 1,265 idle alarms |
| Scheduler | ✅ Configured | Every 3 minutes |
| API | ✅ Working | 8 endpoints |
| Frontend | ✅ 2 Dashboards | Admin + Fleet Manager |
| Data | ✅ Flowing | Real-time <1sec latency |

### Data Today (June 5, 2026):
```
Mei 2026:    16 records (only 25 May has data)
Juni 2026:   1,249 records (always fresh)
Total:       1,265 valid idle alarms
```

---

## 🎯 WHAT YOU NEED TO KNOW

### 1. **Database** (9 tables)
```
Key tables:
  idle_alarms     ← Main table (what frontend uses)
  alarm_raw       ← Raw API import
  devices         ← 397 vehicles
  users           ← Admin + Fleet Manager roles
  system_settings ← Progress tracking
```

### 2. **Scheduler** (runs every 3 minutes)
```
Job: Pull full range (1 Mei - Today) with 5 parallel connections
Result: Data in idle_alarms within 1 second
Smart: Only imports new data (updateOrCreate by GUID)
```

### 3. **Frontend** (2 roles)
```
Admin panel:      /admin/dashboard (full control)
Fleet Manager:    /dashboard (read-only summary)
```

### 4. **Commands** (3 available)
```
Main:       howen:pull-alarms-date-range (every 3 min - ACTIVE)
Alternative 1: howen:pull-alarms-per-day (every 2 min - optional)
Alternative 2: howen:pull-alarms-realtime (every 30 sec - optional)
```

---

## ⚠️ CRITICAL: DO NOT TOUCH

```
❌ Never modify these files:
   - app/Jobs/ImportAlarmPageJob.php
   - app/Jobs/ProcessIdleAlarmJob.php
   - app/Jobs/SyncDeviceJob.php
   - Database schema (unless absolutely needed)

❌ Never run these commands:
   - php artisan migrate:fresh
   - php artisan db:wipe
   - TRUNCATE TABLE anywhere

✅ Always use migrations for schema changes
✅ Always test before pushing
✅ Always check SYSTEM_RULES.md first
```

---

## 🔧 COMMON TASKS

### Check if Scheduler Running:
```bash
# In separate terminal:
# Windows:
tasklist | findstr schedule
# Mac/Linux:
ps aux | grep schedule:work
```

### Manually Pull Data:
```bash
php artisan howen:pull-alarms-date-range --from=2026-05-01 --to=2026-06-05 --pages=50 --parallel --concurrency=5 --wait
```

### Check Recent Data:
```bash
php artisan tinker
>>> DB::table('idle_alarms')->orderByDesc('starting_time')->first()
```

### View Admin Dashboard:
```
http://localhost:8000/admin/dashboard
Email: admin@example.com
Pass: password123
```

### View Fleet Manager Dashboard:
```
http://localhost:8000/dashboard
Email: fleet@example.com
Pass: password123
```

---

## 📈 EXPECTED BEHAVIOR

### When Scheduler Starts:
```
Minute 0:
  ✅ Token refreshed
  ✅ Devices synced
  ✅ Data pull started

Minute 0-10:
  ✅ 1,200+ records imported
  ✅ Idle processing done
  ✅ idle_alarms table filled

Then Every 3 Minutes:
  ✅ New data checked
  ✅ Only new items imported
  ✅ idle_alarms auto-updated
```

---

## 🆘 IF SOMETHING BREAKS

### No Data:
```bash
1. Check scheduler running: ps aux | grep schedule
2. Check logs: tail -f storage/logs/laravel.log
3. Test API manually: php artisan howen:pull-alarms-date-range --wait
4. Check .env credentials: HOWEN_API_URL, HOWEN_USERNAME, HOWEN_PASSWORD
```

### Duplicates in Database:
```bash
# This shouldn't happen (updateOrCreate prevents it)
# But if it does:
1. Check alarm_raw.guid uniqueness
2. Run: php VERIFY_TAHAP12.php
3. Contact for help
```

### Scheduler Not Running:
```bash
1. Kill any existing: taskkill /F /IM php.exe (careful!)
2. Clear cache: php artisan cache:clear
3. Restart: START_SCHEDULER_TAHAP12.bat
```

---

## 📚 DOCUMENTATION STRUCTURE

```
DEVELOPMENT_PROGRESS.md
├── Overview (what is Idle Monitor)
├── TAHAP 1-12 (each phase explained)
├── Current status (data numbers)
├── Commands reference
├── Frontend routes
└── API endpoints

HANDOFF_TO_ANTIGRAVITY.md
├── Project overview
├── Current status
├── TAHAP 12 details
├── How to start
├── Troubleshooting
└── Next steps

SYSTEM_RULES.md
├── Core rules (do's and don'ts)
├── Database protection
├── File protection
├── API protection
└── Backward compatibility requirements
```

---

## ✅ FIRST WEEK CHECKLIST

- [ ] Read DEVELOPMENT_PROGRESS.md (understand what exists)
- [ ] Read HANDOFF_TO_ANTIGRAVITY.md (complete handoff info)
- [ ] Read .kiro/SYSTEM_RULES.md (understand rules)
- [ ] Run VERIFY_TAHAP12.php (check system status)
- [ ] Start scheduler (START_SCHEDULER_TAHAP12.bat)
- [ ] Monitor idle_alarms table growth (should grow every 3 min)
- [ ] Test admin dashboard (login, check data)
- [ ] Test fleet manager dashboard (login, check data)
- [ ] Test API endpoints (curl or postman)
- [ ] Check logs for errors (storage/logs/laravel.log)

---

## 🎓 UNDERSTANDING THE ARCHITECTURE

### Data Flow:
```
Howen API
    ↓
ImportAlarmJob (every 2 min)
    ↓
ImportAlarmPageJob (per-page)
    ↓
alarm_raw table ← RAW DATA STORED HERE
    ↓
ProcessIdleAlarmJob (every 5 min) [+ real-time trigger]
    ↓
VALIDATION:
  - start_speed = 0?
  - end_speed > 0?
  - duration >= 5 min?
  - alarm_state = 1 (ended)?
    ↓
idle_alarms table ← VALID DATA ONLY
    ↓
REST API (/api/idle-alarms)
    ↓
Frontend Dashboards (Admin + Fleet Manager)
```

### Why Two Tables?
- **alarm_raw**: Raw API data (audit trail, for debugging)
- **idle_alarms**: Validated data (what users see)

### Why Scheduler Every 3 Minutes?
- Fast enough: Data feels real-time to users
- Safe: ~20 API calls/hour (no rate limit issues)
- Efficient: Parallel 5 connections for speed

---

## 🚀 TAHAP 13 IDEAS (Future)

When ready for next phase:
1. **Real-time Notifications**: Slack/Email when idle detected
2. **Mobile App**: Flutter/React Native
3. **Advanced Analytics**: Heatmaps, predictions
4. **Export Reports**: PDF, Excel, CSV
5. **Fuel Integration**: Combine with fuel consumption
6. **Driver Behavior**: Score drivers by idle frequency
7. **Geofencing**: Alert when parked outside zones

---

## 📞 QUICK REFERENCE

| Need | Command |
|------|---------|
| Start scheduler | `START_SCHEDULER_TAHAP12.bat` |
| Manual pull | `php artisan howen:pull-alarms-date-range --wait` |
| Check status | `php VERIFY_TAHAP12.php` |
| Database access | `php artisan tinker` |
| Clear cache | `php artisan cache:clear` |
| Check logs | `tail -f storage/logs/laravel.log` |
| Test API | Postman: `GET http://localhost:8000/api/idle-alarms` |

---

## 🎉 YOU'RE READY!

1. ✅ System is built and tested
2. ✅ Scheduler is configured
3. ✅ Data is flowing
4. ✅ Dashboards are ready
5. ✅ Documentation is complete

**Just start the scheduler and monitor!**

```bash
START_SCHEDULER_TAHAP12.bat
```

---

**Welcome to the Antigravity team! 🚀**
**Questions? Check HANDOFF_TO_ANTIGRAVITY.md**
**Need rules? Read .kiro/SYSTEM_RULES.md**
**Want details? Open DEVELOPMENT_PROGRESS.md**

