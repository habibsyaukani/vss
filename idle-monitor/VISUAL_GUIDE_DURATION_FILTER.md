# 🎨 VISUAL GUIDE: Duration Filter Behavior

**Date**: June 10, 2026  
**Fix**: BUGFIX 8 - Duration Filter Sidebar Issue

---

## 📊 BEFORE vs AFTER COMPARISON

### ❌ BEFORE FIX (Problematic Behavior)

```
┌─────────────────────────────────────────────────────────────────┐
│                     IDLE MONITOR - BEFORE FIX                   │
├──────────────┬──────────────────────────────────────────────────┤
│   SIDEBAR    │              TABLE AREA                          │
│              │                                                  │
│ ALL GPE (397)│  [Duration Filter: > 30 Menit] ← User selects  │
│              │                                                  │
│ ☑ BUS (46)   │  ┌─────────────────────────────────────────┐   │
│   ├─ GPE-B-1 │  │ Device ID │ Name    │ Duration         │   │
│   ├─ GPE-B-2 │  │ 73210538  │ GPE-DT  │ 32:15 ✅         │   │
│   └─ GPE-B-3 │  │ 73259624  │ GPE-DT  │ 45:22 ✅         │   │
│              │  └─────────────────────────────────────────┘   │
│ ☑ DT (125)   │                                                  │
│   ├─ GPE-DT-1│  Only shows devices with > 30 min ✅            │
│   ├─ GPE-DT-2│                                                  │
│   └─ ...     │                                                  │
│              │                                                  │
│ ☐ FT (13)    │  ❌ PROBLEM: Sidebar hides BUS & FT devices     │
│              │     because they have no > 30 min records!       │
│ ☐ HD (107)   │                                                  │
│              │  User is confused: "Where did my devices go?"   │
└──────────────┴──────────────────────────────────────────────────┘

      ↓ User changes duration filter ↓
      
┌──────────────┬──────────────────────────────────────────────────┐
│   SIDEBAR    │              TABLE AREA                          │
│              │                                                  │
│ ALL GPE (125)│  [Duration Filter: > 30 Menit]                  │
│              │  ← Counter changed! Only shows devices with data │
│ ❌ BUS (0)   │                                                  │
│   HIDDEN     │  ┌─────────────────────────────────────────┐   │
│              │  │ Device ID │ Name    │ Duration         │   │
│ ☑ DT (125)   │  │ 73210538  │ GPE-DT  │ 32:15            │   │
│   ├─ GPE-DT-1│  │ 73259624  │ GPE-DT  │ 45:22            │   │
│   ├─ GPE-DT-2│  └─────────────────────────────────────────┘   │
│   └─ ...     │                                                  │
│              │  ✅ Table shows correct data                     │
│ ❌ FT (0)    │                                                  │
│   HIDDEN     │  ❌ But sidebar devices disappeared!             │
│              │                                                  │
│ ❌ HD (0)    │  User Problem:                                   │
│   HIDDEN     │  - Can't see all devices anymore                │
│              │  - Can't select BUS devices even if they exist  │
└──────────────┴──────────────────────────────────────────────────┘
```

**Problems**:
- ❌ Sidebar devices disappear based on table data availability
- ❌ User loses context of which devices exist
- ❌ Confusing behavior - duration filter affects both table AND sidebar
- ❌ Can't select devices that are hidden

---

### ✅ AFTER FIX (Correct Behavior)

