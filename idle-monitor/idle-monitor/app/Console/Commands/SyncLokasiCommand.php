<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncLokasiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'devices:sync-lokasi';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync lokasi column with data from location column';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to sync lokasi from location...');
        
        $affected = DB::update("UPDATE devices SET lokasi = location WHERE location IS NOT NULL AND location NOT LIKE '%,%'");
        
        $this->info("Successfully synced {$affected} devices!");
    }
}
