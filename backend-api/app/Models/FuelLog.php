<?php

namespace App\Models;

use App\Enums\FuelType;
use Illuminate\Database\Eloquent\Model;

class FuelLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'waybill_id',
        'vehicle_id',
        'driver_id',
        'fuel_type',
        'liters',
        'cost',
        'odometer',
        'fueled_at',
        'comment',
    ];

    protected $casts = [
        'fuel_type' => FuelType::class,
        'liters' => 'decimal:2',
        'cost' => 'decimal:2',
        'odometer' => 'integer',
        'fueled_at' => 'datetime',
    ];

    public function waybill()
    {
        return $this->belongsTo(Waybill::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
