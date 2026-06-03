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

### ⏳ TAHAP 3 — Sinkronisasi Device
**Target**: Fetch devices dari Howen API → Simpan ke devices table

**Deliverables**:
- [ ] Create system_settings table (key, value) - untuk watermark
- [ ] SyncDeviceJob implementation
- [ ] HowenDeviceService::fetchDevices() - call endpoint /vehicle/getDeviceList.action
- [ ] Batch insert/update ke devices table
- [ ] Manual test jalankan job

**Flow**:
```
Scheduler (1x per hari) 
  ↓
SyncDeviceJob
  ↓
HowenAuthService::getToken()
  ↓
HowenDeviceService::fetchDevices()
  ↓
POST /vss/vehicle/getDeviceList.action
  ↓
Parse response, map ke device_id, device_name, imei, sim_number
  ↓
Batch upsert ke devices table
  ↓
Update last_device_sync di system_settings
```

**Expected Fields dari API**:
```json
{
  "deviceId": "99990001",
  "deviceName": "TRUCK-01",
  "imei": "869459030007543",
  "simNumber": "62812345678"
}
```

**Mapping**:
| API Field | Database |
|-----------|----------|
| deviceId | device_id |
| deviceName | device_name |
| imei | imei |
| simNumber | sim_number |

**Notes**:
- Jalankan 1x per hari (bukan setiap 2 menit)
- Gunakan upsert untuk update existing
- Pastikan data lengkap sebelum lanjut tahap 4

---

### ⏳ TAHAP 4 — Import Alarm Raw (CRITICAL - INCREMENTAL SYNC)
**Target**: Howen Alarm API → alarm_raw table dengan PAGINATION & DELAY

**Deliverables**:
- [ ] Create system_settings table dengan last_alarm_sync
- [ ] ImportAlarmJob implementation (main scheduler)
- [ ] ImportAlarmPageJob implementation (per-page worker)
- [ ] HowenAlarmService::fetchAlarms() dengan pagination
- [ ] Implement delay (500ms) dan retry (3x dengan backoff)
- [ ] Test hingga alarm_raw terisi data

**Flow - INCREMENTAL SYNC PATTERN** ✅:
```
Scheduler (setiap 2 menit)
  ↓
ImportAlarmJob
  ├─ Read last_alarm_sync dari system_settings (watermark)
  ├─ beginTime = last_sync
  ├─ endTime = now()
  ├─ Loop pagination (pageNum 1, 2, 3, ...)
  │   └─ Dispatch ImportAlarmPageJob($page) per page
  │       ├─ Call: POST /vss/alarm/apiFindAllByTime.action
  │       ├─ Delay 500ms (usleep)
  │       ├─ Retry 3x jika error
  │       └─ Insert ke alarm_raw
  └─ Update last_alarm_sync = now()
```

**Request Format**:
```json
{
  "token": "GET_FROM_CACHE",
  "pageNum": 1,
  "pageCount": 200,
  "beginTime": "2026-06-02 10:00:00",
  "endTime": "2026-06-02 10:02:00",
  "alarmType": ""
}
```

**API Response Mapping ke alarm_raw**:
| Howen Field | Database | Type |
|------------|----------|------|
| guid | guid | varchar(100) UNIQUE |
| deviceguid | device_id | varchar(100) |
| deviceName | device_name | varchar(255) |
| alarmtype | alarm_type | int |
| alarmState | alarm_state | tinyint |
| createtime | start_time | datetime |
| endTime | end_time | datetime |
| alarmGps | start_gps | varchar(255) |
| endGps | end_gps | varchar(255) |
| speed | start_speed | decimal(10,2) |
| endSpeed | end_speed | decimal(10,2) |
| reportTime | report_time | datetime |
| alarmTimeLength | duration_seconds | int |
| endDetail | end_detail | text |
| (entire) | raw_json | json |

**Important Notes**:
- Hanya simpan raw data, jangan process idle dulu
- Gunakan pagination, loop sampai response kosong
- Implement delay 500ms antar request (aman dari rate limit)
- Implement retry dengan backoff (3x maksimal)
- Update last_alarm_sync setelah selesai
- Focus: Data masuk 100% akurat tanpa duplicate

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
| 3 | Sinkronisasi Device | 🔄 IN PROGRESS | - |
| 4 | Import Alarm Raw | ⏳ TODO | - |
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

### Solution: Incremental Sync Pattern
```
✅ BAIK (aman dari rate limit):

Howen API
    │
    ├─→ Refresh Token (25 menit)
    │
    ├─→ Import Alarm Scheduler (2 menit)
    │   ├─ Ambil last_alarm_sync (watermark)
    │   ├─ Query: beginTime = last_sync, endTime = now()
    │   ├─ Pagination: pageCount=200 (loop per page)
    │   ├─ Queue Per Page (delay 500ms antar request)
    │   └─ Retry dengan backoff jika error
    │
    ├─→ alarm_raw table
    │   └─ Simpan raw data dari Howen
    │
    ├─→ Process Idle Alarm (1 menit)
    │   └─ Filter alarm_type=100, hitung duration
    │
    ├─→ idle_alarms table (siap frontend)
    │
    └─→ Frontend (hanya baca dari database)
```

### Best Practices yang Diimplementasikan

| # | Practice | Implementation | Status |
|---|----------|-----------------|--------|
| 1 | Incremental Sync | last_sync watermark | ⏳ TODO |
| 2 | Pagination | pageCount=200, loop | ⏳ TODO |
| 3 | Queue Per Page | Dispatch job per page | ⏳ TODO |
| 4 | Delay Antar Request | 500ms (usleep) | ⏳ TODO |
| 5 | Retry dengan Backoff | retry(3, 5000ms) | ⏳ TODO |
| 6 | Pisah Sync Device | Device 1x/hari, Alarm tiap 2 menit | ⏳ TODO |
| 7 | Jangan Query Frontend | Frontend hanya baca DB | ✅ DESIGN |
| 8 | Watermark Time | system_settings table | ⏳ TODO |
| 9 | Recommend Settings | 300 devices, 200k alarm/hari | - |

### Recommended Configuration (untuk 300 kendaraan, 200k alarm/hari)

```php
// Scheduler timing
Refresh Token: 25 menit
Import Alarm: 2 menit  
Query Interval: 2 menit terakhir
Page Size: 200 record
Request Delay: 500ms
Queue Workers: 3 worker
Sync Device: 1 menit (full list)
```

### System Settings yang Diperlukan

| Key | Value | Purpose |
|-----|-------|---------|
| last_alarm_sync | 2026-06-02 10:00:00 | Watermark untuk incremental import |
| last_device_sync | 2026-06-03 08:00:00 | Watermark untuk device sync |
| alarm_import_page_size | 200 | Pagination size |
| alarm_import_delay_ms | 500 | Delay antar request (ms) |

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
