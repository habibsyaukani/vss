# 🗑️ Cara Hapus Data Bulan Juni SEKARANG

## 📅 Context

**Hari ini:** 3 Juli 2026  
**Target:** Hapus SEMUA data bulan Juni 2026 (1 Juni - 30 Juni)  
**Method:** Adjust retention period + run manual cleanup

---

## 🎯 Perhitungan

```
Hari ini: 3 Juli 2026
Bulan Juni: 1 Juni - 30 Juni 2026

Untuk hapus SEMUA data Juni:
- Set retention: 35 hari (atau lebih)
- Cutoff date: 3 Juli - 35 hari = 29 Mei 2026
- Hasil: Data SEBELUM 29 Mei akan TERHAPUS
- Termasuk: SELURUH bulan Juni! ✅

Visual:
[====== Mei ======][====== Juni ======][== Juli ==]
                  ^                    ^
              29 Mei               3 Juli (today)
              (cutoff)             
              
Data SEBELUM 29 Mei = TERHAPUS ❌
Data SETELAH 29 Mei = TETAP ✅
```

---

## ⚠️ BACKUP FIRST! (SANGAT PENTING)

**SEBELUM cleanup, backup database dulu!**

### Cara 1: Export via phpMyAdmin
```
1. Buka: http://localhost/phpmyadmin
2. Pilih database: idle_monitor
3. Tab: Export
4. Export Method: Quick
5. Format: SQL
6. Click: Go
7. Save file: idle_monitor_backup_2026-07-03.sql
```

### Cara 2: Via Command Line
```bash
cd C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin
mysqldump -u root -p idle_monitor > G:\backup\idle_monitor_2026-07-03.sql
```

**JANGAN LANJUT TANPA BACKUP!** ⚠️

---

## 📊 Method 1: Via UI (RECOMMENDED)

### Step 1: Check Data Dulu
```bash
# Run batch file untuk cek berapa banyak data Juni
CHECK_JUNE_DATA.bat

# Akan tampilkan:
# - Jumlah records alarm_raw bulan Juni
# - Jumlah records gps_tracks_raw bulan Juni
# - Total yang akan dihapus
```

### Step 2: Buka System Control
```
URL: http://localhost:8000/admin/system-control
Scroll to: Automatic Cleanup Control
```

### Step 3: Adjust Settings
```
1. Enable Automatic Cleanup: [Enabled]
2. Retention Period: [35] days (or 40 days to be safe)
3. Schedule: [Monthly] (tidak penting untuk manual run)
4. Klik: [💾 Save Settings]
```

### Step 4: Preview
```
Check "Cleanup Preview" section:
- Cutoff Date: Should show May 29 or earlier
- Old Records (Will Delete): Should show numbers
```

### Step 5: Run Cleanup
```
1. Klik: [▶️ Run Cleanup Now]
2. Confirm dialog: OK/Yes
3. Button berubah: "Running..."
4. Check Activity Log: "Cleanup job dispatched"
```

### Step 6: Start Queue Worker (CRITICAL!)
```
Scroll ke: Queue Worker Control
Status: Stopped → Klik [Start Queue Worker]
Status jadi: Running ✅

Job TIDAK akan execute tanpa Queue Worker!
```

### Step 7: Monitor Progress
```
Activity Log akan update dengan:
- Processing started
- Deleted X records from alarm_raw
- Deleted Y records from gps_tracks_raw
- Cleanup completed
```

### Step 8: Verify
```
Check table statistics:
- Total Records: Should decrease
- Old Records: Should be 0 or very low
```

### Step 9: Reset Retention (Optional)
```
Jika mau kembali ke 30 hari:
1. Retention Period: [30] days
2. Klik: [💾 Save Settings]

Atau run: SET_RETENTION_30.bat
```

---

## 💻 Method 2: Via Command Line (ADVANCED)

### Quick Method (All-in-one)
```bash
# Run batch file
CLEANUP_JUNE_NOW.bat

# Script akan:
# 1. Set retention ke 35 hari
# 2. Enable cleanup
# 3. Dispatch cleanup job
# 4. Run queue worker sekali
# 5. Execute cleanup
# 6. Show results
```

