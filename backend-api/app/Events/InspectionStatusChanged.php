<?php

namespace App\Events;

use App\Models\MedicalInspection;
use App\Models\TechnicalInspection;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class InspectionStatusChanged implements ShouldBroadcastNow
{
    public function __construct(public readonly MedicalInspection|TechnicalInspection $inspection)
    {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('driver.' . $this->inspection->driver_id);
    }

    public function broadcastAs(): string
    {
        return 'inspection.status.changed';
    }
}

