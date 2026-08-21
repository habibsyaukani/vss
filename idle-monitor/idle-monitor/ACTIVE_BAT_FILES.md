# 🎯 ACTIVE BAT FILES - QUICK REFERENCE

**Last Updated**: June 26, 2026  
**Total Active Files**: 17  
**Obsolete Files Removed**: 27

---

## 🚀 PRIMARY BATCH FILES (Start Here)

### 1. `RUN_WITH_LARAGON.bat`
**Purpose**: Complete setup + start server (First time or after reset)  
**When to use**: 
- First time setup
- After database reset
- After major changes
- Need fresh database

**What it does**:
- ✓ Detect PHP from Laragon
- ✓ Run database migrations
- ✓ Seed test data
- ✓ Start development server
- ✓ Open browser automatically

**Usage**:
```bash
cd g:\project\vss\idle-monitor
RUN_WITH_LARAGON.bat
```

---

### 2. `RUN_DASHBOARD.bat`
**Purpose**: Quick start server (Regular use)  
**When to use**: 
- Starting work (after RUN_WITH_LARAGON done once)
- Regular server restarts
- Quick server start

**What it does**:
- ✓ Start Laravel development server
- ✓ No setup overhead
- ✓ Assumes database already setup

**Usage**:
```bash
cd g:\project\vss\idle-monitor
RUN_DASHBOARD.bat
```

---

## 🔄 BACKGROUND JOB FILES

### 3. `START_SCHEDULER.bat`
**Purpose**: Start Laravel scheduler for background tasks  
**When to use**: 
- Need to run scheduled tasks
- Want automatic imports to work
- Development with task scheduling

**What it does**:
- ✓ Start scheduler that runs tasks every X minutes
- ✓ ImportAlarmJob, ProcessIdleAlarmJob, RefreshTokenJob, etc.

**Usage**:
```bash
# Run in separate terminal/window
START_SCHEDULER.bat
```

**Note**: Keep running in background while working

---

### 4. `START_QUEUE_WORKER.bat`
**Purpose**: Start queue worker for processing jobs  
**When to use**: 
- Need to process queued jobs
- Want data imports to actually process
- Development environment

**What it does**:
- ✓ Process jobs from queue
- ✓ Handle background tasks
- ✓ ImportAlarmJob, ProcessIdleAlarmJob, etc.

**Usage**:
```bash
# Run in separate terminal/window
START_QUEUE_WORKER.bat
```

**Note**: Keep running in background while working

---

## 🔧 DATA FIX FILES (Maintenance)

### 5. `FIX_DURATION_DRY_RUN.bat`
**Purpose**: Preview duration fixes (dry-run mode)  
**Status**: For fixing duration_seconds = 0 issues  
**When to use**: 
- Before applying fixes
- Want to see what will be changed
- Verify fix logic

**Usage**:
```bash
FIX_DURATION_DRY_RUN.bat
# View what will be fixed without modifying database
```

---

### 6. `FIX_DURATION_APPLY.bat`
**Purpose**: Apply duration fixes to database  
**Status**: For fixing duration_seconds = 0 issues  
**When to use**: 
- After FIX_DURATION_DRY_RUN confirms changes are correct
- Ready to modify database

**Usage**:
```bash
FIX_DURATION_APPLY.bat
# Actually apply fixes to database
```

---

### 7. `FIX_START_DETAIL_DRY_RUN.bat`
**Purpose**: Preview start_detail fixes (dry-run mode)  
**Status**: For fixing start_detail showing dur:0  
**When to use**: 
- Before applying fixes
- Want to see what will be changed

**Usage**:
```bash
FIX_START_DETAIL_DRY_RUN.bat
# View what will be fixed without modifying database
```

---

### 8. `FIX_START_DETAIL_APPLY.bat`
**Purpose**: Apply start_detail fixes to database  
**Status**: For fixing start_detail showing dur:0  
**When to use**: 
- After FIX_START_DETAIL_DRY_RUN confirms changes are correct
- Ready to modify database

**Usage**:
```bash
FIX_START_DETAIL_APPLY.bat
# Actually apply fixes to database
```

---

## 📥 BACKFILL FILES (Legacy Data)

### 9. `BACKFILL_START_DETAIL_DRY_RUN.bat`
**Purpose**: Preview backfill of start_detail from raw_json  
**Status**: For filling empty start_detail column  
**When to use**: 
- Before applying backfill
- Want to see scope of changes

**Usage**:
```bash
BACKFILL_START_DETAIL_DRY_RUN.bat
# View what will be backfilled without modifying database
```

---

### 10. `BACKFILL_START_DETAIL_APPLY.bat`
**Purpose**: Apply backfill of start_detail from raw_json  
**Status**: For filling empty start_detail column (~60% of records)  
**When to use**: 
- After BACKFILL_START_DETAIL_DRY_RUN confirms changes
- Ready to fill empty start_detail values

**Usage**:
```bash
BACKFILL_START_DETAIL_APPLY.bat
# Actually backfill start_detail column
```

---

## 📱 IMPORT/UPDATE FILES

### 11. `IMPORT_DEVICES_TO_DATABASE.bat`
**Purpose**: Import devices from CSV to database  
**Status**: For device synchronization  
**When to use**: 
- Need to import device list from file
- Update device database

**Usage**:
```bash
IMPORT_DEVICES_TO_DATABASE.bat
```

---

### 12. `UPDATE_DEVICES_DRY_RUN.bat`
**Purpose**: Preview device updates (dry-run mode)  
**Status**: For updating device data  
**When to use**: 
- Before applying device updates
- Want to see what will be changed

