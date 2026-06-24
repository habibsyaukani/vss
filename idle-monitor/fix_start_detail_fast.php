<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Fix alarm_raw where start_detail is null
$affectedRaw = DB::statement("
    UPDATE alarm_raw 
    SET start_detail = JSON_UNQUOTE(JSON_EXTRACT(raw_json, '$.alarmvalue'))
    WHERE alarm_type = 32 
    AND start_detail IS NULL 
    AND JSON_EXTRACT(raw_json, '$.alarmvalue') IS NOT NULL
");

echo "alarm_raw updated.\n";

// Fix idle_alarms by copying from alarm_raw
$affectedIdle = DB::statement("
    UPDATE idle_alarms ia
    INNER JOIN alarm_raw ar ON ia.guid = ar.guid
    SET ia.start_detail = ar.start_detail
    WHERE ia.start_detail IS NULL
    AND ar.start_detail IS NOT NULL
");

echo "idle_alarms updated.\n";
