<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GpsTrackRaw;
use App\Models\GpsHourlyStat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RollupGpsStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gps:rollup {--date= : The date to rollup (YYYY-MM-DD)} {--hour= : The specific hour (0-23)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregate GPS tracks into hourly summary table';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Default to processing the previous hour if no arguments are provided
        $targetDate = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::now();
        
        if ($this->option('hour') !== null) {
            $startHour = (int)$this->option('hour');
            $endHour = $startHour;
        } else if ($this->option('date')) {
            // If date is provided but no hour, process the whole day
            $startHour = 0;
            $endHour = 23;
        } else {
            // Default: process the previous hour
            $targetDate->subHour();
            $startHour = $targetDate->hour;
            $endHour = $startHour;
        }

        $dateStr = $targetDate->format('Y-m-d');
        
        $this->info("Starting GPS rollup for $dateStr (Hours: $startHour - $endHour)");

        for ($h = $startHour; $h <= $endHour; $h++) {
            $this->processHour($dateStr, $h);
        }

        $this->info("GPS rollup completed.");
        return Command::SUCCESS;
    }

    private function processHour($dateStr, $hour)
    {
        $startTime = Carbon::parse("$dateStr $hour:00:00")->format('Y-m-d H:i:s');
        $endTime = Carbon::parse("$dateStr $hour:59:59")->format('Y-m-d H:i:s');
        
        $this->line("Processing $startTime to $endTime...");

        // Get aggregated data directly from the DB
        $stats = GpsTrackRaw::select(
                'device_id',
                'device_name',
                DB::raw('MAX(speed) as max_speed'),
                DB::raw('SUM(speed) as sum_speed'),
                DB::raw('COUNT(*) as total_records')
            )
            ->where('gps_time', '>=', $startTime)
            ->where('gps_time', '<=', $endTime)
            ->where('speed', '>', 0)
            ->groupBy('device_id', 'device_name')
            ->get();

        if ($stats->isEmpty()) {
            $this->line("No data found for this hour.");
            return;
        }

        // Upsert into gps_hourly_stats
        $upsertData = [];
        foreach ($stats as $stat) {
            $upsertData[] = [
                'device_id' => $stat->device_id,
                'device_name' => $stat->device_name,
                'record_date' => $dateStr,
                'record_hour' => $hour,
                'max_speed' => $stat->max_speed,
                'sum_speed' => $stat->sum_speed,
                'total_records' => $stat->total_records,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Upsert using the unique constraint (device_id, record_date, record_hour)
        GpsHourlyStat::upsert(
            $upsertData,
            ['device_id', 'record_date', 'record_hour'], // Unique columns
            ['device_name', 'max_speed', 'sum_speed', 'total_records', 'updated_at'] // Columns to update if exists
        );

        $this->info("Inserted/Updated " . count($upsertData) . " device records for hour $hour.");
    }
}
