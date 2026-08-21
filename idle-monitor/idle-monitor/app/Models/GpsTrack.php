<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GpsTrack extends Model
{
    use HasFactory;

    protected $table = 'gps_tracks';

    protected $fillable = [
        'raw_id',
        'device_id',
        'device_name',
        'longitude',
        'latitude',
        'altitude',
        'speed',
        'direction',
        'satellites',
        'gps_time',
        'report_time',
        'is_acc_on',
        'is_overspeed',
        'is_emergency',
        'is_recording',
        'net_type_label',
        'dev_voltage',
        'driver_name',
        'fleet_id',
        'fleet_name',
        'today_mileage',
        'total_mileage',
        'io_state_label',
        'input_output_status',
    ];

    protected $casts = [
        'longitude' => 'decimal:7',
        'latitude' => 'decimal:7',
        'speed' => 'integer',
        'gps_time' => 'datetime',
        'report_time' => 'datetime',
        'is_acc_on' => 'boolean',
        'is_overspeed' => 'boolean',
        'is_emergency' => 'boolean',
        'is_recording' => 'boolean',
        'today_mileage' => 'decimal:2',
        'total_mileage' => 'decimal:2',
    ];

    /**
     * Relationship: Belongs to raw track
     */
    public function raw()
    {
        return $this->belongsTo(GpsTrackRaw::class, 'raw_id');
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
        return $query->where('is_overspeed', true);
    }

    /**
     * Scope: By fleet
     */
    public function scopeByFleet($query, $fleetId)
    {
        return $query->where('fleet_id', $fleetId);
    }

    /**
     * Scope: ACC ON
     */
    public function scopeAccOn($query)
    {
        return $query->where('is_acc_on', true);
    }

    /**
     * Accessor: Speed with unit
     */
    public function getSpeedWithUnitAttribute()
    {
        return $this->speed ? $this->speed . ' km/h' : 'N/A';
    }

    /**
     * Accessor: GPS coordinates formatted
     */
    public function getCoordinatesAttribute()
    {
        if ($this->latitude && $this->longitude) {
            return $this->latitude . ', ' . $this->longitude;
        }
        return 'N/A';
    }
}
