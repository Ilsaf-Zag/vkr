<?php

namespace App\Http\Controllers\Mobile;

use App\Enums\WaybillStatus;
use App\Http\Controllers\Controller;
use App\Models\Waybill;
use App\Services\WaybillStateService;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function __construct(private readonly WaybillStateService $waybills)
    {
    }

    public function currentWorkOrder(Request $request)
    {
        $driver = $request->user()->driver;
        $workOrder = $this->waybills->findCurrentWorkOrder($driver);

        if (! $workOrder) {
            return response()->json([
                'message' => 'На текущую смену отсутствует план-наряд',
            ], 404);
        }

        return response()->json(['work_order' => $workOrder]);
    }

    public function workflow(Request $request)
    {
        $driver = $request->user()->driver;
        $workOrder = $this->waybills->findCurrentWorkOrder($driver);
        $waybill = $this->activeWaybill($driver->id);

        return response()->json($this->waybills->workflowPayload($waybill, $workOrder));
    }

    public function openWaybill(Request $request)
    {
        $payload = $request->validate([
            'odometer_start' => ['nullable', 'integer', 'min:0'],
        ]);

        $driver = $request->user()->driver;
        $workOrder = $this->waybills->findCurrentWorkOrder($driver);

        if (! $workOrder) {
            return response()->json([
                'message' => 'На текущую смену отсутствует план-наряд',
            ], 422);
        }

        $waybill = $this->waybills->openFromWorkOrder($driver, $workOrder, $payload['odometer_start'] ?? null);

        return response()->json(['waybill' => $waybill], 201);
    }

    private function activeWaybill(int $driverId): ?Waybill
    {
        return Waybill::query()
            ->where('driver_id', $driverId)
            ->whereNotIn('status', [WaybillStatus::Closed->value, WaybillStatus::Cancelled->value])
            ->latest('id')
            ->first();
    }
}

