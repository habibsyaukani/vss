<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cols = Illuminate\Support\Facades\DB::select('SHOW COLUMNS FROM idle_alarms');
foreach($cols as $col) {
    if ($col->Field === 'end_speed') {
        echo "end_speed nullable: " . $col->Null . "\n";
    }
}
