<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GpsTrackRaw;
use App\Models\GpsTrack;
use App\Jobs\ProcessGpsTrackJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PullGpsTracksCommand extends Command
{
    protected $signature = 'vss:pull-gps-tracks 
                            {--date= : Specific date (Y-m-d), default: yesterday}
                            {--devices= : Comma-separated device IDs, or "all" for all active devices}
                            {--limit=0 : Limit number of devices (0 = no limit)}';
    
    protected $description = 'Pull GPS track data efficiently from VSS API (loops devices but shows better progress)';

    private string $baseUrl;
    private string $token;

    public function handle()
    {
        try {
            $this->baseUrl = config('vss.base_url', 'http://vss.ptdigital.co.id');
            
            // Get date
            $date = $this->option('date') ?: now()->subDay()->format('Y-m-d');
            $deviceFilter = $this->option('devices') ?: 'all';
            $limit = (int)$this->option('limit') ?: 0;
            
            $beginTime = Carbon::parse("{$date} 00:00:00");
            $endTime = Carbon::parse("{$date} 23:59:59");

            $this->info("🗺️  Pull GPS Tracks - Efficient Method");
            $this->info("   Date: {$date}");
            $this->info("   Range: {$beginTime->format('Y-m-d H:i:s')} → {$endTime->format('Y-m-d H:i:s')}");
            $this->newLine();

            // Get token
            $this->info("🔐 Getting VSS authentication token...");
            $authService = app(\App\Services\VssAuthService::class);
            $this->token = $authService->getToken();
            $this->info("✅ Token obtained");
            $this->newLine();

            // Get devices
            $this->info("🚗 Loading devices...");
            $devicesQuery = \App\Models\Device::whereNotNull('device_id')
                ->orderBy('device_name');
            
            if ($deviceFilter !== 'all') {
                $deviceIds = explode(',', $deviceFilter);
                $devicesQuery->whereIn('device_id', $deviceIds);
            }
            
            if ($limit > 0) {
                $devicesQuery->limit($limit);
            }
            
            $devices = $devicesQuery->get();
            
            if ($devices->isEmpty()) {
                $this->warn("No active devices found");
                return 0;
            }
            
            $this->info("✅ Found {$devices->count()} devices");
            $this->newLine();

            // Sync each device with progress bar
            $this->info("📡 Fetching GPS data per device...");
            $bar = $this->output->createProgressBar($devices->count());
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %message%');
            $bar->setMessage('Starting...');
            $bar->start();

            $syncService = app(\App\Services\GpsTrackSyncService::class);
            $totalFetched = 0;
            $totalSaved = 0;
            $devicesWithData = 0;
            $deviceErrors = [];

            foreach ($devices as $device) {
                $bar->setMessage($device->device_name);
                
                try {
                    $result = $syncService->syncDevice(
                        $this->token,
                        $device->device_id,
                        $beginTime->format('Y-m-d H:i:s'),
                        $endTime->format('Y-m-d H:i:s')
                    );

                    $totalFetched += $result['total_fetched'];
                    $totalSaved += $result['total_saved'];
                    
                    if ($result['total_saved'] > 0) {
                        $devicesWithData++;
                    }
                    
                    // Delay 50ms between devices
                    usleep(50000);
                    
                } catch (\Exception $e) {
                    $deviceErrors[] = [
                        'device_name' => $device->device_name,
                        'error' => $e->getMessage(),
                    ];
                }
                
                $bar->advance();
            }

            $bar->setMessage('Completed');
            $bar->finish();
            $this->newLine(2);

            // Summary
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("📊 SUMMARY");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("✅ Devices processed: {$devices->count()}");
            $this->info("✅ Devices with data: {$devicesWithData}");
            $this->info("✅ Total records fetched: {$totalFetched}");
            $this->info("✅ Total records saved: {$totalSaved}");
            
            if (!empty($deviceErrors)) {
                $this->warn("⚠️  Errors: " . count($deviceErrors) . " devices failed");
            }
            $this->newLine();

            if ($totalSaved === 0) {
                $this->warn("ℹ️  No GPS data found for {$date}");
                $this->warn("   Possible reasons:");
                $this->warn("   - Devices were offline on this date");
                $this->warn("   - GPS devices not transmitting");
                $this->warn("   - Data not yet synced to VSS");
                return 0;
            }

            // Dispatch process job
            $this->info("🔄 Dispatching ProcessGpsTrackJob to Queue...");
            ProcessGpsTrackJob::dispatch();
            $this->info("✅ Job dispatched");
            $this->newLine();

            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("✅ COMPLETED SUCCESSFULLY");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            Log::error('PullGpsTracksCommand failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }

    private function parseTime(?string $value): ?Carbon
    {
        if (empty($value)) return null;
        
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
