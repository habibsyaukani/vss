# Setup Radmin VPN Access untuk Idle Monitor

**Goal**: Agar client bisa akses aplikasi via Radmin VPN dari IP `26.29.218.176:8000`

---

## 📋 STEP-BY-STEP GUIDE

### STEP 1: Edit Apache httpd.conf

**File Location**: 
```
C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\conf\httpd.conf
```

**Cara buka**:
1. Press `WIN + R` (Run dialog)
2. Type: `notepad C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\conf\httpd.conf`
3. Press Enter

Atau:
1. Buka File Explorer
2. Navigate: `C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\conf\`
3. Right-click `httpd.conf`
4. "Open with" → Notepad

---

### STEP 2: Find Listen Directive

Setelah file terbuka, cari **"Listen"** (gunakan Ctrl+F):

```
Cari: Listen
```

Anda akan menemukan baris seperti ini (cari yang dengan port 80):

**BEFORE:**
```
Listen 127.0.0.1:80
```

atau

```
Listen localhost:80
```

---

### STEP 3: Change to Listen All Interfaces

**UBAH dari:**
```
Listen 127.0.0.1:80
```

**MENJADI:**
```
Listen 0.0.0.0:80
```

atau simple:
```
Listen 80
```

**Yang penting**: Hapus `127.0.0.1:` atau `localhost:` - biar bisa diakses dari network manapun.

---

### STEP 4: Save File

1. Press `Ctrl+S`
2. Atau Menu → File → Save
3. Close Notepad

---

### STEP 5: Restart Apache

Di Laragon window:

1. Click **"Stop"** button
2. Wait 3-5 seconds (Apache stop completely)
3. Click **"Start"** button or start services again

Screenshot:
```
┌─ Laragon ──────────────────┐
│                            │
│ Apache: started      80    │
│ MySQL: started      3306   │
│                            │
│ [Stop] [Web] [Database]   │
│         ↑ Click ini       │
│                            │
└────────────────────────────┘
```

---

### STEP 6: Update Laravel .env (If needed)

**File**: `g:\project\vss\idle-monitor\.env`

Cari line:
```
APP_URL=http://localhost
```

Ubah menjadi:
```
APP_URL=http://26.29.218.176
```

atau gunakan wildcard (more flexible):
```
APP_URL=http://localhost
```

(Biarkan localhost, dia akan auto-detect)

**Save** (Ctrl+S)

---

### STEP 7: Clear Laravel Cache

```bash
php artisan config:clear
php artisan cache:clear
```

---

## ✅ TESTING - Setelah Setup Selesai

### Test 1: Local Access (Harus tetap work)
```
http://localhost:8000/
http://127.0.0.1:8000/
```
✅ Should work

### Test 2: Network Access via VPN IP
```
http://26.29.218.176:8000/
```
✅ Should work (dari client yang connect ke Radmin VPN)

### Test 3: Find Your Computer IP
Cari IP komputer Anda di local network:
```
ipconfig
```

Cari **"IPv4 Address"** (biasanya `192.168.x.x`)

```
http://192.168.1.100:8000/
```
✅ Should work (dari client di local network, tanpa VPN)

---

## 🔧 Troubleshooting

### Problem: Apache won't start after change
**Solution**:
1. Open httpd.conf again
2. Check untuk typo
3. Make sure format benar: `Listen 0.0.0.0:80` atau `Listen 80`
4. Save, restart Apache

### Problem: Still can't access from network
**Solution**:
1. Check Windows Firewall
2. Buka Windows Defender Firewall
3. Allow Apache through firewall
4. Or: `netsh advfirewall firewall add rule name="Apache" dir=in action=allow program="C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\bin\httpd.exe" enable=yes`

### Problem: Localhost becomes slow
**Solution**:
- Normal, OS needs to resolve IP
- Use `127.0.0.1` instead of `localhost` untuk faster local access
- Or add to `C:\Windows\System32\drivers\etc\hosts`:
  ```
  127.0.0.1       localhost
  26.29.218.176   localhost
  192.168.1.100   localhost
  ```

### Problem: SSL/Certificate error
**Solution**:
- If using HTTPS, need SSL certificate
- For now, use HTTP (port 80)
- Or: Generate self-signed cert (advanced)

---

## 📝 Quick Checklist

- [ ] Found and opened httpd.conf
- [ ] Changed Listen from `127.0.0.1:80` to `0.0.0.0:80`
- [ ] Saved file
- [ ] Stopped Apache in Laragon
- [ ] Started Apache again
- [ ] Updated .env APP_URL (if needed)
- [ ] Ran `php artisan config:clear`
- [ ] Tested localhost:8000 ✅
- [ ] Tested 26.29.218.176:8000 from another device ✅

---

## 🎯 Summary

**Sekarang bisa diakses dari**:

| URL | Access From | Status |
|-----|-------------|--------|
| `http://localhost:8000` | Lokal desktop Anda | ✅ Work |
| `http://127.0.0.1:8000` | Lokal desktop Anda | ✅ Work |
| `http://192.168.1.x:8000` | Local network (same WiFi/LAN) | ✅ Work |
| `http://26.29.218.176:8000` | Via Radmin VPN | ✅ Work |

---

**Questions?** Let me know!

