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
                            {--date= : Specific date to pull (YYYY-MM-DD), defaults to today}
                            {--devices= : Comma-separated device IDs, or "all" for all active devices}
                            {--limit=0 : Limit number of devices (0 = no limit)}
                            {--hours= : Look back X hours from now (overrides --date)}';
    
    protected $description = 'Pull GPS track data efficiently from VSS API (loops devices but shows better progress)';

    private string $baseUrl;
    private string $token;

    public function handle()
    {
        try {
            $this->baseUrl = config('vss.base_url', 'http://vss.ptdigital.co.id');
            
            // Get date
            $date = $this->option('date') ?: date('Y-m-d');
            $deviceFilter = $this->option('devices') ?: 'all';
            $limit = (int)$this->option('limit') ?: 0;
            $hours = (int)$this->option('hours') ?: 0;
            
            if ($hours > 0) {
                // Tarik data sekian jam terakhir saja
                $beginTime = now()->subHours($hours);
                $endTime = now();
                $date = now()->format('Y-m-d');
            } else {
                // Tarik data 1 hari penuh
                $beginTime = Carbon::parse("{$date} 00:00:00");
                $endTime = Carbon::parse("{$date} 23:59:59");
            }

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
            
            $deviceIds = $devices->pluck('device_id')->toArray();
            
            $this->info("📡 Fetching GPS data for {$devices->count()} devices concurrently...");
            
            $result = $syncService->syncMultipleDevicesFast(
                $this->token,
                $deviceIds, 
                $beginTime->format('Y-m-d H:i:s'),
                $endTime->format('Y-m-d H:i:s')
            );
            
            $totalFetched = $result['total_fetched'] ?? 0;
            $totalSaved = $result['total_saved'] ?? 0;
            $devicesWithData = $result['success_devices'] ?? 0;
            $deviceErrors = $result['errors'] ?? [];

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
