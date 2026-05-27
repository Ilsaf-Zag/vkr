<?php

namespace App\Http\Controllers\Mobile;

use App\Enums\WaybillStatus;
use App\Http\Controllers\Controller;
use App\Models\Waybill;
use App\Services\GpsService;
use Illuminate\Http\Request;

class GpsController extends Controller
{
    public function __construct(private readonly GpsService $gps)
    {
    }

    public function store(Request $request)
    {
        $payload = $this->validatePoint($request);
        $point = $this->gps->storePoint($this->activeWaybill($request), $payload);

        return response()->json(['gps_point' => $point], 201);
    }

    public function batch(Request $request)
    {
        $payload = $request->validate([
            'points' => ['required', 'array'],
            'points.*.latitude' => ['required', 'numeric', 'between:-90,90'],
            'points.*.longitude' => ['required', 'numeric', 'between:-180,180'],
            'points.*.speed' => ['nullable', 'numeric', 'min:0'],
            'points.*.heading' => ['nullable', 'numeric', 'between:0,360'],
            'points.*.recorded_at' => ['nullable', 'date'],
        ]);

        $waybill = $this->activeWaybill($request);
        $points = collect($payload['points'])
            ->map(fn (array $point) => $this->gps->storePoint($waybill, $point))
            ->values();

        return response()->json(['gps_points' => $points], 201);
    }

    private function validatePoint(Request $request): array
    {
        return $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'speed' => ['nullable', 'numeric', 'min:0'],
            'heading' => ['nullable', 'numeric', 'between:0,360'],
            'recorded_at' => ['nullable', 'date'],
        ]);
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

