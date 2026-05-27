<?php

namespace App\Models;

use App\Enums\FuelType;
use App\Enums\VehicleStatus;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'brand',
        'model',
        'plate_number',
        'vin',
        'year',
        'fuel_type',
        'current_mileage',
        'status',
        'photo_file_id',
        'note',
    ];

    protected $casts = [
        'fuel_type' => FuelType::class,
        'status' => VehicleStatus::class,
        'current_mileage' => 'integer',
        'year' => 'integer',
    ];

    public function photo()
    {
        return $this->belongsTo(File::class, 'photo_file_id');
    }

    public function waybills()
    {
        return $this->hasMany(Waybill::class);
    }
}

