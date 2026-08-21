<?php

namespace App\Jobs;

use App\Models\DataPullBatch;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class DataPullBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes max per batch
    public $tries = 2; // Retry once if failed

    protected string $sessionId;
    protected int $batchNumber;

    /**
     * Create a new job instance.
     */
    public function __construct(string $sessionId, int $batchNumber)
    {
        $this->sessionId = $sessionId;
        $this->batchNumber = $batchNumber;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get batch record
        $batch = DataPullBatch::where('session_id', $this->sessionId)
            ->where('batch_number', $this->batchNumber)
            ->first();

        if (!$batch) {
            Log::error("Batch not found", [
                'session_id' => $this->sessionId,
                'batch_number' => $this->batchNumber,
            ]);
            return;
        }

        Log::info("DataPullBatch started", [
            'session_id' => $this->sessionId,
            'batch_number' => $this->batchNumber,
            'date' => $batch->date,
            'time_range' => $batch->time_start . ' - ' . $batch->time_end,
        ]);

        // Mark as processing
        $batch->markAsProcessing();

        try {
            // Pull FULL DAY (command only accepts YYYY-MM-DD format)
            $dateOnly = $batch->date->format('Y-m-d');

            Log::info("Calling Artisan command", [
                'from' => $dateOnly,
                'to' => $dateOnly,
            ]);

            // Call the existing pull command for FULL DAY
            // Note: Command doesn't support time filtering, pulls entire day
            $exitCode = Artisan::call('howen:pull-alarms-date-range', [
                '--from' => $dateOnly,
                '--to' => $dateOnly,
                '--pages' => 200, // Pull all pages for the day
            ]);

            $output = Artisan::output();

            Log::info("Artisan command completed", [
                'exit_code' => $exitCode,
                'output_length' => strlen($output),
            ]);

            // Extract total records from output
            // Look for pattern: "Fetched X records" or "FrontendMatch: Fetched X records"
            $totalRecords = 0;
            if (preg_match('/(?:Fetched|imported)\s+(\d+)\s+records?/i', $output, $matches)) {
                $totalRecords = (int)$matches[1];
            }

            // Mark as completed
            $batch->markAsCompleted($totalRecords);

            Log::info("DataPullBatch completed successfully", [
                'session_id' => $this->sessionId,
                'batch_number' => $this->batchNumber,
                'total_records' => $totalRecords,
                'duration' => $batch->getDuration(),
            ]);

        } catch (\Exception $e) {
            Log::error("DataPullBatch failed", [
                'session_id' => $this->sessionId,
                'batch_number' => $this->batchNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Mark as failed
            $batch->markAsFailed($e->getMessage());

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle job failure (after all retries exhausted)
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("DataPullBatchJob failed permanently", [
            'session_id' => $this->sessionId,
            'batch_number' => $this->batchNumber,
            'error' => $exception->getMessage(),
        ]);

        // Make sure batch is marked as failed
        $batch = DataPullBatch::where('session_id', $this->sessionId)
            ->where('batch_number', $this->batchNumber)
            ->first();

        if ($batch && $batch->status !== 'failed') {
            $batch->markAsFailed('Job failed after retries: ' . $exception->getMessage());
        }
    }
}
