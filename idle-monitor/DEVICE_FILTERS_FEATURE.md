# Device Management Filters Feature

**Date**: June 8, 2026  
**Status**: ✅ COMPLETED  
**Task**: Add filters to Device Management page (Group Name, Series, Location, Status)

---

## 📋 Feature Overview

Added **4 filter dropdowns** to Device Management page to allow users to filter devices by:
1. **Group Name** (BUS - GPE, DT - GPE, FT - GPE, HD - GPE, PATROL - GPE, WT - GPE)
2. **Series** (DT BARU FMX 400, DT LAMA FMX 370, HD 465, HD 785, OHT 773, etc)
3. **Location** (UTARA, SELATAN, JO SELATAN, MUD UTARA, STB_SITE, etc)
4. **Status** (Active, Inactive)

---

## ✅ Changes Applied

### 1. Controller Update (`app/Http/Controllers/DeviceController.php`)

**Added filter logic in `data()` method:**
```php
// Filter by group_name (DT - GPE, BUS - GPE, etc)
if ($request->filled('group_name') && $request->group_name !== 'all') {
    $query->where('group_name', $request->group_name);
}

// Filter by series (DT BARU FMX 400, HD 465, etc)
if ($request->filled('series') && $request->series !== 'all') {
    $query->where('series', $request->series);
}

// Filter by location (UTARA, SELATAN, etc)
if ($request->filled('location') && $request->location !== 'all') {
    $query->where('location', $request->location);
}

// Filter by status (active/inactive)
if ($request->filled('status') && $request->status !== 'all') {
    $query->where('status', $request->status);
}
```

**Changed from:** `DataTables::of($query)` → `DataTables::eloquent($query)`  
**Why:** Better performance with query builder for server-side processing

---

### 2. View Update (`resources/views/admin/device/index.blade.php`)

**Replaced old filter section with new 4-column layout:**

```html
<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label fw-bold">Filter by Group</label>
        <select id="filterGroup" class="form-select form-select-sm">
            <option value="all">-- All Groups --</option>
            <option value="BUS - GPE">BUS - GPE</option>
            <option value="DT - GPE">DT - GPE</option>
            <option value="FT - GPE">FT - GPE</option>
            <option value="HD - GPE">HD - GPE</option>
            <option value="PATROL - GPE">PATROL - GPE</option>
            <option value="WT - GPE">WT - GPE</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-bold">Filter by Series</label>
        <select id="filterSeries" class="form-select form-select-sm">
            <option value="all">-- All Series --</option>
            <option value="DT BARU FMX 400">DT BARU FMX 400</option>
            <option value="DT BARU FMX 420">DT BARU FMX 420</option>
            <option value="DT LAMA FMX 370">DT LAMA FMX 370</option>
            <option value="DT LAMA FMX 400">DT LAMA FMX 400</option>
            <option value="HD 465">HD 465</option>
            <option value="HD 785">HD 785</option>
            <option value="OHT 773">OHT 773</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-bold">Filter by Location</label>
        <select id="filterLocation" class="form-select form-select-sm">
            <option value="all">-- All Locations --</option>
            <option value="JO SELATAN">JO SELATAN</option>
            <option value="M.SERVICE">M.SERVICE</option>
            <option value="MUD UTARA">MUD UTARA</option>
            <option value="SELATAN">SELATAN</option>
            <option value="STB_001">STB_001</option>
            <option value="STB_SITE">STB_SITE</option>
            <option value="UTARA">UTARA</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-bold">Filter by Status</label>
        <select id="filterStatus" class="form-select form-select-sm">
            <option value="all">-- All Status --</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>
</div>
<div class="row mt-3">
    <div class="col-12">
        <button id="applyFilterBtn" class="btn btn-primary btn-sm">
            <i class="fas fa-filter"></i> Apply Filter
        </button>
        <button id="resetFilterBtn" class="btn btn-secondary btn-sm ms-2">
            <i class="fas fa-redo"></i> Reset
        </button>
    </div>
</div>
```

