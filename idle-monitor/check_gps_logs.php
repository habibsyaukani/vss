<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$logs = \App\Models\ImportLog::where('job_name', 'ImportGpsTrackJob')
    ->orderBy('id', 'desc')
    ->take(5)
    ->get(['id', 'started_at', 'finished_at', 'status', 'total_record', 'message']);

echo json_encode($logs, JSON_PRETTY_PRINT);
