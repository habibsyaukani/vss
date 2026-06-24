# Admin Guide - Distribute Access to Clients

**Date**: June 9, 2026
**Status**: Ready for distribution
**Audience**: Administrators

---

## 🎯 Overview

Idle Monitor is now accessible via Radmin VPN. This guide helps you distribute access to clients.

---

## 📋 What You Have

### Server Setup ✅
- **IP**: 26.29.218.176 (Radmin VPN)
- **Port**: 8000 (HTTP)
- **Network**: vss_gpe (Radmin VPN network)
- **Status**: ✅ Running and accessible

### Client Documentation ✅
- `CLIENT_QUICK_START.txt` (Simple 5-minute guide)
- `CLIENT_ACCESS_GUIDE.md` (Comprehensive guide)
- `RADMIN_VPN_VISUAL_GUIDE.txt` (With diagrams)

---

## 🔑 Prerequisites for Clients

Before giving clients access, ensure:

1. **Radmin VPN Installed**
   - Client must have Radmin VPN application
   - Download from: https://www.radmin-vpn.com

2. **Network Access**
   - Client account added to `vss_gpe` network in Radmin VPN
   - Credentials provided to client

3. **Idle Monitor Account**
   - Username created in system
   - Password set (temporary or reset)
   - Role assigned (Admin/Fleet Manager)

---

## 📦 Distribution Steps

### Step 1: Prepare Client Materials

Copy these files to a shared folder or send via email:

**Minimal Package**:
- `CLIENT_QUICK_START.txt` (2 pages, easy to follow)

**Complete Package**:
- `CLIENT_QUICK_START.txt` (Quick start)
- `CLIENT_ACCESS_GUIDE.md` (Comprehensive)
- Optional: `RADMIN_VPN_VISUAL_GUIDE.txt` (With diagrams)

**Files Location**: `g:\project\vss\idle-monitor\`

---

### Step 2: Provide Credentials

Create credentials for each client:

**Radmin VPN Credentials**:
- Network name: `vss_gpe`
- Username: (their username)
- Password: (secure password)

**Idle Monitor Credentials**:
- Username: (usually same as Radmin VPN or email)
- Password: (temporary, they can change later)
- Role: `Fleet Manager` (typical) or `Admin` (if needed)

---

### Step 3: Send Information

Send to each client:

**Email Template**:
```
Subject: Idle Monitor Access via Radmin VPN

Dear [Client Name],

Your Idle Monitor access is ready! Follow these steps:

1. Install Radmin VPN (if not already)
   Download: https://www.radmin-vpn.com

2. Open Radmin VPN and connect to: vss_gpe
   Username: [username]
   Password: [password]

3. Open browser and visit: http://26.29.218.176:8000

4. Login to Idle Monitor
   Username: [username]
   Password: [password]

5. Start monitoring!

Documentation attached: CLIENT_QUICK_START.txt

If you have issues, please contact IT support.