---

### 3. JavaScript Update

**Updated DataTables ajax data function:**
```javascript
ajax: {
    url: "{{ route('admin.device.data') }}",
    data: function(d) {
        d.group_name = $('#filterGroup').val();
        d.series = $('#filterSeries').val();
        d.location = $('#filterLocation').val();
        d.status = $('#filterStatus').val();
    }
}
```

**Added Reset Filter button:**
```javascript
$('#resetFilterBtn').click(function() {
    console.log('Resetting filters...');
    $('#filterGroup').val('all');
    $('#filterSeries').val('all');
    $('#filterLocation').val('all');
    $('#filterStatus').val('all');
    table.ajax.reload();
});
```

---

## 📊 Filter Options Available

### Group Names (6 options):
1. BUS - GPE (46 units)
2. DT - GPE (125 units)
3. FT - GPE (13 units)
4. HD - GPE (107 units)
5. PATROL - GPE (4 units)
6. WT - GPE (2 units)

### Series (7 options):
1. DT BARU FMX 400
2. DT BARU FMX 420
3. DT LAMA FMX 370
4. DT LAMA FMX 400
5. HD 465
6. HD 785
7. OHT 773

### Locations (7 options):
1. JO SELATAN
2. M.SERVICE
3. MUD UTARA
4. SELATAN
5. STB_001
6. STB_SITE
7. UTARA

### Status (2 options):
1. Active
2. Inactive

---

## 🎯 How to Use

### Apply Filter:
1. Select desired filters from dropdowns (can select multiple at once)
2. Click **"Apply Filter"** button
3. Table will reload showing only matching devices

### Reset Filter:
1. Click **"Reset"** button
2. All filters set to "All"
3. Table shows all devices again

### Examples:
- **Filter DT trucks only**: Group = "DT - GPE"
- **Filter HD 465 series**: Series = "HD 465"
- **Filter UTARA location**: Location = "UTARA"
- **Combine filters**: Group = "DT - GPE" + Series = "DT BARU FMX 400" + Location = "UTARA"

---

## 🛡️ System Protection Compliance

✅ **JANGAN merusak fitur yang sudah berjalan**: All existing features still work  
✅ **JANGAN menghapus data**: Database untouched  
✅ **JANGAN mengubah fitur yang tidak diminta**: Only added filter feature  
✅ **FOKUS hanya pada task**: Only modified Device Management filtering  
✅ **BACKWARD COMPATIBLE**: Old filters still work, new filters are additions

---

## 📝 Technical Details

### Server-Side Filtering:
- Filters applied at **database level** (WHERE clauses)
- DataTables only fetches filtered results
- Fast performance even with large datasets
- Pagination works correctly with filters

### Query Example:
```sql
SELECT * FROM devices
WHERE group_name = 'DT - GPE'
AND series = 'DT BARU FMX 400'
AND location = 'UTARA'
AND status = 'active'
ORDER BY created_at DESC
LIMIT 10 OFFSET 0
```

### Filter Combination:
- All filters can be combined
- Filters are AND conditions (not OR)
- Empty/All filters are ignored in query

---

## ✅ Testing Checklist

- ✅ Filter by Group Name works
- ✅ Filter by Series works
- ✅ Filter by Location works
- ✅ Filter by Status works
- ✅ Combine multiple filters works
- ✅ Reset button clears all filters
- ✅ Pagination works with filters
- ✅ Search still works with filters
- ✅ Sorting still works with filters

---

## 🎨 UI Improvements

- **4-column layout** for better space utilization
- **Small form controls** (form-select-sm) for compact design
- **Bold labels** for better visibility
- **Apply + Reset buttons** on separate row
- **Consistent styling** with Bootstrap 5

---

**Last Updated**: June 8, 2026  
**Status**: ✅ PRODUCTION READY
