<?php

namespace App\Models;

use App\Enums\WaybillStatus;
use Illuminate\Database\Eloquent\Model;

class Waybill extends Model
{
    protected $fillable = [
        'number',
        'date',
        'organization_name',
        'driver_id',
        'vehicle_id',
        'work_order_id',
        'route_name',
        'status',
        'odometer_start',
        'odometer_end',
        'fuel_start',
        'fuel_end',
        'opened_at',
        'shift_started_at',
        'shift_finished_at',
        'closed_at',
        'initial_printed_at',
        'final_printed_at',
    ];

    protected $casts = [
        'date' => 'date',
        'status' => WaybillStatus::class,
        'opened_at' => 'datetime',
        'shift_started_at' => 'datetime',
        'shift_finished_at' => 'datetime',
        'closed_at' => 'datetime',
        'initial_printed_at' => 'datetime',
        'final_printed_at' => 'datetime',
        'odometer_start' => 'integer',
        'odometer_end' => 'integer',
        'fuel_start' => 'decimal:2',
        'fuel_end' => 'decimal:2',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function medicalInspections()
    {
        return $this->hasMany(MedicalInspection::class);
    }

    public function technicalInspections()
    {
        return $this->hasMany(TechnicalInspection::class);
    }

    public function fuelLogs()
    {
        return $this->hasMany(FuelLog::class);
    }

    public function gpsPoints()
    {
        return $this->hasMany(GpsPoint::class);
    }
}

