<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\GpsTrackSyncService;
use App\Services\VssAuthService;
use App\Models\Device;
use App\Models\ImportLog;
use Carbon\Carbon;

class ImportGpsTrackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 900;  // 15 minutes untuk handle banyak device

    protected int $hoursBack;
    protected int $delayBetweenDevicesMs;

    /**
     * Create a new job instance.
     * 
     * @param int $hoursBack Berapa jam ke belakang data akan diambil (default: 2 jam)
     * @param int $delayBetweenDevicesMs Delay antar device dalam milliseconds (default: 500ms)
     */
    public function __construct(int $hoursBack = 2, int $delayBetweenDevicesMs = 500)
    {
        $this->hoursBack = $hoursBack;
        $this->delayBetweenDevicesMs = $delayBetweenDevicesMs;
    }

    /**
     * Execute the job - Import GPS tracks from VSS API → gps_tracks_raw
     * 
     * FLOW:
     * 1. Get VSS token from VssAuthService
     * 2. Get all active devices
     * 3. For each device: pull GPS data from last X hours
     * 4. Save to gps_tracks_raw via GpsTrackSyncService
     * 5. Log results
     */
    public function handle(
        GpsTrackSyncService $syncService,
        VssAuthService $authService
    ): void {
        $importLog = ImportLog::create([
            'job_name' => 'ImportGpsTrackJob',
            'started_at' => now(),
            'total_record' => 0,
            'status' => 'running',
        ]);

        try {
            Log::info('ImportGpsTrackJob started', [
                'hours_back' => $this->hoursBack,
                'delay_ms' => $this->delayBetweenDevicesMs,
            ]);

            // 1. Get VSS token
            $token = $authService->getToken();
            
            if (!$token) {
                throw new \Exception('Failed to get VSS token. Please check VssAuthService.');
            }

            Log::info('VSS token obtained successfully');

            // 2. Get active devices with device_id (device_id is required for VSS API)
            $devices = Device::where('status', 'active')
                ->whereNotNull('device_id')
                ->orderBy('device_name')
                ->get();

            if ($devices->isEmpty()) {
                Log::warning('No active devices found with device_id');
                $importLog->update([
                    'finished_at' => now(),
                    'status' => 'completed',
                    'total_record' => 0,
                    'message' => 'No active devices with device_id found',
                ]);
                return;
            }

            Log::info('Found active devices to sync', ['count' => $devices->count()]);

            // 3. Calculate time range
            $endTime = Carbon::now();
            $beginTime = $endTime->copy()->subHours($this->hoursBack);

            $beginTimeStr = $beginTime->format('Y-m-d H:i:s');
            $endTimeStr = $endTime->format('Y-m-d H:i:s');

            Log::info('Time range for GPS sync', [
                'from' => $beginTimeStr,
                'to' => $endTimeStr,
            ]);

            // 4. Sync each device
            $totalFetched = 0;
            $totalSaved = 0;
            $totalDevicesProcessed = 0;
            $deviceErrors = [];

            foreach ($devices as $device) {
                try {
                    Log::info("Syncing device: {$device->device_name} (ID: {$device->device_id})");

                    // Sync device GPS data
                    $result = $syncService->syncDevice(
                        $token,
                        $device->device_id,
                        $beginTimeStr,
                        $endTimeStr
                    );

                    $totalFetched += $result['total_fetched'];
                    $totalSaved += $result['total_saved'];
                    $totalDevicesProcessed++;

                    Log::info("Device sync completed: {$device->device_name}", [
                        'fetched' => $result['total_fetched'],
                        'saved' => $result['total_saved'],
                        'pages' => $result['pages'],
                    ]);

                    // Update progress every device
                    $importLog->update([
                        'total_record' => $totalSaved,
                        'message' => "Processing: {$totalDevicesProcessed}/{$devices->count()} devices",
                        'updated_at' => now(),
                    ]);

                    // Delay between devices to not overwhelm VSS API
                    if ($this->delayBetweenDevicesMs > 0) {
                        usleep($this->delayBetweenDevicesMs * 1000);
                    }

                } catch (\Exception $e) {
                    Log::error("Failed to sync device: {$device->device_name}", [
                        'device_id' => $device->device_id,
                        'error' => $e->getMessage(),
                    ]);
                    
                    $deviceErrors[] = [
                        'device_name' => $device->device_name,
                        'device_id' => $device->device_id,
                        'error' => $e->getMessage(),
                    ];

                    // Continue with next device
                    continue;
                }
            }

            // 5. Final log
            $importLog->update([
                'finished_at' => now(),
                'status' => 'completed',
                'total_record' => $totalSaved,
                'message' => sprintf(
                    'Processed %d devices, fetched %d records, saved %d records. Errors: %d',
                    $totalDevicesProcessed,
                    $totalFetched,
                    $totalSaved,
                    count($deviceErrors)
                ),
            ]);

            Log::info('ImportGpsTrackJob completed', [
                'devices_processed' => $totalDevicesProcessed,
                'total_devices' => $devices->count(),
                'total_fetched' => $totalFetched,
                'total_saved' => $totalSaved,
                'errors' => count($deviceErrors),
            ]);

            if (!empty($deviceErrors)) {
                Log::warning('Some devices failed to sync', ['errors' => $deviceErrors]);
            }

        } catch (\Exception $e) {
            $importLog->update([
                'finished_at' => now(),
                'status' => 'failed',
                'message' => $e->getMessage(),
            ]);

            Log::error('ImportGpsTrackJob failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
