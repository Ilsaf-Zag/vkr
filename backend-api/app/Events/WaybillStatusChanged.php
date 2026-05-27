<?php

namespace App\Events;

use App\Models\Waybill;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class WaybillStatusChanged implements ShouldBroadcastNow
{
    public function __construct(public readonly Waybill $waybill)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('driver.' . $this->waybill->driver_id),
            new Channel('admin.dashboard'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'waybill.status.changed';
    }
}

