# Manual Steps - GPS Tracks Setup

**Karena PHP tidak terdeteksi di system PATH, gunakan salah satu cara berikut:**

---

## 🎯 CARA 1: Double-Click Batch File (TERMUDAH)

1. Buka folder: `g:\project\vss\idle-monitor`
2. **Double-click** file: `RUN_MIGRATION.bat`
3. Lihat output - harus ada message "MIGRATION SUCCESS!"

✅ **Done!** Tables sudah dibuat.

---

## 🎯 CARA 2: Manual Command Prompt

1. Buka **Command Prompt**
2. Jalankan command berikut:

```cmd
cd g:\project\vss\idle-monitor

php artisan migrate
```

**Jika PHP tidak ditemukan**, cari PHP path Anda:
- XAMPP: `C:\xampp\php\php.exe`
- Laragon: `C:\laragon\bin\php\php8.x\php.exe`
- WAMP: `C:\wamp64\bin\php\phpX.X.X\php.exe`

Lalu gunakan full path:
```cmd
C:\xampp\php\php.exe artisan migrate
```

---

## 🎯 CARA 3: Gunakan Laragon/XAMPP Terminal

**Jika menggunakan Laragon:**
1. Klik kanan icon Laragon di system tray
2. Pilih **"Terminal"**
3. Jalankan:
```bash
cd g:\project\vss\idle-monitor
php artisan migrate
```

**Jika menggunakan XAMPP:**
1. Buka **XAMPP Control Panel**
2. Klik **"Shell"** button
3. Jalankan:
```bash
cd /g/project/vss/idle-monitor
php artisan migrate
```

---

## ✅ HASIL YANG DIHARAPKAN

Setelah migration berhasil, Anda akan lihat output seperti ini:

```
Migrating: 2026_06_11_000001_create_gps_tracks_raw_table
Migrated:  2026_06_11_000001_create_gps_tracks_raw_table (XX.XXms)

Migrating: 2026_06_11_000002_create_gps_tracks_table
Migrated:  2026_06_11_000002_create_gps_tracks_table (XX.XXms)
```

---

## 🔍 VERIFIKASI

Cek apakah tables sudah dibuat:

**Cara 1: phpMyAdmin**
1. Buka phpMyAdmin: `http://localhost/phpmyadmin`
2. Pilih database: `vss`
3. Cari tables:
   - `gps_tracks_raw`
   - `gps_tracks`

**Cara 2: SQL Query**
```sql
SHOW TABLES LIKE 'gps_tracks%';
```

✅ **Jika 2 tables muncul**, migration berhasil!

---

## 🚀 SETELAH MIGRATION BERHASIL

Lanjut ke **STEP 2** (optional) atau **STEP 3** (sync data):

### STEP 2: Test Preview API (Optional)

Buka browser, akses:
```
http://localhost:8000/api/gps-tracks/preview?device_id=73200940&begin_time=2026-06-11 00:00:00&end_time=2026-06-11 23:59:59
```

### STEP 3: Sync Data ke Database (PENTING)

**Menggunakan Postman:**
- Method: POST
- URL: `http://localhost:8000/api/gps-tracks/sync`
- Body (JSON):
```json
{
  "device_id": "73200940",
  "begin_time": "2026-06-11 00:00:00",
  "end_time": "2026-06-11 23:59:59"
}
```

**Menggunakan curl:**
```bash
curl -X POST http://localhost:8000/api/gps-tracks/sync -H "Content-Type: application/json" -d "{\"device_id\":\"73200940\",\"begin_time\":\"2026-06-11 00:00:00\",\"end_time\":\"2026-06-11 23:59:59\"}"
```

---

## ❓ TROUBLESHOOTING

**Masalah: "PHP is not recognized"**
- **Solusi**: Gunakan full path ke php.exe (lihat CARA 2 di atas)

**Masalah: "Database connection refused"**
- **Solusi**: 
  1. Start MySQL server (XAMPP/Laragon)
  2. Cek `.env` - pastikan DB_* config benar

**Masalah: "Nothing to migrate"**
- **Solusi**: Tables sudah dibuat sebelumnya, lanjut ke STEP 3

---

**Mulai dengan CARA 1 (Double-click RUN_MIGRATION.bat)!** 🚀

Setelah berhasil, lanjut ke STEP 3 untuk sync data.
