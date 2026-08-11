<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLokasiFromDumpCommand extends Command
{
    protected $signature = 'devices:import-lokasi-dump';
    protected $description = 'Import lokasi data from a SQL dump file';

    public function handle()
    {
        $path = storage_path('app/devices_dump.sql');
        if (!file_exists($path)) {
            $this->error("File tidak ditemukan: storage/app/devices_dump.sql");
            $this->info("Silakan upload file SQL dump Anda ke folder storage/app/ dengan nama devices_dump.sql");
            return 1;
        }

        $this->info("Membaca file SQL dump...");
        $content = file_get_contents($path);

        // Extract all VALUES (...) 
        preg_match_all("/\((?:[^')(]+|'[^']*')*\)/", $content, $matches);
        
        $updated = 0;
        $bar = $this->output->createProgressBar(count($matches[0]));
        $bar->start();

        foreach ($matches[0] as $match) {
            // Remove outer parentheses
            $match = substr($match, 1, -1);
            
            // Parse CSV-like string, considering single quotes
            $parts = str_getcsv($match, ',', "'");
            
            if (count($parts) >= 5) {
                $deviceId = trim($parts[1]);
                $location = trim($parts[4]);
                
                // If location is not NULL and not empty
                if ($location !== 'NULL' && $location !== '') {
                    DB::table('devices')
                        ->where('device_id', $deviceId)
                        ->update(['lokasi' => $location]);
                    $updated++;
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Selesai! Berhasil mengupdate {$updated} perangkat dari SQL dump.");
        return 0;
    }
}
