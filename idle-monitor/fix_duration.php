<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$alarms = App\Models\IdleAlarm::all();
$fixed = 0;
foreach($alarms as $a) {
    $d = 0;
    if (preg_match('/dur:\s*(\d+)/', $a->end_detail, $m)) {
        $d = (int)$m[1];
    } elseif (preg_match('/dur:\s*(\d+)/', $a->start_detail, $m)) {
        $d = (int)$m[1];
    }
    
    if ($d > 0 && $a->duration_seconds != $d) {
        $a->duration_seconds = $d;
        $a->duration_minutes = floor($d / 60);
        $a->save();
        $fixed++;
    }
}
echo "Done fixing existing data. Fixed $fixed records.\n";
