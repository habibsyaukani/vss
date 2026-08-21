<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLokasiFromDumpCommand extends Command
{
    protected $signature = 'devices:import-lokasi-dump';
    protected $description = 'Import lokasi data from DATA_397_DEVICES.txt';

    public function handle()
    {
        $path = base_path('DATA_397_DEVICES.txt');
        if (!file_exists($path)) {
            $this->error("File tidak ditemukan: " . $path);
            return 1;
        }

        $this->info("Membaca file " . $path . "...");
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        $updated = 0;
        $bar = $this->output->createProgressBar(count($lines));
        $bar->start();

        foreach ($lines as $line) {
            $parts = explode("\t", $line);
            
            if (count($parts) >= 4) {
                $deviceName = trim($parts[1]);
                $location = trim($parts[3]);
                
                if ($location !== 'NULL' && $location !== '') {
                    DB::table('devices')
                        ->where('device_name', $deviceName)
                        ->update(['lokasi' => $location]);
                    $updated++;
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Selesai! Berhasil mengupdate {$updated} perangkat dari file TXT.");
        return 0;
    }
}
