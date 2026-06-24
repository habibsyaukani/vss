<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$lastSuccess = \App\Models\SystemSetting::get('realtime_pull_last_success_at');
$lastGps = \App\Models\ImportLog::where('job_name', 'ImportGpsTrackJob')->orderBy('id', 'desc')->first()->started_at ?? 'Belum ada';

echo "LAST REALTIME PULL SUCCESS: " . $lastSuccess . "\n";
echo "LAST GPS PULL STARTED AT  : " . $lastGps . "\n";
