# Hapus 5 Device Extra dari Database

## Status Saat Ini
- **Database**: 402 devices
- **Master Data**: 397 devices  
- **Yang Perlu Dihapus**: 5 devices

## Langkah-Langkah

### STEP 1: Cek Device Extra

Jalankan script untuk cek 5 device yang akan dihapus:

```bash
cd G:\project\vss\idle-monitor
php check_extra_devices.php
```

Script akan menampilkan:
- Total device di database
- List 5 device yang TIDAK ada di master
- Apakah device tersebut punya alarm history

### STEP 2: Review Hasil

**PENTING**: Review list device extra sebelum hapus!
- Pastikan device yang akan dihapus memang BUKAN device operasional
- Cek apakah ada alarm history (warning ⚠️)

### STEP 3: Hapus Device Extra

Jika sudah yakin, jalankan script delete:

```bash
php delete_extra_devices.php
```

Script akan:
1. Tampilkan list device yang akan dihapus
2. Minta konfirmasi (ketik "YES")
3. Hapus 5 device extra
4. Verifikasi total = 397

### STEP 4: Verifikasi

```bash
php artisan tinker
```

Kemudian:
```php
Device::count(); // Harus 397
```

---

## Rollback (Jika Ada Masalah)

Jika salah hapus, restore dengan import ulang master data:
```bash
php sync_master_devices.php
```

---

## Files Created

1. **check_extra_devices.php** - Cek 5 device extra (SAFE, tidak hapus)
2. **delete_extra_devices.php** - Hapus 5 device extra (DESTRUCTIVE)
3. **check_extra.bat** - Windows batch untuk cek
4. **delete_extra.bat** - Windows batch untuk hapus

---

## Expected Result

**Before**:
```
Database: 402 devices
Master: 397 devices
Extra: 5 devices
```

**After**:
```
Database: 397 devices ✅
Master: 397 devices ✅
Match: PERFECT ✅
```

