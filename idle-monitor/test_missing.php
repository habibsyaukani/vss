<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$missingIds = App\Models\AlarmRaw::select('device_id', 'device_name')
    ->distinct()
    ->whereNotIn('device_id', function($q) {
        $q->select('device_id')->from('devices');
    })->get();

echo "Missing count: " . $missingIds->count() . "\n";
foreach($missingIds as $m) {
    echo "Missing: " . $m->device_id . " - " . $m->device_name . "\n";
}
