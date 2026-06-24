@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

echo.
echo ╔════════════════════════════════════════╗
echo ║     CEK DATA IDLE BULAN MEI (2026)    ║
echo ╚════════════════════════════════════════╝
echo.

cd /d "g:\project\vss\idle-monitor"

echo Running query...
echo.

"C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe" -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$kernel = \$app->make(\Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

\$mayCount = \Illuminate\Support\Facades\DB::table('idle_alarms')
    ->whereRaw('MONTH(starting_time) = 5 AND YEAR(starting_time) = 2026')
    ->count();

\$juneCount = \Illuminate\Support\Facades\DB::table('idle_alarms')
    ->whereRaw('MONTH(starting_time) = 6 AND YEAR(starting_time) = 2026')
    ->count();

\$totalCount = \Illuminate\Support\Facades\DB::table('idle_alarms')->count();

\$mayData = \Illuminate\Support\Facades\DB::table('idle_alarms')
    ->selectRaw('DATE(starting_time) as tanggal, COUNT(*) as jumlah')
    ->whereRaw('MONTH(starting_time) = 5 AND YEAR(starting_time) = 2026')
    ->groupBy('tanggal')
    ->orderBy('tanggal')
    ->get();

echo 'Total Mei 2026: ' . \$mayCount . \" records\n\n\";

echo \"Detail per hari:\n\";
foreach(\$mayData as \$row) {
    echo '  ' . \$row->tanggal . ': ' . \$row->jumlah . \" records\n\";
}

echo \"\n═══════════════════════════════════════\n\";
echo 'Mei 2026:    ' . \$mayCount . \" records\n\";
echo 'Juni 2026:   ' . \$juneCount . \" records\n\";
echo 'Total semua: ' . \$totalCount . \" records\n\";
"

pause
