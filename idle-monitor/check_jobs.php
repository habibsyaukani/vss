<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$jobs = \DB::table('jobs')->get(['id', 'queue', 'payload', 'attempts', 'created_at']);
echo "Pending Jobs:\n";
foreach($jobs as $j) {
    $payload = json_decode($j->payload, true);
    $jobName = $payload['displayName'] ?? 'Unknown';
    echo "Job ID: {$j->id} | {$jobName} | Attempts: {$j->attempts} | Created: " . date('Y-m-d H:i:s', $j->created_at) . "\n";
}
