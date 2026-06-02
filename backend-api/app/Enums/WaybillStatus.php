<?php

namespace App\Enums;

enum WaybillStatus: string
{
    case Opened = 'opened';
    case PreMedRequested = 'pre_med_requested';
    case PreMedRejected = 'pre_med_rejected';
    case PreMedApproved = 'pre_med_approved';
    case PreTechRequested = 'pre_tech_requested';
    case PreTechRejected = 'pre_tech_rejected';
    case PreTechApproved = 'pre_tech_approved';
    case InitialPrintPending = 'initial_print_pending';
    case InitialPrinted = 'initial_printed';
    case ShiftStarted = 'shift_started';
    case ShiftInProgress = 'shift_in_progress';
    case ReturnStarted = 'return_started';
    case PostMedRequested = 'post_med_requested';
    case PostMedRejected = 'post_med_rejected';
    case PostMedApproved = 'post_med_approved';
    case PostTechRequested = 'post_tech_requested';
    case PostTechRejected = 'post_tech_rejected';
    case PostTechApproved = 'post_tech_approved';
    case FinalPrintPending = 'final_print_pending';
    case FinalPrinted = 'final_printed';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function blocksDriver(): bool
    {
        return in_array($this, [
            self::PreMedRejected,
            self::PreTechRejected,
            self::PostMedRejected,
            self::PostTechRejected,
            self::Closed,
            self::Cancelled,
        ], true);
    }

    public function mobileStep(): string
    {
        return match ($this) {
            self::Opened => 'pre_trip_medical',
            self::PreMedRequested => 'pre_trip_medical_waiting',
            self::PreMedRejected => 'pre_trip_medical_rejected',
            self::PreMedApproved => 'pre_trip_technical',
            self::PreTechRequested => 'pre_trip_technical_waiting',
            self::PreTechRejected => 'pre_trip_technical_rejected',
            self::PreTechApproved,
            self::InitialPrintPending => 'initial_print',
            self::InitialPrinted => 'start_shift',
            self::ShiftStarted,
            self::ShiftInProgress => 'active_shift',
            self::ReturnStarted => 'post_trip_medical',
            self::PostMedRequested => 'post_trip_medical_waiting',
            self::PostMedRejected => 'post_trip_medical_rejected',
            self::PostMedApproved => 'post_trip_technical',
            self::PostTechRequested => 'post_trip_technical_waiting',
            self::PostTechRejected => 'post_trip_technical_rejected',
            self::PostTechApproved,
            self::FinalPrintPending => 'final_print',
            self::FinalPrinted => 'close_shift',
            self::Closed => 'closed',
            self::Cancelled => 'cancelled',
        };
    }
}
