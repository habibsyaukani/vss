# 🚀 CARA MENJALANKAN IDLE MONITOR SYSTEM

## ⚡ QUICK START (Cara Tercepat)

### **Jalankan Semua Komponen Sekaligus**

1. Buka File Explorer
2. Masuk ke folder: `g:\project\vss\idle-monitor`
3. **Double-click** file: `START_ALL.bat`
4. Akan muncul 3 command windows:
   - **Window 1**: Laravel Server
   - **Window 2**: Queue Worker
   - **Window 3**: Realtime Data Pull
5. Tunggu sampai semua siap (sekitar 10 detik)
6. Buka browser: **http://127.0.0.1:8000**

---

## 📋 CARA MANUAL (Jalankan Satu Per Satu)

### **1. Start Laravel Server**
```
Double-click: start_server.bat
```
- Server akan berjalan di: http://127.0.0.1:8000
- Window ini harus tetap terbuka

### **2. Start Queue Worker**
```
Double-click: start_queue.bat
```
- Memproses background jobs (import alarms, process idle, dll)
- Window ini harus tetap terbuka

### **3. Start Realtime Data Pull**
```
Double-click: start_realtime.bat
```
- Menarik data dari Howen API setiap 3 menit
- Window ini harus tetap terbuka

---

## 🛑 CARA STOP/MATIKAN

### **Stop Semua Komponen**
- Tutup semua command window yang terbuka
- Atau tekan **Ctrl+C** di setiap window

### **Stop Satu Komponen**
- Tutup command window yang ingin di-stop
- Atau tekan **Ctrl+C** di window tersebut

---

## 🔍 VERIFIKASI SISTEM BERJALAN

### **Cek Server Running**
1. Buka browser
2. Akses: http://127.0.0.1:8000
3. Jika muncul halaman login = **✅ Server OK**

### **Cek System Status**
1. Login ke admin panel
2. Buka menu: **System Settings & Status**
3. Lihat status:
   - **API Status**: Harus "Connected" (hijau)
   - **Last Alarm Sync**: Harus < 5 menit yang lalu
   - **Queue Worker**: Harus "Running"
   - **Realtime Pull**: Harus "Running"

---

## 🏠 URL APLIKASI

### **Homepage/Login**
```
http://127.0.0.1:8000
```

### **Admin Panel**
```
http://127.0.0.1:8000/admin/dashboard
```

### **Idle Alarms**
```
http://127.0.0.1:8000/admin/idle-alarms
```

### **System Settings**
```
http://127.0.0.1:8000/admin/system-setting
```

### **System Control**
```
http://127.0.0.1:8000/admin/system-control
```

---

## ⚙️ KOMPONEN SISTEM

### **1. Laravel Server**
- **Fungsi**: Web server untuk aplikasi
- **Port**: 8000
- **Status**: Harus always running

### **2. Queue Worker**
- **Fungsi**: Memproses background jobs
  - Import alarms dari Howen API
  - Process idle alarms (filter & validasi)
  - Sync devices
- **Status**: Harus always running

### **3. Realtime Data Pull**
- **Fungsi**: Menarik data dari Howen API
  - Pull data setiap 3 menit
  - Date range: 48 jam terakhir
  - Auto dispatch ke queue untuk processing
- **Status**: Harus always running

---

## 🔧 TROUBLESHOOTING

### **Problem: Browser tidak bisa akses (Connection Refused)**

**Solusi**:
1. Cek apakah Laravel Server running
2. Buka Task Manager → cari "php.exe"
3. Harus ada minimal 3-4 process PHP
4. Jika tidak ada, jalankan ulang `START_ALL.bat`

### **Problem: Halaman Loading Terus**

**Solusi**:
1. Tutup semua command window
2. Buka Task Manager → Kill semua process "php.exe"
3. Jalankan ulang `START_ALL.bat`
4. Tunggu 10 detik, lalu akses browser

### **Problem: Data Tidak Bertambah**

**Solusi**:
1. Cek System Settings → API Status
2. Jika "Disconnected":
   - Cek Howen API credentials di `.env`
   - Restart Realtime Pull (tutup window, buka lagi)
3. Jika "Connected" tapi data tidak bertambah:
   - Normal, tunggu sampai ada idle alarm baru dari field
   - Data hanya bertambah kalau ada kendaraan idle

### **Problem: Queue Worker Error**

**Solusi**:
1. Tutup Queue Worker window
2. Jalankan ulang: `start_queue.bat`
3. Cek error di window

---

## 📊 MONITORING

### **1. Check Laravel Server Log**
```
Window: Idle Monitor - Laravel Server
```
Lihat output untuk request yang masuk

### **2. Check Queue Worker Log**
```
Window: Idle Monitor - Queue Worker
```
Lihat job yang diproses (ImportAlarmPageJob, ProcessIdleAlarmJob)

### **3. Check Realtime Pull Log**
```
Window: Idle Monitor - Realtime Pull
```
Lihat:
- Iteration #X
- Fetched X records
- Type 32 (Idle) records: X
- Valid idle alarms processed: X

---

## 🎯 TIPS

1. **Jangan tutup command windows** saat sistem sedang digunakan
2. **Simpan shortcut** `START_ALL.bat` di desktop untuk akses cepat
3. **Monitor secara berkala** via System Settings page
4. **Backup data** secara rutin dari database

---

## 📞 NEED HELP?

Jika ada masalah:
1. Screenshot error message
2. Cek Laravel log: `storage/logs/laravel.log`
3. Cek Import Logs di Admin Panel

---

**Last Updated**: June 10, 2026  
**Status**: ✅ PRODUCTION READY  
**Version**: 1.0

