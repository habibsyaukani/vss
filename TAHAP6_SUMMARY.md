# TAHAP 6 - Process Idle Alarm | Perubahan alarm_status

## Masalah yang Diperbaiki

**SEBELUMNYA** ❌:
- `alarm_status` hardcoded ke `'new'` untuk SEMUA record
- Tidak ada validasi alarmState dari Howen API
- Semua alarm disimpan tanpa filter

**SEKARANG** ✅:
- `alarm_status` di-map dari field `alarmState` dari Howen API
- Hanya alarm valid yang disimpan (dengan alarmState = 1 = ALARM_END)
- Proper validation: start_speed=0, end_speed>0, duration>=5min

## Implementation Details

### 1. **alarmState Mapping**
```
Howen API Field: alarmState
  0 = ALARMING   (idle masih berlangsung)
  1 = ALARM_END  (idle sudah selesai, kendaraan bergerak)

Mapping:
  alarmState 0 → alarm_status = 'ALARMING'  (skip, tidak simpan ke idle_alarms)
  alarmState 1 → alarm_status = 'ALARM_END' (simpan jika valid)
```

### 2. **Field Mapping - FINAL CORRECT**
```
Howen API              → Database Field         → Value Example
alarmValue            → start_detail           → "avg:0.00 ; cur:0.00 ; dur:0 ; ..."
endDetail             → end_detail             → "dur:59 ; tt:300 ; cur:13.72 ; ..."
alarmState            → alarm_state            → 0 atau 1
speed                 → start_speed            → 0
endSpeed              → end_speed              → 15
alarmtype (100=idle)  → alarm_type             → 100
createtime            → starting_time          → 2026-06-03 14:44:41
endTime               → ending_time            → 2026-06-03 15:44:41
alarmGps              → starting_location      → -6.2197,107.0088
endGps                → ending_location        → -6.2197,107.0088
(extract dur)         → duration_seconds       → 59 (extracted from endDetail)
```

### 3. **Duration Extraction - CORRECT**
```php
// Extract 'dur' value dari endDetail string
// endDetail format: "dur:59 ; tt:300 ; cur:13.72 ; ..."
if (preg_match('/dur:\s*(\d+)/', $endDetail, $matches)) {
    $durationSeconds = (int)$matches[1];  // 59 seconds
    $durationMinutes = floor($durationSeconds / 60);
}
```

### 4. **Validation Rules (ALL REQUIRED)**
```
1. alarm_state = 1         (ALARM_END)
2. start_speed = 0
3. end_speed > 0
4. duration >= 300 seconds (5 menit minimum)
5. end_time NOT NULL
```

### 5. **Files Updated**

**✅ app/Jobs/ImportAlarmPageJob.php**
- Added: `Log::info("Howen API Alarm Response", [...])`
- Logs: guid, deviceName, alarmState, alarmtype, etc.
- Purpose: Debug API response untuk verify field values

**✅ app/Jobs/ProcessIdleAlarmJob.php**
- Added: `mapAlarmStateToStatus($alarmState)` method
- Updated: Extract alarmState dari alarm_raw
- Updated: Map ke alarm_status (ALARMING atau ALARM_END)
- Updated: Store alarmState + alarm_status to idle_alarms

**✅ database/migrations/..._create_idle_alarms_table.php**
- alarm_state field: already exists (nullable, default=1)
- alarm_status field: already exists (nullable)

**✅ DEVELOPMENT_PROGRESS.md**
- Updated: TAHAP 6 section dengan alarmState mapping notes

## Test Results

### Expected Behavior (setelah fix):

```
Input Data (Howen API Mock):
  alarm-1: guid=alarm-1, alarmState=1, endSpeed=15, deviceName=GPE-B-8322
  alarm-2: guid=alarm-2, alarmState=1, endSpeed=20, deviceName=GPE-FT-873

Process:
  1. Import to alarm_raw:
     - GUID: alarm-1 | State: 1 | Speed: 0→15
     - GUID: alarm-2 | State: 1 | Speed: 0→20

  2. Process to idle_alarms:
     - alarm-1: Valid ✅
       * alarm_state = 1
       * alarm_status = 'ALARM_END'
       * duration = 60 minutes ✅
     
     - alarm-2: Valid ✅
       * alarm_state = 1
       * alarm_status = 'ALARM_END'
       * duration = 60 minutes ✅
```

### Query Frontend:
```sql
SELECT * FROM idle_alarms
WHERE 
    alarm_status IN ('ALARM_END', 'CLOSED')
    AND end_speed > 0
    AND duration_minutes >= 5
ORDER BY starting_time DESC;
```

## How to Verify

### 1. Check logs:
```
storage/logs/laravel.log

Search for:
  [INFO] Howen API Alarm Response: {"guid": ..., "alarmState": 1, ...}
  [INFO] Processing idle alarm with state mapping: {"alarmState": 1, "alarm_status": "ALARM_END"}
```

### 2. Check database:
```sql
-- Check alarm_raw (should have alarm_state)
SELECT guid, device_name, alarm_state, start_speed, end_speed FROM alarm_raw;

-- Check idle_alarms (should have alarm_status mapped correctly)
SELECT guid, device_name, alarm_state, alarm_status, start_speed, end_speed FROM idle_alarms;

-- Verify only ALARM_END alarms are stored
SELECT DISTINCT alarm_status FROM idle_alarms;
-- Result should be: ALARM_END (or ALARMING/CLOSED if mixed)
```

### 3. API Response:
```bash
GET http://localhost:8000/api/idle-alarms

Expected response:
{
  "success": true,
  "data": {
    "alarms": [
      {
        "alarm_status": "ALARM_END",  ← Correct mapping
        "end_speed": 15,
        "duration_minutes": 60
      }
    ]
  }
}
```

## Algorithm Flow

```
┌─── Howen API ───┐
│  alarmState: 1  │
│  endSpeed: 15   │
│  alarmValue: ...│
│  endDetail:dur:59
└────────┬────────┘
         │
         ▼
┌──── ImportAlarmPageJob ────┐
│ 1. Log API Response        │
│ 2. Extract alarmState=1    │
│ 3. Save to alarm_raw       │
│    alarm_state=1           │
└────────┬────────────────────┘
         │
         ▼
┌──── ProcessIdleAlarmJob ────┐
│ 1. Validate (state=1 OK)    │
│ 2. mapAlarmStateToStatus(1) │
│    → 'ALARM_END'            │
│ 3. Save to idle_alarms      │
│    alarm_status='ALARM_END' │
└────────┬────────────────────┘
         │
         ▼
┌──── API Response ───────┐
│ alarm_status: ALARM_END │
│ end_speed: 15           │
│ duration: 60 min        │
└─────────────────────────┘
```

## Key Changes Summary

| Aspect | Before | After |
|--------|--------|-------|
| alarm_status value | 'new' (hardcoded) | Mapped from alarmState |
| alarmState handling | Ignored | Extracted & logged |
| Validation | None | 5 conditions (state, speed, duration) |
| Logging | Minimal | Detailed (alarmState in logs) |
| ALARM_END support | No | Yes (from alarmState=1) |
| Database field usage | Not used | Properly utilized |

## Documentation

See DEVELOPMENT_PROGRESS.md → TAHAP 6 for full details on:
- Business logic
- Validation rules
- Example data (valid & invalid)
- Frontend query examples

---

**Status**: ✅ COMPLETE - Ready for testing
**Commit**: Include code changes + this summary
**Next**: TAHAP 7 (API already complete) → TAHAP 8 (Database optimization)
