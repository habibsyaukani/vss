@echo off
echo ========================================
echo CHECK DATA BULAN JUNI
echo ========================================
echo.

set PHP_PATH=C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe

echo Checking data from June 2026...
echo.

%PHP_PATH% -r "require 'vendor/autoload.php'; $app = require_once 'bootstrap/app.php'; $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); echo 'Counting alarm_raw records from June 2026...' . PHP_EOL; $juneCount = DB::table('alarm_raw')->whereBetween('created_at', ['2026-06-01 00:00:00', '2026-06-30 23:59:59'])->count(); echo 'June alarm_raw: ' . number_format($juneCount) . ' records' . PHP_EOL; echo PHP_EOL; echo 'Counting gps_tracks_raw records from June 2026...' . PHP_EOL; $gpsJuneCount = DB::table('gps_tracks_raw')->whereBetween('created_at', ['2026-06-01 00:00:00', '2026-06-30 23:59:59'])->count(); echo 'June gps_tracks_raw: ' . number_format($gpsJuneCount) . ' records' . PHP_EOL; echo PHP_EOL; echo 'Total June data: ' . number_format($juneCount + $gpsJuneCount) . ' records' . PHP_EOL;"

echo.
echo ========================================
echo These records will be DELETED if you
echo run cleanup with retention > 33 days
echo ========================================
pause
