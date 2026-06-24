# 📦 CARA IMPORT DEVICES KE DATABASE

## ✅ STATUS SAAT INI

- **CSV File:** `devices_update_data.csv` ✅ UPDATED
- **Total Devices:** 397 
- **VOLVO Series:** 16 devices
- **M.SERVICE Location:** 19 entries
- **Database:** ⚠️ BELUM DI-IMPORT

---

## 🔄 STEP-BY-STEP IMPORT KE DATABASE

### Option 1: Menggunakan Batch File (RECOMMENDED)

1. **Double-click file:**
   ```
   IMPORT_DEVICES_TO_DATABASE.bat
   ```

2. **Konfirmasi:**
   - Akan muncul warning bahwa data existing akan dihapus
   - Press ENTER untuk lanjut atau CTRL+C untuk cancel

3. **Tunggu proses import selesai**

4. **Verifikasi hasil:**
   - Total devices harus 397
   - VOLVO series harus 16
   - M.SERVICE location harus 19

---

### Option 2: Manual via PHP Command

```batch
C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe import_devices_from_csv.php
```

---

## ⚠️ IMPORTANT WARNINGS

### 1. BACKUP DATABASE DULU!

Sebelum import, backup database terlebih dahulu:

```sql
-- Via phpMyAdmin atau MySQL CLI
mysqldump -u root vss > backup_vss_before_import.sql
```

### 2. DATA AKAN DIHAPUS

Import script akan:
- ✅ TRUNCATE table `devices` (hapus semua data existing)
- ✅ Insert 397 devices baru dari CSV
- ✅ Include VOLVO series dan M.SERVICE location yang sudah di-update

### 3. PASTIKAN APLIKASI TIDAK RUNNING

Stop aplikasi Laravel dulu sebelum import:
- Stop web server
- Stop queue worker
- Stop scheduler

---

## 🔍 VERIFIKASI SETELAH IMPORT

### Via PHP Script:

```batch
C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe check_devices_table.php
```

### Via MySQL CLI atau phpMyAdmin:

```sql
-- Cek total devices
SELECT COUNT(*) FROM devices;
-- Expected: 397

-- Cek VOLVO series
SELECT COUNT(*) FROM devices WHERE series = 'VOLVO';
-- Expected: 16

-- Cek M.SERVICE location
SELECT COUNT(*) FROM devices WHERE location = 'M.SERVICE';
-- Expected: 19

-- Cek specific VOLVO units
SELECT device_code, unit_code, series, location 
FROM devices 
WHERE unit_code IN ('GPE932', 'GPE937', 'GPE951', 'GPE952', 'GPE953', 'GPE955', 'GPE998', 'GPE999')
ORDER BY unit_code;
-- Expected: 8 rows with series = 'VOLVO', location = 'M.SERVICE'

-- Cek M.SERVICE units (GPE-DT-28xx)
SELECT device_code, unit_code, series, location 
FROM devices 
WHERE device_code LIKE 'GPE-DT-28%'
ORDER BY device_code;
-- Expected: 11 rows with location = 'M.SERVICE'
```

---

## 📋 EXPECTED RESULTS

After successful import, you should see:

```
===========================================
  IMPORT SUMMARY
===========================================
✅ Total devices imported: 397
✅ VOLVO series: 16 devices
✅ M.SERVICE location: 19 devices

DATABASE VERIFICATION:
-------------------------------------------
Total in database: 397
VOLVO in database: 16
M.SERVICE in database: 19

✅ Import verification PASSED!
===========================================
  IMPORT COMPLETED
===========================================
```

---

## 🚨 TROUBLESHOOTING

### Error: "CSV file not found"

**Solution:**
- Pastikan file `devices_update_data.csv` ada di root folder project
- Cek path file di script `import_devices_from_csv.php`

### Error: "Connection refused"

**Solution:**
- Pastikan Laragon MySQL sudah running
- Cek `.env` file untuk database credentials

### Error: "Table 'devices' doesn't exist"

**Solution:**
- Run migration dulu: `php artisan migrate`

### Import berhasil tapi data tidak muncul di aplikasi

**Solution:**
- Clear cache: `php artisan cache:clear`
- Clear config: `php artisan config:clear`
- Restart web server

---

## 📁 FILES INVOLVED

- `devices_update_data.csv` - Source CSV with updated data
- `import_devices_from_csv.php` - PHP import script
- `IMPORT_DEVICES_TO_DATABASE.bat` - Batch file untuk easy import
- `check_devices_table.php` - Verification script
- `.env` - Database configuration

---

## ✅ NEXT STEPS AFTER IMPORT

1. ✅ Verify data di database (via SQL queries above)
2. ✅ Test aplikasi - cek apakah device list muncul dengan benar
3. ✅ Test filter by series "VOLVO"
4. ✅ Test filter by location "M.SERVICE"
5. ✅ Regenerate any cached data if needed

---

**Last Updated:** June 11, 2026  
**Task:** Import updated devices data with VOLVO series and M.SERVICE location
