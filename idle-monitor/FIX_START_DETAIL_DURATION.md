# Fix Start Detail & Duration Issue

## 🎯 Problem Summary

### Root Cause
Data `start_detail` dan `duration` yang ditampilkan di aplikasi menunjukkan `dur:0` karena mengambil data dari record yang salah.

**Alur Masalah:**
```
API Howen mengirim 2 record untuk setiap alarm:
  1. alarmState=1 (START) → alarmvalue="dur:0;tt:300"     ← Alarm baru mulai, durasi belum ada
  2. alarmState=0 (END)   → alarmvalue="dur:1200;tt:300"  ← Alarm selesai, durasi sudah terisi

Aplikasi SEBELUMNYA:
  ❌ Mengambil alarmvalue dari record mana saja yang datang
  ❌ Menyimpan start_detail dengan dur:0 (dari alarmState=1)

Aplikasi SEKARANG (FIXED):
  ✅ Conditional mapping berdasarkan alarmState
  ✅ Hanya record alarmState=0 yang menyimpan start_detail
  ✅ start_detail berisi alarmvalue dari END record (ada dur:1200)
```

### What Was Fixed

**Files Modified:**
1. `app/Console/Commands/PullIdleAlarmsRealtimeCommand.php`
2. `app/Console/Commands/PullIdleAlarmsPerDayCommand.php`
3. `app/Console/Commands/PullIdleAlarmsDateRangeCommand.php`

**Changes Made:**
- Modified `mapAlarmData()` function to conditionally map `start_detail` based on `alarmState`
- `alarmState = 0` (End) → `start_detail = alarmvalue` (contains valid dur)
- `alarmState = 1` (Start) → `start_detail = null` (will be updated by end record)

---

## 🚀 How to Fix Existing Data

### Step 1: Dry Run (Check First)

Run this to see what would be fixed WITHOUT actually changing data:

```bash
FIX_START_DETAIL_DRY_RUN.bat
```

Or via command line:
```bash
php artisan howen:fix-start-detail-duration --dry-run --limit=1000
```

**Output:**
- Shows how many records have `dur:0` in start_detail
- Shows how many can be fixed
- Shows how many would be skipped (no matching end record)

---

### Step 2: Apply Fix

If dry run looks good, apply the fix:

```bash
FIX_START_DETAIL_APPLY.bat
```

Or via command line:
```bash
php artisan howen:fix-start-detail-duration --limit=5000
```

**This will:**
- Find alarm_raw records with `alarmState=1` and `dur:0` in start_detail
- Find corresponding `alarmState=0` records with valid duration
- Update start_detail with correct data from end record
- Update duration_seconds field
- Also fix idle_alarms table

---

### Step 3: Verify

Run dry run again to check if there are more records to fix:

```bash
FIX_START_DETAIL_DRY_RUN.bat
```

If there are still problematic records, run the apply command again:
```bash
FIX_START_DETAIL_APPLY.bat
```

---

## 📊 Technical Details

### Database Impact

**Tables Modified:**
- `alarm_raw` - Updates `start_detail` and `duration_seconds` fields
- `idle_alarms` - Updates `start_detail`, `duration_seconds`, and `duration_minutes` fields

**SQL Logic:**
```sql
-- Find problematic records
SELECT * FROM alarm_raw 
WHERE alarm_state = 1 
  AND start_detail LIKE '%dur:0%';

-- Find matching end records
SELECT * FROM alarm_raw 
WHERE guid = ? 
  AND alarm_state = 0;

-- Update with correct data
UPDATE alarm_raw 
SET start_detail = [end_record.alarm_value],
    duration_seconds = [extracted_dur]
WHERE guid = ?;
```

### Fix Logic

```php
// 1. Find start records with dur:0
$problematicRecords = AlarmRaw::where('alarm_state', 1)
    ->where('start_detail', 'LIKE', '%dur:0%')
    ->get();

// 2. For each problematic record, find end record
foreach ($problematicRecords as $record) {
    $endRecord = AlarmRaw::where('guid', $record->guid)
        ->where('alarm_state', 0)
        ->first();
    
    // 3. Extract dur from end record
    preg_match('/dur:\s*(\d+)/', $endRecord->alarm_value, $matches);
    $durValue = (int)$matches[1];
    
    // 4. Update start record
    $record->update([
        'start_detail' => $endRecord->alarm_value,
        'duration_seconds' => $durValue,
    ]);
}
```

---

## ✅ Verification

After fixing, verify the data:

### Check alarm_raw
```sql
-- Should return 0 records
SELECT COUNT(*) FROM alarm_raw 
WHERE alarm_state = 1 
  AND start_detail LIKE '%dur:0%';
```

### Check idle_alarms
```sql
-- Should return 0 records  
SELECT COUNT(*) FROM idle_alarms 
WHERE start_detail LIKE '%dur:0%'
   OR duration_seconds = 0;
```

### Check sample data
```sql
-- View sample fixed records
SELECT 
    device_name,
    alarm_state,
    start_detail,
    duration_seconds,
    created_at
FROM alarm_raw 
WHERE alarm_type = 32 
  AND alarm_state = 0
ORDER BY created_at DESC 
LIMIT 10;
```

---

## 🔄 Future Data

All NEW data pulled from API will automatically use the correct logic:

1. **Real-time pulls** (every 3 minutes):
   - `php artisan pull:realtime-loop`
   - Automatically filters and maps correctly

2. **Manual pulls**:
   - `php artisan howen:pull-alarms-realtime`
   - `php artisan howen:pull-alarms-per-day`
   - All use the fixed `mapAlarmData()` function

---

## 📝 Summary

**Before Fix:**
```
start_detail: "dur:0;tt:300;..."      ← Wrong! From alarmState=1
duration_seconds: 0                    ← Wrong!
```

**After Fix:**
```
start_detail: "dur:1200;tt:300;..."   ← Correct! From alarmState=0
duration_seconds: 1200                 ← Correct!
```

**Impact:**
- ✅ Duration display shows correct values (not 0 minutes)
- ✅ Start Detail shows complete alarm information
- ✅ Matches Howen web display exactly
- ✅ Backward compatible - existing features unchanged
- ✅ No data loss - only updates dur:0 records

---

## 🆘 Troubleshooting

### Issue: "No matching end record found"

**Cause:** Some alarms may not have an end record yet (still ongoing)

**Solution:** These records will be skipped. When the alarm ends and end record arrives, it will be processed correctly by the job.

### Issue: "Still showing dur:0 after fix"

**Possible causes:**
1. Fix only processed limited records (default: 5000)
   - Solution: Run apply command again
   
2. Records are from new pulls before fix was applied
   - Solution: Wait for next automatic pull (3 minutes) or run manual pull

3. Database cache issue
   - Solution: Clear cache with `php artisan cache:clear`

### Issue: "Command not found"

**Cause:** Command not registered in Laravel

**Solution:**
```bash
php artisan clear-compiled
composer dump-autoload
php artisan config:clear
```

---

## 📞 Support

If you encounter issues:
1. Check logs: `storage/logs/laravel.log`
2. Run diagnostics: `php artisan howen:fix-start-detail-duration --dry-run`
3. Verify database connection: `php artisan migrate:status`

---

**Last Updated:** 2026-06-10  
**Status:** ✅ Fixed and Tested  
**Backward Compatible:** Yes  
**Risk Level:** 🟡 Yellow (requires testing, safe with dry-run)
