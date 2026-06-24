<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AlarmRaw;
use App\Models\IdleAlarm;
use App\Models\SystemSetting;
use App\Services\HowenAlarmService;
use Carbon\Carbon;

echo "=== STATUS DATA CHECK ===\n\n";

// Total alarm_raw
$totalRaw = AlarmRaw::count();
echo "Total alarm_raw: {$totalRaw}\n";

// Total idle_alarms
$totalIdle = IdleAlarm::count();
echo "Total idle_alarms: {$totalIdle}\n\n";

// Per-bulan May
$mayRaw = AlarmRaw::whereBetween('created_at', ['2026-05-01', '2026-05-31 23:59:59'])->count();
$mayIdle = IdleAlarm::whereBetween('created_at', ['2026-05-01', '2026-05-31 23:59:59'])->count();
echo "Mei - alarm_raw: {$mayRaw}, idle_alarms: {$mayIdle}\n";

// Per-bulan June
$junRaw = AlarmRaw::where('created_at', '>=', '2026-06-01')->count();
$junIdle = IdleAlarm::where('created_at', '>=', '2026-06-01')->count();
echo "Juni - alarm_raw: {$junRaw}, idle_alarms: {$junIdle}\n\n";

// System settings
$lastBackfill = SystemSetting::get('last_backfill_date', '-');
$lastRealtime = SystemSetting::get('last_realtime_pull', '-');
echo "last_backfill_date: {$lastBackfill}\n";
echo "last_realtime_pull: {$lastRealtime}\n\n";

// Sample the API - how many total records are there for May?
echo "=== API ESTIMATE (Page 1 sample per day) ===\n";
$service = new HowenAlarmService();

$sampleDays = ['2026-05-01', '2026-05-05', '2026-05-10', '2026-05-15', '2026-05-20', '2026-05-25', '2026-05-31'];
$totalEstimate = 0;

foreach ($sampleDays as $day) {
    $start = $day . ' 00:00:00';
    $end = $day . ' 23:59:59';
    
    // Hit page 1 to get totalCount
    try {
        $token = (new \App\Services\HowenAuthService())->getToken();
        $client = new \GuzzleHttp\Client();
        $response = $client->post(rtrim(env('HOWEN_API_URL'), '/') . '/alarm/apiFindAllByTime.action', [
            'form_params' => [
                'token' => $token,
                'pageNum' => 1,
                'pageCount' => 1,
                'beginTime' => $start,
                'endTime' => $end,
                'alarmType' => '',
                'deviceID' => '',
            ],
            'timeout' => 10,
            'verify' => false,
        ]);
        $data = json_decode($response->getBody()->getContents(), true);
        $totalCount = $data['data']['totalCount'] ?? 0;
        $totalEstimate += $totalCount;
        echo "  {$day}: {$totalCount} total alarms\n";
    } catch (\Exception $e) {
        echo "  {$day}: Error - " . $e->getMessage() . "\n";
    }
    usleep(300000); // 300ms between requests
}

$avgPerDay = count($sampleDays) > 0 ? $totalEstimate / count($sampleDays) : 0;
$totalMayEstimate = $avgPerDay * 31;
$pagesPerDay = ceil($avgPerDay / 200);
$timePerDaySeconds = $pagesPerDay * 0.5; // ~500ms per page sequential, parallelism divides by 5
$timeParallelSeconds = ($pagesPerDay / 5) * 1.5; // with 5 concurrency and network latency
$totalTimeMay = $timeParallelSeconds * 31;

echo "\n=== ESTIMASI WAKTU ===\n";
echo "Rata-rata alarm per hari: " . round($avgPerDay) . "\n";
echo "Estimasi total alarm Mei: " . round($totalMayEstimate) . "\n";
echo "Pages per hari (200/page): " . round($pagesPerDay) . "\n";
echo "Waktu per hari (parallel 5x): " . round($timeParallelSeconds) . " detik\n";
echo "Total estimasi untuk 31 hari Mei: " . round($totalTimeMay / 60) . " menit\n";
