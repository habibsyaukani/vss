<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "AlarmRaw count: " . \App\Models\AlarmRaw::count() . PHP_EOL;
echo "ImportLog count: " . \App\Models\ImportLog::count() . PHP_EOL;
echo "Jobs in queue: " . \DB::table('jobs')->count() . PHP_EOL;

$logs = \App\Models\ImportLog::latest()->limit(5)->get();
echo "\nRecent ImportLog entries:\n";
foreach ($logs as $log) {
    echo "- {$log->job_name}: {$log->status} ({$log->total_record} records) - {$log->message}\n";
}
