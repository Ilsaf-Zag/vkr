<?php

namespace App\Events;

use App\Models\TechnicalInspection;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class TechnicalRequestCreated implements ShouldBroadcastNow
{
    public function __construct(public readonly TechnicalInspection $inspection)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('technical.queue');
    }

    public function broadcastAs(): string
    {
        return 'technical.request.created';
    }
}

