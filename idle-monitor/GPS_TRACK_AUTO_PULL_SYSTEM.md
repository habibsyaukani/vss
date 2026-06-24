# 🗺️ GPS TRACK AUTO-PULL SYSTEM

**Date:** 2026-06-11  
**Status:** ✅ IMPLEMENTED  
**Version:** 1.0

---

## 🎯 OVERVIEW

Sistem otomatis untuk tarik data GPS Track dari VSS API secara berkala, mirip dengan sistem idle alarm yang sudah ada.

### System Flow:

```
┌─────────────────────────────────────────────────────────────┐
│                    GPS TRACK AUTO-PULL FLOW                  │
└─────────────────────────────────────────────────────────────┘

1. IMPORT (Every 5 minutes)
   ┌──────────────────┐
   │ VSS API (Track)  │
   └────────┬─────────┘
            │ ImportGpsTrackJob
            │ (Pull last 2 hours)
            ▼
   ┌──────────────────┐
   │ gps_tracks_raw   │ ← Raw data dari VSS
   └────────┬─────────┘
            │
            
2. PROCESS (Every 3 minutes)
            │ ProcessGpsTrackJob
            │ (Map & Display)
            ▼
   ┌──────────────────┐
   │   gps_tracks     │ ← Display format
   └──────────────────┘
            │
            ▼
   Frontend Dashboard / API
```

---

## 📁 FILES CREATED

### NEW FILES:

1. **`app/Jobs/ImportGpsTrackJob.php`**
   - Tarik GPS data dari VSS API
   - Simpan ke `gps_tracks_raw`
   - Support multi-device dengan delay
   - Error handling per device

2. **`app/Jobs/ProcessGpsTrackJob.php`**
   - Process `gps_tracks_raw` → `gps_tracks`
   - Map data ke display format
   - Extract mileage dari state_json
   - Format IO state dan network type

3. **`GPS_TRACK_AUTO_PULL_SYSTEM.md`** (this file)
   - Dokumentasi lengkap sistem

### MODIFIED FILES:

4. **`app/Console/Kernel.php`**
   - Added: ImportGpsTrackJob scheduler (every 5 minutes)
   - Added: ProcessGpsTrackJob scheduler (every 3 minutes)

---

## ⚙️ CONFIGURATION

### Scheduler Setup:

```php
// GPS Track Import - Every 5 minutes
$schedule->job(new \App\Jobs\ImportGpsTrackJob(2, 500))
    ->everyFiveMinutes()
    ->withoutOverlapping();

// GPS Track Process - Every 3 minutes
$schedule->job(new \App\Jobs\ProcessGpsTrackJob())
    ->everyThreeMinutes()
    ->withoutOverlapping();
```

### ImportGpsTrackJob Parameters:

| Parameter | Default | Description |
|-----------|---------|-------------|
| `$hoursBack` | 2 | Berapa jam ke belakang data diambil |
| `$delayBetweenDevicesMs` | 500 | Delay antar device (milliseconds) |

**Example:**
```php
// Custom: Pull last 4 hours with 1 second delay
new \App\Jobs\ImportGpsTrackJob(4, 1000)
```

---

## 🚀 HOW IT WORKS

### 1️⃣ ImportGpsTrackJob

**Purpose:** Tarik data GPS dari VSS API untuk semua device aktif

**Process:**
1. Get VSS authentication token via `VssAuthService`
2. Get all active devices with `device_id` (required for VSS API)
3. Calculate time range (last X hours)
4. For each device:
   - Call `GpsTrackSyncService->syncDevice()`
   - Pull all GPS data in range
   - Save to `gps_tracks_raw`
   - Add delay between devices (default 500ms)
5. Log results to `import_logs` table

**API Endpoint Used:**
```
POST http://vss.ptdigital.co.id/vss/track/getApiTrackList.action
```

**Parameters:**
- `token`: VSS auth token
- `deviceID`: Device ID
- `beginTime`: Start time (Y-m-d H:i:s)
- `endTime`: End time (Y-m-d H:i:s)
- `pageNum`: Page number
- `pageCount`: Records per page (200)

