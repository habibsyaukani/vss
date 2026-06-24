# 🔧 FIX: Start Detail Mapping dari Howen API

**Date**: June 10, 2026  
**Status**: ✅ FIXED  
**Priority**: HIGH  

---

## 🎯 MASALAH

Kolom **Start Detail** kosong karena kode pull data mencari field yang **SALAH** dari API Howen.

### Root Cause:

**API Howen mengirim**:
```json
{
  "alarmvalue": "avg:0.00;cur:0.00;dur:1200;max:0.00;...",  // ← lowercase "value"
  "endDetail": "dur:5498;tt:300;cur:9.80;..."
}
```

**Kode lama mencari**:
```php
'start_detail' => $alarm['alarmValue'] ?? ...  // ❌ SALAH! Cari huruf besar V
```

**Harusnya**:
```php
'start_detail' => $alarm['alarmvalue'] ?? $alarm['alarmValue'] ?? ...  // ✅ BENAR! Cari lowercase dulu
```

---

## ✅ SOLUSI - FILES FIXED

### 1. ImportAlarmPageJob.php ✅
**File**: `app/Jobs/ImportAlarmPageJob.php`

**Line 105** - Sudah benar (tidak perlu diubah):
```php
'start_detail' => $alarm['alarmvalue'] ?? $alarm['alarmValue'] ?? $alarm['start_detail'] ?? null,
```

**Line 77-86** - Added logging untuk `alarmvalue`:
```php
\Illuminate\Support\Facades\Log::info("Howen API Alarm Response", [
    'alarmvalue' => $alarm['alarmvalue'] ?? null,  // ✅ Added
    'alarmValue' => $alarm['alarmValue'] ?? null,
    'endDetail' => $alarm['endDetail'] ?? null,
]);
```

---

### 2. PullIdleAlarmsDateRangeCommand.php ✅
**File**: `app/Console/Commands/PullIdleAlarmsDateRangeCommand.php`

**Line 204** - FIXED:
```php
// ❌ Before:
'start_detail' => $alarm['alarmValue'] ?? $alarm['start_detail'] ?? null,

// ✅ After:
'start_detail' => $alarm['alarmvalue'] ?? $alarm['alarmValue'] ?? $alarm['start_detail'] ?? null,
```

---

### 3. PullIdleAlarmsRealtimeCommand.php ✅
**File**: `app/Console/Commands/PullIdleAlarmsRealtimeCommand.php`

**Line 123 (mapAlarmData method)** - FIXED:
```php
// ❌ Before:
'start_detail' => $alarm['alarmValue'] ?? $alarm['start_detail'] ?? null,

// ✅ After:
'start_detail' => $alarm['alarmvalue'] ?? $alarm['alarmValue'] ?? $alarm['start_detail'] ?? null,
```

---

### 4. PullIdleAlarmsPerDayCommand.php ✅
**File**: `app/Console/Commands/PullIdleAlarmsPerDayCommand.php`

**Line 255 (mapAlarmData method)** - FIXED:
```php
// ❌ Before:
'start_detail' => $alarm['alarmValue'] ?? $alarm['start_detail'] ?? null,

// ✅ After:
'start_detail' => $alarm['alarmvalue'] ?? $alarm['alarmValue'] ?? $alarm['start_detail'] ?? null,
```

---

## 📊 IMPACT

### Before Fix:
```
API Response: { "alarmvalue": "avg:0.00;cur:0.00;..." }
Kode mencari: $alarm['alarmValue']  ← NOT FOUND
Result: start_detail = NULL ❌
```

### After Fix:
```
API Response: { "alarmvalue": "avg:0.00;cur:0.00;..." }
Kode mencari: $alarm['alarmvalue']  ← FOUND! ✅
Result: start_detail = "avg:0.00;cur:0.00;..." ✅
```

---

## 🚀 YANG PERLU DILAKUKAN

### Step 1: Pull Data Baru ✅

Sekarang jalankan pull data untuk memastikan data BARU punya start_detail:

```bash
# Option A: Pull real-time (last 24 hours)
php artisan howen:pull-alarms-realtime

# Option B: Pull date range (specific dates)
php artisan howen:pull-alarms-date-range --from=2026-06-10 --to=2026-06-10
```

**Result**: Data baru akan punya start_detail terisi ✅

---

### Step 2: Backfill Data Lama (Optional)

Untuk mengisi data lama yang sudah kosong:

```bash
# Backfill alarm_raw
php artisan backfill:start-detail

# Backfill idle_alarms
php artisan backfill:idle-alarms-start-detail
```

**Note**: Backfill membaca dari `raw_json.alarmvalue`, jadi kalau data lama punya raw_json lengkap, bisa di-backfill.

---

## 🔍 VERIFIKASI

### Test Pull Data Baru:

```bash
# Pull today's data
php artisan howen:pull-alarms-realtime

# Check if start_detail is filled
php check_start_detail.php
```

Expected:
```
📋 Sample data from idle_alarms:
Device Name | Starting Time | Start Detail                            | End Detail
GPE-DT-999  | 2026-06-10... | avg:0.00;cur:0.00;dur:0;max:0.00;...   | dur:94;tt:300;...
```

---

## 📝 TECHNICAL DETAILS

### Field Mapping dari Howen API:

| API Field | Database Column | Keterangan |
|-----------|----------------|------------|
| `alarmvalue` | `start_detail` | START conditions (huruf kecil) |
| `endDetail` | `end_detail` | END conditions |
| `alarmState` | `alarm_state` | 0=end, 1=start, 2=ongoing |
| `alarmTimeLength` | `duration_seconds` | Total duration |

### Priority Order:

```php
'start_detail' => 
    $alarm['alarmvalue'] ??        // ✅ Try lowercase first (API standard)
    $alarm['alarmValue'] ??        // ✅ Fallback camelCase
    $alarm['start_detail'] ??      // ✅ Fallback direct field
    null
```

---

## 🛡️ SAFETY COMPLIANCE

**SYSTEM_RULES.md Compliant**: ✅
- ✅ No database changes
- ✅ No schema changes
- ✅ Only fixed import mapping logic
- ✅ Backward compatible
- ✅ Non-breaking change
- ✅ Future imports will work correctly

**Risk Level**: 🟢 **GREEN** (Low Risk)

---

## 🎉 RESULT

**Data baru (setelah fix) akan otomatis punya start_detail terisi!**

### Before:
```
start_detail: NULL ❌
```

### After:
```
start_detail: avg:0.00;cur:0.00;dur:0;max:0.00;min:0.00;pre:8.00;tt:300;vt:2;satellites:22 ✅
```

---

## 📚 FILES MODIFIED

```
app/Jobs/
└── ImportAlarmPageJob.php                         (logging added)

app/Console/Commands/
├── PullIdleAlarmsDateRangeCommand.php             (FIXED)
├── PullIdleAlarmsRealtimeCommand.php              (FIXED)
└── PullIdleAlarmsPerDayCommand.php                (FIXED)

Documentation/
└── FIX_START_DETAIL_MAPPING.md                    (NEW - this file)
```

---

**Fix Complete!** Sekarang semua pull data command akan mengambil `start_detail` dengan benar dari API Howen.

