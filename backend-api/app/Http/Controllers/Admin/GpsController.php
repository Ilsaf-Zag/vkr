<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WaybillStatus;
use App\Http\Controllers\Controller;
use App\Models\GpsPoint;
use App\Models\Waybill;
use Illuminate\Http\Request;

class GpsController extends Controller
{
    public function current()
    {
        $activeWaybills = Waybill::query()
            ->where('status', WaybillStatus::ShiftInProgress->value)
            ->with(['driver', 'vehicle'])
            ->get();

        $items = $activeWaybills->map(function (Waybill $waybill) {
            $point = GpsPoint::query()
                ->where('waybill_id', $waybill->id)
                ->latest('recorded_at')
                ->first();

            return [
                'waybill' => $waybill,
                'last_point' => $point,
            ];
        });

        return response()->json(['items' => $items]);
    }

    public function history(Request $request)
    {
        $payload = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date'],
            'vehicle_id' => ['nullable', 'integer'],
            'driver_id' => ['nullable', 'integer'],
            'waybill_id' => ['nullable', 'integer'],
        ]);

        $query = GpsPoint::query()
            ->whereBetween('recorded_at', [$payload['date_from'], $payload['date_to']])
            ->orderBy('recorded_at');

        foreach (['vehicle_id', 'driver_id', 'waybill_id'] as $field) {
            if (! empty($payload[$field])) {
                $query->where($field, $payload[$field]);
            }
        }

        return response()->json(['points' => $query->get()]);
    }
}
