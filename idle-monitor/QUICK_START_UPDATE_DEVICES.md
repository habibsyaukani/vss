# 🚀 QUICK START: Update Devices Series & Location

**Status**: ⚠️ **BELUM DI-UPDATE** - Menunggu Anda jalankan command

---

## ✅ LANGKAH CEPAT (3 Steps)

### Step 1: Buat File CSV

Buat file: `g:\project\vss\idle-monitor\devices_update_data.csv`

Paste semua 397 baris data Anda dalam format:
```
device_code,unit_code,series,location
GPE-B-806,GPE7801,HD 785,SELATAN
GPE-B-807,GPE7802,HD 785,SELATAN
... (paste all 397 lines)
```

### Step 2: Test Dulu (Dry Run)

```bash
cd g:\project\vss\idle-monitor
C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe artisan update:devices-series-location --dry-run
```

Ini akan show preview **tanpa** update database.

### Step 3: Jalankan Update

```bash
C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe artisan update:devices-series-location
```

**Output yang diharapkan**:
```
═══════════════════════════════════════════════════════════════
  UPDATE DEVICES - SERIES & LOCATION
═══════════════════════════════════════════════════════════════

📊 Devices count BEFORE: 397
📋 Update data loaded: 397 records

🔄 Processing updates...
[Progress bar...]

📊 Devices count AFTER: 397

✅ SUCCESS!
   - Updated: 397 devices
   - Not found: 0 devices
   - Total devices: 397 (maintained)

✅ Update completed successfully!
```

---

## 📋 FILE YANG SUDAH DIBUAT

1. ✅ `app/Console/Commands/UpdateDevicesSeriesLocation.php` - Artisan command
2. ✅ `devices_update_data_TEMPLATE.csv` - Template CSV
3. ✅ `UPDATE_DEVICES_INSTRUCTIONS.md` - Dokumentasi lengkap
4. ✅ `QUICK_START_UPDATE_DEVICES.md` - This file

---

## 🔒 KEAMANAN

- ✅ Uses transaction (auto rollback if error)
- ✅ Verifies count = 397 before and after
- ✅ Only updates `series` and `location` columns
- ✅ No data deletion
- ✅ Dry-run mode available

---

## ⚠️ PENTING

1. **Backup database dulu** (optional tapi recommended):
   ```bash
   mysqldump -u root -p idle_monitor > backup_before_update.sql
   ```

2. **Pastikan CSV file ada** di `g:\project\vss\idle-monitor\devices_update_data.csv`

3. **Format CSV harus benar**: 4 kolom, dipisah koma

---

## 🐛 TROUBLESHOOTING

**Error: "File not found"**
- Pastikan file `devices_update_data.csv` ada di root project
- Path harus: `g:\project\vss\idle-monitor\devices_update_data.csv`

**Error: "Count changed"**
- Command auto-rollback, data aman
- Check CSV format

**Some devices not found**
- device_code harus match persis dengan device_name di database
- Case-sensitive

---

## ✅ VERIFIKASI SETELAH UPDATE

```bash
# Masuk ke tinker
php artisan tinker

# Check count
>>> \App\Models\Device::count();
=> 397  // ✅ Must be 397

# Check updated data
>>> \App\Models\Device::whereNotNull('series')->count();
=> 397  // ✅ All should have series

>>> \App\Models\Device::whereNotNull('location')->count();
=> 397  // ✅ All should have location

# Sample data
>>> \App\Models\Device::where('device_name', 'GPE-B-806')->first(['device_name', 'series', 'location']);
=> {
     device_name: "GPE-B-806",
     series: "HD 785",
     location: "SELATAN"
   }  // ✅ Correct!
```

---

## 📞 BUTUH BANTUAN?

1. Lihat `UPDATE_DEVICES_INSTRUCTIONS.md` untuk detail lengkap
2. Run dengan `--dry-run` dulu untuk preview
3. Check error message di console output

---

**Ready to go!** 🚀
