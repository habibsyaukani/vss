<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TracksolidDeviceService;

class PullTracksolidDevicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pull:tracksolid-devices {--target= : Account to fetch devices from. Default is the main username in .env}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull device list from Tracksolid API and sync to local database';

    /**
     * Execute the console command.
     */
    public function handle(TracksolidDeviceService $deviceService)
    {
        // Jika tidak disuplai target, gunakan username utama
        $target = $this->option('target');
        if (empty($target)) {
            $target = env('TRACKSOLID_USERNAME');
        }

        if (empty($target)) {
            $this->error("Target account is missing! Please set TRACKSOLID_USERNAME in .env or pass --target parameter.");
            return Command::FAILURE;
        }

        $this->info("Pulling devices for Tracksolid account: {$target}");
        
        $stats = $deviceService->syncDevices($target);

        if (!empty($stats['errors'])) {
            $this->error("Errors encountered:");
            foreach ($stats['errors'] as $error) {
                $this->error("- " . $error);
            }
        }

        $this->info("Device Sync Complete!");
        $this->info("Total Fetched from API : {$stats['total_fetched']}");
        $this->info("Total New Devices    : {$stats['total_inserted']}");
        $this->info("Total Updated Devices: {$stats['total_updated']}");

        return Command::SUCCESS;
    }
}
