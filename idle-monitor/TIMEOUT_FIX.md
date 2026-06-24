# ⏱️ PHP Timeout Fix - Data Pull Feature

## 🔴 Problem: "Maximum execution time of 60 seconds exceeded"

### Error yang muncul:
```
ERROR
Maximum execution time of 60 seconds exceeded
8/6/2026, 10.39.49
```

### Root Cause:
- PHP default `max_execution_time = 60` seconds
- Proses `Artisan::call('howen:pull-alarms-date-range')` untuk rentang tanggal besar (multiple days) memakan waktu >60 detik
- PHP membunuh script sebelum proses selesai

---

## ✅ Solution Implemented

### 1. **Backend: Increase PHP Timeout**
File: `app/Http/Controllers/DataPullController.php`

```php
public function execute(Request $request)
{
    // INCREASE PHP TIMEOUT - Data pull can take long time!
    set_time_limit(600); // 10 minutes
    ini_set('max_execution_time', 600);
    
    // ... rest of code
}
```

**Why 10 minutes (600 seconds)?**
- 1 hari data = ~1-3 menit processing
- 7 hari data = ~5-10 menit
- Buffer untuk safety

### 2. **Frontend: Increase AJAX Timeout**
File: `resources/views/admin/data-pull.blade.php`

```javascript
$.ajax({
    url: '{{ route("admin.data-pull.execute") }}',
    method: 'POST',
    data: formData,
    timeout: 900000, // 15 minutes (900 seconds)
    // ... rest of code
});
```

**Why 15 minutes?**
- Lebih besar dari PHP timeout (10 min)
- Memberi buffer untuk network latency
- Mencegah AJAX timeout sebelum PHP selesai

### 3. **UX: Time Estimation**
Menambahkan estimasi waktu berdasarkan range tanggal:

```javascript
const daysDiff = Math.ceil((toDate - fromDate) / (1000 * 60 * 60 * 24)) + 1;
const estimatedMinutes = daysDiff * 1.5; // ~1.5 minutes per day average
```

User sekarang melihat:
- **"Estimasi waktu: 2-5 menit"** (untuk 2-3 hari)
- **"Estimasi waktu: 5-10 menit"** (untuk 4-7 hari)
- **"Estimasi waktu: 10-15 menit"** (untuk 8+ hari)

---

## 📊 Timeout Matrix

| Range Tanggal | Records Approx | Time Needed | PHP Timeout | AJAX Timeout | Status |
|---------------|----------------|-------------|-------------|--------------|--------|
| 1 hari        | 10-20k         | 1-2 min     | 600s ✅     | 900s ✅      | OK     |
| 2-3 hari      | 20-60k         | 2-5 min     | 600s ✅     | 900s ✅      | OK     |
| 4-7 hari      | 40-140k        | 5-10 min    | 600s ✅     | 900s ✅      | OK     |
| 8-15 hari     | 80-300k        | 10-15 min   | 600s ⚠️     | 900s ✅      | LIMIT  |
| >15 hari      | 300k+          | 15+ min     | 600s ❌     | 900s ❌      | FAIL   |

**Recommendation:**
- ✅ **Best practice**: Pull 1-7 hari per request
- ⚠️ **Maximum**: 8-15 hari (bisa timeout jika network lambat)
- ❌ **Avoid**: >15 hari dalam satu request

---

## 🚀 Alternative Solutions (Future Improvements)

### Option A: Laravel Queue/Job (Async Processing)
**Pros:**
- No timeout issues
- Process runs in background
- Better server resource management
- Can handle unlimited data

**Cons:**
- Requires queue worker running
- More complex setup
- Need Redis/Database queue driver

**Implementation:**
```php
// Dispatch job
dispatch(new PullDataJob($fromDate, $toDate, $pages, $concurrency));

// Return immediately
return response()->json(['success' => true, 'job_id' => $jobId]);

// User polls for progress
Route::get('/data-pull/status/{jobId}', [DataPullController::class, 'status']);
```