```
┌─────────────────────────────────────────────────────────────────┐
│                     IDLE MONITOR - AFTER FIX                    │
├──────────────┬──────────────────────────────────────────────────┤
│   SIDEBAR    │              TABLE AREA                          │
│              │                                                  │
│ ALL GPE (397)│  [Duration Filter: > 30 Menit] ← User selects  │
│              │                                                  │
│ ☑ BUS (46)   │  ┌─────────────────────────────────────────┐   │
│   ├─ GPE-B-1 │  │ Device ID │ Name    │ Duration         │   │
│   ├─ GPE-B-2 │  │ 73210538  │ GPE-DT  │ 32:15 ✅         │   │
│   └─ GPE-B-3 │  │ 73259624  │ GPE-DT  │ 45:22 ✅         │   │
│              │  └─────────────────────────────────────────┘   │
│ ☑ DT (125)   │                                                  │
│   ├─ GPE-DT-1│  Only shows devices with > 30 min ✅            │
│   ├─ GPE-DT-2│                                                  │
│   └─ ...     │                                                  │
│              │                                                  │
│ ☑ FT (13)    │  ✅ Sidebar UNCHANGED - all devices visible     │
│   ├─ GPE-FT-1│                                                  │
│   └─ ...     │                                                  │
│              │                                                  │
│ ☑ HD (107)   │                                                  │
│   ├─ GPE-HD-1│                                                  │
│   └─ ...     │                                                  │
└──────────────┴──────────────────────────────────────────────────┘

      ↓ User changes duration filter ↓
      
┌──────────────┬──────────────────────────────────────────────────┐
│   SIDEBAR    │              TABLE AREA                          │
│              │                                                  │
│ ALL GPE (397)│  [Duration Filter: 5-15 Menit]                  │
│              │  ← Counter UNCHANGED! ✅                         │
│ ☑ BUS (46)   │                                                  │
│   ├─ GPE-B-1 │  ┌─────────────────────────────────────────┐   │
│   ├─ GPE-B-2 │  │ Device ID │ Name    │ Duration         │   │
│   └─ GPE-B-3 │  │ 73197518  │ GPE-DT  │ 8:43             │   │
│              │  │ 73180927  │ GPE-DT  │ 12:25            │   │
│ ☑ DT (125)   │  │ 73231861  │ GPE-DT  │ 6:18             │   │
│   ├─ GPE-DT-1│  └─────────────────────────────────────────┘   │
│   ├─ GPE-DT-2│                                                  │
│   └─ ...     │  ✅ Table shows different duration range         │
│              │                                                  │
│ ☑ FT (13)    │  ✅ Sidebar STILL shows all devices              │
│   ├─ GPE-FT-1│                                                  │
│   └─ ...     │  User Happy:                                     │
│              │  - All devices always visible                   │
│ ☑ HD (107)   │  - Can select any device anytime                │
│   ├─ GPE-HD-1│  - Duration filter only changes table data      │
│   └─ ...     │  - Predictable, intuitive behavior              │
└──────────────┴──────────────────────────────────────────────────┘
```

**Benefits**:
- ✅ Sidebar devices ALWAYS visible
- ✅ User maintains context of all available devices
- ✅ Clear separation: Duration = table filter, Location/Series = sidebar filter
- ✅ Can select any device regardless of duration filter

---

## 🎯 FILTER TYPE COMPARISON

### Duration Filter (THIS FIX)

```
                    DURATION FILTER
                          ↓
        ┌─────────────────────────────────┐
        │ User selects: "> 30 Menit"      │
        └─────────────────────────────────┘
                          ↓
        ┌─────────────────────────────────┐
        │ Backend Query Filter             │
        │ WHERE duration_seconds >= 1800   │
        └─────────────────────────────────┘
                          ↓
        ┌─────────────────────────────────┐
        │ ✅ TABLE: Shows filtered data    │
        │ ❌ SIDEBAR: UNCHANGED            │
        └─────────────────────────────────┘
```

### Location Filter (Different Purpose)

```
                   LOCATION FILTER
                          ↓
        ┌─────────────────────────────────┐
        │ User selects: "Jambi"            │
        └─────────────────────────────────┘
                          ↓
        ┌─────────────────────────────────┐
        │ Backend Query + Sidebar Filter   │
        │ WHERE location = 'Jambi'         │
        └─────────────────────────────────┘
                          ↓
        ┌─────────────────────────────────┐
        │ ✅ TABLE: Shows Jambi devices    │
        │ ✅ SIDEBAR: Shows Jambi devices  │
        └─────────────────────────────────┘
```

---

## 📊 USER WORKFLOW EXAMPLES

### Example 1: Fleet Manager Finding Long Idles

```
Goal: "I want to see all vehicles with idle > 30 minutes"

Step 1: Open Idle Monitor page
   Sidebar: Shows all 397 devices ✅
   
Step 2: Select duration filter "> 30 Menit"
   ✅ AFTER FIX:
      - Table: Shows only > 30 min records
      - Sidebar: STILL shows all 397 devices
      - Can see which vehicles had long idles
      - Can select other vehicles to compare
      
   ❌ BEFORE FIX:
      - Table: Shows only > 30 min records
      - Sidebar: HIDES vehicles without > 30 min data
      - Lost context of fleet
      - Can't select hidden vehicles
```

### Example 2: Comparing Vehicle Groups

```
Goal: "Compare DT vs BUS idle patterns for short idles (< 5 min)"

Step 1: Select "< 5 Menit" duration filter
   ✅ AFTER FIX:
      - Table: Shows < 5 min records
      - Sidebar: All groups visible (DT, BUS, FT, HD)
      - Can check DT group → see DT short idles
      - Can uncheck DT, check BUS → see BUS short idles
      - Easy comparison!
      
   ❌ BEFORE FIX:
      - Table: Shows < 5 min records
      - Sidebar: May hide some groups if no data
      - Can't easily switch between groups
      - Workflow broken
```

### Example 3: Multi-Filter Workflow

