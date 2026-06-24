# 🧪 TEST PLAN: Duration Filter Sidebar Fix

**Date**: June 10, 2026  
**Fix**: BUGFIX 8 - Duration Filter Should Only Affect Table Display

---

## ✅ PRE-TEST CHECKLIST

- [ ] Laravel server running (`START_ALL.bat` or `start_server.bat`)
- [ ] Browser opened to: http://127.0.0.1:8000
- [ ] Navigate to: Frontend → Idle Alarm page
- [ ] Sidebar visible with device tree
- [ ] Duration filter dropdown visible at top

---

## 🧪 TEST CASES

### **Test 1: Duration Filter Does Not Hide Devices**

**Steps:**
1. Open Idle Alarm page
2. Observe sidebar - should show all device groups (BUS, DT, FT, HD, etc.)
3. Count visible devices in sidebar (note: e.g., 397 devices)
4. Select duration filter: "> 30 Menit (30:00+)"
5. Wait for table to reload

**Expected Result:**
- ✅ Table shows only records with duration > 30 minutes
- ✅ Sidebar STILL shows all 397 devices (no devices hidden)
- ✅ Group counters unchanged in sidebar
- ✅ All device checkboxes still visible

**Actual Result:** ___________________

**Status:** [ ] PASS  [ ] FAIL

---

### **Test 2: Duration + Device Selection**

**Steps:**
1. In sidebar, check ONLY "DT - GPE" group
2. Verify table shows only DT-GPE devices
3. Change duration filter to "5-15 Menit (5:00-14:59)"
4. Wait for table to reload

**Expected Result:**
- ✅ Table shows only DT-GPE devices with 5-15 min duration
- ✅ Sidebar UNCHANGED - all devices still visible
- ✅ "DT - GPE" group STILL CHECKED
- ✅ Other groups remain unchecked
- ✅ No devices hidden based on duration data

**Actual Result:** ___________________

**Status:** [ ] PASS  [ ] FAIL

---

### **Test 3: Location Filter Still Controls Sidebar**

**Steps:**
1. Clear all device selections
2. Select Location filter: "Jambi" (or any available location)
3. Observe sidebar - should hide non-Jambi devices
4. Now change duration filter to "< 5 Menit (0:00-4:59)"

**Expected Result:**
- ✅ After location filter: Sidebar shows only Jambi devices (location controls sidebar) ✅
- ✅ After duration filter: Table shows Jambi devices with < 5 min duration
- ✅ Sidebar UNCHANGED from step 3 (still showing only Jambi devices)
- ✅ Duration filter does NOT further hide/show devices

**Actual Result:** ___________________

**Status:** [ ] PASS  [ ] FAIL

---

### **Test 4: Clear Duration Filter**

**Steps:**
1. Select duration filter: "> 30 Menit (30:00+)"
2. Verify table shows filtered data
3. Change duration filter back to "Semua Durasi"
4. Wait for table to reload

**Expected Result:**
- ✅ Table shows all durations again
- ✅ Sidebar UNCHANGED throughout
- ✅ No flicker or device hiding/showing in sidebar
- ✅ Checkbox selections preserved (if any were checked)

**Actual Result:** ___________________

**Status:** [ ] PASS  [ ] FAIL

---

### **Test 5: Multiple Filter Combinations**

**Steps:**
1. Check "BUS - GPE" devices only
2. Select Location: "Kutai Barat"
3. Select Duration: "15-30 Menit (15:00-29:59)"
4. Select Date: Today only

**Expected Result:**
- ✅ Table shows: BUS-GPE devices in Kutai Barat with 15-30 min duration today
- ✅ Sidebar shows: Only Kutai Barat devices visible (location filter)
- ✅ "BUS - GPE" checkboxes STILL CHECKED in sidebar
- ✅ Duration and date filters DO NOT affect sidebar visibility

**Actual Result:** ___________________

**Status:** [ ] PASS  [ ] FAIL

---

### **Test 6: Checkbox Preservation (TASK 7 Regression)**

**Steps:**
1. Manually check specific devices: GPE-DT-1015, GPE-DT-1128, GPE-DT-1102
2. Change duration filter 3 times:
   - "< 5 Menit"
   - "5-15 Menit"
   - "> 30 Menit"
3. Observe checkboxes after each change

**Expected Result:**
- ✅ After each duration change: GPE-DT-1015, GPE-DT-1128, GPE-DT-1102 STILL CHECKED
- ✅ No other checkboxes automatically checked/unchecked
- ✅ User selections fully preserved
- ✅ This confirms TASK 7 fix still works

**Actual Result:** ___________________

**Status:** [ ] PASS  [ ] FAIL

---

## 🐛 REGRESSION TESTS

### **Regression 1: Location Filter Still Works**

**Steps:**
1. Select Location: "Samarinda"
2. Verify sidebar hides non-Samarinda devices

**Expected Result:**
- ✅ Sidebar shows ONLY Samarinda devices
- ✅ Location filter sidebar behavior NOT affected by duration fix

**Status:** [ ] PASS  [ ] FAIL

---

### **Regression 2: Series Filter Still Works**

**Steps:**
1. Select Series: "VOLVO"
2. Verify sidebar shows only VOLVO devices

**Expected Result:**
- ✅ Sidebar shows ONLY VOLVO (FMX) devices
- ✅ Series filter sidebar behavior NOT affected by duration fix

**Status:** [ ] PASS  [ ] FAIL

---

## 📊 BROWSER CONSOLE CHECKS

Open browser Developer Tools (F12) → Console tab

**Expected Console Logs:**

When changing duration filter:
```
⏱️ Duration filter CHANGED: gt30
✅ Table reloaded - record count: 87
```

**Should NOT see:**
```
🔄 Syncing sidebar visibility - matching device IDs: ...  ❌ (removed)
✅ Sidebar visibility synced with duration filter - visible devices: ...  ❌ (removed)
```

**Console Check Status:** [ ] PASS  [ ] FAIL

---

## 📝 OVERALL TEST SUMMARY

| Test Case | Status | Notes |
|-----------|--------|-------|
| Test 1: Duration doesn't hide devices | [ ] | |
| Test 2: Duration + Device Selection | [ ] | |
| Test 3: Location filter still controls sidebar | [ ] | |
| Test 4: Clear duration filter | [ ] | |
| Test 5: Multiple filter combinations | [ ] | |
| Test 6: Checkbox preservation | [ ] | |
| Regression 1: Location filter | [ ] | |
| Regression 2: Series filter | [ ] | |
| Console logs correct | [ ] | |

**Overall Status:** [ ] ALL PASSED  [ ] SOME FAILED

---

## 🔍 IF TESTS FAIL

### **Symptoms:**
- Duration filter still hides devices in sidebar
- Checkboxes get reset when changing duration

### **Troubleshooting:**
1. Clear browser cache (Ctrl+F5)
2. Verify file was saved: `resources/views/frontend/idle-alarm/index.blade.php`
3. Check `drawCallback` function - should be simplified (only ~10 lines)
4. Restart Laravel server if blade cache issue:
   ```bash
   php artisan view:clear
   php artisan cache:clear
   ```

---

## ✅ SIGN-OFF

**Tested By:** ___________________  
**Date:** ___________________  
**Result:** [ ] APPROVED  [ ] NEEDS FIX  

**Notes:**
_____________________________________
_____________________________________
_____________________________________
