<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$req = request();
$req->merge(['start_date'=>'2026-06-06', 'end_date'=>'2026-06-06']);
$c = new App\Http\Controllers\Frontend\IdleAlarmController();
$data = $c->data($req)->getData();

if (!empty($data->data)) {
    echo json_encode($data->data[0], JSON_PRETTY_PRINT);
} else {
    echo "No data";
}
