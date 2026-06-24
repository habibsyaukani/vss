# 🔧 BACKFILL: Start Detail Column

**Date**: June 10, 2026  
**Issue**: start_detail column empty for ~60% of records  
**Root Cause**: Old data imported before mapping was added  
**Solution**: Backfill from raw_json.alarmvalue  

---

## 📋 PROBLEM SUMMARY

### Current Situation:
```
alarm_raw table:
- Total records: ~28,000
- Empty start_detail: ~17,000 (60.72%)
- With start_detail: ~11,000 (39.28%)

idle_alarms table:
- Total records: ~28,000
- Empty start_detail: ~17,000 (60.72%)
- With start_detail: ~11,000 (39.28%)
```

### Why Empty?
1. Data imported BEFORE mapping logic was added
2. ImportAlarmPageJob now CORRECTLY maps `alarmvalue` → `start_detail`
3. But OLD data doesn't have this mapping

### Impact:
- Frontend table shows empty "Start Detail" column ❌
- Missing technical data: avg, cur, dur, max, min, pre, tt, vt, satellites

---

## ✅ SOLUTION

### Two-Step Backfill Process:

**Step 1**: Backfill `alarm_raw.start_detail`
- Extract `alarmvalue` from `raw_json` column
- Write to `start_detail` column
- Processes ~17,000 records

**Step 2**: Backfill `idle_alarms.start_detail`
- Copy `start_detail` from `alarm_raw` (by guid)
- Write to `idle_alarms.start_detail`
- Processes ~17,000 records

---

## 🚀 HOW TO RUN

### Option 1: Batch Files (Recommended)

**Dry Run** (Preview only):
```bash
BACKFILL_START_DETAIL_DRY_RUN.bat
```

**Apply Changes**:
```bash
BACKFILL_START_DETAIL_APPLY.bat
```

### Option 2: Manual Commands

**Dry Run**:
```bash
php artisan backfill:start-detail --dry-run
php artisan backfill:idle-alarms-start-detail --dry-run
```

**Apply**:
```bash
php artisan backfill:start-detail
php artisan backfill:idle-alarms-start-detail
```

---

## 📊 COMMAND OPTIONS

### backfill:start-detail

Backfills `alarm_raw.start_detail` from `raw_json.alarmvalue`

**Options**:
```bash
--limit=1000    Number of records per batch (default: 1000)
--dry-run       Preview changes without applying
```

**Example**:
```bash
# Process 500 records per batch
php artisan backfill:start-detail --limit=500

# Preview without applying
php artisan backfill:start-detail --dry-run
```

### backfill:idle-alarms-start-detail

Backfills `idle_alarms.start_detail` from `alarm_raw.start_detail`

**Options**:
```bash
--limit=1000    Number of records per batch (default: 1000)
--dry-run       Preview changes without applying
```

---

## 🔍 VERIFICATION

### Before Backfill:
```bash
php check_start_detail.php
```

Expected output:
```
📊 Statistics:
   Total idle_alarms: 27979
   Empty start_detail: 16990
   Percentage empty: 60.72%
```

### After Backfill:
```bash
php check_start_detail.php
```

Expected output:
```
📊 Statistics:
   Total idle_alarms: 27979
   Empty start_detail: 0
   Percentage empty: 0%
```

---

## ⚠️ IMPORTANT NOTES

### Safety Features:
- ✅ **Transaction-based**: Rollback on error
- ✅ **Batch processing**: 1000 records per batch (configurable)
- ✅ **Progress bar**: Real-time progress tracking
- ✅ **Dry run mode**: Preview before applying
- ✅ **Only fills empty**: Doesn't overwrite existing data

### Data Mapping:
```
alarm_raw:
  raw_json.alarmvalue → start_detail

idle_alarms:
  alarm_raw.start_detail → start_detail (by guid)
```

### Example Data:
**Before**:
```
start_detail: (NULL/EMPTY)
end_detail: dur:94 ; tt:300 ; cur:11.34 ; pre:11.00
```

**After**:
```
start_detail: avg:0.00 ; cur:0.00 ; dur:0 ; max:0.00 ; min:0.00 ; pre:0.00 ; tt:300 ; vt:2 ; satellites:22
end_detail: dur:94 ; tt:300 ; cur:11.34 ; pre:11.00
```

