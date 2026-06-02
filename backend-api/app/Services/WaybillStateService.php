<?php

namespace App\Services;

use App\Enums\WaybillStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Driver;
use App\Models\Waybill;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WaybillStateService
{
    public function __construct(private readonly OdometerControlService $odometerControl)
    {
    }

    public function findCurrentWorkOrder(Driver $driver): ?WorkOrder
    {
        return WorkOrder::query()
            ->where('driver_id', $driver->id)
            ->whereDate('date', now()->toDateString())
            ->whereIn('status', [WorkOrderStatus::Planned->value, WorkOrderStatus::Active->value])
            ->with(['driver', 'vehicle'])
            ->first();
    }

    public function openFromWorkOrder(Driver $driver, WorkOrder $workOrder): Waybill
    {
        if ($workOrder->driver_id !== $driver->id) {
            throw new InvalidArgumentException('План-наряд назначен другому водителю.');
        }

        return DB::transaction(function () use ($workOrder) {
            $waybill = Waybill::query()->firstOrCreate(
                ['work_order_id' => $workOrder->id],
                [
                    'number' => $this->generateNumber(),
                    'date' => $workOrder->date,
                    'organization_name' => 'ООО «АЗЫК»',
                    'driver_id' => $workOrder->driver_id,
                    'vehicle_id' => $workOrder->vehicle_id,
                    'route_name' => $workOrder->route_name,
                    'status' => WaybillStatus::Opened,
                    'odometer_start' => null,
                    'opened_at' => now(),
                ],
            );

            $workOrder->update(['status' => WorkOrderStatus::Active]);

            return $waybill->refresh();
        });
    }

    public function transition(Waybill $waybill, WaybillStatus $target): Waybill
    {
        $current = $waybill->status instanceof WaybillStatus
            ? $waybill->status
            : WaybillStatus::from($waybill->status);

        $allowed = $this->allowedTransitions()[$current->value] ?? [];

        if (! in_array($target->value, $allowed, true)) {
            throw new InvalidArgumentException("Переход {$current->value} -> {$target->value} запрещен.");
        }

        $waybill->update(['status' => $target]);

        return $waybill->refresh();
    }

    public function markInitialPrinted(Waybill $waybill): Waybill
    {
        $this->ensureStatus($waybill, WaybillStatus::InitialPrintPending);

        $waybill->update([
            'status' => WaybillStatus::InitialPrinted,
            'initial_printed_at' => now(),
        ]);

        return $waybill->refresh();
    }

    public function startShift(Waybill $waybill): Waybill
    {
        $this->ensureStatus($waybill, WaybillStatus::InitialPrinted);
        $this->ensureOdometerCaptureConfirmed($waybill, 'start');

        $waybill->update([
            'status' => WaybillStatus::ShiftInProgress,
            'shift_started_at' => now(),
        ]);

        $waybill->vehicle()->update(['status' => 'on_line']);

        return $waybill->refresh();
    }

    public function finishTrip(Waybill $waybill): Waybill
    {
        $this->ensureStatus($waybill, WaybillStatus::ShiftInProgress);

        $waybill->update([
            'status' => WaybillStatus::ReturnStarted,
            'shift_finished_at' => now(),
        ]);

        return $waybill->refresh();
    }

    public function markFinalPrinted(Waybill $waybill): Waybill
    {
        $this->ensureStatus($waybill, WaybillStatus::FinalPrintPending);

        $waybill->update([
            'status' => WaybillStatus::FinalPrinted,
            'final_printed_at' => now(),
        ]);

        return $waybill->refresh();
    }

    public function closeShift(Waybill $waybill): Waybill
    {
        return DB::transaction(function () use ($waybill) {
            $this->ensureStatus($waybill, WaybillStatus::FinalPrinted);
            $this->odometerControl->ensureCanClose($waybill);

            $waybill->update([
                'status' => WaybillStatus::Closed,
                'closed_at' => now(),
            ]);

            $waybill->workOrder()->update(['status' => WorkOrderStatus::Completed]);
            $waybill->vehicle()->update([
                'status' => 'available',
                'current_mileage' => $waybill->odometer_end ?? $waybill->vehicle->current_mileage,
            ]);

            return $waybill->refresh();
        });
    }

    public function ensureStatus(Waybill $waybill, WaybillStatus $expected): void
    {
        $actual = $waybill->status instanceof WaybillStatus
            ? $waybill->status
            : WaybillStatus::from($waybill->status);

        if ($actual !== $expected) {
            throw new InvalidArgumentException("Ожидался статус {$expected->value}, текущий статус {$actual->value}.");
        }
    }

    public function workflowPayload(?Waybill $waybill, ?WorkOrder $workOrder): array
    {
        if (! $waybill) {
            return [
                'step' => $workOrder ? 'work_order_found' : 'no_work_order',
                'work_order' => $workOrder,
                'waybill' => null,
                'message' => $workOrder ? null : 'На текущую смену отсутствует план-наряд',
            ];
        }

        $status = $waybill->status instanceof WaybillStatus
            ? $waybill->status
            : WaybillStatus::from($waybill->status);

        $waybill->load(['driver', 'vehicle', 'workOrder', 'fuelLogs', 'odometerCaptures.file']);
        $step = $status->mobileStep();

        if ($status === WaybillStatus::Opened && ! $this->odometerControl->hasConfirmedCapture($waybill, 'start')) {
            $step = 'start_odometer';
        }

        if ($status === WaybillStatus::ReturnStarted && ! $this->odometerControl->hasConfirmedCapture($waybill, 'finish')) {
            $step = 'finish_odometer';
        }

        return [
            'step' => $step,
            'waybill' => $waybill,
            'work_order' => $workOrder,
            'blocked' => $status->blocksDriver(),
            'odometer_control' => $this->odometerControl->controlPayload($waybill),
        ];
    }

    public function ensureOdometerCaptureConfirmed(Waybill $waybill, string $captureType): void
    {
        if (! $this->odometerControl->hasConfirmedCapture($waybill, $captureType)) {
            $label = $captureType === 'start' ? 'начального' : 'конечного';

            throw new InvalidArgumentException("Необходимо подтвердить фото {$label} одометра.");
        }
    }

    private function generateNumber(): string
    {
        return 'PL-' . now()->format('Ymd') . '-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    private function allowedTransitions(): array
    {
        return [
            WaybillStatus::Opened->value => [WaybillStatus::PreMedRequested->value],
            WaybillStatus::PreMedRequested->value => [
                WaybillStatus::PreMedApproved->value,
                WaybillStatus::PreMedRejected->value,
            ],
            WaybillStatus::PreMedApproved->value => [WaybillStatus::PreTechRequested->value],
            WaybillStatus::PreTechRequested->value => [
                WaybillStatus::PreTechApproved->value,
                WaybillStatus::PreTechRejected->value,
            ],
            WaybillStatus::PreTechApproved->value => [WaybillStatus::InitialPrintPending->value],
            WaybillStatus::InitialPrintPending->value => [WaybillStatus::InitialPrinted->value],
            WaybillStatus::InitialPrinted->value => [WaybillStatus::ShiftInProgress->value],
            WaybillStatus::ShiftInProgress->value => [WaybillStatus::ReturnStarted->value],
            WaybillStatus::ReturnStarted->value => [WaybillStatus::PostMedRequested->value],
            WaybillStatus::PostMedRequested->value => [
                WaybillStatus::PostMedApproved->value,
                WaybillStatus::PostMedRejected->value,
            ],
            WaybillStatus::PostMedApproved->value => [WaybillStatus::PostTechRequested->value],
            WaybillStatus::PostTechRequested->value => [
                WaybillStatus::PostTechApproved->value,
                WaybillStatus::PostTechRejected->value,
            ],
            WaybillStatus::PostTechApproved->value => [WaybillStatus::FinalPrintPending->value],
            WaybillStatus::FinalPrintPending->value => [WaybillStatus::FinalPrinted->value],
            WaybillStatus::FinalPrinted->value => [WaybillStatus::Closed->value],
        ];
    }
}
