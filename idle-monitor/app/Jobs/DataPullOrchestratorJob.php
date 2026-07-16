<?php

namespace App\Jobs;

use App\Models\DataPullBatch;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DataPullOrchestratorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minutes max untuk orchestration
    public $tries = 1; // No retry untuk orchestrator

    protected string $sessionId;
    protected string $date;
    protected int $batchIntervalHours;

    /**
     * Create a new job instance.
     */
    public function __construct(string $sessionId, string $date, int $batchIntervalHours = 3)
    {
        $this->sessionId = $sessionId;
        $this->date = $date;
        $this->batchIntervalHours = $batchIntervalHours;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("DataPullOrchestrator started for session: {$this->sessionId}, date: {$this->date}");

        try {
            // Generate time batches (8 batches x 3 jam = 24 jam)
            $batches = $this->generateTimeBatches();

            Log::info("Generated {count} batches for session: {$this->sessionId}", [
                'count' => count($batches),
                'session_id' => $this->sessionId,
            ]);

            // Create batch records in database
            foreach ($batches as $index => $batch) {
                DataPullBatch::create([
                    'session_id' => $this->sessionId,
                    'batch_number' => $index + 1,
                    'date' => $this->date,
                    'time_start' => $batch['start'],
                    'time_end' => $batch['end'],
                    'status' => 'pending',
                ]);
            }

            // Dispatch batch jobs SEQUENTIALLY with delay
            // Delay: 0s, 10s, 20s, 30s, ... to prevent overwhelming the system
            foreach ($batches as $index => $batch) {
                $batchNumber = $index + 1;
                $delaySeconds = $index * 10; // 10 second delay between batch starts

                DataPullBatchJob::dispatch($this->sessionId, $batchNumber)
                    ->delay(now()->addSeconds($delaySeconds));

                Log::info("Dispatched batch {$batchNumber} with {$delaySeconds}s delay", [
                    'session_id' => $this->sessionId,
                    'batch_number' => $batchNumber,
                    'time_range' => $batch['start'] . ' - ' . $batch['end'],
                ]);
            }

            Log::info("DataPullOrchestrator completed for session: {$this->sessionId}");

        } catch (\Exception $e) {
            Log::error("DataPullOrchestrator failed for session: {$this->sessionId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Mark all pending batches as failed
            DataPullBatch::where('session_id', $this->sessionId)
                ->where('status', 'pending')
                ->update([
                    'status' => 'failed',
                    'error_message' => 'Orchestrator failed: ' . $e->getMessage(),
                ]);

            throw $e;
        }
    }

    /**
     * Generate time batches (8 batches x 3 hours = 24 hours)
     */
    protected function generateTimeBatches(): array
    {
        $batches = [];
        $hoursInDay = 24;
        $batchCount = ceil($hoursInDay / $this->batchIntervalHours);

        for ($i = 0; $i < $batchCount; $i++) {
            $startHour = $i * $this->batchIntervalHours;
            $endHour = min(($i + 1) * $this->batchIntervalHours, 24);

            // Time format: HH:MM:SS
            $startTime = sprintf('%02d:00:00', $startHour);
            
            // End time: Last second of the interval (HH:59:59)
            if ($endHour == 24) {
                $endTime = '23:59:59';
            } else {
                $endTime = sprintf('%02d:59:59', $endHour - 1);
            }

            $batches[] = [
                'start' => $startTime,
                'end' => $endTime,
            ];
        }

        return $batches;
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("DataPullOrchestratorJob failed permanently", [
            'session_id' => $this->sessionId,
            'date' => $this->date,
            'error' => $exception->getMessage(),
        ]);

        // Mark all batches as failed
        DataPullBatch::where('session_id', $this->sessionId)
            ->update([
                'status' => 'failed',
                'error_message' => 'Orchestrator failed: ' . $exception->getMessage(),
                'completed_at' => now(),
            ]);
    }
}
