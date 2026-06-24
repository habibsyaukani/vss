<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdleAlarm extends Model
{
    use HasFactory;

    protected $table = 'idle_alarms';

    protected $fillable = [
        'guid', 'serial_no', 'device_id', 'device_name', 'alarm_type', 'alarm_status',
        'starting_time', 'starting_location', 'ending_time', 'ending_location',
        'start_detail', 'end_detail', 'start_speed', 'end_speed', 'report_time',
        'duration_seconds', 'duration_minutes', 'latitude_start', 'longitude_start',
        'latitude_end', 'longitude_end'
    ];

    protected $casts = [
        'starting_time' => 'datetime',
        'ending_time' => 'datetime',
        'report_time' => 'datetime',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class, 'device_id', 'device_id');
    }

    /**
     * Hitung durasi real-time dari starting_time → ending_time.
     * Jika ending_time kosong (alarm masih aktif), hitung dari starting_time → sekarang.
     */
    public function getDurationFormattedAttribute(): string
    {
        if (!$this->starting_time) return '-';

        $start = \Carbon\Carbon::parse($this->starting_time);
        $end   = $this->ending_time
                    ? \Carbon\Carbon::parse($this->ending_time)
                    : now();

        $totalSeconds = max(0, $end->diffInSeconds($start));

        return "{$totalSeconds} detik";
    }

    /**
     * Kalkulasi duration_seconds real-time dari starting_time → ending_time.
     */
    public function getDurationSecondsCalculatedAttribute(): int
    {
        if (!$this->starting_time) return 0;

        $start = \Carbon\Carbon::parse($this->starting_time);
        $end   = $this->ending_time
                    ? \Carbon\Carbon::parse($this->ending_time)
                    : now();

        return max(0, $end->diffInSeconds($start));
    }
}
