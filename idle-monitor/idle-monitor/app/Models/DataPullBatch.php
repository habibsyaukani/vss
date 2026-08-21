<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DataPullBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'batch_number',
        'date',
        'time_start',
        'time_end',
        'status',
        'total_records',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get batches by session ID
     */
    public static function getBySession(string $sessionId)
    {
        return self::where('session_id', $sessionId)
            ->orderBy('batch_number')
            ->get();
    }

    /**
     * Get session progress summary
     */
    public static function getSessionProgress(string $sessionId): array
    {
        $batches = self::where('session_id', $sessionId)->get();
        
        return [
            'total_batches' => $batches->count(),
            'completed' => $batches->where('status', 'completed')->count(),
            'processing' => $batches->where('status', 'processing')->count(),
            'failed' => $batches->where('status', 'failed')->count(),
            'pending' => $batches->where('status', 'pending')->count(),
            'total_records' => $batches->sum('total_records'),
            'batches' => $batches->map(function ($batch) {
                return [
                    'batch_number' => $batch->batch_number,
                    'time_range' => $batch->time_start . ' - ' . $batch->time_end,
                    'status' => $batch->status,
                    'total_records' => $batch->total_records,
                    'error_message' => $batch->error_message,
                    'started_at' => $batch->started_at?->format('Y-m-d H:i:s'),
                    'completed_at' => $batch->completed_at?->format('Y-m-d H:i:s'),
                    'duration' => $batch->getDuration(),
                ];
            })->toArray(),
        ];
    }

    /**
     * Check if session is completed (all batches done)
     */
    public static function isSessionCompleted(string $sessionId): bool
    {
        $total = self::where('session_id', $sessionId)->count();
        $completed = self::where('session_id', $sessionId)
            ->whereIn('status', ['completed', 'failed'])
            ->count();
        
        return $total > 0 && $total === $completed;
    }

    /**
     * Get duration in human readable format
     */
    public function getDuration(): ?string
    {
        if (!$this->started_at) {
            return null;
        }

        $end = $this->completed_at ?? now();
        $seconds = $this->started_at->diffInSeconds($end);

        if ($seconds < 60) {
            return $seconds . 's';
        } elseif ($seconds < 3600) {
            return floor($seconds / 60) . 'm ' . ($seconds % 60) . 's';
        } else {
            return floor($seconds / 3600) . 'h ' . floor(($seconds % 3600) / 60) . 'm';
        }
    }

    /**
     * Mark batch as processing
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark batch as completed
     */
    public function markAsCompleted(int $totalRecords): void
    {
        $this->update([
            'status' => 'completed',
            'total_records' => $totalRecords,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark batch as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }
}