**Timeout:** 15 minutes (900 seconds)

---

### 2️⃣ ProcessGpsTrackJob

**Purpose:** Map dan proses data dari `gps_tracks_raw` ke `gps_tracks`

**Process:**
1. Find `gps_tracks_raw` records yang belum ada di `gps_tracks`
2. Process dalam chunk (1000 records per batch)
3. For each raw record:
   - Map to display format
   - Extract mileage dari `state_json`
   - Format network type, IO state
   - Calculate flags (ACC ON, overspeed, emergency, recording)
4. Save to `gps_tracks` with `raw_id` link
5. Log results to `import_logs` table

**Mapping Rules:**
- `is_acc_on`: `acc_state == 1`
- `is_overspeed`: `over_speed == 1`
- `is_emergency`: `urgency == 1`
- `is_recording`: `record_state > 0`
- `today_mileage`: Extract from `state_json.mileage.todayDay` (convert: 10m → km)
- `total_mileage`: Extract from `state_json.mileage.total` (convert: 10m → km)

**Timeout:** 10 minutes (600 seconds)

---

## 📊 DATABASE TABLES

### gps_tracks_raw (Raw Data)

Raw data dari VSS API, struktur lengkap:

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| device_id | varchar | Device ID |
| device_name | varchar | Device name |
| guid | varchar | Unique identifier |
| longitude | decimal(10,7) | GPS longitude |
| latitude | decimal(10,7) | GPS latitude |
| altitude | int | Altitude (meter) |
| speed | int | Speed (km/h) |
| direction | int | Direction (0-360°) |
| satellites | int | GPS satellite count |
| precision | int | GPS precision |
| mode | int | GPS mode |
| acc_state | int | ACC ON/OFF (1/0) |
| record_state | int | Recording state (bitmask) |
| video_mask_state | int | Video mask state |
| video_lost_state | int | Video lost state |
| io_state | int | Input/Output state (bitmask) |
| urgency | int | Emergency state |
| over_speed | int | Overspeed flag |
| low_speed | int | Low speed flag |
| oil_volume | varchar | Fuel volume |
| net_type | int | Network type (1-8) |
| signal_value | int | Signal strength |
| dev_voltage | varchar | Device voltage |
| bat_voltage | varchar | Battery voltage |
| driver_card_id | varchar | Driver card ID |
| driver_name | varchar | Driver name |
| gps_time | datetime | GPS timestamp |
| report_time | datetime | Report timestamp |
| state_json | json | Additional state data |
| tempe_humidity | json | Temperature & humidity |
| is_later | int | Is later flag |

### gps_tracks (Display Data)

Display-friendly format untuk frontend:

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| raw_id | bigint | FK to gps_tracks_raw |
| device_id | varchar | Device ID |
| device_name | varchar | Device name |
| longitude | decimal(10,7) | GPS longitude |
| latitude | decimal(10,7) | GPS latitude |
| altitude | int | Altitude |
| speed | int | Speed (km/h) |
| direction | int | Direction |
| satellites | int | Satellite count |
| gps_time | datetime | GPS timestamp |
| report_time | datetime | Report timestamp |
| is_acc_on | boolean | ACC status |
| is_overspeed | boolean | Overspeed flag |
| is_emergency | boolean | Emergency flag |
| is_recording | boolean | Recording flag |
| net_type_label | varchar | Network type (readable) |
| dev_voltage | varchar | Device voltage |
| driver_name | varchar | Driver name |
| today_mileage | decimal(10,2) | Today mileage (km) |
| total_mileage | decimal(10,2) | Total mileage (km) |
| io_state_label | varchar | IO state (readable) |
| input_output_status | varchar | Same as io_state_label |

---

## 🔄 SCHEDULER TIMELINE

