<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$logs = \App\Models\ImportLog::whereDate('created_at', '2026-06-23')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get();
foreach($logs as $l) {
    echo $l->created_at . ' | ' . $l->job_name . ' | Status: ' . $l->status . ' | Records: ' . $l->total_record . ' | Msg: ' . $l->message . "\n";
}
