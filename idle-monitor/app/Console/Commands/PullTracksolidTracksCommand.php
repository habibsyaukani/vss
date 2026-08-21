<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use App\Services\TracksolidTrackService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PullTracksolidTracksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vss:pull-tracksolid-tracks {imei?} {--hours=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull raw GPS tracks from Tracksolid Pro API and store in gps_tracks_raw';

    /**
     * Execute the console command.
     */
    public function handle(TracksolidTrackService $trackService)
    {
        $imei = $this->argument('imei');
        $hours = (int) $this->option('hours');

        // Tracksolid track API usually expects UTC time
        $endTime = Carbon::now('UTC');
        $beginTime = $endTime->copy()->subHours($hours);

        $endTimeStr = $endTime->format('Y-m-d H:i:s');
        $beginTimeStr = $beginTime->format('Y-m-d H:i:s');

        $this->info("Starting Tracksolid Track Pull");
        $this->info("Time Range: {$beginTimeStr} to {$endTimeStr}");

        if ($imei) {
            $devices = Device::where('imei', $imei)->orWhere('device_id', $imei)->get();
            if ($devices->isEmpty()) {
                $this->error("Device with IMEI/ID {$imei} not found in local DB.");
                return 1;
            }
        } else {
            // Pull for all active devices
            $devices = Device::where('status', 'active')
                             ->whereNotNull('imei')
                             ->where('imei', '!=', '')
                             ->get();
            $this->info("Found {$devices->count()} active devices.");
        }

        $totalFetched = 0;
        $totalInserted = 0;
        
        $bar = $this->output->createProgressBar(count($devices));
        $bar->start();

        foreach ($devices as $device) {
            $this->info("\nProcessing Device: {$device->device_name} ({$device->imei})");
            
            $stats = $trackService->syncTracks($device->imei, $beginTimeStr, $endTimeStr);
            
            $totalFetched += $stats['total_fetched'];
            $totalInserted += $stats['total_inserted'];

            if (!empty($stats['errors'])) {
                foreach ($stats['errors'] as $error) {
                    $this->error("  Error: {$error}");
                }
            }
            
            $this->line("  Fetched: {$stats['total_fetched']}, Inserted: {$stats['total_inserted']}");
            $bar->advance();
            
            // Sleep slightly to avoid rate limit
            usleep(200000); // 200ms
        }

        $bar->finish();
        $this->info("\n");
        $this->info("Track Pull Completed!");
        $this->info("Total Fetched: {$totalFetched}");
        $this->info("Total Inserted: {$totalInserted}");

        Log::info("[Track Pull] Completed. Fetched: {$totalFetched}, Inserted: {$totalInserted}");
        
        return 0;
    }
}
