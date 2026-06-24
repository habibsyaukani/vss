<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Device;

// Master list dengan group dari user
$masterData = [
    // BUS - GPE
    'GPE-B-806' => 'BUS - GPE', 'GPE-B-807' => 'BUS - GPE', 'GPE-B-808' => 'BUS - GPE', 'GPE-B-809' => 'BUS - GPE',
    'GPE-B-811' => 'BUS - GPE', 'GPE-B-812' => 'BUS - GPE', 'GPE-B-813' => 'BUS - GPE', 'GPE-B-815' => 'BUS - GPE',
    'GPE-B-816' => 'BUS - GPE', 'GPE-B-818' => 'BUS - GPE', 'GPE-B-819' => 'BUS - GPE', 'GPE-B-820' => 'BUS - GPE',
    'GPE-B-821' => 'BUS - GPE', 'GPE-B-822' => 'BUS - GPE', 'GPE-B-825' => 'BUS - GPE', 'GPE-B-826' => 'BUS - GPE',
    'GPE-B-827' => 'BUS - GPE', 'GPE-B-828' => 'BUS - GPE', 'GPE-B-829' => 'BUS - GPE', 'GPE-B-830' => 'BUS - GPE',
    'GPE-B-831' => 'BUS - GPE', 'GPE-B-832' => 'BUS - GPE', 'GPE-B-833' => 'BUS - GPE', 'GPE-B-835' => 'BUS - GPE',
    'GPE-B-836' => 'BUS - GPE', 'GPE-B-837' => 'BUS - GPE', 'GPE-B-838' => 'BUS - GPE', 'GPE-B-839' => 'BUS - GPE',
    'GPE-B-856' => 'BUS - GPE', 'GPE-B-857' => 'BUS - GPE', 'GPE-B-860' => 'BUS - GPE', 'GPE-B-866' => 'BUS - GPE',
    'GPE-B-867' => 'BUS - GPE', 'GPE-B-871' => 'BUS - GPE', 'GPE-B-873' => 'BUS - GPE', 'GPE-B-876' => 'BUS - GPE',
    'GPE-B-877' => 'BUS - GPE', 'GPE-B-878' => 'BUS - GPE', 'GPE-B-879' => 'BUS - GPE', 'GPE-B-880' => 'BUS - GPE',
    'GPE-B-881' => 'BUS - GPE', 'GPE-B-882' => 'BUS - GPE', 'GPE-B-883' => 'BUS - GPE', 'GPE-B-885' => 'BUS - GPE',
    'GPE-B-886' => 'BUS - GPE', 'GPE-B-887' => 'BUS - GPE',
    // FT - GPE
    'GPE-FT-815' => 'FT - GPE', 'GPE-FT-860' => 'FT - GPE', 'GPE-FT-861' => 'FT - GPE', 'GPE-FT-865' => 'FT - GPE',
    'GPE-FT-866' => 'FT - GPE', 'GPE-FT-867' => 'FT - GPE', 'GPE-FT-868' => 'FT - GPE', 'GPE-FT-869' => 'FT - GPE',
    'GPE-FT-870' => 'FT - GPE', 'GPE-FT-871' => 'FT - GPE', 'GPE-FT-872' => 'FT - GPE', 'GPE-FT-873' => 'FT - GPE',
    // GFTH -> DT - GPE
    'GPE-GFTH-875' => 'DT - GPE',
    // LV -> PATROL - GPE
    'GPE-LV-890' => 'PATROL - GPE', 'GPE-LV-891' => 'PATROL - GPE', 'GPE-LV-892' => 'PATROL - GPE', 'GPE-LV-910' => 'PATROL - GPE',
    // WT - GPE
    'GPE-WT-836' => 'WT - GPE', 'GPE-WT-855' => 'WT - GPE',
];

// All GPE-HD-* -> HD - GPE
// All GPE-DT-* -> DT - GPE

$updated = 0;
$allDevices = Device::all();
foreach($allDevices as $dev) {
    $name = trim($dev->device_name);
    $group = $masterData[$name] ?? null;
    
    // Auto-assign by prefix if not in masterData
    if (!$group) {
        if (str_starts_with($name, 'GPE-HD-')) $group = 'HD - GPE';
        elseif (str_starts_with($name, 'GPE-DT-')) $group = 'DT - GPE';
        elseif (str_starts_with($name, 'GPE-B-'))  $group = 'BUS - GPE';
        elseif (str_starts_with($name, 'GPE-FT-')) $group = 'FT - GPE';
        elseif (str_starts_with($name, 'GPE-WT-')) $group = 'WT - GPE';
        elseif (str_starts_with($name, 'GPE-LV-')) $group = 'PATROL - GPE';
    }
    
    if ($group && $dev->group_name !== $group) {
        $dev->group_name = $group;
        $dev->save();
        $updated++;
    }
}

echo "Updated: $updated devices\n";

// Show final counts by group
$counts = Illuminate\Support\Facades\DB::select('SELECT group_name, COUNT(*) as c FROM devices GROUP BY group_name ORDER BY group_name');
echo "\nFinal group counts:\n";
$total = 0;
foreach($counts as $c) {
    echo "  " . ($c->group_name ?? 'NULL') . ": {$c->c}\n";
    $total += $c->c;
}
echo "TOTAL: $total\n";
