<?php

namespace App\Http\Controllers\Mobile;

use App\Enums\WaybillStatus;
use App\Http\Controllers\Controller;
use App\Models\FuelLog;
use App\Models\Waybill;
use Illuminate\Http\Request;
use InvalidArgumentException;

class FuelLogController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'fuel_logs' => $this->activeWaybill($request)->fuelLogs()->latest('fueled_at')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'fuel_type' => ['required', 'in:petrol,gas,diesel'],
            'liters' => ['required', 'numeric', 'min:0.01'],
            'cost' => ['required', 'numeric', 'min:0'],
            'fueled_at' => ['nullable', 'date'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $waybill = $this->activeWaybill($request);

        if ($waybill->status !== WaybillStatus::ShiftInProgress) {
            throw new InvalidArgumentException('Заправку можно добавить только во время активной смены.');
        }

        $fuelLog = FuelLog::query()->create([
            ...$payload,
            'waybill_id' => $waybill->id,
            'vehicle_id' => $waybill->vehicle_id,
            'driver_id' => $waybill->driver_id,
            'odometer' => $waybill->odometer_end
                ?? $waybill->odometer_start
                ?? $waybill->vehicle?->current_mileage
                ?? 0,
            'fueled_at' => $payload['fueled_at'] ?? now(),
        ]);

        return response()->json(['fuel_log' => $fuelLog], 201);
    }

    private function activeWaybill(Request $request): Waybill
    {
        return Waybill::query()
            ->where('driver_id', $request->user()->driver->id)
            ->whereNotIn('status', [WaybillStatus::Closed->value, WaybillStatus::Cancelled->value])
            ->latest('id')
            ->firstOrFail();
    }
}
