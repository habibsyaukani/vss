<?php

namespace App\Console\Commands;

use App\Console\Commands\PullIdleAlarmsDateRangeCommand;
use App\Models\SystemSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PullIdleAlarmsRealtimeLoopCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pull:realtime-loop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Continuously pull idle alarm data in realtime (loop every 3 minutes)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting continuous realtime data pull...');
        $this->info('📊 Pulling data every 3 minutes');
        $this->info('⚠️  Press Ctrl+C to stop');
        
        $iteration = 0;
        
        while (true) {
            $iteration++;
            $startTime = now();
            
            $this->info("\n" . str_repeat('=', 60));
            $this->info("🔄 Iteration #{$iteration} - " . $startTime->format('Y-m-d H:i:s'));
            $this->info(str_repeat('=', 60));
            
            try {
                // Call the date range pull command for last 48 hours
                $this->call('howen:pull-alarms-date-range', [
                    '--from' => now()->subHours(48)->format('Y-m-d'),
                    '--to' => now()->format('Y-m-d'),
                    '--concurrency' => 5,
                    '--parallel' => null  // Boolean flag, no value needed
                ]);
                
                // --- GPS TRACK SYNC ---
                $this->info("🛰️ Pulling GPS Tracks (last 2 hours)...");
                \App\Jobs\ImportGpsTrackJob::dispatchSync(2, 500);
                
                $this->info("🗺️ Processing GPS Tracks mapping...");
                \App\Jobs\ProcessGpsTrackJob::dispatchSync();
                // ----------------------
                
                $endTime = now();
                $duration = $endTime->diffInSeconds($startTime);
                
                $this->info("✅ Pull completed in {$duration} seconds");
                
                // Update system setting with success status
                SystemSetting::set('realtime_pull_last_success_at', $endTime->toDateTimeString());
                SystemSetting::set('realtime_pull_last_duration', $duration);
                
                Log::info("Realtime pull iteration #{$iteration} completed", [
                    'duration' => $duration,
                    'started_at' => $startTime,
                    'finished_at' => $endTime
                ]);
                
            } catch (\Exception $e) {
                $this->error("❌ Error during pull: " . $e->getMessage());
                
                // Log detailed error
                Log::error("Realtime pull iteration #{$iteration} failed", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'iteration' => $iteration,
                    'timestamp' => now()->toDateTimeString()
                ]);
                
                // Update system setting to show error status
                SystemSetting::set('realtime_pull_last_error', $e->getMessage());
                SystemSetting::set('realtime_pull_last_error_at', now()->toDateTimeString());
                
                // If critical error (command not found), stop the loop
                if (str_contains($e->getMessage(), 'is not defined') || 
                    str_contains($e->getMessage(), 'does not exist')) {
                    $this->error("❌ CRITICAL ERROR: Command or argument not found. Stopping loop.");
                    $this->error("Please check the command configuration and restart.");
                    SystemSetting::set('realtime_pull_status', 'stopped_error');
                    return Command::FAILURE;
                }
            }
            
            // Wait 3 minutes before next pull
            $this->info("⏳ Waiting 3 minutes before next pull...");
            sleep(180); // 3 minutes = 180 seconds
        }
        
        return Command::SUCCESS;
    }
}