```
Time    Import  Process  Idle   Notes
-----   ------  -------  -----  ------------------------
00:00   ✅      ✅       ✅     All jobs run
00:02           ✅              Idle alarm import
00:03           ✅              GPS process
00:04                   ✅     Idle alarm import
00:05   ✅                     GPS import
00:06           ✅       ✅     GPS process + Idle process
00:08                   ✅     Idle alarm import
00:09           ✅              GPS process
00:10   ✅              ✅     GPS import + Idle alarm import
00:12           ✅       ✅     GPS process + Idle process
...

Every 3 minutes: ProcessGpsTrackJob, Idle alarm import
Every 5 minutes: ImportGpsTrackJob, ProcessIdleAlarmJob
```

**Overlap Strategy:**
- `->withoutOverlapping()` ensures no duplicate job runs
- Offset timing prevents resource contention
- GPS import (5 min) vs GPS process (3 min) = efficient pipeline

---

## 📈 MONITORING

### Check Job Status:

```sql
-- Recent GPS import logs
SELECT * FROM import_logs 
WHERE job_name = 'ImportGpsTrackJob' 
ORDER BY started_at DESC 
LIMIT 10;

-- Recent GPS process logs
SELECT * FROM import_logs 
WHERE job_name = 'ProcessGpsTrackJob' 
ORDER BY started_at DESC 
LIMIT 10;

-- Check latest GPS data
SELECT device_name, MAX(gps_time) as latest_gps
FROM gps_tracks
GROUP BY device_name
ORDER BY latest_gps DESC;

-- Check raw vs processed count
SELECT 
    (SELECT COUNT(*) FROM gps_tracks_raw) as raw_count,
    (SELECT COUNT(*) FROM gps_tracks) as processed_count,
    (SELECT COUNT(*) FROM gps_tracks_raw WHERE id NOT IN (SELECT raw_id FROM gps_tracks)) as pending_count;
```

### Laravel Logs:

```bash
# Watch logs in real-time
tail -f storage/logs/laravel.log | grep "GpsTrack"

# Check for errors
grep "ERROR" storage/logs/laravel.log | grep "GpsTrack"
```

### Performance Metrics:

Monitor these in `import_logs`:
- `total_record`: How many records processed
- `started_at` → `finished_at`: Job duration
- `status`: completed / failed
- `message`: Details and errors

---

## 🧪 MANUAL TESTING

### Run Jobs Manually:

```bash
# Run import job (default: 2 hours back)
php artisan queue:work --once --queue=default

# Or dispatch directly in tinker
php artisan tinker
>>> dispatch(new \App\Jobs\ImportGpsTrackJob(2, 500));
>>> dispatch(new \App\Jobs\ProcessGpsTrackJob());
```

### Test with Custom Range:

```php
// Pull last 24 hours
dispatch(new \App\Jobs\ImportGpsTrackJob(24, 500));

// Pull with longer delay (1 second)
dispatch(new \App\Jobs\ImportGpsTrackJob(2, 1000));
```

### Check Results:

```sql
-- Latest GPS tracks
SELECT device_name, gps_time, speed, latitude, longitude, is_acc_on
FROM gps_tracks
ORDER BY gps_time DESC
LIMIT 20;

-- Devices with ACC ON
SELECT device_name, speed, gps_time
FROM gps_tracks
WHERE is_acc_on = 1
ORDER BY gps_time DESC;

-- Overspeed records
SELECT device_name, speed, gps_time, latitude, longitude
FROM gps_tracks
WHERE is_overspeed = 1
ORDER BY gps_time DESC;
```

---

## ⚠️ TROUBLESHOOTING

### Problem: No data imported

**Check:**
1. VSS token valid?
   ```bash
   php artisan tinker
   >>> app(\App\Services\VssAuthService::class)->getToken()
   ```

2. Active devices exist?
   ```sql
   SELECT COUNT(*) FROM devices WHERE status = 'active' AND device_id IS NOT NULL;
   ```

3. Check logs:
   ```sql
   SELECT * FROM import_logs WHERE job_name = 'ImportGpsTrackJob' ORDER BY started_at DESC LIMIT 1;
   ```

---

### Problem: Import succeeds but process fails

