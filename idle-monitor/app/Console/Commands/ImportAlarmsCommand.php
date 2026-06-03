<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportAlarmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'howen:import-alarms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import alarms from Howen API to alarm_raw table with pagination';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting alarm import...');
        $this->newLine();

        try {
            $job = new \App\Jobs\ImportAlarmJob();
            $job->handle();
            
            $this->newLine();
            $this->info("✅ Alarm import completed successfully!");
            
            // Show import logs
            $logs = \App\Models\ImportLog::where('job_name', 'ImportAlarmJob')
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
            
            // Show alarm_raw count
            $alarmRawCount = \App\Models\AlarmRaw::count();
            $this->info("Total alarms in alarm_raw: {$alarmRawCount}");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Alarm import failed: " . $e->getMessage());
            return 1;
        }
    }
}
