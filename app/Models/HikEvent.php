<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HikEvent extends Model
{
    protected $fillable = [
        'event_type',
        'person_code',
        'device_id',
        'device_name',
        'occurred_at',
        'payload',
    ];

    protected $casts = [
        'payload'     => 'array',
        'occurred_at' => 'datetime',
    ];
}
