<?php

namespace App\Http\Controllers\Mobile;

use App\Enums\WaybillStatus;
use App\Http\Controllers\Controller;
use App\Models\Waybill;
use App\Models\WaybillOdometerCapture;
use App\Services\OdometerControlService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OdometerCaptureController extends Controller
{
    public function __construct(private readonly OdometerControlService $odometerControl)
    {
    }

    public function store(Request $request, Waybill $waybill)
    {
        $this->ensureDriverWaybill($request, $waybill);

        $payload = $request->validate([
            'capture_type' => ['required', Rule::in(['start', 'finish'])],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $this->ensureCaptureAllowed($waybill, $payload['capture_type']);

        $capture = $this->odometerControl->storeCapture(
            $waybill,
            $payload['image'],
            $payload['capture_type'],
            $request->user(),
        );

        return response()->json([
            'capture' => $capture,
            'odometer_control' => $this->odometerControl->controlPayload($waybill->refresh()),
        ], 201);
    }

    public function confirm(Request $request, Waybill $waybill, WaybillOdometerCapture $capture)
    {
        $this->ensureDriverWaybill($request, $waybill);

        $payload = $request->validate([
            'confirmed_value' => ['required', 'integer', 'min:0'],
        ]);

        $capture = $this->odometerControl->confirmCapture(
            $waybill,
            $capture,
            $payload['confirmed_value'],
            $request->user(),
        );

        return response()->json([
            'capture' => $capture,
            'odometer_control' => $this->odometerControl->controlPayload($waybill->refresh()),
        ]);
    }

    public function control(Request $request, Waybill $waybill)
    {
        $this->ensureDriverWaybill($request, $waybill);

        return response()->json([
            'odometer_control' => $this->odometerControl->controlPayload($waybill),
        ]);
    }

    private function ensureDriverWaybill(Request $request, Waybill $waybill): void
    {
        if ($waybill->driver_id !== $request->user()->driver?->id) {
            throw ValidationException::withMessages([
                'waybill' => 'Путевой лист не относится к текущему водителю.',
            ]);
        }
    }

    private function ensureCaptureAllowed(Waybill $waybill, string $captureType): void
    {
        $status = $waybill->status instanceof WaybillStatus
            ? $waybill->status
            : WaybillStatus::from($waybill->status);

        if (in_array($status, [WaybillStatus::Closed, WaybillStatus::Cancelled], true)) {
            throw ValidationException::withMessages([
                'waybill' => 'Путевой лист уже закрыт или отменен.',
            ]);
        }

        if ($captureType === 'finish' && $status === WaybillStatus::Opened) {
            throw ValidationException::withMessages([
                'capture_type' => 'Конечный одометр фиксируется после завершения рейса.',
            ]);
        }
    }
}
