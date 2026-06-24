<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Get all from DB and show all device names 
$all = DB::select('SELECT id, device_name, group_name FROM devices ORDER BY device_name');
echo "Total in DB: " . count($all) . "\n\n";

// Show DT ones since that group is 231 vs expected 225
echo "=== DT group ===\n";
$dt = array_filter($all, fn($d) => $d->group_name === 'DT - GPE');
foreach($dt as $d) {
    echo $d->device_name . "\n";
}
echo "DT Count: " . count($dt) . "\n";
