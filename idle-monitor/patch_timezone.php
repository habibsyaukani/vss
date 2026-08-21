<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Add 8 hours to all tracksolid alarms
DB::update("
    UPDATE idle_alarms 
    SET 
        starting_time = DATE_ADD(starting_time, INTERVAL 8 HOUR),
        ending_time = DATE_ADD(ending_time, INTERVAL 8 HOUR),
        report_time = DATE_ADD(report_time, INTERVAL 8 HOUR)
    WHERE device_id LIKE '864%' OR device_id LIKE '865%'
");

echo "Successfully added 8 hours to all Tracksolid records in the database.\n";
