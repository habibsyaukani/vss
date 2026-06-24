# Force Browser Reload - Cache Clear Guide

**Date:** June 11, 2026  
**Feature:** Device Search Filter with Tree Display  
**Issue:** Browser cache preventing new JavaScript from loading

---

## 🔥 LANGKAH CLEAR CACHE (PILIH SALAH SATU)

### ✅ OPTION 1: Batch File (RECOMMENDED - PALING MUDAH)

1. **Double-click** file: `CLEAR_CACHE.bat`
2. Tunggu sampai selesai (akan muncul "SUCCESS" 4x)
3. **TUTUP SEMUA TAB** browser untuk `localhost:8000`
4. **Buka tab BARU** di browser
5. **Hard refresh**: `Ctrl + Shift + R` atau `Ctrl + F5`
6. **Test search**: ketik "806" atau "1098"

**Expected Result:**
```
Search "1098" →
  ✅ ALL GPE (397|190)       ← Visible, expanded
     ✅ DT - GPE (225|125)   ← Visible, expanded
        ✅ GPE-DT-1098       ← Device visible dengan highlight biru
```

---

### ✅ OPTION 2: Manual Command Line

Buka Command Prompt di folder project, lalu jalankan:

```bash
cd g:\project\vss\idle-monitor

php artisan view:clear
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

Lalu **tutup semua tab browser** dan **hard refresh** (`Ctrl + Shift + R`)

---

### ✅ OPTION 3: Browser Cache Clear (MANUAL)

**Chrome / Edge:**
1. Tekan `Ctrl + Shift + Delete`
2. Pilih **"Cached images and files"**
3. Time range: **"Last hour"** atau **"All time"**
4. Klik **"Clear data"**
5. **Tutup semua tab** `localhost:8000`
6. **Buka tab baru** dan hard refresh (`Ctrl + Shift + R`)

**Firefox:**
1. Tekan `Ctrl + Shift + Delete`
2. Pilih **"Cache"**
3. Time range: **"Last hour"**
4. Klik **"Clear Now"**
5. **Tutup semua tab** dan **hard refresh**

---

### ✅ OPTION 4: Incognito/Private Mode (TESTING ONLY)

1. **Tutup semua window browser**
2. **Buka Incognito/Private window**: `Ctrl + Shift + N` (Chrome) atau `Ctrl + Shift + P` (Firefox)
3. Navigate ke `http://localhost:8000/idle-alarm`
4. **Test search**: ketik "806" atau "1098"

**Jika berhasil di Incognito** → berarti memang cache issue, lakukan OPTION 1 atau 2

---

## 🔍 VERIFICATION - Cara Memastikan Berhasil

### Test 1: Device Search "806"

**Input:** Ketik "806" di search box

**Expected Result:**
```
✅ ALL GPE (397|X)
   ✅ BUS - GPE (X|X)
      [🔵 GPE-B-806 🔵]     ← Highlighted biru
   ✅ DT - GPE (X|X)
      [🔵 GPE-DT-2806 🔵]   ← Highlighted biru
   ✅ HD - GPE (X|X)
      [🔵 GPE-HD-806 🔵]    ← Highlighted biru
      [🔵 GPE-HD-7806 🔵]   ← Highlighted biru
```

### Test 2: Device Search "1098"

**Input:** Ketik "1098" di search box

**Expected Result:**
```
✅ ALL GPE (397|X)
   ✅ DT - GPE (225|X)
      [🔵 GPE-DT-1098 🔵]   ← Highlighted biru
```

### Test 3: Clear Search

**Input:** Clear search box (klik X atau delete semua text)

**Expected Result:**
- Tree kembali ke filter Location/Series
- Semua groups visible (based on filter)
- Highlight biru hilang

---

## 🐛 TROUBLESHOOTING

### Issue 1: "php is not recognized as a command"

**Solution:**
- Install PHP atau tambahkan PHP ke system PATH
- Atau jalankan dengan full path, contoh:
  ```
  C:\xampp\php\php.exe artisan view:clear
  ```

### Issue 2: Batch file gagal

**Solution:**
- Run Command Prompt **as Administrator**
- Atau clear browser cache manual (OPTION 3)

### Issue 3: Tree masih tidak muncul setelah clear cache

**Solution:**
1. Buka **DevTools** (`F12`)
2. Go to **Console** tab
3. Ketik "806" di search
4. Lihat console log - harus ada:
   ```
   ✅ [SHOW GROUP] "BUS - GPE" with X devices
   ✅ [SHOW GROUP] "HD - GPE" with X devices
   ```
5. Jika tidak ada log ini → screenshot dan report

### Issue 4: Console shows errors

**Solution:**
- Screenshot console errors
- Check if jQuery is loaded
- Check if file `index.blade.php` has cache buster comment

---

## 📝 TECHNICAL NOTES

**Cache Buster Added:** `v2.0 - Device Search with Tree Display - 2026-06-11 17:30`

**Changes:**
- Device search now shows full tree hierarchy (ALL GPE → Sub-groups → Devices)
- Matching devices highlighted with light blue background (#dbeafe)
- Auto-expand groups with matching devices
- Coordinate with Location/Series filters
- 10ms setTimeout to ensure DOM updates

**Files Modified:**
- `resources/views/frontend/idle-alarm/index.blade.php`

**Risk Level:** 🟢 GREEN (JavaScript only, no backend changes)

---

## ✅ SUCCESS CRITERIA

Search feature berfungsi dengan baik jika:

1. ✅ Ketik "806" → 4 devices muncul dengan highlight
2. ✅ Tree hierarchy lengkap (ALL GPE → Groups → Devices)
3. ✅ Auto-expand groups yang punya matching devices
4. ✅ Clear search → restore ke filter Location/Series
5. ✅ Console log menampilkan "[SHOW GROUP]" messages

---

**Created:** June 11, 2026  
**Last Updated:** June 11, 2026 17:30  
**Version:** 2.0