**Check:**
1. Raw data exists?
   ```sql
   SELECT COUNT(*) FROM gps_tracks_raw WHERE id NOT IN (SELECT raw_id FROM gps_tracks);
   ```

2. Check process logs:
   ```sql
   SELECT * FROM import_logs WHERE job_name = 'ProcessGpsTrackJob' ORDER BY started_at DESC LIMIT 1;
   ```

3. Manual process one record:
   ```bash
   php artisan tinker
   >>> $raw = \App\Models\GpsTrackRaw::whereDoesntHave('track')->first();
   >>> dispatch(new \App\Jobs\ProcessGpsTrackJob());
   ```

---

### Problem: Job timeout

**Solutions:**
1. Reduce `$hoursBack` parameter (2 → 1 hour)
2. Increase `$delayBetweenDevicesMs` (500 → 1000ms)
3. Increase timeout in job:
   ```php
   public $timeout = 1800; // 30 minutes
   ```

---

### Problem: API rate limit

**Solutions:**
1. Increase delay between devices:
   ```php
   new \App\Jobs\ImportGpsTrackJob(2, 1000) // 1 second delay
   ```

2. Reduce frequency in scheduler:
   ```php
   ->everyTenMinutes() // Instead of everyFiveMinutes()
   ```

3. Filter devices (only import critical devices):
   ```php
   // In ImportGpsTrackJob
   $devices = Device::where('status', 'active')
       ->whereNotNull('device_id')
       ->where('is_critical', true) // Add filter
       ->get();
   ```

---

## 🔧 CUSTOMIZATION

### Change Import Frequency:

```php
// app/Console/Kernel.php

// Every 10 minutes instead of 5
$schedule->job(new \App\Jobs\ImportGpsTrackJob(2, 500))
    ->everyTenMinutes()
    ->withoutOverlapping();
```

### Change Time Range:

```php
// Pull last 4 hours instead of 2
$schedule->job(new \App\Jobs\ImportGpsTrackJob(4, 500))
    ->everyFiveMinutes()
    ->withoutOverlapping();
```

### Add Device Filter:

```php
// ImportGpsTrackJob->handle()

// Only specific series
$devices = Device::where('status', 'active')
    ->whereNotNull('device_id')
    ->where('series', 'VOLVO') // Filter by series
    ->get();

// Or specific location
$devices = Device::where('status', 'active')
    ->whereNotNull('device_id')
    ->where('location', 'M.SERVICE') // Filter by location
    ->get();
```

### Custom Processing Logic:

```php
// ProcessGpsTrackJob->mapToDisplay()

// Add custom flag
$data['is_idle'] = $raw->speed == 0 && $raw->acc_state == 1;

// Add custom calculation
$data['fuel_efficiency'] = $this->calculateFuelEfficiency($raw);
```

---

## 📊 COMPARISON: GPS TRACK vs IDLE ALARM

| Aspect | GPS Track | Idle Alarm |
|--------|-----------|------------|
| **Source** | VSS API (Track) | Howen API (Alarm) |
| **Import Frequency** | Every 5 minutes | Every 2 minutes |
| **Process Frequency** | Every 3 minutes | Every 5 minutes |
| **Time Range** | Last 2 hours | Last 2 hours |
| **Data Type** | Position tracking | Event-based alarm |
| **Raw Table** | `gps_tracks_raw` | `alarm_raw` |
| **Display Table** | `gps_tracks` | `idle_alarms` |
| **Filter Logic** | All GPS data | Only type 32, state 0 |
| **Device Scope** | All active devices | All active devices |
| **Delay Between Devices** | 500ms | N/A (bulk) |

**Similarity:**
- Both use two-step process (import → process)
- Both use `ImportLog` for monitoring
- Both use chunk processing for efficiency
- Both have error handling per record
- Both are scheduled jobs with `withoutOverlapping()`

---

## 🎯 BEST PRACTICES

### 1. Monitor Job Health

