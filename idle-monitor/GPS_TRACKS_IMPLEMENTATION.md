# GPS Tracks Implementation Guide

**Date:** June 11, 2026  
**Status:** ✅ Complete - Ready for Testing

---

## 📋 OVERVIEW

GPS Tracks system untuk pull data GPS dari VSS API dan display di Speed Monitoring page.

**Architecture:**
```
VSS API → GpsTrackController → GpsTrackSyncService → DB (gps_tracks_raw + gps_tracks)
                                                    ↓
                                          Frontend (/speed page)
```

---

## 🗄️ DATABASE

### Tables Created:

1. **`gps_tracks_raw`** - Raw data 1:1 dari VSS API
   - Safety net - semua field disimpan apa adanya
   - Field: device_id, longitude, latitude, speed, altitude, acc_state, over_speed, state_json, tempe_humidity, dll
   - Relationship: `hasOne gps_tracks`

2. **`gps_tracks`** - Display table (processed)
   - Bit flags → boolean (is_acc_on, is_recording)
   - Net type number → label string (4G, WiFi)
   - IO state siap tampil di frontend
   - Relationship: `belongsTo gps_tracks_raw`

### Migration Files:
- ✅ `2026_06_11_000001_create_gps_tracks_raw_table.php`
- ✅ `2026_06_11_000002_create_gps_tracks_table.php`

**Run migrations:**
```bash
php artisan migrate
```

---

## 🔐 CONFIGURATION

### .env Configuration:

Add to `.env` file:

```env
# VSS API Configuration
VSS_BASE_URL=http://vss.ptdigital.co.id
VSS_USERNAME=your_username
VSS_PASSWORD=your_md5_hashed_password

# GPS Sync Settings
VSS_PER_PAGE=200
VSS_PAGE_DELAY_MS=500
```

**Important:** `VSS_PASSWORD` harus MD5 hash dari password asli!

---

## 📁 FILES CREATED

### Services:

1. **`app/Services/VssAuthService.php`**
   - Login ke VSS API
   - Token caching (25 menit, VSS expire 30 menit)
   - `getToken()` - Get cached token atau login baru
   - `refreshToken()` - Force refresh token

2. **`app/Services/GpsTrackSyncService.php`**
   - Fetch GPS data dari VSS API
   - Loop semua pages dengan delay 500ms
   - Save ke `gps_tracks_raw` + `gps_tracks`
   - Methods:
     - `syncDevice()` - Sync semua pages ke DB
     - `fetchPage()` - Fetch satu page dari VSS
     - `previewPage()` - Preview tanpa save ke DB
     - `mapToRaw()` - Mapping VSS → raw table
     - `mapToDisplay()` - Mapping VSS → display table

### Controllers:

3. **`app/Http/Controllers/GpsTrackController.php`**
   - API controller untuk GPS tracks
   - Endpoints:
     - `GET /api/gps-tracks/preview` - Preview data
     - `POST /api/gps-tracks/sync` - Sync ke DB

### Models:

4. **`app/Models/GpsTrackRaw.php`**
   - Model untuk `gps_tracks_raw`
   - Relationships, scopes, casts

5. **`app/Models/GpsTrack.php`**
   - Model untuk `gps_tracks`
   - Accessors: `speed_with_unit`, `coordinates`
   - Scopes: `latest()`, `byDevice()`, `overspeed()`, `byFleet()`

### Config:

6. **`config/vss.php`**
   - VSS API configuration
   - Per page settings
   - Delay settings

### Routes:

7. **`routes/api.php`** (updated)
   - Added GPS tracks routes

### Frontend:

8. **`app/Http/Controllers/Frontend/SpeedController.php`** (updated)
   - Display GPS tracks di Speed page
   - Filter by device, fleet, date range, overspeed

9. **`resources/views/frontend/speed/index.blade.php`**
   - Speed monitoring page dengan DataTables
   - Real-time filters
   - Color-coded speed badges

---

## 🔌 API ENDPOINTS

### 1. Preview Data (GET)

**Endpoint:** `GET /api/gps-tracks/preview`

**Purpose:** Preview data dari VSS tanpa save ke DB (untuk testing)

