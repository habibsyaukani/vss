# Development Progress - Idle Monitor System

**Current Date**: June 3, 2026
**Status**: Tahap 2 (Auth Howen API) - Dimulai

---

## 📋 TAHAPAN PENGEMBANGAN

### ✅ TAHAP 1 — Database (SELESAI)
**Target**: Setup tabel database  
**Deliverables**:
- ✅ devices table (device_id, device_name, imei, sim_number, last_sync_at)
- ✅ alarm_types table (alarm_code, alarm_name)
- ✅ alarm_raw table (guid, device_id, alarm_type, start_time, end_time, raw_json, etc)
- ✅ idle_alarms table (guid, device_id, alarm_type, alarm_status, starting_time, ending_time, duration_minutes, etc)
- ✅ api_tokens table (token, expires_at)
- ✅ import_logs table (job_name, started_at, finished_at, total_record, status, message)

**Migrations Created**:
- 2026_06_03_163708_create_devices_table.php ✅
- 2026_06_03_163709_create_alarm_raw_table.php ✅
- 2026_06_03_163711_create_idle_alarms_table.php ✅
- 2026_06_03_163711_create_api_tokens_table.php ✅
- 2026_06_03_163717_create_import_logs_table.php ✅
- 2026_06_03_164517_create_alarm_types_table.php ✅

**Models Created**: Device, AlarmRaw, IdleAlarm, ApiToken, ImportLog, AlarmType ✅

---

### ✅ TAHAP 2 — Login Howen API (SELESAI)
**Target**: Login berhasil → Token tersimpan di api_tokens → Token bisa dipakai request berikutnya

**Deliverables** ✅:
- [x] HowenAuthService::authenticate() - Login ke Howen API ✅
- [x] HowenAuthService::refreshToken() - Refresh token jika expired ✅
- [x] HowenAuthService::getToken() - Ambil token dari cache atau database ✅
- [x] RefreshTokenJob implementation ✅
- [x] TestHowenAuth command untuk test login ✅
- [x] Fix authentication - SUCCESS dengan plain password ✅

**Progress** ✅:
- [x] Setup Howen API URL: https://vss.ptdigital.co.id/vss/
- [x] Setup credentials di .env
- [x] Implementasi HowenAuthService dengan endpoint /user/login.action
- [x] Setup token storage di api_tokens table + file cache
- [x] Verify login response & token storage - BERHASIL
- [x] Test getToken dengan cache - BERHASIL

**Solution**:
- Password tidak perlu di-MD5, kirim plain text
- Response status: 10000 = success
- Token stored di database dengan expires_at 30 menit
- Cache driver: file (Redis optional untuk production)

**Test Results**:
```
✅ Authentication successful!
Token: 7516bf9a2c6f4057b93effbfe7599b...
✅ Token stored in database
Expires at: 2026-06-03 17:36:33
✅ getToken returns same token from cache
✅ All tests passed!
```

**Next**: Lanjut TAHAP 3 - Sync Devices

---

### ✅ TAHAP 3 — Sinkronisasi Device (SELESAI)
**Target**: Fetch devices dari Howen API → Simpan ke devices table

**Deliverables** ✅:
- [x] Create system_settings table (key, value) ✅
- [x] SystemSetting model dengan helper get/set ✅
- [x] SyncDeviceJob implementation ✅
- [x] HowenDeviceService::fetchDevices() dengan multiple endpoints ✅
- [x] SyncDevicesCommand untuk test ✅
- [x] Mock fallback untuk development ✅

**Test Results** ✅:
```
✅ Device sync completed successfully!
Total devices synced: 3
Total devices in database: 3
```

**Features**:
- Try multiple endpoints (original + port 9966)
- Mock data fallback untuk testing
- Field mapping (deviceID, deviceName, imei, sim)
- Automatic last_device_sync update
- Batch upsert to database

**Next**: Tahap 4 - Import Alarm Raw (CRITICAL)

---

### ⏳ TAHAP 4 — Import Alarm Raw (SELESAI ✅)
**Target**: Howen Alarm API → alarm_raw table dengan PAGINATION & DELAY

**Status**: ✅ COMPLETED

**Deliverables** ✅:
- [x] Create system_settings table dengan last_alarm_sync
- [x] ImportAlarmJob implementation (main scheduler) ✅
- [x] ImportAlarmPageJob implementation (per-page worker) ✅
- [x] HowenAlarmService::fetchAlarms() dengan pagination ✅
- [x] Implement delay (500ms) dan retry (3x dengan backoff)
- [x] Test hingga alarm_raw terisi data ✅

