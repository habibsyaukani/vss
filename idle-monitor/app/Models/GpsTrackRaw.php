<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GpsTrackRaw extends Model
{
    use HasFactory;

    protected $table = 'gps_tracks_raw';

    protected $fillable = [
        'device_id',
        'device_name',
        'guid',
        'longitude',
        'latitude',
        'altitude',
        'speed',
        'direction',
        'satellites',
        'precision',
        'mode',
        'acc_state',
        'record_state',
        'video_mask_state',
        'video_lost_state',
        'io_state',
        'urgency',
        'over_speed',
        'low_speed',
        'oil_volume',
        'net_type',
        'signal_value',
        'dev_voltage',
        'bat_voltage',
        'driver_card_id',
        'driver_name',
        'gps_time',
        'report_time',
        'state_json',
        'tempe_humidity',
        'is_later',
    ];

    protected $casts = [
        'longitude' => 'decimal:7',
        'latitude' => 'decimal:7',
        'speed' => 'integer',
        'gps_time' => 'datetime',
        'report_time' => 'datetime',
        'state_json' => 'array',
        'tempe_humidity' => 'array',
    ];

    /**
     * Relationship: Has one processed track
     */
    public function track()
    {
        return $this->hasOne(GpsTrack::class, 'raw_id');
    }

    /**
     * Scope: Get latest tracks
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('gps_time', 'desc');
    }

    /**
     * Scope: By device
     */
    public function scopeByDevice($query, $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    /**
     * Scope: Overspeed records
     */
    public function scopeOverspeed($query)
    {
        return $query->where('over_speed', 1);
    }
}
