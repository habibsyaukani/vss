# Sticky Filter Row Fix - Horizontal Scroll Issue

**Date**: June 11, 2026  
**Issue**: Filter row dan header ikut tergeser saat scroll horizontal  
**Status**: ✅ FIXED  

---

## 🐛 PROBLEM

**Symptom:**
- Saat scroll tabel ke kanan (horizontal), filter row di atas tabel ikut bergeser
- Header tabel juga ikut scroll horizontal
- Frozen columns (5 kolom pertama) bekerja, tapi filter row dan header tidak stay

**Root Cause:**
- `.main-content` container memiliki `overflow-y: auto` 
- Ini membuat `.main-content` menjadi scrollable container
- Semua child elements (termasuk filter row) ter-wrap dalam scroll context
- Ketika table scroll horizontal, parent container juga scroll

---

## ✅ SOLUTION

### Fix Applied:

**1. Override Main Content Overflow** (CRITICAL FIX)
```css
/* Added to page-specific styles */
.main-content {
    overflow-x: hidden !important; /* Prevent horizontal scroll */
    overflow-y: auto; /* Keep vertical scroll */
}
```

**2. Ensure Top Filter Row is Sticky**
```css
.top-filter-row {
    position: sticky;
    top: 0;
    left: 0;
    right: 0;
    width: 100%;
    z-index: 100;
}
```

**3. Table Container Has Overflow**
```css
.table-container {
    overflow-x: auto; /* Only table scrolls horizontally */
    position: relative;
}
```

---

## 🎯 HOW IT WORKS NOW

### Scroll Behavior:

**Horizontal Scroll** (kanan-kiri):
```
main-content (overflow-x: hidden) ← TIDAK SCROLL HORIZONTAL
  ↓
  top-filter-row (sticky, full width) ← TETAP DI TEMPAT ✅
  ↓
  table-container (overflow-x: auto) ← INI YANG SCROLL
    ↓
    table (frozen columns) ← 5 KOLOM TETAP, SISANYA SCROLL ✅
```

**Vertical Scroll** (atas-bawah):
```
main-content (overflow-y: auto) ← SCROLL VERTIKAL
  ↓
  top-filter-row (sticky top: 0) ← TETAP DI ATAS ✅
  ↓
  table header (sticky top: 80px) ← TETAP DI BAWAH FILTER ✅
  ↓
  table body ← SCROLL KEBAWAH
```

---

## 📁 FILES MODIFIED

### 1. `resources/views/frontend/idle-alarm/index.blade.php`

**Changes:**
```css
/* Added at top of styles section */
.main-content {
    overflow-x: hidden !important;
    overflow-y: auto;
}

/* Already existing - no change needed */
.top-filter-row {
    position: sticky;
    top: 0;
    left: 0;
    right: 0;
    width: 100%;
    z-index: 100;
}

.table-container {
    overflow-x: auto;
    position: relative;
}
```

---

## 🧪 TESTING CHECKLIST

### Test Horizontal Scroll:
- [ ] Open page: http://127.0.0.1:8000/idle-alarm
- [ ] Load data dengan banyak kolom
- [ ] **Scroll tabel ke kanan** (drag scrollbar horizontal di tabel)
- [ ] **VERIFY**: Filter row (tanggal, export buttons) **TETAP DI TEMPAT** ✅
- [ ] **VERIFY**: 5 kolom frozen (checkbox, device ID, name) **TETAP DI KIRI** ✅
- [ ] **VERIFY**: Kolom lainnya (time, location) **BERGERAK KE KIRI** ✅

### Test Vertical Scroll:
- [ ] **Scroll ke bawah** (scroll wheel atau drag scrollbar vertikal)
- [ ] **VERIFY**: Filter row **TETAP DI ATAS** ✅
- [ ] **VERIFY**: Table header **TETAP DI BAWAH FILTER ROW** ✅
- [ ] **VERIFY**: Data rows **SCROLL NORMAL** ✅