---

## 🛡️ SAFETY COMPLIANCE

### SYSTEM_RULES.md Compliant:
- ✅ NO schema changes
- ✅ NO data deletion
- ✅ UPDATE only (fills empty columns)
- ✅ Backward compatible
- ✅ Non-breaking change
- ✅ Transaction-based with rollback
- ✅ Safe to deploy

### Risk Level: 🟡 **YELLOW** (Medium Risk)
- Affects ~17,000 records
- Mass data update
- Reversible (can re-run import if needed)
- Safe: only fills NULL/empty values

---

## 📝 TECHNICAL DETAILS

### Why alarmvalue is Empty for Some Records?

The `alarmvalue` field in Howen API contains different data based on alarm type:

**Idle Alarm** (alarm_type = 100):
```json
{
  "alarmvalue": "avg:0.00 ; cur:0.00 ; dur:0 ; max:0.00 ; min:0.00 ; pre:8.00 ; tt:300 ; vt:2 ; satellites:22"
}
```
✅ Full technical data

**Other Alarms** (Eyes Closed, Headway Warning, etc.):
```json
{
  "alarmvalue": "id:'' ; name:'' ; tp:65 ; satellites:22"
}
```
❌ Minimal data only

### Processing Logic:
```php
// Extract from raw_json
$json = json_decode($alarm->raw_json, true);
$startDetail = $json['alarmvalue'] ?? $json['alarmValue'] ?? null;

if ($startDetail) {
    $alarm->start_detail = $startDetail;
    $alarm->save();
}
```

### Batch Processing:
- Processes 1000 records per batch (default)
- Uses Laravel `chunk()` for memory efficiency
- Progress bar shows real-time status
- Transaction per batch (rollback if error)

---

## 📚 FILES CREATED

```
app/Console/Commands/
├── BackfillStartDetailCommand.php                 (NEW)
└── BackfillIdleAlarmsStartDetailCommand.php       (NEW)

root/
├── BACKFILL_START_DETAIL_DRY_RUN.bat              (NEW)
├── BACKFILL_START_DETAIL_APPLY.bat                (NEW)
├── BACKFILL_START_DETAIL.md                       (NEW - this file)
├── check_start_detail.php                         (NEW)
├── check_alarmvalue_field.php                     (NEW)
└── test_import_alarmvalue.php                     (NEW)
```

---

## 🎯 EXPECTED OUTCOME

### Before Backfill:
| Table | Total | Empty start_detail | With Data |
|-------|-------|-------------------|-----------|
| alarm_raw | 28,000 | 17,000 (60.72%) | 11,000 (39.28%) |
| idle_alarms | 28,000 | 17,000 (60.72%) | 11,000 (39.28%) |

### After Backfill:
| Table | Total | Empty start_detail | With Data |
|-------|-------|-------------------|-----------|
| alarm_raw | 28,000 | ~5,000* (17.86%) | ~23,000 (82.14%) |
| idle_alarms | 28,000 | ~5,000* (17.86%) | ~23,000 (82.14%) |

*Some alarms may still be empty if they're non-Idle alarms with minimal alarmvalue data

---

## 💡 NEXT STEPS

After backfill complete:

1. ✅ Verify with `check_start_detail.php`
2. ✅ Check frontend table - "Start Detail" column should have data
3. ✅ Future imports will automatically fill start_detail (ImportAlarmPageJob already correct)
4. ✅ No further backfill needed (one-time operation)

---

## ❓ TROUBLESHOOTING

### If backfill fails:

1. **Check error message** in output
2. **Transaction will rollback** - no partial updates
3. **Can re-run command** - safe to retry
4. **Check database connection** - ensure MySQL is running
5. **Check disk space** - ensure sufficient space for updates

### If start_detail still empty after backfill:

1. Check if record's `raw_json` exists
2. Check if `raw_json.alarmvalue` has data
3. Some alarms (non-Idle) may have minimal alarmvalue
4. Run verification: `php check_alarmvalue_field.php`

---

**Ready to backfill? Run the dry run first!**

```bash
BACKFILL_START_DETAIL_DRY_RUN.bat
```

