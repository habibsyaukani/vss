<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Import Logs:\n";
$logs = \App\Models\ImportLog::orderBy('id', 'desc')->limit(10)->get();
foreach($logs as $l) {
    echo $l->created_at . ' | ' . $l->job_name . ' | Status: ' . $l->status . ' | Records: ' . $l->total_record . ' | Msg: ' . $l->message . "\n";
}

echo "\nFailed Jobs:\n";
$failed = \DB::table('failed_jobs')->latest('failed_at')->limit(5)->get();
foreach($failed as $f) {
    echo "Failed at: {$f->failed_at} | Exception: " . substr($f->exception, 0, 150) . "\n";
}

echo "\nLatest raw alarms:\n";
$raw = \App\Models\AlarmRaw::orderBy('id', 'desc')->limit(2)->get();
foreach($raw as $r) {
    echo $r->created_at . ' | Start: ' . $r->start_time . ' | Device: ' . $r->device_name . "\n";
}
