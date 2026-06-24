<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$devs = DB::select('SELECT group_name, COUNT(*) as c FROM devices GROUP BY group_name ORDER BY group_name');
$total = 0;
foreach($devs as $d) {
    echo ($d->group_name ?? 'NULL') . ': ' . $d->c . "\n";
    $total += $d->c;
}
echo "Total: $total\n";
