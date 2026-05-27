<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GpsPoint extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'waybill_id',
        'vehicle_id',
        'driver_id',
        'latitude',
        'longitude',
        'speed',
        'heading',
        'recorded_at',
        'created_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'speed' => 'decimal:2',
        'heading' => 'decimal:2',
        'recorded_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}

