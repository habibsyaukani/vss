<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class AutoDataPullCommand extends Command
{
    protected $signature = 'auto-pull:run';
    protected $description = 'Auto pull data (Idle Alarm & GPS Track) alternately every 30 minutes';

    public function handle()
    {
        // Check if auto pull is enabled
        $enabled = SystemSetting::get('auto_pull_enabled', false);
        
        if (!$enabled) {
            $this->info('Auto pull is disabled. Skipping...');
            SystemSetting::set('auto_pull_status', 'disabled');
            return 0;
        }

        // Get last run time
        $lastRun = SystemSetting::get('auto_pull_last_run');
        $nextType = SystemSetting::get('auto_pull_next_type', 'idle'); // idle or gps
        $interval = SystemSetting::get('auto_pull_interval', 30); // minutes

        // Check if enough time has passed (30 minutes)
        if ($lastRun) {
            $lastRunTime = Carbon::parse($lastRun);
            $minutesSinceLastRun = $lastRunTime->diffInMinutes(now());
            
            if ($minutesSinceLastRun < $interval) {
                $remaining = $interval - $minutesSinceLastRun;
                $this->info("Waiting... {$remaining} minutes until next pull");
                
                // Set status to waiting
                SystemSetting::set('auto_pull_status', 'waiting');
                SystemSetting::set('auto_pull_next_in_minutes', $remaining);
                
                return 0;
            }
        }

        // Execute pull based on next type
        $this->info("Starting auto pull: {$nextType}");
        
        // Set status to running
        SystemSetting::set('auto_pull_status', 'running');
        SystemSetting::set('auto_pull_current_type', $nextType);
        SystemSetting::set('auto_pull_progress_percent', 0);
        SystemSetting::set('auto_pull_started_at', now()->toDateTimeString());
        
        try {
            if ($nextType === 'idle') {
                $this->pullIdleAlarm();
                // Next time pull GPS
                SystemSetting::set('auto_pull_next_type', 'gps');
            } else {
                $this->pullGpsTrack();
                // Next time pull Idle
                SystemSetting::set('auto_pull_next_type', 'idle');
            }

            // Update last run time
            SystemSetting::set('auto_pull_last_run', now()->toDateTimeString());
            SystemSetting::set('auto_pull_last_success', now()->toDateTimeString());
            SystemSetting::set('auto_pull_last_error', null);
            SystemSetting::set('auto_pull_status', 'completed');
            SystemSetting::set('auto_pull_progress_percent', 100);

            $this->info("Auto pull completed successfully!");
            
            // Reset to waiting after 5 seconds
            sleep(5);
            SystemSetting::set('auto_pull_status', 'waiting');
            
            return 0;

        } catch (\Exception $e) {
            $this->error("Auto pull failed: " . $e->getMessage());
            Log::error("Auto Pull Error: " . $e->getMessage());
            
            SystemSetting::set('auto_pull_last_error', $e->getMessage());
            SystemSetting::set('auto_pull_last_error_at', now()->toDateTimeString());
            SystemSetting::set('auto_pull_status', 'error');
            SystemSetting::set('auto_pull_progress_percent', 0);
            
            return 1;
        }
    }

    protected function pullIdleAlarm()
    {
        $this->info("Pulling Idle Alarm data...");
        
        // Update progress
        SystemSetting::set('auto_pull_status', 'running');
        SystemSetting::set('auto_pull_current_type', 'idle');
        SystemSetting::set('auto_pull_progress_message', 'Preparing to pull Idle Alarm data...');
        SystemSetting::set('auto_pull_progress_percent', 10);
        
        // Date range: last 48 hours
        $endDate = now();
        $startDate = now()->subHours(48);

        $this->info("Date range: {$startDate->format('Y-m-d H:i')} to {$endDate->format('Y-m-d H:i')}");

        SystemSetting::set('auto_pull_progress_message', 'Pulling Idle Alarm data (48 hours)...');
        SystemSetting::set('auto_pull_progress_percent', 30);

        // Execute pull using existing command (realtime pull)
        Artisan::call('howen:pull-alarms-realtime', [
            '--hours' => 48,
        ]);
        
        SystemSetting::set('auto_pull_progress_percent', 80);
        
        $output = Artisan::output();
        $this->info($output);

        // Count records from output
        $totalRecords = 0;
        if (preg_match('/Fetched (\d+) records/', $output, $matches)) {
            $totalRecords = (int)$matches[1];
        }

        $this->info("Idle Alarm pull completed: {$totalRecords} records");
        
        SystemSetting::set('auto_pull_idle_last_count', $totalRecords);
        SystemSetting::set('auto_pull_current_records', $totalRecords);
        SystemSetting::set('auto_pull_progress_message', "Idle Alarm completed: {$totalRecords} records");
        SystemSetting::set('auto_pull_progress_percent', 100);
    }

    protected function pullGpsTrack()
    {
        $this->info("Pulling GPS Track data...");
        
        // Update progress
        SystemSetting::set('auto_pull_status', 'running');
        SystemSetting::set('auto_pull_current_type', 'gps');
        SystemSetting::set('auto_pull_progress_message', 'Preparing to pull GPS Track data...');
        SystemSetting::set('auto_pull_progress_percent', 10);
        
        // Date: today
        $date = now()->format('Y-m-d');

        $this->info("Date: {$date}");

        SystemSetting::set('auto_pull_progress_message', 'Pulling GPS Track data (today)...');
        SystemSetting::set('auto_pull_progress_percent', 30);

        // Execute pull using existing command
        Artisan::call('vss:pull-gps-tracks', [
            '--date' => $date,
            '--devices' => 'all',
        ]);
        
        SystemSetting::set('auto_pull_progress_percent', 80);
        
        $output = Artisan::output();
        $this->info($output);

        // Count records from output
        $totalRecords = 0;
        if (preg_match('/Total records saved: (\d+)/', $output, $matches)) {
            $totalRecords = (int)$matches[1];
        }

        $this->info("GPS Track pull completed: {$totalRecords} records");
        
        SystemSetting::set('auto_pull_gps_last_count', $totalRecords);
        SystemSetting::set('auto_pull_current_records', $totalRecords);
        SystemSetting::set('auto_pull_progress_message', "GPS Track completed: {$totalRecords} records");
        SystemSetting::set('auto_pull_progress_percent', 100);
    }
}
