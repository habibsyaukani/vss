<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use App\Services\TracksolidApiService;
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
    public function handle(TracksolidApiService $apiService, TracksolidTrackService $trackService)
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

        // Fetch official IMEI list from Tracksolid API (cached for 30 minutes when successful)
        $officialImeis = $apiService->getOfficialDeviceImeis();

        if ($imei) {
            if (!in_array($imei, $officialImeis)) {
                $this->error("IMEI/ID {$imei} is not present in official Tracksolid device list.");
                Log::warning("[Track Pull] Specified IMEI {$imei} is not in official Tracksolid device list.");
                return 1;
            }

            $device = Device::where('status', 'active')
                ->where('imei', $imei)
                ->first();
            if (!$device) {
                $this->warn("Warning: Official Tracksolid device with IMEI {$imei} has no local Device mapping in DB.");
                Log::warning("[Track Pull] Official Tracksolid device IMEI {$imei} has no local Device mapping in database.");
                return 1;
            }

            $devices = collect([$device]);
        } else {
            if (empty($officialImeis)) {
                $this->error("Cannot pull tracks: Official Tracksolid device list could not be retrieved or is empty.");
                return 1;
            }

            // Pull only active local devices whose imei exists in the official Tracksolid IMEI list
            $devices = Device::where('status', 'active')
                             ->whereIn('imei', $officialImeis)
                             ->get();

            // Log warning for any official Tracksolid device that has no local active mapping
            $matchedImeis = $devices->pluck('imei')->filter()->toArray();
            $matchedSet = array_flip($matchedImeis);

            foreach ($officialImeis as $offImei) {
                if (!isset($matchedSet[$offImei])) {
                    $this->warn("Warning: Official Tracksolid device IMEI {$offImei} has no local active Device mapping in DB.");
                    Log::warning("[Track Pull] Official Tracksolid device IMEI {$offImei} has no local active Device mapping in database.");
                }
            }

            $this->info("Found " . $devices->count() . " active local devices matching official Tracksolid device list (out of " . count($officialImeis) . " official devices).");
        }

        if ($devices->isEmpty()) {
            $this->warn("No matching active devices to process.");
            return 0;
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
