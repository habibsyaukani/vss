<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$monthStart = Carbon\Carbon::create(2026, 6, 1)->startOfMonth();
$monthEnd = Carbon\Carbon::create(2026, 6, 1)->endOfMonth();

echo "Date Range: " . $monthStart->toDateTimeString() . " to " . $monthEnd->toDateTimeString() . "\n";

$count = DB::table('alarm_raw')->whereBetween('created_at', [$monthStart, $monthEnd])->count();
$type32 = DB::table('alarm_raw')->whereBetween('created_at', [$monthStart, $monthEnd])->where('alarm_type', 32)->count();

echo "Total alarm_raw June: $count, Type 32: $type32\n";

// Sample of created_at values in June
$samples = DB::table('alarm_raw')->whereMonth('created_at', 6)->whereYear('created_at', 2026)->limit(3)->pluck('created_at');
echo "Samples from whereMonth: " . json_encode($samples) . "\n";

// Get guids and see how many are in idle_alarms
$rawGuids = DB::table('alarm_raw')
    ->whereBetween('created_at', [$monthStart, $monthEnd])
    ->where('alarm_type', 32)
    ->limit(10)
    ->pluck('guid')
    ->toArray();

echo "Sample guids: " . json_encode($rawGuids) . "\n";

$processedGuids = DB::table('idle_alarms')
    ->whereIn('guid', $rawGuids)
    ->pluck('guid')
    ->toArray();

echo "Processed guids: " . json_encode($processedGuids) . "\n";

// GPS Tracks test
if (DB::getSchemaBuilder()->hasTable('gps_tracks_raw')) {
    $gpsCount = DB::table('gps_tracks_raw')->whereBetween('created_at', [$monthStart, $monthEnd])->count();
    echo "Total gps_tracks_raw June: $gpsCount\n";
    
    $sample = DB::table('gps_tracks_raw')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->select('device_id', 'gps_time')
                ->limit(10)
                ->get();
    echo "Sample GPS raw: " . json_encode($sample) . "\n";

    $validatedCount = 0;
    foreach ($sample as $raw) {
        $exists = DB::table('gps_tracks')
            ->where('device_id', $raw->device_id)
            ->where('gps_time', $raw->gps_time)
            ->exists();
        
        if ($exists) {
            $validatedCount++;
        }
    }
    echo "Validated GPS count for sample of 10: $validatedCount\n";
}
