# 🐛 BUGFIX: Duration Filter Should Only Affect Table Display, Not Sidebar

**Date**: June 10, 2026  
**File Modified**: `resources/views/frontend/idle-alarm/index.blade.php`  
**Issue**: Duration filter was hiding/showing devices in sidebar, should only filter table data

---

## 🔍 ROOT CAUSE ANALYSIS

### **Problem: drawCallback Logic Affecting Sidebar**

```javascript
// BEFORE (lines 1063-1143)
drawCallback: function(settings) {
    // ... code ...
    
    // Only adjust visibility if duration filter is active
    let durationFilter = $('#durationFilter').val();
    
    if (durationFilter) {
        // ❌ PROBLEM: Hiding devices that don't have data in selected duration range
        $('.device-checkbox').each(function() {
            let deviceId = $(this).val();
            let $treeChild = $(this).closest('.tree-child');
            let hasMatch = matchingSet.has(deviceId);
            
            if (hasMatch) {
                $treeChild.show().css('display', 'flex');
            } else {
                $treeChild.hide().css('display', 'none');  // ❌ Hiding devices
            }
        });
        
        // ... more hide/show logic for groups and counters
    }
}
```

**Impact**:
- When user selects duration filter (e.g., "> 30 minutes")
- Devices without data in that range are **hidden** from sidebar
- This confuses users - they can't see all devices anymore
- User expects: Duration filter = table data only, sidebar unchanged

---

## ✅ EXPECTED BEHAVIOR

### **Filter Responsibilities:**

| Filter Type | Affects Table | Affects Sidebar |
|-------------|---------------|-----------------|
| **Duration** | ✅ YES | ❌ NO |
| **Location** | ✅ YES | ✅ YES (hide/show matching devices) |
| **Series** | ✅ YES | ✅ YES (hide/show matching devices) |
| **Device Checkbox** | ✅ YES | ❌ NO (user selection) |
| **Date Range** | ✅ YES | ❌ NO |

### **Duration Filter Should:**
1. ✅ Filter table data based on idle duration (< 5min, 5-15min, etc.)
2. ✅ Show only matching records in table
3. ❌ **NOT** hide/show devices in sidebar
4. ❌ **NOT** update group counters based on duration
5. ✅ Preserve user checkbox selections (TASK 7)

---

## 🔧 FIX APPLIED

### **Simplified drawCallback - Removed Duration Filter Logic**

**Lines 1063-1075** - Removed entire duration filter sidebar sync:

```javascript
// BEFORE (83 lines of logic)
drawCallback: function(settings) {
    if(settings.json && settings.json.recordsTotal !== undefined) {
        $('#recordCount').text(settings.json.recordsTotal);
    }
    
    // Sync sidebar visibility based on matching device IDs from server
    if(settings.json && settings.json.matching_device_ids !== undefined) {
        let matchingIds = settings.json.matching_device_ids || [];
        let matchingSet = new Set(matchingIds);
        
        // Only adjust visibility if duration filter is active
        let durationFilter = $('#durationFilter').val();
        
        if (durationFilter) {
            // ... 70 lines of hide/show logic
        }
    }
}

// AFTER (Simple, clean)
drawCallback: function(settings) {
    if(settings.json && settings.json.recordsTotal !== undefined) {
        $('#recordCount').text(settings.json.recordsTotal);
    }
    
    // ✅ REMOVED: Duration filter sidebar sync logic
    // Duration filter should ONLY affect table data display
    // Sidebar device visibility is controlled by Location/Series filters only
    // User checkbox selections are always preserved (TASK 7 fix)
    
    console.log('✅ Table reloaded - record count:', settings.json.recordsTotal);
}
```

---

## 🎯 HOW IT WORKS NOW

### **User Flow:**

#### **Scenario 1: Duration Filter Only**
```
1. User sees all devices in sidebar (397 devices)
2. User checks "DT - GPE" group manually
3. User selects duration filter "> 30 minutes"
4. ✅ Table shows only DT-GPE devices with duration > 30 min
5. ✅ Sidebar still shows ALL devices (no hiding)
6. ✅ DT-GPE checkboxes remain checked
```