### Option B: Server-Sent Events (SSE) - Real-time Progress
**Pros:**
- Real-time progress updates
- Better UX (live progress bar)
- No polling needed

**Cons:**
- More complex implementation
- Requires SSE support
- Browser compatibility

### Option C: Chunk Processing (Recommended for Production)
**Pros:**
- Process hari per hari
- Show progress for each day
- Can resume if failed
- Better error handling

**Implementation:**
```javascript
// Frontend: Loop through dates
for (let date = fromDate; date <= toDate; date++) {
    await pullSingleDay(date);
    updateProgress();
}
```

---

## 🔧 Configuration Files (Optional Enhancement)

### If you need GLOBAL PHP timeout increase:

#### Option 1: `.htaccess` (Apache)
Create/modify `public/.htaccess`:
```apache
php_value max_execution_time 600
php_value max_input_time 600
```

#### Option 2: `php.ini` (Laragon)
Edit `C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.ini`:
```ini
max_execution_time = 600
max_input_time = 600
```

**Note:** Our current solution uses `set_time_limit()` in controller, so global config is NOT required.

---

## 📋 Testing After Fix

### Test Case 1: Small Range (1 day)
```
From: 2026-06-08
To: 2026-06-08
Expected: Success in ~1-2 minutes
```

### Test Case 2: Medium Range (7 days)
```
From: 2026-06-01
To: 2026-06-07
Expected: Success in ~5-10 minutes
```

### Test Case 3: Large Range (15 days)
```
From: 2026-05-24
To: 2026-06-08
Expected: Success but close to timeout (~12-15 min)
```

---

## ✅ Verification Checklist

After implementing the fix, verify:

- [ ] PHP timeout increased to 600 seconds in controller
- [ ] AJAX timeout increased to 900 seconds in view
- [ ] Estimasi waktu muncul di UI
- [ ] Warning "PENTING: Jangan refresh" ditambahkan
- [ ] Test dengan range 1 hari - SUCCESS
- [ ] Test dengan range 7 hari - SUCCESS
- [ ] Progress bar tetap muncul selama proses
- [ ] Console log menampilkan estimated time

---

## 📞 If Timeout Still Occurs

### Scenario 1: Timeout after 10 minutes (600s)
**Cause:** Data range terlalu besar
**Solution:**
1. Kurangi range tanggal (max 7 hari)
2. Atau kurangi `pages` parameter (dari 100 ke 50)
3. Atau implement Queue/Job solution

### Scenario 2: Timeout after 15 minutes (900s)
**Cause:** AJAX timeout
**Solution:**
1. Increase AJAX timeout in view: `timeout: 1200000` (20 minutes)
2. But better: Implement chunked processing

### Scenario 3: Random timeout at different times
**Cause:** Server resource constraints
**Solution:**
1. Check server CPU/Memory usage
2. Reduce `concurrency` parameter (dari 5 ke 3)
3. Run during off-peak hours

---

## 🎯 Best Practices

### DO ✅
- Pull data in chunks (1-7 days per request)
- Monitor console log for progress
- Wait for completion message
- Pull during off-peak hours for large ranges

### DON'T ❌
- Don't refresh browser during process
- Don't close browser tab during process
- Don't pull >15 days in one request
- Don't click button multiple times (dapat duplicate data)

---

## 📈 Performance Optimization Tips

1. **Reduce Pages Parameter**
   - Default: 100 pages
   - For quick test: 10 pages
   - For full data: 100-200 pages

2. **Adjust Concurrency**
   - Low: 3 (slower but safer)
   - Medium: 5 (balanced) ← **Recommended**
   - High: 7-10 (faster but heavy on server)

3. **Pull During Off-Peak**
   - Best: Malam hari (22:00 - 06:00)
   - Avoid: Jam kerja (08:00 - 17:00)
   - Why: API response lebih cepat, less contention

4. **Incremental Pulling**
   - Pull kemarin setiap hari (automated)
   - Pull bulk hanya untuk historical data
   - Use scheduler untuk daily automatic pull

---

**Last Updated:** 2026-06-08
**Status:** ✅ FIXED
**Version:** 1.1

