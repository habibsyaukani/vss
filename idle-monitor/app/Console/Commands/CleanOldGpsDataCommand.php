<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanOldGpsDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vss:clean-old-gps-data {--days=14 : Number of days to keep data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean old raw GPS data and idle alarms to prevent disk space exhaustion.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        
        if ($days < 3) {
            $this->error("Cannot delete data newer than 3 days for safety reasons.");
            return 1;
        }

        $cutoffDate = now()->subDays($days)->startOfDay();
        
        $this->info("Starting cleanup for data older than {$cutoffDate->toDateTimeString()} ({$days} days)...");
        
        // 1. Delete old gps_tracks_raw in chunks to avoid locking the table
        $this->info("Cleaning gps_tracks_raw...");
        $deletedTracks = 0;
        do {
            $deleted = DB::table('gps_tracks_raw')
                ->where('gps_time', '<', $cutoffDate)
                ->limit(5000)
                ->delete();
            
            $deletedTracks += $deleted;
            if ($deleted > 0) {
                $this->info("Deleted {$deletedTracks} rows from gps_tracks_raw so far...");
                usleep(500000); // Sleep 0.5s to reduce disk I/O pressure
            }
        } while ($deleted > 0);
        
        $this->info("Successfully deleted {$deletedTracks} total rows from gps_tracks_raw.");
        
        Log::info("vss:clean-old-gps-data completed.", [
            'cutoff_date' => $cutoffDate->toDateTimeString(),
            'deleted_gps_tracks_raw' => $deletedTracks
        ]);

        return 0;
    }
}
