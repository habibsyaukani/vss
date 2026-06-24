<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$jobs = \DB::table('jobs')->count();
echo "Jobs pending: $jobs\n";

$logs = \App\Models\ImportLog::where('job_name', 'like', '%Gps%')
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();
foreach($logs as $l) {
    echo $l->created_at . ' | ' . $l->job_name . ' | Status: ' . $l->status . ' | Records: ' . $l->total_record . "\n";
}
