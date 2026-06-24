<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$groups = DB::table('devices')->orderBy('id', 'desc')->limit(10)->get();
echo "=== Last 10 Devices ===\n";
foreach ($groups as $g) {
    echo "ID: {$g->id} | Name: {$g->device_name} | Group: {$g->group_name} | created: {$g->created_at}\n";
}
