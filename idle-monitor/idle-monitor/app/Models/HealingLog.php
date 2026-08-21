<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class HealingLog extends Model
{
    protected $fillable = [
        'issue_type',
        'severity',
        'problem_description',
        'healing_action',
        'status',
        'result_message',
        'detected_at',
        'healed_at',
        'execution_time_ms',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'healed_at' => 'datetime',
    ];

    /**
     * Get recent healing logs (last 24 hours)
     */
    public static function getRecentLogs(int $hours = 24)
    {
        return static::where('created_at', '>=', now()->subHours($hours))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get success rate for a specific issue type
     */
    public static function getSuccessRate(string $issueType): float
    {
        $total = static::where('issue_type', $issueType)->count();
        if ($total === 0) return 100.0;

        $successful = static::where('issue_type', $issueType)
            ->where('status', 'success')
            ->count();

        return round(($successful / $total) * 100, 1);
    }
}
