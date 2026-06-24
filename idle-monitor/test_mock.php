<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ids = ['755161145', '732390518', '731865503'];
foreach($ids as $id) {
    echo $id . ': ' . App\Models\AlarmRaw::where('device_id', $id)->count() . "\n";
}