**Query Parameters:**
```
device_id    : required | string  | Device ID (e.g., "73200940")
begin_time   : required | string  | Format: Y-m-d H:i:s (e.g., "2026-06-11 13:00:00")
end_time     : required | string  | Format: Y-m-d H:i:s (e.g., "2026-06-11 14:00:00")
page         : optional | integer | Page number (default: 1)
```

**Example:**
```bash
GET /api/gps-tracks/preview?device_id=73200940&begin_time=2026-06-11 13:00:00&end_time=2026-06-11 14:00:00&page=1
```

**Response:**
```json
{
  "success": true,
  "page": 1,
  "per_page": 200,
  "total": 450,
  "total_pages": 3,
  "from": 1,
  "to": 200,
  "data": [
    {
      "device_id": "73200940",
      "device_name": "GPE-DT-1098",
      "longitude": 117.153,
      "latitude": -0.502,
      "speed": 45,
      "altitude": 120,
      "direction": 180,
      "satellites": 12,
      "gps_time": "2026-06-11 13:00:00",
      "acc_on": true,
      "overspeed": false,
      "net_type": "4G"
    },
    ...
  ]
}
```

---

### 2. Sync Data to DB (POST)

**Endpoint:** `POST /api/gps-tracks/sync`

**Purpose:** Pull semua data GPS dan save ke database (loop otomatis semua pages)

**Request Body:**
```json
{
  "device_id": "73200940",
  "begin_time": "2026-06-11 13:00:00",
  "end_time": "2026-06-11 14:00:00"
}
```

**Example cURL:**
```bash
curl -X POST http://localhost:8000/api/gps-tracks/sync \
  -H "Content-Type: application/json" \
  -d '{
    "device_id": "73200940",
    "begin_time": "2026-06-11 13:00:00",
    "end_time": "2026-06-11 14:00:00"
  }'
```

**Response:**
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

**Alur Sync:**
1. Page 1 ditarik dulu untuk tahu `totalCount` dan `totalNum`
2. Loop page 2 sampai habis dengan jeda 500ms per page
3. Setiap record disimpan ke `gps_tracks_raw`
4. Otomatis transform ke `gps_tracks` untuk display

---

## 🌐 FRONTEND

### Speed Monitoring Page

**URL:** `http://localhost:8000/speed`

**Features:**
- ✅ DataTables dengan server-side processing
- ✅ Filter by date range
- ✅ Filter by device
- ✅ Filter by fleet
- ✅ Filter by status (overspeed/acc on)
- ✅ Speed color coding:
  - 🟢 Green: < 80 km/h (normal)
  - 🟡 Yellow: 80-99 km/h (warning)
  - 🔴 Red: ≥ 100 km/h (danger)
- ✅ Status badges: OVERSPEED, ACC ON, EMERGENCY
- ✅ Real-time record count
- ✅ Export button (placeholder)

---

## 🧪 TESTING

### Step 1: Run Migrations

```bash
php artisan migrate
```

### Step 2: Test Preview (tanpa save ke DB)

```bash
# Using curl
curl "http://localhost:8000/api/gps-tracks/preview?device_id=73200940&begin_time=2026-06-11 13:00:00&end_time=2026-06-11 14:00:00&page=1"

# Or using browser/Postman
GET http://localhost:8000/api/gps-tracks/preview?device_id=73200940&begin_time=2026-06-11%2013:00:00&end_time=2026-06-11%2014:00:00&page=1
```

**Expected:** JSON response dengan data GPS (tidak save ke DB)

### Step 3: Test Sync (save ke DB)

```bash
curl -X POST http://localhost:8000/api/gps-tracks/sync \
  -H "Content-Type: application/json" \
  -d '{
    "device_id": "73200940",
    "begin_time": "2026-06-11 13:00:00",
    "end_time": "2026-06-11 14:00:00"
  }'
```

**Expected:** JSON response dengan stats (total_fetched, total_saved, pages)

### Step 4: Check Database

```sql
-- Check raw records
SELECT COUNT(*) FROM gps_tracks_raw;

-- Check display records
SELECT COUNT(*) FROM gps_tracks;

-- Check latest GPS tracks
SELECT device_name, speed, latitude, longitude, gps_time 
FROM gps_tracks 
ORDER BY gps_time DESC 
LIMIT 10;
```

