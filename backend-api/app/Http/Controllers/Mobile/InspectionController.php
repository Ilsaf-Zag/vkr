<?php

namespace App\Http\Controllers\Mobile;

use App\Enums\InspectionType;
use App\Enums\WaybillStatus;
use App\Http\Controllers\Controller;
use App\Models\Waybill;
use App\Services\InspectionService;
use Illuminate\Http\Request;

class InspectionController extends Controller
{
    public function __construct(private readonly InspectionService $inspections)
    {
    }

    public function requestMedical(Request $request)
    {
        $payload = $request->validate([
            'type' => ['required', 'in:pre_trip,post_trip'],
        ]);

        $inspection = $this->inspections->requestMedical(
            $this->activeWaybill($request),
            InspectionType::from($payload['type']),
        );

        return response()->json(['inspection' => $inspection], 201);
    }

    public function requestTechnical(Request $request)
    {
        $payload = $request->validate([
            'type' => ['required', 'in:pre_trip,post_trip'],
        ]);

        $inspection = $this->inspections->requestTechnical(
            $this->activeWaybill($request),
            InspectionType::from($payload['type']),
        );

        return response()->json(['inspection' => $inspection], 201);
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

