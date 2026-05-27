<?php

namespace App\Services;

use App\Enums\WaybillStatus;
use App\Models\GpsPoint;
use App\Models\Waybill;
use InvalidArgumentException;

class GpsService
{
    public function storePoint(Waybill $waybill, array $payload): GpsPoint
    {
        if ($waybill->status !== WaybillStatus::ShiftInProgress) {
            throw new InvalidArgumentException('GPS-точки принимаются только во время активной смены.');
        }

        return GpsPoint::query()->create([
            'waybill_id' => $waybill->id,
            'vehicle_id' => $waybill->vehicle_id,
            'driver_id' => $waybill->driver_id,
            'latitude' => $payload['latitude'],
            'longitude' => $payload['longitude'],
            'speed' => $payload['speed'] ?? null,
            'heading' => $payload['heading'] ?? null,
            'recorded_at' => $payload['recorded_at'] ?? now(),
            'created_at' => now(),
        ]);
    }
}

