# Idle Monitor - Laravel Application

## Overview
Sistem monitoring idle alarm berbasis Laravel untuk Howen system dengan queue processing dan Redis caching.

## Tech Stack
- **Framework**: Laravel 10
- **PHP**: 8.1
- **Database**: MySQL
- **Cache**: Redis
- **Queue**: Redis Queue
- **API Tools**: Laravel Sanctum

## Project Structure
```
app/
├── Jobs/
│   ├── ImportAlarmJob.php
│   ├── ProcessIdleAlarmJob.php
│   ├── RefreshTokenJob.php
│   └── CleanupOldDataJob.php
├── Services/
│   ├── HowenAuthService.php
│   ├── HowenAlarmService.php
│   └── HowenDeviceService.php
├── Models/
│   ├── Device.php
│   ├── IdleAlarm.php
│   ├── AlarmRaw.php
│   ├── ApiToken.php
│   └── ImportLog.php
└── Http/Controllers/Api/
    ├── DashboardController.php
    └── IdleAlarmController.php
```

## Setup Instructions

### 1. Install Dependencies
```bash
composer install
```

### 2. Environment Configuration
.env file sudah dikonfigurasi dengan:
- Database: `vss`
- Cache Driver: Redis
- Queue Connection: Redis

### 3. Run Migrations
```bash
php artisan migrate
```

### 4. Queue Worker (Development)
```bash
php artisan queue:work
```

### 5. Start Development Server
```bash
php artisan serve
```

## Database Schema

### idle_alarms
- id (primary key)
- device_id (foreign key)
- alarm_type
- status
- created_at
- updated_at

### devices
- id (primary key)
- howen_id
- name
- location
- created_at
- updated_at

### alarm_raw
- id (primary key)
- device_id
- raw_data (json)
- processed_at
- created_at

### api_tokens
- id (primary key)
- user_id
- token (unique)
- created_at
- updated_at

### import_logs
- id (primary key)
- record_count
- status
- created_at
- updated_at

## API Endpoints

### Dashboard
- `GET /api/dashboard` - Get dashboard data
- `GET /api/dashboard/statistics` - Get alarm statistics
- `GET /api/dashboard/recent-alarms` - Get recent alarms

### Alarms
- `GET /api/alarms` - List all alarms
- `POST /api/alarms` - Create new alarm
- `GET /api/alarms/{id}` - Get alarm detail
- `PUT /api/alarms/{id}` - Update alarm
- `DELETE /api/alarms/{id}` - Delete alarm

## Queue Jobs

1. **ImportAlarmJob** - Import alarm data dari Howen API
2. **ProcessIdleAlarmJob** - Process idle alarm dan update status
3. **RefreshTokenJob** - Refresh authentication token
4. **CleanupOldDataJob** - Cleanup old data dari database

## Development Tools
- Laravel Debugbar (untuk development)
- Laravel DataTables (untuk tabel interaktif)

## Notes
- Laravel Horizon tidak support di Windows, gunakan di production server Linux
- Redis harus running untuk queue processing
- Queue worker harus running untuk process background jobs

## Next Steps
1. Implement controller methods
2. Implement service classes
3. Implement migration schemas
4. Add unit tests
5. Deploy ke production server
