<?php

namespace App\Services;

use App\Enums\InspectionStatus;
use App\Enums\InspectionType;
use App\Enums\WaybillStatus;
use App\Models\MedicalInspection;
use App\Models\TechnicalInspection;
use App\Models\User;
use App\Models\Waybill;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InspectionService
{
    public function __construct(private readonly WaybillStateService $waybillState)
    {
    }

    public function requestMedical(Waybill $waybill, InspectionType $type): MedicalInspection
    {
        $expected = $type === InspectionType::PreTrip
            ? WaybillStatus::Opened
            : WaybillStatus::ReturnStarted;

        $target = $type === InspectionType::PreTrip
            ? WaybillStatus::PreMedRequested
            : WaybillStatus::PostMedRequested;

        return DB::transaction(function () use ($waybill, $type, $expected, $target) {
            $this->waybillState->ensureStatus($waybill, $expected);
            $this->waybillState->transition($waybill, $target);

            return MedicalInspection::query()->create([
                'waybill_id' => $waybill->id,
                'driver_id' => $waybill->driver_id,
                'type' => $type,
                'status' => InspectionStatus::Pending,
                'requested_at' => now(),
            ]);
        });
    }

    public function decideMedical(MedicalInspection $inspection, User $medic, bool $approved, ?string $reason = null): MedicalInspection
    {
        if ($inspection->status !== InspectionStatus::Pending) {
            throw new InvalidArgumentException('Заявка на медосмотр уже обработана.');
        }

        return DB::transaction(function () use ($inspection, $medic, $approved, $reason) {
            $target = match ([$inspection->type, $approved]) {
                [InspectionType::PreTrip, true] => WaybillStatus::PreMedApproved,
                [InspectionType::PreTrip, false] => WaybillStatus::PreMedRejected,
                [InspectionType::PostTrip, true] => WaybillStatus::PostMedApproved,
                [InspectionType::PostTrip, false] => WaybillStatus::PostMedRejected,
            };

            $inspection->update([
                'status' => $approved ? InspectionStatus::Approved : InspectionStatus::Rejected,
                'decided_at' => now(),
                'medic_id' => $medic->id,
                'rejection_reason' => $approved ? null : $reason,
            ]);

            $this->waybillState->transition($inspection->waybill, $target);

            return $inspection->refresh();
        });
    }

    public function requestTechnical(Waybill $waybill, InspectionType $type): TechnicalInspection
    {
        $expected = $type === InspectionType::PreTrip
            ? WaybillStatus::PreMedApproved
            : WaybillStatus::PostMedApproved;

        $target = $type === InspectionType::PreTrip
            ? WaybillStatus::PreTechRequested
            : WaybillStatus::PostTechRequested;

        return DB::transaction(function () use ($waybill, $type, $expected, $target) {
            $this->waybillState->ensureStatus($waybill, $expected);
            $this->waybillState->transition($waybill, $target);

            return TechnicalInspection::query()->create([
                'waybill_id' => $waybill->id,
                'driver_id' => $waybill->driver_id,
                'vehicle_id' => $waybill->vehicle_id,
                'type' => $type,
                'status' => InspectionStatus::Pending,
                'requested_at' => now(),
            ]);
        });
    }

    public function decideTechnical(TechnicalInspection $inspection, User $mechanic, bool $approved, ?string $reason = null): TechnicalInspection
    {
        if ($inspection->status !== InspectionStatus::Pending) {
            throw new InvalidArgumentException('Заявка на техосмотр уже обработана.');
        }

        return DB::transaction(function () use ($inspection, $mechanic, $approved, $reason) {
            $target = match ([$inspection->type, $approved]) {
                [InspectionType::PreTrip, true] => WaybillStatus::PreTechApproved,
                [InspectionType::PreTrip, false] => WaybillStatus::PreTechRejected,
                [InspectionType::PostTrip, true] => WaybillStatus::PostTechApproved,
                [InspectionType::PostTrip, false] => WaybillStatus::PostTechRejected,
            };

            $inspection->update([
                'status' => $approved ? InspectionStatus::Approved : InspectionStatus::Rejected,
                'decided_at' => now(),
                'mechanic_id' => $mechanic->id,
                'rejection_reason' => $approved ? null : $reason,
            ]);

            $this->waybillState->transition($inspection->waybill, $target);

            if ($approved) {
                $next = $inspection->type === InspectionType::PreTrip
                    ? WaybillStatus::InitialPrintPending
                    : WaybillStatus::FinalPrintPending;

                $this->waybillState->transition($inspection->waybill->refresh(), $next);
            }

            return $inspection->refresh();
        });
    }
}

