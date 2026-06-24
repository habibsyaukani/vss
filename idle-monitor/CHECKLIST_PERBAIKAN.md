# ✅ Checklist Perbaikan Duration

Gunakan checklist ini untuk memastikan semua langkah sudah dilakukan dengan benar.

---

## 📋 PRE-PERBAIKAN

- [ ] Baca `PERBAIKAN_DURATION_PANDUAN.md` (5 menit)
- [ ] Pastikan tidak ada proses pull data yang sedang berjalan
- [ ] (Opsional) Backup database jika ingin extra safe

---

## 🔍 LANGKAH 1: VERIFIKASI STATUS AWAL

- [ ] Jalankan `VERIFY_DURATION.bat`
- [ ] Catat jumlah record problematic:
  - alarm_raw dengan dur=0: ________ records
  - idle_alarms dengan dur=0: ________ records
- [ ] Catat persentase correct:
  - alarm_raw: ________%
  - idle_alarms: ________%

---

## 👀 LANGKAH 2: PREVIEW PERBAIKAN (DRY RUN)

- [ ] Jalankan `FIX_DURATION_DRY_RUN.bat`
- [ ] Review output - pastikan logic benar:
  - [ ] Duration extracted dari alarmvalue? 
  - [ ] Fallback ke endDetail jika alarmvalue kosong?
  - [ ] Skip record yang tidak punya dur value?
- [ ] Berapa record yang akan diperbaiki: ________ records
- [ ] Berapa record yang akan di-skip: ________ records

**⚠️ PENTING:** Jika output terlihat aneh, JANGAN lanjut ke LANGKAH 3!

---

## 🔧 LANGKAH 3: TERAPKAN PERBAIKAN

- [ ] Jalankan `FIX_DURATION_APPLY.bat`
- [ ] Monitor progress:
  - Batch 1: ________ fixed, ________ skipped
  - Batch 2: ________ fixed, ________ skipped
  - Batch 3: ________ fixed, ________ skipped
  - (tambah baris jika perlu)
- [ ] Tunggu sampai selesai semua
- [ ] Catat total yang diperbaiki:
  - Total alarm_raw fixed: ________ records
  - Total idle_alarms fixed: ________ records

**Estimasi waktu:**
- < 1000 records: 2-5 menit
- 1000-5000 records: 10-25 menit
- > 5000 records: 20-50 menit

---

## ✅ LANGKAH 4: VERIFIKASI HASIL

- [ ] Jalankan `VERIFY_DURATION.bat` lagi
- [ ] Bandingkan dengan status awal:

| Metric | Sebelum | Sesudah | Target |
|--------|---------|---------|--------|
| alarm_raw problematic | _____ | _____ | < 1% |
| idle_alarms problematic | _____ | _____ | < 1% |
| alarm_raw % correct | _____% | _____% | > 99% |
| idle_alarms % correct | _____% | _____% | > 99% |

- [ ] Apakah % correct sudah > 99%? 
  - [ ] ✅ Ya → Lanjut ke LANGKAH 5
  - [ ] ❌ Tidak → Ulangi LANGKAH 3 (mungkin masih ada batch tersisa)

---

## 🧪 LANGKAH 5: TEST DATA BARU

- [ ] Pull data baru dari API:
  ```bash
  php artisan howen:pull-alarms-realtime --wait
  ```
- [ ] Verify data baru:
  ```bash
  php verify_duration_fix.php
  ```
- [ ] Cek apakah data baru sudah punya duration yang benar:
  - [ ] ✅ Ya, data baru langsung correct
  - [ ] ❌ Tidak, masih ada yang dur:0 (hubungi developer)

---

## 🗄️ LANGKAH 6: VERIFIKASI DATABASE LANGSUNG

- [ ] Buka phpMyAdmin atau database client
- [ ] Jalankan query verifikasi:

```sql
-- Cek sample data
SELECT 
    guid, 
    LEFT(alarm_value, 50) as alarm_value_preview,
    duration_seconds,
    created_at
FROM alarm_raw 
WHERE alarm_type = 32 
ORDER BY created_at DESC 
LIMIT 10;
```

