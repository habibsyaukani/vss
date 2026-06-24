# 🗺️ GPS PULL COMMAND - USER GUIDE

**Date:** 2026-06-12  
**Command:** `vss:pull-gps-tracks`  
**Status:** ✅ PRODUCTION READY

---

## 🎯 OVERVIEW

Command untuk tarik data GPS Track dari VSS API dengan efisien.

### ⚠️ PERBEDAAN DENGAN IDLE ALARM:

| Aspect | Idle Alarm | GPS Track |
|--------|------------|-----------|
| **API Endpoint** | `/vss/alarm/getApiAlarmList.action` | `/vss/track/getApiTrackList.action` |
| **Pull Method** | ✅ Per Page (all devices per page) | ❌ **Per Device** (wajib deviceID) |
| **Efficiency** | High (1 request = many devices) | Medium (1 request = 1 device) |
| **Why Different?** | Howen Alarm API design | VSS GPS API requires deviceID |

**Kesimpulan:** GPS Track API **WAJIB per-device**, tidak bisa per-page seperti idle alarm.

---

## 🚀 USAGE

### Basic Usage (Yesterday):
```bash
php artisan vss:pull-gps-tracks
```

### Specific Date:
```bash
php artisan vss:pull-gps-tracks --date=2026-06-11
```

### Limit Devices (Testing):
```bash
php artisan vss:pull-gps-tracks --date=2026-06-11 --limit=10
```

### Specific Devices:
```bash
php artisan vss:pull-gps-tracks --date=2026-06-11 --devices=75482223,73189119,75648245
```

---

## 📋 PARAMETERS

| Parameter | Default | Description |
|-----------|---------|-------------|
| `--date` | yesterday | Target date (Y-m-d format) |
| `--devices` | all | Comma-separated device IDs, or "all" |
| `--limit` | 0 | Limit number of devices (0 = no limit) |

---

## 📊 OUTPUT EXAMPLE

```
🗺️  Pull GPS Tracks - Efficient Method
   Date: 2026-06-11
   Range: 2026-06-11 00:00:00 → 2026-06-11 23:59:59

🔐 Getting VSS authentication token...
✅ Token obtained

🚗 Loading devices...
✅ Found 335 devices

📡 Fetching GPS data per device...
 335/335 [============================] 100% | GPE-WT-869

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📊 SUMMARY
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ Devices processed: 335
✅ Devices with data: 2
✅ Total records fetched: 2572
✅ Total records saved: 2572

🔄 Dispatching ProcessGpsTrackJob...
✅ Job dispatched

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ COMPLETED SUCCESSFULLY
```

---

## ⏱️ ESTIMASI WAKTU

**Formula:** `devices × 0.5s = total time`

| Devices | Time |
|---------|------|
| 10 | ~5 seconds |
| 50 | ~25 seconds |
| 100 | ~50 seconds |
| 335 | ~3 minutes |

**Note:** Devices without data return very fast (0.1s), devices with data take longer (~1-2s per page).

---

## ✅ AFTER RUNNING

### Step 1: Process Raw Data
```bash
php artisan queue:work --once
```

### Step 2: Verify Data
```sql
-- Check raw data
SELECT COUNT(*) FROM gps_tracks_raw WHERE DATE(gps_time) = '2026-06-11';

-- Check processed data
SELECT COUNT(*) FROM gps_tracks WHERE DATE(gps_time) = '2026-06-11';

-- Top devices
SELECT device_name, COUNT(*) as records
FROM gps_tracks 
WHERE DATE(gps_time) = '2026-06-11'
GROUP BY device_name
ORDER BY records DESC
LIMIT 20;
```

---

## 🔍 COMMON SCENARIOS

### Scenario 1: Many Devices Return 0 Records

**Why?**
- Devices were offline on that date
- GPS hardware not transmitting
- Vehicles not in operation

**Solution:**
- Normal behavior, not an error
- Try different dates
- Check device activity: `php check_device_activity.php`

