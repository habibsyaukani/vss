# Panduan Perbaikan Duration

## Masalah
Data lama yang sudah ditarik mungkin masih memiliki `duration_seconds = 0` atau `NULL` karena menggunakan logic lama.

## Solusi
Jalankan backfill command untuk memperbaiki data yang sudah ada dengan mengekstrak nilai `dur` dari `alarmvalue` (start_detail).

---

## 🚀 LANGKAH-LANGKAH PERBAIKAN

### LANGKAH 1: Cek Status Saat Ini

Jalankan file:
```
VERIFY_DURATION.bat
```

atau via command:
```bash
php verify_duration_fix.php
```

**Output yang akan ditampilkan:**
- Jumlah record yang sudah benar
- Jumlah record yang masih salah (dur:0 atau NULL)
- Sample data untuk verifikasi
- Persentase data yang sudah benar

**Contoh Output:**
```
alarm_raw (Type 32 - Idle):
  Total records: 32,757
  Records with dur=0 or NULL: 5,234
  Percentage correct: 84.02%

idle_alarms:
  Total records: 25,123
  Records with dur=0 or NULL: 3,456
  Percentage correct: 86.24%
```

---

### LANGKAH 2: Preview Perubahan (Dry Run)

Jalankan file:
```
FIX_DURATION_DRY_RUN.bat
```

atau via command:
```bash
php artisan howen:fix-start-detail-duration --dry-run --limit=100
```

**Fungsi:**
- ✅ Menampilkan apa yang akan diubah
- ✅ TIDAK mengubah data apapun
- ✅ Menampilkan 100 sample record yang akan diperbaiki
- ✅ Menampilkan summary

**Contoh Output:**
```
🔍 Finding alarm_raw records with incorrect duration...
   Found 5,234 records with dur=0 or NULL

Processing: [============================] 100/100

✅ Fixed 98 alarm_raw records
⚠️  Skipped 2 records (no valid duration data)

Summary:
  Total checked: 100
  Would fix: 98
  Would skip: 2
```

**⚠️ PENTING:** Review output ini sebelum lanjut ke LANGKAH 3!

---

### LANGKAH 3: Terapkan Perbaikan

Jalankan file:
```
FIX_DURATION_APPLY.bat
```

atau via command:
```bash
php artisan howen:fix-start-detail-duration --limit=1000
```

**Proses:**
1. Memperbaiki 1000 record per batch
2. Menampilkan progress bar
3. Setelah selesai, cek apakah masih ada record yang perlu diperbaiki
4. Jika masih ada, tanya user apakah mau lanjut batch berikutnya
5. Ulangi sampai semua record selesai

**Fungsi:**
- ✅ UPDATE `alarm_raw.duration_seconds`
- ✅ UPDATE `idle_alarms.duration_seconds` dan `duration_minutes`
- ✅ Extract `dur` dari `alarmvalue` dengan priority yang benar
- ✅ Skip record yang tidak punya data valid
- ✅ Transaction-based (rollback jika error)

**Output yang diharapkan:**
```
Processing batch of 1000 records...
[============================] 1000/1000

✅ Fixed 987 alarm_raw records
⚠️  Skipped 13 records

✅ Fixed 950 idle_alarms records
⚠️  Skipped 50 records

Batch completed. Checking if more records need fixing...

Remaining problematic alarm_raw: 4,234
Remaining problematic idle_alarms: 2,506

Continue with next batch? [Y/N]
```

**Waktu estimasi:**
- 1000 records ≈ 2-5 menit
- 5000 records ≈ 10-25 menit
- 10000 records ≈ 20-50 menit

---

### LANGKAH 4: Verifikasi Hasil

Jalankan kembali:
```
VERIFY_DURATION.bat
```

atau via command:
```bash
php verify_duration_fix.php
```

**Expected Result:**
```
alarm_raw (Type 32 - Idle):
  Total records: 32,757
  Records with dur=0 or NULL: 45  ← Berkurang drastis!
  Percentage correct: 99.86%      ← Naik drastis!

idle_alarms:
  Total records: 25,123
  Records with dur=0 or NULL: 12  ← Berkurang drastis!
  Percentage correct: 99.95%      ← Naik drastis!
```

✅ **Target:** > 99% data correct

---

### LANGKAH 5: Test Data Baru

Test bahwa data baru yang ditarik juga sudah benar:

```bash
php artisan howen:pull-alarms-realtime --wait
```

Lalu verify:
```bash
php verify_duration_fix.php
```

**Expected:** Data baru langsung punya duration yang benar (tidak perlu backfill lagi).

---

## 🔍 APA YANG DIPERBAIKI?

### Priority Extraction (Sudah Benar):
```
1. Cari dur di alarmvalue (start_detail)  ← PRIMARY
2. Jika tidak ada, cari di endDetail       ← FALLBACK 1
3. Jika tidak ada, pakai alarmTimeLength   ← FALLBACK 2
4. Jika semua gagal, hitung dari time diff ← EMERGENCY
```

