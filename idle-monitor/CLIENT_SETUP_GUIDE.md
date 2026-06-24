# CLIENT ACCESS GUIDE - Idle Monitor via Radmin VPN

**For**: Client users yang ingin akses aplikasi Idle Monitor
**Requirement**: Radmin VPN installed
**Time**: 5 minutes

---

## 🎯 CLIENT SETUP STEPS

### STEP 1: Install Radmin VPN (If Not Installed)

1. **Download Radmin VPN**
   - Visit: https://www.radmin-vpn.com
   - Click: Download
   - Choose: Windows version

2. **Install**
   - Run installer
   - Follow wizard
   - Restart computer if prompted

3. **Verify installed**
   - Check taskbar (bottom right)
   - Should see Radmin VPN icon

---

### STEP 2: Add VPN Connection

1. **Open Radmin VPN**
   - Click icon in taskbar
   - Or search "Radmin VPN" in Start menu

2. **In Radmin VPN window, look for**:
   - Network list area
   - Button or field to "Add" or "Connect"

3. **Add new connection**:
   - Network name: `vss_gpe`
   - Server IP: `26.29.218.176` (or ask your IT admin)
   - Or: Your company's Radmin server IP

4. **Connect to VPN**
   - Click on network: `vss_gpe`
   - Click: "Connect" button
   - Wait for: "Online" status

---

### STEP 3: Verify VPN Connection

After connecting, check:

1. **Radmin VPN window shows**:
   ```
   Status: Online ✓
   Connected: Yes ✓
   ```

2. **In Windows**:
   - Open Control Panel → Network
   - Should show VPN adapter active

3. **Test connection**:
   - Open Command Prompt (WIN + R, type: cmd)
   - Type: `ping 26.29.218.176`
   - Should get response (not "unreachable")

---

### STEP 4: Access Idle Monitor Application

1. **Open Web Browser**
   - Chrome, Firefox, Edge, etc.

2. **Type URL**:
   ```
   http://26.29.218.176:8000
   ```

3. **Press**: Enter

4. **You should see**:
   ```
   Idle Monitor Login Page
   ```

---

### STEP 5: Login

1. **Username**: (ask your admin)
2. **Password**: (ask your admin)
3. **Click**: Login

4. **You should see**:
   ```
   Idle Monitor Dashboard
   ```

---

## ✅ ACCESS SUCCESSFUL

When logged in, you should see:

- **Dashboard** with statistics
- **Idle Alarm** list with data
- **Device** information
- **Filters** to search data

---

## 🔧 TROUBLESHOOTING

### Problem: Can't connect to Radmin VPN

**Solution**:
1. Check network name is correct: `vss_gpe`
2. Check server IP is correct: `26.29.218.176`
3. Check Internet connection is working
4. Try restart Radmin VPN app
5. Contact your IT admin

### Problem: Connected to VPN but "site can't be reached"

**Solution**:
1. Verify VPN shows "Online" status
2. Test: `ping 26.29.218.176` in Command Prompt
3. If ping fails, contact IT admin
4. If ping works, try clear browser cache:
   - Press: Ctrl+Shift+Delete
   - Clear all cache
   - Try URL again

### Problem: Login page appears but login fails

**Solution**:
1. Verify username and password
2. Check CAPS LOCK is off
3. Ask admin to reset password
4. Try different browser
5. Clear browser cookies/cache

### Problem: Slow connection

**Solution**:
1. This is normal if VPN is far away
2. Wait a bit longer for pages to load
3. Try disabling browser extensions
4. Refresh page (F5 or Ctrl+R)
5. Check your Internet speed

### Problem: "Connection refused" error

**Solution**:
1. Server might be down (offline)
2. Contact IT admin to check if server is running
3. Ask if application maintenance is happening
4. Try again in 5 minutes

---

## 📊 Quick Checklist

- [ ] Radmin VPN installed
- [ ] Connected to network `vss_gpe`
- [ ] VPN shows "Online" status
- [ ] Can ping `26.29.218.176`
- [ ] Browser URL: `http://26.29.218.176:8000`
- [ ] Login page loads
- [ ] Successfully logged in
- [ ] Dashboard visible

---

## 💡 TIPS

### Bookmark the URL
1. When on application page
2. Press: Ctrl+D (or click bookmark icon)
3. Next time just click bookmark instead of typing URL

### Save Login
1. When login prompt appears
2. Check: "Save password" (if browser offers)
3. Next time faster to login

### Auto-start VPN
1. Open Radmin VPN settings
2. Check: "Start with Windows"
3. Check: "Auto-connect last network"
4. Now VPN connects automatically on startup

---

## 📞 GETTING HELP

If you have problems:

1. **Take a screenshot** of error message
2. **Note down**:
   - What were you trying to do?
   - What error did you get?
   - When did it happen?
3. **Contact**: Your IT admin
4. **Provide**: Screenshots and notes

---

## 🎯 SUMMARY

To access Idle Monitor via Radmin VPN:

1. ✅ Install Radmin VPN
2. ✅ Connect to network `vss_gpe`
3. ✅ Open browser
4. ✅ Visit: `http://26.29.218.176:8000`
5. ✅ Login with credentials
6. ✅ Use application

---

**Questions?** Contact your IT administrator!

