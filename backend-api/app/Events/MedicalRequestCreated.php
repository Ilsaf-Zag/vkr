<?php

namespace App\Events;

use App\Models\MedicalInspection;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MedicalRequestCreated implements ShouldBroadcastNow
{
    public function __construct(public readonly MedicalInspection $inspection)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('medical.queue');
    }

    public function broadcastAs(): string
    {
        return 'medical.request.created';
    }
}

