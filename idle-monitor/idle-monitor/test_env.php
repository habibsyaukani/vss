<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "APP_KEY: " . env('TRACKSOLID_APP_KEY') . "\n";
echo "SECRET: " . env('TRACKSOLID_APP_SECRET') . "\n";