---

### Scenario 2: Pull Multiple Dates

**Option A: Loop Dates**
```bash
for date in 2026-06-09 2026-06-10 2026-06-11
do
    php artisan vss:pull-gps-tracks --date=$date
done
```

**Option B: Date Range (Future Enhancement)**
```bash
# Not implemented yet, but can be added
php artisan vss:pull-gps-tracks --from=2026-06-09 --to=2026-06-11
```

---

### Scenario 3: Pull Only Specific Fleet

**Example: Only VOLVO series**
```sql
-- Get VOLVO device IDs
SELECT GROUP_CONCAT(device_id) FROM devices WHERE series = 'VOLVO';
-- Output: 75482223,73189119,75648245

-- Pull only those devices
php artisan vss:pull-gps-tracks --devices=75482223,73189119,75648245
```

---

## 🔧 TROUBLESHOOTING

### Issue: "Invalid parameter"

**Cause:** VSS API requires valid deviceID

**Solution:**
- Ensure devices table has valid device_id
- Check: `SELECT COUNT(*) FROM devices WHERE status='active' AND device_id IS NOT NULL;`

---

### Issue: Token expired

**Solution:**
```bash
php artisan cache:clear
php artisan vss:pull-gps-tracks --date=2026-06-11
```

---

### Issue: Too slow

**Solution:**
- Use `--limit` for testing: `--limit=10`
- Or filter specific devices: `--devices=123,456,789`
- Delay is intentional (300ms) to not overwhelm API

---

## 📝 COMPARISON WITH OLD SCRIPT

| Aspect | Old (pull_gps_yesterday.php) | New (vss:pull-gps-tracks) |
|--------|------------------------------|---------------------------|
| **Progress Display** | Manual echo | ✅ Progress bar |
| **Error Handling** | Basic try-catch | ✅ Per-device error tracking |
| **Summary** | Basic | ✅ Detailed with stats |
| **Flexibility** | Single date | ✅ Any date, any devices |
| **Laravel Integration** | Standalone script | ✅ Artisan command |
| **Logging** | Manual | ✅ Laravel logs |

**Recommendation:** Use new command for all future pulls.

---

## 🎯 BEST PRACTICES

### 1. Test First
```bash
# Test with 10 devices
php artisan vss:pull-gps-tracks --date=2026-06-11 --limit=10

# If OK, pull all
php artisan vss:pull-gps-tracks --date=2026-06-11
```

### 2. Check Activity First
```bash
# See which dates have most activity
php check_device_activity.php

# Pull those dates
php artisan vss:pull-gps-tracks --date=<best-date>
```

### 3. Process Immediately
```bash
# Pull + process in one go
php artisan vss:pull-gps-tracks --date=2026-06-11 && php artisan queue:work --once
```

### 4. Schedule for Daily
```php
// In app/Console/Kernel.php (if needed)
$schedule->command('vss:pull-gps-tracks')->daily();
```

---

## ⚠️ IMPORTANT NOTES

1. **VSS GPS API Limitation:**
   - Cannot pull all devices per page like Howen Alarm API
   - MUST loop through devices one by one
   - This is API design, not our code limitation

2. **Performance:**
   - 335 devices × 0.5s = ~3 minutes (acceptable)
   - Delay (300ms) is intentional to be API-friendly
   - Can be reduced if needed (edit command code)

3. **Data Availability:**
   - Not all devices have data for all dates
   - Normal to see "Devices with data: 2 / 335"
   - Depends on device operational status

---

## 🚀 FUTURE ENHANCEMENTS

Possible improvements:
- [ ] Date range support (--from / --to)
- [ ] Parallel processing (5 devices at once)
- [ ] Resume from last failed device
- [ ] Export summary to file
- [ ] Email notification on completion

---

**Created:** 2026-06-12  
**Status:** ✅ PRODUCTION READY  
**Recommended:** Use this command instead of manual scripts

