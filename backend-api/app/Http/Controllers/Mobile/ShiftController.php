<?php

namespace App\Http\Controllers\Mobile;

use App\Enums\WaybillStatus;
use App\Http\Controllers\Controller;
use App\Models\Waybill;
use App\Services\WaybillStateService;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function __construct(private readonly WaybillStateService $waybills)
    {
    }

    public function initialPrintDone(Request $request)
    {
        return response()->json([
            'waybill' => $this->waybills->markInitialPrinted($this->activeWaybill($request)),
        ]);
    }

    public function start(Request $request)
    {
        return response()->json([
            'waybill' => $this->waybills->startShift($this->activeWaybill($request)),
        ]);
    }

    public function finishTrip(Request $request)
    {
        $payload = $request->validate([
            'odometer_end' => ['nullable', 'integer', 'min:0'],
        ]);

        return response()->json([
            'waybill' => $this->waybills->finishTrip($this->activeWaybill($request), $payload['odometer_end'] ?? null),
        ]);
    }

    public function finalPrintDone(Request $request)
    {
        return response()->json([
            'waybill' => $this->waybills->markFinalPrinted($this->activeWaybill($request)),
        ]);
    }

    public function close(Request $request)
    {
        $waybill = $this->waybills->closeShift($this->activeWaybill($request));
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'waybill' => $waybill,
            'message' => 'Смена закрыта, локальную сессию можно очистить.',
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

