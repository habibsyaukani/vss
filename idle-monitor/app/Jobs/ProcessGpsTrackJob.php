<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\GpsTrackRaw;
use App\Models\GpsTrack;
use App\Models\ImportLog;

class ProcessGpsTrackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;  // 10 minutes

    /**
     * Execute the job - Process GPS tracks from gps_tracks_raw → gps_tracks
     * 
     * FLOW:
     * 1. Find gps_tracks_raw yang belum ada di gps_tracks
     * 2. Map data ke format display
     * 3. Save ke gps_tracks dengan link ke raw_id
     * 4. Log results
     * 
     * NOTE: GpsTrackSyncService sudah handle mapping dan save,
     * tapi job ini untuk proses ulang data yang mungkin gagal atau
     * untuk consistency check.
     */
    public function handle(): void
    {
        $processLog = ImportLog::create([
            'job_name' => 'ProcessGpsTrackJob',
            'started_at' => now(),
            'total_record' => 0,
            'status' => 'running',
        ]);

        try {
            Log::info('ProcessGpsTrackJob started');

            $processed = 0;
            $skipped = 0;

            // Find gps_tracks_raw yang belum diproses (jauh lebih cepat daripada whereDoesntHave)
            // Proses dalam chunk untuk efisiensi memory
            GpsTrackRaw::where('is_processed', 0)
                ->orderBy('id', 'asc')
                ->chunk(1000, function ($rawTracks) use (&$processed, &$skipped, $processLog) {
                    Log::info("Processing chunk of GPS raw tracks", ['count' => $rawTracks->count()]);

                    foreach ($rawTracks as $rawTrack) {
                        try {
                            // Map dan create display record
                            $displayData = $this->mapToDisplay($rawTrack);

                            GpsTrack::updateOrCreate(
                                ['raw_id' => $rawTrack->id],
                                $displayData
                            );
                            
                            // Tandai sebagai sudah diproses
                            $rawTrack->update(['is_processed' => 1]);

                            $processed++;

                            // Update progress every 100 records
                            if ($processed % 100 === 0) {
                                $processLog->update([
                                    'total_record' => $processed,
                                    'updated_at' => now(),
                                ]);
                                
                                Log::info("ProcessGpsTrackJob progress", [
                                    'processed' => $processed,
                                    'skipped' => $skipped,
                                ]);
                            }

                        } catch (\Exception $e) {
                            $skipped++;
                            Log::error("Failed to process GPS raw track: {$rawTrack->id}", [
                                'error' => $e->getMessage(),
                                'device_id' => $rawTrack->device_id,
                                'gps_time' => $rawTrack->gps_time,
                            ]);
                        }
                    }
                });

            // Final log
            $processLog->update([
                'finished_at' => now(),
                'status' => 'completed',
                'total_record' => $processed,
                'message' => "Processed {$processed} GPS tracks, skipped {$skipped}",
            ]);

            Log::info('ProcessGpsTrackJob completed', [
                'processed' => $processed,
                'skipped' => $skipped,
            ]);

        } catch (\Exception $e) {
            $processLog->update([
                'finished_at' => now(),
                'status' => 'failed',
                'message' => $e->getMessage(),
            ]);

            Log::error('ProcessGpsTrackJob failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Map GpsTrackRaw to GpsTrack display format
     * 
     * @param GpsTrackRaw $raw
     * @return array
     */
    protected function mapToDisplay(GpsTrackRaw $raw): array
    {
        return [
            'raw_id'             => $raw->id,
            'device_id'          => $raw->device_id,
            'device_name'        => $raw->device_name,
            'longitude'          => $raw->longitude,
            'latitude'           => $raw->latitude,
            'altitude'           => $raw->altitude,
            'speed'              => $raw->speed,
            'direction'          => $raw->direction,
            'satellites'         => $raw->satellites,
            'gps_time'           => $raw->gps_time,
            'report_time'        => $raw->report_time,
            'is_acc_on'          => $raw->acc_state == 1,
            'is_overspeed'       => $raw->over_speed == 1,
            'is_emergency'       => $raw->urgency == 1,
            'is_recording'       => $this->hasRecording($raw->record_state),
            'net_type_label'     => $this->netTypeLabel($raw->net_type),
            'dev_voltage'        => $raw->dev_voltage,
            'io_state_label'     => $this->formatIoState($raw->io_state),
            'input_output_status'=> $this->formatIoState($raw->io_state),
            'driver_name'        => $raw->driver_name,
            'today_mileage'      => $this->extractMileage($raw, 'today'),
            'total_mileage'      => $this->extractMileage($raw, 'total'),
        ];
    }

    /**
     * Check if device is recording
     */
    protected function hasRecording(?int $recordState): bool
    {
        if (is_null($recordState)) return false;
        // recordState adalah bitmask; jika ada bit yang aktif = ada yang recording
        return $recordState > 0;
    }

    /**
     * Get network type label
     */
    protected function netTypeLabel(?int $type): ?string
    {
        if (is_null($type)) return null;
        
        return match ($type) {
            1 => 'Ethernet',
            2 => 'WiFi',
            3 => '2G',
            4 => '3G',
            5 => '4G',
            6 => '5G',
            7 => 'WiFi+Mobile',
            8 => 'Cable+Mobile',
            default => null,
        };
    }

    /**
     * Format IO state to readable string
     */
    protected function formatIoState(?int $ioState): ?string
    {
        if (is_null($ioState)) return null;
        
        // Convert to binary string for bit analysis
        // This is a simplified version - customize based on your device specs
        $binary = sprintf('%08b', $ioState);
        
        $states = [];
        if ($binary[7] == '1') $states[] = 'Input1';
        if ($binary[6] == '1') $states[] = 'Input2';
        if ($binary[5] == '1') $states[] = 'Output1';
        if ($binary[4] == '1') $states[] = 'Output2';
        
        return empty($states) ? 'Normal' : implode(', ', $states);
    }

    /**
     * Extract mileage from state_json
     */
    protected function extractMileage(GpsTrackRaw $raw, string $type): ?float
    {
        try {
            $stateJson = $raw->state_json;
            
            if (empty($stateJson)) return null;
            
            // If state_json is string, decode it
            if (is_string($stateJson)) {
                $state = json_decode($stateJson, true);
            } else {
                $state = $stateJson;
            }
            
            if (!is_array($state)) return null;
            
            if ($type === 'today' && isset($state['mileage']['todayDay'])) {
                // VSS simpan dalam satuan 10 meter → convert ke km
                return round($state['mileage']['todayDay'] * 10 / 1000, 2);
            }
            
            if ($type === 'total' && isset($state['mileage']['total'])) {
                return round($state['mileage']['total'] * 10 / 1000, 2);
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::warning('Failed to extract mileage', [
                'raw_id' => $raw->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