### Contoh Perbaikan:

**SEBELUM:**
```sql
guid: abc-123
alarm_value: "avg:0.00;cur:0.00;dur:1200;max:0.00;..."
duration_seconds: 0  ❌ SALAH
```

**SESUDAH:**
```sql
guid: abc-123
alarm_value: "avg:0.00;cur:0.00;dur:1200;max:0.00;..."
duration_seconds: 1200  ✅ BENAR (diambil dari dur:1200)
```

---

## ❓ FAQ

### Q: Apakah data lama akan hilang?
**A:** TIDAK. Command ini hanya UPDATE `duration_seconds` dan `duration_minutes`. Data lain (start_detail, end_detail, raw_json, dll) tetap utuh.

### Q: Apakah bisa di-rollback jika ada masalah?
**A:** Ya, karena `raw_json` masih tersimpan lengkap. Bisa re-run command atau restore dari raw_json.

### Q: Berapa lama prosesnya?
**A:** Tergantung jumlah record yang perlu diperbaiki:
- 1,000 records ≈ 2-5 menit
- 5,000 records ≈ 10-25 menit
- 10,000+ records ≈ 20-50 menit

### Q: Apakah perlu stop aplikasi?
**A:** TIDAK perlu. Backfill berjalan di background dan tidak mengganggu aplikasi yang sedang running.

### Q: Bagaimana jika ada error di tengah proses?
**A:** Command menggunakan transaction. Jika error, batch yang sedang berjalan akan di-rollback. Batch sebelumnya yang sudah selesai tetap tersimpan. Bisa re-run dari batch terakhir.

### Q: Apakah data baru (setelah fix) sudah otomatis benar?
**A:** YA. Semua pull command sudah diperbaiki:
- ✅ PullIdleAlarmsRealtimeCommand
- ✅ PullIdleAlarmsPerDayCommand
- ✅ PullIdleAlarmsDateRangeCommand
- ✅ ProcessIdleAlarmJob

Data yang ditarik setelah fix ini akan langsung memiliki duration yang benar.

---

## 🛡️ SAFETY

✅ **NO DATA DELETION** - Hanya update duration fields  
✅ **NO SCHEMA CHANGE** - Tidak mengubah struktur table  
✅ **TRANSACTION-BASED** - Rollback jika error  
✅ **BATCH PROCESSING** - Tidak overload memory  
✅ **DRY RUN MODE** - Preview sebelum apply  
✅ **RAW JSON PRESERVED** - Data mentah tetap utuh  

---

## 📊 MONITORING

### Cek Progress:
```bash
php verify_duration_fix.php
```

### Cek Record Terakhir di Database:
```sql
SELECT 
    guid, 
    LEFT(alarm_value, 50) as alarm_value,
    duration_seconds,
    created_at
FROM alarm_raw 
WHERE alarm_type = 32 
ORDER BY created_at DESC 
LIMIT 10;
```

### Cek Record Problematic:
```sql
-- alarm_raw
SELECT COUNT(*) as total_problematic
FROM alarm_raw 
WHERE alarm_type = 32 
AND (duration_seconds = 0 OR duration_seconds IS NULL);

-- idle_alarms  
SELECT COUNT(*) as total_problematic
FROM idle_alarms 
WHERE duration_seconds = 0 OR duration_seconds IS NULL 
   OR duration_minutes = 0 OR duration_minutes IS NULL;
```

---

## 📝 SUMMARY

### Batch Files:
1. `VERIFY_DURATION.bat` - Cek status saat ini
2. `FIX_DURATION_DRY_RUN.bat` - Preview perubahan (aman)
3. `FIX_DURATION_APPLY.bat` - Terapkan perbaikan (update database)

### Commands:
1. `php verify_duration_fix.php` - Verifikasi
2. `php artisan howen:fix-start-detail-duration --dry-run` - Preview
3. `php artisan howen:fix-start-detail-duration --limit=1000` - Apply

### Workflow:
```
1. VERIFY_DURATION.bat
   ↓ (cek berapa yang perlu diperbaiki)
   
2. FIX_DURATION_DRY_RUN.bat
   ↓ (preview, pastikan logic benar)
   
3. FIX_DURATION_APPLY.bat
   ↓ (apply perbaikan ke database)
   
4. VERIFY_DURATION.bat
   ↓ (confirm hasilnya benar)
   
5. DONE! ✅
```

---

**Pertanyaan?** Lihat dokumentasi lengkap di:
- `DURATION_FIX_SUMMARY.md` - Technical details
- `QUICK_START_DURATION_FIX.md` - Quick reference
- `DEVELOPMENT_PROGRESS.md` - Full project history

**Status:** ✅ Siap digunakan  
**Tanggal:** 11 Juni 2026  
**Risk Level:** 🟢 GREEN (Safe)