### Step 5: Test Frontend

1. Navigate to: `http://localhost:8000/speed`
2. Should display GPS tracks in DataTable
3. Test filters (date, device, fleet, status)
4. Verify speed color coding
5. Check record count updates

---

## 🔧 TROUBLESHOOTING

### Issue 1: "VSS login gagal"

**Cause:** Wrong VSS credentials atau password tidak MD5 hashed

**Solution:**
```bash
# Check .env
VSS_USERNAME=correct_username
VSS_PASSWORD=5f4dcc3b5aa765d61d8327deb882cf99  # MD5 hash

# Test MD5 hash
echo -n "your_password" | md5sum
```

### Issue 2: "Token expired" error

**Cause:** Token cache expired atau VSS session timeout

**Solution:**
- Token auto-refresh setiap 25 menit
- Manual refresh: `VssAuthService::refreshToken()`

### Issue 3: No data in `/speed` page

**Cause:** No GPS tracks in database

**Solution:**
```bash
# Sync data first
curl -X POST http://localhost:8000/api/gps-tracks/sync \
  -H "Content-Type: application/json" \
  -d '{"device_id":"73200940", "begin_time":"2026-06-11 00:00:00", "end_time":"2026-06-11 23:59:59"}'

# Then refresh /speed page
```

### Issue 4: "HTTP 429 Too Many Requests" dari VSS

**Cause:** Too many requests too fast

**Solution:**
- Increase delay: `VSS_PAGE_DELAY_MS=1000` (1 second)
- Reduce page size: `VSS_PER_PAGE=100`

---

## 📊 DATA MAPPING

### VSS API Fields → Database Fields:

| VSS Field | gps_tracks_raw | gps_tracks | Notes |
|-----------|----------------|------------|-------|
| deviceID | device_id | device_id | Device ID |
| deviceName | device_name | device_name | Device Name |
| longitude | longitude | longitude | Decimal(11,7) |
| latitude | latitude | latitude | Decimal(10,7) |
| speed | speed | speed | Integer (km/h) |
| altitude | altitude | altitude | Integer (meter) |
| direct | direction | direction | 0-360 degrees |
| satellites | satellites | satellites | Count |
| createtime | gps_time | gps_time | GPS timestamp |
| reportTime | report_time | report_time | Report timestamp |
| accState | acc_state | is_acc_on | 1=ON → true |
| overSpeed | over_speed | is_overspeed | 1=YES → true |
| urgency | urgency | is_emergency | 1=YES → true |
| recordState | record_state | is_recording | Bitmask > 0 → true |
| netType | net_type | net_type_label | 5 → "4G" |
| devVoltage | dev_voltage | dev_voltage | Float |
| driverName | driver_name | driver_name | String |
| stateJson | state_json | (mileage extract) | JSON → today/total km |

---

## 🚀 NEXT STEPS

1. ✅ Models created
2. ✅ Services created (VssAuthService, GpsTrackSyncService)
3. ✅ Controller created (GpsTrackController)
4. ✅ API routes added
5. ✅ Frontend view created (/speed)
6. ✅ Config file created (vss.php)

**TODO:**
- [ ] Run migrations (`php artisan migrate`)
- [ ] Configure .env (VSS credentials)
- [ ] Test preview endpoint
- [ ] Test sync endpoint
- [ ] Verify frontend display
- [ ] Create scheduled job for auto-sync (optional)
- [ ] Add export feature to Speed page (optional)

---

## 📝 NOTES

**Token Caching:**
- Token di-cache 25 menit (VSS expire 30 menit)
- Auto-refresh jika expired
- Cache key: `vss_token`

**Page Delay:**
- Default: 500ms between pages
- Configurable via `VSS_PAGE_DELAY_MS`
- Prevents VSS server overload

**Data Safety:**
- Raw data tersimpan di `gps_tracks_raw` (safety net)
- Display data di `gps_tracks` (processed, ready for frontend)
- `updateOrCreate` prevents duplicates

**Performance:**
- Default 200 records per page
- Batch processing dengan delay
- Efficient DB queries dengan indexes

---

**Created:** June 11, 2026  
**Last Updated:** June 11, 2026  
**Version:** 1.0