```
Goal: "Show me Kutai Barat DT vehicles with 15-30 min idles today"

✅ AFTER FIX:
   Step 1: Select Location "Kutai Barat"
      → Sidebar shows only Kutai Barat devices (location controls sidebar) ✅
   
   Step 2: Check "DT - GPE" group
      → Table shows only Kutai Barat DT devices ✅
   
   Step 3: Select duration "15-30 Menit"
      → Table filters to 15-30 min duration ✅
      → Sidebar UNCHANGED (still shows Kutai Barat devices) ✅
   
   Step 4: Select date "Today"
      → Table filters to today only ✅
      → Sidebar UNCHANGED ✅
   
   Result: Perfect multi-filter combination! 🎉

❌ BEFORE FIX:
   Step 3: Select duration "15-30 Menit"
      → Table filters correctly ✅
      → Sidebar hides devices without 15-30 min data ❌
      → Lost context of which devices are in Kutai Barat ❌
      → Confusing UX ❌
```

---

## 🔍 TECHNICAL VIEW

### Code Change Visualization

#### BEFORE (Complex, 83 lines)

```javascript
drawCallback: function(settings) {
    // Record count ✅
    $('#recordCount').text(settings.json.recordsTotal);
    
    // Get matching device IDs from server
    let matchingIds = settings.json.matching_device_ids || [];
    let matchingSet = new Set(matchingIds);
    
    // Check if duration filter active
    let durationFilter = $('#durationFilter').val();
    
    if (durationFilter) {  // ❌ PROBLEM: This entire block
        // Loop through all devices
        $('.device-checkbox').each(function() {
            let deviceId = $(this).val();
            let hasMatch = matchingSet.has(deviceId);
            
            if (hasMatch) {
                $treeChild.show();  // Show device
            } else {
                $treeChild.hide();  // ❌ HIDE device
            }
        });
        
        // Update groups visibility
        $allGroups.each(function() {
            // Count visible devices
            if (visibleCount > 0) {
                $groupItem.show();
            } else {
                $groupItem.hide();  // ❌ HIDE group
            }
        });
        
        // Update counters
        $masterCounter.html(`(${totalVisible}|...)`);  // ❌ Change counter
    }
}

// Result: 83 lines of hide/show logic ❌
```

#### AFTER (Simple, 10 lines)

```javascript
drawCallback: function(settings) {
    // Record count ✅
    $('#recordCount').text(settings.json.recordsTotal);
    
    // ✅ REMOVED: Duration filter sidebar sync logic
    // Duration filter should ONLY affect table data display
    // Sidebar device visibility is controlled by Location/Series filters only
    // User checkbox selections are always preserved (TASK 7 fix)
    
    console.log('✅ Table reloaded - record count:', settings.json.recordsTotal);
}

// Result: 10 lines, simple and clear ✅
```

---

## 🎨 COLOR CODING GUIDE

**In Table Display** (UNCHANGED):

| Duration Range | Color | Badge Example |
|----------------|-------|---------------|
| < 5 minutes | 🟢 Green | `ALARM_END` |
| 5-15 minutes | 🟡 Yellow | `ALARM_END` |
| 15-30 minutes | 🟠 Orange | `ALARM_END` |
| > 30 minutes | 🔴 Red | `ALARM_END` |

**Duration Filter Options**:
- "Semua Durasi" → No filter, show all
- "< 5 Menit (0:00-4:59)" → Green category
- "5-15 Menit (5:00-14:59)" → Yellow category
- "15-30 Menit (15:00-29:59)" → Orange category
- "> 30 Menit (30:00+)" → Red category

---

## 📱 RESPONSIVE BEHAVIOR

**Desktop View** (> 1200px):
```
┌──────────────────────────────────────────────┐
│ [Sidebar] │ [Table with filters & data]     │
│ 300px     │ Remaining width                 │
└──────────────────────────────────────────────┘
Duration filter affects TABLE only ✅
```

**Tablet View** (768px - 1200px):
```
┌──────────────────────────────────────────────┐
│ [Sidebar] │ [Table (narrower)]              │
│ 250px     │ Remaining width                 │
└──────────────────────────────────────────────┘
Duration filter affects TABLE only ✅
```

**Mobile View** (< 768px):
```
┌──────────────────────────────────────────────┐
│ [Collapsible Sidebar Toggle]                │
├──────────────────────────────────────────────┤
│ [Table (full width)]                        │
└──────────────────────────────────────────────┘
Duration filter affects TABLE only ✅
Sidebar behavior consistent across all screens
```

---

## ✅ SUMMARY

**Fix Achievement**:
- ✅ Duration filter = Table data filter ONLY
- ✅ Sidebar = Always shows all devices (or filtered by Location/Series)
- ✅ User experience = Predictable and intuitive
- ✅ Code = Simpler and more maintainable

**User Benefits**:
- ✅ No confusion about missing devices
- ✅ Can always select any device
- ✅ Duration filter works as expected
- ✅ Multi-filter workflows smooth and logical

---

**Visual Guide Complete** 🎉
