<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Device Filter Options\n";
echo "=====================\n\n";

// Group Names
echo "1. Group Names:\n";
$groups = DB::table('devices')->distinct()->pluck('group_name')->filter()->sort()->values();
foreach ($groups as $group) {
    echo "   - {$group}\n";
}

echo "\n2. Series:\n";
$series = DB::table('devices')->distinct()->pluck('series')->filter()->sort()->values();
foreach ($series as $s) {
    echo "   - {$s}\n";
}

echo "\n3. Locations:\n";
$locations = DB::table('devices')->distinct()->pluck('location')->filter()->sort()->values();
foreach ($locations as $loc) {
    echo "   - {$loc}\n";
}

echo "\n=====================\n";
