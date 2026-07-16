@echo off
echo ========================================
echo BATCH DATA PULL - VERIFICATION SCRIPT
echo ========================================
echo.

echo [1] Checking if table exists...
php artisan tinker --execute="echo \App\Models\DataPullBatch::count() . ' records in data_pull_batches table';"
echo.

echo [2] Checking latest batches...
php artisan tinker --execute="\App\Models\DataPullBatch::latest()->limit(5)->get(['session_id', 'batch_number', 'status'])->each(fn($b) => echo \"Session: {$b->session_id}, Batch: {$b->batch_number}, Status: {$b->status}\n\");"
echo.

echo [3] Checking queue worker status...
tasklist | findstr php
echo.

echo ========================================
echo VERIFICATION COMPLETED
echo ========================================
pause
