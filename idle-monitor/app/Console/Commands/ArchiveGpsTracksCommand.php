<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GpsTrackRaw;
use Carbon\Carbon;

class ArchiveGpsTracksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gps:archive {--months=3 : Number of months to keep}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old GPS tracks from raw table to save disk space';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $months = (int) $this->option('months');
        if ($months < 1) {
            $this->error("Months must be at least 1.");
            return Command::FAILURE;
        }

        $thresholdDate = Carbon::now()->subMonths($months)->format('Y-m-d H:i:s');
        
        $this->info("Starting GPS data cleanup.");
        $this->info("Threshold date: $thresholdDate (Keeping last $months months)");
        
        // Count before deleting for logging purposes
        $countToDelete = GpsTrackRaw::where('gps_time', '<', $thresholdDate)->count();
        
        if ($countToDelete === 0) {
            $this->info("No old data found to delete.");
            return Command::SUCCESS;
        }

        $this->line("Deleting $countToDelete rows... This might take a while.");
        
        // Delete in chunks to prevent locking the table for too long
        $deletedTotal = 0;
        do {
            $deleted = GpsTrackRaw::where('gps_time', '<', $thresholdDate)
                                  ->limit(10000)
                                  ->delete();
            $deletedTotal += $deleted;
            $this->line("Deleted $deletedTotal / $countToDelete");
            
            // Sleep briefly to reduce DB load
            if ($deleted > 0) {
                usleep(500000); // 0.5s
            }
        } while ($deleted > 0);

        $this->info("Successfully deleted $deletedTotal old GPS track records.");
        return Command::SUCCESS;
    }
}
