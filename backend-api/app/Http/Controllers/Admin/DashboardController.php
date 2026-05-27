<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InspectionStatus;
use App\Enums\WaybillStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FuelLog;
use App\Models\MedicalInspection;
use App\Models\TechnicalInspection;
use App\Models\Vehicle;
use App\Models\Waybill;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'active_shifts' => Waybill::query()->where('status', WaybillStatus::ShiftInProgress->value)->count(),
            'vehicles_on_line' => Vehicle::query()->where('status', 'on_line')->count(),
            'pending_medical_inspections' => MedicalInspection::query()->where('status', InspectionStatus::Pending->value)->count(),
            'pending_technical_inspections' => TechnicalInspection::query()->where('status', InspectionStatus::Pending->value)->count(),
            'today_waybills' => Waybill::query()->whereDate('date', today())->count(),
            'today_fuel_logs' => FuelLog::query()->whereDate('fueled_at', today())->count(),
            'latest_actions' => AuditLog::query()->latest('created_at')->limit(10)->get(),
        ]);
    }
}
