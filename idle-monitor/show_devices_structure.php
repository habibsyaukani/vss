<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "===========================================\n";
echo "  DEVICES TABLE STRUCTURE\n";
echo "===========================================\n\n";

// Get table columns
$columns = DB::select("DESCRIBE devices");

echo "Columns in 'devices' table:\n";
echo "-------------------------------------------\n";
foreach ($columns as $column) {
    echo "• {$column->Field} ({$column->Type}) - {$column->Null} - {$column->Key}\n";
}

echo "\n";
echo "Sample data (first 3 rows):\n";
echo "-------------------------------------------\n";
$samples = DB::table('devices')->limit(3)->get();

if ($samples->count() > 0) {
    foreach ($samples as $sample) {
        print_r($sample);
        echo "\n";
    }
} else {
    echo "No data in table yet.\n";
}
?>
