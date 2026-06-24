<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = ['group_id', 'device_id', 'device_name', 'group_name', 'plate_no', 'imei', 'sim_number', 'status', 'last_sync_at', 'unit_code', 'location', 'series'];

    public function group()
    {
        return $this->belongsTo(DeviceGroup::class, 'group_id');
    }

    public function idleAlarms()
    {
        return $this->hasMany(IdleAlarm::class, 'device_id', 'device_id');
    }

    public function alarmRaws()
    {
        return $this->hasMany(AlarmRaw::class, 'device_id', 'device_id');
    }
}
