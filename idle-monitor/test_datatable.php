<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\IdleAlarm;
use Illuminate\Support\Facades\DB;

DB::enableQueryLog();

$query = IdleAlarm::select('idle_alarms.*')
            ->leftJoin('devices', 'idle_alarms.device_id', '=', 'devices.device_id');

// Simulate date filter
$start = '2026-08-18 00:00:00';
$query->where('idle_alarms.starting_time', '>=', $start);
$end = '2026-08-18 23:59:59';
$query->where('idle_alarms.starting_time', '<=', $end);

// Simulate ALL GPE checked -> All device IDs!
// Let's not add device_ids filter first
$count = $query->count();
echo "Total without device_ids filter: $count\n";

// Let's see what happens if we add the device_ids filter with the devices array
// The controller gets device_ids from the selected devices.
// The device_id in the DB is string '865478070069424'
$allDevices = \App\Models\Device::pluck('device_id')->toArray();
$cleanIds = array_map(function($id) { return ltrim((string)$id, '0'); }, $allDevices);

$query->whereIn('idle_alarms.device_id', $cleanIds);
$count2 = $query->count();
echo "Total with device_ids filter: $count2\n";

print_r(DB::getQueryLog());
