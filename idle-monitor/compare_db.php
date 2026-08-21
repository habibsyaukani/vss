<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$t = \App\Models\IdleAlarm::where('device_id', 'like', '864%')->first();
$h = \App\Models\IdleAlarm::where('device_id', 'not like', '864%')->where('device_id', 'not like', '865%')->first();

echo json_encode([
    'tracksolid' => $t ? $t->toArray() : null,
    'howen' => $h ? $h->toArray() : null
], JSON_PRETTY_PRINT);