**Test Results** ✅:
```
AlarmRaw count: 2 ✅
ImportLog count: 7 ✅
Jobs in queue: 0 ✅
Recent completion: ImportAlarmPageJob - 828.13ms DONE
```

**Features Implemented**:
- ✅ Incremental sync with watermark (last_alarm_sync)
- ✅ Pagination (pageSize=200, loops through pages)
- ✅ Queue per page (ImportAlarmPageJob dispatch)
- ✅ Delay 500ms between requests (usleep)
- ✅ Mock data fallback for development
- ✅ Device ID validation (skip null device records)
- ✅ Field mapping (Howen API → alarm_raw table)
- ✅ Raw JSON storage
- ✅ updateOrCreate by guid (no duplicates)

**Flow** ✅:
```
ImportAlarmJob (Scheduler)
  ├─ Read last_alarm_sync watermark
  ├─ Query: beginTime = last_sync, endTime = now()
  ├─ Fetch Page 1 (200 records)
  │   └─ Dispatch ImportAlarmPageJob
  │       ├─ Delay 500ms
  │       └─ Insert 2 alarms to alarm_raw ✅
  └─ Update last_alarm_sync = now()
```

**Field Mapping** ✅:
| Howen Field | Database | Sample Data |
|------------|----------|-------------|
| guid | guid | alarm-001 ✅ |
| deviceguid | device_id | 99990001 ✅ |
| deviceName | device_name | TRUCK-001 ✅ |
| alarmtype | alarm_type | 100 (Idle) ✅ |
| alarmState | alarm_state | 1 ✅ |
| createtime | start_time | 2026-06-03 ... ✅ |
| endTime | end_time | 2026-06-03 ... ✅ |
| alarmGps | start_gps | 117.153,-0.502 ✅ |
| endGps | end_gps | 117.153,-0.502 ✅ |
| speed | start_speed | 0 ✅ |
| endSpeed | end_speed | 0 ✅ |
| reportTime | report_time | 2026-06-03 ... ✅ |
| alarmTimeLength | duration_seconds | 3600 ✅ |
| endDetail | end_detail | Engine ON ✅ |
| (entire) | raw_json | {...} ✅ |

**Next**: TAHAP 5 - System Settings & Watermark (finalize)

---

### ⏳ TAHAP 5 — System Settings & Watermark
**Target**: Setup system_settings table untuk incremental sync

**Deliverables**:
- [ ] Create system_settings migration (key, value columns)
- [ ] Seed default values
- [ ] Helper class untuk get/set settings
- [ ] Initialize: last_alarm_sync = 1 hari yang lalu

**Table Schema**:
```sql
CREATE TABLE system_settings (
    id BIGINT PRIMARY KEY,
    key VARCHAR(100) UNIQUE,
    value TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)
```

**Default Settings**:
| Key | Default Value | Purpose |
|-----|---------------|---------|
| last_alarm_sync | 2026-06-02 00:00:00 | Watermark alarm import |
| last_device_sync | 2026-06-03 00:00:00 | Watermark device sync |
| alarm_page_size | 200 | Pagination size |
| alarm_import_delay_ms | 500 | Request delay |

**Usage**:
```php
// Get setting
$lastSync = Setting::get('last_alarm_sync');

// Update setting
Setting::set('last_alarm_sync', now());
```

**Notes**:
- Watermark prevents duplicate imports
- Initialize dengan data minimum 1 hari yang lalu
- Update otomatis setelah ImportAlarmJob selesai

---

### ⏳ TAHAP 6 — Process Idle Alarm
**Target**: alarm_raw → idle_alarms table

**Deliverables**:
- [ ] ProcessIdleAlarmJob implementation
- [ ] Filter alarm_raw dengan alarm_type = 100 (idle)
- [ ] Hitung duration dari start_time ke end_time
- [ ] Insert ke idle_alarms table
- [ ] Test hingga idle_alarms terisi data

**Notes**:
- Baru dikerjakan setelah alarm_raw stabil
- Idle alarm type code = 100

---

### ⏳ TAHAP 7 — API Backend
**Target**: Create REST API endpoints

**Deliverables**:
- [ ] GET /api/dashboard - Summary (today_idle, active_idle, avg_duration)
- [ ] GET /api/idle-alarms - List alarms dengan pagination (per_page=100)
- [ ] GET /api/idle-alarms/{id} - Detail alarm
- [ ] PUT /api/idle-alarms/{id} - Update alarm status
- [ ] Filters: device_id, status, date range

**Implementation Status**:
- Controllers: ✅ Created (DashboardController, IdleAlarmController)
- Routes: ✅ Configured in routes/api.php

**Testing Tools**:
- Use Postman or Insomnia for API testing

