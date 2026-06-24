# Automatic Fix for Radmin VPN Access

**Status**: Client getting "site can't be reached" error
**Solution**: Run automated fix script
**Time needed**: 2 minutes

---

## 🚀 STEP 1: Download Fix Script

The fix script files have been created:

- `fix_apache_radmin_vpn.ps1` (PowerShell script)
- `FIX_RADMIN_VPN.bat` (Batch wrapper)

Both are in: `g:\project\vss\idle-monitor\`

---

## 🎯 STEP 2: Run Fix Script

### Method A: Using Batch File (EASIEST)

1. **Navigate to project folder**:
   ```
   g:\project\vss\idle-monitor\
   ```

2. **Right-click on**: `FIX_RADMIN_VPN.bat`

3. **Select**: "Run as Administrator"

4. **Wait for script to complete** (30 seconds max)

5. **You should see**:
   ```
   ✓ Fix Complete!
   ```

6. **Press any key to close**

---

### Method B: Using PowerShell (MANUAL)

1. **Press**: WIN + X
2. **Select**: Windows PowerShell (Admin)
3. **Type**:
   ```powershell
   Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope Process
   ```
4. **Press**: Enter
5. **Type**:
   ```powershell
   cd g:\project\vss\idle-monitor
   .\fix_apache_radmin_vpn.ps1
   ```
6. **Press**: Enter
7. **Wait** for script to complete

---

## ✅ STEP 3: What Script Does

The script automatically:

1. **Verifies Apache config file** exists
2. **Checks current Listen directive**
3. **Fixes httpd.conf** if needed:
   - Changes from: `Listen 127.0.0.1:80`
   - Changes to: `Listen 0.0.0.0:80`
4. **Creates backup** of original file
5. **Adds Windows Firewall rule** for Apache
6. **Reports status** at the end

---

## 🔧 STEP 4: Restart Laragon

After script completes:

1. **Close Laragon** (click X button)
2. **Wait** 5 seconds
3. **Re-open Laragon** (click icon in taskbar or start menu)
4. **Wait** for Apache and MySQL to start
   - Should see: "Apache: started"
   - Should see: "MySQL: started"

---

## 🧪 STEP 5: Test

After Laragon restarts:

### Test 1: Local Access
```
http://localhost:8000
```
✅ Should show Idle Monitor dashboard

### Test 2: VPN Access
```
http://26.29.218.176:8000
```
✅ Should show Idle Monitor dashboard (if VPN connected)

### Test 3: Client Access
Have client connect to Radmin VPN and visit:
```
http://26.29.218.176:8000
```
✅ Should work now!

---

## 📋 Troubleshooting

### Script won't run
- Make sure running as Administrator
- Check file is in correct location
- Try Method B (PowerShell) instead

### Error: "File not found"
- Verify Laragon is installed in `C:\laragon`
- Check file path in script
- Manually verify Apache config path

### Still can't access after restart
- Check Apache is actually running (Laragon window)
- Check no errors in Laragon
- Try: `netstat -ano | find ":80"` in CMD
- Check Windows Firewall in Control Panel

### Firewall rule won't create
- Run script as Administrator
- Or manually add rule:
  ```
  netsh advfirewall firewall add rule name="Apache" dir=in action=allow program="C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\bin\httpd.exe" enable=yes
  ```

---

## ✨ Expected Output

When you run the script, you should see:

```
======================================
Laragon Apache Radmin VPN Fix Script
======================================

[STEP 1] Checking Apache config file...
✓ File found: C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\conf\httpd.conf

[STEP 2] Checking current Listen directive...
✗ Found incorrect: Listen 127.0.0.1:80

[STEP 3] Fixing httpd.conf...
✓ Backup created: C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\conf\httpd.conf.backup.20260609-143022
✓ httpd.conf fixed: Changed to Listen 0.0.0.0:80

[STEP 4] Adding Windows Firewall rule...
✓ Firewall rule created successfully

======================================
✓ Fix Complete!
======================================

Next steps:
1. Restart Laragon (Stop → Start)
2. Test: http://localhost:8000
3. Test: http://26.29.218.176:8000
```

---

## 📊 Summary

| Step | Action | Status |
|------|--------|--------|
| 1 | Run FIX_RADMIN_VPN.bat as Admin | ✅ Automatic |
| 2 | Wait for completion | ✅ 30 seconds |
| 3 | Restart Laragon | ✅ Manual |
| 4 | Test localhost:8000 | ✅ Should work |
| 5 | Test 26.29.218.176:8000 | ✅ Should work |
| 6 | Client test VPN access | ✅ Should work |

---

## 🎯 Result

After this fix:

- ✅ Apache listens on all network interfaces
- ✅ Local access: `http://localhost:8000`
- ✅ VPN access: `http://26.29.218.176:8000`
- ✅ Firewall allows Apache inbound
- ✅ Client can access via Radmin VPN

---

## 📝 Backup Info

If something goes wrong, backup file created:

```
C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\conf\httpd.conf.backup.YYYYMMDD-HHMMSS
```

You can restore from backup if needed.

---

**Ready?** 

1. Run `FIX_RADMIN_VPN.bat` as Administrator
2. Restart Laragon
3. Test the URLs
4. Should be working now! ✅

