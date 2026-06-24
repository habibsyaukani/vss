═══════════════════════════════════════════════════════════════
  UPDATE DEVICES SERIES & LOCATION - README
═══════════════════════════════════════════════════════════════

STATUS: ❌ BELUM DI-UPDATE - Menunggu Anda jalankan

───────────────────────────────────────────────────────────────
 YANG SUDAH DIBUAT
───────────────────────────────────────────────────────────────

✅ Artisan Command:
   app/Console/Commands/UpdateDevicesSeriesLocation.php
   
✅ Batch Files (Double-click to run):
   - UPDATE_DEVICES_DRY_RUN.bat  (Preview tanpa update)
   - UPDATE_DEVICES_APPLY.bat    (Update database)
   
✅ Documentation:
   - QUICK_START_UPDATE_DEVICES.md  (Quick guide)
   - UPDATE_DEVICES_INSTRUCTIONS.md (Detailed guide)
   - README_UPDATE_DEVICES.txt      (This file)
   
✅ Template:
   - devices_update_data_TEMPLATE.csv

───────────────────────────────────────────────────────────────
 CARA JALANKAN (3 LANGKAH)
───────────────────────────────────────────────────────────────

STEP 1: Buat File CSV
----------------------
Buat file: devices_update_data.csv
Paste semua 397 baris data Anda

Format:
device_code,unit_code,series,location
GPE-B-806,GPE7801,HD 785,SELATAN
GPE-B-807,GPE7802,HD 785,SELATAN
... (all 397 lines)

STEP 2: Test Dulu (Dry Run)
----------------------------
Double-click: UPDATE_DEVICES_DRY_RUN.bat

Atau manual:
cd g:\project\vss\idle-monitor
C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe artisan update:devices-series-location --dry-run

STEP 3: Apply Update
--------------------
Double-click: UPDATE_DEVICES_APPLY.bat

Atau manual:
cd g:\project\vss\idle-monitor
C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe artisan update:devices-series-location

───────────────────────────────────────────────────────────────
 KEAMANAN
───────────────────────────────────────────────────────────────

✅ Transaction-based (auto rollback if error)
✅ Verifies count = 397 before and after
✅ Only updates series & location columns
✅ No data deletion
✅ No schema changes
✅ Backward compatible
✅ Dry-run mode available

───────────────────────────────────────────────────────────────
 OUTPUT YANG DIHARAPKAN
───────────────────────────────────────────────────────────────

📊 Devices count BEFORE: 397
📋 Update data loaded: 397 records
🔄 Processing updates... [Progress bar]
📊 Devices count AFTER: 397

✅ SUCCESS!
   - Updated: 397 devices
   - Not found: 0 devices
   - Total devices: 397 (maintained)

───────────────────────────────────────────────────────────────
 VERIFIKASI SETELAH UPDATE
───────────────────────────────────────────────────────────────

php artisan tinker

>>> \App\Models\Device::count();
=> 397  ✅

>>> \App\Models\Device::whereNotNull('series')->count();
=> 397  ✅

>>> \App\Models\Device::whereNotNull('location')->count();
=> 397  ✅

>>> \App\Models\Device::first(['device_name', 'series', 'location']);
=> {device_name: "GPE-B-806", series: "HD 785", location: "SELATAN"}  ✅

───────────────────────────────────────────────────────────────
 TROUBLESHOOTING
───────────────────────────────────────────────────────────────

Error: "File not found"
→ Pastikan devices_update_data.csv ada di root project

Error: "Count changed"
→ Auto-rollback, data aman. Check CSV format.

Some devices not found
→ device_code harus match persis dengan device_name

───────────────────────────────────────────────────────────────
 BUTUH BANTUAN?
───────────────────────────────────────────────────────────────

1. Baca: QUICK_START_UPDATE_DEVICES.md
2. Run dengan --dry-run dulu
3. Check console error messages

───────────────────────────────────────────────────────────────

SIAP UNTUK DI-UPDATE! 🚀

LANGKAH BERIKUTNYA:
1. Buat devices_update_data.csv (paste 397 lines)
2. Run UPDATE_DEVICES_DRY_RUN.bat
3. Run UPDATE_DEVICES_APPLY.bat

═══════════════════════════════════════════════════════════════
