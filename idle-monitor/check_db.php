<?php
require __DIR__.'/vendor/autoload.php';
\ = require_once __DIR__.'/bootstrap/app.php';
\ = \->make(Illuminate\Contracts\Console\Kernel::class);
\->bootstrap();

echo "Total AlarmRaw: " . \App\Models\AlarmRaw::count() . "\n";
echo "Pending Idle (type 32, state 0, not processed): " . \App\Models\AlarmRaw::where('alarm_type', 32)->where('alarm_state', 0)->where('is_processed', 0)->count() . "\n";
echo "Processed Idle (type 32, state 0, processed): " . \App\Models\AlarmRaw::where('alarm_type', 32)->where('alarm_state', 0)->where('is_processed', 1)->count() . "\n";
echo "Failed Jobs: " . DB::table('failed_jobs')->count() . "\n";
echo "Pending Jobs: " . DB::table('jobs')->count() . "\n";
