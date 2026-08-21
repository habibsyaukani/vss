# 🚀 BATCH DATA PULL - QUICK START GUIDE

**Implementasi**: Batch Auto-Split & Real-time Progress Tracking  
**Date**: July 16, 2026

---

## ⚡ QUICK DEPLOYMENT

### 1. Run Migration
```bash
cd g:\project\vss\idle-monitor
php artisan migrate
```

### 2. Start Queue Worker (WAJIB!)
```bash
php artisan queue:work --tries=2 --timeout=600
```

### 3. Test Feature
```
URL: http://127.0.0.1:8000/admin/data-pull

Steps:
1. Pilih tanggal (contoh: 10/07/2026)
2. Klik "Tarik Data Sekarang"
3. Lihat progress 8 batch
4. Tunggu ~8-10 menit
5. Verify data di database
```

---

## ✅ VERIFIKASI

### Check Table Created:
```sql
SHOW TABLES LIKE 'data_pull_batches';
SELECT * FROM data_pull_batches LIMIT 5;
```

### Check Queue Running:
```bash
# Windows
tasklist | findstr php

# Should see: php.exe (queue:work process)
```

### Check Data Masuk:
```sql
-- Check batch progress
SELECT session_id, batch_number, status, total_records 
FROM data_pull_batches 
ORDER BY created_at DESC;

-- Check idle alarms
SELECT COUNT(*) FROM idle_alarms 
WHERE DATE(starting_time) = '2026-07-10';
```

---

## 🔧 TROUBLESHOOTING

### Progress tidak update?
```bash
# Start queue worker
php artisan queue:work --tries=2 --timeout=600
```

### Batch failed?
```sql
-- Check error message
SELECT batch_number, error_message 
FROM data_pull_batches 
WHERE status = 'failed';
```

### 504 Timeout masih terjadi?
- Verify controller returns immediately (check code)
- Clear route cache: `php artisan route:clear`

---

## 📋 FILES CHANGED

### NEW FILES:
- `database/migrations/2026_07_16_100000_create_data_pull_batches_table.php`
- `app/Models/DataPullBatch.php`
- `app/Jobs/DataPullOrchestratorJob.php`
- `app/Jobs/DataPullBatchJob.php`

### MODIFIED FILES:
- `app/Http/Controllers/DataPullController.php`
- `routes/admin.php`
- `resources/views/admin/data-pull.blade.php`
- `public/js/data-pull.js`

---

## 🎯 WHAT CHANGED?

### BEFORE:
- User pilih range tanggal → Browser wait 10+ min → 504 timeout
- No progress tracking

### AFTER:
- User pilih 1 tanggal → Backend split jadi 8 batch otomatis
- Browser langsung dapat response (< 1 detik)
- Progress real-time dengan auto-refresh 3 detik
- Batch retry jika gagal

---

## 🛡️ ROLLBACK (If Needed)

```bash
php artisan migrate:rollback --step=1
git checkout HEAD -- app/Http/Controllers/DataPullController.php
git checkout HEAD -- resources/views/admin/data-pull.blade.php
git checkout HEAD -- public/js/data-pull.js
rm app/Jobs/DataPullOrchestratorJob.php
rm app/Jobs/DataPullBatchJob.php
rm app/Models/DataPullBatch.php
php artisan route:clear && php artisan config:clear
```

---

**Full Documentation**: See `BATCH_DATA_PULL_IMPLEMENTATION.md`
