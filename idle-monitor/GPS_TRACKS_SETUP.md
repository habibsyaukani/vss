# GPS Tracks Setup Instructions

**Date:** June 11, 2026  
**Status:** ✅ Configuration Complete - Ready to Run Migrations

---

## ✅ COMPLETED STEPS

### Step 1: Configuration ✅
- ✅ `.env` updated with VSS credentials
- ✅ Config uses same credentials as HOWEN (already configured)
- ✅ VSS_BASE_URL: `https://vss.ptdigital.co.id`
- ✅ VSS_USERNAME: `dash_gpe_gam`
- ✅ VSS_PASSWORD: `Gpe@939393!`
- ✅ VSS_PER_PAGE: `200`
- ✅ VSS_PAGE_DELAY_MS: `500`

### Step 2: Migration Files Created ✅
- ✅ `2026_06_11_000001_create_gps_tracks_raw_table.php`
- ✅ `2026_06_11_000002_create_gps_tracks_table.php`

---

## 🚀 NEXT STEPS TO RUN

### Step 3: Run Migrations

Open terminal dan jalankan:

```bash
cd g:\project\vss\idle-monitor

php artisan migrate
```

**Expected Output:**
```
Migrating: 2026_06_11_000001_create_gps_tracks_raw_table
Migrated:  2026_06_11_000001_create_gps_tracks_raw_table (XX.XXms)

Migrating: 2026_06_11_000002_create_gps_tracks_table
Migrated:  2026_06_11_000002_create_gps_tracks_table (XX.XXms)
```

### Step 4: Verify Tables Created

Check database:

```sql
SHOW TABLES LIKE 'gps_tracks%';
```

Expected result:
- `gps_tracks`
- `gps_tracks_raw`

### Step 5: Test Preview API (No DB Save)

Test preview endpoint tanpa save ke database:

```bash
# Using curl (adjust device_id, dates as needed)
curl "http://localhost:8000/api/gps-tracks/preview?device_id=73200940&begin_time=2026-06-11 00:00:00&end_time=2026-06-11 23:59:59&page=1"
```

**Or using browser/Postman:**
```
GET http://localhost:8000/api/gps-tracks/preview?device_id=73200940&begin_time=2026-06-11%2000:00:00&end_time=2026-06-11%2023:59:59&page=1
```

**Expected Response:**
```json
{
  "success": true,
  "page": 1,
  "per_page": 200,
  "total": 450,
  "total_pages": 3,
  "from": 1,
  "to": 200,
  "data": [...]
}
```

### Step 6: Test Sync API (Save to DB)

Sync data ke database:

```bash
curl -X POST http://localhost:8000/api/gps-tracks/sync \
  -H "Content-Type: application/json" \
  -d '{
    "device_id": "73200940",
    "begin_time": "2026-06-11 00:00:00",
    "end_time": "2026-06-11 23:59:59"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "stats": {
    "total_fetched": 450,
    "total_saved": 450,
    "pages": 3,
    "errors": []
  }
}
```

### Step 7: Verify Data in Database

Check if data saved:

```sql
-- Check raw records
SELECT COUNT(*) as total_raw FROM gps_tracks_raw;

-- Check display records
SELECT COUNT(*) as total_display FROM gps_tracks;

-- Check latest 10 GPS tracks
SELECT 
    device_name, 
    speed, 
    latitude, 
    longitude, 
    is_overspeed,
    is_acc_on,
    gps_time 
FROM gps_tracks 
ORDER BY gps_time DESC 
LIMIT 10;
```

### Step 8: Test Frontend Speed Page

Navigate to Speed Monitoring page:

```
http://localhost:8000/speed
```

**Expected:**
- ✅ Page loads successfully
- ✅ DataTable displays GPS tracks
- ✅ Filters work (date, device, fleet, status)
- ✅ Speed color coding visible (green/yellow/red)
- ✅ Record count updates

---

## 📊 AVAILABLE DEVICE IDS

To find available device IDs, check devices table:

```sql
SELECT device_id, device_name, status 
FROM devices 
WHERE status = 'active'
ORDER BY device_name
LIMIT 10;
```

Example device IDs:
- `73200940` (GPE-DT-1098)
- `755161145` (GPE-B-8322)
- `732390518` (GPE-FT-873)
- etc.

---

## 🔧 TROUBLESHOOTING

### Issue 1: Migration fails with "table already exists"

**Solution:**
```bash
# Check existing tables
php artisan db:show

# If tables exist, rollback first
php artisan migrate:rollback --step=2

# Then migrate again
php artisan migrate
```

### Issue 2: VSS Login Error

**Error:** "VSS login gagal"

**Check:**
```bash
# Verify .env config
cat .env | grep VSS

# Expected:
# VSS_BASE_URL=https://vss.ptdigital.co.id
# VSS_USERNAME=dash_gpe_gam
# VSS_PASSWORD=Gpe@939393!
```

### Issue 3: No data returned from preview

**Possible causes:**
- Device ID tidak ada
- Date range tidak ada data
- VSS API down

**Check logs:**
```bash
tail -f storage/logs/laravel.log
```

### Issue 4: Sync saves 0 records

**Check:**
- VSS API response status
- Database connection
- Laravel logs for errors

---

## 📝 QUICK REFERENCE

### API Endpoints:

**Preview (no save):**
```
GET /api/gps-tracks/preview
  ?device_id=73200940
  &begin_time=2026-06-11 00:00:00
  &end_time=2026-06-11 23:59:59
  &page=1
```

**Sync (save to DB):**
```
POST /api/gps-tracks/sync
{
  "device_id": "73200940",
  "begin_time": "2026-06-11 00:00:00",
  "end_time": "2026-06-11 23:59:59"
}
```

### Frontend:
```
http://localhost:8000/speed
```

---

## ✅ SUCCESS CRITERIA

GPS Tracks system berfungsi dengan baik jika:

1. ✅ Migrations run successfully (2 tables created)
2. ✅ Preview API returns GPS data
3. ✅ Sync API saves data to DB
4. ✅ Database has records in both tables
5. ✅ Frontend `/speed` displays data
6. ✅ Filters work (date, device, fleet, status)
7. ✅ Speed color coding visible
8. ✅ No errors in Laravel logs

---

**Ready to proceed with Step 3!** 🚀

Run the migration command:
```bash
cd g:\project\vss\idle-monitor
php artisan migrate
```

Then test the API endpoints.

---

**Created:** June 11, 2026  
**Version:** 1.0
