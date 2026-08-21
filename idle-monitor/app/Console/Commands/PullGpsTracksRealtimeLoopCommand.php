<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\VssAuthService;
use App\Services\GpsTrackSyncService;
use App\Models\Device;
use App\Jobs\ProcessGpsTrackJob;

class PullGpsTracksRealtimeLoopCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vss:pull-gps-tracks-loop {--interval=30 : Interval in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Continuously pull GPS track data in realtime (HTTP Polling as websocket fallback)';

    /**
     * Execute the console command.
     */
    public function handle(VssAuthService $authService, GpsTrackSyncService $syncService)
    {
        $interval = (int)$this->option('interval');
        $this->info('🚀 Starting continuous realtime GPS data pull (HTTP Polling)...');
        $this->info("📊 Pulling data every {$interval} seconds");
        $this->info('⚠️  Press Ctrl+C to stop');
        
        $iteration = 0;
        
        while (true) {
            $iteration++;
            $startTime = now();
            $duration = 0;
            
            $this->info("\n" . str_repeat('=', 60));
            $this->info("🔄 Iteration #{$iteration} - " . $startTime->format('Y-m-d H:i:s'));
            $this->info(str_repeat('=', 60));
            
            try {
                $token = $authService->getToken();
                
                if (!$token) {
                    $this->error("❌ Failed to get VSS authentication token.");
                } else {
                    $devices = Device::whereNotNull('device_id')->pluck('device_id')->toArray();
                    
                    if (empty($devices)) {
                        $this->warn("⚠️  No active devices found.");
                    } else {
                        // Tarik data 10 menit terakhir saja agar sangat ringan & cepat
                        $beginTime = now()->subMinutes(10)->format('Y-m-d H:i:s');
                        $endTime = now()->format('Y-m-d H:i:s');
                        
                        $this->info("📡 Fetching GPS for " . count($devices) . " devices...");
                        
                        $result = $syncService->syncMultipleDevicesFast($token, $devices, $beginTime, $endTime);
                        
                        $totalFetched = $result['total_fetched'] ?? 0;
                        $totalSaved = $result['total_saved'] ?? 0;
                        
                        $this->info("✅ Fetched: {$totalFetched} records | Saved: {$totalSaved} new records");
                        
                        // Jika ada data baru yang masuk, proses mapping ke idle alarm/tracking
                        if ($totalSaved > 0) {
                            $this->info("🔄 Dispatching ProcessGpsTrackJob...");
                            ProcessGpsTrackJob::dispatchSync();
                        }
                    }
                }
                
                $duration = now()->diffInSeconds($startTime);
                $this->info("✅ Iteration completed in {$duration} seconds");
                
            } catch (\Exception $e) {
                $this->error("❌ Error during pull: " . $e->getMessage());
                Log::error("Realtime GPS pull failed", ['error' => $e->getMessage()]);
            }
            
            $wait = max(0, $interval - $duration);
            if ($wait > 0) {
                $this->info("⏳ Waiting {$wait} seconds before next pull...");
                sleep($wait);
            }
        }
        
        return Command::SUCCESS;
    }
}
