<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance and receives
| all of the arguments and options passed to the command.
|
*/

Artisan::command('check:data', function () {
    $alarmCount = \App\Models\AlarmRaw::count();
    $idleCount = \App\Models\IdleAlarm::count();
    $importCount = \App\Models\ImportLog::count();
    $jobsCount = \DB::table('jobs')->count();
    
    $this->info("AlarmRaw count: {$alarmCount}");
    $this->info("IdleAlarm count: {$idleCount}");
    $this->info("ImportLog count: {$importCount}");
    $this->info("Jobs in queue: {$jobsCount}");
    
    $this->newLine();
    $this->info("Recent ImportLog entries:");
    
    $logs = \App\Models\ImportLog::latest()->limit(5)->get();
    foreach ($logs as $log) {
        $this->line("- {$log->job_name}: {$log->status} ({$log->total_record} records) - {$log->message}");
    }
    
    if ($idleCount > 0) {
        $this->newLine();
        $this->info("Sample idle alarms:");
        $alarms = \App\Models\IdleAlarm::limit(3)->get();
        foreach ($alarms as $alarm) {
            $this->line("  GUID: {$alarm->guid}, Device: {$alarm->device_name}, Duration: {$alarm->duration_minutes}min, Status: {$alarm->alarm_status}");
        }
    }
})->describe('Check database data status');
