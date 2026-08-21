<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GpsHourlyStat extends Model
{
    use HasFactory;

    protected $table = 'gps_hourly_stats';

    protected $fillable = [
        'device_id',
        'device_name',
        'record_date',
        'record_hour',
        'max_speed',
        'sum_speed',
        'total_records',
    ];

    protected $casts = [
        'record_date' => 'date',
        'record_hour' => 'integer',
        'max_speed' => 'float',
        'sum_speed' => 'float',
        'total_records' => 'integer',
        'avg_speed' => 'float',
    ];
}