**Usage**:
```bash
UPDATE_DEVICES_DRY_RUN.bat
# View what will be updated without modifying database
```

---

### 13. `UPDATE_DEVICES_APPLY.bat`
**Purpose**: Apply device updates to database  
**Status**: For updating device data  
**When to use**: 
- After UPDATE_DEVICES_DRY_RUN confirms changes
- Ready to update device records

**Usage**:
```bash
UPDATE_DEVICES_APPLY.bat
# Actually apply device updates
```

---

## ✅ VERIFICATION FILES

### 14. `VERIFY_DURATION.bat`
**Purpose**: Verify duration data quality and correctness  
**Status**: For data validation  
**When to use**: 
- After applying duration fixes
- Want to confirm data is correct
- Quality assurance check

**Usage**:
```bash
VERIFY_DURATION.bat
# Check duration_seconds values in database
```

---

### 15. `VERIFY_UPDATE_RESULT.bat`
**Purpose**: Verify results after updates  
**Status**: For data validation  
**When to use**: 
- After UPDATE_DEVICES_APPLY
- Want to confirm updates worked correctly

**Usage**:
```bash
VERIFY_UPDATE_RESULT.bat
# Check updated values in database
```

---

## 🗺️ GPS TRACK FILES

### 16. `TEST_GPS_TRACK_PULL.bat`
**Purpose**: Test GPS track data pull with sample data  
**Status**: For GPS track system testing  
**When to use**: 
- Before running full GPS pull
- Want to test with small dataset (10 devices)
- Quick verification

**Usage**:
```bash
TEST_GPS_TRACK_PULL.bat
# Test GPS pull with limited records
```

**Note**: Uses limit=10 for fast testing (~30 seconds)

---

### 17. `PULL_GPS_JUNE_11.bat`
**Purpose**: Pull GPS track data for June 11, 2026  
**Status**: For manual GPS data pulls  
**When to use**: 
- Need specific GPS data for June 11
- Manual import of GPS tracks

**Usage**:
```bash
PULL_GPS_JUNE_11.bat
# Pull GPS data for June 11
```

---

## 📊 WORKFLOW GUIDE

### Daily Development Workflow

```
1. Start server:
   RUN_DASHBOARD.bat

2. (Optional) Start background jobs:
   Terminal 2: START_SCHEDULER.bat
   Terminal 3: START_QUEUE_WORKER.bat

3. Access: http://localhost:8000/login

4. When done, press Ctrl+C to stop server
```

### When Fixing Data Issues

```
1. Preview fix:
   FIX_DURATION_DRY_RUN.bat

2. If preview looks good, apply:
   FIX_DURATION_APPLY.bat

3. Verify results:
   VERIFY_DURATION.bat

4. Confirm in dashboard
```

### GPS Track Pull Workflow

```
1. Test with sample (10 devices):
   TEST_GPS_TRACK_PULL.bat

2. If successful, pull specific date:
   PULL_GPS_JUNE_11.bat

3. Or access manual page:
   http://localhost:8000/admin/gps-track-pull
```

---

## 🗑️ DELETED FILES (Why)

27 files were deleted because they were:

- **Obsolete**: setup_php.bat, RUN_MIGRATION.bat, PHASE_2_INSTALLER.bat (old setup scripts)
- **Duplicate**: start_queue.bat (use START_QUEUE_WORKER.bat instead), start_server.bat (use RUN_DASHBOARD.bat), run_server.bat
- **Testing only**: CHECK_FIX_PROGRESS.bat, check_extra.bat, check_mei.bat (old debug scripts)
- **Replaced by commands**: regenerate.bat, regenerate_idle_alarms.bat, fix_and_regenerate.bat
- **Deprecated**: START_SCHEDULER_TAHAP12.bat (outdated version), VERIFY_START_DETAIL_FIX.bat (specific issue)
- **Alternative methods**: delete_extra.bat, clear_data.bat, clear_cache.bat, fix_alarm_status.bat, insert_users.bat, process_queue.bat
- **One-time use**: FIX_START_DETAIL_QUICK.bat, FIX_IDLE_ALARMS_DATA.bat, FIX_RADMIN_VPN.bat, RUN_BACKFILL_BACKGROUND.bat, VERIFY_VOLVO_COUNT.bat, start_realtime.bat

---

## ⚡ QUICK REFERENCE

| Task | File | Time |
|------|------|------|
| First setup | RUN_WITH_LARAGON.bat | 2-3 min |
| Quick start | RUN_DASHBOARD.bat | 30 sec |
| Fix duration | FIX_DURATION_DRY_RUN.bat + APPLY | 5 min |
| Fix start detail | FIX_START_DETAIL_DRY_RUN.bat + APPLY | 5 min |
| Backfill data | BACKFILL_START_DETAIL_DRY_RUN.bat + APPLY | 10 min |
| Import devices | IMPORT_DEVICES_TO_DATABASE.bat | 1 min |
| Test GPS pull | TEST_GPS_TRACK_PULL.bat | 30 sec |
| Full GPS pull | PULL_GPS_JUNE_11.bat | 2-3 min |

---

## 📝 NOTES

1. **Always run DRY-RUN first** before APPLY to preview changes
2. **Background jobs** (SCHEDULER + QUEUE_WORKER) optional in development, required for auto-pull
3. **Use separate terminals** for server, scheduler, and queue worker (3 windows total)
4. **Verify after fixes** using VERIFY_*.bat files
5. **Keep database backup** before running APPLY operations

---

**System Status**: ✅ PRODUCTION READY  
**Last Maintenance**: June 26, 2026  
**Total Active BAT Files**: 17