Check daily:
```sql
SELECT 
    job_name,
    COUNT(*) as runs,
    AVG(TIMESTAMPDIFF(SECOND, started_at, finished_at)) as avg_duration_sec,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failures
FROM import_logs
WHERE DATE(started_at) = CURDATE()
GROUP BY job_name;
```

### 2. Clean Old Raw Data

GPS data can grow large. Archive or delete old data:

```sql
-- Archive data older than 30 days
INSERT INTO gps_tracks_raw_archive
SELECT * FROM gps_tracks_raw
WHERE gps_time < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Then delete
DELETE FROM gps_tracks_raw
WHERE gps_time < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

### 3. Index Important Columns

Ensure fast queries:
```sql
CREATE INDEX idx_gps_time ON gps_tracks_raw(gps_time);
CREATE INDEX idx_device_gps ON gps_tracks_raw(device_id, gps_time);
CREATE INDEX idx_raw_id ON gps_tracks(raw_id);
```

### 4. Alert on Failures

Setup alert when jobs fail repeatedly:
```sql
SELECT COUNT(*) as failure_count
FROM import_logs
WHERE job_name IN ('ImportGpsTrackJob', 'ProcessGpsTrackJob')
AND status = 'failed'
AND started_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);
-- If failure_count > 3: Send alert
```

---

## 🔒 SAFETY & PROTECTION

### ✅ Protection Rules Followed:

- [x] No database schema changes (tables already exist)
- [x] No changes to existing jobs (idle alarm untouched)
- [x] No changes to models (reused existing)
- [x] No changes to controllers or routes
- [x] No data deletion
- [x] Backward compatible
- [x] Easy to disable (comment in scheduler)
- [x] Error handling per device (one failure doesn't stop others)
- [x] Logging for debugging
- [x] Timeout protection

### Risk Level: 🟡 YELLOW

**Mitigated by:**
- Proven pattern (copied from idle alarm)
- Chunk processing (memory efficient)
- Delay between devices (API friendly)
- Comprehensive error handling
- Detailed logging
- Easy to rollback (comment scheduler)

---

## 📝 CHANGELOG

### Version 1.0 (2026-06-11)

**Created:**
- ✅ `ImportGpsTrackJob` - Auto-pull GPS data from VSS API
- ✅ `ProcessGpsTrackJob` - Map raw → display format
- ✅ Scheduler configuration (5 min import, 3 min process)
- ✅ Complete documentation

**Features:**
- Multi-device support with delay
- Error handling per device
- Progress logging
- Mileage extraction from state_json
- Network type and IO state formatting
- Chunk processing for efficiency
- VSS authentication integration

---

## 🚀 DEPLOYMENT

### Steps to Activate:

1. **Verify scheduler is running:**
   ```bash
   # Check cron or task scheduler
   php artisan schedule:list
   ```

2. **Test manually first:**
   ```bash
   php artisan tinker
   >>> dispatch(new \App\Jobs\ImportGpsTrackJob(1, 500)); // Test 1 hour
   ```

3. **Monitor first few runs:**
   ```sql
   SELECT * FROM import_logs 
   WHERE job_name LIKE '%GpsTrack%' 
   ORDER BY started_at DESC;
   ```

4. **Check data is flowing:**
   ```sql
   SELECT COUNT(*) FROM gps_tracks WHERE gps_time > DATE_SUB(NOW(), INTERVAL 2 HOUR);
   ```

### Rollback (if needed):

Comment out in `app/Console/Kernel.php`:
```php
// $schedule->job(new \App\Jobs\ImportGpsTrackJob(2, 500))
//     ->everyFiveMinutes()
//     ->withoutOverlapping();

// $schedule->job(new \App\Jobs\ProcessGpsTrackJob())
//     ->everyThreeMinutes()
//     ->withoutOverlapping();
```

---

**Status:** ✅ READY FOR DEPLOYMENT  
**Risk:** 🟡 YELLOW (Medium-Low)  
**Next Step:** Test manually, then enable scheduler  

**Created By:** Kiro AI  
**Date:** 2026-06-11  
**Project:** VSS Idle Monitor

