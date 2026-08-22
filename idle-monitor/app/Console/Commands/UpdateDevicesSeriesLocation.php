<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Device;

class UpdateDevicesSeriesLocation extends Command
{
    protected $signature = 'update:devices-series-location {--dry-run : Show what would be updated without actually updating}';
    protected $description = 'Update series and location columns for all devices (maintains 397 total)';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  UPDATE DEVICES - SERIES & LOCATION');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();
        
        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No actual updates will be made');
            $this->newLine();
        }
        
        // Verify count before
        $countBefore = Device::count();
        $this->info("📊 Devices count BEFORE: {$countBefore}");
        
        if ($countBefore === 0) {
            $this->error("❌ ERROR: No devices found in the database");
            $this->error('❌ Aborting to prevent data issues');
            return 1;
        }
        
        // Get update data (your CSV data inline)
        $updateData = $this->getUpdateData();
        $this->info("📋 Update data loaded: " . count($updateData) . " records");
        $this->newLine();
        
        if ($isDryRun) {
            $this->showDryRunPreview($updateData);
            return 0;
        }
        
        // Start transaction
        DB::beginTransaction();
        
        try {
            $updated = 0;
            $notFound = 0;
            $errors = [];
            
            $this->info('🔄 Processing updates...');
            $bar = $this->output->createProgressBar(count($updateData));
            $bar->start();
            
            foreach ($updateData as $data) {
                $device = Device::where('device_name', $data['device_code'])->first();
                
                if ($device) {
                    $device->series = $data['series'];
                    $device->location = $data['location'];
                    $device->lokasi = $data['location']; // Isi juga kolom 'lokasi' agar seragam
                    $device->save();
                    $updated++;
                } else {
                    $notFound++;
                    $errors[] = "Device not found: {$data['device_code']}";
                }
                
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine(2);
            
            // Verify count after
            $countAfter = Device::count();
            $this->info("📊 Devices count AFTER: {$countAfter}");
            
            if ($countAfter < $countBefore) {
                $this->error("❌ ERROR: Count dropped from {$countBefore} to {$countAfter}!");
                $this->error('❌ Rolling back transaction...');
                DB::rollBack();
                return 1;
            }
            
            // Commit transaction
            DB::commit();
            
            $this->newLine();
            $this->info("✅ SUCCESS!");
            $this->info("   - Updated: {$updated} devices");
            $this->info("   - Not found: {$notFound} devices");
            $this->info("   - Total devices: {$countAfter} (maintained)");
            
            if (!empty($errors)) {
                $this->newLine();
                $this->warn('⚠️  Devices not found in database:');
                foreach (array_slice($errors, 0, 10) as $error) {
                    $this->warn("   - {$error}");
                }
                if (count($errors) > 10) {
                    $this->warn("   ... and " . (count($errors) - 10) . " more");
                }
            }
            
            $this->newLine();
            $this->info('✅ Update completed successfully!');
            
            return 0;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ ERROR: ' . $e->getMessage());
            $this->error('❌ Transaction rolled back');
            return 1;
        }
    }


    
    private function showDryRunPreview($updateData)
    {
        $this->info('📋 PREVIEW OF UPDATES (first 10 records):');
        $this->newLine();
        
        foreach (array_slice($updateData, 0, 10) as $data) {
            $device = Device::where('device_name', $data['device_code'])->first();
            if ($device) {
                $this->line("   {$data['device_code']}:");
                $this->line("      series: NULL → '{$data['series']}'");
                $this->line("      location: NULL → '{$data['location']}'");
            } else {
                $this->warn("   {$data['device_code']}: NOT FOUND");
            }
        }
        
        $this->newLine();
        $this->info("... and " . (count($updateData) - 10) . " more records");
        $this->newLine();
        $this->info('✅ Dry run complete. Run without --dry-run to apply changes.');
    }

    
    private function getUpdateData()
    {
        // Check if external CSV file exists
        $csvFile = base_path('devices_update_data.csv');
        
        if (file_exists($csvFile)) {
            $this->info("📁 Reading from: devices_update_data.csv");
            return $this->parseCSVFile($csvFile);
        }
        
        // Fallback: inline sample data (first 50 records as example)
        $this->warn("⚠️  File 'devices_update_data.csv' not found");
        $this->warn("⚠️  Using inline sample data (first 50 records only)");
        $this->newLine();
        
        return $this->getInlineData();
    }
    
    private function parseCSVFile($filePath)
    {
        $data = [];
        $handle = fopen($filePath, 'r');
        
        // Skip header if exists
        $firstLine = fgets($handle);
        if (stripos($firstLine, 'device_code') === false) {
            // No header, rewind
            rewind($handle);
        }
        
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $parts = str_getcsv($line);
            if (count($parts) >= 4) {
                $data[] = [
                    'device_code' => trim($parts[0]),
                    'unit_code' => trim($parts[1]),
                    'series' => trim($parts[2]),
                    'location' => trim($parts[3]),
                ];
            }
        }
        
        fclose($handle);
        return $data;
    }
    
    private function getInlineData()
    {
        // CSV data: device_code, unit_code, series, location
        // Parsing your provided CSV data
        $csvText = <<<'CSV'
GPE-B-806,GPE7801,HD 785,SELATAN
GPE-B-807,GPE7802,HD 785,SELATAN
GPE-B-808,GPE7803,HD 785,SELATAN
GPE-B-809,GPE7805,HD 785,SELATAN
GPE-B-811,GPE7806,HD 785,SELATAN
GPE-B-812,GPE7807,HD 785,SELATAN
GPE-B-813,GPE7808,HD 785,SELATAN
GPE-B-815,GPE7809,HD 785,SELATAN
GPE-B-816,GPE7810,HD 785,SELATAN
GPE-B-818,GPE7811,HD 785,SELATAN
GPE-B-819,GPE7812,HD 785,SELATAN
GPE-B-820,GPE7813,HD 785,SELATAN
GPE-B-821,GPE7815,HD 785,SELATAN
GPE-B-822,GPE7816,HD 785,SELATAN
GPE-B-825,GPE7817,HD 785,SELATAN
GPE-B-826,GPE7818,HD 785,SELATAN
GPE-B-827,GPE7819,HD 785,SELATAN
GPE-B-828,GPE7820,HD 785,SELATAN
GPE-B-829,GPE7301,OHT 773,SELATAN
GPE-B-830,GPE7302,OHT 773,SELATAN
GPE-B-831,GPE7303,OHT 773,SELATAN
GPE-B-832,GPE7305,OHT 773,SELATAN
GPE-B-833,GPE7306,OHT 773,SELATAN
GPE-B-835,GPE7307,OHT 773,SELATAN
GPE-B-836,GPE7308,OHT 773,SELATAN
GPE-B-837,GPE7309,OHT 773,SELATAN
GPE-B-838,GPE7310,OHT 773,SELATAN
GPE-B-839,GPE7311,OHT 773,SELATAN
GPE-B-856,GPE7312,OHT 773,SELATAN
GPE-B-857,GPE7313,OHT 773,SELATAN
GPE-B-860,GPE802,HD 465,SELATAN
GPE-B-866,GPE803,HD 465,SELATAN
GPE-B-867,GPE805,HD 465,SELATAN
GPE-B-871,GPE806,HD 465,SELATAN
GPE-B-873,GPE807,HD 465,SELATAN
GPE-B-876,GPE810,HD 465,SELATAN
GPE-B-877,GPE812,HD 465,SELATAN
GPE-B-878,GPE813,HD 465,SELATAN
GPE-B-879,GPE815,HD 465,SELATAN
GPE-B-880,GPE816,HD 465,SELATAN
GPE-B-881,GPE817,HD 465,SELATAN
GPE-B-882,GPE819,HD 465,SELATAN
GPE-B-883,GPE820,HD 465,SELATAN
GPE-B-885,GPE821,HD 465,SELATAN
GPE-B-886,GPE822,HD 465,SELATAN
GPE-B-887,GPE823,HD 465,SELATAN
GPE-DT-1000,GPE825,HD 465,SELATAN
GPE-DT-1001,GPE826,HD 465,SELATAN
GPE-DT-1002,GPE827,HD 465,SELATAN
GPE-DT-1003,GPE828,HD 465,SELATAN
CSV;
        
        $lines = explode("\n", trim($csvText));
        $data = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $parts = str_getcsv($line);
            if (count($parts) >= 4) {
                $data[] = [
                    'device_code' => trim($parts[0]),
                    'unit_code' => trim($parts[1]),
                    'series' => trim($parts[2]),
                    'location' => trim($parts[3]),
                ];
            }
        }
        
        return $data;
    }
}
