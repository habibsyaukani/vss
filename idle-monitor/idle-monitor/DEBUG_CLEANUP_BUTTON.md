# Debug: Run Cleanup Now Button Not Working

## Step 1: Check Browser Console

1. Open browser: http://localhost:8000/admin/system-control
2. Press F12 (Open Developer Tools)
3. Go to "Console" tab
4. Click "Run Cleanup Now" button
5. Check for errors (red text)

**Look for:**
- AJAX errors
- 404 Not Found
- 500 Internal Server Error
- CSRF token mismatch
- JavaScript syntax errors

---

## Step 2: Check Network Tab

1. Open Developer Tools (F12)
2. Go to "Network" tab
3. Click "Run Cleanup Now"
4. Look for failed requests (red)

**Expected:**
- POST request to: /admin/system-control/cleanup/run
- Status: 200 OK
- Response: JSON with success message

---

## Step 3: Manual Test Routes

Open new browser tab and test:

### Test 1: Status Route
```
http://localhost:8000/admin/system-control/status
```
Expected: JSON response with queue, realtime, cleanup data

### Test 2: Check if route exists
```
Run: php artisan route:list --name=cleanup
```
Should show cleanup routes

---

## Step 4: Check Laravel Log

```
File: storage/logs/laravel.log
Look for: Recent errors when clicking button
```

---

## Common Issues:

### Issue 1: CSRF Token Mismatch
**Symptom:** 419 error in console
**Fix:** Clear browser cache, refresh page

### Issue 2: Route Not Found
**Symptom:** 404 error in console
**Fix:** Run `php artisan route:cache`

### Issue 3: JavaScript Not Loaded
**Symptom:** Button does nothing, no console error
**Fix:** Check if jQuery and SweetAlert loaded

### Issue 4: Job Queue Not Running
**Symptom:** Button works but job doesn't execute
**Fix:** Start queue worker

---

## Quick Fix Checklist:

[ ] Clear browser cache (Ctrl+Shift+Del)
[ ] Refresh page (Ctrl+F5)
[ ] Check console for errors
[ ] Run: php artisan route:clear
[ ] Run: php artisan cache:clear
[ ] Run: php artisan config:clear
[ ] Restart browser
[ ] Try different browser

---

## Manual Run (Alternative):

If button still doesn't work, run manually via command:

```bash
cd idle-monitor
php artisan queue:work --once --tries=1
```

Then dispatch job manually:
```bash
php artisan tinker
>>> \App\Jobs\CleanupOldRawDataJob::dispatch();
>>> exit
```
