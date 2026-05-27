<?php

namespace App\Models;

use App\Enums\InspectionStatus;
use App\Enums\InspectionType;
use Illuminate\Database\Eloquent\Model;

class TechnicalInspection extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'waybill_id',
        'driver_id',
        'vehicle_id',
        'type',
        'status',
        'requested_at',
        'decided_at',
        'mechanic_id',
        'rejection_reason',
    ];

    protected $casts = [
        'type' => InspectionType::class,
        'status' => InspectionStatus::class,
        'requested_at' => 'datetime',
        'decided_at' => 'datetime',
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

    public function mechanic()
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }
}

