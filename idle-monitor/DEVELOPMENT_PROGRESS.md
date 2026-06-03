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

### 🔄 TAHAP 2 — Login Howen API (SEDANG BERLANGSUNG)
**Target**: Login berhasil → Token tersimpan di api_tokens → Token bisa dipakai request berikutnya

**Deliverables**:
- [ ] HowenAuthService::login() - Login ke Howen API
- [ ] HowenAuthService::refreshToken() - Refresh token jika expired
- [ ] HowenAuthService::getToken() - Ambil token dari cache atau database
- [ ] Test login sampai token tersimpan di database

**Progress**:
- [ ] Cari Howen API documentation
- [ ] Implementasi login endpoint
- [ ] Setup token caching dengan Redis
- [ ] Test login & verify token stored

**Notes**:
- Token Howen expired setiap 30 menit jika tidak digunakan
- Simpan token di api_tokens table + Redis cache
- Setup error handling untuk retry

---

### ⏳ TAHAP 3 — Sinkronisasi Device
**Target**: Fetch devices dari Howen API → Simpan ke devices table

**Deliverables**:
- [ ] SyncDeviceJob implementation
- [ ] Fetch dari Howen API endpoint
- [ ] Insert/Update devices table (device_id, device_name, imei, sim_number)
- [ ] Manual test jalankan job

**Notes**:
- Pastikan tabel devices terisi sebelum lanjut ke tahap 4
- Gunakan upsert untuk update existing devices

---

### ⏳ TAHAP 4 — Import Alarm Raw (CRITICAL)
**Target**: Howen Alarm API → alarm_raw table

**Deliverables**:
- [ ] ImportAlarmJob implementation
- [ ] Flow: ambil token → ambil last_sync → request alarm dari Howen API → insert alarm_raw
- [ ] Don't process idle alarms yet, fokus raw data masuk 100%
- [ ] Test hingga alarm_raw terisi data

**Notes**:
- Jangan process idle dulu
- Focus: Pastikan data masuk 100% akurat
- Gunakan last_sync untuk pagination query

---

### ⏳ TAHAP 5 — Last Sync Management
**Target**: Prevent duplicate data import

**Deliverables**:
- [ ] Create system_settings table (key, value columns)
- [ ] Store last_alarm_sync timestamp
- [ ] Update last_sync setelah ImportAlarmJob selesai
- [ ] ImportAlarmJob: mulai query dari last_sync timestamp

**Flow**:
1. Import alarm jam 10:00
2. Simpan last_sync = 2026-06-03 10:00:00
3. Import berikutnya mulai dari 2026-06-03 10:00:00
4. Tidak ada duplicate data

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
| 2 | Login Howen API | 🔄 IN PROGRESS | 2026-06-03 |
| 3 | Sinkronisasi Device | ⏳ TODO | - |
| 4 | Import Alarm Raw | ⏳ TODO | - |
| 5 | Last Sync Logic | ⏳ TODO | - |
| 6 | Process Idle Alarm | ⏳ TODO | - |
| 7 | API Backend | ⏳ TODO | - |
| 8 | Database Optimization | ⏳ TODO | - |
| 9 | Frontend | ⏳ TODO | - |

---

## 🔑 REQUIREMENTS

### Howen API Endpoints Needed:
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
