# Quick Start - Duration Fix

## Problem
Duration showing `dur:0` instead of actual values like `dur:1200` (20 minutes).

## Solution Summary
Fixed duration extraction to use correct priority order:
```
alarmvalue (start_detail) → endDetail → alarmTimeLength
```

## Quick Commands

### 1. Check Current Status
```bash
php verify_duration_fix.php
```
Shows:
- How many records have incorrect duration
- Sample records with actual vs expected values
- Overall statistics

### 2. Preview Fix (Dry Run)
```bash
php artisan howen:fix-start-detail-duration --dry-run --limit=100
```
Shows what will be changed without modifying data.

### 3. Apply Fix
```bash
php artisan howen:fix-start-detail-duration --limit=1000
```
Fixes up to 1000 records per run.

### 4. Verify Fix Applied
```bash
php verify_duration_fix.php
```
Confirm that duration values are now correct.

### 5. Test New Data
```bash
php artisan howen:pull-alarms-realtime --wait
php verify_duration_fix.php
```
Pull new data and verify it has correct duration values.

## What Was Fixed

### Files Modified
1. ✅ `PullIdleAlarmsDateRangeCommand.php` - DateRange pull command
2. ✅ `ProcessIdleAlarmJob.php` - Job that processes alarms
3. ✅ `FixStartDetailDurationCommand.php` - Backfill command (rewritten)

Note: `PullIdleAlarmsRealtimeCommand.php` and `PullIdleAlarmsPerDayCommand.php` were already fixed in previous session.

### Before Fix
```php
// Wrong priority
$duration = $alarmTimeLength;  // Always used hardcoded value
```

### After Fix
```php
// Correct priority: alarmvalue > endDetail > alarmTimeLength
$durationFromStart = 0;
if (preg_match('/dur:(\d+)/', $alarmValue, $m)) {
    $durationFromStart = (int)$m[1];
}

$durationFromEnd = 0;
if (preg_match('/dur:(\d+)/', $endDetail, $m)) {
    $durationFromEnd = (int)$m[1];
}

$duration = $durationFromStart > 0 ? $durationFromStart : 
           ($durationFromEnd > 0 ? $durationFromEnd : $alarmTimeLength);
```

## Expected Results

### Before
```
GUID: abc123
  start_detail: alarmtype:32;...;dur:1200;...
  duration_seconds: 0  ❌ WRONG
```

### After
```
GUID: abc123
  start_detail: alarmtype:32;...;dur:1200;...
  duration_seconds: 1200  ✅ CORRECT
```

## Verification Query

```sql
SELECT 
    guid, 
    LEFT(start_detail, 40) as start_detail, 
    duration_seconds, 
    report_time 
FROM alarm_raw 
WHERE alarm_type = 32 
ORDER BY report_time DESC 
LIMIT 10;
```

**Expected**: `duration_seconds` should match `dur:XXXX` value in `start_detail`.

## Safety
- ✅ No schema changes
- ✅ No data deletion
- ✅ Only updates duration fields
- ✅ Dry-run mode available
- ✅ Batch processing with --limit
- ✅ Can be re-run safely

## Need Help?
See detailed documentation:
- `DURATION_FIX_SUMMARY.md` - Complete implementation guide
- `DEVELOPMENT_PROGRESS.md` - Full project history

---
**Status**: ✅ Ready to use
**Date**: June 11, 2026
