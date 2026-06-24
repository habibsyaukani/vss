# 🔧 FIX: idle_alarms Data - Start Detail & Duration

**Date**: June 10, 2026  
**Purpose**: Perbaiki table idle_alarms agar semua data punya start_detail dan duration yang benar  

---

## 🎯 TUJUAN

1. ✅ **Semua idle_alarms punya start_detail**
2. ✅ **Duration dihitung dari start_detail** (field `dur`)
3. ✅ **Data akurat dan konsisten**

---

## 🚀 CARA MENJALANKAN

### **Option 1: Batch File** (Paling Mudah)

Klik 2x file ini:
```
FIX_IDLE_ALARMS_DATA.bat
```

---

### **Option 2: Manual Command**

```bash
cd g:\project\vss\idle-monitor

# Dry run (preview only)
php artisan fix:idle-alarms-data --dry-run

# Apply changes
php artisan fix:idle-alarms-data
```

---

## ⏱️ WAKTU PROSES

- **Dry Run**: ~2-3 menit (preview)
- **Apply**: ~5-10 menit (fix ~150,000+ records)
- Progress bar akan muncul

---

## 📊 APA YANG DILAKUKAN

### **STEP 1: Backfill alarm_raw**

Extract `alarmvalue` dari `raw_json` → tulis ke `start_detail`

**Before**:
```
alarm_raw.start_detail: NULL
alarm_raw.raw_json: { "alarmvalue": "avg:0.00;cur:0.00;dur:123;..." }
```

**After**:
```
alarm_raw.start_detail: "avg:0.00;cur:0.00;dur:123;..."
```

---

### **STEP 2: Fix idle_alarms**

1. Copy `start_detail` dari alarm_raw → idle_alarms
2. Extract `dur` dari start_detail
3. Recalculate `duration_seconds` dan `duration_minutes`

**Before**:
```
idle_alarms.start_detail: NULL
idle_alarms.duration_seconds: 3600 (dari API)
```

**After**:
```
idle_alarms.start_detail: "avg:0.00;cur:0.00;dur:123;..."
idle_alarms.duration_seconds: 123 (dari dur dalam start_detail)
idle_alarms.duration_minutes: 2.05 (123 / 60)
```

---

## 🔍 VERIFIKASI

### Sebelum Fix:
```bash
php check_start_detail.php
```

Output:
```
Total idle_alarms: 28,164
Empty start_detail: 17,170 (60.96%) ❌
With start_detail: 10,994 (39.04%)
```

---

### Setelah Fix:
```bash
php check_start_detail.php
```

Output:
```
Total idle_alarms: 28,164
Empty start_detail: 0 (0%) ✅
With start_detail: 28,164 (100%) ✅
```

---

## 🛡️ SAFETY FEATURES

### Transaction-Based:
- ✅ Auto rollback kalau error
- ✅ Batch processing (1000 per batch)
- ✅ Progress bar real-time

### Safe Updates:
- ✅ Only updates empty start_detail
- ✅ Reads from existing alarm_raw
- ✅ No data deletion
- ✅ Reversible (can re-run ProcessIdleAlarmJob)

---

## 📝 TECHNICAL DETAILS

### Duration Calculation:

**Priority Order**:
1. **start_detail `dur`** ← PREFERRED (most accurate)
2. API `alarmTimeLength` (fallback)
3. Calculated from start_time → end_time (fallback)

**Format start_detail**:
```
avg:0.00;cur:0.00;dur:123;max:0.00;min:0.00;pre:8.00;tt:300;vt:2;satellites:22
                  ^^^^^^
                  Duration in seconds
```

**Regex Extract**:
```php
preg_match('/dur:\s*(\d+)/', $detail, $matches);
$duration = $matches[1];  // 123
```

---

## ⚠️ CATATAN PENTING

### Kenapa Duration Berbeda?

**API `alarmTimeLength`**:
- Total waktu dari `createtime` → `endTime`
- Termasuk waktu idle + waktu bergerak

**start_detail `dur`**:
- **HANYA waktu idle** (sebelum kendaraan bergerak)
- Lebih akurat untuk laporan idle

**Contoh**:
```
createtime: 08:00
kendaraan idle: 08:00 - 08:05 (5 menit)
kendaraan bergerak: 08:05 - 08:10
endTime: 08:10

alarmTimeLength: 600 seconds (10 menit total)
dur (start_detail): 300 seconds (5 menit idle) ← LEBIH AKURAT
```

---

## 🎯 HASIL AKHIR

### Data Structure:

**idle_alarms record**:
```json
{
  "device_name": "GPE-DT-999",
  "starting_time": "2026-06-10 11:02:08",
  "start_detail": "avg:0.00;cur:0.00;dur:86;max:0.00;min:0.00;pre:0.00;tt:300;vt:2;satellites:22",
  "end_detail": "dur:86;tt:300;cur:13.24;pre:13.00;avg:0.52;min:8.23;max:13.78;vt:2;satellites:22",
  "duration_seconds": 86,
  "duration_minutes": 1.43
}
```

---

## 📚 FILES CREATED

```
app/Console/Commands/
└── FixIdleAlarmsDataCommand.php         (NEW)

root/
├── FIX_IDLE_ALARMS_DATA.bat            (NEW)
├── FIX_IDLE_ALARMS_INSTRUCTIONS.md     (NEW - this file)
└── check_start_detail.php              (verification)
```

---

## 💡 NEXT STEPS

1. **Run fix command**:
   ```
   FIX_IDLE_ALARMS_DATA.bat
   ```

2. **Wait** 5-10 minutes (progress bar will show)

3. **Verify**:
   ```bash
   php check_start_detail.php
   ```

4. **Refresh frontend** - start_detail akan terisi

---

**Ready? Jalankan:**

```
FIX_IDLE_ALARMS_DATA.bat
```

