# 📅 PULL GPS DATA - TANGGAL 11 JUNI 2026

**Date:** 2026-06-12  
**Target Date:** 2026-06-11 (kemarin)  
**Purpose:** Tarik data GPS untuk tanggal kemarin (backfill)

---

## 🎯 OVERVIEW

Script ini akan:
1. ✅ Tarik data GPS dari VSS API untuk tanggal 11 Juni 2026
2. ✅ Range: 00:00:00 - 23:59:59 (full day)
3. ✅ Simpan ke `gps_tracks_raw`
4. ✅ Dispatch `ProcessGpsTrackJob` untuk proses ke `gps_tracks`

---

## 🚀 CARA MENJALANKAN

### Option 1: Via Batch File (Recommended)

**Double-click:**
```
PULL_GPS_JUNE_11.bat
```

**Atau via command line:**
```cmd
cd g:\project\vss\idle-monitor
PULL_GPS_JUNE_11.bat
```

### Option 2: Via PHP Direct

```cmd
cd g:\project\vss\idle-monitor
C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe pull_gps_yesterday.php
```

---

## ⏱️ ESTIMASI WAKTU

**Tergantung jumlah device dan data:**
- 10 devices: ~3-5 menit
- 50 devices: ~10-15 menit
- 100+ devices: ~20-30 menit
- 397 devices: ~45-60 menit

**Delay between devices:** 500ms (untuk tidak overwhelm API)

---

## 📊 OUTPUT EXAMPLE

```
========================================
  PULL GPS DATA - 11 JUNI 2026
========================================

📅 Target Date: 2026-06-11
⏰ Time Range: 2026-06-11 00:00:00 - 2026-06-11 23:59:59

🔐 Getting VSS authentication token...
✅ Token obtained successfully

🚗 Loading active devices...
✅ Found 397 active devices

📡 Starting GPS data sync...
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

[1/397] GPE-B-8322 (ID: 755161145)
   ✅ Fetched: 120 | Saved: 120 | Pages: 1

[2/397] GPE-FT-873 (ID: 732390518)
   ✅ Fetched: 200 | Saved: 200 | Pages: 1

[3/397] GPE-DTI-807 (ID: 731865503)
   ✅ Fetched: 180 | Saved: 180 | Pages: 1

...

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📊 IMPORT SUMMARY
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✅ Devices Processed: 397 / 397
✅ Total Records Fetched: 45,234
✅ Total Records Saved: 45,234
⏱️  Duration: 2847.5 seconds

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🔄 PROCESSING RAW DATA
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Processing gps_tracks_raw → gps_tracks...
✅ ProcessGpsTrackJob dispatched to queue
ℹ️  Run queue worker to process: php artisan queue:work

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ COMPLETED SUCCESSFULLY
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

## ✅ AFTER RUNNING

### Step 1: Process Raw Data

Script sudah dispatch `ProcessGpsTrackJob` ke queue. Sekarang run queue worker:

```bash
cd g:\project\vss\idle-monitor
php artisan queue:work --once
```

Atau untuk process terus:
```bash
php artisan queue:work
```

### Step 2: Verify Data

**Check raw data count:**
```sql
SELECT COUNT(*) as total 
FROM gps_tracks_raw 
WHERE DATE(gps_time) = '2026-06-11';
```

**Check processed data count:**
```sql
SELECT COUNT(*) as total 
FROM gps_tracks 
WHERE DATE(gps_time) = '2026-06-11';
```

**Check latest GPS per device:**
```sql
SELECT device_name, MAX(gps_time) as latest, COUNT(*) as records
FROM gps_tracks 
WHERE DATE(gps_time) = '2026-06-11' 
GROUP BY device_name
ORDER BY device_name;
```

**Check sample data:**
```sql
SELECT device_name, gps_time, speed, latitude, longitude, is_acc_on
FROM gps_tracks
WHERE DATE(gps_time) = '2026-06-11'
ORDER BY gps_time DESC
LIMIT 20;
```

### Step 3: Check Import Logs

```sql
SELECT * FROM import_logs
WHERE job_name IN ('ImportGpsTrackJob', 'ProcessGpsTrackJob')
ORDER BY started_at DESC
LIMIT 5;
```

---

## 🔍 MONITORING DURING RUN

### Option 1: Via Script Output
Script akan show progress untuk setiap device:
```
[123/397] GPE-HD-855 (ID: 732390760)
   ✅ Fetched: 150 | Saved: 150 | Pages: 1
