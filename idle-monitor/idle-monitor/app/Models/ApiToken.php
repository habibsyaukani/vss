<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiToken extends Model
{
    use HasFactory;

    protected $fillable = ['token', 'pid', 'username', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
