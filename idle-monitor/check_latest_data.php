<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║              CEK DATA TERAKHIR & STATUS SCHEDULER              ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Cek data terakhir di idle_alarms
$latest = DB::table('idle_alarms')->orderByDesc('created_at')->first();
if ($latest) {
    echo "📊 Data Terakhir di idle_alarms:\n";
    echo "   Device ID: {$latest->device_id}\n";
    echo "   Starting Time: {$latest->starting_time}\n";
    echo "   Created At: {$latest->created_at}\n";
    echo "   Updated At: {$latest->updated_at}\n";
    
    $now = now();
    $createdAt = \Carbon\Carbon::parse($latest->created_at);
    $diff = $now->diffInMinutes($createdAt);
    
    echo "\n";
    echo "⏰ Waktu dari data terakhir: {$diff} menit yang lalu\n";
    
    if ($diff < 5) {
        echo "✅ Status: DATA FRESH (< 5 menit)\n";
    } elseif ($diff < 30) {
        echo "⚠️  Status: DATA AGAK LAMA ({$diff} menit)\n";
    } else {
        echo "❌ Status: DATA SUDAH LAMA ({$diff} menit)\n";
        echo "   Scheduler mungkin tidak berjalan!\n";
    }
} else {
    echo "❌ Tidak ada data di idle_alarms\n";
}

echo "\n";

// Cek system settings
echo "📋 System Settings:\n";
$settings = DB::table('system_settings')
    ->whereIn('key', ['last_backfill_date', 'last_realtime_pull'])
    ->get();

foreach ($settings as $setting) {
    echo "   {$setting->key}: {$setting->value}\n";
}

echo "\n";

// Count data
echo "📈 Jumlah Data:\n";
$meiCount = DB::table('idle_alarms')
    ->whereRaw('MONTH(starting_time) = 5 AND YEAR(starting_time) = 2026')
    ->count();
$juneCount = DB::table('idle_alarms')
    ->whereRaw('MONTH(starting_time) = 6 AND YEAR(starting_time) = 2026')
    ->count();
$totalCount = DB::table('idle_alarms')->count();

echo "   Mei 2026:    {$meiCount} records\n";
echo "   Juni 2026:   {$juneCount} records\n";
echo "   Total:       {$totalCount} idle alarms\n";

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  KESIMPULAN                                                    ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";

if ($latest && $diff < 5) {
    echo "✅ Scheduler BERJALAN dengan baik (data fresh)\n";
} else {
    echo "❌ Scheduler TIDAK berjalan / sudah lama tidak update\n";
    echo "   Jalankan: START_SCHEDULER_TAHAP12.bat\n";
}

echo "\n";
