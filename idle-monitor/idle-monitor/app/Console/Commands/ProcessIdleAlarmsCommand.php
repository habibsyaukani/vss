<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProcessIdleAlarmsCommand extends Command
{
    protected $signature = 'howen:process-idle-alarms';
    protected $description = 'Process idle alarms from alarm_raw to idle_alarms table';

    public function handle()
    {
        $this->info('Starting idle alarm processing...');
        $this->newLine();

        try {
            $job = new \App\Jobs\ProcessIdleAlarmJob();
            $job->handle();
            
            $this->newLine();
            $this->info("✅ Idle alarm processing completed successfully!");
            
            // Show process logs
            $logs = \App\Models\ImportLog::where('job_name', 'ProcessIdleAlarmJob')
                ->latest()
                ->limit(3)
                ->get();
            
            $this->table(
                ['Job', 'Status', 'Records', 'Message'],
                $logs->map(fn($log) => [
                    $log->job_name,
                    $log->status,
                    $log->total_record,
                    $log->message,
                ])->toArray()
            );
            
            // Show idle_alarms count
            $idleCount = \App\Models\IdleAlarm::count();
            $this->info("Total idle alarms processed: {$idleCount}");
            
            if ($idleCount > 0) {
                $this->newLine();
                $this->info("Sample processed alarms:");
                $alarms = \App\Models\IdleAlarm::limit(3)->get();
                foreach ($alarms as $alarm) {
                    $duration = $alarm->duration_minutes;
                    $this->line("  [{$alarm->guid}] {$alarm->device_name} - {$duration}min - {$alarm->alarm_status}");
                }
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Idle alarm processing failed: " . $e->getMessage());
            return 1;
        }
    }
}
