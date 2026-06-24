<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_code',
        'group_name',
        'total_devices',
    ];

    /**
     * Get devices in this group
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class, 'group_id');
    }
}