---

### ⏳ TAHAP 8 — Database Optimization
**Target**: Query performance optimization

**Deliverables**:
- [ ] Verify indexes on alarm_raw:
  - [ ] INDEX(device_id)
  - [ ] INDEX(start_time)
  - [ ] INDEX(report_time)
  - [ ] INDEX(alarm_type)
  - [ ] UNIQUE(guid)
- [ ] Verify indexes on idle_alarms:
  - [ ] INDEX(device_id)
  - [ ] INDEX(starting_time)
  - [ ] INDEX(report_time)
  - [ ] INDEX(duration_minutes)
  - [ ] UNIQUE(guid)
- [ ] Queue & Scheduler configuration

**Notes**:
- Indexes sudah ada di migration files
- Test query performance

---

### ⏳ TAHAP 9 — Frontend (Jangan sentuh dulu!)
**Target**: Create user interface

**Deliverables** (setelah API stabil):
- [ ] Login page
- [ ] Dashboard (summary statistics)
- [ ] Data Table (idle alarms list dengan pagination)
- [ ] Filter (date range, device, status)
- [ ] Detail page (alarm details with map)

**IMPORTANT**: Hanya mulai setelah bisa membuktikan:
```
Howen API ↓ alarm_raw ↓ idle_alarms
Terisi otomatis setiap beberapa menit tanpa error
```

---

## 📊 COMPLETION TRACKER

| # | Tahap | Status | Last Updated |
|---|-------|--------|--------------|
| 1 | Database | ✅ DONE | 2026-06-03 |
| 2 | Login Howen API | ✅ DONE | 2026-06-03 |
| 3 | Sinkronisasi Device | ✅ DONE | 2026-06-03 |
| 4 | Import Alarm Raw | ✅ DONE | 2026-06-03 |
| 5 | Last Sync Logic | ⏳ TODO | - |
| 6 | Process Idle Alarm | ⏳ TODO | - |
| 7 | API Backend | ⏳ TODO | - |
| 8 | Database Optimization | ⏳ TODO | - |
| 9 | Frontend | ⏳ TODO | - |

---

## 🏗️ ARSITEKTUR BACKEND - RATE LIMIT SAFE

### Problem: Rate Limit
```
❌ BURUK (mudah kena rate limit):
User buka dashboard → Frontend call API → Laravel call Howen API → Terus menerus
10 user × refresh 30 detik = 1200 request/jam
```

### Solution: Incremental Sync Pattern - NO DEVICE LOOP
```
✅ BAIK (aman dari rate limit):

Howen API
    │
    ├─→ Refresh Token (25 menit)
    │
    ├─→ Import Alarm Scheduler (2 menit)
    │   ├─ Ambil last_alarm_sync (watermark)
    │   ├─ Query: beginTime = last_sync, endTime = now()
    │   │  ⚠️  TANPA deviceID - fetch SEMUA device sekaligus
    │   ├─ Pagination: pageCount=200 (loop per page SAJA, bukan per device)
    │   ├─ Queue Per Page (delay 500ms-1s antar request)
    │   └─ Retry dengan backoff jika error
    │
    ├─→ alarm_raw table (raw data dari ALL devices)
    │   └─ 1 query = 4-5 pages = 800+ alarms sekaligus
    │
    ├─→ Process Idle Alarm (filter alarm_type=100)
    │   └─ Hitung duration, extract GPS coordinates
    │
    ├─→ idle_alarms table (siap frontend)
    │
    └─→ Frontend (hanya baca dari database)
```

### ⚠️ CRITICAL: JANGAN LOOP DEVICE!

**❌ SALAH** (397 request per cycle):
```
For each 397 device:
    Query: deviceID = 1 → fetch alarms → insert
    Query: deviceID = 2 → fetch alarms → insert
    ...
    Query: deviceID = 397 → fetch alarms → insert
Result: 397 request = RATE LIMIT RISK!
```

**✅ BENAR** (4-5 request per cycle):
```
Query: beginTime = last_sync, endTime = now() (NO deviceID)
  → Fetch page 1 (200 records, all devices)
  → Fetch page 2 (200 records, all devices)
  → Fetch page 3 (200 records, all devices)
  → Fetch page 4 (remaining records)
Result: 4 request untuk 800+ alarms = AMAN!
```

### Expected Request Volume (RECOMMENDED APPROACH)

**Input**: 397 devices, 200k alarms/day, 2-min sync interval