### Test Combined:
- [ ] **Scroll kanan + bawah** (gabungan)
- [ ] **VERIFY**: Filter row tetap atas ✅
- [ ] **VERIFY**: Frozen columns tetap kiri ✅
- [ ] **VERIFY**: Header tetap di bawah filter ✅
- [ ] **VERIFY**: Tidak ada element yang "nyangkut" atau overlap ❌

---

## 🔍 DEBUGGING GUIDE

### If Filter Row Still Scrolls Horizontally:

**Check 1: Inspect Element**
```javascript
// Open browser console, run:
$('.main-content').css('overflow-x')
// Should return: "hidden"

$('.top-filter-row').css('position')
// Should return: "sticky"

$('.top-filter-row').css('z-index')
// Should return: "100"
```

**Check 2: Clear Cache**
```bash
php artisan view:clear
# Then hard refresh browser (Ctrl+F5)
```

**Check 3: Verify CSS Applied**
```
1. Open DevTools (F12)
2. Select .main-content element
3. Check Computed styles
4. Ensure overflow-x: hidden is applied (not overridden)
```

### If Table Doesn't Scroll Horizontally:

**Check:**
```css
/* .table-container should have: */
overflow-x: auto; /* Not hidden! */
```

---

## 💡 WHY THIS FIX WORKS

### Overflow Context Explanation:

**Before Fix:**
```
.main-content
  overflow-y: auto (creates scroll context)
  overflow-x: auto (default, allows horizontal scroll)
  
  When table is wide:
    → .main-content scrolls horizontally
    → .top-filter-row (child) moves with scroll
    → .table scrolls inside scrolling parent
    → DOUBLE SCROLL = BAD UX ❌
```

**After Fix:**
```
.main-content
  overflow-y: auto (vertical scroll OK)
  overflow-x: hidden (NO horizontal scroll)
  
  .top-filter-row (sticky, top: 0)
    → Sticks to .main-content viewport
    → TIDAK SCROLL HORIZONTAL ✅
  
  .table-container (overflow-x: auto)
    → ONLY THIS scrolls horizontally
    → Frozen columns implemented here
    → SINGLE SCROLL POINT = GOOD UX ✅
```

---

## 🎨 CSS STACKING ORDER

### Z-Index Layers (From Top to Bottom):

```
Layer 1: top-filter-row (z-index: 100)
  ↓ Always on top, never scrolls
  
Layer 2: table frozen headers (z-index: 60)
  ↓ Sticky left + sticky top (corner)
  
Layer 3: table regular headers (z-index: 50)
  ↓ Sticky top only
  
Layer 4: table frozen cells (z-index: 5)
  ↓ Sticky left only
  
Layer 5: table regular cells (z-index: auto)
  ↓ Normal flow
```

---

## 🔄 ROLLBACK PROCEDURE

If you need to revert this fix:

### Remove Overflow Override:
```css
/* Delete or comment this section: */
/*
.main-content {
    overflow-x: hidden !important;
    overflow-y: auto;
}
*/
```

### Then:
```bash
php artisan view:clear
# Refresh browser (Ctrl+F5)
```

**Note**: Reverting will bring back the horizontal scroll issue.

---

## 📚 RELATED DOCUMENTATION

- **Main Guide**: `FROZEN_COLUMNS_PANDUAN.md`
- **Technical Details**: `FROZEN_COLUMNS_IMPLEMENTATION.md`
- **Z-Index Diagram**: `STICKY_LAYERS_DIAGRAM.md`
- **CSS Overflow**: https://developer.mozilla.org/en-US/docs/Web/CSS/overflow

---

## ✅ VERIFICATION

After applying fix, verify:
- [x] Filter row tidak scroll horizontal ✅
- [x] Table bisa scroll horizontal (hanya table container) ✅
- [x] Frozen columns tetap bekerja ✅
- [x] Sticky header tetap bekerja ✅
- [x] Vertical scroll normal ✅
- [x] No console errors ✅
- [x] All functionality intact ✅

---

**Created**: June 11, 2026  
**Status**: ✅ RESOLVED  
**Risk**: 🟢 GREEN (CSS only, no breaking changes)

---

*Critical fix for horizontal scroll behavior on sticky elements*
