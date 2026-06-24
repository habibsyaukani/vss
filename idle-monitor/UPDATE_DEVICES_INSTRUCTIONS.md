# 📋 UPDATE DEVICES SERIES & LOCATION - INSTRUCTIONS

**Date**: June 10, 2026  
**Purpose**: Update `series` and `location` columns in `devices` table  
**Safety**: Transaction-based, verifies 397 devices maintained

---

## ✅ WHAT THIS DOES

- Updates `series` column (HD 785, HD 465, FMX 400, FMX 420, etc.)
- Updates `location` column (SELATAN, UTARA, STB_SITE, JO SELATAN, MUD UTARA, M.SERVICE)
- **Does NOT**:
  - Change device_name
  - Delete any devices
  - Add new devices
  - Modify other columns

---

## 🔒 SAFETY FEATURES

1. **Transaction**: Automatic rollback if anything fails
2. **Count verification**: Must remain 397 devices before and after
3. **Backup recommended**: Take database backup first
4. **UPDATE only**: No INSERT or DELETE
5. **Specific columns**: Only `series` and `location`

---

## 📂 FILES CREATED

1. **`devices_update_data.txt`** - Raw CSV data (397 lines)
2. **`app/Console/Commands/UpdateDevicesSeriesLocation.php`** - Artisan command
3. **`UPDATE_DEVICES_INSTRUCTIONS.md`** - This file

---

## 🚀 HOW TO RUN

### Step 1: Prepare Data File

Create file: `g:\project\vss\idle-monitor\devices_update_data.txt`

Paste your CSV data (all 397 lines) in this format:
```
device_code,unit_code,series,location
GPE-B-806,GPE7801,HD 785,SELATAN
GPE-B-807,GPE7802,HD 785,SELATAN
... (all 397 lines)
```

### Step 2: Run Artisan Command

```bash
cd g:\project\vss\idle-monitor
C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe artisan update:devices-series-location
```

### Step 3: Verify

Command will show:
- ✅ Before count: 397
- ✅ Updates processed: X
- ✅ After count: 397
- ✅ Success message

---

## 📊 EXPECTED RESULTS

**Before Update**:
```sql
SELECT COUNT(*) FROM devices;  -- 397
SELECT COUNT(*) FROM devices WHERE series IS NULL;  -- Maybe 397 (all NULL)
SELECT COUNT(*) FROM devices WHERE location IS NULL;  -- Maybe 397 (all NULL)
```

**After Update**:
```sql
SELECT COUNT(*) FROM devices;  -- 397 (MUST BE SAME!)
SELECT COUNT(*) FROM devices WHERE series IS NOT NULL;  -- 397 (all updated)
SELECT COUNT(*) FROM devices WHERE location IS NOT NULL;  -- 397 (all updated)
```

**Sample Data After Update**:
```
device_name    | series          | location
GPE-B-806      | HD 785          | SELATAN
GPE-DT-1015    | HD 465          | SELATAN
GPE-DT-1181    | DT LAMA FMX 400 | STB_SITE
GPE-DT-1182    | DT LAMA FMX 400 | UTARA
GPE-FT-860     | DT BARU FMX 400 | UTARA
GPE-HD-701     | DT BARU FMX 420 | UTARA
```

---

## 🧪 TEST FIRST (Optional)

Run in dry-run mode (if implemented):
```bash
php artisan update:devices-series-location --dry-run
```

This will show what WOULD be updated without actually updating.

---

## 🔄 ROLLBACK (If Needed)

If something goes wrong, restore from backup:

```sql
-- Restore from backup
mysql -u root -p idle_monitor < backup_devices_before_update.sql

-- Or manually revert
UPDATE devices SET series = NULL, location = NULL;
```

---

## ✅ VERIFICATION QUERIES

After update, run these queries:

```sql
-- 1. Check total count (MUST be 397)
SELECT COUNT(*) as total_devices FROM devices;

-- 2. Check updated series
SELECT COUNT(*) as devices_with_series 
FROM devices 
WHERE series IS NOT NULL;

-- 3. Check updated location
SELECT COUNT(*) as devices_with_location 
FROM devices 
WHERE location IS NOT NULL;

-- 4. Check series distribution
SELECT series, COUNT(*) as count 
FROM devices 
GROUP BY series 
ORDER BY count DESC;

-- 5. Check location distribution
SELECT location, COUNT(*) as count 
FROM devices 
GROUP BY location 
ORDER BY count DESC;

-- 6. Sample data
SELECT device_name, series, location 
FROM devices 
LIMIT 10;
```

Expected series values:
- HD 785
- HD 465
- OHT 773
- DT LAMA FMX 400
- DT LAMA FMX 370
- DT BARU FMX 400
- DT BARU FMX 420

Expected location values:
- SELATAN
- UTARA
- STB_SITE
- JO SELATAN
- MUD UTARA
- M.SERVICE
- STB_001

---

## 📝 DATA FORMAT

Your CSV should be in this format:

```
device_code,unit_code,series,location
GPE-B-806,GPE7801,HD 785,SELATAN
```

Where:
- `device_code` = device_name in database (e.g., GPE-B-806)
- `unit_code` = Not used (reference only)
- `series` = Value to update in devices.series
- `location` = Value to update in devices.location

---

## ⚠️ IMPORTANT NOTES

1. **Backup First**: Take database backup before running
2. **Test Environment**: Test on dev/staging first if possible
3. **Production**: Run during low-traffic time
4. **Verification**: Always verify count = 397 after update
5. **No Duplicates**: Each device_code should appear once
6. **Match Exact**: device_code must match device_name in database

---

## 🐛 TROUBLESHOOTING

**Problem**: Count not 397 after update
- **Solution**: Transaction should auto-rollback, restore from backup

**Problem**: Some devices not updated
- **Solution**: Check device_code matches device_name exactly (case-sensitive)

**Problem**: Transaction fails
- **Solution**: Check database connection, check data format

**Problem**: Data file not found
- **Solution**: Ensure `devices_update_data.txt` exists in project root

---

## 📞 SUPPORT

If issues arise:
1. Check command output for error messages
2. Verify data file format
3. Check database connection
4. Review transaction log
5. Restore from backup if needed

---

**Status**: Ready to execute
**Risk**: 🟢 GREEN (Safe, transaction-based, count-verified)
