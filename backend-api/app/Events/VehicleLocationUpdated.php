<?php

namespace App\Events;

use App\Models\GpsPoint;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class VehicleLocationUpdated implements ShouldBroadcastNow
{
    public function __construct(public readonly GpsPoint $point)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('admin.map');
    }

    public function broadcastAs(): string
    {
        return 'vehicle.location.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'waybill_id' => $this->point->waybill_id,
            'vehicle_id' => $this->point->vehicle_id,
            'driver_id' => $this->point->driver_id,
            'latitude' => $this->point->latitude,
            'longitude' => $this->point->longitude,
            'speed' => $this->point->speed,
            'recorded_at' => $this->point->recorded_at,
        ];
    }
}

