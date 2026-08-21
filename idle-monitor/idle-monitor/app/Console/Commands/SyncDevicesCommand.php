<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncDevicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'howen:sync-devices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync devices from Howen API to database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Syncing devices from Howen API...');
        $this->newLine();

        try {
            $deviceService = new \App\Services\HowenDeviceService();
            $synced = $deviceService->syncDevices();
            
            $this->newLine();
            $this->info("✅ Device sync completed successfully!");
            $this->info("Total devices synced: {$synced}");
            
            // Show devices count in database
            $totalDevices = \App\Models\Device::count();
            $this->info("Total devices in database: {$totalDevices}");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Device sync failed: " . $e->getMessage());
            return 1;
        }
    }
}