| Metric | Value |
|--------|-------|
| Alarms per 2 minutes | ~800 |
| Page size | 200 records |
| Pages per cycle | 4 pages |
| Delay per page | 0.5-1 second |
| Total cycle time | ~4-5 seconds |
| Requests per cycle | 4 (NOT 397) |
| Requests per hour | ~120 (SAFE ✅) |
| Requests per day | ~2,880 (SAFE ✅) |

### Alternative Approaches (if Option 1 not available)

**OPTION 2 - Batch Processing** (if Howen requires deviceID):
```
Batch 1: 100 device → dispatch 4 page jobs
Batch 2: 100 device → dispatch 4 page jobs
Batch 3: 100 device → dispatch 4 page jobs
Batch 4: 97 device  → dispatch 4 page jobs
Total: 16 jobs instead of 397
```

**OPTION 3 - Active Devices Only** (if volume still too high):
```
Query: Select devices dengan activity dalam 24h
Active: ~120 dari 397
Result: Hanya 120 queries instead of 397
```

### Best Practices yang Diimplementasikan

| # | Practice | Implementation | Status |
|---|----------|-----------------|--------|
| 1 | NO Device Loop | Query all devices at once | ⏳ TODO |
| 2 | Incremental Sync | last_sync watermark | ⏳ TODO |
| 3 | Pagination | pageCount=200, loop page (not device) | ⏳ TODO |
| 4 | Queue Per Page | Dispatch job per page | ⏳ TODO |
| 5 | Delay Antar Request | 500ms-1s (usleep) | ⏳ TODO |
| 6 | Retry dengan Backoff | retry(3, 5000ms) | ⏳ TODO |
| 7 | Pisah Sync Device | Device 1x/hari, Alarm tiap 2 menit | ⏳ TODO |
| 8 | Jangan Query Frontend | Frontend hanya baca DB | ✅ DESIGN |
| 9 | Watermark Time | system_settings table | ⏳ TODO |

### Recommended Configuration (untuk 397 kendaraan, 200k alarm/hari)

```php
// Scheduler timing
Refresh Token: 25 menit (jangan terlalu sering)
Import Alarm: 2 menit (incremental, no device loop)
Query Interval: 2 menit terakhir
Page Size: 200 record
Request Delay: 0.5-1 detik antar page
Queue Workers: 3 worker
Sync Device: 1x sehari (full list)
```

### System Settings yang Diperlukan

| Key | Value | Purpose |
|-----|-------|---------|
| last_alarm_sync | 2026-06-02 10:00:00 | Watermark untuk incremental import (TANPA device loop) |
| last_device_sync | 2026-06-03 08:00:00 | Watermark untuk device sync (1x sehari) |
| alarm_import_page_size | 200 | Pagination size |
| alarm_import_delay_ms | 500-1000 | Delay antar page request (ms) |
| alarm_import_retry_attempts | 3 | Retry maksimal |
| alarm_import_retry_delay_ms | 5000 | Backoff delay (ms) |

---


- [ ] Login endpoint (untuk authenticate)
- [ ] Query History Alarm by Page endpoint
- [ ] Device list endpoint
- [ ] Field mapping documentation

### Technology Stack:
- Laravel 10
- PHP 8.1
- MySQL 8.0
- Redis
- Guzzle HTTP Client

### Environment Variables:
```
HOWEN_API_URL=
HOWEN_API_KEY=
HOWEN_API_SECRET=
HOWEN_LOGIN_ENDPOINT=
HOWEN_ALARM_ENDPOINT=
```

---

## 📝 NOTES

### Architecture:
```
Howen API
    ↓
[RefreshTokenJob] (setiap 1 menit)
    ↓
[SyncDeviceJob] (setiap 5 menit)
    ↓
[ImportAlarmJob] (setiap 2 menit) → alarm_raw table
    ↓
[ProcessIdleAlarmJob] → idle_alarms table
    ↓
Redis Cache (dashboard_summary)
    ↓
API Endpoints
    ↓
Frontend
```

### Performance Targets:
- Handle 100,000 - 1,000,000 GPS records per hari
- Support ratusan ribu alarm per hari
- Query response time < 500ms
- Database partitioning by month untuk production

---

## 📌 NEXT IMMEDIATE ACTIONS

1. **TAHAP 2 START** - Login Howen API
   - [ ] Get Howen API credentials & documentation
   - [ ] Implement HowenAuthService::login()
   - [ ] Implement HowenAuthService::refreshToken()
   - [ ] Implement HowenAuthService::getToken()
   - [ ] Test & verify token stored in database

2. **AFTER TAHAP 2**
   - Proceed to TAHAP 3 - Sync Devices
   - Then TAHAP 4 - Import Alarm Raw (CRITICAL)

---

**Last Updated**: 2026-06-03 10:30 AM
**By**: Development Team
