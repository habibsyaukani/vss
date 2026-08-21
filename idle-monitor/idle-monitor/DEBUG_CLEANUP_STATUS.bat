@echo off
echo ========================================
echo DEBUG: Cleanup Job Status
echo ========================================
echo.

set PHP_PATH=C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe

echo 1. Checking if Queue Worker is running...
echo.
tasklist | findstr php
echo.

echo 2. Checking jobs in queue (database)...
%PHP_PATH% artisan tinker --execute="$pending = DB::table('jobs')->count(); $failed = DB::table('failed_jobs')->count(); echo 'Pending jobs: ' . $pending . PHP_EOL; echo 'Failed jobs: ' . $failed . PHP_EOL; if ($failed > 0) { echo PHP_EOL . 'FAILED JOBS:' . PHP_EOL; DB::table('failed_jobs')->orderBy('failed_at', 'desc')->limit(3)->get()->each(function($job) { echo '- Failed at: ' . $job->failed_at . PHP_EOL; echo '  Payload: ' . substr($job->payload, 0, 200) . '...' . PHP_EOL; echo '  Exception: ' . substr($job->exception, 0, 500) . '...' . PHP_EOL . PHP_EOL; }); }"

echo.
echo 3. Checking June data count...
%PHP_PATH% -r "require 'vendor/autoload.php'; $app = require_once 'bootstrap/app.php'; $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); $june = DB::table('alarm_raw')->whereBetween('created_at', ['2026-06-01', '2026-06-30 23:59:59'])->count(); $juneGps = DB::table('gps_tracks_raw')->whereBetween('created_at', ['2026-06-01', '2026-06-30 23:59:59'])->count(); echo 'June alarm_raw: ' . number_format($june) . ' records' . PHP_EOL; echo 'June gps_tracks_raw: ' . number_format($juneGps) . ' records' . PHP_EOL; echo 'Total June data: ' . number_format($june + $juneGps) . ' records' . PHP_EOL;"

echo.
echo 4. Checking last 10 log entries...
%PHP_PATH% artisan tinker --execute="$logs = DB::table('system_logs')->orderBy('created_at', 'desc')->limit(10)->get(['event', 'message', 'created_at']); foreach($logs as $log) { echo '[' . $log->created_at . '] ' . $log->event . ': ' . $log->message . PHP_EOL; }"

echo.
echo ========================================
echo DIAGNOSIS:
echo ========================================
echo If "Pending jobs" > 0 but Queue Worker NOT running:
echo   ^> Job belum diproses, start Queue Worker!
echo.
echo If "Failed jobs" > 0:
echo   ^> Job gagal execute, check error message
echo.
echo If Queue Worker running but June data masih ada:
echo   ^> Job masih dalam proses (5-15 menit)
echo   ^> Atau ada error saat delete
echo.
pause
