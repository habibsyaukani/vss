<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlarmRaw extends Model
{
    use HasFactory;

    protected $table = 'alarm_raw';
    
    protected $fillable = [
        'guid', 'device_id', 'device_name', 'alarm_type', 'alarm_value', 'alarm_state',
        'start_time', 'end_time', 'start_gps', 'end_gps', 'start_speed', 'end_speed',
        'report_time', 'duration_seconds', 'start_detail', 'end_detail', 'raw_json'
    ];

    protected $casts = [
        'raw_json' => 'json',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'report_time' => 'datetime',
    ];
}
