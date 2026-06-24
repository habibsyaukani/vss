# Client Access Guide - Idle Monitor via Radmin VPN

**Date**: June 9, 2026
**Application**: Idle Monitor System
**Access Method**: Radmin VPN
**Server IP**: 26.29.218.176
**Port**: 8000

---

## 🎯 Quick Start (For Clients)

### Prerequisites
- Radmin VPN client installed
- Network access credentials
- Username and password for Idle Monitor

---

## 📖 Step-by-Step Instructions

### STEP 1: Install Radmin VPN (If Not Installed)

1. **Download Radmin VPN**:
   - Visit: https://www.radmin-vpn.com
   - Download the client for your OS (Windows, Mac, Linux)

2. **Install**:
   - Run installer
   - Follow on-screen instructions
   - Restart if prompted

3. **Verify**:
   - Should see Radmin VPN icon in system tray (taskbar)

---

### STEP 2: Connect to Network

1. **Open Radmin VPN** (click icon in taskbar or start from Applications)

2. **You should see**:
   ```
   ┌─ Radmin VPN ─────────────────┐
   │ System Network                │
   │ [List of networks]            │
   │ - vss_gpe                     │
   │ - other_networks              │
   └───────────────────────────────┘
   ```

3. **Find**: `vss_gpe`

4. **Click on**: `vss_gpe`

5. **Click**: **"Connect"** button

6. **Wait** for connection (should take 5-10 seconds)

7. **Status** should show: **"Online"** or **"Connected"**

---

### STEP 3: Open Web Browser

1. **Open your browser**:
   - Chrome
   - Firefox
   - Edge
   - Safari
   - Or any web browser

2. **In address bar, type**:
   ```
   http://26.29.218.176:8000
   ```

3. **Press Enter**

4. **Wait** for page to load (5-10 seconds on first load)

---

### STEP 4: Login to Idle Monitor

You should see the Idle Monitor login page:

```
┌─────────────────────────────────┐
│     IDLE MONITOR SYSTEM         │
│                                 │
│  Username: [____________]       │
│  Password: [____________]       │
│                                 │
│  [Login] [Forgot Password]      │
└─────────────────────────────────┘
```

**Enter your credentials**:
1. **Username**: Your username (provided by admin)
2. **Password**: Your password (provided by admin)
3. **Click**: **[Login]**

---

### STEP 5: Access Dashboard

After successful login, you should see:

```
┌─ Idle Monitor Dashboard ────────────────┐
│  📊 Monitoring durasi mesin menyala...  │
│                                        │
│  ✅ System Active                      │
│  Total Devices: 397                    │
│  Total Alarms Today: X                 │
│                                        │
│  [Dashboard] [Idle Alarms] [Devices]   │
└────────────────────────────────────────┘
```

**Congratulations!** ✅ You have successfully accessed Idle Monitor!

---

## 🔍 Features Available

Once logged in, you can:

### Dashboard
- View real-time statistics
- See total idle alarms
- View device status
- Monitor system health

### Idle Alarms
- View list of all idle events
- Filter by:
  - Date range
  - Device
  - Duration
  - Location
- Export data as CSV

### Devices
- View all devices
- Check device status (Active/Offline)
- See last sync time
- View device details

---

## 🆘 Troubleshooting

### Problem: "This site can't be reached"

**Solutions**:
1. **Verify VPN connection**:
   - Check Radmin VPN status shows "Online"
   - Try disconnecting and reconnecting

2. **Check network connection**:
   - Verify your internet is working
   - Try: http://google.com (should work)

3. **Verify correct IP**:
   - Make sure you typed: `26.29.218.176`
   - Not: `26.29.218.176:8080` or other port

4. **Try different port**:
   - Some networks block port 8000
   - Ask your admin for alternative port

5. **Wait a bit longer**:
   - First load can take 10-20 seconds
   - Be patient!

---

### Problem: Login credentials don't work

**Solutions**:
1. **Check Caps Lock**: 
   - Password is case-sensitive
   - Verify Caps Lock is OFF

2. **Verify username**:
   - Sometimes it's email instead of username
   - Ask your admin if unsure

3. **Reset password**:
   - Click "Forgot Password" on login page
   - Or ask your admin to reset

4. **Try later**:
   - Database might be restarting
   - Wait a few minutes and try again

---

### Problem: Page loads very slowly

**Solutions**:
1. **Check internet speed**:
   - VPN uses your internet connection
   - Slower internet = slower page loads
   - Try: https://speedtest.net