```

### Option 2: Via Database (別の terminal)

**Count records in real-time:**
```sql
-- Update setiap 30 detik
SELECT COUNT(*) FROM gps_tracks_raw WHERE DATE(gps_time) = '2026-06-11';
```

**Check latest device synced:**
```sql
SELECT device_name, MAX(created_at) as last_sync
FROM gps_tracks_raw
WHERE DATE(gps_time) = '2026-06-11'
GROUP BY device_name
ORDER BY last_sync DESC
LIMIT 5;
```

---

## ⚠️ TROUBLESHOOTING

### Issue: Token Error
```
❌ Failed to get VSS token
```

**Solution:**
```bash
# Test authentication
php artisan app:test-vss-auth

# Or refresh token
php artisan tinker
>>> app(\App\Services\VssAuthService::class)->refreshToken();
```

---

### Issue: Device Errors
```
❌ Error: HTTP 500 / Connection timeout
```

**Solution:**
- Script akan continue dengan device berikutnya
- Error devices akan di-list di summary
- Re-run script untuk retry failed devices
- Or: Increase delay between devices (edit script: 500000 → 1000000)

---

### Issue: No Data for Some Devices
```
✅ Fetched: 0 | Saved: 0 | Pages: 0
```

**Possible Reasons:**
- Device tidak ada data GPS di tanggal tersebut
- Device tidak aktif di tanggal tersebut
- Device baru registered setelah tanggal tersebut
- GPS device mati/offline

**Check:**
```sql
-- Check device status
SELECT device_name, status, device_id, last_sync_at
FROM devices
WHERE device_name = 'GPE-XXX-XXX';

-- Check if device has ANY GPS data
SELECT MIN(gps_time) as first_gps, MAX(gps_time) as last_gps, COUNT(*) as total
FROM gps_tracks_raw
WHERE device_id = '755161145';
```

---

### Issue: Queue Job Not Processing
```
ℹ️  Run queue worker to process: php artisan queue:work
```

**Solution:**
```bash
# Check queue
php artisan queue:work --once

# Or manual dispatch
php artisan tinker
>>> dispatch(new \App\Jobs\ProcessGpsTrackJob());
```

---

## 📝 FILES CREATED

1. **`pull_gps_yesterday.php`** - Main script
2. **`PULL_GPS_JUNE_11.bat`** - Batch file untuk run
3. **`PULL_GPS_JUNE_11_GUIDE.md`** - This guide

---

## 🔄 FOR OTHER DATES

Untuk tarik data tanggal lain, edit `pull_gps_yesterday.php`:

```php
// Line 37: Change target date
$targetDate = '2026-06-11';  // Ganti dengan tanggal yang diinginkan

// Example:
$targetDate = '2026-06-10';  // 10 Juni 2026
$targetDate = '2026-06-09';  // 9 Juni 2026
```

Or create new script:
```bash
cp pull_gps_yesterday.php pull_gps_june_10.php
# Edit $targetDate = '2026-06-10';
```

---

## 🛡️ SAFETY

✅ **Safe to Run:**
- Script hanya INSERT/UPDATE data
- Tidak ada DELETE operation
- Tidak mengubah existing data
- Menggunakan `updateOrCreate` (safe duplicate handling)

✅ **Can Be Re-run:**
- Script dapat dijalankan ulang tanpa masalah
- Data yang sama akan di-update, bukan duplicate

✅ **No Impact to Live System:**
- Hanya tarik historical data
- Tidak mempengaruhi scheduler yang sedang running
- Tidak mempengaruhi idle alarm system

---

## ✅ SUCCESS CRITERIA

Script berhasil jika:
- ✅ Devices Processed: 397 / 397 (atau sesuai jumlah active devices)
- ✅ Total Records Fetched: > 0
- ✅ Total Records Saved: = Total Fetched
- ✅ Errors: 0 atau minimal

Verification berhasil jika:
- ✅ `gps_tracks_raw` count > 0 untuk 2026-06-11
- ✅ `gps_tracks` count = `gps_tracks_raw` count
- ✅ Sample data memiliki lat/long, speed, timestamp yang valid

---

## 📞 SUPPORT

**If Issues:**
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check import_logs table
3. Run verification queries
4. Check this guide's troubleshooting section

**For Customization:**
- Edit `pull_gps_yesterday.php` directly
- Change `$targetDate` for different dates
- Adjust delay: `usleep(500000)` (500ms) → increase if needed

---

**Created:** 2026-06-12  
**Target Date:** 2026-06-11  
**Status:** ✅ READY TO RUN

