# VSS Project - Idle Monitor System

Project VSS yang sedang dikembangkan - Sistem monitoring idle alarm berbasis Laravel.

## Project Folders

### idle-monitor/
Laravel application untuk Idle Monitor system dengan fitur:
- REST API untuk dashboard dan alarm management
- Queue processing dengan Redis
- Database MySQL dengan migrations
- Service layer untuk Howen API integration
- Support untuk 100k-1M GPS records per hari

## Setup

### Backend (Laravel)
```bash
cd idle-monitor
composer install
php artisan migrate
php artisan queue:work  # Run di terminal terpisah untuk queue processing
php artisan serve       # Development server
```

### Requirements
- PHP 8.1+
- MySQL 8.0
- Redis
- Node.js (untuk assets)

### Key Technologies
- **Framework**: Laravel 10
- **ORM**: Eloquent
- **Queue**: Redis Queue
- **Cache**: Redis
- **API**: REST + Sanctum

## Database
- Database: `vss`
- Tables: devices, idle_alarms, alarm_raw, api_tokens, import_logs

## Endpoints
- Dashboard: `GET /api/dashboard`
- Alarms: `GET/POST/PUT/DELETE /api/alarms`

## Queue Jobs
- ImportAlarmJob - Import dari Howen API
- ProcessIdleAlarmJob - Process idle alarms
- RefreshTokenJob - Token refresh
- CleanupOldDataJob - Cleanup data

---

*Last updated: June 3, 2026*
