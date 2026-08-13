<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TracksolidAlarmService;
use App\Models\Device;
use Carbon\Carbon;

class PullTracksolidAlarmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pull:tracksolid-alarms {imei?} {--days=1 : Number of days to pull back from now}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull alarms from Tracksolid API and sync to idle_alarms table';

    /**
     * Execute the console command.
     */
    public function handle(TracksolidAlarmService $alarmService)
    {
        $imei = $this->argument('imei');
        $days = (int) $this->option('days');
        
        $beginTime = Carbon::now()->subDays($days)->format('Y-m-d H:i:s');
        $endTime = Carbon::now()->format('Y-m-d H:i:s');

        $imeisToFetch = [];

        if ($imei) {
            $imeisToFetch[] = $imei;
        } else {
            // Fetch all tracksolid devices (limit to first 100 as per API spec for single request)
            $this->info("No specific IMEI provided, fetching batch from database...");
            $imeisToFetch = Device::whereRaw('LENGTH(device_id) > 10')->pluck('device_id')->toArray();
        }

        if (empty($imeisToFetch)) {
            $this->error("No valid IMEIs found to fetch alarms.");
            return Command::FAILURE;
        }

        $this->info("Pulling alarms from {$beginTime} to {$endTime}");
        
        // Process in chunks of 50 to avoid API limits on long URL strings
        $chunks = array_chunk($imeisToFetch, 50);
        
        $totalFetched = 0;
        $totalInserted = 0;

        foreach ($chunks as $chunk) {
            $imeiString = implode(',', $chunk);
            $this->info("Fetching for chunk of " . count($chunk) . " devices...");
            
            $stats = $alarmService->syncAlarms($imeiString, $beginTime, $endTime);
            
            if (!empty($stats['errors'])) {
                foreach ($stats['errors'] as $err) {
                    $this->error($err);
                }
            }
            
            $totalFetched += $stats['total_fetched'] ?? 0;
            $totalInserted += $stats['total_inserted'] ?? 0;
        }

        $this->info("Alarm Sync Complete!");
        $this->info("Total Alarms Fetched  : {$totalFetched}");
        $this->info("Total New Alarms Saved: {$totalInserted}");

        return Command::SUCCESS;
    }
}
