# 🗑️ Automatic Cleanup - Penjelasan Lengkap

## 📝 Apa itu Retention Period?

**Retention Period = Berapa lama data disimpan**

### Contoh: Retention 30 Hari

```
Logika: ROLLING/BERJALAN
- Simpan: 30 hari TERAKHIR
- Hapus: Data yang LEBIH TUA dari 30 hari
```

### Ilustrasi Per Hari:

#### **3 Juli 2026 (Hari Ini)**
```
Retention: 30 hari
Cutoff Date: 3 Juli - 30 hari = 3 Juni 2026

✅ SIMPAN: Data dari 3 Juni 2026 s/d 3 Juli 2026 (30 hari)
❌ HAPUS:  Data sebelum 3 Juni 2026
```

#### **4 Juli 2026 (Besok)**
```
Retention: 30 hari (sama)
Cutoff Date: 4 Juli - 30 hari = 4 Juni 2026

✅ SIMPAN: Data dari 4 Juni 2026 s/d 4 Juli 2026 (30 hari)
❌ HAPUS:  Data sebelum 4 Juni 2026
```

#### **3 Agustus 2026 (Bulan Depan)**
```
Retention: 30 hari (sama)
Cutoff Date: 3 Agustus - 30 hari = 4 Juli 2026

✅ SIMPAN: Data dari 4 Juli 2026 s/d 3 Agustus 2026 (30 hari)
❌ HAPUS:  Data sebelum 4 Juli 2026 (termasuk semua data Juni!)
```

### Kesimpulan

**LOGIKA ROLLING:**
- Cutoff date bergerak setiap hari
- Selalu simpan X hari terakhir
- Hapus yang lebih tua dari X hari
- Otomatis menghapus data bulan lalu jika sudah lewat retention period

---

## 🎯 Skenario Penggunaan

### **Scenario 1: Retention 30 Hari**
```
Kebutuhan: Data 1 bulan terakhir saja
Schedule: Monthly (1st of month at 02:00 AM)

Timeline:
- 1 Juli: Hapus data sebelum 1 Juni (simpan Juni-Juli)
- 1 Agustus: Hapus data sebelum 1 Juli (simpan Juli-Agustus)
- 1 September: Hapus data sebelum 1 Agustus (simpan Agustus-September)
```

### **Scenario 2: Retention 90 Hari**
```
Kebutuhan: Data 3 bulan terakhir
Schedule: Monthly (1st of month at 02:00 AM)

Timeline:
- 1 Juli: Hapus data sebelum 1 April (simpan Apr-Mei-Jun-Jul)
- 1 Agustus: Hapus data sebelum 1 Mei (simpan Mei-Jun-Jul-Agu)
- 1 September: Hapus data sebelum 1 Juni (simpan Jun-Jul-Agu-Sep)
```

### **Scenario 3: Retention 365 Hari**
```
Kebutuhan: Data 1 tahun terakhir
Schedule: Monthly (1st of month at 02:00 AM)

Timeline:
- 1 Juli 2026: Hapus data sebelum 1 Juli 2025 (simpan 1 tahun)
- 1 Agustus 2026: Hapus data sebelum 1 Agustus 2025 (simpan 1 tahun)
```

---

## ⚙️ Schedule Options

### **1. Daily (Harian)**
```
Run: Setiap hari jam 02:00 AM
Use case: Data cepat berubah, perlu cleanup sering
Pros: Database selalu bersih
Cons: Overhead lebih tinggi
```

### **2. Weekly (Mingguan)**
```
Run: Setiap Minggu jam 02:00 AM
Use case: Keseimbangan antara performa dan cleanup
Pros: Tidak terlalu sering, database tetap terjaga
Cons: Data lama bisa menumpuk seminggu
```

### **3. Monthly (Bulanan)** ⭐ **RECOMMENDED**
```
Run: Setiap tanggal 1 jam 02:00 AM
Use case: Most common use case
Pros: Paling efisien, overhead minimal
Cons: Data lama bisa menumpuk sebulan
```

---

## 🔒 Safety Features

### **1. Data Validation**
```php
// Hanya hapus data yang SUDAH diproses ke tabel final
if ($guidExistsInIdleAlarms) {
    // Aman untuk dihapus dari alarm_raw
    delete();
}
```

### **2. Percentage Check**
```php
// Hanya hapus jika >95% sudah diproses
if ($processedPercentage > 95) {
    // Aman untuk dihapus dari gps_tracks_raw
    delete();
}
```

### **3. Logging**
```
Semua aktivitas cleanup dicatat ke:
- storage/logs/cleanup.log
- System log activity
```

---

## 📊 Preview/Statistics

### **Table: Cleanup Preview**

| Table | Total Records | Old Records | Percentage |
|-------|---------------|-------------|------------|
| alarm_raw | 393,733 | 0 | 0.0% |
| gps_tracks_raw | 5,436,328 (estimated) | 0 | 0.0% |

**Note:** gps_raw menggunakan **estimasi** untuk performa (62 detik → 2ms)

---

## ✅ Cara Menggunakan

### **1. Enable Cleanup (Manual)**
```
1. Buka: /admin/system-control
2. Bagian: Automatic Cleanup Control
3. Status: DISABLED → Ubah ke "Enabled"
4. Set retention: 30 hari (atau sesuai kebutuhan)
5. Set schedule: Monthly (recommended)
6. Klik: "Save Settings"
```

### **2. Run Manual (Test)**
```
1. Pastikan settings sudah benar
2. Klik: "Run Cleanup Now"
3. Check Activity Log untuk progress
4. Verify di table statistics
```

### **3. Let It Run Automatically**
```
- Setelah enabled, cleanup akan jalan otomatis sesuai schedule
- Check "Last Run" untuk memastikan berjalan
- Monitor Activity Log untuk errors
```

---

## 🚫 Default Settings

**NEW (After Fix):**
```
cleanup_enabled: FALSE (DISABLED)
cleanup_retention_days: 30
cleanup_schedule: monthly

User MUST manually ENABLE from UI!
```

**Why Disabled by Default?**
- User should control when to start cleanup
- Prevent accidental data deletion
- Give time to understand the system
- User can configure first, then enable

---

## 🎯 Recommendations

### **For Most Users:**
```
✅ Retention: 30-60 hari
✅ Schedule: Monthly
✅ Enable: After understanding how it works
```

### **For Heavy Users:**
```
✅ Retention: 90-180 hari
✅ Schedule: Weekly atau Monthly
✅ Monitor: Check logs regularly
```

### **For Long-term Storage:**
```
✅ Retention: 180-365 hari
✅ Schedule: Monthly
✅ Backup: Consider database backup before cleanup
```

---

## ❓ FAQ

### **Q: Apa bedanya dengan backup?**
A: Cleanup = hapus data lama. Backup = simpan copy data ke tempat lain. Cleanup TIDAK membuat backup!

### **Q: Bisa di-undo?**
A: TIDAK! Data yang sudah dihapus tidak bisa dikembalikan. Pastikan retention period sudah benar!

### **Q: Aman tidak?**
A: YA, aman. Hanya hapus data yang sudah diproses ke tabel final. Ada validasi double-check.

### **Q: Perlu manual run dulu?**
A: Tidak wajib, tapi disarankan untuk test apakah cleanup berjalan dengan benar.

### **Q: Kalau error gimana?**
A: Cleanup akan skip dan log error. Data tetap aman, tidak akan terhapus.

---

**Last Updated**: 2026-07-03  
**Version**: 2.0 (With Detailed Explanation)