2. **Check VPN network**:
   - Too many users on VPN can slow it down
   - Try disconnecting and reconnecting

3. **Try different browser**:
   - Chrome usually fastest
   - Try: Chrome, Firefox, or Edge

4. **Clear browser cache**:
   - Press: Ctrl+Shift+Delete
   - Clear browsing data
   - Reload page

---

### Problem: Disconnected mid-session

**Solutions**:
1. **Reconnect to VPN**:
   - Radmin VPN window → Disconnect
   - Wait 5 seconds
   - Reconnect to vss_gpe
   - Reload browser page

2. **Refresh page**:
   - Press: Ctrl+R or F5
   - Or click browser Refresh button

3. **Check network stability**:
   - Your internet might be unstable
   - Restart your modem/router if needed

---

## 📞 Support Contact

If you have issues:

**IT Support**:
- Email: [admin email]
- Phone: [admin phone]
- Slack: [admin channel]

**Provide information**:
- Your username
- Error message (screenshot if possible)
- What you were doing when it happened
- When it happened

---

## 🔐 Security & Best Practices

### Password Security
- ✅ Never share your password
- ✅ Don't write it down
- ✅ Use strong password (if you set your own)
- ✅ Change password regularly

### Network Security
- ✅ Only connect to vss_gpe network
- ✅ Disconnect VPN when not in use
- ✅ Don't access on public WiFi (use VPN only)
- ✅ Keep your VPN software updated

### Data Handling
- ✅ Don't take screenshots of sensitive data
- ✅ Don't share login credentials
- ✅ Report security issues to admin
- ✅ Log out before closing browser (optional but good practice)

---

## ✅ Checklist - Before Contacting Support

Before asking for help, verify:

- [ ] Radmin VPN is installed
- [ ] Connected to vss_gpe network (shows "Online")
- [ ] Internet connection is working
- [ ] IP address is correct: 26.29.218.176
- [ ] Port number is correct: 8000
- [ ] Browser is modern (Chrome, Firefox, Edge, Safari)
- [ ] JavaScript is enabled in browser
- [ ] Tried different browser
- [ ] Tried clearing browser cache
- [ ] Tried refreshing page (F5 or Ctrl+R)
- [ ] Waited at least 30 seconds for page load

---

## 📱 Mobile Access (Optional)

**Note**: Not officially supported, but some users report it works:

1. Install Radmin VPN on mobile
2. Connect to vss_gpe
3. Open browser
4. Visit: http://26.29.218.176:8000

**May have display issues** on small screens.

---

## 🎓 Tips & Tricks

### Faster Access
- **Bookmark the page**: Press Ctrl+D in browser
- **Use direct IP**: Instead of typing full URL each time
- **Pin to taskbar**: Quick access to application

### Better Performance
- **Use wired connection** if possible (faster than WiFi)
- **Connect to VPN during off-peak hours** (usually midnight-6am)
- **Close other applications** to free up bandwidth

### Data Export
- Go to Idle Alarms page
- Click "Export All Excel" button
- Get CSV file with all data
- Open in Excel for analysis

---

## 🚀 Getting Started Checklist

- [ ] Install Radmin VPN
- [ ] Get network access from admin
- [ ] Connect to vss_gpe network
- [ ] Open browser
- [ ] Visit http://26.29.218.176:8000
- [ ] Login with credentials
- [ ] Explore dashboard
- [ ] Try filtering data
- [ ] Try exporting CSV
- [ ] Bookmark for easy access

---

## 📝 FAQ

**Q: What if I forget my password?**
A: Click "Forgot Password" on login page, or ask admin to reset.

**Q: Can I access without VPN?**
A: No, VPN connection is required for security.

**Q: Is my data secure?**
A: Yes, VPN encrypts all traffic. Your data is protected.

**Q: Can I access from multiple devices?**
A: Yes, but only one active session per user typically.

**Q: What time can I access?**
A: 24/7, anytime VPN is available.

**Q: Do I need to disconnect VPN when done?**
A: Optional, but recommended to save bandwidth.

**Q: Can I use my mobile phone?**
A: Yes, if Radmin VPN is installed. Display may be small though.

---

## 🎉 Enjoy!

You now have access to the Idle Monitor System!

**Questions?** Contact your administrator.

**Happy monitoring!** 📊

---

**Document Version**: 1.0
**Last Updated**: June 9, 2026
**Status**: ✅ Ready for Distribution

