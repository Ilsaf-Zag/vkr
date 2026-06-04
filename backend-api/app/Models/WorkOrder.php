<?php

namespace App\Models;

use App\Enums\WorkOrderStatus;
use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{
    protected $fillable = [
        'date',
        'shift',
        'driver_id',
        'vehicle_id',
        'route_name',
        'dispatcher_comment',
        'status',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'status' => WorkOrderStatus::class,
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function waybill()
    {
        return $this->hasOne(Waybill::class);
    }
}
