<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "===========================================\n";
echo "  CHECK DEVICES TABLE\n";
echo "===========================================\n\n";

// 1. Cek total devices di database
$totalDb = DB::table('devices')->count();
echo "[1] Total devices in database: $totalDb\n\n";

// 2. Cek VOLVO di database
$volvoDb = DB::table('devices')->where('series', 'VOLVO')->count();
echo "[2] VOLVO series in database: $volvoDb\n\n";

// 3. Cek M.SERVICE di database
$mserviceDb = DB::table('devices')->where('location', 'M.SERVICE')->count();
echo "[3] M.SERVICE location in database: $mserviceDb\n\n";

// 4. Sample VOLVO units yang seharusnya ada
$volvoUnits = ['GPE932', 'GPE937', 'GPE951', 'GPE952', 'GPE953', 'GPE955', 'GPE998', 'GPE999', 
               'GPE825', 'GPE826', 'GPE827', 'GPE828', 'GPE829', 'GPE830', 'GPE831', 'GPE832'];

echo "[4] Checking specific VOLVO units in database:\n";
echo "-------------------------------------------\n";
foreach ($volvoUnits as $unit) {
    $device = DB::table('devices')->where('unit_code', $unit)->first();
    if ($device) {
        echo "  • $unit: series = '{$device->series}', location = '{$device->location}'\n";
    } else {
        echo "  • $unit: NOT FOUND\n";
    }
}

echo "\n[5] Checking M.SERVICE units (GPE1105-GPE1128):\n";
echo "-------------------------------------------\n";
$mserviceUnits = ['GPE1105', 'GPE1106', 'GPE1108', 'GPE1109', 'GPE1110', 
                  'GPE1112', 'GPE1113', 'GPE1125', 'GPE1126', 'GPE1127', 'GPE1128'];

foreach ($mserviceUnits as $unit) {
    $device = DB::table('devices')->where('unit_code', $unit)->first();
    if ($device) {
        echo "  • $unit: series = '{$device->series}', location = '{$device->location}'\n";
    } else {
        echo "  • $unit: NOT FOUND\n";
    }
}

echo "\n===========================================\n";
echo "  CHECK COMPLETED\n";
echo "===========================================\n";
