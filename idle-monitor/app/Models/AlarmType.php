<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlarmType extends Model
{
    use HasFactory;

    protected $fillable = ['alarm_code', 'alarm_name'];
}
