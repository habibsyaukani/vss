# Idle Monitor System

Sistem monitoring idle alarm dan max speed untuk fleet management GPE menggunakan VSS/Howen API.

## 🚀 Features

- ✅ **Real-time Monitoring** - Dashboard dengan statistik live
- ✅ **Idle Alarm Tracking** - Monitor durasi idle kendaraan
- ✅ **Max Speed Tracking** - Monitor kecepatan maksimum
- ✅ **GPS Tracking** - Tracking lokasi kendaraan real-time
- ✅ **Auto Data Sync** - Sinkronisasi otomatis dengan VSS API
- ✅ **Manual Data Pull** - Tarik data manual untuk rentang tanggal
- ✅ **Report & Export** - Export data ke Excel
- ✅ **Multi-Fleet Support** - Support multiple device groups
- ✅ **User Management** - Role-based access control
- ✅ **System Health** - Monitor kesehatan sistem

## 📋 Tech Stack

- **Backend**: Laravel 10.x (PHP 8.1+)
- **Frontend**: Bootstrap 5, Chart.js, DataTables
- **Database**: MySQL 8.0
- **Cache**: Redis
- **Queue**: Laravel Queue (Database driver)
- **Deployment**: Docker, Nginx, PHP-FPM

## 🔧 Server Requirements

- Ubuntu 20.04+ / Debian 11+
- PHP 8.1+ with extensions (mysql, redis, xml, curl, mbstring, zip, bcmath, gd)
- MySQL 8.0 or Docker
- Redis (optional, recommended for production)
- Composer 2.x
- Node.js 18+ & NPM (for assets compilation)

## 📦 Quick Start (Local Development)

### 1. Clone Repository
```bash
git clone https://github.com/habibsyaukani/vss.git
cd vss/idle-monitor
```

### 2. Install Dependencies
```bash
composer install
npm install && npm run build
```

### 3. Configure Environment
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vss
DB_USERNAME=root
DB_PASSWORD=

HOWEN_API_URL=https://vss.ptdigital.co.id/vss/
HOWEN_USERNAME=your_username
HOWEN_PASSWORD=your_password
```

### 4. Setup Database
```bash
php artisan migrate
php artisan db:seed
```

### 5. Start Development Server
```bash
# Terminal 1: Web server
php artisan serve

# Terminal 2: Queue worker
php artisan queue:work

# Terminal 3: Scheduler (optional)
php artisan schedule:work
```

Access: `http://127.0.0.1:8000`

Default Login:
- **Email**: admin@example.com
- **Password**: password

## 🚀 Production Deployment

### Automatic Deployment to Server 10.2.2.18

1. **Update GitHub** (if needed):
```bash
git add .
git commit -m "Deploy to production"
git push origin main
```

2. **Run Deployment Script**:
```bash
chmod +x deploy-to-server.sh
./deploy-to-server.sh
```

3. **Access Application**:
```
http://10.2.2.18
```

### Manual Deployment

See detailed guide in: [DEPLOYMENT_GUIDE.txt](DEPLOYMENT_GUIDE.txt)

## 📖 Documentation

- **[DEPLOYMENT_GUIDE.txt](DEPLOYMENT_GUIDE.txt)** - Complete deployment guide
- **[QUICK_DEPLOY_STEPS.txt](QUICK_DEPLOY_STEPS.txt)** - Quick deployment reference
- **[DEPLOY_CHECKLIST.txt](DEPLOY_CHECKLIST.txt)** - Deployment checklist
- **[DEVELOPMENT_PROGRESS.md](DEVELOPMENT_PROGRESS.md)** - Development history & features

## 🔑 Default Credentials

### Application
- **Admin Email**: admin@example.com
- **Admin Password**: password

### Database (Production Docker)
- **Host**: 127.0.0.1
- **Port**: 3306
- **Database**: vss
- **Username**: idle_user
- **Password**: IdleUser@2026!

### VSS/Howen API
- **URL**: https://vss.ptdigital.co.id/vss/
- **Username**: dash_gpe_gam
- **Password**: Gpe@939393!

⚠️ **IMPORTANT**: Change default passwords in production!

## 🛠️ Common Commands

### Application
```bash
# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run migrations
php artisan migrate
php artisan migrate:rollback

# Queue operations
php artisan queue:work
php artisan queue:restart
php artisan queue:failed

# Data pull commands
php artisan howen:pull-alarms-realtime
php artisan howen:pull-alarms-date-range --from=2026-06-01 --to=2026-06-30
php artisan vss:pull-gps-tracks --date=2026-06-29
```

### System (Production Server)
```bash
# Service management
systemctl status nginx
systemctl status php8.1-fpm
systemctl status idle-monitor-queue
systemctl restart idle-monitor-queue

# Docker operations
docker ps
docker logs idle-monitor-mysql
docker logs idle-monitor-redis
docker-compose restart

# View logs
tail -f storage/logs/laravel.log
journalctl -u idle-monitor-queue -f
tail -f /var/log/nginx/error.log
```

## 📊 Features Overview

### Frontend Dashboard
- Real-time statistics (Idle Alarms, Max Speed)
- Distribution charts by fleet
- 7-day trend comparison
- Date range filtering
- Export to Excel

### Admin Panel
- User management
- Device management
- Manual data pull
- GPS Track pull
- Import logs monitoring
- System health monitoring
- System settings

### Data Synchronization
- **Automatic**: Scheduler runs every 5 minutes
- **Manual**: Web-based data pull interface
- **Real-time**: Pull last 2 hours data on-demand
- **Date Range**: Pull historical data for specific dates

## 🔐 Security Features

- CSRF protection
- XSS prevention
- SQL injection protection
- Role-based access control
- Session management
- Password hashing (bcrypt)
- API authentication
- Rate limiting

## 🧪 Testing

```bash
# Run tests
php artisan test

# Run specific test
php artisan test --filter=UserTest
```

## 📈 Performance Optimization

- Database indexing
- Query optimization
- Cache implementation (Redis)
- Queue processing (background jobs)
- Asset optimization (minification)
- Lazy loading
- Chunk processing for large datasets

## 🐛 Troubleshooting

### Issue: Database connection error
```bash
# Check MySQL is running
docker ps
docker logs idle-monitor-mysql

# Verify credentials in .env
cat .env | grep DB_
```

### Issue: Queue not processing
```bash
# Check queue worker
systemctl status idle-monitor-queue

# Restart queue worker
systemctl restart idle-monitor-queue

# Check failed jobs
php artisan queue:failed
```

### Issue: Website not accessible
```bash
# Check Nginx
systemctl status nginx

# Check PHP-FPM
systemctl status php8.1-fpm

# Check application logs
tail -100 storage/logs/laravel.log
```

## 📞 Support

For issues and questions:
- Check documentation files
- Review application logs
- Contact system administrator

## 📄 License

Proprietary - All rights reserved

## 👥 Team

Developed for GPE Fleet Management

---

**Last Updated**: June 2026
**Version**: 1.0.0
**Status**: Production Ready ✅