#### **Scenario 2: Duration + Device Selection**
```
1. User checks only "BUS - GPE" devices
2. User selects duration filter "5-15 minutes"
3. ✅ Table shows only BUS-GPE idle alarms with 5-15 min duration
4. ✅ Sidebar shows all devices (BUS still checked)
5. ✅ No devices hidden based on duration data availability
```

#### **Scenario 3: Location + Duration Filter**
```
1. User selects Location filter "Jambi"
2. ✅ Sidebar hides non-Jambi devices (Location filter controls sidebar)
3. User selects duration filter "> 10 minutes"
4. ✅ Table shows only Jambi devices with > 10 min duration
5. ✅ Sidebar unchanged (still showing only Jambi devices)
```

---

## 📊 FILTER BEHAVIOR SUMMARY

### **Duration Filter Now:**
- ✅ **Backend**: Filters query by `duration_seconds` range
- ✅ **Table**: Shows only matching duration records
- ✅ **Sidebar**: **UNCHANGED** - all devices visible
- ✅ **Checkboxes**: **PRESERVED** - user selections respected

### **Location/Series Filters:**
- ✅ **Backend**: Filters query by location/series
- ✅ **Table**: Shows only matching records
- ✅ **Sidebar**: **HIDE/SHOW** devices based on filter
- ✅ **Checkboxes**: **PRESERVED** (from TASK 7 fix)

---

## 🧪 TESTING

### **Test Case 1: Duration Filter Alone**
```
1. Select "All Devices" (no device filter)
2. Select duration "> 30 minutes"
3. ✅ EXPECTED: Table shows only > 30 min records, sidebar shows all devices
4. ✅ RESULT: PASSED
```

### **Test Case 2: Duration + Device Selection**
```
1. Check only "DT - GPE" devices
2. Select duration "5-15 minutes"
3. ✅ EXPECTED: Table shows DT-GPE with 5-15 min, sidebar unchanged
4. ✅ RESULT: PASSED
```

### **Test Case 3: Location + Duration**
```
1. Select location "Kutai Barat"
2. Select duration "< 5 minutes"
3. ✅ EXPECTED: Table shows Kutai Barat devices with < 5 min, sidebar shows only Kutai Barat devices
4. ✅ RESULT: PASSED
```

### **Test Case 4: Clear Duration Filter**
```
1. Select duration "> 30 minutes"
2. Clear duration filter (select "Semua Durasi")
3. ✅ EXPECTED: Table shows all durations, sidebar unchanged
4. ✅ RESULT: PASSED
```

---

## 📝 SUMMARY

**Changes Made:**
- ✅ Removed 70+ lines of duration filter sidebar sync logic from `drawCallback`
- ✅ Duration filter now ONLY affects table data query (backend)
- ✅ Sidebar device visibility unaffected by duration filter
- ✅ Preserves TASK 7 fix (checkbox selections preserved)

**Lines Modified:**
- Lines 1063-1143: Removed duration filter sidebar logic, simplified drawCallback

**Backend (Unchanged):**
- ✅ `Frontend\IdleAlarmController::data()` already filters by `duration_range` correctly
- ✅ No backend changes needed

**Testing**: ✅ All test cases passed

**Backward Compatible**: ✅ Yes - only removes unwanted sidebar behavior

**Risk**: 🟢 GREEN (UI improvement only, no data/API changes)

---

## 🔗 RELATED FIXES

This fix builds on previous work:
- **TASK 7** (BUGFIX_CHECKBOX_SIDEBAR.md): Preserved checkbox selections across filter changes
- **Current Fix**: Duration filter no longer hides sidebar devices

**Combined Result:**
- ✅ User checkbox selections always preserved
- ✅ Duration filter only affects table display
- ✅ Location/Series filters control sidebar visibility
- ✅ Clean separation of concerns

---

**Status**: ✅ FIXED & TESTED  
**Risk**: 🟢 GREEN (UI-only, no breaking changes)  
**Complexity**: Low (removed unnecessary code)
