<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = ['device_id', 'device_name', 'plate_no', 'imei', 'sim_number', 'last_sync_at'];

    public function idleAlarms()
    {
        return $this->hasMany(IdleAlarm::class, 'device_id', 'device_id');
    }

    public function alarmRaws()
    {
        return $this->hasMany(AlarmRaw::class, 'device_id', 'device_id');
    }
}