- [ ] Pastikan `duration_seconds` match dengan `dur:XXXX` di `alarm_value`
- [ ] Sample 10 record:
  - Record 1: dur:_____ → duration:_____ ✅ Match?
  - Record 2: dur:_____ → duration:_____ ✅ Match?
  - Record 3: dur:_____ → duration:_____ ✅ Match?

---

## 📊 LANGKAH 7: VERIFIKASI FRONTEND

- [ ] Buka halaman Idle Alarm di frontend
- [ ] Cek kolom Duration:
  - [ ] Tidak ada lagi yang 0 seconds atau NULL
  - [ ] Semua menampilkan durasi yang wajar (5+ menit)
  - [ ] Duration match dengan Start Detail column
- [ ] Cek beberapa device:
  - Device 1: ________________ ✅ Duration OK?
  - Device 2: ________________ ✅ Duration OK?
  - Device 3: ________________ ✅ Duration OK?

---

## 📝 POST-PERBAIKAN

- [ ] Catat hasil akhir:
  - Total records diperbaiki: ________ records
  - Waktu total: ________ menit
  - Status: ✅ Berhasil / ❌ Perlu review
- [ ] Update log internal (jika ada)
- [ ] (Opsional) Screenshot hasil verifikasi
- [ ] (Opsional) Hapus backup database jika yakin berhasil

---

## 🎯 SUCCESS CRITERIA

Perbaikan dianggap **BERHASIL** jika:

✅ **Data Lama:**
- [ ] > 99% alarm_raw memiliki duration_seconds > 0
- [ ] > 99% idle_alarms memiliki duration_seconds > 0
- [ ] Duration match dengan dur value di alarm_value

✅ **Data Baru:**
- [ ] Data yang ditarik setelah fix langsung punya duration correct
- [ ] Tidak perlu backfill lagi untuk data baru

✅ **Frontend:**
- [ ] Duration column tidak ada lagi yang 0 atau NULL
- [ ] Semua durasi masuk akal (5+ menit untuk idle alarm)

✅ **Performance:**
- [ ] Query tidak melambat
- [ ] API response time normal
- [ ] Dashboard load time normal

---

## ⚠️ TROUBLESHOOTING

### Problem: Masih banyak record dengan dur:0 setelah fix

**Solution:**
- [ ] Cek apakah ada error di log: `storage/logs/laravel.log`
- [ ] Jalankan verify untuk cek detail: `php verify_duration_fix.php`
- [ ] Cek sample raw_json apakah punya dur value
- [ ] Jika raw_json tidak punya dur, itu memang data tidak lengkap (wajar)

### Problem: Data baru masih ada yang dur:0

**Solution:**
- [ ] Cek log pull command: `storage/logs/laravel.log`
- [ ] Cek response dari Howen API (log harus ada)
- [ ] Kemungkinan API response tidak punya dur (hubungi provider)

### Problem: Duration tidak match dengan frontend

**Solution:**
- [ ] Clear cache browser: Ctrl+Shift+R
- [ ] Clear cache Laravel: `php artisan cache:clear`
- [ ] Cek query di frontend apakah sudah benar
- [ ] Verify database langsung dengan SQL query

---

## 📞 NEED HELP?

Jika ada masalah yang tidak bisa diselesaikan:

1. **Cek dokumentasi:**
   - `PERBAIKAN_DURATION_PANDUAN.md` - Panduan lengkap
   - `DURATION_FIX_SUMMARY.md` - Technical details
   - `README_PERBAIKAN_DURATION.txt` - Quick guide

2. **Cek log:**
   - `storage/logs/laravel.log` - Application log
   - Error message dari command

3. **Cek database:**
   - phpMyAdmin atau database client
   - Jalankan query verifikasi manual

4. **Rollback (jika perlu):**
   - Data raw_json masih utuh
   - Bisa re-run command dengan logic berbeda
   - Atau restore dari backup (jika ada)

---

**Status:** ✅ Siap digunakan  
**Risk Level:** 🟢 GREEN (Safe)  
**Tanggal:** 11 Juni 2026  

**Selamat memperbaiki data! 🚀**