Best regards,
[Your Name]
```

---

### Step 4: Verify Access

1. **Have client confirm**:
   - VPN connected successfully
   - Can access http://26.29.218.176:8000
   - Can login with provided credentials
   - Can see dashboard

2. **Test on your end**:
   - Verify client account is working
   - Check permissions are correct
   - Verify they can only see appropriate data

---

## 📊 Client Accounts Setup

### Creating Client Accounts in Idle Monitor

1. **Open Idle Monitor Admin Panel**:
   ```
   http://localhost:8000/admin (local only)
   ```

2. **Go to**: Users Management

3. **Create New User**:
   - Name: Client name
   - Email: client@company.com
   - Username: client_username
   - Password: temporary_password
   - Role: `Fleet Manager` (read-only, typical)

4. **Save**

5. **Share credentials** with client

---

### Role Permissions

**Admin** (Full Access):
- ✅ View all data
- ✅ Edit settings
- ✅ Manage users
- ✅ System control

**Fleet Manager** (Read-Only):
- ✅ View idle alarms
- ✅ View devices
- ✅ View dashboard
- ✅ Export data
- ❌ Cannot edit
- ❌ Cannot manage users
- ❌ Cannot change settings

---

## 🆘 Support Issues

### Common Client Issues

**"Site can't be reached"**
- Verify Radmin VPN is connected (shows "Online")
- Verify correct IP: 26.29.218.176
- Try different browser
- Clear browser cache

**"Login failed"**
- Check credentials are correct
- Verify account exists in system
- Check password (might need reset)

**"Page loads slowly"**
- Check internet connection
- VPN network might be congested
- Try later

**"Disconnected mid-session"**
- Reconnect to VPN
- Refresh browser page
- Check network stability

---

## 📈 Scaling to Many Clients

### Bulk Distribution

**If you have many clients**:

1. **Create distribution package**:
   - `CLIENT_QUICK_START.txt`
   - `CLIENT_ACCESS_GUIDE.md`
   - `credentials_list.xlsx` (with usernames/passwords)

2. **Use email automation** (optional):
   - Mail merge with client names
   - Send personalized emails with credentials

3. **Schedule training session** (optional):
   - Live demo of Idle Monitor
   - Q&A session
   - Hands-on practice

4. **Create support channel**:
   - Slack/Teams channel for questions
   - Email support address
   - Help desk ticket system

---

## 🔐 Security Checklist

Before distributing access:

- [ ] VPN network is secure
- [ ] Passwords are strong
- [ ] Role-based access configured correctly
- [ ] Admin accounts protected (strong passwords)
- [ ] Firewall rules are in place
- [ ] SSL/HTTPS considered for future
- [ ] User data is backed up
- [ ] Audit logging enabled
- [ ] Support plan in place
- [ ] NDA signed (if applicable)

---

## 📝 Client Onboarding Checklist

For each new client:

- [ ] Radmin VPN credentials provided
- [ ] Idle Monitor account created
- [ ] Documentation sent (Quick Start guide)
- [ ] Client confirms VPN connection works
- [ ] Client confirms Idle Monitor access works
- [ ] Client confirms login successful
- [ ] Client confirms dashboard visible
- [ ] Client trained on features (optional)
- [ ] Client knows how to get support
- [ ] Feedback collected

---

## 🎓 Training & Documentation

### For Clients

**Provide**:
- Quick Start guide (5 minutes to setup)
- Full Access guide (reference)
- Video tutorial (optional, record screen)
- FAQ document

**Topics to cover**:
- How to connect to VPN
- How to access Idle Monitor
- How to view dashboard
- How to filter data
- How to export CSV
- Troubleshooting steps
- Contact support

---

### For Your Team

**Document**:
- How to create accounts
- How to reset passwords
- How to manage permissions
- How to troubleshoot
- Emergency procedures

---

## 📞 Support Plan

### Support Levels

**Level 1: Client Self-Service**
- Provide comprehensive documentation
- FAQ document
- Video tutorials
- Troubleshooting guide

**Level 2: IT Support**
- Email support
- Response time: 24 hours
- Can reset passwords
- Can troubleshoot access

**Level 3: Admin Support**
- Phone support
- Response time: 2 hours
- Can recreate accounts
- Can debug system issues

---

## 📊 Monitoring Client Usage

### Useful Metrics

Track:
- Number of active clients
- Peak usage times
- Common errors
- Feature usage
- Performance metrics

**Use for**:
- Capacity planning
- Performance optimization
- Feature requests
- User feedback

---

## 🚀 Future Improvements

### Consider Implementing

**Phase 1 (Current)**:
- ✅ Basic VPN access
- ✅ Login authentication
- ✅ Read-only access

**Phase 2 (Soon)**:
- ⏳ HTTPS/SSL (secure connection)
- ⏳ API access (for integrations)
- ⏳ Mobile-optimized UI
- ⏳ Advanced filtering

**Phase 3 (Future)**:
- ⏳ Multi-factor authentication
- ⏳ Single Sign-On (SSO)
- ⏳ Advanced reporting
- ⏳ Data analytics

---

## ✅ Checklist - Ready to Distribute

Before letting clients access:

- [ ] Server is running (http://26.29.218.176:8000 works)
- [ ] Radmin VPN network is setup
- [ ] Client documentation prepared
- [ ] Test account created and verified working
- [ ] Support plan documented
- [ ] Credentials securely shared
- [ ] Client trained (or access to training material)
- [ ] Feedback mechanism in place
- [ ] Monitoring setup
- [ ] Emergency rollback plan documented

---

## 📝 Quick Reference

**Server Information to Share with Clients**:
```
Application: Idle Monitor System
Access URL: http://26.29.218.176:8000
Network: vss_gpe (Radmin VPN)
Credentials: Provided separately
Support: Contact IT Administrator
```

**Files to Send to Clients**:
1. CLIENT_QUICK_START.txt
2. CLIENT_ACCESS_GUIDE.md

**Files to Keep Internal**:
1. This guide (ADMIN_CLIENT_DISTRIBUTION_GUIDE.md)
2. Account management procedures
3. Support runbooks

---

## 🎉 You're Ready!

Everything is set up for client access. Follow this guide to smoothly distribute access.

**Questions?** See other documentation files or contact your infrastructure team.

---

**Status**: ✅ Ready for production client distribution
**Version**: 1.0
**Last Updated**: June 9, 2026

