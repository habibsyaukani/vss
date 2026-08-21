<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\HowenAlarmService;
use App\Models\AlarmRaw;
use App\Jobs\ProcessIdleAlarmJob;
use Carbon\Carbon;

echo "Memulai penarikan data dari 2026-08-05 16:00:00 sampai sekarang...\n";

$service = new HowenAlarmService();
$start = '2026-08-05 16:00:00';
$end = date('Y-m-d H:i:s');

// Tarik data secara sekuensial agar tidak membebani server
$page = 1;
$totalInserted = 0;

while(true) {
    echo "Menarik Halaman $page ... ";
    $alarms = $service->fetchAlarmsPage($page, 200, $start, $end);
    
    if (empty($alarms)) {
        echo "Kosong. Selesai menarik.\n";
        break;
    }
    
    $count = count($alarms);
    echo "Dapat $count baris. Menyimpan ke database...\n";
    
    $insertedThisPage = 0;
    foreach($alarms as $alarm) {
        $deviceId = $alarm['deviceguid'] ?? $alarm['device_id'] ?? null;
        if (!$deviceId) continue;

        $alarmValue = $alarm['alarmvalue'] ?? $alarm['alarmValue'] ?? null;
        $endDetail = $alarm['endDetail'] ?? $alarm['end_detail'] ?? null;
        
        $durationFromStart = 0;
        if (!empty($alarmValue) && preg_match('/dur:(\d+)/', $alarmValue, $m)) $durationFromStart = (int)$m[1];
        
        $durationFromEnd = 0;
        if (!empty($endDetail) && preg_match('/dur:(\d+)/', $endDetail, $m)) $durationFromEnd = (int)$m[1];
        
        $alarmTimeLength = (int)($alarm['alarmTimeLength'] ?? $alarm['duration_seconds'] ?? 0);
        $durationSeconds = $durationFromStart > 0 ? $durationFromStart : ($durationFromEnd > 0 ? $durationFromEnd : $alarmTimeLength);

        $guid = $alarm['guid'] ?? uniqid();
        
        try {
            AlarmRaw::updateOrCreate(
                ['guid' => $guid],
                [
                    'device_id' => $deviceId,
                    'device_name' => $alarm['deviceName'] ?? $alarm['device_name'] ?? null,
                    'alarm_type' => $alarm['alarmtype'] ?? $alarm['alarm_type'] ?? null,
                    'alarm_value' => $alarmValue,
                    'alarm_state' => $alarm['alarmState'] ?? $alarm['alarm_state'] ?? 0,
                    'start_time' => $alarm['createtime'] ?? $alarm['start_time'] ?? null,
                    'end_time' => $alarm['endTime'] ?? $alarm['end_time'] ?? null,
                    'start_gps' => $alarm['alarmGps'] ?? $alarm['start_gps'] ?? null,
                    'end_gps' => $alarm['endGps'] ?? $alarm['end_gps'] ?? null,
                    'start_speed' => (float)($alarm['speed'] ?? $alarm['start_speed'] ?? 0),
                    'end_speed' => (float)($alarm['endSpeed'] ?? $alarm['end_speed'] ?? 0),
                    'report_time' => $alarm['reportTime'] ?? $alarm['report_time'] ?? null,
                    'duration_seconds' => $durationSeconds,
                    'start_detail' => $alarmValue,
                    'end_detail' => $endDetail,
                    'raw_json' => json_encode($alarm),
                ]
            );
            $insertedThisPage++;
        } catch (\Exception $e) {
            // Abaikan error duplikat
        }
    }
    
    $totalInserted += $insertedThisPage;
    echo "Selesai halaman $page. ($insertedThisPage baris masuk)\n";
    $page++;
    
    if ($page > 10) { // Limit 10 halaman
        echo "Mencapai batas 10 halaman.\n";
        break;
    }
    
    usleep(500000); // Jeda 0.5 detik
}

echo "Total raw data masuk: $totalInserted\n";
echo "Memproses menjadi data Idle Alarm...\n";

$job = new ProcessIdleAlarmJob();
$job->handle();

echo "SELESAI! Silakan cek kembali.\n";
