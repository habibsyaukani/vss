# BUGFIX 10 - Start Detail & Duration Fix

**Date**: June 10, 2026  
**Status**: ✅ FIXED  
**Risk Level**: 🟡 Yellow (requires backfill, safe with dry-run)

---

## 🎯 Problem

`start_detail` dan `duration` menampilkan `dur:0` padahal alarm sudah selesai.

**Expected**: `dur:1200` (20 menit) seperti di Howen web  
**Actual**: `dur:0` (0 menit)

---

## 🔍 Root Cause

Code mengambil `alarmvalue` dari **START record** (`alarmState:1`) yang memiliki `dur:0`.

Seharusnya mengambil dari **END record** (`alarmState:0`) yang memiliki `dur:1200`.

**Penjelasan API Howen**:
```
Setiap alarm dikirim 2x oleh API:

Record 1 (START):
  - alarmState: 1
  - alarmvalue: "dur:0;tt:300"     ← DURASI BELUM ADA

Record 2 (END):
  - alarmState: 0
  - alarmvalue: "dur:1200;tt:300"  ← DURASI SUDAH TERISI ✅
```

---

## ✅ Solution Implemented

### 1. Fix Pull Commands

Modified `mapAlarmData()` function di 3 files:
- `PullIdleAlarmsRealtimeCommand.php`
- `PullIdleAlarmsPerDayCommand.php`
- `PullIdleAlarmsDateRangeCommand.php`

**Logic baru**:
```php
if ($alarmState == 0) {
    // End Record - use this for start_detail
    $startDetail = $alarmValue;  // dur:1200 ✅
} else {
    // Start Record - skip
    $startDetail = null;
}
```

### 2. Backfill Command

Created: `FixStartDetailDurationCommand.php`

**Features**:
- ✅ Dry run mode (preview without changes)
- ✅ Batch processing (1000-5000 records)
- ✅ Progress bar
- ✅ Transaction safety
- ✅ Fixes both `alarm_raw` and `idle_alarms` tables

---

## 🚀 How to Use

### Step 1: Check What Will Be Fixed (Dry Run)

```bash
FIX_START_DETAIL_DRY_RUN.bat
```

Output akan menunjukkan:
- Berapa record yang punya `dur:0`
- Berapa yang bisa di-fix
- Berapa yang akan di-skip

### Step 2: Apply the Fix

Jika dry run OK, jalankan:

```bash
FIX_START_DETAIL_APPLY.bat
```

**⚠️ WARNING**: Ini akan MODIFY database!

### Step 3: Verify

Jalankan dry run lagi untuk cek:

```bash
FIX_START_DETAIL_DRY_RUN.bat
```

Jika masih ada yang perlu di-fix, ulangi Step 2.

---

## 📊 Impact

### Future Data (NEW Pulls)
- ✅ Automatically fixed
- ✅ No action needed
- ✅ All new data akan benar

### Existing Data (OLD Records)
- ⚠️ Needs backfill
- ⚠️ Run fix command
- ✅ Safe with dry-run first

---

## 📝 Technical Details

**Query Logic**:
```sql
-- Find problematic records
SELECT * FROM alarm_raw 
WHERE alarm_state = 1 
  AND start_detail LIKE '%dur:0%';

-- Find matching end record
SELECT * FROM alarm_raw 
WHERE guid = ? AND alarm_state = 0;

-- Update with correct data
UPDATE alarm_raw 
SET start_detail = [end_record.alarm_value]
WHERE guid = ?;
```

**Files Changed**:
- ✅ 3 Pull command files (mapAlarmData function)
- ✅ 1 New fix command (FixStartDetailDurationCommand.php)
- ✅ 2 Batch files (.bat)
- ✅ 2 Documentation files (.md)

---

## ✅ Verification

After fix, check:

```sql
-- Should be 0
SELECT COUNT(*) FROM alarm_raw 
WHERE alarm_state = 1 AND start_detail LIKE '%dur:0%';

-- Should be 0
SELECT COUNT(*) FROM idle_alarms 
WHERE start_detail LIKE '%dur:0%';
```

---

## 📞 Quick Reference

**Dry Run**: `FIX_START_DETAIL_DRY_RUN.bat`  
**Apply**: `FIX_START_DETAIL_APPLY.bat`  
**Docs**: `FIX_START_DETAIL_DURATION.md`

**Command Options**:
```bash
# Dry run dengan limit
php artisan howen:fix-start-detail-duration --dry-run --limit=1000

# Apply dengan limit
php artisan howen:fix-start-detail-duration --limit=5000
```

---

**Last Updated**: June 10, 2026  
**Status**: ✅ Fixed and Ready to Backfill  
**Backward Compatible**: ✅ Yes