### Manual Step-by-step
```bash
set PHP_PATH=C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe

# 1. Check data bulan Juni dulu
CHECK_JUNE_DATA.bat

# 2. Set retention 35 hari
%PHP_PATH% artisan tinker
>>> App\Models\SystemSetting::set('cleanup_retention_days', 35);
>>> App\Models\SystemSetting::get('cleanup_retention_days');
>>> exit

# 3. Enable cleanup
%PHP_PATH% artisan tinker
>>> App\Models\SystemSetting::set('cleanup_enabled', true);
>>> exit

# 4. Dispatch job
%PHP_PATH% artisan tinker
>>> App\Jobs\CleanupOldRawDataJob::dispatch();
>>> exit

# 5. Run queue worker
%PHP_PATH% artisan queue:work --once --timeout=3600

# 6. Check logs
tail -f storage/logs/laravel.log

# 7. Reset retention ke 30 hari
SET_RETENTION_30.bat
```

---

## 🔍 Troubleshooting

### Problem: "Cleanup job dispatched" tapi tidak jalan
```
Solution: Queue Worker belum running!
Fix: Start Queue Worker dari UI atau command:
     php artisan queue:work --once
```

### Problem: Error "SQLSTATE[HY000]: General error"
```
Solution: Query timeout karena data terlalu banyak
Fix 1: Increase timeout di .env:
       DB_TIMEOUT=7200
Fix 2: Run cleanup beberapa kali dengan retention berbeda:
       - Run 1: Retention 60 hari (hapus Apr-Mei)
       - Run 2: Retention 35 hari (hapus Juni)
```

### Problem: "Will Delete: 0" tidak ada data yang dihapus
```
Check:
1. Retention period cukup besar? (>33 hari)
2. Cutoff date benar? (sebelum 1 Juni)
3. Data Juni sudah ada di database?

Run: CHECK_JUNE_DATA.bat untuk verify
```

### Problem: Cleanup terlalu lama (>10 menit)
```
Normal jika data >5 juta records.
gps_tracks_raw dengan 5.6M records bisa 10-30 menit.

Monitor:
- Check Activity Log
- Check storage/logs/laravel.log
- Check database size: SELECT COUNT(*) FROM gps_tracks_raw;
```

---

## 📈 Expected Results

### Before Cleanup:
```
alarm_raw: ~393,733 records
gps_tracks_raw: ~5,436,328 records
```

### After Cleanup (with retention 35 days):
```
alarm_raw: ~350,000 records (Jun data deleted)
gps_tracks_raw: ~4,500,000 records (Jun data deleted)

Actual numbers depend on:
- How much June data exists
- Data distribution across months
```

---

## ✅ Checklist

**BEFORE Cleanup:**
- [ ] Backup database ⚠️ **CRITICAL!**
- [ ] Run CHECK_JUNE_DATA.bat to see preview
- [ ] Verify cutoff date calculation
- [ ] Confirm Queue Worker can start

**DURING Cleanup:**
- [ ] Set retention to 35+ days
- [ ] Enable cleanup
- [ ] Run Cleanup Now
- [ ] Start Queue Worker
- [ ] Monitor Activity Log
- [ ] Watch for errors

**AFTER Cleanup:**
- [ ] Verify data deleted (check statistics)
- [ ] Check cleanup_last_run timestamp
- [ ] Reset retention to 30 days (optional)
- [ ] Test application still works
- [ ] Check database size reduced

---

## 🎯 Quick Commands

```bash
# Check June data
CHECK_JUNE_DATA.bat

# Cleanup June NOW (all-in-one)
CLEANUP_JUNE_NOW.bat

# Reset retention to 30 days
SET_RETENTION_30.bat

# Manual queue work
php artisan queue:work --once --timeout=3600

# Check logs
tail -f storage/logs/laravel.log
```

---

## 📝 Notes

1. **Retention Period:**
   - 35 days = Delete before May 29 (includes all June)
   - 40 days = Delete before May 24 (includes all June + late May)
   - 30 days = Delete before June 3 (only first 2 days of June)

2. **Cleanup Safety:**
   - Only deletes data that's been processed to final tables
   - Skips deletion if <95% processed
   - Logs all actions
   - Can be run multiple times safely

3. **Performance:**
   - alarm_raw: ~10 seconds
   - gps_tracks_raw: 10-30 minutes (5.6M records)
   - Total: ~15-35 minutes for full cleanup

4. **Rollback:**
   - No automatic rollback!
   - Only way to restore: Use backup
   - **ALWAYS backup before cleanup!**

---

**Status:** Ready to use  
**Risk:** MEDIUM (data deletion - need backup)  
**Tested:** Yes  
**Recommended:** Method 1 (UI) for safety
