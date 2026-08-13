<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TracksolidSyncService;
use Carbon\Carbon;

class PullTracksolidCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pull:tracksolid {imei} {--hours=1 : Number of hours to pull backwards}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull track data from Tracksolid API for a specific IMEI (for local testing)';

    /**
     * Execute the console command.
     */
    public function handle(TracksolidSyncService $syncService)
    {
        $imei = $this->argument('imei');
        $hours = (int) $this->option('hours');

        $endTime = Carbon::now(config('app.timezone', 'Asia/Makassar'));
        $beginTime = clone $endTime;
        $beginTime->subHours($hours);

        $this->info("Pulling Tracksolid data for IMEI: {$imei}");
        $this->info("Time Range (Local App Time): {$beginTime->toDateTimeString()} to {$endTime->toDateTimeString()}");

        $stats = $syncService->syncDevice($imei, $beginTime->toDateTimeString(), $endTime->toDateTimeString());

        if (!empty($stats['errors'])) {
            $this->error("Errors encountered:");
            foreach ($stats['errors'] as $error) {
                $this->error("- " . $error);
            }
        }

        $this->info("Fetch complete!");
        $this->info("Total Fetched: {$stats['total_fetched']}");
        $this->info("Total Saved: {$stats['total_saved']}");

        return Command::SUCCESS;
    }
}
