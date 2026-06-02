<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Waybill;
use App\Services\OdometerControlService;
use App\Services\PdfService;
use Illuminate\Http\Request;

class WaybillController extends Controller
{
    public function index(Request $request)
    {
        $query = Waybill::query()
            ->with(['driver', 'vehicle', 'workOrder'])
            ->latest('date')
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($query) use ($q) {
                $query->where('number', 'ilike', "%{$q}%")
                    ->orWhere('route_name', 'ilike', "%{$q}%")
                    ->orWhereHas('driver', fn ($driver) => $driver->where('full_name', 'ilike', "%{$q}%"))
                    ->orWhereHas('vehicle', fn ($vehicle) => $vehicle->where('plate_number', 'ilike', "%{$q}%"));
            });
        }

        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->integer('driver_id'));
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->integer('vehicle_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date('date_to'));
        }

        return response()->json(['items' => $query->paginate(20)]);
    }

    public function show(Waybill $waybill)
    {
        return response()->json([
            'waybill' => $waybill->load([
                'driver',
                'vehicle',
                'workOrder',
                'medicalInspections',
                'technicalInspections',
                'fuelLogs',
                'odometerCaptures.file',
                'odometerCaptures.confirmedBy',
            ]),
            'odometer_control' => app(OdometerControlService::class)->controlPayload($waybill),
        ]);
    }

    public function odometerControl(Waybill $waybill, OdometerControlService $odometerControl)
    {
        return response()->json([
            'odometer_control' => $odometerControl->controlPayload($waybill),
        ]);
    }

    public function initialPdf(Waybill $waybill, PdfService $pdf)
    {
        return $pdf->initialWaybill($waybill)->stream("waybill-{$waybill->number}.pdf");
    }

    public function finalPdf(Waybill $waybill, PdfService $pdf)
    {
        return $pdf->finalWaybill($waybill)->stream("waybill-final-{$waybill->number}.pdf");
    }
}
