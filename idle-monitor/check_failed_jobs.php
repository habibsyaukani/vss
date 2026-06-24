<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Total Failed: " . \DB::table('failed_jobs')->count() . "\n";
$failed = \DB::table('failed_jobs')->latest('failed_at')->limit(5)->get(['id', 'exception', 'failed_at']);
foreach($failed as $f) {
    echo 'Failed At: ' . $f->failed_at . "\n";
    echo substr($f->exception, 0, 200) . "\n";
    echo "-----------------\n";
}
